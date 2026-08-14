<?php
/**
 * Quick Status Check
 */
header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html><html><head><title>Status Check</title>";
echo "<style>body{font-family:system-ui;padding:40px;background:#f5f5f5;}";
echo ".ok{color:#28a745;} .fail{color:#dc3545;} .warn{color:#ffc107;}";
echo "code{background:#fff;padding:4px 8px;border-radius:4px;}</style></head><body>";
echo "<h1>🔍 Wangari - System Status</h1>";

// Check database connection
require_once __DIR__ . '/Backend/config/database.php';

echo "<h2>Database Connection</h2>";
$pdo = getDatabaseConnection();
if ($pdo) {
    echo "<p class='ok'>✓ Connected to database: <code>" . DB_NAME . "</code></p>";
    
    // Check if tables exist
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (empty($tables)) {
            echo "<p class='warn'>⚠ Database connected but NO TABLES found!</p>";
            echo "<p><strong>ACTION REQUIRED:</strong> Run <a href='/setup_production_database.php'>setup_production_database.php</a></p>";
        } else {
            echo "<p class='ok'>✓ Found " . count($tables) . " tables</p>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li><code>$table</code></li>";
            }
            echo "</ul>";
            
            // Check products
            $productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
            echo "<p class='ok'>✓ Products in database: <strong>$productCount</strong></p>";
            
            // Check users
            $userCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('super_admin', 'farm_manager')")->fetchColumn();
            echo "<p class='ok'>✓ Admin users: <strong>$userCount</strong></p>";
            
            if ($productCount > 0 && $userCount > 0) {
                echo "<p class='ok'><strong>✓ DATABASE FULLY MIGRATED AND READY!</strong></p>";
            }
        }
    } catch (Exception $e) {
        echo "<p class='warn'>⚠ Tables check failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>ACTION REQUIRED:</strong> Run <a href='/setup_production_database.php'>setup_production_database.php</a></p>";
    }
} else {
    echo "<p class='fail'>✗ Cannot connect to database</p>";
    echo "<p>Host: <code>" . DB_HOST . "</code></p>";
    echo "<p>Database: <code>" . DB_NAME . "</code></p>";
    echo "<p>User: <code>" . DB_USER . "</code></p>";
}

echo "<h2>Admin Access</h2>";
echo "<p>Admin Login: <a href='/Frontend/admin/login.php'>/Frontend/admin/login.php</a></p>";
echo "<p>Shortcut: <a href='/wangariadmin'>/wangariadmin</a> (redirects to admin login)</p>";

echo "<h2>Test Pages</h2>";
echo "<ul>";
echo "<li><a href='/'>Homepage</a></li>";
echo "<li><a href='/test_assets.php'>Asset Test</a></li>";
echo "</ul>";

echo "</body></html>";
?>
