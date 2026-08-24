<?php
/**
 * Google Services OAuth Callback
 * 
 * Handles the callback after user authorizes Gmail + Calendar access.
 * Stores tokens for sending emails and managing calendar events.
 */

require_once dirname(__DIR__, 3) . '/Backend/config/session.php';
require_once dirname(__DIR__, 3) . '/Backend/config/database.php';
require_once dirname(__DIR__, 3) . '/Backend/config/google_services.php';

wangariStartSession();

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    header('Location: /Frontend/pages/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';
$error = $_GET['error'] ?? '';

// Handle errors
if (!empty($error)) {
    header('Location: /Frontend/admin/hub_settings.php?tab=connectors&error=' . urlencode($error));
    exit;
}

// Validate code
if (empty($code)) {
    header('Location: /Frontend/admin/hub_settings.php?tab=connectors&error=no_code');
    exit;
}

// Exchange code for tokens
$tokens = getGoogleServicesTokens($code);

if (!$tokens) {
    header('Location: /Frontend/admin/hub_settings.php?tab=connectors&error=token_failed');
    exit;
}

// Get user info from Google
$accessToken = $tokens['access_token'];
$googleUser = getGoogleUserProfile($accessToken);

// Calculate expiry
$expiresAt = date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 3600));

// Store tokens in database
try {
    $pdo = getDatabaseConnection();
    
    // Check if user already has tokens
    $stmt = $pdo->prepare("SELECT id FROM user_google_tokens WHERE user_id = ?");
    $stmt->execute([$userId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing tokens
        $stmt = $pdo->prepare("
            UPDATE user_google_tokens 
            SET access_token = ?, refresh_token = ?, expires_at = ?, scope = ?, google_id = ?, email = ?, updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->execute([
            $accessToken,
            $tokens['refresh_token'] ?? null,
            $expiresAt,
            implode(' ', GOOGLE_SCOPES_ALL),
            $googleUser['sub'] ?? '',
            $googleUser['email'] ?? '',
            $userId
        ]);
    } else {
        // Insert new tokens
        $stmt = $pdo->prepare("
            INSERT INTO user_google_tokens (user_id, google_id, email, access_token, refresh_token, expires_at, scope, connected_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $googleUser['sub'] ?? '',
            $googleUser['email'] ?? '',
            $accessToken,
            $tokens['refresh_token'] ?? null,
            $expiresAt,
            implode(' ', GOOGLE_SCOPES_ALL)
        ]);
    }
    
    // Create default notification preferences if not exists
    $stmt = $pdo->prepare("INSERT IGNORE INTO notification_preferences (user_id) VALUES (?)");
    $stmt->execute([$userId]);
    
    header('Location: /Frontend/admin/hub_settings.php?tab=connectors&success=connected');
    exit;
    
} catch (Exception $e) {
    error_log("Google Services Callback Error: " . $e->getMessage());
    header('Location: /Frontend/admin/hub_settings.php?tab=connectors&error=db_error');
    exit;
}
