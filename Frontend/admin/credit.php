<?php
/**
 * Admin — Customer Credit (Money Owed to You)
 * Track who bought on credit, how much they owe, and when they pay.
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
$page_title = 'Customer Credit (Owed) - Admin';
include __DIR__ . '/includes/admin_header.php';

$pdo = getDB();
$customers = ($pdo && tableExists($pdo, 'walk_in_customers'))
    ? $pdo->query("SELECT id, customer_name, phone, customer_type FROM walk_in_customers ORDER BY customer_name")->fetchAll(PDO::FETCH_ASSOC)
    : [];
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Customer Credit (Money Owed)</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Track customers who took goods on credit. See who owes you, what's overdue, and record payments when they pay.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/Backend/api/export.php?module=credit" class="btn btn-outline"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
        <button class="btn btn-primary" onclick="openCreditModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Record Credit Sale</button>
    </div>
</div>

<!-- Summary -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
    <div class="stat-card"><div class="stat-card-info"><small>Total Owed to You</small><strong id="cr-total-owed" style="color:#dc2626;">KES 0</strong></div><div class="stat-card-icon" style="background:#fee2e2;color:#dc2626;"><i data-lucide="hand-coins" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Overdue (Past Due Date)</small><strong id="cr-overdue" style="color:#dc2626;">KES 0</strong></div><div class="stat-card-icon" style="background:#fee2e2;color:#dc2626;"><i data-lucide="alert-triangle" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Active Customers w/ Credit</small><strong id="cr-active">0</strong></div><div class="stat-card-icon info"><i data-lucide="users" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Collected This Month</small><strong id="cr-collected" style="color:#16a34a;">KES 0</strong></div><div class="stat-card-icon" style="background:#dcfce7;color:#16a34a;"><i data-lucide="check-circle" style="width:22px;height:22px;"></i></div></div>
</div>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">All Credit Sales</h3>
        <select class="admin-form-control" id="cr-filter-status" onchange="loadCredit()" style="max-width:180px;">
            <option value="">All</option>
            <option value="unpaid">Not Paid</option>
            <option value="partial">Partly Paid</option>
            <option value="paid">Fully Paid</option>
            <option value="overdue">Overdue</option>
        </select>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr>
                <th>Date</th><th>Customer</th><th>Phone</th>
                <th>What They Took</th><th>Total</th>
                <th>Paid</th><th>Balance</th>
                <th>Due Date</th><th>Status</th><th>Actions</th>
            </tr></thead>
            <tbody id="cr-body">
                <tr><td colspan="10" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- New Credit Modal -->
<div id="credit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;overflow-y:auto;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:520px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;margin:20px;">
        <h3 style="margin:0 0 8px;font-family:'Outfit',sans-serif;">New Credit Sale</h3>
        <p style="margin:0 0 18px;color:#64748b;font-size:0.85rem;">Customer takes goods now and promises to pay later. Always set a due date!</p>
        <form id="credit-form">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" id="cr-date" required value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Due Date</label><input class="admin-form-control" type="date" id="cr-due" value="<?= date('Y-m-d', strtotime('+14 days')) ?>"></div>
                <div class="admin-form-group" style="grid-column:span 2">
                    <label class="admin-form-label">Existing Customer</label>
                    <select class="admin-form-control" id="cr-cust" onchange="fillCust()">
                        <option value="">— New customer —</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" data-name="<?= htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') ?>" data-phone="<?= htmlspecialchars($c['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Customer Name *</label><input class="admin-form-control" id="cr-name" required></div>
                <div class="admin-form-group"><label class="admin-form-label">Phone</label><input class="admin-form-control" id="cr-phone"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">What they took *</label><input class="admin-form-control" id="cr-item" required placeholder="e.g. 10 crates eggs, 2 bags feed"></div>
                <div class="admin-form-group"><label class="admin-form-label">Total (KES) *</label><input class="admin-form-control" type="number" step="0.01" id="cr-total" min="0.01" required></div>
                <div class="admin-form-group"><label class="admin-form-label">Paid Now (KES)</label><input class="admin-form-control" type="number" step="0.01" id="cr-paid" value="0" min="0"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Note</label><input class="admin-form-control" id="cr-notes" placeholder="Optional"></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('credit-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Payment Modal -->
<div id="pay-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:420px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 8px;font-family:'Outfit',sans-serif;color:#16a34a;">Record Payment</h3>
        <p style="margin:0 0 18px;color:#64748b;font-size:0.85rem;">Customer is paying back their credit.</p>
        <form id="pay-form">
            <input type="hidden" id="pay-credit-id">
            <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" id="pay-date" required value="<?= date('Y-m-d') ?>"></div>
            <div class="admin-form-group"><label class="admin-form-label">Amount (KES) *</label><input class="admin-form-control" type="number" step="0.01" id="pay-amount" min="0.01" required></div>
            <div class="admin-form-group"><label class="admin-form-label">How Paid</label>
                <select class="admin-form-control" id="pay-method">
                    <option value="cash">Cash</option>
                    <option value="mpesa">M-Pesa</option>
                    <option value="bank">Bank</option>
                </select>
            </div>
            <div class="admin-form-group"><label class="admin-form-label">Reference (e.g. M-Pesa code)</label><input class="admin-form-control" id="pay-ref"></div>
            <div class="admin-form-group"><label class="admin-form-label">Note</label><input class="admin-form-control" id="pay-notes"></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('pay-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-success" style="flex:1;"><i data-lucide="check" style="width:15px;height:15px;"></i> Record Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
function fillCust() {
    const sel = document.getElementById('cr-cust');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        document.getElementById('cr-name').value = opt.dataset.name || '';
        document.getElementById('cr-phone').value = opt.dataset.phone || '';
    }
}

function openCreditModal() { document.getElementById('credit-modal').style.display = 'flex'; }

document.getElementById('credit-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('customer_id', document.getElementById('cr-cust').value);
    fd.append('customer_name', document.getElementById('cr-name').value);
    fd.append('customer_phone', document.getElementById('cr-phone').value);
    fd.append('credit_date', document.getElementById('cr-date').value);
    fd.append('due_date', document.getElementById('cr-due').value);
    fd.append('item_description', document.getElementById('cr-item').value);
    fd.append('total_amount', document.getElementById('cr-total').value);
    fd.append('amount_paid', document.getElementById('cr-paid').value);
    fd.append('notes', document.getElementById('cr-notes').value);
    const res = await fetch('/Backend/api/admin_business.php?action=add_credit', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { alert('Credit sale recorded. Balance: KES ' + r.balance.toFixed(2)); document.getElementById('credit-modal').style.display='none'; document.getElementById('credit-form').reset(); document.getElementById('cr-date').value = new Date().toISOString().split('T')[0]; loadCredit(); }
    else alert('Error: ' + r.message);
});

function openPayModal(id) {
    document.getElementById('pay-credit-id').value = id;
    document.getElementById('pay-modal').style.display = 'flex';
}

document.getElementById('pay-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('credit_id', document.getElementById('pay-credit-id').value);
    fd.append('payment_date', document.getElementById('pay-date').value);
    fd.append('amount', document.getElementById('pay-amount').value);
    fd.append('paid_through', document.getElementById('pay-method').value);
    fd.append('reference_no', document.getElementById('pay-ref').value);
    fd.append('notes', document.getElementById('pay-notes').value);
    const res = await fetch('/Backend/api/admin_business.php?action=add_credit_payment', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { alert('Payment recorded'); document.getElementById('pay-modal').style.display='none'; document.getElementById('pay-form').reset(); document.getElementById('pay-date').value = new Date().toISOString().split('T')[0]; loadCredit(); }
    else alert('Error: ' + r.message);
});

function updateKpis(data) {
    const today = new Date().toISOString().split('T')[0];
    const totalOwed = data.filter(c => c.status!=='paid').reduce((s,c) => s + parseFloat(c.balance), 0);
    const overdue = data.filter(c => c.status!=='paid' && c.due_date && c.due_date < today).reduce((s,c) => s + parseFloat(c.balance), 0);
    const activeCustomers = new Set(data.filter(c => c.status!=='paid').map(c => c.customer_name)).size;
    const monthStart = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
    const collectedMonth = data.filter(c => c.last_payment_date && c.last_payment_date >= monthStart).reduce((s,c) => s + parseFloat(c.amount_paid), 0);
    document.getElementById('cr-total-owed').textContent = 'KES ' + totalOwed.toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
    document.getElementById('cr-overdue').textContent = 'KES ' + overdue.toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
    document.getElementById('cr-active').textContent = activeCustomers;
    document.getElementById('cr-collected').textContent = 'KES ' + collectedMonth.toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
}

async function loadCredit() {
    const status = document.getElementById('cr-filter-status').value;
    let url = '/Backend/api/admin_business.php?action=list_credit';
    if (status) url += '&status=' + status;
    const tbody = document.getElementById('cr-body');
    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch(url);
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        const data = r.data || [];
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:28px;color:#94a3b8;">No credit sales yet.</td></tr>'; updateKpis([]); return; }
        const today = new Date().toISOString().split('T')[0];
        tbody.innerHTML = data.map(c => {
            const isOverdue = c.status !== 'paid' && c.due_date && c.due_date < today;
            const statusBadge = c.status==='paid' ? 'badge-pill-success' : (isOverdue ? 'badge-pill-danger' : (c.status==='partial' ? 'badge-pill-warning' : 'badge-pill-info'));
            const statusTxt = c.status==='paid' ? 'Paid' : (isOverdue ? 'OVERDUE' : (c.status==='partial' ? 'Partly Paid' : 'Not Paid'));
            return `<tr>
                <td>${c.credit_date}</td>
                <td><strong>${escapeHtml(c.customer_name)}</strong></td>
                <td>${escapeHtml(c.customer_phone||'—')}</td>
                <td>${escapeHtml(c.item_description)}</td>
                <td>KES ${parseFloat(c.total_amount).toFixed(2)}</td>
                <td style="color:#16a34a;">KES ${parseFloat(c.amount_paid).toFixed(2)}</td>
                <td><strong style="color:${c.balance>0?'#dc2626':'#16a34a'};">KES ${parseFloat(c.balance).toFixed(2)}</strong></td>
                <td>${c.due_date||'—'}</td>
                <td><span class="badge-pill ${statusBadge}">${statusTxt}</span></td>
                <td>
                    ${c.status!=='paid' ? `<button class="btn btn-success btn-sm" onclick="openPayModal(${c.id})"><i data-lucide="hand-coins" style="width:13px;height:13px;"></i> Pay</button>` : ''}
                </td>
            </tr>`;
        }).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
        // Summary
        const totalOwed = data.filter(c => c.status!=='paid').reduce((s,c) => s + parseFloat(c.balance), 0);
        const overdue = data.filter(c => c.status!=='paid' && c.due_date && c.due_date < today).reduce((s,c) => s + parseFloat(c.balance), 0);
        const activeCustomers = new Set(data.filter(c => c.status!=='paid').map(c => c.customer_name)).size;
        const monthStart = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
        const collectedMonth = data.filter(c => c.last_payment_date && c.last_payment_date >= monthStart).reduce((s,c) => s + parseFloat(c.amount_paid), 0);
        document.getElementById('cr-total-owed').textContent = 'KES ' + totalOwed.toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
        document.getElementById('cr-overdue').textContent = 'KES ' + overdue.toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
        document.getElementById('cr-active').textContent = activeCustomers;
        document.getElementById('cr-collected').textContent = 'KES ' + collectedMonth.toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
    } catch (e) { tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;color:#dc2626;">Network error</td></tr>'; }
}

function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

document.addEventListener('DOMContentLoaded', () => { loadCredit(); if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
