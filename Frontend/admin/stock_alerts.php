<?php
/**
 * Sub-Module: Alert Center
 * Notifications for low stock levels and price fluctuations.
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager', 'stock_manager'], true)) {
    echo "<script>window.location.href = '/wangariadmin';</script>";
    exit;
}

$path_prefix = '../../';
$page_title = 'Alert Center';
include __DIR__ . '/includes/admin_header.php';
?>

<link rel="stylesheet" href="/Frontend/assets/css/admin-stock.css">

<div class="admin-stock-wrapper">
    <div class="dashboard-hero-card">
        <h1>Alert Center</h1>
        <p>Stay informed about ingredients that are running low, market price changes, and anything that needs your attention.</p>
    </div>

    <?php include __DIR__ . '/includes/stock_nav.php'; ?>

    <div class="admin-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0;">Active Notifications</h3>
            <button class="btn btn-trans btn-sm" onclick="resolveAlert(0)">Mark All as Resolved</button>
        </div>

        <div id="alerts-container">
            <div style="text-align: center; padding: 40px; color: #64748b;">
                <div class="spinner"></div>
                <p>Checking system health...</p>
            </div>
        </div>
    </div>
</div>

<script>
async function loadAlerts() {
    try {
        const response = await fetch('/Backend/api/admin_stock.php?action=get_dashboard');
        const result = await response.json();
        
        const container = document.getElementById('alerts-container');
        if (!result.success) {
            container.innerHTML = '<p style="padding:16px;text-align:center;color:#64748b;">Failed to load.</p>';
            return;
        }

        const { alerts, raw_materials } = result.data;

        // Build reorder alerts from raw materials
        const reorderAlerts = (raw_materials || [])
            .filter(rm => rm.is_below_reorder)
            .map(rm => ({
                id: 'rm-' + rm.id,
                alert_type: 'reorder_point',
                material_name: rm.name,
                stock_tons: Number(rm.stock_tons).toFixed(2),
                min_stock_level: Number(rm.min_stock_level).toFixed(2),
                days_covered: rm.days_covered,
                avg_daily_consumption_kg: rm.avg_daily_consumption_kg,
                message: `<strong>${rm.name}</strong> is below reorder threshold. Current: <strong>${Number(rm.stock_tons).toFixed(2)} kgs</strong> (min: ${Number(rm.min_stock_level).toFixed(2)} kgs). ${rm.days_covered < 999 ? `Covers <strong>${rm.days_covered} days</strong> of production at current velocity.` : 'No recent production data to estimate coverage.'}`,
                created_at: new Date().toISOString()
            }));

        const allAlerts = [...reorderAlerts, ...(alerts || [])];
        
        if (allAlerts.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 60px 40px; background: #f8fafc; border-radius: 8px;">
                    <i data-lucide="check-circle-2" style="width: 64px; height: 64px; color: #16a34a; margin-bottom: 16px;"></i>
                    <h3>System Healthy</h3>
                    <p>No critical stock alerts or price fluctuations detected at this time.</p>
                </div>
            `;
        } else {
            container.innerHTML = allAlerts.map(a => {
                let icon = 'bell', borderColor = '#f59e0b', bgColor = 'rgba(245,158,11,0.06)';
                
                if (a.alert_type === 'reorder_point') {
                    icon = 'package-x';
                    const urgent = a.days_covered < 7;
                    borderColor = urgent ? '#dc2626' : '#f59e0b';
                    bgColor = urgent ? 'rgba(220,38,38,0.04)' : 'rgba(245,158,11,0.04)';
                } else if (a.alert_type === 'low_stock') {
                    icon = 'alert-triangle';
                    borderColor = '#ef4444';
                    bgColor = 'rgba(239,68,68,0.04)';
                } else if (a.alert_type === 'price_fluctuation') {
                    icon = 'trending-up';
                    borderColor = '#3b82f6';
                    bgColor = 'rgba(59,130,246,0.04)';
                }

                return `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-left: 4px solid ${borderColor}; background: ${bgColor}; border-radius: 0 8px 8px 0; margin-bottom: 12px;">
                    <div style="display: flex; gap: 16px; align-items: center; flex: 1;">
                        <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center;">
                            <i data-lucide="${icon}"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <strong style="font-size: 0.9rem; text-transform: capitalize;">${a.alert_type.replace(/_/g, ' ')}</strong>
                                ${a.days_covered !== undefined && a.days_covered < 999 ? `<span style="background: ${a.days_covered < 3 ? '#dc2626' : (a.days_covered < 7 ? '#f59e0b' : '#22c55e')}; color: #fff; font-size: 0.7rem; padding: 2px 8px; border-radius: 9999px; font-weight: 700;">${a.days_covered}d coverage</span>` : ''}
                            </div>
                            <p style="margin: 0; color: #475569; font-size: 0.9rem;">${a.message}</p>
                            <span style="font-size: 0.75rem; color: #94a3b8;">${new Date(a.created_at).toLocaleString()}</span>
                        </div>
                    </div>
                    ${typeof a.id === 'number' ? `<button class="btn btn-trans btn-sm" onclick="resolveAlert(${a.id})">Resolve</button>` : ''}
                </div>`;
            }).join('');
        }

        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (err) { console.error(err); }
}

async function resolveAlert(id) {
    try {
        const formData = new FormData();
        formData.append('action', 'resolve_alert');
        formData.append('id', id);
        formData.append('csrf_token', window.WangariAdmin?.csrfToken || '');

        const response = await fetch('/Backend/api/admin_stock.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            loadAlerts();
        }
    } catch (err) { console.error(err); }
}

document.addEventListener('DOMContentLoaded', loadAlerts);
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
