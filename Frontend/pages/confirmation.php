<?php
/**
 * Order Confirmation, Wangari
 * Growvi design language.
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) {
    session_save_path($temp_dir);
}
session_start();

$path_prefix = '../';
$page_title = 'Order Confirmation - Wangari';

include '../includes/header.php';

// Redirect if no recent order
if (empty($_SESSION['last_order'])) {
    echo "<script>window.location.href = '/Frontend/pages/shop.php';</script>";
    exit;
}

$order = $_SESSION['last_order'];
?>

<main class="g-main">

    <section class="g-page-hero">
        <div class="g-container">
            <h1>Order <span class="g-serif">Complete</span></h1>
            <p>Your order has been placed successfully.</p>
        </div>
    </section>

    <section class="g-section">
        <div class="g-container" style="max-width: 720px;">
            <div style="text-align: center; margin-bottom: 2.5rem;">
                <div style="width: 88px; height: 88px; background: #ECFDF5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.6rem; color: #15803d;">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <h2 style="font-size: clamp(1.8rem, 4vw, 2.4rem); margin-bottom: 0.8rem;">Order Placed Successfully!</h2>
                <p style="color: var(--g-muted); font-size: 1.05rem; max-width: 46ch; margin: 0 auto;">
                    We've received your order and are processing it now. A confirmation has been sent to your email and phone.
                </p>
            </div>

            <div class="g-summary" style="text-align: left;">
                <h3 style="font-size: 1.1rem; margin-bottom: 1.2rem; border-bottom: 1px solid var(--g-line); padding-bottom: 0.8rem;">Order Details</h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; margin-bottom: 1.4rem;">
                    <div>
                        <div style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--g-muted); margin-bottom: 0.3rem;">Order Number</div>
                        <div style="font-weight: 700; color: var(--g-text);"><?php echo htmlspecialchars($order['order_number'] ?? ''); ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--g-muted); margin-bottom: 0.3rem;">Date</div>
                        <div style="font-weight: 700; color: var(--g-text);"><?php echo date('M d, Y', strtotime($order['created_at'] ?? 'now')); ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--g-muted); margin-bottom: 0.3rem;">Total Amount</div>
                        <div style="font-weight: 800; color: var(--g-ink); font-size: 1.2rem;">KES <?php echo number_format((float)($order['total_amount'] ?? 0), 0); ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--g-muted); margin-bottom: 0.3rem;">Payment Method</div>
                        <div style="font-weight: 700; color: var(--g-text); text-transform: capitalize;">
                            <?php
                                $pm = $order['payment_method'] ?? '';
                                if ($pm === 'mpesa') echo 'M-Pesa';
                                elseif ($pm === 'cod') echo 'Cash on Delivery';
                                elseif ($pm === 'bank') echo 'Bank Transfer';
                                else echo htmlspecialchars(str_replace('_', ' ', $pm));
                            ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($order['delivery_charge']) || isset($order['subtotal'])): ?>
                <div style="border-top: 1px solid var(--g-line); padding-top: 1rem;">
                    <div class="g-summary-row"><span>Subtotal</span><span>KES <?php echo number_format((float)($order['subtotal'] ?? $order['total_amount'] ?? 0), 0); ?></span></div>
                    <div class="g-summary-row"><span>Delivery</span><span><?php echo (float)($order['delivery_charge'] ?? 0) > 0 ? 'KES ' . number_format((float)$order['delivery_charge'], 0) : '<span style="color:#15803d;font-weight:700;">FREE</span>'; ?></span></div>
                </div>
                <?php endif; ?>

                <div style="margin-top: 1.8rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="/Frontend/pages/shop.php" class="g-btn g-btn-dark">Continue Shopping</a>
                    <a href="/Frontend/pages/dashboard.php" class="g-btn g-btn-outline-dark">My Dashboard</a>
                </div>
            </div>
        </div>
    </section>

</main>

<?php
include '../includes/footer.php';
?>
