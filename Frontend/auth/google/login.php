<?php
/**
 * Google OAuth Redirect Page
 * Initiates the Google Sign-in flow.
 */
declare(strict_types=1);

// Start session FIRST
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/config.php';

// Generate a secure state token to prevent CSRF attacks
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
session_write_close(); // Force save session before redirect

// Redirect to Google's OAuth consent screen
$authUrl = getGoogleAuthUrl($state);
header("Location: " . $authUrl);
exit;
