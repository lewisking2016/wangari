<?php
/**
 * Database Connection & Configuration
 * PDO-based database management for Wangari
 * PRODUCTION CONFIGURATION FOR CPANEL
 */
declare(strict_types=1);

// Database Configuration
const DB_HOST = 'localhost';
const DB_NAME = 'wangari_db';
const DB_USER = 'YOUR_CPANEL_DB_USER';
const DB_PASS = 'YOUR_CPANEL_DB_PASSWORD';
const DB_CHARSET = 'utf8mb4';

// PDO Options
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
];

// Global PDO instance
$pdo = null;

/**
 * Get Database Connection
 */
function getDatabaseConnection(): PDO {
    global $pdo;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw $e;
    }
}

// Try to initialize connection
try {
    $pdo = getDatabaseConnection();
} catch (PDOException $e) {
    // Log error but don't die - let frontend handle it gracefully
    error_log("Initial database connection failed: " . $e->getMessage());
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

?>
