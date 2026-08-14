<?php
/**
 * Privacy Policy, Wangari (Growvi style)
 */
declare(strict_types=1);

$path_prefix = '../';
$page_title = 'Privacy Policy | Wangari';
include '../includes/header.php';
?>

<section class="g-page-hero">
    <div class="g-container">
        <h1>Privacy <span class="g-serif">Policy</span></h1>
        <p>Your data is yours. We are built on that principle, in the spirit of Wangari Maathai.</p>
    </div>
</section>

<section class="g-section">
    <div class="g-container" style="max-width: 760px;">
        <div class="g-form-card">
            <h3 style="margin-bottom: 0.8rem;">1. Your data belongs to you</h3>
            <p style="color: var(--g-muted);">All farm records, customer information, and financial data you enter into Wangari are yours. You can export or delete your data at any time, no lock-in, no hidden fees.</p>

            <h3 style="margin-top: 2rem; margin-bottom: 0.8rem;">2. What we collect</h3>
            <p style="color: var(--g-muted);">We collect only what is needed to run the platform: your account details (name, email, phone), and the farm data you choose to record.</p>

            <h3 style="margin-top: 2rem; margin-bottom: 0.8rem;">3. What we do not do</h3>
            <p style="color: var(--g-muted);">We do not sell your data. We do not share your farm records with third parties without your consent. Payment information is processed securely through your chosen provider (e.g. M-Pesa via Safaricom's API).</p>

            <h3 style="margin-top: 2rem; margin-bottom: 0.8rem;">4. Security</h3>
            <p style="color: var(--g-muted);">We use encryption, secure sessions, and role-based access to protect your information. Access to your account is protected by strong password hashing.</p>

            <h3 style="margin-top: 2rem; margin-bottom: 0.8rem;">5. Contact</h3>
            <p style="color: var(--g-muted);">Questions about this policy? Contact us at <a href="mailto:info@wangari.farm" style="color: var(--g-tan); font-weight: 600;">info@wangari.farm</a>.</p>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
