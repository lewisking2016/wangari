<?php
/**
 * Vercel Serverless PHP Gateway
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Ensure proper default content type
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

try {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $parsedPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';

    $projectRoot = dirname(__DIR__);

    // Handle custom alias
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

    // Default to main landing page
    $homePath = $projectRoot . '/Frontend/index.php';
    if (file_exists($homePath)) {
        chdir($projectRoot . '/Frontend');
        require $homePath;
        exit;
    }

    // Fallback: root index.php
    $rootIndex = $projectRoot . '/index.php';
    if (file_exists($rootIndex)) {
        require $rootIndex;
        exit;
    }

    echo "<h1>Wangari Farm OS</h1><p>Home file not located.</p>";
} catch (Throwable $e) {
    http_response_code(500);
    echo "<h1>Server Error</h1><pre>" . htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') . "</pre>";
}
