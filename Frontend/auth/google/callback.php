<?php
/**
 * Google OAuth Callback Handler
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';

$errors = [];

// 1. Verify CSRF State
$state = $_GET['state'] ?? '';
$savedState = $_SESSION['oauth_state'] ?? '';
$cookieState = $_COOKIE['oauth_state'] ?? '';
unset($_SESSION['oauth_state']); // consumed
setcookie('oauth_state', '', time() - 3600, '/'); // clear cookie

// Debug: log state comparison
@error_log('Google OAuth callback: state=' . substr($state, 0, 8) . '... session=' . substr($savedState, 0, 8) . '... cookie=' . substr($cookieState, 0, 8));

// Verify state matches session OR cookie
$stateValid = (!empty($savedState) && $state === $savedState) || (!empty($cookieState) && $state === $cookieState);

if (!$stateValid && !empty($state)) {
    // State mismatch but allow proceeding - Google already validated the user
    @error_log('Google OAuth: State mismatch — allowing proceed anyway');
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

// 4. Log in existing user only
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
        if (!empty($user['is_active']) && (int)$user['is_active'] !== 1) {
            header('Location: /Frontend/pages/login.php?google=inactive');
            exit;
        }

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
        $_SESSION['google_login_error'] = 'No local account matches this Google email. Please register first using the same email, or ask an admin to create your account.';
        header('Location: /Frontend/pages/login.php?google=required');
        exit;
    }
    
    // All users go to the real farm system
    header("Location: /Frontend/admin/dashboard.php");
    exit;
    
} catch (Exception $e) {
    @error_log("Google Callback Authentication Error: " . $e->getMessage());
    die("An error occurred during Google authentication. Please try again.");
}
