<?php
/**
 * Desktop App Version Check API
 * Returns latest version info so the app can prompt users to update.
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$latestVersion = '1.2.0';
$downloadUrl = 'https://wangari.imeantech.com/Frontend/pages/download.php';
$releaseNotes = 'Login flow, 40-day trial, bug fixes, modern design';

echo json_encode([
    'latest_version' => $latestVersion,
    'download_url' => $downloadUrl,
    'release_notes' => $releaseNotes,
    'min_version' => '1.0.0',
]);
