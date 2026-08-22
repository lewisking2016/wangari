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

$platformApi = file_get_contents($root . '/Backend/api/platform.php');
$licenseApi = file_get_contents($root . '/Backend/api/license.php');
$migrate = file_get_contents($root . '/Backend/config/auto_migrate.php');
$platformUi = file_get_contents($root . '/Frontend/wangariadmin/index.html');
$desktopMain = file_get_contents($root . '/electron/main.js');
$desktopActivation = file_get_contents($root . '/electron/activation.html');

if ($platformApi === false || $licenseApi === false || $migrate === false || $platformUi === false || $desktopMain === false || $desktopActivation === false) {
    fail('FAIL: Could not read one or more license flow files');
}

assertContains($platformApi, 'wangari_licenses', 'platform API');
assertContains($platformApi, "case 'codes'", 'platform API');
assertContains($platformApi, 'user_id', 'platform API');
assertContains($platformApi, 'Please select a registered user account', 'platform API');
assertContains($platformUi, "api('codes','generate'", 'platform UI');
assertContains($platformUi, 'Generate Desktop Licenses', 'platform UI');
assertContains($platformUi, 'Registered User Account *', 'platform UI');
assertContains($platformUi, 'cc-user', 'platform UI');
assertContains($platformUi, 'max_devices', 'platform UI');
assertContains($platformUi, 'selected registered account', 'platform UI');
assertContains($licenseApi, 'wangari_licenses', 'desktop license API');
assertContains($licenseApi, 'WANGARI_JWT_SECRET', 'desktop license API');
assertContains($licenseApi, 'user_id', 'desktop license API');
assertContains($migrate, 'wangari_licenses', 'auto migrate');
assertContains($migrate, 'user_id INT DEFAULT NULL', 'auto migrate');
assertContains($desktopMain, 'Backend/api/license.php', 'desktop main');
assertContains($desktopMain, 'WANGARI_JWT_SECRET', 'desktop main');
assertContains($desktopActivation, 'Activation Code', 'desktop activation');
assertContains($desktopActivation, 'Please enter a valid activation code.', 'desktop activation');
assertNotContains($desktopMain, 'license.wangari.app', 'desktop main');
assertNotContains($platformUi, "api('codes','create'", 'platform UI');

echo "License flow checks passed." . PHP_EOL;
