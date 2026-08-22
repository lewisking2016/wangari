<?php
/**
 * Database Connection & Configuration
 * PDO-based database management for Wangari
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

// License guard — validates JWT in desktop mode, no-op on web
require_once __DIR__ . '/../license/guard.php';

// ---------------------------------------------------------------------
// Database Configuration
//
// Credentials are resolved in this order:
//   1. Backend/config/database.local.php  (gitignored — never commit this)
//   2. Environment variables DB_HOST / DB_NAME / DB_USER / DB_PASS
//   3. Local development defaults (root / empty password / wangari_db)
//   4. Legacy production fallback (kept ONLY so the live site keeps
//      connecting during migration — see note below)
//
// Recommended production setup (cPanel): set the DB_* environment variables
// or drop a database.local.php next to this file containing, e.g.:
//     $DB_HOST = 'localhost';
//     $DB_NAME = 'your_cpanel_db_name';
//     $DB_USER = 'your_cpanel_db_user';
//     $DB_PASS = 'your_cpanel_db_password';
//
// SECURITY NOTE: the production fallback below is legacy and must be
// removed after rotating the cPanel database password. Rotating the
// password invalidates the old credentials everywhere (including git
// history), then move the new credentials into database.local.php or
// DB_* env vars and delete the fallback block.
// ---------------------------------------------------------------------
$localConfigFile = __DIR__ . '/database.local.php';
if (is_file($localConfigFile)) {
    @include $localConfigFile;
}

$dbEnv = function (string $key): ?string {
    $value = $_ENV[$key] ?? getenv($key);
    return ($value === false || $value === '') ? null : (string)$value;
};

// Environment Detection
$isCli = (php_sapi_name() === 'cli');
$isDesktop = ($_ENV['WANGARI_MODE'] ?? getenv('WANGARI_MODE')) === 'desktop';

$isLocalhost = $isCli
    || in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost:8000', 'localhost', '127.0.0.1'], true)
    || ($_ENV['DB_HOST'] ?? getenv('DB_HOST')) === 'localhost'
    || (!empty($_SERVER['DOCUMENT_ROOT'])
        && (str_contains($_SERVER['DOCUMENT_ROOT'], 'Users') || str_contains($_SERVER['DOCUMENT_ROOT'], 'Desktop')));

// DSN & Connection variables initialization
$pdo = null;

if ($isDesktop) {
    // Persistent local directory inside user profile
    $userHome = $_SERVER['USERPROFILE'] ?? $_SERVER['HOME'] ?? sys_get_temp_dir();
    $dbDir = $userHome . '/.wangari';
    if (!is_dir($dbDir)) {
        @mkdir($dbDir, 0755, true);
    }
    $sqlitePath = $dbDir . '/wangari_local.sqlite';
    define('DB_SQLITE_PATH', $sqlitePath);
    define('DB_DRIVER', 'sqlite');
} else {
    // Per-environment defaults for MySQL.
    $defaults = $isLocalhost
        ? ['localhost', 'wangari_db', 'root', '']
        : ['localhost', 'wangari_db', 'wangari', 'Wangari2026!'];

    define('DB_HOST', $DB_HOST ?? $dbEnv('DB_HOST') ?? $defaults[0]);
    define('DB_NAME', $DB_NAME ?? $dbEnv('DB_NAME') ?? $defaults[1]);
    define('DB_USER', $DB_USER ?? $dbEnv('DB_USER') ?? $defaults[2]);
    define('DB_PASS', $DB_PASS ?? $dbEnv('DB_PASS') ?? $defaults[3]);
    define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4');
    define('DB_DRIVER', 'mysql');
}

// Connection String (DSN)
$dsn = (DB_DRIVER === 'sqlite') 
    ? "sqlite:" . DB_SQLITE_PATH
    : "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// Auto-migration helper is safe to include repeatedly
@require_once __DIR__ . '/auto_migrate.php';

/**
 * Get Database Connection
 */
