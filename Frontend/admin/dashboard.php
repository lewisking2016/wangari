<?php
/**
 * Admin Dashboard with Analytics
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

// Admin access check - allow all authenticated users
if (empty($_SESSION['user_id']) || !wangariIsFarmSystemRole((string)($_SESSION['role'] ?? ''))) {
    header('Location: /Frontend/pages/login.php');
    exit;
}

$path_prefix = '../../';
$page_title = 'Admin Dashboard';
include __DIR__ . '/includes/admin_header.php';

$deniedModule = isset($_GET['denied']) ? 'that module' : '';

// ── Weather data (open-meteo.com, free, no API key) ──
$weatherData = null;
try {
    $wPdo = getDB();
    if ($wPdo) {
        // Check cache first (refresh daily)
        $cached = $wPdo->query("SELECT * FROM weather_cache WHERE cache_date = CURDATE() AND location = 'default' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($cached && $cached['forecast_json']) {
            $weatherData = json_decode($cached['forecast_json'], true);
        } else {
            // Read farm location from settings (editable in Connectors → Weather tab)
            $wLat = '0.46'; $wLon = '34.56';
            try {
                $wSet = $wPdo->query("SELECT setting_value FROM farm_settings WHERE setting_key='weather_lat' LIMIT 1")->fetchColumn();
                if ($wSet) $wLat = $wSet;
                $wSet2 = $wPdo->query("SELECT setting_value FROM farm_settings WHERE setting_key='weather_lon' LIMIT 1")->fetchColumn();
                if ($wSet2) $wLon = $wSet2;
            } catch (Exception $e) {}
            $url = "https://api.open-meteo.com/v1/forecast?latitude=" . urlencode($wLat) . '&longitude=' . urlencode($wLon) . '&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,weathercode&current_weather=true&timezone=Africa/Nairobi&forecast_days=5';
            $ctx = stream_context_create(['http' => ['timeout' => 8]]);
            $json = @file_get_contents($url, false, $ctx);
            if ($json !== false) {
                $weatherData = json_decode($json, true);
                if ($weatherData && isset($weatherData['daily'])) {
                    $wPdo->prepare('INSERT INTO weather_cache (cache_date, location, temperature_max, temperature_min, rainfall_mm, weather_code, forecast_json) VALUES (CURDATE(),?,?,?,?,?,?) ON DUPLICATE KEY UPDATE forecast_json=VALUES(forecast_json), temperature_max=VALUES(temperature_max), temperature_min=VALUES(temperature_min)')
                        ->execute(['default', $weatherData['daily']['temperature_2m_max'][0] ?? null, $weatherData['daily']['temperature_2m_min'][0] ?? null, $weatherData['daily']['precipitation_sum'][0] ?? 0, $weatherData['current_weather']['weathercode'] ?? '', $json]);
                }
            }
        }
    }
} catch (Exception $e) { /* weather is nice-to-have */ }

// ── Financial Summary (this month) ──
$financeSummary = ['income' => 0, 'expenses' => 0, 'profit' => 0, 'pending_credit' => 0];
$livestockCount = 0;
$activeCropsCount = 0;
$activeWorkersCount = 0;

try {
    if (!isset($wPdo)) $wPdo = getDB();
    if ($wPdo) {
        $month = date('Y-m');
        $inc = $wPdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type='income' AND DATE_FORMAT(transaction_date,'%Y-%m')='$month'")->fetchColumn();
        $exp = $wPdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type='expense' AND DATE_FORMAT(transaction_date,'%Y-%m')='$month'")->fetchColumn();
        $crd = $wPdo->query("SELECT COALESCE(SUM(balance_owed),0) FROM customer_credits WHERE status='pending'")->fetchColumn();
        $financeSummary = ['income' => (float)$inc, 'expenses' => (float)$exp, 'profit' => (float)$inc - (float)$exp, 'pending_credit' => (float)$crd];
        
        // Extra counts
        try {
            $livestockCount = (int)$wPdo->query("SELECT COUNT(*) FROM animals WHERE status IN ('Active','alive','active')")->fetchColumn();
            $groupHeadCount = (int)$wPdo->query("SELECT COALESCE(SUM(head_count),0) FROM animal_groups WHERE status='active'")->fetchColumn();
            $livestockCount += $groupHeadCount;
        } catch (Exception $ex) {}

        try {
            $activeCropsCount = (int)$wPdo->query("SELECT COUNT(*) FROM crop_plantings WHERE status IN ('active','planted')")->fetchColumn();
        } catch (Exception $ex) {}

        try {
            $activeWorkersCount = (int)$wPdo->query("SELECT COUNT(*) FROM workers WHERE status='active'")->fetchColumn();
        } catch (Exception $ex) {}
    }
} catch (Exception $e) { /* finance is nice-to-have */ }
?>

