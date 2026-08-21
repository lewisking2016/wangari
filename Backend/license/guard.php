<?php
/**
 * Wangari License Guard — PHP Side
 *
 * Called once at boot (from database.php or router.php) when running in
 * desktop mode (WANGARI_MODE=desktop).
 *
 * Flow:
 *   1. Read JWT from WANGARI_LICENSE_TOKEN env var (injected by Electron main.js)
 *   2. Verify JWT signature using the bundled RSA public key
 *   3. Check hardware_id claim matches WANGARI_HW_ID env var
 *   4. Check `exp` claim has not expired
 *   5. On any failure → 403 JSON response and exit
 *
 * On web/cloud mode (WANGARI_MODE != desktop) this file is a no-op.
 * Authentication is handled by session-based login instead.
 */
declare(strict_types=1);

function wangari_check_desktop_license(): void
{
    $mode = $_ENV['WANGARI_MODE'] ?? getenv('WANGARI_MODE') ?? '';
    if ($mode !== 'desktop') {
        return; // Web mode — license guard not applicable here
    }

    $token = $_ENV['WANGARI_LICENSE_TOKEN'] ?? getenv('WANGARI_LICENSE_TOKEN') ?? '';
    $hwId  = $_ENV['WANGARI_HW_ID']         ?? getenv('WANGARI_HW_ID')         ?? '';

    if (empty($token)) {
        wangari_license_deny('No license token present. Please restart the application.');
    }

    // ── JWT decode (pure PHP, no external library needed) ─────────────────────
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        wangari_license_deny('Malformed license token.');
    }

    [$headerB64, $payloadB64, $sigB64] = $parts;

    $header  = json_decode(base64url_decode($headerB64), true);
    $payload = json_decode(base64url_decode($payloadB64), true);
    $sig     = base64url_decode($sigB64);

    if (!$header || !$payload || $sig === false) {
        wangari_license_deny('License token could not be decoded.');
    }

    // ── Signature verification (RS256 using bundled public key) ───────────────
    $pubKeyPath = __DIR__ . '/license_public.pem';
    if (!file_exists($pubKeyPath)) {
        // DEV MODE: If no public key exists yet, allow desktop access.
        // Remove this block in production — the key MUST exist.
        if (($_ENV['WANGARI_DEV'] ?? getenv('WANGARI_DEV')) === '1') {
            return; // Dev bypass — skip signature check
        }
        wangari_license_deny('License public key not found. Installation may be corrupt.');
    }

    $pubKey = openssl_pkey_get_public(file_get_contents($pubKeyPath));
    if (!$pubKey) {
        wangari_license_deny('Could not load license public key.');
    }

    $dataToVerify = $headerB64 . '.' . $payloadB64;
    $algo = strtoupper($header['alg'] ?? '');

    $verified = false;
    if ($algo === 'RS256') {
        $verified = openssl_verify($dataToVerify, $sig, $pubKey, OPENSSL_ALGO_SHA256) === 1;
    } elseif ($algo === 'HS256') {
        // Fallback: HMAC-SHA256 (for simpler deployments)
        $secret   = $_ENV['WANGARI_JWT_SECRET'] ?? getenv('WANGARI_JWT_SECRET') ?? '';
        $expected = hash_hmac('sha256', $dataToVerify, $secret, true);
        $verified = hash_equals($expected, $sig);
    }

    if (!$verified) {
        wangari_license_deny('License signature invalid. The license may have been tampered with.');
    }

    // ── Claims validation ──────────────────────────────────────────────────────
    $now = time();

    // Expiry
    if (isset($payload['exp']) && $now > (int)$payload['exp']) {
        wangari_license_deny('License has expired. Please reconnect to the internet to renew.');
    }

    // Not-before
    if (isset($payload['nbf']) && $now < (int)$payload['nbf']) {
        wangari_license_deny('License is not yet valid.');
    }

    // Hardware fingerprint binding
    if (!empty($hwId) && isset($payload['hardware_id'])) {
        if (!hash_equals($payload['hardware_id'], $hwId)) {
            wangari_license_deny('License is not valid for this device.');
        }
    }

    // License must not be revoked (optional: check `status` claim)
    if (isset($payload['status']) && $payload['status'] !== 'active') {
        wangari_license_deny('License has been revoked. Please contact support.');
    }

    // All checks passed — store plan info for use elsewhere
    if (!defined('WANGARI_LICENSE_PLAN')) {
        define('WANGARI_LICENSE_PLAN', $payload['plan'] ?? 'basic');
    }
}

/**
 * Deny access with a 403 response and terminate.
 */
function wangari_license_deny(string $reason): never
{
    // Only send headers if not already sent
    if (!headers_sent()) {
        http_response_code(403);
        header('Content-Type: application/json');
    }
    echo json_encode([
        'error'   => 'license_denied',
        'message' => $reason,
        'action'  => 'reactivate',
    ]);
    exit(0);
}

/**
 * Base64url decode (JWT-compatible, no padding required)
 */
function base64url_decode(string $data)
{
    $padded = $data . str_repeat('=', (4 - strlen($data) % 4) % 4);
    return base64_decode(strtr($padded, '-_', '+/'));
}

// ── Auto-run when desktop mode is active ──────────────────────────────────────
wangari_check_desktop_license();

