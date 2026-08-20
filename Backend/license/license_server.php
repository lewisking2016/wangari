<?php
/**
 * Wangari License Server API
 * Endpoint: POST /api/license/activate
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * DEPLOY THIS FILE TO YOUR WEB HOSTING (e.g. license.wangari.app)
 * NOT inside the desktop app bundle.
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Database table required (MySQL):
 *   CREATE TABLE wangari_licenses (
 *     id            INT AUTO_INCREMENT PRIMARY KEY,
 *     license_key   VARCHAR(64)  NOT NULL UNIQUE,
 *     customer_name VARCHAR(255) DEFAULT NULL,
 *     customer_email VARCHAR(255) DEFAULT NULL,
 *     plan          VARCHAR(50)  DEFAULT 'basic',
 *     status        ENUM('active','expired','revoked') DEFAULT 'active',
 *     hardware_id   VARCHAR(128) DEFAULT NULL,
 *     activations   INT          DEFAULT 0,
 *     max_devices   INT          DEFAULT 1,
 *     expires_at    DATETIME     DEFAULT NULL,
 *     created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
 *     last_seen     DATETIME     DEFAULT NULL
 *   );
 *
 * RSA keys: generate once with:
 *   openssl genrsa -out license_private.pem 2048
 *   openssl rsa -in license_private.pem -pubout -out license_public.pem
 * Store private.pem OUTSIDE web root. Distribute public.pem inside the app.
 */
declare(strict_types=1);
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// ── Config ────────────────────────────────────────────────────────────────────
$DB_HOST = $_ENV['LIC_DB_HOST'] ?? 'localhost';
$DB_NAME = $_ENV['LIC_DB_NAME'] ?? 'wangari_licenses';
$DB_USER = $_ENV['LIC_DB_USER'] ?? 'root';
$DB_PASS = $_ENV['LIC_DB_PASS'] ?? '';
$PRIVATE_KEY_PATH = $_ENV['LIC_PRIVATE_KEY'] ?? __DIR__ . '/../../private/license_private.pem';

// ── Request validation ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$licenseKey  = trim($body['license_key']  ?? '');
$hardwareId  = trim($body['hardware_id']  ?? '');
$appVersion  = trim($body['app_version']  ?? '');

if (empty($licenseKey) || empty($hardwareId)) {
    http_response_code(400);
    echo json_encode(['error' => 'license_key and hardware_id are required']);
    exit;
}

// Basic format validation (XXXX-XXXX-XXXX-XXXX or similar)
if (!preg_match('/^[A-Z0-9\-]{6,64}$/i', $licenseKey)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid license key format']);
    exit;
}

// ── Database ──────────────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(503);
    echo json_encode(['error' => 'Service temporarily unavailable']);
    error_log('[license-server] DB error: ' . $e->getMessage());
    exit;
}

// ── Lookup license ────────────────────────────────────────────────────────────
$stmt = $pdo->prepare('SELECT * FROM wangari_licenses WHERE license_key = ? LIMIT 1');
$stmt->execute([$licenseKey]);
$lic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lic) {
    http_response_code(404);
    echo json_encode(['error' => 'License key not found. Please purchase a valid license.']);
    exit;
}

// ── Status checks ─────────────────────────────────────────────────────────────
if ($lic['status'] === 'revoked') {
    http_response_code(403);
    echo json_encode(['error' => 'This license has been revoked. Please contact support.']);
    exit;
}

if ($lic['status'] === 'expired') {
    http_response_code(402);
    echo json_encode(['error' => 'License subscription has expired. Please renew.']);
    exit;
}

// Expiry check (if expires_at is set)
if (!empty($lic['expires_at'])) {
    if (strtotime($lic['expires_at']) < time()) {
        $pdo->prepare("UPDATE wangari_licenses SET status='expired' WHERE id=?")->execute([$lic['id']]);
        http_response_code(402);
        echo json_encode(['error' => 'License has expired. Please renew your subscription.']);
        exit;
    }
}

// ── Hardware fingerprint binding ─────────────────────────────────────────────
if (!empty($lic['hardware_id']) && $lic['hardware_id'] !== $hardwareId) {
    // The key is registered to a different machine
    // Allow if max_devices > 1 (multi-seat license)
    if ((int)$lic['max_devices'] <= 1) {
        // Log suspicious multi-machine use
        error_log("[license-server] Multi-device attempt for key={$licenseKey} hw={$hardwareId}");
        http_response_code(403);
        echo json_encode([
            'error' => 'License is already activated on another device. Please contact support to transfer.',
            'support_url' => 'https://wangari.app/support',
        ]);
        exit;
    }
}

// ── Register hardware ID if first activation ──────────────────────────────────
$update = $pdo->prepare(
    "UPDATE wangari_licenses
     SET hardware_id = COALESCE(hardware_id, ?),
         activations = activations + 1,
         last_seen   = NOW()
     WHERE id = ?"
);
$update->execute([$hardwareId, $lic['id']]);

// ── Sign JWT (RS256) ──────────────────────────────────────────────────────────
if (!file_exists($PRIVATE_KEY_PATH)) {
    // Fallback to HS256 for local dev if no RSA key present
    $secret = $_ENV['WANGARI_JWT_SECRET'] ?? 'CHANGEME_GENERATE_A_REAL_SECRET';
    $jwt = hs256_sign([
        'iss'         => 'wangari-license-server',
        'sub'         => $licenseKey,
        'hardware_id' => $hardwareId,
        'plan'        => $lic['plan'],
        'status'      => 'active',
        'iat'         => time(),
        'exp'         => time() + (14 * 24 * 3600), // 14 days
        'nbf'         => time(),
    ], $secret);
    echo json_encode(['jwt' => $jwt, 'plan' => $lic['plan'], 'alg' => 'HS256']);
    exit;
}

$privateKey = openssl_pkey_get_private(file_get_contents($PRIVATE_KEY_PATH));
if (!$privateKey) {
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error. Please contact support.']);
    exit;
}

$claims = [
    'iss'         => 'wangari-license-server',
    'sub'         => $licenseKey,
    'hardware_id' => $hardwareId,
    'plan'        => $lic['plan'],
    'status'      => 'active',
    'iat'         => time(),
    'exp'         => time() + (14 * 24 * 3600), // 14-day token
    'nbf'         => time(),
];

$jwt = rs256_sign($claims, $privateKey);
echo json_encode(['jwt' => $jwt, 'plan' => $lic['plan'], 'alg' => 'RS256']);

// ── JWT helpers ───────────────────────────────────────────────────────────────
function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function rs256_sign(array $payload, $privateKey): string {
    $header  = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $body    = base64url_encode(json_encode($payload));
    $signing = "$header.$body";
    openssl_sign($signing, $sig, $privateKey, OPENSSL_ALGO_SHA256);
    return "$signing." . base64url_encode($sig);
}

function hs256_sign(array $payload, string $secret): string {
    $header  = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $body    = base64url_encode(json_encode($payload));
    $signing = "$header.$body";
    $sig     = hash_hmac('sha256', $signing, $secret, true);
    return "$signing." . base64url_encode($sig);
}

