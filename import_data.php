<?php
/**
 * Web-based Database Import Tool
 * Run this once to import all data
 */
declare(strict_types=1);

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'mrhzdunf_busiachicken');
define('DB_USER', 'mrhzdunf_busia_user');
define('DB_PASS', 'busia_user');

header('Content-Type: text/html; charset=UTF-8');
echo "<!DOCTYPE html><html><head><title>Data Import</title>";
echo "<style>body{font-family:system-ui;padding:40px;background:#f5f5f5;max-width:1000px;margin:0 auto;}";
echo ".ok{color:#28a745;padding:8px;background:#d4edda;border-radius:4px;margin:4px 0;display:block;}";
echo ".fail{color:#dc3545;padding:8px;background:#fee;border-radius:4px;margin:4px 0;display:block;}";
echo "h1{color:#2c3e50;}</style></head><body>";
echo "<h1>🔄 Database Import Tool</h1>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<span class='ok'>✓ Connected to database</span>";
    
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    
    // Check what already exists
    $existingProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    echo "<p>Current products in database: <strong>$existingProducts</strong></p>";
    
    if ($existingProducts >= 18) {
        echo "<span class='ok'>✓ Database already has full data ($existingProducts products). No import needed!</span>";
        echo "<p><a href='/status.php'>Check Status</a> | <a href='/'>Visit Homepage</a></p>";
        exit;
    }
    
    echo "<h2>Importing Additional Data...</h2>";
    
    // Insert additional products
    $newProducts = [
        [4, 'Bovans Brown Layers', 'bovans-brown-layers', 'Robust brown egg layers. Excellent feed conversion and egg quality.', 'live_chicken', 360.00, 35, 1, 0],
        [3, 'Kienyeji/Indigenous Chicks', 'kienyeji-chicks', 'Hardy indigenous breed chicks. Disease resistant and suitable for free-range farming.', 'chicks', 60.00, 300, 1, 1],
        [4, 'Chick Mash (0-4 weeks)', 'chick-mash', 'Fine mash feed for young chicks. Easy to digest with high energy content. 25kg bags.', 'feed', 1800.00, 90, 1, 1],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO products (category_id, name, slug, description, product_type, price, stock_quantity, is_active, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $inserted = 0;
    foreach ($newProducts as $product) {
        if ($stmt->execute($product)) {
            $inserted++;
            echo "<span class='ok'>✓ Added: {$product[1]}</span>";
        }
    }
    
    // Insert product variants
    $variants = [
        [12, 'Bag Size', '25kg', 1700.00, 50],
        [13, 'Bag Size', '25kg', 1500.00, 60],
        [14, 'Bag Size', '25kg', 1350.00, 80],
    ];
    
    $varStmt = $pdo->prepare("INSERT IGNORE INTO product_variants (product_id, variant_name, variant_value, variant_price, variant_stock) VALUES (?, ?, ?, ?, ?)");
    $varInserted = 0;
    foreach ($variants as $variant) {
        try {
            if ($varStmt->execute($variant)) {
                $varInserted++;
            }
        } catch (Exception $e) {
            // Ignore duplicates
        }
    }
    
    if ($varInserted > 0) {
        echo "<span class='ok'>✓ Added $varInserted product variants</span>";
    }
    
    // Insert flocks
    $pdo->exec("INSERT IGNORE INTO flocks (flock_name, breed, initial_count, current_count, hatch_date, status) VALUES
        ('Flock A - Broilers', 'Ross 308', 500, 480, '2026-06-01', 'active'),
        ('Flock B - Layers', 'ISA Brown', 300, 295, '2026-04-15', 'active'),
        ('Flock C - Kienyeji', 'Indigenous Mixed', 200, 198, '2026-05-20', 'active')
    ");
    echo "<span class='ok'>✓ Added flock records</span>";
    
    // Insert testimonials
    $pdo->exec("INSERT IGNORE INTO testimonials (customer_name, customer_role, rating, content, is_approved) VALUES
        ('John Kamau', 'Commercial Farmer, Nairobi', 5, 'Wangari has been my go-to supplier for the past 3 years. Their day-old chicks have excellent survival rates and their feeds produce outstanding results.', 1),
        ('Mary Akinyi', 'Small-Scale Farmer, Kisumu', 5, 'The quality of their layers is exceptional. My hens are producing 290+ eggs per year. The support team is also very helpful with advice.', 1),
        ('Peter Ochieng', 'Farm Manager, Bungoma', 4, 'Great products and reliable delivery. Their broilers reach market weight faster than other breeds I have tried. Highly recommended!', 1)
    ");
    echo "<span class='ok'>✓ Added customer testimonials</span>";
    
    // Add farm manager user
    $managerHash = password_hash('manager123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO users (username, email, password_hash, role, first_name, last_name) VALUES 
        ('manager', 'manager@wangari.farm', '$managerHash', 'farm_manager', 'Farm', 'Manager')
    ");
    echo "<span class='ok'>✓ Added farm manager account (manager / manager123)</span>";
    
    // Re-enable foreign keys
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    
    // Final count
    $finalCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $flockCount = $pdo->query("SELECT COUNT(*) FROM flocks")->fetchColumn();
    $testimonialCount = $pdo->query("SELECT COUNT(*) FROM testimonials")->fetchColumn();
    
    echo "<h2>✅ Import Complete!</h2>";
    echo "<ul>";
    echo "<li><strong>Products:</strong> $finalCount</li>";
    echo "<li><strong>Users:</strong> $userCount (admin, demo, manager)</li>";
    echo "<li><strong>Flocks:</strong> $flockCount</li>";
    echo "<li><strong>Testimonials:</strong> $testimonialCount</li>";
    echo "</ul>";
    
    echo "<p><strong>⚠️ IMPORTANT: Delete this file (import_data.php) now for security!</strong></p>";
    echo "<p><a href='/status.php' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:4px;display:inline-block;margin:10px 5px 0 0;'>Check Status</a>";
    echo "<a href='/' style='padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:4px;display:inline-block;margin:10px 0 0 0;'>Visit Homepage</a></p>";
    
} catch (PDOException $e) {
    echo "<span class='fail'>✗ Database Error: " . htmlspecialchars($e->getMessage()) . "</span>";
    echo "<p>Connection details being used:</p>";
    echo "<ul>";
    echo "<li>Host: " . DB_HOST . "</li>";
    echo "<li>Database: " . DB_NAME . "</li>";
    echo "<li>User: " . DB_USER . "</li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "<span class='fail'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>";
}

echo "</body></html>";
?>
