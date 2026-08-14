<?php
/**
 * Admin Dashboard with Analytics
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

// Admin access check
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager', 'stock_manager', 'sales_staff'], true)) {
    echo "<script>window.location.href = '/wangariadmin';</script>";
    exit;
}

$path_prefix = '../../';
$page_title = 'Admin Dashboard';
include __DIR__ . '/includes/admin_header.php';

$deniedModule = isset($_GET['denied']) ? 'that module' : '';
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
            background: radial-gradient(circle, rgba(208,242,76,0.18) 0%, transparent 65%);
        }
        .d2-hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #D0F24C;
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
        .d2-hero h1 .w2-serif { color: #D0F24C; font-family: 'Instrument Serif', serif; font-weight: 400; font-style: italic; }
        .d2-hero p { margin: 0 0 20px; color: rgba(255,255,255,0.72); font-size: 0.95rem; max-width: 560px; }
        .d2-hero-actions { display: flex; gap: 10px; flex-wrap: wrap; position: relative; z-index: 2; }
        .d2-hero-actions .btn { border-radius: 999px; }
        .d2-hero-actions .btn-lime { background: #D0F24C; color: #0B1220; font-weight: 700; }
        .d2-hero-actions .btn-ghost { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.25); }

        .d2-kpis {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .d2-kpi {
            background: #fff;
            border: 1px solid var(--w2-border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--w2-shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }
        .d2-kpi:hover { transform: translateY(-3px); box-shadow: 0 14px 34px rgba(15,23,42,0.09); }
        .d2-kpi small { color: #64748B; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; }
        .d2-kpi strong { display: block; margin-top: 6px; font-size: 1.55rem; font-weight: 800; color: #0F172A; letter-spacing: -0.5px; }
        .d2-kpi-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .d2-kpi-icon.green { background: #E4F7E9; color: #15803D; }
        .d2-kpi-icon.blue  { background: #E0EDFF; color: #1D4ED8; }
        .d2-kpi-icon.amber { background: #FEF5E0; color: #B45309; }
        .d2-kpi-icon.red   { background: #FDE8E8; color: #B91C1C; }

        .d2-ai {
            background: linear-gradient(135deg, #ffffff 0%, #F7FBF2 100%);
            border: 1px solid rgba(208,242,76,0.55);
            border-radius: 16px;
            padding: 22px 24px;
            margin-bottom: 24px;
            box-shadow: var(--w2-shadow);
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
        }
        .d2-ai-input-wrap input:focus { border-color: #1B7A3D; box-shadow: 0 0 0 4px rgba(22,101,52,0.1); }

        .d2-main { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px; }
        .d2-side { display: flex; flex-direction: column; gap: 16px; }
        @media (max-width: 1024px) {
            .d2-kpis { grid-template-columns: repeat(2, 1fr); }
            .d2-main { grid-template-columns: 1fr; }
        }
        @media (max-width: 560px) {
            .d2-kpis { grid-template-columns: 1fr; }
            .d2-hero { padding: 24px 20px; }
            .d2-ai-input-wrap { flex-direction: column; }
        }
    </style>

    <!-- V2 Welcome Hero -->
    <div class="d2-hero">
        <div class="d2-hero-eyebrow"><i data-lucide="leaf" style="width:14px;height:14px;"></i> Farm Operations Overview</div>
        <h1>Wangari <span class="w2-serif">Home</span></h1>
        <p>Everything that needs you today — orders, stock health, production and your AI assistant — in one clean workspace.</p>
        <div class="d2-hero-actions">
            <a class="btn btn-lime" href="orders.php"><i data-lucide="shopping-cart" style="width:16px;height:16px;"></i> Review Orders</a>
            <a class="btn btn-ghost" href="products.php"><i data-lucide="package" style="width:16px;height:16px;"></i> Manage Products</a>
            <a class="btn btn-ghost" href="reports.php"><i data-lucide="bar-chart" style="width:16px;height:16px;"></i> Analytics</a>
        </div>
    </div>

    <!-- V2 KPI cards -->
    <div class="d2-kpis">
        <div class="d2-kpi" onclick="window.location.href='orders.php'">
            <div>
                <small>Total Revenue</small>
                <strong id="kpi-sales">KES 0</strong>
            </div>
            <div class="d2-kpi-icon green"><i data-lucide="trending-up" style="width:22px;height:22px;"></i></div>
        </div>
        <div class="d2-kpi" onclick="window.location.href='orders.php'">
            <div>
                <small>Orders Completed</small>
                <strong id="kpi-orders">0</strong>
            </div>
            <div class="d2-kpi-icon blue"><i data-lucide="shopping-bag" style="width:22px;height:22px;"></i></div>
        </div>
        <div class="d2-kpi" onclick="window.location.href='orders.php'">
            <div>
                <small>Avg. Order Value</small>
                <strong id="kpi-avg">KES 0</strong>
            </div>
            <div class="d2-kpi-icon amber"><i data-lucide="pie-chart" style="width:22px;height:22px;"></i></div>
        </div>
        <div class="d2-kpi" onclick="window.location.href='stock_alerts.php'">
            <div>
                <small>Inventory Alerts</small>
                <strong id="kpi-alerts-summary">0</strong>
            </div>
            <div class="d2-kpi-icon red"><i data-lucide="alert-triangle" style="width:22px;height:22px;"></i></div>
        </div>
    </div>

    <!-- V2 AI Assistant (wired to real backend) -->
    <div class="d2-ai">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:42px;height:42px;border-radius:13px;background:#0B1220;color:#D0F24C;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 16px rgba(11,18,32,0.2);">
                    <i data-lucide="sparkles" style="width:20px;height:20px;"></i>
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
        <form class="d2-ai-input-wrap" method="POST" action="ai_assistant.php">
            <input name="question" placeholder="Try: 'How much did I sell this month?' or 'Who owes me credit?'" autocomplete="off">
            <button class="btn btn-primary" style="white-space:nowrap;"><i data-lucide="send" style="width:16px;height:16px;"></i> Ask</button>
        </form>
    </div>

    <!-- V2 Main Grid -->
    <div class="d2-main">
        <div class="admin-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
                <h3 style="margin:0;font-size:1.05rem;color:#0F172A;">Revenue Trend</h3>
                <span class="badge-pill badge-pill-success">Live Sync</span>
            </div>
            <div class="chart-box" style="height:280px;">
                <canvas id="chart-sales"></canvas>
            </div>
        </div>

        <div class="d2-side">
            <!-- System Status -->
            <div class="admin-card" style="padding:18px !important;">
                <h3 style="margin:0 0 14px;font-size:1rem;color:#0F172A;">System Overview</h3>
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
            <h3 style="margin:0 0 18px;font-size:1.05rem;color:#0F172A;">Order Volumes</h3>
            <div class="chart-box" style="height:240px;">
                <canvas id="chart-orders"></canvas>
            </div>
        </div>
        <div class="admin-card">
            <h3 style="margin:0 0 14px;font-size:1.05rem;color:#0F172A;">Recent Activity</h3>
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
<script src="/Frontend/assets/js/busia-charts.js"></script>
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

    const labels = sales.map((item) => BusiaCharts.dayLabel(item.date));

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
                pointBackgroundColor: '#D0F24C',
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
                backgroundColor: '#D0F24C',
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
        dashCharts.top = BusiaCharts.hBarChart(
            document.getElementById('chart-top-products'),
            topProducts.map(p => p.name || ''),
            topProducts.map(p => +p.qty || 0),
            { color: BusiaCharts.C.primary }
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
    if (typeof BusiaCharts !== 'undefined') BusiaCharts.countUpAll();
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }
loadDashboard();
</script>


<?php include __DIR__ . '/includes/admin_footer.php'; ?>
