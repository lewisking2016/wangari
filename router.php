<?php
/**
 * Development router for the PHP built-in server.
 *
 * Serves real static files directly so CSS, JS, images, and other assets
 * keep their correct content types. Falls back to the main app entry point
 * for site routes like "/" when no file exists.
 */
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rawurldecode($path);
$file = __DIR__ . $path;

if (is_dir($file) && is_file($file . '/index.php')) {
    require $file . '/index.php';
    return true;
}

if ($path === '/wangariadmin' || $path === '/wangariadmin/') {
    header('Location: /Frontend/admin/login.php', true, 302);
    exit;
}

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
