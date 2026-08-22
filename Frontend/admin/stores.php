<?php
/**
 * Admin — Stores / Raw Materials Module
 * Mirrors "STORES TRACKING 2026" spreadsheet with two views:
 *  - Feed Ingredients (maize, soya, lime, premix etc.)
 *  - Drugs & Other Items (Amin Vit, Tylodoxy, Agritonic etc.)
 * Tracks: opening balance, received, used_production, transfer, sales
 */
declare(strict_types=1);
$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();
$page_title = 'Stores & Raw Materials - Admin';
include __DIR__ . '/includes/admin_header.php';

$pdo = getDB();
$tab = $_GET['tab'] ?? 'ingredients';
$validTabs = ['ingredients','drugs','movements','suppliers'];
if (!in_array($tab, $validTabs, true)) $tab = 'ingredients';

// Loadments will be fetched via safe API calls; protect against missing tables
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Stores & Raw Materials</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Track every kilogram of feed ingredients, drugs, and packaging — with full movement history.</p>
    </div>
</div>

<!-- Tab Bar -->
<div style="display:flex;gap:4px;background:#f1f5f9;padding:5px;border-radius:10px;margin-bottom:24px;overflow-x:auto;">
    <a href="?tab=ingredients" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;font-weight:600;font-size:0.86rem;<?= $tab==='ingredients'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>"><i data-lucide="wheat" style="width:15px;height:15px;"></i> Feed Ingredients</a>
    <a href="?tab=drugs" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;font-weight:600;font-size:0.86rem;<?= $tab==='drugs'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>"><i data-lucide="pill" style="width:15px;height:15px;"></i> Drugs & Other</a>
    <a href="?tab=movements" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;font-weight:600;font-size:0.86rem;<?= $tab==='movements'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>"><i data-lucide="arrow-left-right" style="width:15px;height:15px;"></i> Movements</a>
    <a href="?tab=suppliers" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;font-weight:600;font-size:0.86rem;<?= $tab==='suppliers'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>"><i data-lucide="truck" style="width:15px;height:15px;"></i> Suppliers</a>
</div>

