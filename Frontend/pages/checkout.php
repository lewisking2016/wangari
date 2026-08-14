<?php
/**
 * Checkout, Wangari
 * Growvi design language. Form contract preserved for /Backend/api/checkout.php.
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) {
    session_save_path($temp_dir);
}

$path_prefix = '../';
$page_title = 'Checkout - Wangari';

include '../includes/header.php';
$csrf_token = function_exists('generateCSRFToken') ? generateCSRFToken() : ($_SESSION['csrf_token'] ?? '');
$mpesa_enabled = function_exists('getSetting') ? getSetting('mpesa_enabled', '1') === '1' : true;
$cod_enabled = function_exists('getSetting') ? getSetting('cod_enabled', '0') === '1' : false;

// Get database connection
$pdo = getDB();

// Redirect to cart if empty
if (empty($_SESSION['cart'])) {
    echo "<script>window.location.href = '/Frontend/pages/cart.php';</script>";
    exit;
}

// Calculate totals from database
$subtotal = 0;
$ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$db_products = fetchAll($pdo, "SELECT * FROM products WHERE id IN ($placeholders)", $ids);

$productMap = [];
foreach ($db_products as $p) {
    $productMap[$p['id']] = $p;
}

foreach ($_SESSION['cart'] as $product_id => $quantity) {
    if (isset($productMap[$product_id])) {
        $subtotal += (float)$productMap[$product_id]['price'] * $quantity;
    }
}

$delivery_charge = ($subtotal >= 5000) ? 0 : 500;
$total_amount = $subtotal + $delivery_charge;
?>

<main class="g-main">

    <section class="g-page-hero">
        <div class="g-container">
            <h1>Check<span class="g-serif">out</span></h1>
            <p>Complete your order by providing delivery and payment details.</p>
        </div>
    </section>

    <section class="g-section">
        <div class="g-container">
            <form id="checkout-form" class="g-stack-mobile" style="display: grid; grid-template-columns: 1.3fr 0.7fr; gap: 3rem; align-items: start;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                <!-- Left: Delivery + Payment -->
                <div>
                    <div class="g-form-card" style="margin-bottom: 2rem;">
                        <h3 style="display: flex; align-items: center; gap: 0.8rem; margin-bottom: 1.6rem;">
                            <span style="width: 34px; height: 34px; background: var(--g-ink); color: var(--g-lime); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 700;">1</span>
                            Delivery Information
                        </h3>

                        <div class="g-form" style="grid-template-columns: 1fr 1fr; gap: 1.2rem;">
                            <div class="g-field">
                                <label for="first_name">First Name *</label>
                                <input type="text" name="first_name" id="first_name" required>
                            </div>
                            <div class="g-field">
                                <label for="last_name">Last Name *</label>
                                <input type="text" name="last_name" id="last_name" required>
                            </div>
                            <div class="g-field">
                                <label for="email">Email Address *</label>
                                <input type="email" name="email" id="email" required>
                            </div>
                            <div class="g-field">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" name="phone" id="phone" placeholder="e.g. 0727585599" required>
                            </div>
                            <div class="g-field" style="grid-column: 1 / -1;">
                                <label for="address">Delivery Address *</label>
                                <textarea name="address" id="address" required></textarea>
                            </div>
                            <div class="g-field">
                                <label for="city">City / Town *</label>
                                <input type="text" name="city" id="city" required>
                            </div>
                            <div class="g-field">
                                <label for="notes">Order Notes (optional)</label>
                                <input type="text" name="notes" id="notes" placeholder="Any delivery instructions">
                            </div>
                        </div>
                    </div>

                    <div class="g-form-card">
                        <h3 style="display: flex; align-items: center; gap: 0.8rem; margin-bottom: 1.6rem;">
                            <span style="width: 34px; height: 34px; background: var(--g-ink); color: var(--g-lime); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 700;">2</span>
                            Payment Method
                        </h3>

                        <div style="display: flex; flex-direction: column; gap: 0.9rem;">
                            <?php if ($mpesa_enabled): ?>
                            <label style="display: flex; align-items: center; gap: 1rem; padding: 1.2rem; border: 1px solid var(--g-line); border-radius: var(--g-radius-sm); cursor: pointer; transition: border-color 0.2s, background 0.2s;">
                                <input type="radio" name="payment_method" value="mpesa" checked style="accent-color: var(--g-lime); width: 18px; height: 18px;">
                                <div style="flex-grow: 1;">
                                    <div style="font-weight: 600; color: var(--g-text);">M-Pesa</div>
                                    <div style="font-size: 0.85rem; color: var(--g-muted);">Pay instantly using your phone</div>
                                </div>
                                <span style="display: inline-flex; align-items: center; justify-content: center; height: 24px; padding: 0 12px; border-radius: 999px; background: #d1fae5; color: #065f46; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.04em;">M-PESA</span>
                            </label>
                            <?php endif; ?>

                            <label style="display: flex; align-items: center; gap: 1rem; padding: 1.2rem; border: 1px solid var(--g-line); border-radius: var(--g-radius-sm); cursor: pointer; transition: border-color 0.2s, background 0.2s;">
                                <input type="radio" name="payment_method" value="bank" <?php echo $mpesa_enabled ? '' : 'checked'; ?> style="accent-color: var(--g-lime); width: 18px; height: 18px;">
                                <div style="flex-grow: 1;">
                                    <div style="font-weight: 600; color: var(--g-text);">Bank Transfer</div>
                                    <div style="font-size: 0.85rem; color: var(--g-muted);">Direct bank deposit or transfer</div>
                                </div>
                            </label>

                            <?php if ($cod_enabled): ?>
                            <label style="display: flex; align-items: center; gap: 1rem; padding: 1.2rem; border: 1px solid var(--g-line); border-radius: var(--g-radius-sm); cursor: pointer; transition: border-color 0.2s, background 0.2s;">
                                <input type="radio" name="payment_method" value="cod" style="accent-color: var(--g-lime); width: 18px; height: 18px;">
                                <div style="flex-grow: 1;">
                                    <div style="font-weight: 600; color: var(--g-text);">Cash on Delivery</div>
                                    <div style="font-size: 0.85rem; color: var(--g-muted);">Pay when you receive your order</div>
                                </div>
                            </label>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Order Summary -->
                <aside>
                    <div class="g-summary" style="position: sticky; top: 110px;">
                        <h3 style="font-size: 1.15rem; margin-bottom: 1.2rem;">Order Summary</h3>

                        <div style="max-height: 300px; overflow-y: auto; margin-bottom: 1.2rem;">
                            <?php foreach ($_SESSION['cart'] as $id => $qty):
                                if (isset($productMap[$id])):
                                    $p = $productMap[$id];
                                    $img = $p['image_url'] ?? '';
                                    if (!$img) {
                                        $type = $p['product_type'] ?? 'feed';
                                        $img = match($type) {
                                            'feed' => '/Frontend/images/Growers Mash.png',
                                            'eggs' => '/Frontend/images/download (3).png',
                                            'chicks' => '/Frontend/images/download (7).png',
                                            'live_chicken' => '/Frontend/images/download (4).png',
                                            default => '/Frontend/images/Chick Starter Crumbs.png'
                                        };
                                    }
                            ?>
                            <div style="display: flex; gap: 0.9rem; margin-bottom: 1rem; align-items: center;">
                                <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" style="width: 52px; height: 52px; border-radius: var(--g-radius-sm); object-fit: cover; border: 1px solid var(--g-line);">
                                <div style="flex-grow: 1;">
                                    <div style="font-weight: 600; color: var(--g-text); font-size: 0.92rem;"><?php echo htmlspecialchars($p['name']); ?></div>
                                    <div style="font-size: 0.8rem; color: var(--g-muted);">Qty <?php echo $qty; ?></div>
                                </div>
                                <div style="font-weight: 600; color: var(--g-text); font-size: 0.9rem;">KES <?php echo number_format((float)$p['price'] * $qty, 0); ?></div>
                            </div>
                            <?php endif; endforeach; ?>
                        </div>

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
                            <span>KES <?php echo number_format($total_amount, 0); ?></span>
                        </div>

                        <button type="submit" id="checkout-submit" class="g-btn g-btn-lime" style="width: 100%; margin-top: 1.4rem;">
                            Place Order
                        </button>
                        <div style="margin-top: 1.2rem; display: flex; align-items: center; gap: 0.5rem; justify-content: center; color: var(--g-muted); font-size: 0.82rem;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            Secure Checkout · M-Pesa & Bank
                        </div>
                    </div>
                </aside>
            </form>
        </div>
    </section>

</main>

<script>
document.getElementById('checkout-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const submitBtn = document.getElementById('checkout-submit');
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Processing...';

    try {
        const formData = new FormData(e.target);
        const response = await fetch('/Backend/api/checkout.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            if (typeof WangariApp !== 'undefined' && WangariApp.showNotification) {
                WangariApp.showNotification('Order placed successfully!', 'success');
            }
            setTimeout(() => {
                window.location.href = '/Frontend/pages/confirmation.php';
            }, 1500);
        } else {
            if (typeof WangariApp !== 'undefined' && WangariApp.showNotification) {
                WangariApp.showNotification(result.message || 'Checkout failed', 'error');
            }
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Place Order';
        }
    } catch (error) {
        console.error(error);
        if (typeof WangariApp !== 'undefined' && WangariApp.showNotification) {
            WangariApp.showNotification('Error processing order', 'error');
        }
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Place Order';
    }
});
</script>

<?php
include '../includes/footer.php';
?>
