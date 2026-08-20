<?php
/**
 * Google OAuth Redirect Page
 * Initiates the Google Sign-in flow.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';

// Generate a secure state token to prevent CSRF attacks
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Redirect to Google's OAuth consent screen
$authUrl = getGoogleAuthUrl($state);
header("Location: " . $authUrl);
exit;
