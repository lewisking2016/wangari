<?php
/**
 * Desktop App Authentication API
 * Handles login, trial check, and license activation for the desktop app.
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_GET['action'] ?? ($input['action'] ?? 'login');

try {
    switch ($action) {

        // ═══ LOGIN ═══
        case 'login':
            $identifier = trim($input['email'] ?? $input['username'] ?? '');
            $password = $input['password'] ?? '';

            if (!$identifier || !$password) {
                http_response_code(400);
                echo json_encode(['error' => 'Email and password are required']);
                exit;
            }

            // Check both users table and platform_users table
            $user = null;

            // Try users table first (Google/manual registration)
            try {
                $stmt = $pdo->prepare('SELECT id, username, email, password_hash, role, first_name, last_name, created_at FROM users WHERE (email = ? OR username = ?) LIMIT 1');
                $stmt->execute([$identifier, $identifier]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && !password_verify($password, $user['password_hash'])) {
                    $user = null;
                }
            } catch (Exception $e) {
                // users table might not have password_hash column
            }

            // Try platform_users table
            if (!$user) {
                try {
                    $stmt = $pdo->prepare('SELECT id, username, email, password, role, full_name, subscription_status, subscription_expires, trial_ends, created_at FROM platform_users WHERE (email = ? OR username = ?) AND is_active = 1 LIMIT 1');
                    $stmt->execute([$identifier, $identifier]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($user && !password_verify($password, $user['password'])) {
                        $user = null;
                    }
                } catch (Exception $e) {
                    // platform_users table might not exist
                }
            }

            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid email or password']);
                exit;
            }

            // Determine trial/subscription status
            $status = 'trial';
            $trialEnds = null;
            $isTrialActive = false;

            if (isset($user['subscription_status'])) {
                // platform_users table
                $status = $user['subscription_status'];
                $trialEnds = $user['trial_ends'] ?? $user['subscription_expires'] ?? null;
            } else {
                // users table — check if within 40-day trial
                $createdAt = $user['created_at'];
                $trialEndDate = date('Y-m-d', strtotime($createdAt . ' +40 days'));
                $trialEnds = $trialEndDate;
                if (date('Y-m-d') > $trialEndDate) {
                    $status = 'expired';
                } else {
                    $status = 'trial';
                }
            }

            $isTrialActive = ($status === 'trial' || $status === 'active') && (!$trialEnds || date('Y-m-d') <= $trialEnds);
            $isExpired = ($status === 'expired') || ($trialEnds && date('Y-m-d') > $trialEnds);

            // Update last login
            try {
                $pdo->prepare('UPDATE users SET updated_at = NOW() WHERE id = ?')->execute([$user['id']]);
            } catch (Exception $e) { /* ignore */ }

            echo json_encode([
                'ok' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'] ?? ($user['first_name'] . ' ' . $user['last_name']) ?? $user['username'],
                    'role' => $user['role'],
                ],
                'trial' => [
                    'active' => $isTrialActive,
                    'status' => $status,
                    'expires' => $trialEnds,
                    'days_left' => $trialEnds ? max(0, (int) ((strtotime($trialEnds) - time()) / 86400)) : null,
                ],
                'needs_license' => $isExpired,
            ]);
            break;

        // ═══ CHECK LICENSE ═══
        case 'check_license':
            $licenseKey = trim($input['license_key'] ?? '');
            $hardwareId = $input['hardware_id'] ?? '';

            if (!$licenseKey) {
                http_response_code(400);
                echo json_encode(['error' => 'License key is required']);
                exit;
            }

            // Check wangari_licenses table
            try {
                $stmt = $pdo->prepare('SELECT * FROM wangari_licenses WHERE license_key = ? LIMIT 1');
                $stmt->execute([$licenseKey]);
                $license = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$license) {
                    http_response_code(404);
                    echo json_encode(['ok' => false, 'error' => 'Invalid license key']);
                    exit;
                }

                if ($license['status'] === 'revoked') {
                    http_response_code(403);
                    echo json_encode(['ok' => false, 'error' => 'This license has been revoked']);
                    exit;
                }

                if ($license['expires_at'] && date('Y-m-d H:i:s') > $license['expires_at']) {
                    http_response_code(403);
                    echo json_encode(['ok' => false, 'error' => 'This license has expired']);
                    exit;
                }

                $maxDevices = (int)($license['max_devices'] ?? 1);
                $activations = (int)($license['activations'] ?? 0);

                if ($hardwareId && $license['hardware_id'] && $license['hardware_id'] !== $hardwareId) {
                    if ($activations >= $maxDevices) {
                        http_response_code(403);
                        echo json_encode(['ok' => false, 'error' => 'This license is already activated on another device']);
                        exit;
                    }
                }

                // Activate or re-activate
                if ($hardwareId) {
                    $newActivations = max($activations + 1, 1);
                    $pdo->prepare('UPDATE wangari_licenses SET hardware_id = ?, activations = ? WHERE id = ?')
                        ->execute([$hardwareId, $newActivations, $license['id']]);
                }

                echo json_encode([
                    'ok' => true,
                    'plan' => $license['plan'] ?? 'desktop',
                    'expires_at' => $license['expires_at'],
                    'license_key' => $license['license_key'],
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['ok' => false, 'error' => 'License check failed: ' . $e->getMessage()]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
