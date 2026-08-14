<?php
/**
 * Shopping Cart, Wangari
 * Growvi design language.
 */
declare(strict_types=1);

$path_prefix = '../';
$page_title = 'Shopping Cart | Wangari';

if (session_status() === PHP_SESSION_NONE) {
    $temp_dir = sys_get_temp_dir();
    if (is_writable($temp_dir)) session_save_path($temp_dir);
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
$pdo = getDB();

// Load cart items
$cart_items = [];
$subtotal = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $products = fetchAll($pdo, "SELECT * FROM products WHERE id IN ($placeholders)", $ids);
    foreach ($products as $product) {
        $qty = (int)($_SESSION['cart'][$product['id']] ?? 1);
        $price = (float)$product['price'];
        $total = $price * $qty;
        $subtotal += $total;
        $cart_items[] = [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'price' => $price,
            'quantity' => $qty,
            'total' => $total,
            'product_type' => $product['product_type'] ?? 'product',
            'image' => $product['image_url'] ?? '',
        ];
    }
}

$delivery_charge = ($subtotal >= 5000 || $subtotal == 0) ? 0 : 500;
$total_amount = $subtotal + $delivery_charge;

include '../includes/header.php';
?>

<main class="g-main">

    <section class="g-page-hero">
        <div class="g-container">
            <h1>Your <span class="g-serif">Cart</span></h1>
            <p>Review your items before proceeding to checkout.</p>
        </div>
    </section>

    <section class="g-section">
        <div class="g-container">
            <?php if (empty($cart_items)): ?>
                <!-- Empty cart -->
                <div style="text-align: center; padding: 4rem 0; max-width: 420px; margin: 0 auto;">
                    <div style="width: 84px; height: 84px; background: var(--g-cream); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.8rem; color: var(--g-muted);">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                    </div>
                    <h2 style="margin-bottom: 0.8rem;">Your cart is empty</h2>
                    <p style="color: var(--g-muted); margin-bottom: 2rem;">
                        Looks like you haven't added any products to your cart yet. Browse our shop to find the best poultry products.
                    </p>
                    <a href="/Frontend/pages/shop.php" class="g-btn g-btn-dark">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="g-stack-mobile" style="display: grid; grid-template-columns: 1fr 360px; gap: 2.5rem; align-items: start;">

                    <!-- Items list -->
                    <div class="g-table-wrap">
                        <table class="g-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th style="text-align: right;">Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item):
                                    $img = $item['image'];
                                    if (!$img) {
                                        $img = match($item['product_type']) {
                                            'feed' => '/Frontend/images/Growers Mash.png',
                                            'eggs' => '/Frontend/images/download (3).png',
                                            'chicks' => '/Frontend/images/download (7).png',
                                            'live_chicken' => '/Frontend/images/download (4).png',
                                            default => '/Frontend/images/Chick Starter Crumbs.png'
                                        };
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 64px; height: 64px; border-radius: var(--g-radius-sm); object-fit: cover; border: 1px solid var(--g-line);">
                                            <div>
                                                <div style="font-weight: 600; color: var(--g-text);"><?php echo htmlspecialchars($item['name']); ?></div>
                                                <div style="font-size: 0.82rem; color: var(--g-muted); text-transform: capitalize;"><?php echo str_replace('_', ' ', $item['product_type']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="color: var(--g-muted);">KES <?php echo number_format($item['price'], 0); ?></td>
                                    <td>
                                        <div class="g-qty">
                                            <button onclick="updateQty(<?php echo $item['id']; ?>, -1)" aria-label="Decrease">−</button>
                                            <span id="qty-<?php echo $item['id']; ?>" style="min-width: 40px; text-align: center; font-weight: 600;"><?php echo $item['quantity']; ?></span>
                                            <button onclick="updateQty(<?php echo $item['id']; ?>, 1)" aria-label="Increase">+</button>
                                        </div>
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: var(--g-text);">KES <?php echo number_format($item['total'], 0); ?></td>
                                    <td style="text-align: right;">
                                        <button onclick="removeItem(<?php echo $item['id']; ?>)" style="background: none; border: none; cursor: pointer; color: var(--g-muted);" aria-label="Remove item">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div style="margin-top: 1.6rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.8rem;">
                            <a href="/Frontend/pages/shop.php" class="g-btn g-btn-outline-dark" style="font-size: 0.88rem;">
                                ← Continue Shopping
                            </a>
                            <button onclick="clearCart()" style="background: none; border: none; color: var(--g-muted); cursor: pointer; font-size: 0.9rem; text-decoration: underline;">Clear Cart</button>
                        </div>
                    </div>

                    <!-- Summary -->
                    <aside>
                        <div class="g-summary">
                            <h3 style="font-size: 1.15rem; margin-bottom: 1.2rem;">Order Summary</h3>
                            <div class="g-summary-row">
                                <span>Subtotal</span>
                                <span>KES <?php echo number_format($subtotal, 0); ?></span>
                            </div>
                            <div class="g-summary-row">
                                <span>Delivery</span>
                                <span>
                                    <?php if ($delivery_charge == 0): ?>
                                        <span style="color: #15803d; font-weight: 700;">FREE</span>
                                    <?php else: ?>
                                        KES <?php echo number_format($delivery_charge, 0); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="g-summary-row g-total">
                                <span>Total</span>
                                <span style="color: var(--g-ink);">KES <?php echo number_format($total_amount, 0); ?></span>
                            </div>
                            <a href="/Frontend/pages/checkout.php" class="g-btn g-btn-lime" style="width: 100%; margin-top: 1.4rem;">
                                Proceed to Checkout →
                            </a>
                            <div style="margin-top: 1.4rem; display: flex; align-items: center; gap: 0.5rem; justify-content: center; color: var(--g-muted); font-size: 0.82rem;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                Secure Checkout · M-Pesa & Bank
                            </div>
                        </div>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </section>

</main>

<script>
async function updateQty(id, delta) {
    const qtyEl = document.getElementById(`qty-${id}`);
    let newQty = parseInt(qtyEl.textContent) + delta;
    if (newQty < 1) return;

    try {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('product_id', id);
        formData.append('quantity', newQty);

        const response = await fetch('/Backend/api/cart.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            location.reload();
        }
    } catch (e) {
        console.error(e);
    }
}

async function removeItem(id) {
    if (!confirm('Remove this item from cart?')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('product_id', id);

        const response = await fetch('/Backend/api/cart.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            location.reload();
        }
    } catch (e) {
        console.error(e);
    }
}

async function clearCart() {
    if (!confirm('Clear all items from cart?')) return;

    try {
        const response = await fetch('/Backend/api/cart.php?action=clear');
        const result = await response.json();
        if (result.success) {
            location.reload();
        }
    } catch (e) {
        console.error(e);
    }
}
</script>

<?php
include '../includes/footer.php';
?>
