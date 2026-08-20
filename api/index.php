<?php
/**
 * Vercel Serverless PHP Gateway
 */
declare(strict_types=1);

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';

$projectRoot = dirname(__DIR__);

// Handle special custom aliases
if ($parsedPath === '/wangariadmin') {
    $parsedPath = '/Frontend/admin/login.php';
}

$targetFile = $projectRoot . $parsedPath;

// Route to exact PHP file if requested
if ($parsedPath !== '/' && file_exists($targetFile) && is_file($targetFile) && str_ends_with($targetFile, '.php')) {
    chdir(dirname($targetFile));
    require $targetFile;
    exit;
}

// Fallback to Main Public Homepage
chdir($projectRoot . '/Frontend');
require $projectRoot . '/Frontend/index.php';
