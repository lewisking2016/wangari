<?php
/**
 * Sub-Module: Live Stock Dashboard
 * Real-time tracking of raw materials and finished bags.
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
$page_title = 'Live Stock Dashboard';
include __DIR__ . '/includes/admin_header.php';
?>

<link rel="stylesheet" href="/Frontend/assets/css/admin-stock.css">
<style>
    /* Premium Redesign for Feed & Stock */
    .kpi-summary-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }
    .stock-grid {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 24px;
    }
    @media (max-width: 991px) {
        .kpi-summary-row {
            grid-template-columns: 1fr;
        }
        .stock-grid {
            grid-template-columns: 1fr;
        }
    }
    .custom-progress-bar {
        background: #f1f5f9;
        border-radius: 99px;
        height: 6px;
        overflow: hidden;
        margin-top: 6px;
        position: relative;
    }
    .custom-progress-fill {
        height: 100%;
        border-radius: 99px;
        transition: width 0.4s ease;
    }
</style>

<div class="admin-stock-wrapper" style="animation: fadeIn 0.4s ease;">
    <!-- Minimal Premium Header Card -->
    <div style="background: linear-gradient(135deg, var(--admin-primary) 0%, #0c3e12 100%); padding: 24px; border-radius: 12px; margin-bottom: 24px; color: #ffffff; box-shadow: 0 10px 25px rgba(27,94,32,0.15);">
        <h1 style="color: #ffffff; margin: 0 0 4px 0; font-family:'Outfit',sans-serif; font-size: 1.5rem; font-weight: 700;">Feed & Stock Control Room</h1>
        <p style="color: rgba(255, 255, 255, 0.85); margin: 0; font-size: 0.9rem;">Real-time asset valuation, raw material reserves, and finished product stock levels.</p>
    </div>

    <?php include __DIR__ . '/includes/stock_nav.php'; ?>

    <!-- KPI Summary Row -->
    <div class="kpi-summary-row" style="margin-top: 24px;">
        <div class="stat-card" style="background: #ffffff; border-radius: 12px; border: 1px solid var(--admin-border);">
            <div class="stat-card-info">
                <small style="color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.72rem;">Raw Ingredients Value</small>
                <strong id="val-raw-total" style="font-size: 1.35rem; color: var(--admin-text-heading); font-family:'Outfit',sans-serif;">KES 0</strong>
            </div>
            <div class="stat-card-icon" style="background: rgba(27,94,32,0.08); color: var(--admin-primary);"><i data-lucide="database"></i></div>
        </div>
        <div class="stat-card" style="background: #ffffff; border-radius: 12px; border: 1px solid var(--admin-border);">
            <div class="stat-card-info">
                <small style="color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.72rem;">Finished Feed Value</small>
                <strong id="val-finished-total" style="font-size: 1.35rem; color: var(--admin-text-heading); font-family:'Outfit',sans-serif;">KES 0</strong>
            </div>
            <div class="stat-card-icon info" style="background: rgba(14,165,233,0.08); color: #0ea5e9;"><i data-lucide="package"></i></div>
        </div>
        <div class="stat-card" style="background: #ffffff; border-radius: 12px; border: 1px solid var(--admin-border); background: linear-gradient(135deg, #1b5e20, #2e7d32); color: #ffffff;">
            <div class="stat-card-info">
                <small style="color: rgba(255,255,255,0.75); font-weight: 600; text-transform: uppercase; font-size: 0.72rem;">Total Stock Assets</small>
                <strong id="val-total-assets" style="font-size: 1.35rem; color: #ffffff; font-family:'Outfit',sans-serif;">KES 0</strong>
            </div>
            <div class="stat-card-icon" style="background: rgba(255,255,255,0.15); color: #ffffff;"><i data-lucide="trending-up"></i></div>
        </div>
    </div>

    <!-- Main Stock Grid -->
    <div class="stock-grid">
        <!-- Raw Materials Section -->
        <div class="admin-card" style="border-radius: 12px; border: 1px solid var(--admin-border); padding: 24px; background: #ffffff;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h3 style="margin: 0; font-family:'Outfit',sans-serif; font-size: 1.1rem; color: var(--admin-text-heading);">Raw Ingredients (Bulk Stock)</h3>
                    <p style="margin: 3px 0 0 0; font-size: 0.8rem; color: #64748b;">Ingredients used to manufacture premium livestock feed mixtures.</p>
                </div>
                <button class="btn btn-primary btn-sm" onclick="openRMModal()">
                    <i data-lucide="plus-circle" style="width: 15px; height: 15px;"></i> Add Ingredient
                </button>
            </div>
            <div class="table-responsive">
                <table class="admin-table" style="font-size: 0.88rem;">
                    <thead>
                        <tr>
                            <th>Ingredient</th>
                            <th>Stock Level</th>
                            <th>Avg Cost</th>
                            <th>Total Value</th>
                            <th>Days Left</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="raw-materials-body">
                        <tr><td colspan="7" style="text-align:center; padding: 32px; color: #94a3b8;"><div style="display:inline-flex; align-items:center; gap:8px;"><div style="width:16px;height:16px;border:2px solid #cbd5e1;border-top-color:var(--admin-primary);border-radius:50%;animation:spin 0.8s linear infinite;"></div>Loading inventory...</div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Finished Product Stocks -->
        <div class="admin-card" style="border-radius: 12px; border: 1px solid var(--admin-border); padding: 24px; background: #ffffff;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h3 style="margin: 0; font-family:'Outfit',sans-serif; font-size: 1.1rem; color: var(--admin-text-heading);">Finished Feed Bags</h3>
                    <p style="margin: 3px 0 0 0; font-size: 0.8rem; color: #64748b;">Packaged bags ready for retail distribution.</p>
                </div>
                <span class="badge-pill badge-pill-success" style="font-size: 0.72rem; font-weight: 700; padding: 4px 10px;">Store Sync</span>
            </div>
            <div class="table-responsive">
                <table class="admin-table" style="font-size: 0.88rem;">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Stock (Bags)</th>
                            <th>Bag Value</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="finished-stock-body">
                        <tr><td colspan="4" style="text-align:center; padding: 32px; color: #94a3b8;">Loading inventory...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Raw Material Add/Edit Modal -->
