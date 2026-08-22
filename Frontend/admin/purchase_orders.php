<?php
/**
 * Admin — Procurement / Purchase Orders
 * Order raw materials from suppliers, receive them, auto-update stock.
 * Simple flow: Create → Send → Receive (stock auto-added)
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
$page_title = 'Procurement (Purchase Orders) - Admin';
include __DIR__ . '/includes/admin_header.php';

$pdo = getDB();
$tab = $_GET['tab'] ?? 'orders';
$validTabs = ['orders','suppliers'];
if (!in_array($tab, $validTabs, true)) $tab = 'orders';
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Procurement (Buy Raw Materials)</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Order feed ingredients from suppliers. When goods arrive, mark them received — stock updates automatically.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/Backend/api/export.php?module=purchase_orders" class="btn btn-outline"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
        <button class="btn btn-primary" onclick="openPoModal()"><i data-lucide="plus" style="width:16px;height:16px;"></i> New Order</button>
    </div>
</div>

<!-- Tab Bar -->
<div style="display:flex;gap:4px;background:#f1f5f9;padding:5px;border-radius:10px;margin-bottom:24px;">
    <a href="?tab=orders" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;font-weight:600;font-size:0.86rem;<?= $tab==='orders'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>"><i data-lucide="truck" style="width:15px;height:15px;"></i> Purchase Orders</a>
    <a href="?tab=suppliers" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;font-weight:600;font-size:0.86rem;<?= $tab==='suppliers'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>"><i data-lucide="users" style="width:15px;height:15px;"></i> Suppliers</a>
</div>

<?php if ($tab === 'orders'): ?>
<div class="admin-card">
    <h3 style="margin:0 0 18px;font-family:'Outfit',sans-serif;font-size:1.1rem;">All Purchase Orders</h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr>
                <th>PO Number</th><th>Supplier</th><th>Date</th>
                <th>Expected</th><th>Items</th><th>Total</th>
                <th>Status</th><th>Actions</th>
            </tr></thead>
            <tbody id="po-body">
                <tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php else: /* suppliers */ ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Suppliers</h3>
        <button class="btn btn-primary" onclick="openSupplierModal()"><i data-lucide="plus" style="width:15px;height:15px;"></i> Add Supplier</button>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Name</th><th>Contact</th><th>Phone</th><th>Email</th><th>Lead Time</th><th>Actions</th></tr></thead>
            <tbody id="sup-body">
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- PO Modal -->
<div id="po-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;overflow-y:auto;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:800px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;margin:20px;">
        <h3 id="po-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">New Purchase Order</h3>
        <form id="po-form">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Order Date *</label><input class="admin-form-control" type="date" id="po-date" required value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Supplier *</label>
                    <select class="admin-form-control" id="po-supplier" required>
                        <option value="">Choose supplier...</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Expected Delivery</label><input class="admin-form-control" type="date" id="po-expected"></div>
            </div>

            <h4 style="margin:20px 0 8px;font-family:'Outfit',sans-serif;font-size:0.95rem;">Items to Order</h4>
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr style="background:#f8fafc;">
                    <th style="text-align:left;padding:8px;font-size:0.82rem;">Material</th>
                    <th style="text-align:left;padding:8px;font-size:0.82rem;">Quantity</th>
                    <th style="text-align:left;padding:8px;font-size:0.82rem;">Unit</th>
                    <th style="text-align:left;padding:8px;font-size:0.82rem;">Unit Price</th>
                    <th style="text-align:left;padding:8px;font-size:0.82rem;">Line Total</th>
                    <th style="padding:8px;"></th>
                </tr></thead>
                <tbody id="po-lines"></tbody>
            </table>
            <button type="button" class="btn btn-outline btn-sm" style="margin-top:10px;" onclick="addPoLine()"><i data-lucide="plus" style="width:13px;height:13px;"></i> Add Item</button>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:20px;padding:14px;background:#f8fafc;border-radius:8px;">
                <strong style="font-family:'Outfit',sans-serif;font-size:1.05rem;">Total:</strong>
                <strong id="po-total-display" style="font-size:1.4rem;color:var(--admin-primary);">KES 0.00</strong>
            </div>

            <div class="admin-form-group" style="margin-top:14px;"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" id="po-notes" rows="2"></textarea></div>

            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('po-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Order</button>
            </div>
        </form>
    </div>
</div>

<!-- Supplier Modal -->
<div id="supplier-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 id="supplier-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add Supplier</h3>
        <form id="supplier-form">
            <input type="hidden" id="sup-id">
            <div class="admin-form-group"><label class="admin-form-label">Supplier Name *</label><input class="admin-form-control" id="sup-name" required></div>
            <div class="admin-form-group"><label class="admin-form-label">Contact Person</label><input class="admin-form-control" id="sup-contact"></div>
            <div class="admin-form-group"><label class="admin-form-label">Phone</label><input class="admin-form-control" id="sup-phone"></div>
            <div class="admin-form-group"><label class="admin-form-label">Email</label><input class="admin-form-control" id="sup-email"></div>
            <div class="admin-form-group"><label class="admin-form-label">Address</label><input class="admin-form-control" id="sup-addr"></div>
            <div class="admin-form-group"><label class="admin-form-label">Lead Time (days)</label><input class="admin-form-control" type="number" id="sup-lead" min="0" value="5"></div>
            <div class="admin-form-group"><label class="admin-form-label">Notes</label><input class="admin-form-control" id="sup-notes"></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('supplier-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
const currentTab = '<?= $tab ?>';
let materials = [];
let poIdx = 0;
let suppList = [];

async function loadSuppliers() {
    const res = await fetch('/Backend/api/admin_business.php?action=list_suppliers');
    const r = await res.json();
    suppList = r.data || [];
    const sel = document.getElementById('po-supplier');
    if (sel) sel.innerHTML = '<option value="">Choose supplier...</option>' + suppList.map(s => `<option value="${s.id}">${escapeHtml(s.supplier_name)}</option>`).join('');
}

async function loadMaterials() {
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=get_materials');
    const r = await res.json();
    materials = r.data || [];
}

function addPoLine(data = null) {
    const idx = poIdx++;
    const tr = document.createElement('tr');
    tr.dataset.idx = idx;
    tr.style.borderBottom = '1px solid #e2e8f0';
    let opts = '<option value="">Choose material...</option>' + materials.map(m => `<option value="${m.id}" data-unit="${m.unit}" data-price="${m.current_price_per_unit}">${escapeHtml(m.material_name)} (${m.current_stock} ${m.unit} in stock)</option>`).join('');
    tr.innerHTML = `
        <td style="padding:6px;"><select class="admin-form-control" data-field="material_id" style="font-size:0.85rem;padding:6px 8px;" onchange="onMaterialChange(this)">${opts}</select></td>
        <td style="padding:6px;"><input class="admin-form-control" type="number" step="0.001" data-field="quantity" value="${data?.quantity||0}" style="font-size:0.85rem;padding:6px 8px;" oninput="recalcPo()"></td>
        <td style="padding:6px;"><input class="admin-form-control" data-field="unit" value="${data?.unit||'kg'}" style="font-size:0.85rem;padding:6px 8px;"></td>
        <td style="padding:6px;"><input class="admin-form-control" type="number" step="0.01" data-field="unit_price" value="${data?.unit_price||0}" style="font-size:0.85rem;padding:6px 8px;" oninput="recalcPo()"></td>
        <td style="padding:6px;font-weight:700;font-size:0.9rem;" class="line-total">KES 0.00</td>
        <td style="padding:6px;"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove();recalcPo();" style="padding:4px 8px;"><i data-lucide="x" style="width:12px;height:12px;"></i></button></td>
    `;
    document.getElementById('po-lines').appendChild(tr);
    if (data) tr.querySelector('[data-field=material_id]').value = data.material_id;
    recalcPo();
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function onMaterialChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    const tr = sel.closest('tr');
    if (opt && opt.value) {
        tr.querySelector('[data-field=unit]').value = opt.dataset.unit || 'kg';
        if (parseFloat(tr.querySelector('[data-field=unit_price]').value) === 0) {
            tr.querySelector('[data-field=unit_price]').value = opt.dataset.price || 0;
        }
        recalcPo();
    }
}

function recalcPo() {
    let total = 0;
    document.querySelectorAll('#po-lines tr').forEach(tr => {
        const q = parseFloat(tr.querySelector('[data-field=quantity]').value || 0);
        const p = parseFloat(tr.querySelector('[data-field=unit_price]').value || 0);
        const lt = q * p;
        tr.querySelector('.line-total').textContent = 'KES ' + lt.toFixed(2);
        total += lt;
    });
    document.getElementById('po-total-display').textContent = 'KES ' + total.toFixed(2);
}

function openPoModal() {
    document.getElementById('po-lines').innerHTML = '';
    poIdx = 0;
    addPoLine(); addPoLine();
    document.getElementById('po-modal').style.display = 'flex';
}

document.getElementById('po-form').addEventListener('submit', async e => {
    e.preventDefault();
    const lines = [];
    document.querySelectorAll('#po-lines tr').forEach(tr => {
        lines.push({
            material_id: tr.querySelector('[data-field=material_id]').value,
            quantity: parseFloat(tr.querySelector('[data-field=quantity]').value || 0),
            unit: tr.querySelector('[data-field=unit]').value,
            unit_price: parseFloat(tr.querySelector('[data-field=unit_price]').value || 0)
        });
    });
    if (!lines.filter(l => l.material_id).length) { alert('Add at least one item with material selected'); return; }
    const fd = new FormData();
    fd.append('order_date', document.getElementById('po-date').value);
    fd.append('supplier_id', document.getElementById('po-supplier').value);
    fd.append('expected_delivery', document.getElementById('po-expected').value);
    fd.append('notes', document.getElementById('po-notes').value);
    fd.append('items', JSON.stringify(lines.filter(l => l.material_id)));
    const res = await fetch('/Backend/api/admin_business.php?action=create_po', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { alert('Purchase order saved'); document.getElementById('po-modal').style.display='none'; loadOrders(); }
    else alert('Error: ' + r.message);
});

async function loadOrders() {
    const tbody = document.getElementById('po-body');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch('/Backend/api/admin_business.php?action=list_purchase_orders');
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        const data = r.data || [];
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">No purchase orders yet.</td></tr>'; return; }
        tbody.innerHTML = data.map(p => {
            const sc = p.status==='received'?'badge-pill-success':(p.status==='cancelled'?'badge-pill-danger':(p.status==='draft'?'badge-pill-info':'badge-pill-warning'));
            return `<tr>
                <td><code style="background:#f1f5f9;padding:2px 8px;border-radius:4px;">${p.po_number}</code></td>
                <td><strong>${escapeHtml(p.supplier_name||'—')}</strong></td>
                <td>${p.order_date}</td>
                <td>${p.expected_delivery||'—'}</td>
                <td>${p.item_count} items</td>
                <td><strong>KES ${parseFloat(p.total_amount).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</strong></td>
                <td><span class="badge-pill ${sc}">${p.status}</span></td>
                <td>
                    ${p.status!=='received'&&p.status!=='cancelled'?`<button class="btn btn-success btn-sm" onclick="receivePo(${p.id})"><i data-lucide="package" style="width:13px;height:13px;"></i> Receive</button>`:''}
                </td>
            </tr>`;
        }).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (e) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#dc2626;">Network error</td></tr>'; }
}

async function receivePo(id) {
    if (!confirm('Mark this order as received? Stock will be updated automatically.')) return;
    const fd = new FormData(); fd.append('id', id);
    const res = await fetch('/Backend/api/admin_business.php?action=receive_po', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { alert('Order received. Stock updated.'); loadOrders(); }
    else alert('Error: ' + r.message);
}

async function loadSuppliersTable() {
    const tbody = document.getElementById('sup-body');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch('/Backend/api/admin_business.php?action=list_suppliers');
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        const data = r.data || [];
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">No suppliers yet.</td></tr>'; return; }
        tbody.innerHTML = data.map(s => `<tr>
            <td><strong>${escapeHtml(s.supplier_name)}</strong></td>
            <td>${escapeHtml(s.contact_name||'—')}</td>
            <td>${escapeHtml(s.phone||'—')}</td>
            <td>${escapeHtml(s.email||'—')}</td>
            <td>${s.lead_time_days} days</td>
            <td>
                <button class="btn btn-trans btn-sm" onclick='openSupplierModal(${JSON.stringify(s)})'><i data-lucide="pencil" style="width:13px;height:13px;"></i></button>
            </td>
        </tr>`).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (e) {}
}

function openSupplierModal(d) {
    document.getElementById('supplier-modal-title').textContent = d?.id ? 'Edit Supplier' : 'Add Supplier';
    document.getElementById('sup-id').value = d?.id || '';
    document.getElementById('sup-name').value = d?.supplier_name || '';
    document.getElementById('sup-contact').value = d?.contact_name || '';
    document.getElementById('sup-phone').value = d?.phone || '';
    document.getElementById('sup-email').value = d?.email || '';
    document.getElementById('sup-addr').value = d?.address || '';
    document.getElementById('sup-lead').value = d?.lead_time_days || 5;
    document.getElementById('sup-notes').value = d?.notes || '';
    document.getElementById('supplier-modal').style.display = 'flex';
}

document.getElementById('supplier-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('id', document.getElementById('sup-id').value);
    fd.append('supplier_name', document.getElementById('sup-name').value);
    fd.append('contact_name', document.getElementById('sup-contact').value);
    fd.append('phone', document.getElementById('sup-phone').value);
    fd.append('email', document.getElementById('sup-email').value);
    fd.append('address', document.getElementById('sup-addr').value);
    fd.append('lead_time_days', document.getElementById('sup-lead').value);
    fd.append('notes', document.getElementById('sup-notes').value);
    const res = await fetch('/Backend/api/admin_business.php?action=save_supplier', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { document.getElementById('supplier-modal').style.display='none'; loadSuppliersTable(); }
    else alert('Error: ' + r.message);
});

function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

document.addEventListener('DOMContentLoaded', () => {
    if (currentTab === 'orders') { loadSuppliers(); loadMaterials(); loadOrders(); }
    if (currentTab === 'suppliers') { loadSuppliersTable(); loadSuppliers(); }
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
