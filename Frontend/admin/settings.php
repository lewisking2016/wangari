<?php
/**
 * Admin - Settings & Configuration
 * Clean SaaS Minimalist Design
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) {
    session_save_path($temp_dir);
}
session_start();

$path_prefix = '../../';
$page_title = 'System Settings - Admin';

include __DIR__ . '/includes/admin_header.php';
$csrf_token = function_exists('generateCSRFToken') ? generateCSRFToken() : ($_SESSION['csrf_token'] ?? '');

// Check admin access
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','sales_staff'], true)) {
    echo "<script>window.location.href = '/Frontend/admin/login.php';</script>";
    exit;
}

// Handle form submission
$save_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $save_message = 'Security token expired. Please refresh and try again.';
    } else {
        $textSettings = ['farm_name', 'farm_email', 'farm_phone', 'farm_address', 'currency', 'timezone', 'mpesa_shortcode', 'mpesa_passkey'];
        foreach ($textSettings as $key) {
            if (array_key_exists($key, $_POST)) {
                updateSetting($key, trim((string)$_POST[$key]));
            }
        }

        $toggles = ['mpesa_enabled', 'cod_enabled', 'order_notify', 'stock_notify', 'weekly_report'];
        foreach ($toggles as $toggle) {
            updateSetting($toggle, isset($_POST[$toggle]) ? '1' : '0');
        }
        $save_message = 'Settings saved successfully.';
    }
}
?>

<!-- Page Title -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.5rem; color: var(--admin-text-heading);">System Settings</h2>
        <p style="margin: 4px 0 0 0; font-size: 0.875rem; color: #64748b;">Configure your farm management platform.</p>
    </div>
    <button class="btn btn-primary" style="border-radius: 4px; display: flex; align-items: center; gap: 8px;" onclick="document.getElementById('settings-form').submit();">
        <i data-lucide="save" style="width: 18px; height: 18px;"></i>
        <span>Save Changes</span>
    </button>
</div>

<?php if ($save_message): ?>
<div style="padding: 12px 20px; background: #dcfce7; border: 1px solid #bbf7d0; border-radius: 4px; color: #15803d; font-size: 0.9rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
    <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
    <?php echo $save_message; ?>
</div>
<?php endif; ?>

<form id="settings-form" method="POST" action="">
<input type="hidden" name="save_settings" value="1">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

<!-- Settings Sections -->
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- General Configuration -->
    <div class="admin-card">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--admin-border);">
            <i data-lucide="globe" style="width: 20px; height: 20px; color: var(--admin-primary);"></i>
            <h3 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.15rem; color: var(--admin-text-heading);">General Configuration</h3>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="admin-form-group">
                <label class="admin-form-label">Farm Name</label>
                <input type="text" name="farm_name" value="<?php echo htmlspecialchars(getSetting('farm_name', 'Wangari')); ?>" class="admin-form-control">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Contact Email</label>
                <input type="email" name="farm_email" value="<?php echo htmlspecialchars(getSetting('farm_email', 'info@wangari.com')); ?>" class="admin-form-control">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Phone Number</label>
                <input type="text" name="farm_phone" value="<?php echo htmlspecialchars(getSetting('farm_phone', '+254 700 000 000')); ?>" class="admin-form-control">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Location</label>
                <input type="text" name="farm_address" value="<?php echo htmlspecialchars(getSetting('farm_address', 'Wangari, Kenya')); ?>" class="admin-form-control">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Currency</label>
                <select name="currency" class="admin-form-control">
                    <option value="KES" <?php echo getSetting('currency') === 'KES' ? 'selected' : ''; ?>>KES - Kenyan Shilling</option>
                    <option value="USD" <?php echo getSetting('currency') === 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                    <option value="UGX" <?php echo getSetting('currency') === 'UGX' ? 'selected' : ''; ?>>UGX - Ugandan Shilling</option>
                </select>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Timezone</label>
                <select name="timezone" class="admin-form-control">
                    <option value="Africa/Nairobi" <?php echo getSetting('timezone') === 'Africa/Nairobi' ? 'selected' : ''; ?>>Africa/Nairobi (EAT)</option>
                    <option value="UTC" <?php echo getSetting('timezone') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Payment Settings -->
    <div class="admin-card">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--admin-border);">
            <i data-lucide="credit-card" style="width: 20px; height: 20px; color: var(--admin-primary);"></i>
            <h3 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.15rem; color: var(--admin-text-heading);">Payment Integration</h3>
        </div>

        <!-- M-Pesa -->
        <div style="margin-bottom: 24px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <div>
                    <h4 style="margin: 0; font-size: 1rem; color: var(--admin-text-heading);">M-Pesa Express (STK Push)</h4>
                    <p style="font-size: 0.8rem; color: #64748b; margin: 4px 0 0 0;">Direct mobile money payments via Safaricom.</p>
                </div>
                <label class="admin-toggle">
                    <input type="checkbox" name="mpesa_enabled" <?php echo getSetting('mpesa_enabled', '1') === '1' ? 'checked' : ''; ?>>
                    <span class="admin-toggle-slider"></span>
                </label>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="admin-form-group">
                    <label class="admin-form-label">Business Shortcode</label>
                    <input type="text" name="mpesa_shortcode" value="<?php echo htmlspecialchars(getSetting('mpesa_shortcode', '174379')); ?>" class="admin-form-control">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Passkey</label>
                    <input type="password" name="mpesa_passkey" value="<?php echo htmlspecialchars(getSetting('mpesa_passkey', '')); ?>" class="admin-form-control">
                </div>
            </div>
        </div>

        <!-- Cash on Delivery -->
        <div style="border-top: 1px solid var(--admin-border); padding-top: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h4 style="margin: 0; font-size: 1rem; color: var(--admin-text-heading);">Cash on Delivery</h4>
                    <p style="font-size: 0.8rem; color: #64748b; margin: 4px 0 0 0;">Allow customers to pay upon receipt.</p>
                </div>
                <label class="admin-toggle">
                    <input type="checkbox" name="cod_enabled" <?php echo getSetting('cod_enabled', '0') === '1' ? 'checked' : ''; ?>>
                    <span class="admin-toggle-slider"></span>
                </label>
            </div>
        </div>
    </div>

    <!-- Notification Settings -->
    <div class="admin-card">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--admin-border);">
            <i data-lucide="bell" style="width: 20px; height: 20px; color: var(--admin-primary);"></i>
            <h3 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.15rem; color: var(--admin-text-heading);">Notifications</h3>
        </div>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--admin-border);">
                <div>
                    <div style="font-weight: 600; color: var(--admin-text-heading); font-size: 0.95rem;">Order Notifications</div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">Email alerts when new orders arrive.</div>
                </div>
                <label class="admin-toggle">
                    <input type="checkbox" name="order_notify" <?php echo getSetting('order_notify', '1') === '1' ? 'checked' : ''; ?>>
                    <span class="admin-toggle-slider"></span>
                </label>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--admin-border);">
                <div>
                    <div style="font-weight: 600; color: var(--admin-text-heading); font-size: 0.95rem;">Low Stock Alerts</div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">Get notified when items drop below threshold.</div>
                </div>
                <label class="admin-toggle">
                    <input type="checkbox" name="stock_notify" <?php echo getSetting('stock_notify', '1') === '1' ? 'checked' : ''; ?>>
                    <span class="admin-toggle-slider"></span>
                </label>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0;">
                <div>
                    <div style="font-weight: 600; color: var(--admin-text-heading); font-size: 0.95rem;">Weekly Reports</div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">Automated weekly performance summaries.</div>
                </div>
                <label class="admin-toggle">
                    <input type="checkbox" name="weekly_report" <?php echo getSetting('weekly_report', '0') === '1' ? 'checked' : ''; ?>>
                    <span class="admin-toggle-slider"></span>
                </label>
            </div>
        </div>
    </div>
</div>
</form>

<?php
include __DIR__ . '/includes/admin_footer.php';
?>
