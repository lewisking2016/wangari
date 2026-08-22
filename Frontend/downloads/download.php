<?php
/**
 * Streams published desktop installers without exposing filesystem paths.
 * Upload production releases to Frontend/downloads/releases/ on the server.
 */
declare(strict_types=1);

$platform = strtolower((string)($_GET['platform'] ?? ''));
$releases = [
    'windows' => [
        'file' => 'Wangari Farm Manager Setup 1.0.0.exe',
        'name' => 'Wangari-Farm-Manager-Setup-1.0.0.exe',
        'type' => 'application/octet-stream',
    ],
];

if (!isset($releases[$platform])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'This desktop build is not available.';
    exit;
}

$release = $releases[$platform];
$candidates = [
    __DIR__ . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $release['file'],
    dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'dist-electron' . DIRECTORY_SEPARATOR . $release['file'],
];
$filePath = null;
foreach ($candidates as $candidate) {
    if (is_file($candidate) && is_readable($candidate)) {
        $filePath = $candidate;
        break;
    }
}

if ($filePath === null) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'The Windows installer has not been published on this server yet.';
    exit;
}

while (ob_get_level() > 0) ob_end_clean();
header('Content-Type: ' . $release['type']);
header('Content-Disposition: attachment; filename="' . $release['name'] . '"');
header('Content-Length: ' . (string)filesize($filePath));
header('X-Content-Type-Options: nosniff');
readfile($filePath);