<?php if ($tab === 'ingredients' || $tab === 'drugs'): ?>
<?php $cat = $tab === 'ingredients' ? 'feed_ingredient' : ['drug','other']; ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;"><?= $tab==='ingredients'?'Feed Ingredients':'Drugs & Other Items' ?></h3>
        <div style="display:flex;gap:8px;">
            <a href="/Backend/api/export.php?module=raw_materials" class="btn btn-outline"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
            <button class="btn btn-outline" onclick="openMaterialModal()"><i data-lucide="plus" style="width:15px;height:15px;"></i> Add Material</button>
            <button class="btn btn-primary" onclick="openMovementModal()"><i data-lucide="arrow-down-circle" style="width:15px;height:15px;"></i> Record Movement</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr>
                <th>Material</th><th>Code</th><th>Category</th>
                <th>Opening</th><th>Current Stock</th><th>Reserved</th>
                <th>Min Level</th><th>Unit Price</th><th>Stock Value</th><th>Status</th><th>Actions</th>
            </tr></thead>
            <tbody id="materials-body">
                <tr><td colspan="11" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($tab === 'movements'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Stock Movement History</h3>
        <div style="display:flex;gap:8px;align-items:center;">
            <select class="admin-form-control" id="mv-filter-mat" style="max-width:280px;" onchange="loadMovements()"><option value="">All materials</option></select>
            <input class="admin-form-control" type="date" id="mv-from" onchange="loadMovements()" style="max-width:160px;">
            <input class="admin-form-control" type="date" id="mv-to" onchange="loadMovements()" style="max-width:160px;">
            <a href="/Backend/api/export.php?module=stores_movements" class="btn btn-outline" id="mv-export"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
            <button class="btn btn-primary" onclick="openMovementModal()"><i data-lucide="plus" style="width:15px;height:15px;"></i> New Movement</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Material</th><th>Type</th><th>Quantity (kg/unit)</th><th>Balance After</th><th>Unit Cost</th><th>Total</th><th>Description</th></tr></thead>
            <tbody id="movements-body">
                <tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php else: /* suppliers */ ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Suppliers</h3>
        <button class="btn btn-primary" onclick="alert('Supplier form coming soon. Use direct API in the meantime.')"><i data-lucide="plus" style="width:15px;height:15px;"></i> Add Supplier</button>
    </div>
    <p style="color:#64748b;font-size:0.9rem;">Supplier management — track vendors of maize, soya, drugs, packaging, etc.</p>
</div>
<?php endif; ?>

<!-- Material Modal -->
<div id="material-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:520px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 id="material-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add Material</h3>
        <form id="material-form">
            <input type="hidden" id="m-id">
            <div class="admin-form-group"><label class="admin-form-label">Material Name *</label><input class="admin-form-control" id="m-name" required placeholder="e.g. Maize"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Code</label><input class="admin-form-control" id="m-code" placeholder="e.g. MAIZE-01"></div>
                <div class="admin-form-group"><label class="admin-form-label">Unit</label>
                    <select class="admin-form-control" id="m-unit">
                        <option value="kg">kg</option>
                        <option value="g">g</option>
                        <option value="litre">litre</option>
                        <option value="piece">piece</option>
                        <option value="bag">bag</option>
                        <option value="crate">crate</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Category</label>
                    <select class="admin-form-control" id="m-cat">
                        <option value="feed_ingredient">Feed Ingredient</option>
                        <option value="drug">Drug</option>
                        <option value="vaccine">Vaccine</option>
                        <option value="packaging">Packaging</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Opening Balance</label><input class="admin-form-control" type="number" step="0.001" id="m-open" value="0"></div>
                <div class="admin-form-group"><label class="admin-form-label">Min Stock Level</label><input class="admin-form-control" type="number" step="0.001" id="m-min" value="1"></div>
                <div class="admin-form-group"><label class="admin-form-label">Price per Unit (KES)</label><input class="admin-form-control" type="number" step="0.01" id="m-price" value="0"></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeMaterialModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Movement Modal -->
<div id="movement-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:520px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Record Stock Movement</h3>
        <form id="movement-form">
            <div class="admin-form-group"><label class="admin-form-label">Material *</label>
                <select class="admin-form-control" id="mv-material" required></select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" id="mv-date" required value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Type *</label>
                    <select class="admin-form-control" id="mv-type" required>
                        <option value="received">Received (in)</option>
                        <option value="used_production">Used in Production (out)</option>
                        <option value="used_treatment">Used in Treatment (out)</option>
                        <option value="sold">Sold (out)</option>
                        <option value="transfer_out">Transfer Out</option>
                        <option value="transfer_in">Transfer In</option>
                        <option value="adjustment_add">Adjustment Add</option>
                        <option value="adjustment_remove">Adjustment Remove</option>
                        <option value="staff_use">Staff Use</option>
                        <option value="wastage">Wastage/Spoilage</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Quantity *</label><input class="admin-form-control" type="number" step="0.001" id="mv-qty" required min="0.001"></div>
                <div class="admin-form-group"><label class="admin-form-label">Unit Cost (KES)</label><input class="admin-form-control" type="number" step="0.01" id="mv-cost" value="0"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Reference (e.g. supplier invoice)</label><input class="admin-form-control" id="mv-ref" placeholder="e.g. Supplier Invoice #1234"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Description</label><input class="admin-form-control" id="mv-desc" placeholder="e.g. Received from Fred, Used for Layers Batch 17"></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeMovementModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Movement</button>
            </div>
        </form>
    </div>
</div>

<script>
const CSRF = window.WangariAdmin?.csrfToken || '';
const currentTab = '<?= $tab ?>';
let allMaterials = [];

async function loadMaterials() {
    const tbody = document.getElementById('materials-body');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch('/Backend/api/admin_poultry_v2.php?action=get_materials');
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        allMaterials = r.data || [];
        // Populate filter dropdown for movements tab
        const f = document.getElementById('mv-filter-mat');
        if (f) {
            f.innerHTML = '<option value="">All materials</option>' + allMaterials.map(m => `<option value="${m.id}">${escapeHtml(m.material_name)}</option>`).join('');
        }
        // Populate movement modal material select
        const m = document.getElementById('mv-material');
        if (m) {
            m.innerHTML = '<option value="">Choose material...</option>' + allMaterials.map(mat => `<option value="${mat.id}">${escapeHtml(mat.material_name)} (${mat.current_stock} ${mat.unit})</option>`).join('');
        }
        // Filter by tab
        const cats = currentTab === 'ingredients' ? ['feed_ingredient'] : ['drug','other','vaccine','packaging'];
        const filtered = allMaterials.filter(m => cats.includes(m.category));
        if (!filtered.length) { tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:28px;color:#94a3b8;">No materials yet.</td></tr>'; return; }
        tbody.innerHTML = filtered.map(m => {
            const stock = parseFloat(m.current_stock);
            const min = parseFloat(m.min_stock_level);
            const lowStock = stock <= min;
            const value = stock * parseFloat(m.current_price_per_unit);
            return `<tr>
                <td><strong>${escapeHtml(m.material_name)}</strong></td>
                <td><code style="background:#f1f5f9;padding:2px 8px;border-radius:4px;">${escapeHtml(m.material_code||'—')}</code></td>
                <td><span class="badge-pill badge-pill-info">${m.category}</span></td>
                <td>${parseFloat(m.opening_balance).toFixed(2)} ${m.unit}</td>
                <td><strong>${stock.toFixed(2)} ${m.unit}</strong></td>
                <td>${parseFloat(m.reserved_production_kg||0).toFixed(2)} ${m.unit}</td>
                <td>${min.toFixed(2)}</td>
                <td>KES ${parseFloat(m.current_price_per_unit).toFixed(2)}</td>
                <td>KES ${value.toFixed(2)}</td>
                <td>${lowStock?'<span class="badge-pill badge-pill-danger">LOW</span>':'<span class="badge-pill badge-pill-success">OK</span>'}</td>
                <td>
                    <button class="btn btn-trans btn-sm" onclick='openMaterialModal(${JSON.stringify(m)})'><i data-lucide="pencil" style="width:13px;height:13px;"></i></button>
                    <button class="btn btn-primary btn-sm" onclick='openMovementModalFor(${m.id})'><i data-lucide="plus" style="width:13px;height:13px;"></i></button>
                </td>
            </tr>`;
        }).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;color:#dc2626;">Network error</td></tr>';
    }
}

async function loadMovements() {
    const tbody = document.getElementById('movements-body');
    if (!tbody) return;
    const mat = document.getElementById('mv-filter-mat')?.value || '';
    const from = document.getElementById('mv-from')?.value || '';
    const to = document.getElementById('mv-to')?.value || '';
    let url = '/Backend/api/admin_poultry_v2.php?action=get_movements';
    if (mat) url += '&material_id=' + mat;
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch(url);
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        let data = r.data || [];
        if (from) data = data.filter(m => m.movement_date >= from);
        if (to) data = data.filter(m => m.movement_date <= to);
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">No movements yet.</td></tr>'; return; }
        tbody.innerHTML = data.map(m => {
            const isIn = m.movement_type.includes('in') || m.movement_type==='received' || m.movement_type==='opening_balance' || m.movement_type==='adjustment_add' || m.movement_type==='transfer_in';
            const qty = parseFloat(m.quantity_kg);
            return `<tr>
                <td>${m.movement_date}</td>
                <td><strong>${escapeHtml(m.material_name||'—')}</strong></td>
                <td><span class="badge-pill ${isIn?'badge-pill-success':'badge-pill-warning'}">${m.movement_type}</span></td>
                <td style="color:${isIn?'#16a34a':'#dc2626'};font-weight:700;">${isIn?'+':''}${qty.toFixed(2)} ${m.unit||''}</td>
                <td><strong>${parseFloat(m.balance_after).toFixed(2)}</strong></td>
                <td>KES ${parseFloat(m.unit_cost).toFixed(2)}</td>
                <td>KES ${parseFloat(m.total_cost).toFixed(2)}</td>
                <td>${escapeHtml(m.description||'')}</td>
            </tr>`;
        }).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#dc2626;">Network error</td></tr>';
    }
}

