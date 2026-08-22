<?php
/**
 * Auth API — handles login for the Wangari frontend.
 * Supports both username/password AND Google OAuth code exchange.
 */
declare(strict_types=1);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/email_policy.php';
$pdo = getDatabaseConnection();

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// ══════ GOOGLE OAUTH LOGIN ══════
$googleCode = $input['google_code'] ?? '';
if (!empty($googleCode)) {
    require __DIR__ . '/../config/google_oauth.php';

    // Exchange code for access token
    $tokenData = getGoogleAccessToken($googleCode);
    if (!$tokenData || !isset($tokenData['access_token'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Google authentication failed. Could not get access token.']);
        exit;
    }

    // Fetch user profile from Google
    $profile = getGoogleUserProfile($tokenData['access_token']);
    if (!$profile || !isset($profile['email'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Could not fetch your Google profile.']);
        exit;
    }

    $googleId  = $profile['sub'] ?? '';
    $email     = $profile['email'];
    $firstName = $profile['given_name'] ?? '';
    $lastName  = $profile['family_name'] ?? '';
    $picture   = $profile['picture'] ?? '';

    if (!wangariIsAllowedEmail($email)) {
        http_response_code(403);
        echo json_encode(['error' => 'Only Gmail and Outlook email addresses are allowed.']);
        exit;
    }

    try {
        // Find existing user by Google ID first, then by email variants
        $stmt = $pdo->prepare('SELECT id, username, email, role, full_name, google_id, profile_pic, is_active FROM users WHERE google_id = ? LIMIT 1');
        $stmt->execute([$googleId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $variants = wangariEmailVariants($email);
            $placeholders = implode(',', array_fill(0, count($variants), '?'));
            $stmt = $pdo->prepare("SELECT id, username, email, role, full_name, google_id, profile_pic, is_active FROM users WHERE LOWER(email) IN ($placeholders) LIMIT 1");
            $stmt->execute($variants);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($user) {
            if (!empty($user['is_active']) && (int)$user['is_active'] !== 1) {
                http_response_code(403);
                echo json_encode(['error' => 'This account is inactive.']);
                exit;
            }

            // Update Google ID and profile pic if missing
            $updates = [];
            $params = [];
            if (empty($user['google_id'])) {
                $updates[] = 'google_id = ?';
                $params[] = $googleId;
            }
            if (!empty($picture) && ($user['profile_pic'] ?? '') !== $picture) {
                $updates[] = 'profile_pic = ?';
                $params[] = $picture;
            }
            if (!empty($updates)) {
                $params[] = $user['id'];
                $pdo->prepare('UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?')->execute($params);
            }
        } else {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['google_registration_profile'] = [
                'google_id' => $googleId,
                'email' => $email,
                'full_name' => trim($firstName . ' ' . $lastName),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'profile_pic' => $picture,
                'flow' => 'register',
            ];

            http_response_code(401);
            echo json_encode([
                'error' => 'No local account matches this Google email. Please register first, then choose Farm Owner or Join as Worker to finish connecting your Google account.',
                'register_required' => true,
                'redirect' => '/Frontend/pages/register.php?google=required',
            ]);
            exit;
        }

        // Start session (do NOT override Redis session path)
        if (session_status() === PHP_SESSION_NONE) session_start();

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['email']      = $user['email'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['full_name']  = $user['full_name'] ?? $user['username'];
        unset($_SESSION['google_registration_profile']);

        // Role-based redirect
        $redirect = '/Frontend/admin/dashboard.php';
        if (($user['role'] ?? '') === 'super_admin') {
            $redirect = '/Frontend/admin/super_admin.php';
        } elseif (($user['role'] ?? '') === 'customer') {
            $redirect = '/Frontend/index.php';
        }

        echo json_encode([
            'success' => true,
            'user' => [
                'id'         => $user['id'],
                'username'   => $user['username'],
                'email'      => $user['email'],
                'role'       => $user['role'],
                'full_name'  => $user['full_name'] ?? $user['username'],
                'profile_pic'=> $picture ?: ($user['profile_pic'] ?? ''),
            ],
            'redirect' => $redirect,
            'session_id' => session_id(),
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Google login failed: ' . $e->getMessage()]);
    }
    exit;
}

// ══════ USERNAME / PASSWORD LOGIN ══════
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password required']);
    exit;
}

try {
    // Try to find user by email or username
    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $variants = wangariEmailVariants($username);
        $placeholders = implode(',', array_fill(0, count($variants), '?'));
        $stmt = $pdo->prepare("SELECT id, username, email, password, role, full_name FROM users WHERE LOWER(email) IN ($placeholders) LIMIT 1");
        $stmt->execute($variants);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare('SELECT id, username, email, password, role, full_name FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid username or password']);
        exit;
    }

    // Start session (do NOT override Redis session path)
    if (session_status() === PHP_SESSION_NONE) session_start();

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];

    $session_id = session_id();

    // Role-based redirect
    $redirect = '/Frontend/admin/dashboard.php';
    if (($user['role'] ?? '') === 'super_admin') {
        $redirect = '/Frontend/admin/super_admin.php';
    } elseif (($user['role'] ?? '') === 'customer') {
        $redirect = '/Frontend/index.php';
    }

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'full_name' => $user['full_name'] ?? $user['username'],
        ],
        'redirect' => $redirect,
        'session_id' => $session_id,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Login failed: ' . $e->getMessage()]);
}
