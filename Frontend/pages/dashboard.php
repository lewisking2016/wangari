<?php
/**
 * Customer Dashboard, Wangari (Growvi style)
 * Clean account overview with serif KPIs.
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) {
    session_save_path($temp_dir);
}
session_start();

// Require login
if (empty($_SESSION['user_id'])) {
    header('Location: /Frontend/pages/login');
    exit;
}

$path_prefix = '../';
$page_title = 'My Account - Wangari';

include '../includes/header.php';

$pdo = getDB();
$user_id = (int)$_SESSION['user_id'];

// Fetch user profile
$user = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        error_log("Dashboard user fetch: " . $e->getMessage());
    }
}

// Fetch order stats
$total_orders = 0;
$total_spent = 0;
$pending_orders = 0;
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as total FROM orders WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_orders = (int)$row['cnt'];
        $total_spent = (float)$row['total'];

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('pending', 'processing')");
        $stmt->execute([$user_id]);
        $pending_orders = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Dashboard stats: " . $e->getMessage());
    }
}

// Fetch recent orders
$recent_orders = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT o.*, 
            (SELECT GROUP_CONCAT(p.name SEPARATOR ', ') FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id LIMIT 3) as product_names
            FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC LIMIT 5");
        $stmt->execute([$user_id]);
        $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Dashboard orders: " . $e->getMessage());
    }
}
?>

<style>
    .user-dash-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 60px 20px 80px;
    }
    .user-dash-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 32px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .user-dash-header h1 {
        font-size: 1.9rem;
        font-weight: 600;
        letter-spacing: -0.02em;
        color: var(--g-text);
        margin: 0;
    }
    .user-dash-header p { margin: 4px 0 0 0; font-size: 0.9rem; color: var(--g-muted); }
    .user-kpi-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 32px;
    }
    .user-kpi-card {
        background: #fff;
        border: 1px solid var(--g-line);
        border-radius: var(--g-radius);
        padding: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .user-kpi-card:hover { box-shadow: 0 14px 34px rgba(0,11,34,0.08); transform: translateY(-3px); }
    .user-kpi-card small {
        display: block;
        color: var(--g-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-size: 0.75rem;
    }
    .user-kpi-card strong {
        display: block;
        margin-top: 8px;
        font-size: 1.9rem;
        font-family: var(--g-serif);
        font-weight: 400;
        color: var(--g-ink);
    }
    .user-kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .user-main-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        margin-bottom: 32px;
    }
    .user-card {
        background: #fff;
        border: 1px solid var(--g-line);
        border-radius: var(--g-radius);
        padding: 24px;
    }
    .user-card h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--g-text);
        margin: 0 0 20px 0;
    }
    .user-order-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 0;
        border-bottom: 1px solid var(--g-line);
        gap: 12px;
    }
    .user-order-row:last-child { border-bottom: none; }
    .user-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    .user-badge-pending { background: #fef3c7; color: #b45309; }
    .user-badge-completed { background: #dcfce7; color: #15803d; }
    .user-badge-cancelled { background: #fee2e2; color: #b91c1c; }
    .user-badge-processing { background: #dbeafe; color: #1d4ed8; }
    .user-badge-shipped { background: #e0e7ff; color: #4338ca; }
    .user-badge-paid { background: #dcfce7; color: #15803d; }
    .user-profile-field {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid var(--g-line);
        font-size: 0.9rem;
    }
    .user-profile-field:last-child { border-bottom: none; }
    .user-profile-field .label { color: var(--g-muted); font-weight: 500; }
    .user-profile-field .value { color: var(--g-text); font-weight: 600; }
    .user-quick-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-top: 20px;
    }
    .user-action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 16px;
        border: 1px solid var(--g-line);
        border-radius: 999px;
        background: #fff;
        color: var(--g-text);
        font-weight: 600;
        font-size: 0.82rem;
        text-decoration: none;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .user-action-btn:hover { border-color: var(--g-ink); background: var(--g-ink); color: #fff; }
    @media (max-width: 768px) {
        .user-kpi-grid { grid-template-columns: 1fr; }
        .user-main-grid { grid-template-columns: 1fr; }
        .user-quick-actions { grid-template-columns: 1fr; }
    }
</style>

<div class="user-dash-container">
    <!-- Header -->
    <div class="user-dash-header">
        <div>
            <h1>Welcome back, <?php echo htmlspecialchars($user['first_name'] ?? $user['username'] ?? 'Customer'); ?></h1>
            <p>Here's an overview of your account activity.</p>
        </div>
        <a href="/Frontend/pages/shop.php" class="g-btn g-btn-lime">
            <span>Continue Shopping</span>
        </a>
    </div>

    <!-- KPI Cards -->
    <div class="user-kpi-grid">
        <div class="user-kpi-card">
            <div>
                <small>Total Orders</small>
                <strong><?php echo $total_orders; ?></strong>
            </div>
            <div class="user-kpi-icon" style="background: rgba(208,242,76,0.25); color: var(--g-ink);">
                <i data-lucide="shopping-bag" style="width: 22px; height: 22px;"></i>
            </div>
        </div>
        <div class="user-kpi-card">
            <div>
                <small>Amount Spent</small>
                <strong>KES <?php echo number_format($total_spent); ?></strong>
            </div>
            <div class="user-kpi-icon" style="background: rgba(208,242,76,0.25); color: var(--g-ink);">
                <i data-lucide="wallet" style="width: 22px; height: 22px;"></i>
            </div>
        </div>
        <div class="user-kpi-card">
            <div>
                <small>Pending Orders</small>
                <strong><?php echo $pending_orders; ?></strong>
            </div>
            <div class="user-kpi-icon" style="background: rgba(217,119,6,0.12); color: #d97706;">
                <i data-lucide="clock" style="width: 22px; height: 22px;"></i>
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="user-main-grid">
        <!-- Recent Orders -->
        <div class="user-card" style="padding: 0; overflow: hidden;">
            <div style="padding: 20px 24px; border-bottom: 1px solid var(--g-line); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">Recent Orders</h3>
            </div>
            <div style="padding: 0 24px;">
                <?php if (!empty($recent_orders)): ?>
                    <?php foreach ($recent_orders as $order): ?>
                    <?php 
                        $badge = 'user-badge-pending';
                        $s = strtolower($order['status']);
                        if ($s === 'completed' || $s === 'delivered') $badge = 'user-badge-completed';
                        elseif ($s === 'cancelled') $badge = 'user-badge-cancelled';
                        elseif ($s === 'picking' || $s === 'packing' || $s === 'production' || $s === 'dispatch') $badge = 'user-badge-processing';
                        elseif ($s === 'shipped') $badge = 'user-badge-shipped';
                        elseif ($s === 'paid') $badge = 'user-badge-paid';
                    ?>
                    <div class="user-order-row">
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 600; color: var(--g-text); font-size: 0.9rem;">
                                Order #<?php echo htmlspecialchars($order['order_number']); ?>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--g-muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo htmlspecialchars($order['product_names'] ?? 'Items'); ?>
                            </div>
                        </div>
                        <div style="text-align: right; flex-shrink: 0;">
                            <div style="font-weight: 600; color: var(--g-text); font-size: 0.9rem;">
                                KES <?php echo number_format((float)$order['total_amount']); ?>
                            </div>
                            <span class="user-badge <?php echo $badge; ?>" style="margin-top: 4px; display: inline-block;">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 40px 0; text-align: center; color: var(--g-muted);">
                        <i data-lucide="package" style="width: 40px; height: 40px; margin-bottom: 12px; opacity: 0.4;"></i>
                        <p style="margin: 0; font-size: 0.95rem;">No orders yet. Start shopping to see your orders here.</p>
                        <a href="/Frontend/pages/shop.php" style="display: inline-block; margin-top: 16px; color: var(--g-tan); font-weight: 600; font-size: 0.9rem;">Browse Products →</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Profile & Quick Actions -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Profile Summary -->
            <div class="user-card">
                <h3>My Profile</h3>
                <div class="user-profile-field">
                    <span class="label">Name</span>
                    <span class="value"><?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Not set'); ?></span>
                </div>
                <div class="user-profile-field">
                    <span class="label">Email</span>
                    <span class="value"><?php echo htmlspecialchars($user['email'] ?? '-'); ?></span>
                </div>
                <div class="user-profile-field">
                    <span class="label">Phone</span>
                    <span class="value"><?php echo htmlspecialchars($user['phone_number'] ?? 'Not set'); ?></span>
                </div>
                <div class="user-profile-field">
                    <span class="label">Member Since</span>
                    <span class="value"><?php echo isset($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : '-'; ?></span>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="user-card">
                <h3>Quick Actions</h3>
                <div class="user-quick-actions">
                    <a href="/Frontend/pages/shop.php" class="user-action-btn">
                        <i data-lucide="shopping-cart" style="width: 16px; height: 16px;"></i>
                        Shop Now
                    </a>
                    <a href="/Frontend/pages/cart.php" class="user-action-btn">
                        <i data-lucide="package" style="width: 16px; height: 16px;"></i>
                        View Cart
                    </a>
                    <a href="/Frontend/pages/contact.php" class="user-action-btn">
                        <i data-lucide="headphones" style="width: 16px; height: 16px;"></i>
                        Support
                    </a>
                    <a href="/Frontend/pages/logout.php" class="user-action-btn" style="color: #dc2626;">
                        <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