<div id="rm-modal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.45); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: #ffffff; padding: 32px; border-radius: 12px; width: 100%; max-width: 480px; box-shadow: 0 20px 45px rgba(0,0,0,0.18);">
        <h3 id="rm-modal-title" style="margin:0 0 20px 0; font-family:'Outfit',sans-serif; color: var(--admin-text-heading);">Add Raw Material</h3>
        <form id="rm-form">
            <input type="hidden" name="id" id="rm-id">
            <div class="admin-form-group" style="margin-bottom: 16px;">
                <label class="admin-form-label" style="font-weight: 600;">Ingredient Name *</label>
                <input type="text" name="name" id="rm-name" class="admin-form-control" required placeholder="e.g. Yellow Maize, Cotton Cake">
            </div>
            <div class="admin-form-group" style="margin-bottom: 16px;">
                <label class="admin-form-label" style="font-weight: 600;">Current Price per kg (KES) *</label>
                <input type="number" name="current_price_per_ton" id="rm-price" class="admin-form-control" step="0.01" required placeholder="0.00">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px;">
                <div class="admin-form-group">
                    <label class="admin-form-label" style="font-weight: 600;">Current Stock (kgs) *</label>
                    <input type="number" name="stock_tons" id="rm-stock" class="admin-form-control" step="0.01" required placeholder="0.00">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label" style="font-weight: 600;">Min Alert Level (kgs)</label>
                    <input type="number" name="min_stock_level" id="rm-min" class="admin-form-control" step="0.01" value="1000.00">
                </div>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 28px;">
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeRMModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;" id="rm-submit-btn"><i data-lucide="save" style="width: 15px; height: 15px;"></i> Save Material</button>
            </div>
        </form>
    </div>
</div>

<script>
window.raw_materials_data = [];
const CSRF = window.WangariAdmin?.csrfToken || '';