<?php if (isset($_GET['denied'])): ?>
<div style="padding:13px 18px;background:#fef3c7;border:1px solid #fde68a;border-radius:8px;color:#92400e;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <i data-lucide="shield-alert" style="width:18px;height:18px;flex-shrink:0;"></i>
    <span>Your role doesn't have permission to open that module. Ask the Super Admin to grant access under <strong>Settings → Roles &amp; Permissions</strong>.</span>
</div>
<?php endif; ?>

<div class="admin-dashboard-wrapper" style="margin: 0; padding: 0;">
    <style>
        .dashboard-hero-card {
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-primary-light) 100%);
            color: #ffffff !important;
            border-radius: 4px;
            padding: 32px;
            margin-bottom: 24px;
            border: none;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(27, 94, 32, 0.15);
        }

        .dashboard-hero-card::after {
            content: '';
            position: absolute;
            top: -20%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,193,7,0.15) 0%, transparent 80%);
            border-radius: 50%;
        }

        .dashboard-hero-card h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            margin: 0 0 12px 0;
            font-weight: 700;
            color: #ffffff !important;
        }

        .dashboard-hero-card p {
            margin: 0 0 24px 0;
            color: rgba(255, 255, 255, 0.9) !important;
            font-size: 1rem;
            line-height: 1.6;
            max-width: 600px;
        }

        .dashboard-hero-card .btn {
            border-radius: 4px;
            padding: 10px 20px;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .dashboard-hero-card .btn-white {
            background: #ffffff;
            color: var(--admin-primary);
        }

        .dashboard-hero-card .btn-white:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .dashboard-hero-card .btn-trans {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border: 1px solid rgba(255,255,255,0.25);
        }

        .dashboard-hero-card .btn-trans:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        /* Grid layouts */
        .dashboard-kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        .dashboard-main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        @media (max-width: 1024px) {
            .dashboard-kpi-row {
                grid-template-columns: repeat(2, 1fr);
            }
            .dashboard-main-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .dashboard-kpi-row {
                grid-template-columns: 1fr;
            }
        }

        .mini-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            border-bottom: 1px solid var(--admin-border);
            transition: background 0.2s ease;
        }

        .mini-list-item:last-child {
            border-bottom: none;
        }

        .mini-list-item:hover {
            background: rgba(248, 250, 252, 0.8);
        }

        .mini-list-item .item-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .mini-list-item .item-details strong {
            color: var(--admin-text-heading);
            font-size: 0.95rem;
        }

        .mini-list-item .item-details span {
            color: #64748b;
            font-size: 0.8rem;
        }

        .chart-box {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>

    <style>
        /* ── Dashboard V2 ── */
        .d2-hero {
            background: linear-gradient(120deg, #0B1220 0%, #0E2A1D 55%, #14532D 100%);
            border-radius: 18px;
            padding: 30px 32px;
            color: #fff;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 14px 40px rgba(11, 18, 32, 0.25);
        }
        .d2-hero::after {
            content: '';
            position: absolute;
            right: -80px;
            top: -80px;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(34,197,94,0.18) 0%, transparent 65%);
        }
        .d2-hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #22C55E;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            margin-bottom: 10px;
        }
        .d2-hero h1 {
            margin: 0 0 8px;
            font-size: 1.7rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.4px;
        }
        .d2-hero h1 .w2-serif { color: #22C55E; font-family: 'Instrument Serif', serif; font-weight: 400; font-style: italic; }
        .d2-hero p { margin: 0 0 20px; color: rgba(255,255,255,0.72); font-size: 0.95rem; max-width: 560px; }
        .d2-hero-actions { display: flex; gap: 10px; flex-wrap: wrap; position: relative; z-index: 2; }
        .d2-hero-actions .btn { border-radius: 999px; }
        .d2-hero-actions .btn-lime { background: #22C55E; color: #0B1220; font-weight: 700; }
        .d2-hero-actions .btn-ghost { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.25); }

        .d2-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .d2-kpi {
            background: #fff;
            border: 1px solid var(--w2-border);
            border-radius: 16px;
            padding: 18px;
            box-shadow: var(--w2-shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            min-width: 0;
            overflow: hidden;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
        }
        .d2-kpi::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--xai-lime), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .d2-kpi:hover { 
            transform: translateY(-6px) scale(1.02); 
            box-shadow: 0 20px 40px rgba(15,23,42,0.12), 0 0 20px rgba(34,197,94,0.1); 
        }
        .d2-kpi:hover::before { opacity: 1; }
        .d2-kpi:hover .d2-kpi-icon { 
            transform: scale(1.15) rotate(5deg); 
        }
        .d2-kpi:hover strong { color: #166534; }
        .d2-kpi small { color: #64748B; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; }
        .d2-kpi strong { display: block; margin-top: 6px; font-size: 1.55rem; font-weight: 800; color: #0F172A; letter-spacing: -0.5px; transition: color 0.3s ease; }
        .d2-kpi-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .d2-kpi-icon.green { background: #E4F7E9; color: #15803D; }
        .d2-kpi-icon.blue  { background: #E0EDFF; color: #1D4ED8; }
        .d2-kpi-icon.amber { background: #FEF5E0; color: #B45309; }
        .d2-kpi-icon.red   { background: #FDE8E8; color: #B91C1C; }

        .d2-ai {
            background: linear-gradient(135deg, #ffffff 0%, #F5FBF2 50%, #E8F7DF 100%) !important;
            border: 1.5px solid rgba(34,197,94,0.3) !important;
            border-radius: 16px;
            padding: 22px 24px;
            margin-bottom: 24px;
            box-shadow: 0 10px 30px rgba(34, 197, 94, 0.06), var(--w2-shadow) !important;
            position: relative;
            overflow: hidden;
        }
        .d2-ai::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(34,197,94,0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .ai-pulse-dot {
            width: 8px;
            height: 8px;
            background: #22C55E;
            border-radius: 50%;
            display: inline-block;
            margin-left: 6px;
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            animation: ai-pulse 2s infinite;
            vertical-align: middle;
        }
        @keyframes ai-pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.5); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
        .d2-ai-input-wrap { display: flex; gap: 10px; }
        .d2-ai-input-wrap input {
            flex: 1;
            padding: 13px 18px;
            border: 1.5px solid #D8DEE8;
            border-radius: 999px;
            font-family: inherit;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: rgba(255,255,255,0.9);
        }
        .d2-ai-input-wrap input:focus { border-color: #1B7A3D; box-shadow: 0 0 0 4px rgba(22,101,52,0.1); }

        .d2-main { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px; align-items: stretch; min-width: 0; }
        .d2-side { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
        .d2-charts-col { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
        .d2-charts-col .admin-card { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .d2-charts-col .chart-box { flex: 1; height: auto; min-height: 200px; }
        @media (max-width: 1024px) {
            .d2-kpis { grid-template-columns: repeat(2, 1fr); }
            .d2-main { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .d2-kpis { grid-template-columns: 1fr; }
            .d2-hero { padding: 24px 20px; }
            .d2-hero h1 { font-size: 1.3rem; }
            .d2-ai-input-wrap { flex-direction: column; }
            .d2-kpi { padding: 14px; }
            .d2-kpi strong { font-size: 1.25rem; }
        }
    </style>

    <!-- V2 Welcome Hero -->
    <div class="d2-hero">
        <div class="d2-hero-eyebrow"><i data-lucide="leaf" style="width:14px;height:14px;"></i> Farm Operations Overview</div>
        <h1>Wangari <span class="w2-serif">Home</span></h1>
        <p>Everything that needs you today — orders, stock health, production and your AI assistant — in one clean workspace.</p>
        <div class="d2-hero-actions">
            <a class="btn btn-lime" href="/Frontend/admin/hub_finance.php?tab=orders"><i data-lucide="shopping-cart" style="width:16px;height:16px;"></i> Review Orders</a>
            <a class="btn btn-ghost" href="/Frontend/admin/hub_inventory.php?tab=products"><i data-lucide="package" style="width:16px;height:16px;"></i> Manage Products</a>
            <a class="btn btn-ghost" href="/Frontend/admin/hub_finance.php?tab=reports"><i data-lucide="bar-chart" style="width:16px;height:16px;"></i> Analytics</a>
        </div>
    </div>

    <?php
    // Get subscription status for banner
    require_once dirname(__DIR__, 2) . '/Backend/config/limits.php';
    $dashSubStatus = 'trial';
    $dashSubPlan = 'Free Trial';
    $dashSubExpires = '';
    $dashMaxAnimals = 5;
    
    $dashRegisteredAt = '';
    if (isset($wPdo) && $wPdo) {
        $stmt = $wPdo->prepare('SELECT subscription_status, subscription_expires, max_animals, created_at FROM platform_users WHERE id = ?');
        $stmt->execute([(int)($_SESSION['user_id'] ?? 0)]);
        $dashSub = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dashSub) {
            $dashSubStatus = $dashSub['subscription_status'] ?? 'trial';
            $dashSubExpires = $dashSub['subscription_expires'] ?? '';
            $dashMaxAnimals = (int)($dashSub['max_animals'] ?? 5);
            $dashRegisteredAt = $dashSub['created_at'] ?? '';
            if ($dashSubStatus === 'active') {
                $dashSubPlan = $dashMaxAnimals >= 200 ? 'Plus' : 'Pro';
            }
        }
        // If no subscription_expires, calculate from registration date + 30 days
        if (!$dashSubExpires && $dashRegisteredAt) {
            $dashSubExpires = date('Y-m-d', strtotime($dashRegisteredAt) . ' +30 days');
        }
    }
    
    // Show banner only for trial or expired users
    if ($dashSubStatus !== 'active'): ?>
    <div style="background: <?php echo $dashSubStatus === 'trial' ? 'linear-gradient(135deg, #166534 0%, #22c55e 100%)' : 'linear-gradient(135deg, #991B1B 0%, #DC2626 100%)'; ?>; border-radius: 14px; padding: 18px 24px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; color: white;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
                <?php
                    // Trial countdown from registration date
                    $trialEndDate = $dashSubExpires ?: date('Y-m-d', strtotime($dashRegisteredAt ?: date('Y-m-d')) . ' +30 days');
                    $daysLeft = max(0, (int)((strtotime($trialEndDate) - time()) / 86400));
                    $hoursLeft = max(0, (int)((strtotime($trialEndDate) - time()) % 86400 / 3600));
                    $registeredDate = $dashRegisteredAt ? date('M j, Y', strtotime($dashRegisteredAt)) : 'today';
                ?>
                <div style="font-weight: 700; font-size: 1rem;">
                    <?php if ($dashSubStatus === 'trial'): ?>
                        You're on a <strong><?php echo $daysLeft; ?>-day free trial</strong>
                    <?php else: ?>
                        Your subscription has expired
                    <?php endif; ?>
                </div>
                <div style="font-size: 0.85rem; opacity: 0.9;">
                    <?php if ($dashSubStatus === 'trial'): ?>
                        <strong><?php echo $daysLeft; ?>d <?php echo $hoursLeft; ?>h remaining</strong> — Trial started <?php echo $registeredDate; ?>. Upgrade to keep all features
                    <?php else: ?>
                        Expired <?php echo date('M j, Y', strtotime($dashSubExpires ?? date('Y-m-d'))); ?> — Subscribe now to continue
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <a href="/Frontend/pages/pricing.php" style="background: white; color: <?php echo $dashSubStatus === 'trial' ? '#166534' : '#991B1B'; ?>; padding: 10px 20px; border-radius: 999px; font-weight: 700; font-size: 0.85rem; text-decoration: none; white-space: nowrap;">
            <?php echo $dashSubStatus === 'trial' ? 'Upgrade Plan' : 'Renew Subscription'; ?> →
        </a>
    </div>
    <?php endif; ?>

    <!-- V2 KPI cards -->
    <div class="d2-kpis">
        <div class="d2-kpi" onclick="window.location.href='/Frontend/admin/hub_finance.php?tab=sales'">
            <div>
                <small>Total Revenue</small>
                <strong id="kpi-sales">KES 0</strong>
            </div>
            <div class="d2-kpi-icon green"><i data-lucide="trending-up" style="width:22px;height:22px;"></i></div>
        </div>
        <div class="d2-kpi" onclick="window.location.href='/Frontend/admin/hub_finance.php?tab=orders'">
            <div>
                <small>Orders Completed</small>
                <strong id="kpi-orders">0</strong>
            </div>
            <div class="d2-kpi-icon blue"><i data-lucide="shopping-bag" style="width:22px;height:22px;"></i></div>
        </div>
        <div class="d2-kpi" onclick="window.location.href='/Frontend/admin/hub_finance.php?tab=orders'">
            <div>
                <small>Avg. Order Value</small>
                <strong id="kpi-avg">KES 0</strong>
            </div>
            <div class="d2-kpi-icon amber"><i data-lucide="pie-chart" style="width:22px;height:22px;"></i></div>
        </div>
        <div class="d2-kpi" onclick="window.location.href='/Frontend/admin/hub_inventory.php?tab=alerts'">
            <div>
                <small>Inventory Alerts</small>
                <strong id="kpi-alerts-summary">0</strong>
            </div>
            <div class="d2-kpi-icon red"><i data-lucide="alert-triangle" style="width:22px;height:22px;"></i></div>
        </div>
        
        <!-- Additional KPI cards -->
        <div class="d2-kpi" onclick="window.location.href='/Frontend/admin/hub_operations.php?tab=animals'">
            <div>
                <small>Active Livestock</small>
                <strong><?= number_format($livestockCount) ?></strong>
            </div>
            <div class="d2-kpi-icon green"><i data-lucide="sprout" style="width:22px;height:22px;"></i></div>
        </div>
        <div class="d2-kpi" onclick="window.location.href='/Frontend/admin/hub_crops.php?tab=plantings'">
            <div>
                <small>Active Crops</small>
                <strong><?= number_format($activeCropsCount) ?></strong>
            </div>
            <div class="d2-kpi-icon blue"><i data-lucide="wheat" style="width:22px;height:22px;"></i></div>
        </div>
        <div class="d2-kpi" onclick="window.location.href='/Frontend/admin/hub_labour.php?tab=workers'">
            <div>
                <small>Active Workers</small>
                <strong><?= number_format($activeWorkersCount) ?></strong>
            </div>
            <div class="d2-kpi-icon amber"><i data-lucide="users" style="width:22px;height:22px;"></i></div>
        </div>
        <div class="d2-kpi" onclick="window.location.href='/Frontend/admin/hub_crm.php?tab=customers'">
            <div>
                <small>Outstanding Credit</small>
                <strong style="color:#b45309;">KES <?= number_format($financeSummary['pending_credit'], 0) ?></strong>
            </div>
            <div class="d2-kpi-icon red"><i data-lucide="credit-card" style="width:22px;height:22px;"></i></div>
        </div>
    </div>

    <!-- V2 AI Assistant (wired to real backend) -->
    <div class="d2-ai">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="position:relative;width:42px;height:42px;border-radius:13px;background:#0B1220;color:#22C55E;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 16px rgba(11,18,32,0.2);">
                    <i data-lucide="sparkles" style="width:20px;height:20px;"></i>
                    <span class="ai-pulse-dot" style="position:absolute;top:2px;right:2px;margin:0;"></span>
                </div>
                <div>
                    <h3 style="margin:0;font-size:1.05rem;color:#0F172A;">Ask Wangari <span style="color:#1B7A3D;font-family:'Instrument Serif',serif;font-style:italic;">AI</span></h3>
                    <p style="margin:2px 0 0;font-size:0.78rem;color:#64748b;">Answers come from your own farm records.</p>
                </div>
            </div>
            <a class="btn btn-outline btn-sm" href="ai_assistant.php">Open Assistant <i data-lucide="arrow-right" style="width:14px;height:14px;"></i></a>
        </div>
        <div style="display:flex;align-items:center;gap:10px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:10px 14px;margin-bottom:12px;font-size:0.83rem;color:#166534;flex-wrap:wrap;">
            <i data-lucide="sun" style="width:16px;height:16px;flex:none;color:#1B7A3D;"></i>
            <strong style="flex:none;">Today:</strong>
            <span><?php echo htmlspecialchars(function_exists('getTodayDigest') ? getTodayDigest($pdo) : 'No activity recorded yet today.', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <form class="d2-ai-input-wrap" method="POST" action="ai_assistant.php" style="margin-bottom:12px;">
            <input name="question" id="ai-question-input" placeholder="Try: 'How much did I sell this month?' or 'Who owes me credit?'" autocomplete="off">
            <button class="btn btn-primary" style="white-space:nowrap;"><i data-lucide="send" style="width:16px;height:16px;"></i> Ask</button>
        </form>
        <div style="display:flex;gap:8px;flex-wrap:wrap;" class="d2-ai-suggestions">
            <span onclick="fillQuestion('How much did I sell this month?')" style="cursor:pointer;padding:6px 12px;background:#fff;border:1px solid #e2e8f0;border-radius:999px;font-size:0.75rem;color:#475569;font-weight:600;transition:all 0.2s;" onmouseover="this.style.borderColor='#22C55E';this.style.color='#166534';this.style.background='#F0FDF4'" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#475569';this.style.background='#fff'">💰 Sales this month</span>
            <span onclick="fillQuestion('How many active animals do we have?')" style="cursor:pointer;padding:6px 12px;background:#fff;border:1px solid #e2e8f0;border-radius:999px;font-size:0.75rem;color:#475569;font-weight:600;transition:all 0.2s;" onmouseover="this.style.borderColor='#22C55E';this.style.color='#166534';this.style.background='#F0FDF4'" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#475569';this.style.background='#fff'">🐣 Active Livestock</span>
            <span onclick="fillQuestion('Who owes me credit?')" style="cursor:pointer;padding:6px 12px;background:#fff;border:1px solid #e2e8f0;border-radius:999px;font-size:0.75rem;color:#475569;font-weight:600;transition:all 0.2s;" onmouseover="this.style.borderColor='#22C55E';this.style.color='#166534';this.style.background='#F0FDF4'" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#475569';this.style.background='#fff'">💳 Outstanding credit</span>
        </div>
    </div>
    
    <script>
    function fillQuestion(q) {
        const input = document.getElementById('ai-question-input');
        if (input) {
            input.value = q;
            input.closest('form').submit();
        }
    }
    </script>

    <!-- V2 Main Grid -->
    <div class="d2-main">
        <div class="d2-charts-col">
            <div class="admin-card" style="flex:1;display:flex;flex-direction:column;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
                    <h3 style="margin:0;font-size:1.05rem;color:#0F172A;">Revenue Trend <?= helpTip("Shows how much money your farm earned over time. Rising line = more income.") ?></h3>
                    <span class="badge-pill badge-pill-success">Live Sync</span>
                </div>
                <div class="chart-box" style="flex:1;min-height:200px;">
                    <canvas id="chart-sales"></canvas>
                </div>
            </div>

            <div class="admin-card" style="flex:1;display:flex;flex-direction:column;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
                    <h3 style="margin:0;font-size:1.05rem;color:#0F172A;">Order Volumes <?= helpTip("How many orders you get each day or week. More orders = more customers buying.") ?></h3>
                    <span class="badge-pill badge-pill-info">Daily</span>
                </div>
                <div class="chart-box" style="flex:1;min-height:200px;">
                    <canvas id="chart-orders"></canvas>
                </div>
            </div>
        </div>

        <div class="d2-side">
            <!-- System Status -->
            <div class="admin-card" style="padding:18px !important;">
                <h3 style="margin:0 0 14px;font-size:1rem;color:#0F172A;">System Overview <?= helpTip("Summary of your farm: total animals, crops, orders, and stock levels at a glance.") ?></h3>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div style="display:flex;align-items:center;gap:12px;padding:11px 14px;background:#F0FDF4;border-radius:12px;border:1px solid #DCFCE7;">
                        <div style="width:9px;height:9px;background:#16a34a;border-radius:50%;box-shadow:0 0 0 4px rgba(22,163,74,0.15);"></div>
                        <div style="flex:1;">
                            <h5 style="margin:0;font-size:0.88rem;color:#0F172A;">Platform Status</h5>
                            <p style="margin:0;font-size:0.75rem;color:#64748b;">All systems operational</p>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;padding:11px 14px;background:#FEF5E0;border-radius:12px;border:1px solid #FDE68A;cursor:pointer;" onclick="window.location.href='hub_inventory.php?tab=alerts'">
                        <i data-lucide="bell" style="width:16px;height:16px;color:#d97706;"></i>
                        <div style="flex:1;">
                            <h5 style="margin:0;font-size:0.88rem;color:#0F172A;"><span id="kpi-alerts">0</span> Stock Alerts</h5>
                            <p style="margin:0;font-size:0.75rem;color:#64748b;">Items needing attention</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weather Widget -->
            <div class="admin-card" style="padding:18px !important;">
                <h4 style="margin:0 0 12px;font-size:0.95rem;color:#0F172A;"><i data-lucide="cloud-sun" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:6px;color:#d97706;"></i>Weather — Wangari</h4>
                <?php if ($weatherData && isset($weatherData["current_weather"])): ?>
                    <?php $cw = $weatherData["current_weather"]; $temp = $cw["temperature"] ?? 0; $wcode = $cw["weathercode"] ?? 0; $wind = $cw["windspeed"] ?? 0; $wmoMap = [0=>["☀️","Clear"],1=>["🌤","Mainly clear"],2=>["⛅","Partly cloudy"],3=>["☁️","Overcast"],45=>["🌫","Fog"],48=>["🌫","Rime fog"],51=>["🌦","Light drizzle"],53=>["🌦","Drizzle"],55=>["🌧","Dense drizzle"],61=>["🌧","Slight rain"],63=>["🌧","Moderate rain"],65=>["🌧","Heavy rain"],71=>["❄️","Slight snow"],73=>["❄️","Moderate snow"],75=>["❄️","Heavy snow"],80=>["🌦","Slight showers"],81=>["🌧","Moderate showers"],82=>["⛈","Violent showers"],95=>["⛈","Thunderstorm"],96=>["⛈","Thunderstorm+hail"],99=>["⛈","Severe thunderstorm"]]; $wInfo = $wmoMap[$wcode] ?? ["🌡","Unknown"]; ?>
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:10px;">
                        <span style="font-size:2.2rem;line-height:1;"><?= $wInfo[0] ?></span>
                        <div>
                            <div style="font-size:1.6rem;font-weight:700;color:#0F172A;"><?= number_format((float)$temp, 1) ?>°C</div>
                            <div style="font-size:0.8rem;color:#64748b;"><?= $wInfo[1] ?></div>
                        </div>
                    </div>
                    <div style="display:flex;gap:16px;font-size:0.82rem;color:#475569;">
                        <span>💨 <?= number_format((float)$wind, 0) ?> km/h</span>
                        <span>💧 <?= number_format((float)($weatherData["daily"]["precipitation_sum"][0] ?? 0), 1) ?> mm</span>
                    </div>
                    <?php if (isset($weatherData["daily"])): ?>
                    <div style="margin-top:12px;padding-top:10px;border-top:1px solid #f1f5f9;display:flex;gap:6px;overflow-x:auto;">
                        <?php for ($d = 1; $d < min(5, count($weatherData["daily"]["time"])); $d++): $dDate = $weatherData["daily"]["time"][$d]; $dMax = $weatherData["daily"]["temperature_2m_max"][$d] ?? 0; $dMin = $weatherData["daily"]["temperature_2m_min"][$d] ?? 0; $dRain = $weatherData["daily"]["precipitation_sum"][$d] ?? 0; $dayName = date("D", strtotime($dDate)); ?>
                            <div style="flex:1;min-width:55px;text-align:center;padding:6px;background:#f8fafc;border-radius:8px;">
                                <div style="font-size:0.7rem;color:#64748b;font-weight:600;"><?= $dayName ?></div>
                                <div style="font-size:0.85rem;font-weight:700;color:#0F172A;"><?= number_format((float)$dMax, 0) ?>°</div>
                                <div style="font-size:0.72rem;color:#94a3b8;"><?= number_format((float)$dMin, 0) ?>°</div>
                                <?php if ($dRain > 0): ?><div style="font-size:0.65rem;color:#3b82f6;">💧<?= number_format((float)$dRain, 1) ?></div><?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="color:#94a3b8;font-size:0.85rem;margin:0;">Weather data will load on next visit.</p>
                <?php endif; ?>
            </div>

            <!-- Financial Summary Widget -->
            <div class="admin-card" style="padding:18px !important;">
                <h4 style="margin:0 0 14px;font-size:0.95rem;color:#0F172A;"><i data-lucide="wallet" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:6px;color:#16a34a;"></i>This Month</h4>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#F0FDF4;border-radius:10px;border:1px solid #DCFCE7;">
                        <span style="font-size:0.85rem;color:#166534;font-weight:500;">Income</span>
                        <strong style="color:#166534;font-size:0.95rem;">KES <?= number_format($financeSummary["income"], 0) ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#FEF2F2;border-radius:10px;border:1px solid #FECACA;">
                        <span style="font-size:0.85rem;color:#991B1B;font-weight:500;">Expenses</span>
                        <strong style="color:#991B1B;font-size:0.95rem;">KES <?= number_format($financeSummary["expenses"], 0) ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:<?= $financeSummary["profit"] >= 0 ? "#F0FDF4" : "#FEF2F2" ?>;border-radius:10px;border:1px solid <?= $financeSummary["profit"] >= 0 ? "#DCFCE7" : "#FECACA" ?>;">
                        <span style="font-size:0.85rem;color:<?= $financeSummary["profit"] >= 0 ? "#166534" : "#991B1B" ?>;font-weight:500;">Net Profit</span>
                        <strong style="color:<?= $financeSummary["profit"] >= 0 ? "#166534" : "#991B1B" ?>;font-size:0.95rem;">KES <?= number_format($financeSummary["profit"], 0) ?></strong>
                    </div>
                    <?php if ($financeSummary["pending_credit"] > 0): ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#FEF5E0;border-radius:10px;border:1px solid #FDE68A;cursor:pointer;" onclick="window.location.href='credit.php'">
                        <span style="font-size:0.85rem;color:#92400E;font-weight:500;">Pending Credit</span>
                        <strong style="color:#92400E;font-size:0.95rem;">KES <?= number_format($financeSummary["pending_credit"], 0) ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Products -->
            <div class="admin-card" style="padding:18px !important;">
                <h4 style="margin:0 0 12px;font-size:0.95rem;color:#0F172A;">Top Moving Products</h4>
                <div id="top-products" style="border:1px solid var(--w2-border);border-radius:12px;overflow:hidden;">
                    <p style="padding:14px;text-align:center;color:#64748b;margin:0;font-size:0.85rem;">Loading products...</p>
                </div>
            </div>

            <!-- Raw Material Health -->
            <div class="admin-card" style="padding:18px !important;">
                <h4 style="margin:0 0 12px;font-size:0.95rem;color:#0F172A;">Raw Material Health</h4>
                <div id="raw-material-health" style="border:1px solid var(--w2-border);border-radius:12px;overflow:hidden;">
                    <p style="padding:14px;text-align:center;color:#64748b;margin:0;font-size:0.85rem;">Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d2-main" style="grid-template-columns:1fr 1fr;">
        <div class="admin-card">
            <h3 style="margin:0 0 14px;font-size:1.05rem;color:#0F172A;">Recent Activity <?= helpTip("Latest things done in the system: new orders, animal records, payments.") ?></h3>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead><tr><th>Action / Log Details</th><th style="text-align:right;">Time</th></tr></thead>
                    <tbody id="recent-activity">
                        <tr><td colspan="2" style="text-align:center;color:#64748b;padding:18px;">Fetching logs...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/Frontend/assets/js/wangari-charts.js"></script>
<script>
let dashCharts = {};
async function loadDashboard() {
    const response = await fetch('/Backend/api/admin_analytics.php');
    const data = await response.json();

    if (!data.success) {
        console.error('Analytics fetch failed', data);
        return;
    }

    // Destroy previous
    Object.values(dashCharts).forEach(c => c && c.destroy());
    dashCharts = {};

    const sales = (data.data.sales || []).map((item) => ({ date: item.day, value: Number(item.total) }));
    const orders = (data.data.orders || []).map((item) => ({ date: item.day, value: Number(item.cnt) }));
    const topProducts = data.data.top_products || [];

    const labels = sales.map((item) => WangariCharts.dayLabel(item.date));

    new Chart(document.getElementById('chart-sales'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Revenue',
                data: sales.map((item) => item.value),
                borderColor: '#166534',
                backgroundColor: 'rgba(22, 101, 52, 0.08)',
                fill: true,
                tension: 0.32,
                pointRadius: 3,
                pointBackgroundColor: '#22C55E',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#64748b' } },
                y: { grid: { color: 'rgba(148,163,184,0.12)' }, ticks: { color: '#64748b' } },
            },
        },
    });

    new Chart(document.getElementById('chart-orders'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Orders',
                data: orders.map((item) => item.value),
                backgroundColor: '#22C55E',
                borderRadius: 0,
                maxBarThickness: 28,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#64748b' } },
                y: { grid: { color: 'rgba(148,163,184,0.12)' }, ticks: { color: '#64748b', precision: 0 } },
            },
        },
    });

    // Top products (horizontal bar)
    if (document.getElementById('chart-top-products') && topProducts.length) {
        dashCharts.top = WangariCharts.hBarChart(
            document.getElementById('chart-top-products'),
            topProducts.map(p => p.name || ''),
            topProducts.map(p => +p.qty || 0),
            { color: WangariCharts.C.primary }
        );
    }

    // Recent activity (audit log)
    const acts = data.data.recent_orders || [];
    const tbody = document.getElementById('recent-activity');
    if (tbody) {
        if (!acts.length) {
            tbody.innerHTML = '<tr><td colspan="2" style="text-align:center;color:#94a3b8;padding:20px;">No recent activity.</td></tr>';
        } else {
            tbody.innerHTML = acts.map(a => {
                const name = ((a.first_name || '') + ' ' + (a.last_name || '')).trim() || 'Guest';
                const color = a.status === 'completed' ? '#16a34a' : a.status === 'cancelled' ? '#dc2626' : '#d97706';
                return `<tr><td><strong>${escapeHtml(name)}</strong> — order #${a.id} for KES ${parseFloat(a.total_amount||0).toLocaleString()} <span style="color:${color};font-size:0.78rem;text-transform:uppercase;font-weight:600;">${a.status}</span></td><td style="text-align:right;color:#64748b;font-size:0.85rem;">${(a.created_at||'').split(' ')[0]}</td></tr>`;
            }).join('');
        }
    }

    // Top KPI cards: Total Revenue / Orders Completed / Avg. Order Value
    const totalSales = sales.reduce((acc, v) => acc + v.value, 0);
    const totalOrders = orders.reduce((acc, v) => acc + v.value, 0);
    const avgOrder = totalOrders ? Math.round(totalSales / totalOrders) : 0;
    const kpiSalesEl = document.getElementById('kpi-sales');
    const kpiOrdersEl = document.getElementById('kpi-orders');
    const kpiAvgEl = document.getElementById('kpi-avg');
    const fmt = (n) => n.toLocaleString();
    if (kpiSalesEl) kpiSalesEl.textContent = 'KES ' + fmt(totalSales);
    if (kpiOrdersEl) kpiOrdersEl.textContent = fmt(totalOrders);
    if (kpiAvgEl) kpiAvgEl.textContent = 'KES ' + fmt(avgOrder);

    // Update small KPI numbers with count-up
    if (typeof WangariCharts !== 'undefined') WangariCharts.countUpAll();
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }
loadDashboard();
</script>


<?php include __DIR__ . '/includes/admin_footer.php'; ?>
