<?php
/**
 * Desktop License Activation API.
 *
 * One license key activates one desktop install (bound to hardware_id).
 * This endpoint is used by the Electron desktop app.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$licenseKey = strtoupper(trim((string)($body['license_key'] ?? '')));
$hardwareId = trim((string)($body['hardware_id'] ?? ''));
$appVersion = trim((string)($body['app_version'] ?? ''));

if ($licenseKey === '' || $hardwareId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'license_key and hardware_id are required']);
    exit;
}

if (!preg_match('/^[A-Z0-9\-]{6,64}$/', $licenseKey)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid license key format']);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM wangari_licenses WHERE license_key = ? LIMIT 1');
$stmt->execute([$licenseKey]);
$license = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$license) {
    http_response_code(404);
    echo json_encode(['error' => 'License key not found']);
    exit;
}

if (($license['status'] ?? '') === 'revoked') {
    http_response_code(403);
    echo json_encode(['error' => 'License has been revoked']);
    exit;
}

if (!empty($license['expires_at']) && strtotime((string)$license['expires_at']) < time()) {
    $pdo->prepare("UPDATE wangari_licenses SET status='expired' WHERE id=?")->execute([$license['id']]);
    http_response_code(402);
    echo json_encode(['error' => 'License has expired']);
    exit;
}

if (!empty($license['hardware_id']) && !hash_equals((string)$license['hardware_id'], $hardwareId)) {
    if ((int)($license['max_devices'] ?? 1) <= 1) {
        http_response_code(403);
        echo json_encode(['error' => 'License is already activated on another device']);
        exit;
    }
}

$pdo->prepare(
    "UPDATE wangari_licenses
     SET hardware_id = COALESCE(hardware_id, ?),
         activations = activations + 1,
         last_seen = NOW()
     WHERE id = ?"
)->execute([$hardwareId, $license['id']]);

$claims = [
    'iss' => 'wangari-desktop-license',
    'sub' => $licenseKey,
    'user_id' => (int)($license['user_id'] ?? 0),
    'hardware_id' => $hardwareId,
    'plan' => $license['plan'] ?? 'desktop',
    'status' => 'active',
    'app_version' => $appVersion,
    'iat' => time(),
    'nbf' => time(),
    'exp' => time() + (14 * 24 * 60 * 60),
];

$secret = $_ENV['WANGARI_JWT_SECRET'] ?? getenv('WANGARI_JWT_SECRET') ?? 'WANGARI_DESKTOP_LICENSE_SECRET_CHANGE_ME';
$jwt = signHs256($claims, $secret);

echo json_encode([
    'success' => true,
    'jwt' => $jwt,
    'plan' => $claims['plan'],
    'user_id' => $claims['user_id'],
    'expires_at' => $claims['exp'] * 1000,
]);

function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function signHs256(array $payload, string $secret): string
{
    $header = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_UNESCAPED_SLASHES));
    $body = base64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
    $signing = $header . '.' . $body;
    $sig = hash_hmac('sha256', $signing, $secret, true);
    return $signing . '.' . base64url_encode($sig);
}
