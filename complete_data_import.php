<?php
/**
 * Complete Data Import - All Products, Categories, and Raw Materials
 * Run this once to import ALL missing data
 */
declare(strict_types=1);

require_once __DIR__ . '/Backend/config/database.php';

header('Content-Type: text/html; charset=UTF-8');
echo "<!DOCTYPE html><html><head><title>Complete Data Import</title>";
echo "<style>body{font-family:system-ui;padding:40px;background:#f5f5f5;max-width:1200px;margin:0 auto;}";
echo ".ok{color:#28a745;padding:8px;background:#d4edda;border-radius:4px;margin:4px 0;display:block;}";
echo ".info{color:#0066cc;padding:8px;background:#e7f3ff;border-radius:4px;margin:4px 0;display:block;}";
echo ".fail{color:#dc3545;padding:8px;background:#fee;border-radius:4px;margin:4px 0;display:block;}";
echo "h1{color:#2c3e50;} h2{margin-top:30px;border-bottom:2px solid #ddd;padding-bottom:10px;}</style></head><body>";
echo "<h1>🔄 Complete Data Import</h1>";

try {
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception("Cannot connect to database");
    }
    
    echo "<span class='ok'>✓ Connected to database</span>";
    
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    
    // ==================================
    // STEP 1: CHECK EXISTING DATA
    // ==================================
    echo "<h2>Step 1: Checking Current Database</h2>";
    
    $currentProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $currentCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    
    echo "<span class='info'>Current Products: $currentProducts</span>";
    echo "<span class='info'>Current Categories: $currentCategories</span>";
    
    // ==================================
    // STEP 2: ADD MISSING CATEGORIES
    // ==================================
    echo "<h2>Step 2: Adding Categories</h2>";
    
    $categories = [
        ['id' => 1, 'name' => 'Broilers', 'slug' => 'broilers', 'type' => 'chicken', 'desc' => 'Fast-growing broiler chickens for meat production'],
        ['id' => 2, 'name' => 'Layers', 'slug' => 'layers', 'type' => 'chicken', 'desc' => 'High-productivity layer chickens for egg production'],
        ['id' => 3, 'name' => 'Day-Old Chicks', 'slug' => 'day-old-chicks', 'type' => 'chicken', 'desc' => 'Vaccinated day-old chicks ready for rearing'],
        ['id' => 4, 'name' => 'Feeds', 'slug' => 'feeds', 'type' => 'feed', 'desc' => 'Specialized animal feeds for optimal poultry nutrition'],
        ['id' => 5, 'name' => 'Raw Materials', 'slug' => 'raw-materials', 'type' => 'feed', 'desc' => 'Raw materials for feed formulation'],
        ['id' => 6, 'name' => 'Eggs', 'slug' => 'eggs', 'type' => 'chicken', 'desc' => 'Fresh farm eggs from our layer flocks'],
    ];
    
    $catStmt = $pdo->prepare("INSERT INTO categories (id, name, slug, category_type, description) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE description = VALUES(description)");
    
    $catAdded = 0;
    foreach ($categories as $cat) {
        if ($catStmt->execute([$cat['id'], $cat['name'], $cat['slug'], $cat['type'], $cat['desc']])) {
            $catAdded++;
            echo "<span class='ok'>✓ {$cat['name']}</span>";
        }
    }
    
    echo "<span class='info'>Categories processed: $catAdded</span>";
    
    // ==================================
    // STEP 3: ADD ALL PRODUCTS
    // ==================================
    echo "<h2>Step 3: Adding Products</h2>";
    
    $allProducts = [
        // BROILERS (Category 1)
        [1, 'Ross 308 Broilers', 'ross-308-broilers', 'Premium fast-growing broiler breed. Excellent feed efficiency and meat quality. Ready for market in 6-7 weeks.', 'live_chicken', 450.00, 50, 1, 1, ''],
        [1, 'Cobb 500 Broilers', 'cobb-500-broilers', 'High-performance broilers with excellent feed conversion. Superior meat yield and quality.', 'live_chicken', 480.00, 40, 1, 1, ''],
        [1, 'Hubbard Broilers', 'hubbard-broilers', 'Reliable broiler breed with consistent meat quality. Great for commercial farming.', 'live_chicken', 420.00, 60, 1, 0, ''],
        [1, 'Kuroiler Broilers', 'kuroiler-broilers', 'Dual-purpose breed suitable for both meat and egg production. Hardy and disease resistant.', 'live_chicken', 400.00, 45, 1, 0, ''],
        
        // LAYERS (Category 2)
        [2, 'ISA Brown Layers', 'isa-brown-layers', 'Premium brown egg layer producing 300+ eggs/year. Excellent feed efficiency.', 'live_chicken', 350.00, 45, 1, 1, ''],
        [2, 'Lohmann Layers', 'lohmann-layers', 'White egg layers with exceptional livability and performance. Long laying cycle.', 'live_chicken', 340.00, 55, 1, 0, ''],
        [2, 'Bovans Brown Layers', 'bovans-brown-layers', 'Robust brown egg layers. Excellent feed conversion and egg quality.', 'live_chicken', 360.00, 35, 1, 0, ''],
        [2, 'Hyline Brown Layers', 'hyline-brown-layers', 'Superior brown egg layers with consistent production. 320+ eggs per year.', 'live_chicken', 370.00, 40, 1, 1, ''],
        
        // EGGS (Category 6)
        [6, 'Fresh Farm Eggs (Trays)', 'fresh-farm-eggs', 'Premium quality eggs from our free-range layer flock. 30-egg trays. Freshly collected daily.', 'eggs', 420.00, 100, 1, 1, ''],
        [6, 'Organic Free-Range Eggs', 'organic-free-range-eggs', 'Premium organic eggs from free-range hens. 30-egg trays.', 'eggs', 550.00, 50, 1, 1, ''],
        
        // DAY-OLD CHICKS (Category 3)
        [3, 'Day-Old Broiler Chicks', 'day-old-broiler-chicks', 'Vaccinated broiler chicks from quality parent stock. 95%+ hatch rate guarantee.', 'chicks', 80.00, 1000, 1, 1, ''],
        [3, 'Day-Old Layer Chicks', 'day-old-layer-chicks', 'Premium layer chicks vaccinated against Mareks and Newcastle disease. Ready to grow.', 'chicks', 70.00, 800, 1, 1, ''],
        [3, 'Mixed Day-Old Chicks', 'mixed-day-old-chicks', 'Combination of broiler and layer chicks. Great for mixed farming operations.', 'chicks', 75.00, 500, 1, 0, ''],
        [3, 'Kienyeji/Indigenous Chicks', 'kienyeji-chicks', 'Hardy indigenous breed chicks. Disease resistant and suitable for free-range farming.', 'chicks', 60.00, 300, 1, 1, ''],
        [3, 'Kuroiler Chicks', 'kuroiler-chicks', 'Fast-growing dual-purpose chicks. Suitable for both meat and eggs.', 'chicks', 85.00, 400, 1, 1, ''],
        
        // FEEDS (Category 4)
        [4, 'Chick Starter Crumbs (0-4 weeks)', 'chick-starter-crumbs', 'High-protein formula for day-old chicks. 24% crude protein with vitamins and probiotics. 50kg bags.', 'feed', 3200.00, 100, 1, 1, ''],
        [4, 'Grower Mash (4-8 weeks)', 'grower-mash', 'Balanced formula for growing chicks. 20% crude protein with essential amino acids. 50kg bags.', 'feed', 2800.00, 120, 1, 1, ''],
        [4, 'Layer Mash (16 weeks+)', 'layer-mash', 'Premium feed for laying hens. 18% crude protein with calcium for strong eggshells. 50kg bags.', 'feed', 2500.00, 150, 1, 1, ''],
        [4, 'Broiler Finisher (6-8 weeks)', 'broiler-finisher', 'Final stage feed for broilers. High energy formula for rapid weight gain. 50kg bags.', 'feed', 2900.00, 110, 1, 0, ''],
        [4, 'Kienyeji Mash', 'kienyeji-mash', 'Specially formulated for indigenous/kienyeji chickens. Balanced nutrition for free-range birds. 50kg bags.', 'feed', 2600.00, 90, 1, 1, ''],
        [4, 'Wangari Premium Mix', 'wangari-premium-mix', 'Our signature blend. Multi-purpose feed suitable for all poultry types. 50kg bags.', 'feed', 3100.00, 200, 1, 1, ''],
        [4, 'Vitamin & Mineral Supplements', 'vitamin-mineral-supplements', 'Complete vitamin complex and mineral pack for all poultry. Boosts immunity and productivity. 5kg bags.', 'feed', 1200.00, 80, 1, 0, ''],
        [4, 'Chick Mash (0-4 weeks)', 'chick-mash', 'Fine mash feed for young chicks. Easy to digest with high energy content. 25kg bags.', 'feed', 1800.00, 90, 1, 1, ''],
        
        // RAW MATERIALS (Category 5)
        [5, 'Maize (Yellow Corn)', 'maize-yellow-corn', 'Premium quality yellow maize. High energy content for feed formulation. 90kg bags.', 'feed', 4500.00, 200, 1, 1, ''],
        [5, 'Soya Bean Cake', 'soya-bean-cake', 'High protein soya bean meal. 44% crude protein. Essential for layer and broiler feeds. 50kg bags.', 'feed', 5200.00, 150, 1, 1, ''],
        [5, 'Sunflower Cake', 'sunflower-cake', 'Protein-rich sunflower meal. 35% crude protein. Good alternative to soya. 50kg bags.', 'feed', 3800.00, 100, 1, 1, ''],
        [5, 'Wheat Bran', 'wheat-bran', 'High fiber content. Good for digestive health. 50kg bags.', 'feed', 1800.00, 180, 1, 1, ''],
        [5, 'Fish Meal', 'fish-meal', 'High quality protein source. 60% crude protein. Essential for layer feeds. 50kg bags.', 'feed', 8500.00, 80, 1, 0, ''],
        [5, 'Limestone (Calcium)', 'limestone-calcium', 'Essential calcium source for layers. Promotes strong eggshells. 50kg bags.', 'feed', 1200.00, 200, 1, 1, ''],
        [5, 'Dicalcium Phosphate (DCP)', 'dicalcium-phosphate', 'Calcium and phosphorus supplement. Essential for bone development. 25kg bags.', 'feed', 3500.00, 60, 1, 0, ''],
        [5, 'Salt (Sodium Chloride)', 'salt-sodium-chloride', 'Feed grade salt. Essential mineral for poultry health. 25kg bags.', 'feed', 800.00, 100, 1, 1, ''],
        [5, 'Premix (Vitamins & Minerals)', 'premix-vitamins-minerals', 'Complete vitamin and mineral premix. Essential micronutrients. 25kg bags.', 'feed', 6500.00, 50, 1, 1, ''],
        [5, 'Methionine (Amino Acid)', 'methionine-amino-acid', 'Essential amino acid supplement. Improves feather quality and growth. 25kg bags.', 'feed', 12000.00, 30, 1, 0, ''],
        [5, 'Lysine (Amino Acid)', 'lysine-amino-acid', 'Essential amino acid. Critical for protein synthesis and growth. 25kg bags.', 'feed', 11000.00, 30, 1, 0, ''],
        [5, 'Toxin Binder', 'toxin-binder', 'Protects against mycotoxins in feed. Improves feed quality and bird health. 25kg bags.', 'feed', 4800.00, 40, 1, 0, ''],
    ];
    
    $prodStmt = $pdo->prepare("INSERT INTO products (category_id, name, slug, description, product_type, price, stock_quantity, is_active, is_featured, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE price = VALUES(price), stock_quantity = VALUES(stock_quantity), is_active = VALUES(is_active), is_featured = VALUES(is_featured)");
    
    $prodAdded = 0;
    foreach ($allProducts as $prod) {
        if ($prodStmt->execute($prod)) {
            $prodAdded++;
            echo "<span class='ok'>✓ {$prod[1]}</span>";
        }
    }
    
    echo "<span class='info'>Products processed: $prodAdded</span>";
    
    // Re-enable foreign keys
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    
    // ==================================
    // FINAL REPORT
    // ==================================
    echo "<h2>✅ Import Complete!</h2>";
    
    $finalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $finalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    
    // Count by category
    $broilers = $pdo->query("SELECT COUNT(*) FROM products WHERE category_id = 1")->fetchColumn();
    $layers = $pdo->query("SELECT COUNT(*) FROM products WHERE category_id = 2")->fetchColumn();
    $chicks = $pdo->query("SELECT COUNT(*) FROM products WHERE category_id = 3")->fetchColumn();
    $feeds = $pdo->query("SELECT COUNT(*) FROM products WHERE category_id = 4")->fetchColumn();
    $rawMaterials = $pdo->query("SELECT COUNT(*) FROM products WHERE category_id = 5")->fetchColumn();
    $eggs = $pdo->query("SELECT COUNT(*) FROM products WHERE category_id = 6")->fetchColumn();
    
    echo "<table style='width:100%;border-collapse:collapse;background:white;margin-top:20px;'>";
    echo "<tr style='background:#f8f9fa;'><th style='padding:12px;text-align:left;border:1px solid #dee2e6;'>Category</th><th style='padding:12px;text-align:right;border:1px solid #dee2e6;'>Products</th></tr>";
    echo "<tr><td style='padding:10px;border:1px solid #dee2e6;'>Broilers</td><td style='padding:10px;text-align:right;border:1px solid #dee2e6;'><strong>$broilers</strong></td></tr>";
    echo "<tr><td style='padding:10px;border:1px solid #dee2e6;'>Layers</td><td style='padding:10px;text-align:right;border:1px solid #dee2e6;'><strong>$layers</strong></td></tr>";
    echo "<tr><td style='padding:10px;border:1px solid #dee2e6;'>Eggs</td><td style='padding:10px;text-align:right;border:1px solid #dee2e6;'><strong>$eggs</strong></td></tr>";
    echo "<tr><td style='padding:10px;border:1px solid #dee2e6;'>Day-Old Chicks</td><td style='padding:10px;text-align:right;border:1px solid #dee2e6;'><strong>$chicks</strong></td></tr>";
    echo "<tr><td style='padding:10px;border:1px solid #dee2e6;'>Feeds</td><td style='padding:10px;text-align:right;border:1px solid #dee2e6;'><strong>$feeds</strong></td></tr>";
    echo "<tr><td style='padding:10px;border:1px solid #dee2e6;'>Raw Materials</td><td style='padding:10px;text-align:right;border:1px solid #dee2e6;'><strong>$rawMaterials</strong></td></tr>";
    echo "<tr style='background:#d4edda;font-weight:bold;'><td style='padding:12px;border:1px solid #dee2e6;'>TOTAL</td><td style='padding:12px;text-align:right;border:1px solid #dee2e6;'>$finalProducts</td></tr>";
    echo "</table>";
    
    echo "<p style='margin-top:30px;padding:20px;background:#fff3cd;border-left:4px solid #ffc107;border-radius:4px;'><strong>⚠️ IMPORTANT: Delete this file (complete_data_import.php) now for security!</strong></p>";
    echo "<p><a href='/status.php' style='padding:12px 24px;background:#28a745;color:white;text-decoration:none;border-radius:6px;display:inline-block;margin:10px 10px 0 0;'>Check Status</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<span class='fail'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>";
}

echo "</body></html>";
?>
