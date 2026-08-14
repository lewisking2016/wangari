<?php
/**
 * Pricing, Wangari (Growvi style)
 * Growvi-style pricing page for the farm platform.
 */
declare(strict_types=1);

$path_prefix = '../';
$page_title = 'Pricing | Wangari';
include '../includes/header.php';
?>

<section class="g-page-hero">
    <div class="g-container">
        <h1>Simple plans for farms of <span class="g-serif">every size</span></h1>
        <p>Start free. Grow with us. Your data stays yours on every plan.</p>
    </div>
</section>

<section class="g-section">
    <div class="g-container">
        <div class="g-grid-3">
            <!-- Starter -->
            <div class="g-card g-reveal g-delay-1" style="display: flex; flex-direction: column;">
                <h4 style="margin-bottom: 0.4rem;">Starter</h4>
                <p style="font-size: 0.9rem; color: var(--g-muted); margin-bottom: 1.5rem;">For new farms getting organised.</p>
                <div style="margin-bottom: 1.8rem;">
                    <span class="g-serif" style="font-size: 3rem; color: var(--g-ink);">Free</span>
                </div>
                <ul style="list-style: none; flex-grow: 1; display: flex; flex-direction: column; gap: 0.7rem; margin-bottom: 1.8rem; font-size: 0.95rem; padding: 0;">
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> Up to 50 animals / 1 flock</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> Sales &amp; expenses tracking</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> Shop with M-Pesa checkout</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> Excel import/export</li>
                </ul>
                <a href="/Frontend/pages/register.php" class="g-btn g-btn-outline-dark" style="width: 100%;">Start Free</a>
            </div>

            <!-- Grow -->
            <div class="g-card g-reveal g-delay-2" style="display: flex; flex-direction: column; border: 2px solid var(--g-lime); position: relative; background: var(--g-ink);">
                <span style="position: absolute; top: -12px; right: 20px; background: var(--g-lime); color: var(--g-ink); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.06em; padding: 0.35rem 0.8rem; border-radius: 999px;">POPULAR</span>
                <h4 style="margin-bottom: 0.4rem; color: #fff;">Grow</h4>
                <p style="font-size: 0.9rem; color: rgba(255,255,255,0.6); margin-bottom: 1.5rem;">For farms ready to scale.</p>
                <div style="margin-bottom: 1.8rem;">
                    <span class="g-serif" style="font-size: 2.6rem; color: var(--g-lime);">KES 2,500</span>
                    <span style="color: rgba(255,255,255,0.6); font-size: 0.9rem;">/month</span>
                </div>
                <ul style="list-style: none; flex-grow: 1; display: flex; flex-direction: column; gap: 0.7rem; margin-bottom: 1.8rem; font-size: 0.95rem; padding: 0;">
                    <li style="display: flex; gap: 10px; color: rgba(255,255,255,0.85);"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> Unlimited animals &amp; flocks</li>
                    <li style="display: flex; gap: 10px; color: rgba(255,255,255,0.85);"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> Feed recipes &amp; stock costing</li>
                    <li style="display: flex; gap: 10px; color: rgba(255,255,255,0.85);"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> Sales, orders, credit &amp; cashbook</li>
                    <li style="display: flex; gap: 10px; color: rgba(255,255,255,0.85);"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> Staff, tasks &amp; calendar</li>
                    <li style="display: flex; gap: 10px; color: rgba(255,255,255,0.85);"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-lime); flex-shrink: 0;"></i> Reports &amp; analytics</li>
                </ul>
                <a href="/Frontend/pages/register.php" class="g-btn g-btn-lime" style="width: 100%;">Choose Grow</a>
            </div>

            <!-- Enterprise -->
            <div class="g-card g-reveal g-delay-3" style="display: flex; flex-direction: column;">
                <h4 style="margin-bottom: 0.4rem;">Enterprise</h4>
                <p style="font-size: 0.9rem; color: var(--g-muted); margin-bottom: 1.5rem;">For agribusinesses &amp; multi-farm operations.</p>
                <div style="margin-bottom: 1.8rem;">
                    <span class="g-serif" style="font-size: 3rem; color: var(--g-ink);">Custom</span>
                </div>
                <ul style="list-style: none; flex-grow: 1; display: flex; flex-direction: column; gap: 0.7rem; margin-bottom: 1.8rem; font-size: 0.95rem; padding: 0;">
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> Multi-location &amp; multi-enterprise</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> Role-based permissions</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> AI assistant &amp; advanced analytics</li>
                    <li style="display: flex; gap: 10px;"><i data-lucide="check" style="width: 18px; height: 18px; color: var(--g-tan); flex-shrink: 0;"></i> Priority support</li>
                </ul>
                <a href="/Frontend/pages/contact.php" class="g-btn g-btn-outline-dark" style="width: 100%;">Contact Sales</a>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
