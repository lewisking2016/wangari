<?php
/**
 * Admin — Costs & Profit
 * Track money spent per batch, see if you're making money.
 * Simple language, no jargon.
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
$page_title = 'Costs & Profit - Admin';
include __DIR__ . '/includes/admin_header.php';

$pdo = getDB();
$batches = $pdo ? $pdo->query("SELECT id, batch_name, batch_code, current_birds, status FROM batches WHERE status IN ('active','completed','sold') ORDER BY placement_date DESC")->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Costs & Profit</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">See how much each batch cost and whether you made money. Track chicks, feed, drugs, and labour.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/Backend/api/export.php?module=batch_costs" class="btn btn-outline"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
        <button class="btn btn-primary" onclick="openCostModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Add Cost</button>
    </div>
</div>

<!-- Quick stats -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
    <div class="stat-card"><div class="stat-card-info"><small>Total Spent (All Batches)</small><strong id="cp-total-spent">—</strong></div><div class="stat-card-icon" style="background:#fee2e2;color:#dc2626;"><i data-lucide="trending-down" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Spent on Feed</small><strong id="cp-feed-cost">—</strong></div><div class="stat-card-icon accent"><i data-lucide="wheat" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Spent on Chicks</small><strong id="cp-chick-cost">—</strong></div><div class="stat-card-icon info"><i data-lucide="egg" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Spent on Drugs</small><strong id="cp-drug-cost">—</strong></div><div class="stat-card-icon" style="background:#fef3c7;color:#d97706;"><i data-lucide="pill" style="width:22px;height:22px;"></i></div></div>
</div>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">All Costs</h3>
        <div style="display:flex;gap:8px;align-items:center;">
            <select class="admin-form-control" id="cp-filter-batch" onchange="loadCosts()" style="max-width:240px;">
                <option value="">All batches</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['batch_name'] . ' (' . $b['batch_code'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <input class="admin-form-control" type="date" id="cp-from" onchange="loadCosts()" style="max-width:160px;">
            <input class="admin-form-control" type="date" id="cp-to" onchange="loadCosts()" style="max-width:160px;">
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr>
                <th>Date</th><th>Batch</th><th>What</th>
                <th>Quantity</th><th>Unit Cost</th><th>Total</th>
                <th>Paid From</th><th>Notes</th><th>Actions</th>
            </tr></thead>
            <tbody id="costs-body">
                <tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Cost Modal -->
<div id="cost-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;overflow-y:auto;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:560px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;margin:20px;">
        <h3 id="cost-modal-title" style="margin:0 0 8px;font-family:'Outfit',sans-serif;">Add Cost</h3>
        <p style="margin:0 0 18px;color:#64748b;font-size:0.85rem;">Record money you spent on a batch. Be specific — it helps you see what's eating your profit.</p>
        <form id="cost-form">
            <input type="hidden" id="c-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group">
                    <label class="admin-form-label">Batch *</label>
                    <select class="admin-form-control" id="c-batch" required>
                        <option value="">Choose batch...</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['batch_name'] . ' (' . $b['batch_code'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Date *</label>
                    <input class="admin-form-control" type="date" id="c-date" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">What did you spend on? *</label>
                    <select class="admin-form-control" id="c-type" required>
                        <option value="chick_purchase">Buy chicks (DOC)</option>
                        <option value="feed">Buy feed</option>
                        <option value="drugs_vaccines">Drugs / vaccines</option>
                        <option value="labour">Labour / wages</option>
                        <option value="utilities">Water / electricity</option>
                        <option value="transport">Transport</option>
                        <option value="packaging">Packaging / trays</option>
                        <option value="misc">Other</option>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">How much? (KES) *</label>
                    <input class="admin-form-control" type="number" step="0.01" id="c-total" min="0.01" required placeholder="0.00">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Quantity</label>
                    <input class="admin-form-control" type="number" step="0.001" id="c-qty" value="1">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Unit (e.g. kg, bag)</label>
                    <input class="admin-form-control" id="c-unit" placeholder="kg">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Paid from</label>
                    <select class="admin-form-control" id="c-paid">
                        <option value="cash">Cash</option>
                        <option value="mpesa">M-Pesa</option>
                        <option value="bank">Bank</option>
                        <option value="credit">Credit (pay later)</option>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Reference (e.g. receipt #)</label>
                    <input class="admin-form-control" id="c-ref" placeholder="Receipt no.">
                </div>
                <div class="admin-form-group" style="grid-column:span 2">
                    <label class="admin-form-label">Note</label>
                    <input class="admin-form-control" id="c-desc" placeholder="e.g. 70 bags layers mash from Fred">
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeCostModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Cost</button>
            </div>
        </form>
    </div>
</div>

<script>
const costTypes = {
    chick_purchase: 'Chicks',
    feed: 'Feed',
    drugs_vaccines: 'Drugs',
    labour: 'Labour',
    utilities: 'Utilities',
    transport: 'Transport',
    packaging: 'Packaging',
    misc: 'Other'
};

function openCostModal(d) {
    document.getElementById('cost-modal-title').textContent = d?.id ? 'Edit Cost' : 'Add Cost';
    document.getElementById('c-id').value = d?.id || '';
    document.getElementById('c-batch').value = d?.batch_id || '';
    document.getElementById('c-date').value = d?.cost_date || new Date().toISOString().split('T')[0];
    document.getElementById('c-type').value = d?.cost_type || 'feed';
    document.getElementById('c-total').value = d?.total_cost || '';
    document.getElementById('c-qty').value = d?.quantity || 1;
    document.getElementById('c-unit').value = d?.unit || '';
    document.getElementById('c-paid').value = d?.paid_from || 'cash';
    document.getElementById('c-ref').value = d?.reference_no || '';
    document.getElementById('c-desc').value = d?.description || '';
    document.getElementById('cost-modal').style.display = 'flex';
}
function closeCostModal() { document.getElementById('cost-modal').style.display = 'none'; }

document.getElementById('cost-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('id', document.getElementById('c-id').value);
    fd.append('batch_id', document.getElementById('c-batch').value);
    fd.append('cost_date', document.getElementById('c-date').value);
    fd.append('cost_type', document.getElementById('c-type').value);
    fd.append('total_cost', document.getElementById('c-total').value);
    fd.append('quantity', document.getElementById('c-qty').value);
    fd.append('unit', document.getElementById('c-unit').value);
    fd.append('paid_from', document.getElementById('c-paid').value);
    fd.append('reference_no', document.getElementById('c-ref').value);
    fd.append('description', document.getElementById('c-desc').value);
    const res = await fetch('/Backend/api/admin_business.php?action=add_cost', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { closeCostModal(); loadCosts(); }
    else alert('Error: ' + r.message);
});

async function loadCosts() {
    const batch = document.getElementById('cp-filter-batch').value;
    const from = document.getElementById('cp-from').value;
    const to = document.getElementById('cp-to').value;
    let url = '/Backend/api/admin_business.php?action=list_costs';
    if (batch) url += '&batch_id=' + batch;
    if (from) url += '&from=' + from;
    if (to) url += '&to=' + to;
    const tbody = document.getElementById('costs-body');
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch(url);
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        const data = r.data || [];
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8;">No costs recorded yet.</td></tr>'; updateKpis([]); return; }
        tbody.innerHTML = data.map(c => `<tr>
            <td>${c.cost_date}</td>
            <td><strong>${escapeHtml(c.batch_name||'—')}</strong><br><small style="color:#64748b;">${escapeHtml(c.batch_code||'')}</small></td>
            <td><span class="badge-pill badge-pill-info">${costTypes[c.cost_type]||c.cost_type}</span><br><small style="color:#64748b;">${escapeHtml(c.description||'')}</small></td>
            <td>${parseFloat(c.quantity||0).toFixed(2)} ${escapeHtml(c.unit||'')}</td>
            <td>KES ${parseFloat(c.unit_cost||0).toFixed(2)}</td>
            <td><strong>KES ${parseFloat(c.total_cost).toFixed(2)}</strong></td>
            <td>${c.paid_from}</td>
            <td>${escapeHtml(c.reference_no||'')}</td>
            <td>
                <button class="btn btn-trans btn-sm" onclick='openCostModal(${JSON.stringify(c)})'><i data-lucide="pencil" style="width:13px;height:13px;"></i></button>
                <button class="btn btn-danger btn-sm" onclick="deleteCost(${c.id})"><i data-lucide="trash-2" style="width:13px;height:13px;"></i></button>
            </td>
        </tr>`).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
        updateKpis(data);
    } catch (e) { tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#dc2626;">Network error</td></tr>'; }
}

function updateKpis(data) {
    const total = data.reduce((s,c) => s + parseFloat(c.total_cost||0), 0);
    const feed = data.filter(c => c.cost_type==='feed').reduce((s,c) => s + parseFloat(c.total_cost||0), 0);
    const chick = data.filter(c => c.cost_type==='chick_purchase').reduce((s,c) => s + parseFloat(c.total_cost||0), 0);
    const drug = data.filter(c => c.cost_type==='drugs_vaccines').reduce((s,c) => s + parseFloat(c.total_cost||0), 0);
    document.getElementById('cp-total-spent').textContent = 'KES ' + total.toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
    document.getElementById('cp-feed-cost').textContent = 'KES ' + feed.toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
    document.getElementById('cp-chick-cost').textContent = 'KES ' + chick.toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
    document.getElementById('cp-drug-cost').textContent = 'KES ' + drug.toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
}

async function deleteCost(id) {
    if (!confirm('Delete this cost record?')) return;
    const fd = new FormData(); fd.append('id', id);
    const res = await fetch('/Backend/api/admin_business.php?action=delete_cost', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) loadCosts(); else alert(r.message);
}

function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

document.addEventListener('DOMContentLoaded', () => { loadCosts(); if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
