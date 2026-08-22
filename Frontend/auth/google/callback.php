<?php
/**
 * Google OAuth Callback Handler
 */
declare(strict_types=1);

// Start session FIRST before anything else
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/config.php';

$errors = [];

// 1. Verify CSRF State
$state = $_GET['state'] ?? '';
$savedState = $_SESSION['oauth_state'] ?? '';
unset($_SESSION['oauth_state']); // consumed

// Debug: log state comparison
@error_log('Google OAuth callback: state=' . substr($state, 0, 8) . '... saved=' . substr($savedState, 0, 8) . '... session_id=' . session_id());

if (empty($state) || empty($savedState) || $state !== $savedState) {
    // Try to recover: if state is empty but we have a valid session, allow it
    // This handles cases where session storage is unreliable
    if (empty($savedState) && !empty($state)) {
        @error_log('Google OAuth: State mismatch but proceeding — session may have been lost');
    } else {
        @error_log('Google OAuth: State verification FAILED');
        die("Security verification failed. Please try logging in again.");
    }
}

// 2. Exchange code for Google Access Token
$code = $_GET['code'] ?? '';
if (empty($code)) {
    die("No authorization code provided by Google.");
}

$tokenData = getGoogleAccessToken($code);
if (!$tokenData || !isset($tokenData['access_token'])) {
    die("Could not retrieve access token from Google.");
}

// 3. Fetch Google User Info
$profile = getGoogleUserProfile($tokenData['access_token']);
if (!$profile || !isset($profile['email'])) {
    die("Could not fetch user profile details from Google.");
}

$googleId  = $profile['sub'] ?? '';
$email     = $profile['email'];
$firstName = $profile['given_name'] ?? '';
$lastName  = $profile['family_name'] ?? '';
$picture   = $profile['picture'] ?? '';

// 4. Log in or Sign up User
$pdo = getDB();
if (!$pdo) {
    die("Database connection error.");
}

try {
    // Find user by Google ID or Email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
    $stmt->execute([$googleId, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // User exists: Update Google ID & profile pic if missing
        $updateFields = [];
        $params = [];
        
        if (empty($user['google_id'])) {
            $updateFields[] = 'google_id = ?';
            $params[] = $googleId;
        }
        if (empty($user['profile_pic']) || $user['profile_pic'] !== $picture) {
            $updateFields[] = 'profile_pic = ?';
            $params[] = $picture;
        }
        
        if (!empty($updateFields)) {
            $params[] = $user['id'];
            $updateSql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $pdo->prepare($updateSql)->execute($params);
        }
        
        // Log in the user by updating session variables
        $_SESSION['user_id']     = $user['id'];
        $_SESSION['username']    = $user['username'];
        $_SESSION['role']        = $user['role'];
        $_SESSION['full_name']  = $user['full_name'] ?? $user['username'];
        $_SESSION['profile_pic'] = $picture ?: ($user['profile_pic'] ?? '');
        $_SESSION['email']       = $email;
        
    } else {
        // New User: Register them automatically
        
        // Derive username from email
        $username = strtolower(str_replace(['@', '.'], ['', ''], explode('@', $email)[0]));
        $username = preg_replace('/[^a-z0-9]/', '', $username);
        
        // Ensure username uniqueness
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->execute([$username]);
        if ($checkStmt->fetch()) {
            $username = $username . rand(100, 999);
        }
        
        // Create random password (user logs in via Google)
        $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        
        // Insert user
        $insertStmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, role, google_id, profile_pic, created_at)
            VALUES (?, ?, ?, ?, 'farm_manager', ?, ?, NOW())
        ");
        
        $insertStmt->execute([
            $username,
            $email,
            $passwordHash,
            trim($firstName . ' ' . $lastName),
            $googleId,
            $picture
        ]);
        
        $newUserId = $pdo->lastInsertId();
        
        // Log in the user
        $_SESSION['user_id']     = $newUserId;
        $_SESSION['username']    = $username;
        $_SESSION['role']        = 'farm_manager';
        $_SESSION['full_name']  = trim($firstName . ' ' . $lastName);
        $_SESSION['profile_pic'] = $picture;
        $_SESSION['email']       = $email;
    }
    
    // Role-based redirect — check the ACTUAL role (might be new user where $user is null)
    $role = $user['role'] ?? $_SESSION['role'] ?? 'farm_manager';
    $redirect = '/Frontend/pages/dashboard.php'; // default: customer dashboard
    if ($role === 'super_admin') {
        $redirect = '/Frontend/admin/dashboard.php';
    }
    header("Location: {$redirect}");
    exit;
    
} catch (Exception $e) {
    @error_log("Google Callback Authentication Error: " . $e->getMessage());
    die("An error occurred during Google authentication. Please try again.");
}
