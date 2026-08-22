<?php
/**
 * Super Admin Control Center — Premium Redesign
 * Platform management — users, accounts, system health, analytics
 */
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
$page_title = 'Platform Control Center — Wangari';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header('Location: /Frontend/admin/login.php');
    exit;
}

include __DIR__ . '/includes/admin_header.php';
$pdo = getDB();

$stats = [];
try {
    $stats['total_users'] = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['active_users'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1 OR is_active IS NULL")->fetchColumn();
    $stats['inactive_users'] = $stats['total_users'] - $stats['active_users'];
    $stats['users_by_role'] = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role ORDER BY count DESC")->fetchAll(PDO::FETCH_KEY_PAIR);
    $stats['new_today'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $stats['new_week'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    $stats['new_month'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
    $stats['total_products'] = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $stats['total_orders'] = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['total_animals'] = (int) $pdo->query("SELECT COUNT(*) FROM animals")->fetchColumn();
    $stats['revenue'] = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type='income' AND MONTH(transaction_date)=MONTH(CURDATE())")->fetchColumn();
    $recentUsers = $pdo->query("SELECT id, username, email, full_name, role, is_active, created_at FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $activityLog = $pdo->query("SELECT al.*, u.username FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $error = $e->getMessage(); }
?>

<style>
/* ═══════ SUPER ADMIN PREMIUM DESIGN SYSTEM ═══════ */
:root {
    --sa-green: #16A34A;
    --sa-green-dark: #15803D;
    --sa-emerald: #059669;
    --sa-lime: #84CC16;
    --sa-amber: #F59E0B;
    --sa-red: #EF4444;
    --sa-blue: #3B82F6;
    --sa-purple: #8B5CF6;
    --sa-pink: #EC4899;
    --sa-ink: #0F172A;
    --sa-slate: #334155;
    --sa-muted: #94A3B8;
    --sa-border: #E2E8F0;
    --sa-bg: #F8FAFC;
    --sa-card: rgba(255,255,255,0.85);
    --sa-card-solid: #FFFFFF;
    --sa-glass: rgba(255,255,255,0.6);
    --sa-radius: 20px;
    --sa-radius-sm: 14px;
    --sa-radius-xs: 10px;
    --sa-shadow: 0 1px 3px rgba(15,23,42,0.04), 0 4px 16px rgba(15,23,42,0.04);
    --sa-shadow-lg: 0 4px 24px rgba(15,23,42,0.08), 0 1px 4px rgba(15,23,42,0.04);
    --sa-shadow-glow: 0 8px 32px rgba(22,163,74,0.15);
}

/* Hero header */
.sa-hero {
    background: linear-gradient(135deg, #052E16 0%, #14532D 40%, #166534 70%, #15803D 100%);
    border-radius: var(--sa-radius);
    padding: 36px 40px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    color: #fff;
}
.sa-hero::before {
    content: '';
    position: absolute;
    top: -80px; right: -80px;
    width: 300px; height: 300px;
    background: radial-gradient(circle, rgba(132,204,22,0.2) 0%, transparent 70%);
    border-radius: 50%;
}
.sa-hero::after {
    content: '';
    position: absolute;
    bottom: -60px; left: 40%;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(5,150,105,0.25) 0%, transparent 70%);
    border-radius: 50%;
}
.sa-hero-inner { position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; }
.sa-hero h1 { margin: 0; font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px; }
.sa-hero p { margin: 6px 0 0; color: rgba(255,255,255,0.75); font-size: 0.95rem; }
.sa-hero-actions { display: flex; gap: 10px; }
.sa-hero-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 22px; border-radius: 999px; font-weight: 600; font-size: 0.9rem;
    border: none; cursor: pointer; transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
    font-family: inherit; text-decoration: none;
}
.sa-hero-btn-primary {
    background: rgba(255,255,255,0.15); color: #fff; backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.2);
}
.sa-hero-btn-primary:hover { background: rgba(255,255,255,0.25); transform: translateY(-1px); }
.sa-hero-btn-solid { background: #fff; color: #15803D; }
.sa-hero-btn-solid:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,0,0,0.2); }

/* Stat cards */
.sa-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 28px; }
.sa-card {
    background: var(--sa-card-solid);
    border: 1px solid var(--sa-border);
    border-radius: var(--sa-radius-sm);
    padding: 24px;
    box-shadow: var(--sa-shadow);
    transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    position: relative;
    overflow: hidden;
}
.sa-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--sa-shadow-lg);
    border-color: transparent;
}
.sa-card-glow {
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    border-radius: var(--sa-radius-sm) var(--sa-radius-sm) 0 0;
}
.sa-stat-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
.sa-stat-icon {
    width: 52px; height: 52px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    transition: transform 0.3s ease;
}
.sa-card:hover .sa-stat-icon { transform: scale(1.1) rotate(5deg); }
.sa-stat-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 700;
}
.sa-stat-value {
    font-family: 'Outfit', serif; font-size: 2.2rem; font-weight: 700;
    color: var(--sa-ink); letter-spacing: -1px; line-height: 1;
}
.sa-stat-label { margin-top: 6px; font-size: 0.82rem; color: var(--sa-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; }

/* Role chips */
.sa-role-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 28px; }
.sa-role-chip {
    display: flex; align-items: center; gap: 10px;
    padding: 14px 20px; border-radius: var(--sa-radius-sm);
    background: var(--sa-card-solid); border: 1px solid var(--sa-border);
    box-shadow: var(--sa-shadow); flex: 1; min-width: 160px;
    transition: all 0.25s ease;
}
.sa-role-chip:hover { transform: translateY(-2px); box-shadow: var(--sa-shadow-lg); }
.sa-role-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.sa-role-chip h4 { margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.4rem; font-weight: 700; color: var(--sa-ink); }
.sa-role-chip span { font-size: 0.75rem; color: var(--sa-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

/* Tabs */
.sa-tabs {
    display: flex; gap: 6px; background: var(--sa-card-solid); border: 1px solid var(--sa-border);
    padding: 6px; border-radius: 14px; margin-bottom: 24px; box-shadow: var(--sa-shadow);
}
.sa-tab {
    display: flex; align-items: center; gap: 8px; padding: 12px 20px;
    border-radius: 10px; font-weight: 600; font-size: 0.88rem;
    transition: all 0.2s ease; cursor: pointer; border: none; background: none;
    color: var(--sa-muted); font-family: inherit; white-space: nowrap;
}
.sa-tab:hover:not(.active) { color: var(--sa-ink); background: #F1F5F9; }
.sa-tab.active {
    background: var(--sa-green); color: #fff;
    box-shadow: 0 4px 12px rgba(22,163,74,0.25);
}
.sa-tab-panel { display: none; }
.sa-tab-panel.active { display: block; animation: saFadeIn 0.3s ease; }
@keyframes saFadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

/* Table */
.sa-table-wrap {
    background: var(--sa-card-solid); border: 1px solid var(--sa-border);
    border-radius: var(--sa-radius-sm); overflow: hidden; box-shadow: var(--sa-shadow);
}
.sa-table-toolbar {
    display: flex; justify-content: space-between; align-items: center;
    padding: 20px 24px; border-bottom: 1px solid var(--sa-border); flex-wrap: wrap; gap: 12px;
}
.sa-search {
    display: flex; align-items: center; gap: 10px; background: #F8FAFC;
    border: 1.5px solid var(--sa-border); border-radius: 12px; padding: 0 14px;
    transition: all 0.2s ease; width: 300px;
}
.sa-search:focus-within { border-color: var(--sa-green); box-shadow: 0 0 0 3px rgba(22,163,74,0.1); background: #fff; }
.sa-search input {
    border: none; outline: none; background: transparent; padding: 11px 0;
    font-size: 0.9rem; font-family: inherit; color: var(--sa-ink); width: 100%;
}
.sa-search input::placeholder { color: var(--sa-muted); }
.sa-select {
    padding: 10px 14px; border: 1.5px solid var(--sa-border); border-radius: 12px;
    font-size: 0.88rem; font-family: inherit; color: var(--sa-slate);
    background: #F8FAFC; cursor: pointer; transition: all 0.2s ease;
}
.sa-select:focus { border-color: var(--sa-green); outline: none; }
.sa-tbl { width: 100%; border-collapse: collapse; }
.sa-tbl th {
    padding: 14px 20px; text-align: left; font-size: 0.72rem; font-weight: 700;
    color: var(--sa-muted); text-transform: uppercase; letter-spacing: 0.08em;
    background: #F8FAFC; border-bottom: 1px solid var(--sa-border);
}
.sa-tbl td {
    padding: 16px 20px; border-bottom: 1px solid #F1F5F9;
    font-size: 0.9rem; color: var(--sa-slate);
}
.sa-tbl tr:last-child td { border-bottom: none; }
.sa-tbl tr:hover td { background: #FAFBFD; }
.sa-user-cell { display: flex; align-items: center; gap: 12px; }
.sa-avatar {
    width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center;
    justify-content: center; font-weight: 700; font-size: 0.9rem; flex-shrink: 0;
}
.sa-role-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 600;
}
.sa-actions { display: flex; gap: 6px; justify-content: flex-end; }
.sa-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 10px; font-size: 0.82rem; font-weight: 600;
    border: 1.5px solid transparent; cursor: pointer; transition: all 0.2s ease;
    font-family: inherit; text-decoration: none;
}
.sa-btn-edit { background: #F0FDF4; border-color: #BBF7D0; color: #15803D; }
.sa-btn-edit:hover { background: #DCFCE7; }
.sa-btn-danger { background: #FEF2F2; border-color: #FECACA; color: #DC2626; }
.sa-btn-danger:hover { background: #FEE2E2; }
.sa-btn-primary { background: var(--sa-green); color: #fff; box-shadow: 0 4px 12px rgba(22,163,74,0.2); }
.sa-btn-primary:hover { background: var(--sa-green-dark); transform: translateY(-1px); box-shadow: 0 6px 16px rgba(22,163,74,0.3); }

/* Toggle */
.sa-toggle { position: relative; width: 44px; height: 24px; cursor: pointer; display: inline-block; }
.sa-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
.sa-toggle .track {
    position: absolute; inset: 0; background: #CBD5E1; border-radius: 24px; transition: 0.3s;
}
.sa-toggle .thumb {
    position: absolute; width: 18px; height: 18px; border-radius: 50%; background: #fff;
    left: 3px; top: 3px; transition: 0.3s; box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}
.sa-toggle input:checked + .track { background: var(--sa-green); }
.sa-toggle input:checked + .track + .thumb { transform: translateX(20px); }

/* Health cards */
.sa-health-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; }
.sa-health-card {
    background: var(--sa-card-solid); border: 1px solid var(--sa-border);
    border-radius: var(--sa-radius-sm); padding: 28px; box-shadow: var(--sa-shadow);
    position: relative; overflow: hidden; transition: all 0.3s ease;
}
.sa-health-card:hover { transform: translateY(-3px); box-shadow: var(--sa-shadow-lg); }
.sa-health-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.sa-health-title { display: flex; align-items: center; gap: 12px; }
.sa-health-icon {
    width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center;
    justify-content: center;
}
.sa-health-title h3 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--sa-ink); }
.sa-health-status {
    display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px;
    border-radius: 999px; font-size: 0.78rem; font-weight: 700;
}
.sa-health-status.healthy { background: #DCFCE7; color: #15803D; }
.sa-health-status.warning { background: #FEF3C7; color: #D97706; }
.sa-health-status.error { background: #FEE2E2; color: #DC2626; }
.sa-health-dot { width: 8px; height: 8px; border-radius: 50%; }
.sa-health-detail { font-size: 0.88rem; color: var(--sa-muted); line-height: 1.7; }
.sa-health-detail strong { color: var(--sa-slate); }

/* Activity timeline */
.sa-timeline-item {
    display: flex; gap: 16px; padding: 16px 0;
    border-bottom: 1px solid #F1F5F9; transition: background 0.15s;
}
.sa-timeline-item:last-child { border-bottom: none; }
.sa-timeline-dot {
    width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center;
    justify-content: center; flex-shrink: 0; font-size: 0.8rem;
}
.sa-timeline-content { flex: 1; }
.sa-timeline-content h4 { margin: 0 0 4px; font-size: 0.9rem; font-weight: 600; color: var(--sa-ink); }
.sa-timeline-content p { margin: 0; font-size: 0.82rem; color: var(--sa-muted); }

/* Modal */
.sa-modal-backdrop {
    display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6);
    backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center;
    animation: saFadeIn 0.2s ease;
}
.sa-modal { background: #fff; border-radius: var(--sa-radius); width: 100%; max-width: 580px; box-shadow: 0 24px 60px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }
.sa-modal-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 24px 28px 20px; border-bottom: 1px solid var(--sa-border);
}
.sa-modal-header h3 { margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 700; }
.sa-modal-close {
    width: 36px; height: 36px; border-radius: 10px; border: none; background: #F1F5F9;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all 0.2s; color: var(--sa-muted);
}
.sa-modal-close:hover { background: #E2E8F0; color: var(--sa-ink); }
.sa-modal-body { padding: 24px 28px; }
.sa-modal-footer { padding: 16px 28px 24px; display: flex; gap: 10px; justify-content: flex-end; border-top: 1px solid var(--sa-border); }
.sa-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.sa-form-group { margin-bottom: 18px; }
.sa-form-label { display: block; font-weight: 600; font-size: 0.84rem; color: var(--sa-slate); margin-bottom: 6px; }
.sa-form-input {
    width: 100%; padding: 11px 14px; border: 1.5px solid var(--sa-border); border-radius: 12px;
    font-size: 0.9rem; font-family: inherit; color: var(--sa-ink); background: #F8FAFC;
    transition: all 0.2s; outline: none;
}
.sa-form-input:focus { border-color: var(--sa-green); background: #fff; box-shadow: 0 0 0 3px rgba(22,163,74,0.1); }
.sa-form-input::placeholder { color: #CBD5E1; }

/* Responsive */
@media (max-width: 768px) {
    .sa-hero { padding: 24px 20px; }
    .sa-hero h1 { font-size: 1.5rem; }
    .sa-stats { grid-template-columns: 1fr 1fr; }
    .sa-stat-value { font-size: 1.6rem; }
    .sa-form-row { grid-template-columns: 1fr; }
    .sa-search { width: 100%; }
    .sa-tbl { min-width: 700px; }
    .sa-table-wrap { overflow-x: auto; }
    .sa-health-grid { grid-template-columns: 1fr; }
    .sa-role-bar { flex-direction: column; }
}
</style>

<!-- ═══════════════ HERO HEADER ═══════════════ -->
<div class="sa-hero">
    <div class="sa-hero-inner">
        <div>
            <h1>
                <i data-lucide="shield-check" style="width:28px;height:28px;vertical-align:middle;margin-right:8px;opacity:0.8;"></i>
                Platform Control Center
            </h1>
            <p>Manage users, monitor system health, and control your platform from one place.</p>
        </div>
        <div class="sa-hero-actions">
            <button class="sa-hero-btn sa-hero-btn-primary" onclick="openUserModal()">
                <i data-lucide="user-plus" style="width:16px;height:16px;"></i> New User
            </button>
            <button class="sa-hero-btn sa-hero-btn-solid" onclick="location.reload()">
                <i data-lucide="refresh-cw" style="width:16px;height:16px;"></i> Refresh
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════ STAT CARDS ═══════════════ -->
<div class="sa-stats">
    <div class="sa-card">
        <div class="sa-card-glow" style="background:linear-gradient(90deg,#16A34A,#22C55E);"></div>
        <div class="sa-stat-top">
            <div class="sa-stat-icon" style="background:linear-gradient(135deg,#DCFCE7,#BBF7D0);color:#16A34A;">
                <i data-lucide="users" style="width:24px;height:24px;"></i>
            </div>
            <span class="sa-stat-badge" style="background:#DCFCE7;color:#15803D;">
                <i data-lucide="trending-up" style="width:12px;height:12px;"></i> <?= $stats['new_today'] ?> new
            </span>
        </div>
        <div class="sa-stat-value"><?= number_format($stats['total_users']) ?></div>
        <div class="sa-stat-label">Total Users</div>
    </div>
    <div class="sa-card">
        <div class="sa-card-glow" style="background:linear-gradient(90deg,#059669,#10B981);"></div>
        <div class="sa-stat-top">
            <div class="sa-stat-icon" style="background:linear-gradient(135deg,#D1FAE5,#A7F3D0);color:#059669;">
                <i data-lucide="user-check" style="width:24px;height:24px;"></i>
            </div>
            <span class="sa-stat-badge" style="background:#D1FAE5;color:#059669;"><?= $stats['active_users'] ?></span>
        </div>
        <div class="sa-stat-value"><?= $stats['active_users'] ?></div>
        <div class="sa-stat-label">Active Accounts</div>
    </div>
    <div class="sa-card">
        <div class="sa-card-glow" style="background:linear-gradient(90deg,#F59E0B,#FBBF24);"></div>
        <div class="sa-stat-top">
            <div class="sa-stat-icon" style="background:linear-gradient(135deg,#FEF3C7,#FDE68A);color:#D97706;">
                <i data-lucide="banknote" style="width:24px;height:24px;"></i>
            </div>
            <span class="sa-stat-badge" style="background:#FEF3C7;color:#D97706;">
                <i data-lucide="calendar" style="width:12px;height:12px;"></i> this month
            </span>
        </div>
        <div class="sa-stat-value">KES <?= number_format($stats['revenue']) ?></div>
        <div class="sa-stat-label">Revenue</div>
    </div>
    <div class="sa-card">
        <div class="sa-card-glow" style="background:linear-gradient(90deg,#3B82F6,#60A5FA);"></div>
        <div class="sa-stat-top">
            <div class="sa-stat-icon" style="background:linear-gradient(135deg,#DBEAFE,#BFDBFE);color:#2563EB;">
                <i data-lucide="heart" style="width:24px;height:24px;"></i>
            </div>
        </div>
        <div class="sa-stat-value"><?= number_format($stats['total_animals']) ?></div>
        <div class="sa-stat-label">Animals Tracked</div>
    </div>
    <div class="sa-card">
        <div class="sa-card-glow" style="background:linear-gradient(90deg,#8B5CF6,#A78BFA);"></div>
        <div class="sa-stat-top">
            <div class="sa-stat-icon" style="background:linear-gradient(135deg,#EDE9FE,#DDD6FE);color:#7C3AED;">
                <i data-lucide="shopping-bag" style="width:24px;height:24px;"></i>
            </div>
        </div>
        <div class="sa-stat-value"><?= number_format($stats['total_products']) ?></div>
        <div class="sa-stat-label">Products</div>
    </div>
    <div class="sa-card">
        <div class="sa-card-glow" style="background:linear-gradient(90deg,#EC4899,#F472B6);"></div>
        <div class="sa-stat-top">
            <div class="sa-stat-icon" style="background:linear-gradient(135deg,#FCE7F3,#FBCFE8);color:#DB2777;">
                <i data-lucide="package" style="width:24px;height:24px;"></i>
            </div>
        </div>
        <div class="sa-stat-value"><?= number_format($stats['total_orders']) ?></div>
        <div class="sa-stat-label">Orders</div>
    </div>
</div>

<!-- ═══════════════ ROLE BREAKDOWN ═══════════════ -->
<div class="sa-role-bar">
    <?php
    $roleMeta = [
        'super_admin' => ['color' => '#DC2626', 'bg' => '#FEE2E2', 'icon' => 'shield'],
        'farm_manager' => ['color' => '#16A34A', 'bg' => '#DCFCE7', 'icon' => 'sprout'],
        'stock_manager' => ['color' => '#3B82F6', 'bg' => '#DBEAFE', 'icon' => 'package'],
        'sales_staff' => ['color' => '#F59E0B', 'bg' => '#FEF3C7', 'icon' => 'trending-up'],
        'customer' => ['color' => '#6B7280', 'bg' => '#F3F4F6', 'icon' => 'user'],
    ];
    foreach ($stats['users_by_role'] as $role => $count):
        $m = $roleMeta[$role] ?? ['color' => '#6B7280', 'bg' => '#F3F4F6', 'icon' => 'user'];
    ?>
    <div class="sa-role-chip">
        <div class="sa-role-dot" style="background:<?= $m['color'] ?>;"></div>
        <div>
            <h4><?= $count ?></h4>
            <span><?= ucwords(str_replace('_', ' ', $role)) ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ═══════════════ TABS ═══════════════ -->
<div class="sa-tabs">
    <button class="sa-tab active" onclick="switchTab('users', this)">
        <i data-lucide="users" style="width:16px;height:16px;"></i> User Management
    </button>
    <button class="sa-tab" onclick="switchTab('health', this)">
        <i data-lucide="activity" style="width:16px;height:16px;"></i> System Health
    </button>
    <button class="sa-tab" onclick="switchTab('activity', this)">
        <i data-lucide="scroll" style="width:16px;height:16px;"></i> Activity Log
    </button>
</div>

<!-- ═══════════════ USERS PANEL ═══════════════ -->
<div id="panel-users" class="sa-tab-panel active">
    <div class="sa-table-wrap">
        <div class="sa-table-toolbar">
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <div class="sa-search">
                    <i data-lucide="search" style="width:16px;height:16px;color:var(--sa-muted);flex-shrink:0;"></i>
                    <input type="text" id="sa-search" placeholder="Search users..." oninput="searchUsers()">
                </div>
                <select class="sa-select" id="sa-role-filter" onchange="searchUsers()">
                    <option value="">All Roles</option>
                    <option value="super_admin">Super Admin</option>
                    <option value="farm_manager">Farm Manager</option>
                    <option value="stock_manager">Stock Manager</option>
                    <option value="sales_staff">Sales Staff</option>
                    <option value="customer">Customer</option>
                </select>
            </div>
            <span id="sa-count" style="font-size:0.85rem;color:var(--sa-muted);font-weight:600;"></span>
        </div>
        <div style="overflow-x:auto;">
            <table class="sa-tbl">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="sa-users-body">
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--sa-muted);">Loading users...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ═══════════════ HEALTH PANEL ═══════════════ -->
<div id="panel-health" class="sa-tab-panel">
    <div class="sa-health-grid">
        <div class="sa-health-card" id="h-db">
            <div class="sa-health-header">
                <div class="sa-health-title">
                    <div class="sa-health-icon" style="background:#DBEAFE;color:#2563EB;"><i data-lucide="database" style="width:20px;height:20px;"></i></div>
                    <h3>Database</h3>
                </div>
                <span class="sa-health-status" style="background:#F1F5F9;color:var(--sa-muted);"><span class="sa-health-dot" style="background:#CBD5E1;"></span> Loading...</span>
            </div>
            <div class="sa-health-detail">Checking...</div>
        </div>
        <div class="sa-health-card" id="h-redis">
            <div class="sa-health-header">
                <div class="sa-health-title">
                    <div class="sa-health-icon" style="background:#FEE2E2;color:#DC2626;"><i data-lucide="zap" style="width:20px;height:20px;"></i></div>
                    <h3>Redis Cache</h3>
                </div>
                <span class="sa-health-status" style="background:#F1F5F9;color:var(--sa-muted);"><span class="sa-health-dot" style="background:#CBD5E1;"></span> Loading...</span>
            </div>
            <div class="sa-health-detail">Checking...</div>
        </div>
        <div class="sa-health-card" id="h-opcache">
            <div class="sa-health-header">
                <div class="sa-health-title">
                    <div class="sa-health-icon" style="background:#FEF3C7;color:#D97706;"><i data-lucide="cpu" style="width:20px;height:20px;"></i></div>
                    <h3>OPcache</h3>
                </div>
                <span class="sa-health-status" style="background:#F1F5F9;color:var(--sa-muted);"><span class="sa-health-dot" style="background:#CBD5E1;"></span> Loading...</span>
            </div>
            <div class="sa-health-detail">Checking...</div>
        </div>
        <div class="sa-health-card" id="h-php">
            <div class="sa-health-header">
                <div class="sa-health-title">
                    <div class="sa-health-icon" style="background:#EDE9FE;color:#7C3AED;"><i data-lucide="code-2" style="width:20px;height:20px;"></i></div>
                    <h3>PHP Runtime</h3>
                </div>
                <span class="sa-health-status" style="background:#F1F5F9;color:var(--sa-muted);"><span class="sa-health-dot" style="background:#CBD5E1;"></span> Loading...</span>
            </div>
            <div class="sa-health-detail">Checking...</div>
        </div>
    </div>
</div>

<!-- ═══════════════ ACTIVITY PANEL ═══════════════ -->
<div id="panel-activity" class="sa-tab-panel">
    <div class="sa-table-wrap" style="padding:24px;">
        <h3 style="margin:0 0 20px;font-family:'Outfit',sans-serif;font-size:1.1rem;font-weight:700;color:var(--sa-ink);">
            <i data-lucide="history" style="width:18px;height:18px;vertical-align:middle;margin-right:6px;"></i>
            Recent Activity
        </h3>
        <?php if (empty($activityLog)): ?>
            <div style="text-align:center;padding:40px;color:var(--sa-muted);">No activity recorded yet.</div>
        <?php else: foreach ($activityLog as $log): ?>
            <?php
            $actionColors = [
                'create' => ['bg' => '#DCFCE7', 'text' => '#15803D', 'icon' => 'plus-circle'],
                'update' => ['bg' => '#DBEAFE', 'text' => '#2563EB', 'icon' => 'pencil'],
                'delete' => ['bg' => '#FEE2E2', 'text' => '#DC2626', 'icon' => 'trash-2'],
                'login'  => ['bg' => '#EDE9FE', 'text' => '#7C3AED', 'icon' => 'log-in'],
            ];
            $ac = $actionColors[$log['action'] ?? ''] ?? ['bg' => '#F1F5F9', 'text' => '#64748B', 'icon' => 'activity'];
            ?>
            <div class="sa-timeline-item">
                <div class="sa-timeline-dot" style="background:<?= $ac['bg'] ?>;color:<?= $ac['text'] ?>;">
                    <i data-lucide="<?= $ac['icon'] ?>" style="width:16px;height:16px;"></i>
                </div>
                <div class="sa-timeline-content">
                    <h4><?= htmlspecialchars($log['username'] ?? 'System', ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($log['action'] ?? '-', ENT_QUOTES, 'UTF-8') ?></h4>
                    <p><?= htmlspecialchars($log['details'] ?? $log['module'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div style="font-size:0.78rem;color:var(--sa-muted);white-space:nowrap;"><?= htmlspecialchars(substr($log['created_at'] ?? '', 0, 16), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<!-- ═══════════════ USER MODAL ═══════════════ -->
<div id="sa-modal" class="sa-modal-backdrop" onclick="if(event.target===this)closeModal()">
    <div class="sa-modal">
        <div class="sa-modal-header">
            <h3 id="sa-modal-title">New User</h3>
            <button class="sa-modal-close" onclick="closeModal()"><i data-lucide="x" style="width:18px;height:18px;"></i></button>
        </div>
        <div class="sa-modal-body">
            <form id="sa-form" onsubmit="saveUser(event)">
                <input type="hidden" id="sa-f-id" value="0">
                <div class="sa-form-row">
                    <div class="sa-form-group"><label class="sa-form-label">First Name</label><input class="sa-form-input" id="sa-f-first" required></div>
                    <div class="sa-form-group"><label class="sa-form-label">Last Name</label><input class="sa-form-input" id="sa-f-last"></div>
                </div>
                <div class="sa-form-group"><label class="sa-form-label">Username</label><input class="sa-form-input" id="sa-f-user" required></div>
                <div class="sa-form-group"><label class="sa-form-label">Email</label><input class="sa-form-input" type="email" id="sa-f-email" required></div>
                <div class="sa-form-row">
                    <div class="sa-form-group"><label class="sa-form-label">Phone</label><input class="sa-form-input" id="sa-f-phone"></div>
                    <div class="sa-form-group">
                        <label class="sa-form-label">Role</label>
                        <select class="sa-form-input" id="sa-f-role">
                            <option value="customer">Customer</option>
                            <option value="sales_staff">Sales Staff</option>
                            <option value="stock_manager">Stock Manager</option>
                            <option value="farm_manager">Farm Manager</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                </div>
                <div class="sa-form-group"><label class="sa-form-label">Password</label><input class="sa-form-input" type="password" id="sa-f-pass" placeholder="Leave blank to keep current"></div>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                    <label class="sa-toggle"><input type="checkbox" id="sa-f-active" checked><span class="track"></span><span class="thumb"></span></label>
                    <span style="font-weight:600;font-size:0.9rem;">Account Active</span>
                </div>
            </form>
        </div>
        <div class="sa-modal-footer">
            <button class="sa-btn" style="background:#F1F5F9;color:var(--sa-slate);" onclick="closeModal()">Cancel</button>
            <button class="sa-btn sa-btn-primary" onclick="document.getElementById('sa-form').requestSubmit()">
                <i data-lucide="save" style="width:15px;height:15px;"></i> Save User
            </button>
        </div>
    </div>
</div>

<script>
const API = '/Backend/api/super_admin.php';
const COLORS = { super_admin:'#DC2626', farm_manager:'#16A34A', stock_manager:'#3B82F6', sales_staff:'#F59E0B', customer:'#6B7280' };

function switchTab(id, btn) {
    document.querySelectorAll('.sa-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sa-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('panel-' + id).classList.add('active');
    btn.classList.add('active');
    if (id === 'health') loadHealth();
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

async function loadUsers() {
    const q = document.getElementById('sa-search').value;
    const r = document.getElementById('sa-role-filter').value;
    try {
        const res = await fetch(`${API}?endpoint=users&search=${encodeURIComponent(q)}&role=${r}`);
        const d = await res.json();
        const tb = document.getElementById('sa-users-body');
        if (!res.ok || d.error) {
            throw new Error(d.error || `Users request failed (${res.status})`);
        }
        document.getElementById('sa-count').textContent = `${d.total} user(s)`;
        if (!d.users || !d.users.length) { tb.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--sa-muted);">No users found.</td></tr>'; return; }
        tb.innerHTML = d.users.map(u => {
            const c = COLORS[u.role] || '#6B7280';
            const ini = (u.full_name || u.username || 'U').charAt(0).toUpperCase();
            const name = u.full_name || u.username;
            const on = u.is_active == 1;
            return `<tr>
                <td><div class="sa-user-cell"><div class="sa-avatar" style="background:${c}15;color:${c};">${ini}</div><div><strong style="color:var(--sa-ink);">${h(name)}</strong><br><small style="color:var(--sa-muted);">@${h(u.username)}</small></div></div></td>
                <td>${h(u.email||'-')}</td>
                <td><span class="sa-role-badge" style="background:${c}12;color:${c};"><span class="sa-role-dot" style="background:${c};width:6px;height:6px;"></span>${h(u.role.replace(/_/g,' '))}</span></td>
                <td><label class="sa-toggle"><input type="checkbox" ${on?'checked':''} onchange="toggleUser(${u.id})"><span class="track"></span><span class="thumb"></span></label></td>
                <td style="color:var(--sa-muted);font-size:0.84rem;">${u.created_at?new Date(u.created_at).toLocaleDateString():'-'}</td>
                <td><div class="sa-actions"><button class="sa-btn sa-btn-edit" onclick='editUser(${JSON.stringify(u)})'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button>${u.role!=='super_admin'?`<button class="sa-btn sa-btn-danger" onclick="delUser(${u.id},'${h(u.username)}')"><i data-lucide="trash-2" style="width:13px;height:13px;"></i></button>`:''}</div></td>
            </tr>`;
        }).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch(e) {
        console.error('Unable to load users', e);
        const tb = document.getElementById('sa-users-body');
        if (tb) tb.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:40px;color:#B91C1C;">Unable to load users: ${h(e.message || 'Unknown error')}</td></tr>`;
    }
}
function searchUsers() { loadUsers(); }

async function toggleUser(id) {
    await fetch(`${API}?endpoint=toggle_user`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id}) });
}
async function delUser(id, name) {
    if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
    const r = await fetch(`${API}?endpoint=delete_user`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id}) });
    const d = await r.json();
    if (d.success) loadUsers(); else alert(d.error||'Failed');
}

function openUserModal() {
    document.getElementById('sa-modal-title').textContent = 'New User';
    document.getElementById('sa-f-id').value = '0';
    ['sa-f-first','sa-f-last','sa-f-user','sa-f-email','sa-f-phone','sa-f-pass'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('sa-f-role').value = 'customer';
    document.getElementById('sa-f-active').checked = true;
    document.getElementById('sa-modal').style.display = 'flex';
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
function editUser(u) {
    document.getElementById('sa-modal-title').textContent = 'Edit User';
    document.getElementById('sa-f-id').value = u.id;
    const p = (u.full_name||'').split(' ');
    document.getElementById('sa-f-first').value = p[0]||'';
    document.getElementById('sa-f-last').value = p.slice(1).join(' ')||'';
    document.getElementById('sa-f-user').value = u.username||'';
    document.getElementById('sa-f-email').value = u.email||'';
    document.getElementById('sa-f-phone').value = u.phone||'';
    document.getElementById('sa-f-role').value = u.role||'customer';
    document.getElementById('sa-f-pass').value = '';
    document.getElementById('sa-f-active').checked = u.is_active==1;
    document.getElementById('sa-modal').style.display = 'flex';
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
function closeModal() { document.getElementById('sa-modal').style.display = 'none'; }

async function saveUser(e) {
    e.preventDefault();
    const data = {
        id: +document.getElementById('sa-f-id').value,
        first_name: document.getElementById('sa-f-first').value,
        last_name: document.getElementById('sa-f-last').value,
        username: document.getElementById('sa-f-user').value,
        email: document.getElementById('sa-f-email').value,
        phone: document.getElementById('sa-f-phone').value,
        role: document.getElementById('sa-f-role').value,
        password: document.getElementById('sa-f-pass').value,
        is_active: document.getElementById('sa-f-active').checked ? 1 : 0
    };
    const r = await fetch(`${API}?endpoint=save_user`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) });
    const d = await r.json();
    if (d.success) { closeModal(); loadUsers(); } else alert(d.error||'Failed');
}

async function loadHealth() {
    const r = await fetch(`${API}?endpoint=health`);
    const d = await r.json();
    const svc = (id, icon, label, data, color) => {
        const s = data?.status || 'unknown';
        const sc = s === 'healthy' ? 'healthy' : s === 'error' ? 'error' : 'warning';
        const dot = s === 'healthy' ? '#22C55E' : s === 'error' ? '#EF4444' : '#F59E0B';
        let detail = '';
        if (data?.tables) detail += `<strong>${data.tables}</strong> tables`;
        if (data?.memory) detail += `${detail?'<br>':''}Memory: <strong>${data.memory}</strong>`;
        if (data?.hit_rate) detail += `${detail?'<br>':''}Hit rate: <strong>${data.hit_rate}%</strong>`;
        if (data?.version) detail += `Version: <strong>${data.version}</strong><br>Memory limit: <strong>${data.memory_limit||'?'}</strong>`;
        document.getElementById(id).innerHTML = `
            <div class="sa-health-header">
                <div class="sa-health-title">
                    <div class="sa-health-icon" style="background:${color};"><i data-lucide="${icon}" style="width:20px;height:20px;"></i></div>
                    <h3>${label}</h3>
                </div>
                <span class="sa-health-status ${sc}"><span class="sa-health-dot" style="background:${dot};"></span> ${s}</span>
            </div>
            <div class="sa-health-detail">${detail||'OK'}</div>`;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    };
    svc('h-db','database','Database',d.database,'#DBEAFE');
    svc('h-redis','zap','Redis Cache',d.redis,'#FEF3C7');
    svc('h-opcache','cpu','OPcache',d.opcache,'#FEF3C7');
    svc('h-php','code-2','PHP Runtime',d.php,'#EDE9FE');
}

function h(s) { const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
document.addEventListener('DOMContentLoaded', () => { loadUsers(); if(typeof lucide!=='undefined')lucide.createIcons(); });
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
