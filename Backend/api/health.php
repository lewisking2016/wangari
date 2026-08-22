<?php
/**
 * Health Check API Endpoint
 * Returns system status for monitoring tools
 * GET /api/health.php
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'services' => [],
    'version' => '1.0.0'
];

// Check Database
try {
    require_once __DIR__ . '/../config/database.php';
    if (function_exists('getDatabaseConnection')) {
        $pdo = getDatabaseConnection();
        if ($pdo) {
            $stmt = $pdo->query('SELECT 1');
            $health['services']['database'] = [
                'status' => 'up',
                'type' => DB_DRIVER
            ];
        } else {
            $health['services']['database'] = ['status' => 'down'];
            $health['status'] = 'degraded';
        }
    }
} catch (Exception $e) {
    $health['services']['database'] = [
        'status' => 'down',
        'error' => $e->getMessage()
    ];
    $health['status'] = 'degraded';
}

// Check Redis
if (class_exists('Redis')) {
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379, 2);
        $redis->ping();
        $health['services']['redis'] = ['status' => 'up'];
    } catch (Exception $e) {
        $health['services']['redis'] = ['status' => 'down'];
        // Redis down is not critical, just degraded
    }
} else {
    $health['services']['redis'] = ['status' => 'not_installed'];
}

// Check OPcache
if (function_exists('opcache_get_status')) {
    $opcache = @opcache_get_status(false);
    if ($opcache !== false) {
        $health['services']['opcache'] = [
            'status' => 'up',
            'memory_usage' => $opcache['memory_usage']['used_memory'] ?? 0,
            'hit_rate' => round(
                ($opcache['opcache_statistics']['opcache_hit_rate'] ?? 0) * 100,
                2
            )
        ];
    } else {
        $health['services']['opcache'] = ['status' => 'down'];
    }
}

// System info
$health['system'] = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize')
];

// Uptime
if (function_exists('sys_getloadavg')) {
    $load = sys_getloadavg();
    $health['system']['load_avg'] = [
        '1min' => round($load[0], 2),
        '5min' => round($load[1], 2),
        '15min' => round($load[2], 2)
    ];
}

// Overall status
$allUp = true;
foreach ($health['services'] as $service) {
    if ($service['status'] !== 'up') {
        $allUp = false;
        break;
    }
}

$health['status'] = $allUp ? 'healthy' : 'degraded';

// HTTP status code based on health
http_response_code($allUp ? 200 : 503);

echo json_encode($health, JSON_PRETTY_PRINT);
