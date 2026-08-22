<?php
/**
 * Admin - Reports & Analytics
 * Premium Minimalist Redesign
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();

$path_prefix = '../../';
$page_title = 'Reports & Analytics - Admin';

include __DIR__ . '/includes/admin_header.php';

// Check admin access
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','sales_staff'], true)) {
    echo "<script>window.location.href = '/Frontend/admin/login.php';</script>";
    exit;
}

$pdo = getDB();
$stats = [
    'total_revenue' => 0,
    'total_orders' => 0,
    'total_customers' => 0,
    'low_stock_count' => 0
];

$range = $_GET['range'] ?? 'all';
$date_query = "";
if ($range === 'month') {
    $date_query = " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
}

if ($pdo) {
    try {
        $stats['total_revenue'] = (float)$pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled' $date_query")->fetchColumn();
        $stats['total_orders'] = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE 1=1 $date_query")->fetchColumn();
        $stats['total_customers'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer' $date_query")->fetchColumn();
        $stats['low_stock_count'] = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 10")->fetchColumn();
    } catch (Exception $e) {
        error_log("Admin reports error: " . $e->getMessage());
    }
}


// Fetch top products from DB
$top_products = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT p.name, COALESCE(SUM(oi.quantity), 0) as total_sold 
                             FROM products p 
                             LEFT JOIN order_items oi ON p.id = oi.product_id 
                             GROUP BY p.id, p.name 
                             ORDER BY total_sold DESC LIMIT 5");
        $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Top products error: " . $e->getMessage());
    }
}

// Fetch monthly revenue for chart
$monthly_revenue = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as revenue 
                             FROM orders WHERE status != 'cancelled' 
                             GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                             ORDER BY month DESC LIMIT 6");
        $monthly_revenue = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        error_log("Monthly revenue error: " . $e->getMessage());
    }
}

// Fetch recent orders
$recent_orders = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT o.id, o.total_amount, o.status, o.created_at, u.username 
                             FROM orders o JOIN users u ON o.user_id = u.id 
                             ORDER BY o.created_at DESC LIMIT 5");
        $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Recent orders error: " . $e->getMessage());
    }
}
?>

<!-- Page Title -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.5rem; color: var(--admin-text-heading);">Reports & Analytics</h2>
        <p style="margin: 4px 0 0 0; font-size: 0.875rem; color: #64748b;">Real-time performance metrics from your database.</p>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="?range=<?php echo $range === 'month' ? 'all' : 'month'; ?>" class="btn <?php echo $range === 'month' ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius: 4px; display: flex; align-items: center; gap: 8px; text-decoration: none;">
            <i data-lucide="calendar" style="width: 18px; height: 18px;"></i>
            <span><?php echo $range === 'month' ? 'Showing: This Month' : 'This Month'; ?></span>
        </a>
        <a href="/Backend/api/admin_actions.php?action=export_orders" class="btn btn-primary" style="border-radius: 4px; display: flex; align-items: center; gap: 8px; text-decoration: none;">
            <i data-lucide="download" style="width: 18px; height: 18px;"></i>
            <span>Export Report</span>
        </a>
    </div>
</div>

<!-- KPI Row -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px;">
    <div class="stat-card">
        <div class="stat-card-info">
            <small>Total Revenue</small>
            <strong>KES <?php echo number_format($stats['total_revenue']); ?></strong>
        </div>
        <div class="stat-card-icon">
            <i data-lucide="dollar-sign"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <small>Total Orders</small>
            <strong><?php echo number_format($stats['total_orders']); ?></strong>
        </div>
        <div class="stat-card-icon info">
            <i data-lucide="shopping-bag"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <small>Customers</small>
            <strong><?php echo number_format($stats['total_customers']); ?></strong>
        </div>
        <div class="stat-card-icon" style="background: rgba(124, 58, 237, 0.1); color: #7c3aed;">
            <i data-lucide="users"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <small>Low Stock Items</small>
            <strong><?php echo $stats['low_stock_count']; ?></strong>
        </div>
        <div class="stat-card-icon accent">
            <i data-lucide="alert-triangle"></i>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 28px;">
    <!-- Revenue Chart -->
    <div class="admin-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.15rem; color: var(--admin-text-heading);">Revenue Trend</h3>
            <span class="badge-pill badge-pill-success">Live Data</span>
        </div>
        <div style="height: 280px; position: relative;">
            <canvas id="report-chart-revenue"></canvas>
        </div>
    </div>

    <!-- Top Products -->
    <div class="admin-card">
        <h3 style="margin: 0 0 20px 0; font-family: 'Outfit', sans-serif; font-size: 1.15rem; color: var(--admin-text-heading);">Top Products</h3>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php if (!empty($top_products)): ?>
                <?php 
                $max_sold = max(array_column($top_products, 'total_sold'));
                if ($max_sold == 0) $max_sold = 1;
                foreach ($top_products as $tp): 
                    $pct = round(($tp['total_sold'] / $max_sold) * 100);
                ?>
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-size: 0.9rem; color: var(--admin-text-main); font-weight: 500;"><?php echo htmlspecialchars($tp['name']); ?></span>
                        <span style="font-size: 0.85rem; font-weight: 600; color: var(--admin-text-heading);"><?php echo $tp['total_sold']; ?> sold</span>
                    </div>
                    <div style="width: 100%; height: 6px; background: #f1f5f9; border-radius: 0; overflow: hidden;">
                        <div style="width: <?php echo $pct; ?>%; height: 100%; background: var(--admin-primary); transition: width 0.5s ease;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #64748b; font-size: 0.9rem; text-align: center; padding: 20px 0;">No product sales data yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Orders Table -->
<div class="admin-card" style="padding: 0; overflow: hidden; border-radius: 4px;">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--admin-border);">
        <h3 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.15rem; color: var(--admin-text-heading);">Recent Transactions</h3>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_orders)): ?>
                    <?php foreach ($recent_orders as $ro): ?>
                    <?php 
                        $sc = 'badge-pill-warning';
                        if ($ro['status'] === 'completed') $sc = 'badge-pill-success';
                        elseif ($ro['status'] === 'cancelled') $sc = 'badge-pill-danger';
                    ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 600; color: var(--admin-primary);">#<?php echo $ro['id']; ?></td>
                        <td style="font-weight: 500;"><?php echo htmlspecialchars($ro['username']); ?></td>
                        <td style="font-weight: 600;">KES <?php echo number_format((float)$ro['total_amount']); ?></td>
                        <td><span class="badge-pill <?php echo $sc; ?>"><?php echo ucfirst($ro['status']); ?></span></td>
                        <td style="color: #64748b; font-size: 0.9rem;"><?php echo date('M d, Y', strtotime($ro['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; color: #64748b; padding: 24px;">No orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Formula Performance Report -->
<div class="admin-card" style="margin-top: 24px; overflow: hidden; border-radius: 4px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h3 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.15rem; color: var(--admin-text-heading);">Formula Performance Report</h3>
            <p style="margin: 4px 0 0; font-size: 0.85rem; color: #64748b;">Yield comparison: production output vs. sales revenue per feed formula.</p>
        </div>
        <span class="badge-pill badge-pill-success">Live Data</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Formula</th>
                    <th>Product</th>
                    <th style="text-align: center;">Total Produced</th>
                    <th style="text-align: center;">Total Sold</th>
                    <th style="text-align: right;">Production Cost</th>
                    <th style="text-align: right;">Revenue</th>
                    <th style="text-align: right;">Gross Profit</th>
                    <th style="text-align: center;">Margin %</th>
                </tr>
            </thead>
            <tbody id="formula-perf-body">
                <tr><td colspan="8" style="text-align: center; color: #64748b; padding: 24px;">Loading formula performance data...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
// Load formula performance data
async function loadFormulaPerformance() {
    try {
        const res = await fetch('/Backend/api/admin_stock.php?action=get_formula_performance');
        const result = await res.json();
        const tbody = document.getElementById('formula-perf-body');
        
        if (!result.success || !result.data.length) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#64748b;padding:24px;">No formula performance data available yet.</td></tr>';
            return;
        }

        tbody.innerHTML = result.data.map(p => {
            const profitColor = p.gross_profit >= 0 ? '#16a34a' : '#dc2626';
            const marginColor = p.profit_margin_pct >= 20 ? '#16a34a' : (p.profit_margin_pct >= 10 ? '#f59e0b' : '#dc2626');
            return `
            <tr>
                <td style="font-weight: 700;">${p.formula_name}</td>
                <td style="color: #475569;">${p.product_name}</td>
                <td style="text-align: center; font-weight: 600;">${Number(p.total_produced).toLocaleString()} bags</td>
                <td style="text-align: center; font-weight: 600;">${Number(p.total_sold).toLocaleString()} units</td>
                <td style="text-align: right; color: #475569;">KES ${Number(p.total_production_cost).toLocaleString()}</td>
                <td style="text-align: right; font-weight: 600;">KES ${Number(p.total_revenue).toLocaleString()}</td>
                <td style="text-align: right; font-weight: 700; color: ${profitColor};">KES ${Number(p.gross_profit).toLocaleString()}</td>
                <td style="text-align: center;">
                    <span style="background: ${marginColor}; color: #fff; padding: 3px 10px; border-radius: 9999px; font-size: 0.8rem; font-weight: 700;">
                        ${p.profit_margin_pct}%
                    </span>
                </td>
            </tr>`;
        }).join('');
    } catch(e) { console.error(e); }
}

document.addEventListener('DOMContentLoaded', loadFormulaPerformance);
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const labels = <?php echo json_encode(array_map(function($m) { 
        return date('M Y', strtotime($m['month'] . '-01')); 
    }, $monthly_revenue)); ?>;
    const values = <?php echo json_encode(array_map(function($m) { 
        return (float)$m['revenue']; 
    }, $monthly_revenue)); ?>;

    if (document.getElementById('report-chart-revenue') && labels.length > 0) {
        new Chart(document.getElementById('report-chart-revenue'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue',
                    data: values,
                    borderColor: '#1B5E20',
                    backgroundColor: 'rgba(27, 94, 32, 0.08)',
                    fill: true,
                    tension: 0.32,
                    pointRadius: 4,
                    pointBackgroundColor: '#FFC107',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b' } },
                    y: { grid: { color: 'rgba(148,163,184,0.12)' }, ticks: { color: '#64748b' } },
                }
            }
        });
    }
});
</script>

<?php
include __DIR__ . '/includes/admin_footer.php';
?>