function openMaterialModal(d) {
    document.getElementById('material-modal-title').textContent = d?.id ? 'Edit Material' : 'Add Material';
    document.getElementById('m-id').value = d?.id || '';
    document.getElementById('m-name').value = d?.material_name || '';
    document.getElementById('m-code').value = d?.material_code || '';
    document.getElementById('m-unit').value = d?.unit || 'kg';
    document.getElementById('m-cat').value = d?.category || 'feed_ingredient';
    document.getElementById('m-open').value = d?.opening_balance || 0;
    document.getElementById('m-min').value = d?.min_stock_level || 1;
    document.getElementById('m-price').value = d?.current_price_per_unit || 0;
    document.getElementById('material-modal').style.display = 'flex';
}
function closeMaterialModal() { document.getElementById('material-modal').style.display = 'none'; }

document.getElementById('material-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('id', document.getElementById('m-id').value);
    fd.append('material_name', document.getElementById('m-name').value);
    fd.append('material_code', document.getElementById('m-code').value);
    fd.append('unit', document.getElementById('m-unit').value);
    fd.append('category', document.getElementById('m-cat').value);
    fd.append('opening_balance', document.getElementById('m-open').value);
    fd.append('min_stock_level', document.getElementById('m-min').value);
    fd.append('current_price_per_unit', document.getElementById('m-price').value);
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=save_material', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { closeMaterialModal(); loadMaterials(); }
    else alert('Error: ' + r.message);
});

function openMovementModal() { openMovementModalFor(null); }
function openMovementModalFor(materialId) {
    if (materialId) document.getElementById('mv-material').value = materialId;
    document.getElementById('movement-modal').style.display = 'flex';
}
function closeMovementModal() { document.getElementById('movement-modal').style.display = 'none'; }

document.getElementById('movement-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('material_id', document.getElementById('mv-material').value);
    fd.append('movement_date', document.getElementById('mv-date').value);
    fd.append('movement_type', document.getElementById('mv-type').value);
    fd.append('quantity_kg', document.getElementById('mv-qty').value);
    fd.append('unit_cost', document.getElementById('mv-cost').value);
    fd.append('reference_no', document.getElementById('mv-ref').value);
    fd.append('description', document.getElementById('mv-desc').value);
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=save_movement', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) {
        alert('Movement recorded. New balance: ' + r.new_balance);
        closeMovementModal();
        if (currentTab === 'movements') loadMovements(); else loadMaterials();
    } else alert('Error: ' + r.message);
});

function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

document.addEventListener('DOMContentLoaded', () => {
    if (currentTab === 'movements') { loadMovements(); loadMaterials(); }
    else { loadMaterials(); }
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
