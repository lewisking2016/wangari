<?php
/**
 * Product Detail Page, Wangari
 * Growvi design language.
 */
declare(strict_types=1);

$path_prefix = '../';
include '../includes/header.php';

$pdo = getDB();
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$productSlug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

$product = null;
try {
    if ($pdo) {
        if ($productId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($productSlug !== '') {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE slug = ? AND is_active = 1");
            $stmt->execute([$productSlug]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    @error_log("Failed to fetch product details: " . $e->getMessage());
}

// Fallback to display products if DB product missing (e.g. images resolved via source)
if (!$product) {
    require_once __DIR__ . '/../includes/product_source.php';
    $displayProducts = loadDisplayProducts($pdo);
    foreach ($displayProducts as $p) {
        if (($productId > 0 && (int)$p['id'] === $productId) || ($productSlug !== '' && ($p['slug'] ?? '') === $productSlug)) {
            $product = $p;
            break;
        }
    }
}

if (!$product) {
    ?>
    <main class="g-main">
        <section class="g-section" style="text-align: center; padding: 6rem 1.25rem;">
            <h2>Product Not Found</h2>
            <p style="color: var(--g-muted); margin-bottom: 2rem;">The product you are looking for does not exist or has been removed.</p>
            <a href="/Frontend/pages/shop.php" class="g-btn g-btn-dark">Back to Shop</a>
        </section>
    </main>
    <?php
    include '../includes/footer.php';
    exit;
}

$page_title = $product['name'] . ' - Buy Online | Wangari';
$inStock = ($product['stock_quantity'] ?? 0) > 0;
?>

<main class="g-main">

    <!-- Breadcrumb -->
    <section class="g-section" style="padding: 7.5rem 0 0;">
        <div class="g-container">
            <div style="font-size: 0.85rem; color: var(--g-muted); margin-bottom: 1rem; display: flex; gap: 0.5rem; align-items: center;">
                <a href="/" style="color: inherit;">Home</a>
                <span>/</span>
                <a href="/Frontend/pages/shop.php" style="color: inherit;">Shop</a>
                <span>/</span>
                <span style="color: var(--g-ink); font-weight: 600;"><?php echo htmlspecialchars($product['name']); ?></span>
            </div>
        </div>
    </section>

    <!-- Detail -->
    <section class="g-section" style="padding-top: 1.5rem;">
        <div class="g-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 3.5rem; align-items: start;">

            <!-- Image -->
            <div style="background: var(--g-cream); border-radius: var(--g-radius); padding: 2rem; display: flex; align-items: center; justify-content: center; border: 1px solid var(--g-line); min-height: 420px;">
                <img src="<?php echo htmlspecialchars($product['image_url'] ?? '/Frontend/images/download (4).png'); ?>"
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     style="max-width: 100%; max-height: 360px; object-fit: contain;">
            </div>

            <!-- Content -->
            <div>
                <span class="g-eyebrow"><?php echo htmlspecialchars(str_replace('_', ' ', $product['product_type'] ?? 'Product')); ?></span>
                <h1 style="font-size: clamp(1.9rem, 4vw, 2.8rem); margin-bottom: 0.9rem;">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h1>

                <div style="font-family: var(--g-serif); font-size: 2rem; color: var(--g-ink); margin-bottom: 1.6rem;">
                    KES <?php echo number_format((float)$product['price'], 2); ?>
                </div>

                <p style="color: var(--g-muted); line-height: 1.75; font-size: 1.02rem; margin-bottom: 2rem;">
                    <?php echo htmlspecialchars($product['description']); ?>
                </p>

                <!-- Stock status -->
                <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 2rem;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: <?php echo $inStock ? '#16a34a' : '#dc2626'; ?>; display: inline-block;"></span>
                    <span style="font-weight: 600; font-size: 0.9rem; color: <?php echo $inStock ? '#15803d' : '#b91c1c'; ?>;">
                        <?php echo $inStock ? 'In Stock (' . $product['stock_quantity'] . ' units available)' : 'Out of Stock'; ?>
                    </span>
                </div>

                <!-- Qty + cart -->
                <div style="display: flex; gap: 1rem; align-items: center; max-width: 420px;">
                    <?php if ($inStock): ?>
                        <div class="g-qty">
                            <button onclick="adjustDetailQty(-1)" aria-label="Decrease quantity">−</button>
                            <input type="number" id="detail-qty" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" style="border: none; width: 50px; text-align: center; font-weight: 600; height: 40px; outline: none; font-family: var(--g-font);" readonly>
                            <button onclick="adjustDetailQty(1)" aria-label="Increase quantity">+</button>
                        </div>
                        <button class="g-btn g-btn-dark add-to-cart-btn" id="detail-add-btn" data-id="<?php echo $product['id']; ?>" data-qty="1" style="flex: 1; height: 48px;">
                            Add to Cart
                        </button>
                    <?php else: ?>
                        <button class="g-btn g-btn-outline-dark" style="flex: 1; height: 48px; cursor: not-allowed;" disabled>Out of Stock</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Related hint -->
    <section class="g-section g-section-cream" style="padding-top: 2.5rem;">
        <div class="g-container">
            <div class="g-section-head">
                <span class="g-eyebrow">Keep Browsing</span>
                <h2>More from the <span class="g-serif">shop</span></h2>
            </div>
            <a href="/Frontend/pages/shop.php" class="g-btn g-btn-dark">Back to Shop</a>
        </div>
    </section>

</main>

<script>
function adjustDetailQty(delta) {
    const input = document.getElementById('detail-qty');
    if (!input) return;
    let val = parseInt(input.value) + delta;
    const maxVal = parseInt(input.max) || 100;
    if (val < 1) val = 1;
    if (val > maxVal) val = maxVal;
    input.value = val;
    const btn = document.getElementById('detail-add-btn');
    if (btn) {
        btn.setAttribute('data-qty', val);
    }
}
</script>

<?php
include '../includes/footer.php';
?>
