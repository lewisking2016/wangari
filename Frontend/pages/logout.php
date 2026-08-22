<?php
/**
 * Logout Handler
 */
declare(strict_types=1);

// Load config (handles Redis sessions)
require_once __DIR__ . '/../includes/config.php';

// Record who is leaving (before the session is wiped)
if (!empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/../../Backend/config/database.php';
    $logoutPdo = getDatabaseConnection();
    if ($logoutPdo) {
        logActivity($logoutPdo, 'logout', 'auth', ($_SESSION['username'] ?? '') . ' logged out', (int)$_SESSION['user_id'], 'user');
    }
}

// Clear session data
$_SESSION = [];

// Destroy session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy session
session_destroy();

// Redirect to home
header('Location: /');
exit;
?>