async function loadDashboardData() {
    try {
        const response = await fetch('/Backend/api/admin_stock.php?action=get_dashboard');
        const result = await response.json();
        if (!result.success) return;

        window.raw_materials_data = result.data.raw_materials;
        const { raw_materials, finished_products, summary } = result.data;

        // Render Raw Materials
        document.getElementById('raw-materials-body').innerHTML = raw_materials.map(m => {
            const stockVal = Number(m.stock_tons);
            const minVal = Number(m.min_stock_level);
            let statusText = 'Healthy';
            let statusClass = 'badge-pill-success';
            let daysColor = '#16a34a';

            if (stockVal <= 0) {
                statusText = 'Stockout';
                statusClass = 'badge-pill-danger';
                daysColor = '#dc2626';
            } else if (stockVal <= minVal) {
                statusText = 'Low Stock';
                statusClass = 'badge-pill-warning';
                daysColor = '#d97706';
            }

            const fillWidth = Math.min(100, (stockVal / (minVal * 3 || 3000)) * 100);

            return `
            <tr>
                <td><strong>${m.name}</strong></td>
                <td>
                    <span style="font-weight: 700;">${stockVal.toLocaleString()} kg</span>
                    <div class="custom-progress-bar">
                        <div class="custom-progress-fill" style="width: ${fillWidth}%; background: ${daysColor};"></div>
                    </div>
                </td>
                <td>KES ${Number(m.current_price_per_ton).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                <td><strong>KES ${Number(m.total_value).toLocaleString(undefined, {minimumFractionDigits:2})}</strong></td>
                <td>
                    ${m.days_covered < 999 ? `<span style="color: ${daysColor}; font-weight: 700;">${m.days_covered} days</span>` : '<span style="color:#94a3b8;">No records</span>'}
                </td>
                <td><span class="badge-pill ${statusClass}">${statusText}</span></td>
                <td style="text-align: right;">
                    <button class="btn btn-trans btn-sm" onclick="editRM(${m.id})"><i data-lucide="pencil" style="width: 12px; height: 12px;"></i> Edit</button>
                </td>
            </tr>
        `}).join('');

        // Render Finished Products
        document.getElementById('finished-stock-body').innerHTML = finished_products.map(p => `
            <tr>
                <td><strong>${p.name}</strong></td>
                <td><strong>${p.stock_quantity} Bags</strong></td>
                <td>KES ${Number(p.price).toLocaleString()}</td>
                <td style="white-space: nowrap;">
                    <a href="/Frontend/admin/hub_inventory.php?tab=products" class="btn btn-trans btn-sm"><i data-lucide="eye" style="width: 12px; height: 12px;"></i> Manage</a>
                </td>
            </tr>
        `).join('');

        // Update Summary Cards
        document.getElementById('val-raw-total').textContent = `KES ${Number(summary.raw_value).toLocaleString(undefined, {maximumFractionDigits:0})}`;
        document.getElementById('val-finished-total').textContent = `KES ${Number(summary.finished_value).toLocaleString(undefined, {maximumFractionDigits:0})}`;
        document.getElementById('val-total-assets').textContent = `KES ${Number(summary.total_value).toLocaleString(undefined, {maximumFractionDigits:0})}`;

        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (err) {
        console.error('Failed to load dashboard data', err);
    }
}

function openRMModal() {
    document.getElementById('rm-modal-title').textContent = 'Add Ingredient';
    document.getElementById('rm-form').reset();
    document.getElementById('rm-id').value = '';
    document.getElementById('rm-modal').style.display = 'flex';
}
function closeRMModal() {
    document.getElementById('rm-modal').style.display = 'none';
}
function editRM(id) {
    const m = window.raw_materials_data.find(item => item.id == id);
    if (!m) return;
    document.getElementById('rm-modal-title').textContent = 'Edit Ingredient';
    document.getElementById('rm-id').value = m.id;
    document.getElementById('rm-name').value = m.name;
    document.getElementById('rm-price').value = m.current_price_per_ton;
    document.getElementById('rm-stock').value = m.stock_tons;
    document.getElementById('rm-min').value = m.min_stock_level;
    document.getElementById('rm-modal').style.display = 'flex';
}

document.getElementById('rm-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('rm-submit-btn');
    btn.disabled = true; btn.textContent = 'Saving...';
    
    const formData = new FormData(e.target);
    formData.append('csrf_token', CSRF);
    
    try {
        const response = await fetch('/Backend/api/admin_stock.php?action=save_raw_material', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            closeRMModal();
            loadDashboardData();
        } else {
            alert(result.message || 'Failed to save material');
        }
    } catch (err) {
        alert('An error occurred while saving.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="save" style="width: 15px; height: 15px;"></i> Save Material';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
});

document.addEventListener('click', e => {
    const m = document.getElementById('rm-modal');
    if (m && e.target === m) m.style.display = 'none';
});

document.addEventListener('DOMContentLoaded', () => {
    loadDashboardData();
    setInterval(loadDashboardData, 30000);
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
