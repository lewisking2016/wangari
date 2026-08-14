<?php
declare(strict_types=1);

/**
 * Centralized product source used by both shop and homepage
 * Tries DB first, otherwise returns the local sample dataset.
 */
function loadDisplayProducts(?PDO $pdo = null): array
{
    if (!$pdo) {
        return getFallbackProducts();
    }

    try {
        $stmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY name ASC");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return !empty($products) ? $products : getFallbackProducts();
    } catch (Exception $e) {
        @error_log("Failed to load products from database: " . $e->getMessage());
        return getFallbackProducts();
    }
}

/**
 * Fallback products when database is not available
 */
function getFallbackProducts(): array
{
    return [
        [
            'id' => 1,
            'name' => 'Ross 308 Broilers',
            'slug' => 'ross-308-broilers',
            'description' => 'Premium fast-growing broiler breed. Excellent feed efficiency and meat quality.',
            'product_type' => 'live_chicken',
            'price' => 450,
            'stock_quantity' => 50,
            'image_url' => '/Frontend/images/download (4).png',
            'is_featured' => 1,
        ],
        [
            'id' => 2,
            'name' => 'Fresh Farm Eggs (Trays)',
            'slug' => 'fresh-farm-eggs',
            'description' => 'Premium quality eggs from our layer flock. 30-egg trays.',
            'product_type' => 'eggs',
            'price' => 420,
            'stock_quantity' => 100,
            'image_url' => '/Frontend/images/download (3).png',
            'is_featured' => 1,
        ],
        [
            'id' => 3,
            'name' => 'Day-Old Broiler Chicks',
            'slug' => 'day-old-broiler-chicks',
            'description' => 'Vaccinated broiler chicks. 95%+ hatch rate.',
            'product_type' => 'chicks',
            'price' => 80,
            'stock_quantity' => 1000,
            'image_url' => '/Frontend/images/download (7).png',
            'is_featured' => 1,
        ],
        [
            'id' => 4,
            'name' => 'Starter Feed (0-4 weeks)',
            'slug' => 'starter-feed',
            'description' => 'High-protein formula for day-old chicks. 24% crude protein with probiotics.',
            'product_type' => 'feed',
            'price' => 3200,
            'stock_quantity' => 100,
            'image_url' => '/Frontend/images/Chick Starter Crumbs.png',
            'is_featured' => 1,
        ],
        [
            'id' => 5,
            'name' => 'Layer Mash (16 weeks+)',
            'slug' => 'layer-mash',
            'description' => 'Premium feed for laying hens. 18% crude protein with calcium.',
            'product_type' => 'feed',
            'price' => 2500,
            'stock_quantity' => 150,
            'image_url' => '/Frontend/images/Growers Mash.png',
            'is_featured' => 1,
        ],
        [
            'id' => 6,
            'name' => 'Wangari Premium Mix',
            'slug' => 'wangari-premium-mix',
            'description' => 'Our signature blend. Multi-purpose feed suitable for all poultry types.',
            'product_type' => 'feed',
            'price' => 3100,
            'stock_quantity' => 200,
            'image_url' => '/Frontend/images/kienyeji mash.png',
            'is_featured' => 1,
        ],
    ];
}

?>
