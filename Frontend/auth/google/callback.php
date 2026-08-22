<?php
/**
 * Google OAuth Callback Handler
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once dirname(__DIR__, 3) . '/Backend/config/email_policy.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

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

if (!$stateValid) {
    @error_log('Google OAuth: State mismatch - blocking callback');
    $_SESSION['google_login_error'] = 'Your Google sign-in could not be verified. Please try again.';
    header('Location: /Frontend/pages/login.php?google=state');
    exit;
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
$oauthFlow = $_SESSION['oauth_flow'] ?? 'login';

if (!wangariIsAllowedEmail($email)) {
    $_SESSION['google_login_error'] = 'Only Gmail and Outlook email addresses are allowed. Please register with one of those addresses.';
    header('Location: /Frontend/pages/login.php?google=restricted');
    exit;
}

// 4. Log in existing user only
$pdo = getDB();
if (!$pdo) {
    die("Database connection error.");
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? LIMIT 1");
    $stmt->execute([$googleId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $variants = wangariEmailVariants($email);
        $placeholders = implode(',', array_fill(0, count($variants), '?'));
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) IN ($placeholders) LIMIT 1");
        $stmt->execute($variants);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

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

        unset($_SESSION['google_registration_profile'], $_SESSION['oauth_flow']);
        
        // Rotate the session after OAuth authentication and rebuild CSRF state.
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        // Log in the user by updating session variables
        $_SESSION['user_id']     = $user['id'];
        $_SESSION['username']    = $user['username'];
        $_SESSION['role']        = $user['role'];
        $_SESSION['full_name']  = $user['full_name'] ?? $user['username'];
        $_SESSION['profile_pic'] = $picture ?: ($user['profile_pic'] ?? '');
        $_SESSION['email']       = $email;
        
    } else {
        $_SESSION['google_registration_profile'] = [
            'google_id' => $googleId,
            'email' => $email,
            'full_name' => trim($firstName . ' ' . $lastName),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'profile_pic' => $picture,
            'flow' => $oauthFlow,
        ];
        $_SESSION['google_login_error'] = 'No local account matches this Google email. Please register first, then choose Farm Owner or Join as Worker to finish connecting your Google account.';
        header('Location: /Frontend/pages/register.php?google=required');
        exit;
    }
    
    unset($_SESSION['oauth_flow']);
    // Persist the authenticated session before the browser follows the redirect.
    session_write_close();

    // All authenticated farm roles go to the real farm system.
    $redirect = wangariAuthRedirectPath((string)($_SESSION['role'] ?? ''));
    header('Location: ' . $redirect);
    exit;
    
} catch (Exception $e) {
    @error_log("Google Callback Authentication Error: " . $e->getMessage());
    die("An error occurred during Google authentication. Please try again.");
}
