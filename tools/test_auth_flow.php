<?php
declare(strict_types=1);

function fail(string $message): void
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function assertContains(string $haystack, string $needle, string $label): void
{
    if (strpos($haystack, $needle) === false) {
        fail("FAIL: {$label} missing expected text: {$needle}");
    }
}

function assertNotContains(string $haystack, string $needle, string $label): void
{
    if (strpos($haystack, $needle) !== false) {
        fail("FAIL: {$label} still contains forbidden text: {$needle}");
    }
}

$root = dirname(__DIR__);

$loginPhp = file_get_contents($root . '/Frontend/pages/login.php');
$registerPhp = file_get_contents($root . '/Frontend/pages/register.php');
$adminLogin = file_get_contents($root . '/Frontend/admin/login.php');
$googleLogin = file_get_contents($root . '/Frontend/auth/google/login.php');
$googleCallback = file_get_contents($root . '/Frontend/auth/google/callback.php');
$apiAuth = file_get_contents($root . '/Backend/api/auth.php');
$emailPolicy = file_get_contents($root . '/Backend/config/email_policy.php');
$dashboard = file_get_contents($root . '/Frontend/admin/dashboard.php');
$loginHtml = file_get_contents($root . '/Frontend/pages/login.html');
$registerHtml = file_get_contents($root . '/Frontend/pages/register.html');
$configPhp = file_get_contents($root . '/Frontend/includes/config.php');
$sessionPhp = file_get_contents($root . '/Backend/config/session.php');
$adminHeader = file_get_contents($root . '/Frontend/admin/includes/admin_header.php');

if ($loginPhp === false || $registerPhp === false || $adminLogin === false || $googleLogin === false || $googleCallback === false || $apiAuth === false || $emailPolicy === false || $dashboard === false || $loginHtml === false || $registerHtml === false || $configPhp === false || $sessionPhp === false || $adminHeader === false) {
    fail('FAIL: Could not read one or more auth files');
}

assertContains($loginPhp, '/Frontend/auth/google/login.php', 'public login page');
assertContains($loginPhp, '/Frontend/pages/register.php', 'public login page');
assertContains($registerPhp, '/Frontend/pages/login.php', 'public register page');
assertContains($registerPhp, 'Only Gmail and Outlook email addresses are allowed', 'public register page');
assertContains($registerPhp, 'Continue with Google', 'public register page');
assertContains($registerPhp, 'flow=register', 'public register page');
assertContains($registerPhp, 'google_registration_profile', 'public register page');

assertContains($adminLogin, '/Frontend/auth/google/login.php', 'admin login page');
assertContains($loginHtml, '/Frontend/auth/google/login.php', 'static login page');
assertContains($registerHtml, '/Frontend/auth/google/login.php', 'static register page');
assertContains($registerHtml, 'login.php', 'static register page');
assertContains($registerHtml, 'Use a Gmail or Outlook email address only.', 'static register page');
assertContains($emailPolicy, 'gmail.com', 'email policy');
assertContains($emailPolicy, 'outlook.com', 'email policy');

assertNotContains($googleLogin, 'session_start();', 'google login bootstrap');
assertNotContains($googleCallback, 'session_start();', 'google callback bootstrap');
assertNotContains($apiAuth, 'Create new user from Google profile', 'api auth google branch');
assertNotContains($apiAuth, 'INSERT INTO users (username, email, password, full_name, role, google_id, profile_pic, created_at)', 'api auth google branch');
assertContains($apiAuth, 'No local account matches this Google email', 'api auth google branch');
assertContains($apiAuth, 'register_required', 'api auth google branch');
assertContains($apiAuth, 'google_registration_profile', 'api auth google branch');
assertNotContains($dashboard, 'session_save_path(', 'admin dashboard bootstrap');
assertContains($dashboard, "require_once __DIR__ . '/../includes/config.php';", 'admin dashboard bootstrap');
assertNotContains($configPhp, "save_handler', 'redis'", 'shared session config');
assertContains($googleCallback, 'No local account matches this Google email', 'google callback');
assertContains($googleCallback, 'header(\'Location: /Frontend/pages/register.php?google=required\')', 'google callback');
assertContains($googleCallback, 'Your Google sign-in could not be verified.', 'google callback');
assertNotContains($googleCallback, 'New User: Register them automatically', 'google callback');
assertNotContains($googleCallback, 'INSERT INTO users (username, email, password, full_name, role, google_id, profile_pic, created_at)', 'google callback');
assertContains($googleCallback, 'google_registration_profile', 'google callback');
assertContains($loginPhp, 'No local account matches this Google email', 'public login page');
assertContains($loginPhp, 'wangariEmailVariants', 'public login page');
assertContains($configPhp, 'function wangariIsFarmSystemRole', 'shared farm role guard');
assertContains($dashboard, 'wangariIsFarmSystemRole', 'admin dashboard role guard');
assertContains($adminHeader, 'wangariIsFarmSystemRole', 'admin header role guard');
assertContains($googleCallback, 'session_write_close();', 'Google callback session persistence');
assertContains($configPhp, "require_once dirname(__DIR__, 2) . '/Backend/config/session.php';", 'shared session bootstrap');
assertContains($sessionPhp, 'Backend/storage/sessions', 'shared session storage');
assertContains($sessionPhp, "session.save_handler', 'files'", 'file session handler');
assertContains($sessionPhp, "session.cookie_samesite', 'Lax'", 'session cookie policy');
assertContains($sessionPhp, '/var/lib/php/sessions', 'system session storage fallback');
assertContains($loginPhp, "session_regenerate_id(true);", 'password login session rotation');

echo "Auth flow checks passed." . PHP_EOL;
