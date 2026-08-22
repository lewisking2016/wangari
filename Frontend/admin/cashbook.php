<?php
/**
 * Admin — Cashbook (Money Book)
 * The simplest possible money tracker. One column for money in,
 * one for money out, with running balance. Like a school notebook.
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
$page_title = 'Cashbook - Admin';
include __DIR__ . '/includes/admin_header.php';
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Cashbook (Money Book)</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Every shilling that comes in and goes out — like writing in a school money book. Always shows your running balance.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/Backend/api/export.php?module=cashbook" class="btn btn-outline" id="cb-export"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
        <button class="btn btn-success" onclick="openMoneyInModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Money In</button>
        <button class="btn btn-danger" onclick="openMoneyOutModal()"><i data-lucide="minus-circle" style="width:16px;height:16px;"></i> Money Out</button>
    </div>
</div>

<!-- Today summary -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
    <div class="stat-card"><div class="stat-card-info"><small>Money In Today</small><strong id="cb-in-today" style="color:#16a34a;">KES 0</strong></div><div class="stat-card-icon" style="background:#dcfce7;color:#16a34a;"><i data-lucide="arrow-down" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Money Out Today</small><strong id="cb-out-today" style="color:#dc2626;">KES 0</strong></div><div class="stat-card-icon" style="background:#fee2e2;color:#dc2626;"><i data-lucide="arrow-up" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Profit Today</small><strong id="cb-profit-today">KES 0</strong></div><div class="stat-card-icon accent"><i data-lucide="trending-up" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Closing Balance</small><strong id="cb-balance">KES 0</strong></div><div class="stat-card-icon info"><i data-lucide="wallet" style="width:22px;height:22px;"></i></div></div>
</div>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">All Entries</h3>
        <div style="display:flex;gap:8px;align-items:center;">
            <input class="admin-form-control" type="date" id="cb-from" onchange="loadCashbook()" style="max-width:160px;">
            <input class="admin-form-control" type="date" id="cb-to" onchange="loadCashbook()" style="max-width:160px;">
            <select class="admin-form-control" id="cb-dir" onchange="loadCashbook()" style="max-width:140px;">
                <option value="">All</option>
                <option value="in">Money In</option>
                <option value="out">Money Out</option>
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr>
                <th>Date</th>
                <th style="text-align:right;color:#16a34a;">Money In</th>
                <th style="text-align:right;color:#dc2626;">Money Out</th>
                <th>What For</th>
                <th>Customer / Supplier</th>
                <th>How Paid</th>
                <th>Balance</th>
                <th>Actions</th>
            </tr></thead>
            <tbody id="cb-body">
                <tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Money In Modal -->
<div id="money-in-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 8px;font-family:'Outfit',sans-serif;color:#16a34a;">Money In</h3>
        <p style="margin:0 0 18px;color:#64748b;font-size:0.85rem;">Money received from sales or other income.</p>
        <form id="money-in-form">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" id="in-date" required value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Amount (KES) *</label><input class="admin-form-control" type="number" step="0.01" id="in-amount" min="0.01" required placeholder="0.00"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">What was it for? *</label>
                    <select class="admin-form-control" id="in-source" required>
                        <option value="egg_sales">Sold eggs</option>
                        <option value="broiler_sales">Sold broilers</option>
                        <option value="chick_sales">Sold chicks (DOC)</option>
                        <option value="feed_sales">Sold feed</option>
                        <option value="raw_material_sales">Sold raw materials (maize, etc.)</option>
                        <option value="online_order">Online order</option>
                        <option value="bulk_sale">Walk-in / bulk sale</option>
                        <option value="credit_payment">Customer paid back credit</option>
                        <option value="loan_in">Loan received</option>
                        <option value="other_in">Other income</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">How was it paid?</label>
                    <select class="admin-form-control" id="in-paid">
                        <option value="cash">Cash</option>
                        <option value="mpesa">M-Pesa</option>
                        <option value="bank">Bank</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Customer name</label><input class="admin-form-control" id="in-customer" placeholder="e.g. John Mwangi"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Reference (e.g. receipt no.)</label><input class="admin-form-control" id="in-ref" placeholder="Receipt #"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Note</label><input class="admin-form-control" id="in-desc" placeholder="Optional"></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('money-in-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-success" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Money Out Modal -->
<div id="money-out-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 8px;font-family:'Outfit',sans-serif;color:#dc2626;">Money Out</h3>
        <p style="margin:0 0 18px;color:#64748b;font-size:0.85rem;">Money paid for expenses, purchases, or anything else.</p>
        <form id="money-out-form">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" id="out-date" required value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Amount (KES) *</label><input class="admin-form-control" type="number" step="0.01" id="out-amount" min="0.01" required placeholder="0.00"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">What was it for? *</label>
                    <select class="admin-form-control" id="out-source" required>
                        <option value="feed_purchase">Bought feed</option>
                        <option value="raw_material_purchase">Bought raw materials (maize, etc.)</option>
                        <option value="drugs_purchase">Bought drugs / vaccines</option>
                        <option value="chick_purchase">Bought chicks (DOC)</option>
                        <option value="labour">Paid wages / labour</option>
                        <option value="transport">Transport</option>
                        <option value="utilities">Water / electricity</option>
                        <option value="rent">Rent</option>
                        <option value="loan_repayment">Loan repayment</option>
                        <option value="other_out">Other expense</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">How was it paid?</label>
                    <select class="admin-form-control" id="out-paid">
                        <option value="cash">Cash</option>
                        <option value="mpesa">M-Pesa</option>
                        <option value="bank">Bank</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Supplier name</label><input class="admin-form-control" id="out-supplier" placeholder="e.g. Fred"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Reference (e.g. receipt no.)</label><input class="admin-form-control" id="out-ref" placeholder="Receipt #"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Note</label><input class="admin-form-control" id="out-desc" placeholder="Optional"></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('money-out-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-danger" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
const sourceLabels = {
    egg_sales: 'Sold eggs', broiler_sales: 'Sold broilers', chick_sales: 'Sold chicks',
    feed_sales: 'Sold feed', raw_material_sales: 'Sold raw materials', online_order: 'Online order',
    bulk_sale: 'Walk-in sale', credit_payment: 'Credit payment', loan_in: 'Loan received', other_in: 'Other',
    feed_purchase: 'Bought feed', raw_material_purchase: 'Bought raw materials',
    drugs_purchase: 'Bought drugs', chick_purchase: 'Bought chicks',
    labour: 'Wages', transport: 'Transport', utilities: 'Utilities', rent: 'Rent',
    loan_repayment: 'Loan repayment', other_out: 'Other expense'
};

function openMoneyInModal() { document.getElementById('money-in-modal').style.display = 'flex'; }
function openMoneyOutModal() { document.getElementById('money-out-modal').style.display = 'flex'; }

document.getElementById('money-in-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('entry_date', document.getElementById('in-date').value);
    fd.append('amount', document.getElementById('in-amount').value);
    fd.append('money_source', document.getElementById('in-source').value);
    fd.append('paid_through', document.getElementById('in-paid').value);
    fd.append('customer_name', document.getElementById('in-customer').value);
    fd.append('reference_no', document.getElementById('in-ref').value);
    fd.append('description', document.getElementById('in-desc').value);
    const res = await fetch('/Backend/api/admin_business.php?action=add_money_in', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { alert('Money in recorded'); document.getElementById('money-in-modal').style.display='none'; document.getElementById('money-in-form').reset(); document.getElementById('in-date').value = new Date().toISOString().split('T')[0]; loadCashbook(); }
    else alert('Error: ' + r.message);
});

document.getElementById('money-out-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('entry_date', document.getElementById('out-date').value);
    fd.append('amount', document.getElementById('out-amount').value);
    fd.append('money_source', document.getElementById('out-source').value);
    fd.append('paid_through', document.getElementById('out-paid').value);
    fd.append('supplier_name', document.getElementById('out-supplier').value);
    fd.append('reference_no', document.getElementById('out-ref').value);
    fd.append('description', document.getElementById('out-desc').value);
    const res = await fetch('/Backend/api/admin_business.php?action=add_money_out', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { alert('Money out recorded'); document.getElementById('money-out-modal').style.display='none'; document.getElementById('money-out-form').reset(); document.getElementById('out-date').value = new Date().toISOString().split('T')[0]; loadCashbook(); }
    else alert('Error: ' + r.message);
});

async function loadCashbook() {
    const from = document.getElementById('cb-from').value;
    const to = document.getElementById('cb-to').value;
    const dir = document.getElementById('cb-dir').value;
    let url = '/Backend/api/admin_business.php?action=list_cashbook';
    if (from) url += '&from=' + from;
    if (to) url += '&to=' + to;
    if (dir) url += '&direction=' + dir;
    const tbody = document.getElementById('cb-body');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch(url);
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        const data = r.data || [];
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">No entries yet. Start by adding money in or out.</td></tr>'; return; }
        tbody.innerHTML = data.map(e => {
            const isIn = e.direction === 'in';
            return `<tr>
                <td>${e.entry_date}</td>
                <td style="text-align:right;font-weight:700;color:#16a34a;">${isIn ? 'KES ' + parseFloat(e.amount).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) : ''}</td>
                <td style="text-align:right;font-weight:700;color:#dc2626;">${!isIn ? 'KES ' + parseFloat(e.amount).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) : ''}</td>
                <td><span class="badge-pill badge-pill-info">${sourceLabels[e.money_source]||e.money_source}</span><br><small style="color:#64748b;">${escapeHtml(e.description||'')}</small></td>
                <td>${escapeHtml(e.customer_name||e.supplier_name||'—')}</td>
                <td>${e.paid_through}</td>
                <td><strong>KES ${parseFloat(e.running_balance).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</strong></td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="deleteEntry(${e.id})"><i data-lucide="trash-2" style="width:13px;height:13px;"></i></button>
                </td>
            </tr>`;
        }).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
        // KPIs
        const today = new Date().toISOString().split('T')[0];
        const inToday = data.filter(e => e.entry_date === today && e.direction === 'in').reduce((s,e) => s + parseFloat(e.amount), 0);
        const outToday = data.filter(e => e.entry_date === today && e.direction === 'out').reduce((s,e) => s + parseFloat(e.amount), 0);
        document.getElementById('cb-in-today').textContent = 'KES ' + inToday.toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
        document.getElementById('cb-out-today').textContent = 'KES ' + outToday.toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
        document.getElementById('cb-profit-today').textContent = 'KES ' + (inToday - outToday).toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
        document.getElementById('cb-balance').textContent = 'KES ' + parseFloat(r.closing_balance || 0).toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0});
    } catch (e) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#dc2626;">Network error</td></tr>'; }
}

async function deleteEntry(id) {
    if (!confirm('Delete this entry?')) return;
    const fd = new FormData(); fd.append('id', id);
    const res = await fetch('/Backend/api/admin_business.php?action=delete_cashbook', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) loadCashbook(); else alert(r.message);
}

function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

document.addEventListener('DOMContentLoaded', () => { loadCashbook(); if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
