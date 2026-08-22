<?php
/**
 * Admin — Bulk Sales / Walk-in Customers (Selling Point)
 * For customers who buy at the farm gate, from agents, or via phone orders
 * Tracks: customer info, product sold, quantity, price, payment status
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
$page_title = 'Bulk Sales & Walk-in - Admin';
include __DIR__ . '/includes/admin_header.php';

$pdo = getDB();
$tab = $_GET['tab'] ?? 'sales';
$validTabs = ['sales','customers'];
if (!in_array($tab, $validTabs, true)) $tab = 'sales';

$customers = $pdo ? $pdo->query("SELECT * FROM walk_in_customers ORDER BY customer_name")->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Bulk Sales & Walk-in</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Record sales at the farm gate, from agents, or via phone. Tracks customer info and payment status.</p>
    </div>
</div>

<!-- Tab Bar -->
<div style="display:flex;gap:4px;background:#f1f5f9;padding:5px;border-radius:10px;margin-bottom:24px;overflow-x:auto;">
    <a href="?tab=sales" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;font-weight:600;font-size:0.86rem;<?= $tab==='sales'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>"><i data-lucide="receipt" style="width:15px;height:15px;"></i> Sales</a>
    <a href="?tab=customers" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;font-weight:600;font-size:0.86rem;<?= $tab==='customers'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>"><i data-lucide="users" style="width:15px;height:15px;"></i> Customers</a>
</div>

<?php if ($tab === 'sales'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">All Bulk Sales</h3>
        <div style="display:flex;gap:8px;align-items:center;">
            <input class="admin-form-control" type="date" id="bs-from" onchange="loadBulkSales()" style="max-width:160px;">
            <input class="admin-form-control" type="date" id="bs-to" onchange="loadBulkSales()" style="max-width:160px;">
            <a href="/Backend/api/export.php?module=bulk_sales" class="btn btn-outline" id="bs-export"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
            <button class="btn btn-primary" onclick="openBulkSaleModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> New Sale</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr>
                <th>Date</th><th>Sale No</th><th>Customer</th><th>Product</th>
                <th>Qty</th><th>Unit Price</th><th>Total</th>
                <th>Paid</th><th>Balance</th><th>Status</th><th>Actions</th>
            </tr></thead>
            <tbody id="bs-body">
                <tr><td colspan="11" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php else: /* customers */ ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Walk-in Customers</h3>
        <div style="display:flex;gap:8px;">
            <a href="/Backend/api/export.php?module=customers" class="btn btn-outline"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
            <button class="btn btn-primary" onclick="openCustomerModal()"><i data-lucide="plus" style="width:15px;height:15px;"></i> Add Customer</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Name</th><th>Phone</th><th>Type</th><th>Address</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($customers)): ?>
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">No customers yet.</td></tr>
            <?php else: foreach ($customers as $c): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($c['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge-pill badge-pill-info"><?= htmlspecialchars($c['customer_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($c['address'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(substr($c['created_at'], 0, 10), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <button class="btn btn-trans btn-sm" onclick='openCustomerModal(<?= json_encode($c) ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i></button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Bulk Sale Modal -->
<div id="bs-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;overflow-y:auto;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:640px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;margin:20px;">
        <h3 id="bs-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">New Bulk Sale</h3>
        <form id="bs-form">
            <input type="hidden" id="bs-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" id="bs-date" required value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Existing Customer</label>
                    <select class="admin-form-control" id="bs-customer" onchange="fillCustomer()">
                        <option value="">— New customer or quick sale —</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" data-name="<?= htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') ?>" data-phone="<?= htmlspecialchars($c['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Customer Name *</label><input class="admin-form-control" id="bs-name" required placeholder="Walk-in customer name"></div>
                <div class="admin-form-group"><label class="admin-form-label">Phone</label><input class="admin-form-control" id="bs-phone" placeholder="07XX XXX XXX"></div>
                <div class="admin-form-group"><label class="admin-form-label">Product *</label>
                    <select class="admin-form-control" id="bs-product" required>
                        <option value="eggs">Eggs (crates)</option>
                        <option value="broiler">Broiler (kg)</option>
                        <option value="kienyeji">Kienyeji (kg)</option>
                        <option value="manure">Manure (bag)</option>
                        <option value="feed">Feed (bag)</option>
                        <option value="chicks">Chicks (piece)</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Quantity *</label><input class="admin-form-control" type="number" step="0.01" id="bs-qty" required min="0.01"></div>
                <div class="admin-form-group"><label class="admin-form-label">Unit</label>
                    <select class="admin-form-control" id="bs-unit">
                        <option value="crate">Crate</option>
                        <option value="kg">kg</option>
                        <option value="bag">Bag</option>
                        <option value="piece">Piece</option>
                        <option value="litre">Litre</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Unit Price (KES) *</label><input class="admin-form-control" type="number" step="0.01" id="bs-price" required min="0.01"></div>
                <div class="admin-form-group"><label class="admin-form-label">Amount Paid (KES)</label><input class="admin-form-control" type="number" step="0.01" id="bs-paid" value="0"></div>
                <div class="admin-form-group"><label class="admin-form-label">Payment Method</label>
                    <select class="admin-form-control" id="bs-method">
                        <option value="cash">Cash</option>
                        <option value="mpesa">M-Pesa</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="credit">Credit (Pay Later)</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Payment Status</label>
                    <select class="admin-form-control" id="bs-status">
                        <option value="paid">Paid</option>
                        <option value="partial">Partial</option>
                        <option value="pending">Pending</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" id="bs-notes" rows="2"></textarea></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeBulkSaleModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Record Sale</button>
            </div>
        </form>
    </div>
</div>

<!-- Customer Modal -->
<div id="cust-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 id="cust-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add Customer</h3>
        <form id="cust-form">
            <input type="hidden" id="c-id">
            <div class="admin-form-group"><label class="admin-form-label">Customer Name *</label><input class="admin-form-control" id="c-name" required></div>
            <div class="admin-form-group"><label class="admin-form-label">Phone</label><input class="admin-form-control" id="c-phone"></div>
            <div class="admin-form-group"><label class="admin-form-label">Type</label>
                <select class="admin-form-control" id="c-type">
                    <option value="retail">Retail</option>
                    <option value="wholesale">Wholesale</option>
                    <option value="institution">Institution</option>
                    <option value="agent">Agent</option>
                </select>
            </div>
            <div class="admin-form-group"><label class="admin-form-label">Address</label><input class="admin-form-control" id="c-addr"></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeCustomerModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openBulkSaleModal(d) {
    document.getElementById('bs-modal-title').textContent = d?.id ? 'Edit Sale' : 'New Bulk Sale';
    document.getElementById('bs-id').value = d?.id || '';
    document.getElementById('bs-date').value = d?.sale_date || new Date().toISOString().split('T')[0];
    document.getElementById('bs-customer').value = d?.customer_id || '';
    document.getElementById('bs-name').value = d?.customer_name || '';
    document.getElementById('bs-phone').value = d?.customer_phone || '';
    document.getElementById('bs-product').value = d?.product_type || 'eggs';
    document.getElementById('bs-qty').value = d?.quantity || '';
    document.getElementById('bs-unit').value = d?.unit || 'crate';
    document.getElementById('bs-price').value = d?.unit_price || '';
    document.getElementById('bs-paid').value = d?.amount_paid || 0;
    document.getElementById('bs-method').value = d?.payment_method || 'cash';
    document.getElementById('bs-status').value = d?.payment_status || 'paid';
    document.getElementById('bs-notes').value = d?.notes || '';
    document.getElementById('bs-modal').style.display = 'flex';
}
function closeBulkSaleModal() { document.getElementById('bs-modal').style.display = 'none'; }

function fillCustomer() {
    const sel = document.getElementById('bs-customer');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        document.getElementById('bs-name').value = opt.dataset.name || '';
        document.getElementById('bs-phone').value = opt.dataset.phone || '';
    }
}

document.getElementById('bs-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('id', document.getElementById('bs-id').value);
    fd.append('sale_date', document.getElementById('bs-date').value);
    fd.append('customer_id', document.getElementById('bs-customer').value);
    fd.append('customer_name', document.getElementById('bs-name').value);
    fd.append('customer_phone', document.getElementById('bs-phone').value);
    fd.append('product_type', document.getElementById('bs-product').value);
    fd.append('quantity', document.getElementById('bs-qty').value);
    fd.append('unit', document.getElementById('bs-unit').value);
    fd.append('unit_price', document.getElementById('bs-price').value);
    fd.append('amount_paid', document.getElementById('bs-paid').value);
    fd.append('payment_method', document.getElementById('bs-method').value);
    fd.append('payment_status', document.getElementById('bs-status').value);
    fd.append('notes', document.getElementById('bs-notes').value);
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=save_bulk_sale', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { alert('Sale recorded. Total: KES ' + r.total.toFixed(2) + ', Balance: KES ' + r.balance.toFixed(2)); closeBulkSaleModal(); loadBulkSales(); }
    else alert('Error: ' + r.message);
});

async function loadBulkSales() {
    const tbody = document.getElementById('bs-body');
    if (!tbody) return;
    const from = document.getElementById('bs-from')?.value || '';
    const to = document.getElementById('bs-to')?.value || '';
    let url = '/Backend/api/admin_poultry_v2.php?action=get_bulk_sales';
    if (from) url += '&from=' + from;
    if (to) url += '&to=' + to;
    tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch(url);
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        const data = r.data || [];
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:28px;color:#94a3b8;">No sales yet.</td></tr>'; return; }
        tbody.innerHTML = data.map(s => {
            const statusBadge = s.payment_status==='paid'?'badge-pill-success':(s.payment_status==='partial'?'badge-pill-warning':(s.payment_status==='pending'?'badge-pill-info':'badge-pill-danger'));
            return `<tr>
                <td>${s.sale_date}</td>
                <td><code style="background:#f1f5f9;padding:2px 8px;border-radius:4px;">${s.sale_number}</code></td>
                <td><strong>${escapeHtml(s.customer_name)}</strong>${s.customer_phone?'<br><small style="color:#64748b;">'+s.customer_phone+'</small>':''}</td>
                <td><span class="badge-pill badge-pill-info">${s.product_type}</span></td>
                <td>${parseFloat(s.quantity).toFixed(2)} ${s.unit}</td>
                <td>KES ${parseFloat(s.unit_price).toFixed(2)}</td>
                <td><strong>KES ${parseFloat(s.total_amount).toFixed(2)}</strong></td>
                <td>KES ${parseFloat(s.amount_paid).toFixed(2)}</td>
                <td style="color:${s.balance>0?'#dc2626':'#16a34a'};">KES ${parseFloat(s.balance).toFixed(2)}</td>
                <td><span class="badge-pill ${statusBadge}">${s.payment_status}</span><br><small style="color:#64748b;">${s.payment_method}</small></td>
                <td>
                    <button class="btn btn-trans btn-sm" onclick='openBulkSaleModal(${JSON.stringify(s)})'><i data-lucide="pencil" style="width:13px;height:13px;"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="deleteBulkSale(${s.id})"><i data-lucide="trash-2" style="width:13px;height:13px;"></i></button>
                </td>
            </tr>`;
        }).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;color:#dc2626;">Network error</td></tr>';
    }
}

async function deleteBulkSale(id) {
    if (!confirm('Delete this sale?')) return;
    const fd = new FormData(); fd.append('id', id);
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=delete_bulk_sale', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) loadBulkSales(); else alert(r.message);
}

function openCustomerModal(d) {
    document.getElementById('cust-modal-title').textContent = d?.id ? 'Edit Customer' : 'Add Customer';
    document.getElementById('c-id').value = d?.id || '';
    document.getElementById('c-name').value = d?.customer_name || '';
    document.getElementById('c-phone').value = d?.phone || '';
    document.getElementById('c-type').value = d?.customer_type || 'retail';
    document.getElementById('c-addr').value = d?.address || '';
    document.getElementById('cust-modal').style.display = 'flex';
}
function closeCustomerModal() { document.getElementById('cust-modal').style.display = 'none'; }

document.getElementById('cust-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('id', document.getElementById('c-id').value);
    fd.append('customer_name', document.getElementById('c-name').value);
    fd.append('phone', document.getElementById('c-phone').value);
    fd.append('customer_type', document.getElementById('c-type').value);
    fd.append('address', document.getElementById('c-addr').value);
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=save_walkin_customer', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) location.reload();
    else alert('Error: ' + r.message);
});

function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

document.addEventListener('DOMContentLoaded', () => {
    loadBulkSales();
    if (typeof lucide !== 'undefined') lucide.createIcons();
    const exp = document.getElementById('bs-export');
    if (exp) {
        const update = () => {
            const f = document.getElementById('bs-from')?.value || '';
            const t = document.getElementById('bs-to')?.value || '';
            exp.href = '/Backend/api/export.php?module=bulk_sales' + (f?'&from='+f:'') + (t?'&to='+t:'');
        };
        document.getElementById('bs-from')?.addEventListener('change', update);
        document.getElementById('bs-to')?.addEventListener('change', update);
    }
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
