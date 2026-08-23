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
$planPrice = $plan === 'PLUS' ? 'KES 4,500' : 'KES 1,500';
?>

<section class="g-page-hero" style="min-height: 70vh; display: flex; align-items: center; background: linear-gradient(135deg, #0B1220 0%, #166534 50%, #0B1220 100%);">
    <div class="g-container" style="text-align: center; max-width: 650px; padding: 4rem 2rem;">
        
        <!-- Animated Checkmark -->
        <div style="margin-bottom: 2rem; position: relative; display: inline-block;">
            <div style="width: 120px; height: 120px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; box-shadow: 0 0 60px rgba(34, 197, 94, 0.4), 0 0 100px rgba(34, 197, 94, 0.2); animation: pulse 2s infinite;">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <div style="position: absolute; top: -10px; right: -10px; width: 40px; height: 40px; background: #fbbf24; border-radius: 50%; display: flex; align-items: center; justify-content: center; animation: bounce 1s infinite;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B1220" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
            </div>
        </div>
        
        <h1 style="font-size: 2.8rem; margin-bottom: 0.5rem; color: #fff; font-weight: 800; letter-spacing: -1px;">
            Welcome to <span style="color: #22c55e;">Wangari <?= htmlspecialchars($plan) ?></span>!
        </h1>
        
        <p style="font-size: 1.15rem; color: rgba(255,255,255,0.7); margin-bottom: 2.5rem; line-height: 1.7; max-width: 500px; margin-left: auto; margin-right: auto;">
            Your subscription is now active. All premium features are unlocked and ready to use.
        </p>
        
        <!-- Subscription Details Card -->
        <div style="background: rgba(255,255,255,0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.15); border-radius: 20px; padding: 2rem; margin-bottom: 2.5rem; text-align: left;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </div>
                <div>
                    <div style="color: rgba(255,255,255,0.6); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 600;">Subscription Active</div>
                    <div style="color: #fff; font-size: 1.1rem; font-weight: 700;"><?= htmlspecialchars($plan) ?> Plan</div>
                </div>
                <div style="margin-left: auto; background: #22c55e; color: #0B1220; padding: 6px 14px; border-radius: 999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                    Active
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
                <div style="text-align: center;">
                    <div style="color: rgba(255,255,255,0.5); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px;">Plan</div>
                    <div style="color: #fff; font-size: 1.1rem; font-weight: 700;"><?= htmlspecialchars($plan) ?></div>
                </div>
                <div style="text-align: center;">
                    <div style="color: rgba(255,255,255,0.5); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px;">Amount</div>
                    <div style="color: #22c55e; font-size: 1.1rem; font-weight: 700;"><?= $planPrice ?>/mo</div>
                </div>
                <div style="text-align: center;">
                    <div style="color: rgba(255,255,255,0.5); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px;">Next Billing</div>
                    <div style="color: #fff; font-size: 1rem; font-weight: 600;"><?= $billing === 'annual' ? date('M j, Y', strtotime('+1 year')) : date('M j, Y', strtotime('+30 days')) ?></div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 2rem;">
            <a href="/Frontend/admin/dashboard.php" style="display: inline-flex; align-items: center; gap: 10px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: #fff; padding: 16px 32px; border-radius: 999px; font-weight: 700; font-size: 1rem; text-decoration: none; box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4); transition: all 0.3s ease;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
                Go to Dashboard
            </a>
            <a href="/" style="display: inline-flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 16px 32px; border-radius: 999px; font-weight: 600; font-size: 1rem; text-decoration: none; transition: all 0.3s ease;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Visit Website
            </a>
        </div>
        
        <!-- Receipt Info -->
        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; color: rgba(255,255,255,0.5); font-size: 0.9rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
            </svg>
            <span>A receipt has been sent to your email</span>
        </div>
        
    </div>
</section>

<style>
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}
</style>

<?php include '../includes/footer.php'; ?>
