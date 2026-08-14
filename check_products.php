<?php
/**
 * Quick Product Check - Debug Tool
 */
require_once __DIR__ . '/Backend/config/database.php';

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>Product Database Check</h1>";
echo "<style>body{font-family:system-ui;padding:20px;} table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f0f0f0;}</style>";

try {
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        die("Cannot connect to database");
    }
    
    echo "<p><strong>✓ Database Connected</strong></p>";
    
    // Count by category
    $stmt = $pdo->query("
        SELECT c.name, COUNT(p.id) as count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id 
        GROUP BY c.id, c.name
        ORDER BY c.name
    ");
    
    $categoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Products by Category</h2>";
    echo "<table>";
    echo "<tr><th>Category</th><th>Product Count</th></tr>";
    foreach ($categoryData as $row) {
        echo "<tr><td>{$row['name']}</td><td><strong>{$row['count']}</strong></td></tr>";
    }
    echo "</table>";
    
    // Show all products
    $products = $pdo->query("SELECT id, name, category_id, product_type, price, stock_quantity, is_active FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>All Products (" . count($products) . " total)</h2>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Category ID</th><th>Type</th><th>Price</th><th>Stock</th><th>Active</th></tr>";
    foreach ($products as $p) {
        $active = $p['is_active'] ? '✓ Yes' : '✗ No';
        echo "<tr><td>{$p['id']}</td><td>{$p['name']}</td><td>{$p['category_id']}</td><td>{$p['product_type']}</td><td>KES " . number_format($p['price']) . "</td><td>{$p['stock_quantity']}</td><td>$active</td></tr>";
    }
    echo "</table>";
    
    echo "<p><a href='/wangariadmin'>Go to Admin Login</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
