<?php
/**
 * E-Commerce Shop Page, Wangari
 * Growvi design language. Keeps JS filter contract (product-filters / product-card data attrs).
 */
declare(strict_types=1);

$path_prefix = '../';
$page_title = 'Shop - Buy Chicken Products & Feeds | Wangari';

include '../includes/header.php';

// Get database connection and load products via shared source
$pdo = getDB();
$products = [];
require_once __DIR__ . '/../includes/product_source.php';
$products = loadDisplayProducts($pdo);
?>

<main class="g-main">

    <!-- Page hero -->
    <section class="g-page-hero">
        <div class="g-container">
            <h1>Online <span class="g-serif">Shop</span></h1>
            <p>Browse and purchase premium poultry products, fresh eggs and formulated feeds, delivered across Kenya.</p>
        </div>
    </section>

    <!-- Nutrition / value intro -->
    <section class="g-section">
        <div class="g-container g-stack-mobile" style="display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 3.5rem; align-items: center;">
            <div>
                <span class="g-eyebrow">Premium Feeds</span>
                <h2 style="font-size: clamp(1.8rem, 3.5vw, 2.6rem);">Formulated feeds for <span class="g-serif" style="color: #1B7A3D;">Kenyan poultry</span></h2>
                <p style="color: var(--g-muted); font-size: 1.05rem;">
                    Specially formulated animal feeds designed for optimal growth, productivity, and health. Each formula is balanced with essential nutrients, amino acids, and vitamins for maximum performance.
                </p>
                <ul style="list-style: none; padding: 0; margin: 0 0 2rem; display: grid; gap: 0.6rem;">
                    <li style="display: flex; gap: 0.6rem; align-items: center; color: var(--g-muted);">• Starter, Grower, and Finisher feeds</li>
                    <li style="display: flex; gap: 0.6rem; align-items: center; color: var(--g-muted);">• Premium Layer Mash with calcium</li>
                    <li style="display: flex; gap: 0.6rem; align-items: center; color: var(--g-muted);">• Available in 50kg bulk bags</li>
                </ul>
                <a href="/Frontend/pages/shop.php?category=feeds" class="g-btn g-btn-dark">Shop Feeds</a>
            </div>
            <div style="border-radius: var(--g-radius); overflow: hidden;">
                <img src="/Frontend/images/Growers Mash.png" alt="Premium animal feeds" style="width: 100%; display: block; object-fit: cover; min-height: 380px;">
            </div>
        </div>
    </section>

    <!-- Shop content: filters + grid -->
    <section class="g-section g-section-cream" style="padding-top: 3rem;">
        <div class="g-container">
            <div class="g-stack-mobile-260" style="display: grid; grid-template-columns: 260px 1fr; gap: 2.5rem; align-items: start;">

                <!-- Sidebar: Filters -->
                <aside>
                    <div class="g-shop-sidebar" style="position: sticky; top: 110px;">
                        <div class="g-form-card" style="padding: 1.6rem;">
                            <h3 style="font-size: 1.1rem; margin-bottom: 1.2rem;">Filters</h3>

                            <form class="product-filters g-form" style="gap: 1.4rem;">
                                <div class="g-field">
                                    <label>Product Type</label>
                                    <?php
                                    require_once __DIR__ . '/../../Backend/api/dropdowns.php';
                                    $types = getSystemDropdownOptions('product_types');
                                    foreach ($types as $t):
                                    ?>
                                    <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; color: var(--g-muted); font-size: 0.92rem; font-weight: 500; margin-bottom: 0.4rem;">
                                        <input type="checkbox" name="type" value="<?php echo htmlspecialchars($t['option_value']); ?>" class="form-checkbox" style="accent-color: var(--g-lime); width: 16px; height: 16px;">
                                        <?php echo htmlspecialchars($t['option_label']); ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>

                                <div class="g-field">
                                    <label>Availability</label>
                                    <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; color: var(--g-muted); font-size: 0.92rem; font-weight: 500; margin-bottom: 0.4rem;">
                                        <input type="checkbox" name="availability" value="in-stock" class="form-checkbox" checked style="accent-color: var(--g-lime); width: 16px; height: 16px;"> In Stock
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; color: var(--g-muted); font-size: 0.92rem; font-weight: 500;">
                                        <input type="checkbox" name="availability" value="preorder" class="form-checkbox" style="accent-color: var(--g-lime); width: 16px; height: 16px;"> Pre-Order
                                    </label>
                                </div>

                                <button type="button" class="g-btn g-btn-outline-dark" style="width: 100%; font-size: 0.85rem;"
                                    onclick="document.querySelectorAll('.product-filters input').forEach(i=>i.checked=false);document.querySelectorAll('.product-card').forEach(c=>c.style.display='');document.getElementById('products-count').textContent = document.querySelectorAll('.product-card').length;">Reset Filters</button>
                            </form>
                        </div>
                    </div>
                </aside>

                <!-- Main Content -->
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.6rem; flex-wrap: wrap; gap: 0.8rem;">
                        <p style="color: var(--g-muted); margin: 0; font-size: 0.95rem;">Showing <span id="products-count"><?php echo count($products); ?></span> products</p>
                        <div style="display: flex; gap: 0.8rem; align-items: center;">
                            <label style="font-size: 0.9rem; color: var(--g-muted);">Sort by:</label>
                            <select style="padding: 0.55rem 2.2rem 0.55rem 0.7rem; border-radius: var(--g-radius-sm); border: 1px solid var(--g-line); background-color: #fff; font-size: 0.9rem; color: var(--g-text); cursor: pointer; outline: none;">
                                <option>Newest Arrivals</option>
                                <option>Price: Low to High</option>
                                <option>Price: High to Low</option>
                            </select>
                        </div>
                    </div>

                    <div class="g-products">
                        <?php
                        if (!empty($products)) {
                            foreach ($products as $index => $product):
                                $img = $product['img'] ?? '';
                                if (!$img) {
                                    $img = match($product['product_type'] ?? 'feed') {
                                        'feed' => '/Frontend/images/Growers Mash.png',
                                        'eggs' => '/Frontend/images/download (3).png',
                                        'chicks' => '/Frontend/images/download (7).png',
                                        'live_chicken' => '/Frontend/images/download (4).png',
                                        default => '/Frontend/images/Chick Starter Crumbs.png'
                                    };
                                }
                                $stock = $product['stock_quantity'] ?? 0;
                                $inStock = $stock > 0;
                        ?>
                        <div class="g-product product-card" data-id="<?php echo $product['id']; ?>" data-type="<?php echo htmlspecialchars($product['product_type'] ?? '', ENT_QUOTES); ?>" data-instock="<?php echo $inStock ? '1' : '0'; ?>">
                            <a href="/Frontend/pages/product-detail.php?id=<?php echo $product['id']; ?>" style="display: block;">
                                <div class="g-product-img">
                                    <span class="g-product-badge"><?php echo $inStock ? 'In Stock' : 'Out of Stock'; ?></span>
                                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>
                            </a>
                            <div class="g-product-body">
                                <h3 class="product-name">
                                    <a href="/Frontend/pages/product-detail.php?id=<?php echo $product['id']; ?>" style="color: inherit;"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></a>
                                </h3>
                                <p class="g-product-desc"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 90) . (strlen($product['description'] ?? '') > 90 ? '…' : ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                <div class="g-product-meta">
                                    <span class="g-product-price">KES <?php echo number_format((float)$product['price'], 0); ?></span>
                                    <button class="g-product-btn add-to-cart-btn" data-id="<?php echo $product['id']; ?>" data-qty="1" <?php echo !$inStock ? 'disabled' : ''; ?>>
                                        <?php echo $inStock ? 'Add to Cart' : 'Out of Stock'; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php
                            endforeach;
                        } else {
                        ?>
                        <div style="grid-column: 1/-1; text-align: center; padding: 4rem 0;">
                            <p style="color: var(--g-muted); font-size: 1.1rem;">No products available at the moment.</p>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

<?php
include '../includes/footer.php';
?>