function getDatabaseConnection(): ?PDO {
    global $pdo;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        if (DB_DRIVER === 'sqlite') {
            $pdo = new PDO("sqlite:" . DB_SQLITE_PATH, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            // Enable foreign key support in SQLite
            $pdo->exec("PRAGMA foreign_keys = ON;");
        } else {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
        }

        if (function_exists('ensureWangariSchema')) {
            try {
                ensureWangariSchema($pdo);
            } catch (Exception $e) {
                @error_log('Auto schema ensure failed: ' . $e->getMessage());
            }
        }

        return $pdo;
    } catch (PDOException $e) {
        @error_log("Database connection failed: " . $e->getMessage());
        return null;
    } catch (Exception $e) {
        @error_log("Database connection exception: " . $e->getMessage());
        return null;
    }
}

/**
 * Check whether a table exists in the current database.
 */
function tableExists(PDO $pdo, string $table): bool {
    try {
        if (DB_DRIVER === 'sqlite') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name=?");
            $stmt->execute([$table]);
            return (int)$stmt->fetchColumn() > 0;
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
            $stmt->execute([$table]);
            return (int)$stmt->fetchColumn() > 0;
        }
    } catch (Exception $e) {
        @error_log("tableExists failed for {$table}: " . $e->getMessage());
        return false;
    }
}

/**
 * Check whether a column exists on a table in the current database.
 */
function columnExists(PDO $pdo, string $table, string $column): bool {
    try {
        if (DB_DRIVER === 'sqlite') {
            $stmt = $pdo->query("PRAGMA table_info(" . preg_replace('/[^a-zA-Z0-9_]/', '', $table) . ")");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                if (strtolower($col['name']) === strtolower($column)) {
                    return true;
                }
            }
            return false;
        } else {
            $stmt = $pdo->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        }
    } catch (Exception $e) {
        @error_log("columnExists failed for {$table}.{$column}: " . $e->getMessage());
        return false;
    }
}


// Try to initialize connection - NEVER throw errors, always return null on failure
try {
    $pdo = getDatabaseConnection();
} catch (PDOException $e) {
    // Log error but don't die - let frontend handle it gracefully
    @error_log("Initial database connection failed: " . $e->getMessage());
    $pdo = null;
} catch (Exception $e) {
    @error_log("Database connection exception: " . $e->getMessage());
    $pdo = null;
}

/**
 * Database Helper Functions
 */

/**
 * Escape and sanitize string output
 */
function escape(string $raw): string {
    return htmlspecialchars($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Fetch single row
 */
function fetchOne(PDO $pdo, string $query, array $params = []): ?array {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        return null;
    }
}

/**
 * Fetch all rows
 */
function fetchAll(PDO $pdo, string $query, array $params = []): array {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        return [];
    }
}

/**
 * Execute insert/update/delete query
 */
function execute(PDO $pdo, string $query, array $params = []): bool {
    try {
        $stmt = $pdo->prepare($query);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get last inserted ID
 */
function lastInsertId(PDO $pdo): string {
    return $pdo->lastInsertId();
}

/**
 * Get row count from last query
 */
function rowCount(PDO $pdo, string $query, array $params = []): int {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Database Health Check
 */
function checkDatabaseHealth(PDO $pdo): bool {
    try {
        $result = $pdo->query("SELECT 1");
        return $result !== false;
    } catch (PDOException $e) {
        error_log("Database health check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Record an entry in the activity log (feeds Settings → System Activity Logs).
 * Best-effort: never throws, silently no-ops if the table is missing.
 */
function logActivity(PDO $pdo, string $action, string $module, string $details = '', ?int $entityId = null, string $entityType = ''): void {
    try {
        if (!tableExists($pdo, 'activity_logs')) {
            return;
        }
        $stmt = $pdo->prepare(
            'INSERT INTO activity_logs (user_id, username, action, module, entity_type, entity_id, details, ip_address) VALUES (?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            (int)($_SESSION['user_id'] ?? 0),
            (string)($_SESSION['username'] ?? ($_SESSION['first_name'] ?? 'system')),
            substr($action, 0, 100),
            substr($module, 0, 50),
            substr($entityType, 0, 50),
            $entityId !== null ? (int)$entityId : null,
            substr((string)$details, 0, 500),
            (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);
    } catch (Exception $e) {
        // Never break the caller over a log write
    }
}

?>
