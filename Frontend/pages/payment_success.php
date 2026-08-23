<?php
/**
 * Payment Success Page
 * Shows confirmation after successful payment
 */
declare(strict_types=1);

$path_prefix = '../';
$page_title = 'Payment Successful | Wangari';
include '../includes/header.php';

$plan = $_GET['plan'] ?? 'PRO';
$billing = $_GET['billing'] ?? 'monthly';
?>

<section class="g-page-hero" style="min-height: 60vh; display: flex; align-items: center;">
    <div class="g-container" style="text-align: center; max-width: 600px;">
        <div style="margin-bottom: 2rem;">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" style="margin: 0 auto;">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        
        <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--g-ink);">
            Payment Successful!
        </h1>
        
        <p style="font-size: 1.1rem; color: var(--g-muted); margin-bottom: 2rem; line-height: 1.6;">
            Thank you for subscribing to <strong>Wangari <?= htmlspecialchars($plan) ?></strong>. 
            Your account has been upgraded and all features are now unlocked.
        </p>
        
        <div style="background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
            <p style="margin: 0; font-size: 0.9rem; color: var(--g-ink);">
                <strong>Plan:</strong> <?= htmlspecialchars($plan) ?> (<?= ucfirst(htmlspecialchars($billing)) ?>)<br>
                <strong>Status:</strong> Active<br>
                <strong>Next billing:</strong> <?= $billing === 'annual' ? date('F j, Y', strtotime('+1 year')) : date('F j, Y', strtotime('+30 days')) ?>
            </p>
        </div>
        
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="/Frontend/admin/dashboard.php" class="g-btn g-btn-lime" style="min-width: 200px;">
                Go to Dashboard
            </a>
            <a href="/Frontend/pages/pricing.php" class="g-btn g-btn-outline-dark" style="min-width: 200px;">
                View Plans
            </a>
        </div>
        
        <p style="margin-top: 2rem; font-size: 0.85rem; color: var(--g-muted);">
            A receipt has been sent to your email. If you have any questions, 
            <a href="/Frontend/pages/contact.php" style="color: var(--g-lime);">contact support</a>.
        </p>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
