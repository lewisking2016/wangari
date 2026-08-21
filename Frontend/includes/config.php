<?php
/**
 * Frontend Configuration & Session Setup
 * Global configuration constants and database connection initialization
 */
declare(strict_types=1);

// PHP 8.0 Polyfills for PHP 7.4 compatibility
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return '' === $needle || false !== strpos($haystack, $needle);
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return 0 === strpos($haystack, $needle);
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return '' === $needle || $needle === substr($haystack, -strlen($needle));
    }
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    $temp_dir = sys_get_temp_dir();
    if (is_writable($temp_dir)) {
        session_save_path($temp_dir);
    }
    // Secure Session Configuration parameters
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// Detect BASE_URL automatically
$scriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? '';
$documentRoot   = $_SERVER['DOCUMENT_ROOT'] ?? '';
$scriptDir = $scriptFilename !== '' ? str_replace('\\', '/', dirname($scriptFilename)) : '';
$docRoot   = $documentRoot !== '' ? str_replace('\\', '/', $documentRoot) : '';

if (str_contains($scriptDir, '/Frontend') || str_contains($scriptDir, '/Frontend/pages')) {
    define('BASE_URL', '/Frontend/');
} else {
    define('BASE_URL', '/Frontend/');
}

define('ASSETS_URL', BASE_URL . 'assets/');
define('API_URL', '/Backend/api/');

// Environment Detection
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', APP_ENV === 'development');

// Site Information
define('SITE_NAME', 'Wangari');
define('SITE_TAGLINE', 'Smart Farming for a Sustainable Future');
define('SITE_DESCRIPTION', 'The all-in-one farm management platform for Africa. Track livestock, crops, feed production, sales and finances, and grow smarter, rooted in the spirit of Prof. Wangari Maathai.');
define('SITE_EMAIL', 'info@wangari.farm');
define('SITE_PHONE', '+254 727 585 599');
define('SITE_ADDRESS', 'Nairobi, Kenya');

// Pagination
define('ITEMS_PER_PAGE', 12);

// Currency
define('CURRENCY', 'KES');
define('CURRENCY_SYMBOL', 'KES');

// Payment Methods (No M-Pesa)
define('PAYMENT_METHODS', [
    'bank_transfer' => 'Bank Transfer',
    'cash_on_delivery' => 'Cash on Delivery'
]);

// Order Status
define('ORDER_STATUS', [
    'pending' => 'Pending',
    'paid' => 'Paid',
    'picking' => 'Picking',
    'packing' => 'Packing',
    'production' => 'In Production',
    'dispatch' => 'Dispatch',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
]);

// Delivery Zones
define('DELIVERY_ZONES', [
    'wangari' => ['name' => 'Wangari County', 'cost' => 0],
    'kakamega' => ['name' => 'Kakamega County', 'cost' => 500],
    'kisumu' => ['name' => 'Kisumu County', 'cost' => 1000],
    'kisii' => ['name' => 'Kisii County', 'cost' => 1200],
]);

// Minimum Order Value & Free Delivery
define('MIN_ORDER_VALUE', 2000);
define('FREE_DELIVERY_THRESHOLD', 5000);

// Product Categories
define('PRODUCT_CATEGORIES', [
    'chicken' => 'Chicken Products',
    'feeds' => 'Animal Feeds'
]);

// Product Types
define('PRODUCT_TYPES', [
    'live_chicken' => 'Live Chicken',
    'chicks' => 'Day-Old Chicks',
    'eggs' => 'Eggs',
    'feed' => 'Animal Feed'
]);

// Include Backend Configuration Files
try {
    $backendPath = dirname(__DIR__, 2) . '/Backend/config/';
    
    if (file_exists($backendPath . 'database.php')) {
        require_once $backendPath . 'database.php';
    }
    
    if (file_exists($backendPath . 'queries.php')) {
        require_once $backendPath . 'queries.php';
    }
    
    if (file_exists($backendPath . 'security.php')) {
        require_once $backendPath . 'security.php';
    }
    
    if (file_exists($backendPath . 'google_oauth.php')) {
        require_once $backendPath . 'google_oauth.php';
    }
    
    // Initialize Database Connection - NEVER let this fail
    if (function_exists('getDatabaseConnection')) {
        try {
            $GLOBALS['pdo'] = getDatabaseConnection();
        } catch (Exception $e) {
            @error_log('Database connection error: ' . $e->getMessage());
            $GLOBALS['pdo'] = null;
        }
    } else {
        $GLOBALS['pdo'] = null;
    }
} catch (Exception $e) {
    @error_log('Configuration error: ' . $e->getMessage());
    $GLOBALS['pdo'] = null;
}

// Helper function to get PDO instance
function getDB(): ?PDO {
    if (!empty($GLOBALS['pdo'])) return $GLOBALS['pdo'];
    $GLOBALS['pdo'] = getDatabaseConnection();
    if (!empty($GLOBALS['pdo'])) {
        // Auto-run new tables if missing — keeps the live site self-healing
        @require_once __DIR__ . '/../../Backend/config/auto_migrate.php';
        if (function_exists('ensureBusiaSchema')) {
            ensureBusiaSchema($GLOBALS['pdo']);
        }
    }
    return $GLOBALS['pdo'];
}

/**
 * Execute a SELECT query safely and return rows.
 */
function safeQueryAll(PDO $pdo, string $sql, array $params = []): array {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('SafeQueryAll failed: ' . $e->getMessage() . ' SQL: ' . $sql);
        return [];
    }
}

/**
 * Execute a scalar query safely and return a single value.
 */
function safeQueryScalar(PDO $pdo, string $sql, array $params = [], $default = null) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
    } catch (Exception $e) {
        error_log('SafeQueryScalar failed: ' . $e->getMessage() . ' SQL: ' . $sql);
        return $default;
    }
}

/**
 * Get site setting by key
 */
function getSetting(string $key, string $default = ''): string {
    try {
        $pdo = getDB();
        if (!$pdo) return $default;
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? (string)$result : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Update site setting
 */
function updateSetting(string $key, string $value): bool {
    $pdo = getDB();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

// Apply live settings that affect runtime behavior.
try {
    $configuredTimezone = getSetting('timezone', 'Africa/Nairobi');
    if ($configuredTimezone && in_array($configuredTimezone, timezone_identifiers_list(), true)) {
        @date_default_timezone_set($configuredTimezone);
    } else {
        @date_default_timezone_set('Africa/Nairobi');
    }
} catch (Exception $e) {
    @date_default_timezone_set('Africa/Nairobi');
}
