<?php
/**
 * Admin — Daily Sales Reconciliation
 * Mirrors "BATCH 16 DAILY SALES REPORT" spreadsheet:
 *   - One row per day with production count
 *   - Multiple price tiers (360, 380, 400 KES)
 *   - Total sales, opening/closing balance
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
$page_title = 'Daily Sales Reconciliation - Admin';
include __DIR__ . '/includes/admin_header.php';
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Daily Sales Reconciliation</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Reconcile each day's egg production against crates sold at various price points. Tracks opening/closing balance.</p>
    </div>
    <button class="btn btn-primary" onclick="openSaleModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Record Daily Sales</button>
</div>

<!-- KPIs -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
    <div class="stat-card"><div class="stat-card-info"><small>Crates Sold Today</small><strong id="ds-kpi-crates">—</strong></div><div class="stat-card-icon accent"><i data-lucide="package" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Sales Today</small><strong id="ds-kpi-sales">—</strong></div><div class="stat-card-icon info"><i data-lucide="banknote" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>This Week Sales</small><strong id="ds-kpi-week">—</strong></div><div class="stat-card-icon" style="background:#dcfce7;color:#16a34a;"><i data-lucide="trending-up" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>This Month Sales</small><strong id="ds-kpi-month">—</strong></div><div class="stat-card-icon" style="background:#fef3c7;color:#d97706;"><i data-lucide="calendar" style="width:22px;height:22px;"></i></div></div>
</div>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Recent Reconciliation</h3>
        <div style="display:flex;gap:8px;align-items:center;">
            <input class="admin-form-control" type="date" id="ds-from" onchange="loadSales()" style="max-width:160px;">
            <input class="admin-form-control" type="date" id="ds-to" onchange="loadSales()" style="max-width:160px;">
            <a href="/Backend/api/export.php?module=daily_sales" class="btn btn-outline" id="ds-export"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr>
                <th>Date</th>
                <th>Open Bal</th>
                <th>Produced</th>
                <th>Sold</th>
                <th>Close Bal</th>
                <th>Total Sales</th>
                <th>Eggs</th>
                <th>Lines</th>
                <th>Actions</th>
            </tr></thead>
            <tbody id="sales-body">
                <tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Daily Sales Modal -->
<div id="sale-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;overflow-y:auto;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:760px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;margin:20px;">
        <h3 id="sale-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Daily Sales Reconciliation</h3>
        <form id="sale-form">
            <input type="hidden" id="s-id">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" id="s-date" required value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Opening Balance (crates)</label><input class="admin-form-control" type="number" id="s-open" min="0" value="0"></div>
                <div class="admin-form-group"><label class="admin-form-label">Total Produced Today (crates)</label><input class="admin-form-control" type="number" id="s-prod" min="0" value="0"></div>
            </div>

            <h4 style="margin:24px 0 12px;font-family:'Outfit',sans-serif;font-size:0.95rem;color:var(--admin-primary);font-weight:700;">Sales Lines</h4>
            <p style="font-size:0.85rem;color:#64748b;margin:0 0 12px;">Add one line per price tier (e.g. 300 KES small, 390 KES medium, etc.)</p>
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr style="background:#f8fafc;">
                    <th style="text-align:left;padding:8px;font-size:0.82rem;">Product</th>
                    <th style="text-align:left;padding:8px;font-size:0.82rem;">Unit Price (KES)</th>
                    <th style="text-align:left;padding:8px;font-size:0.82rem;">Crates</th>
                    <th style="text-align:left;padding:8px;font-size:0.82rem;">Line Total</th>
                    <th style="padding:8px;"></th>
                </tr></thead>
                <tbody id="sale-lines"></tbody>
            </table>
            <button type="button" class="btn btn-outline btn-sm" style="margin-top:10px;" onclick="addLine()"><i data-lucide="plus" style="width:13px;height:13px;"></i> Add Line</button>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:20px;padding:14px;background:#f8fafc;border-radius:8px;">
                <strong style="font-family:'Outfit',sans-serif;font-size:1.05rem;">Total Sales:</strong>
                <strong id="sale-total-display" style="font-size:1.4rem;color:var(--admin-primary);">KES 0.00</strong>
            </div>

            <div class="admin-form-group" style="margin-top:14px;"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" id="s-notes" rows="2"></textarea></div>

            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeSaleModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Reconciliation</button>
            </div>
        </form>
    </div>
</div>

<script>
let lineIdx = 0;

function addLine(data = null) {
    const idx = lineIdx++;
    const tr = document.createElement('tr');
    tr.dataset.idx = idx;
    tr.style.borderBottom = '1px solid #e2e8f0';
    tr.innerHTML = `
        <td style="padding:6px;">
            <select class="admin-form-control" data-field="product_type" style="font-size:0.85rem;padding:6px 8px;">
                <option value="eggs">Eggs</option>
                <option value="broiler">Broiler</option>
                <option value="kienyeji">Kienyeji</option>
                <option value="manure">Manure</option>
                <option value="other">Other</option>
            </select>
        </td>
        <td style="padding:6px;"><input class="admin-form-control" type="number" step="0.01" data-field="unit_price" value="${data?.unit_price||380}" style="font-size:0.85rem;padding:6px 8px;"></td>
        <td style="padding:6px;"><input class="admin-form-control" type="number" data-field="quantity_crates" value="${data?.quantity_crates||0}" style="font-size:0.85rem;padding:6px 8px;" oninput="recalcTotal()"></td>
        <td style="padding:6px;font-weight:700;font-size:0.9rem;" class="line-total">KES 0.00</td>
        <td style="padding:6px;"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove();recalcTotal();" style="padding:4px 8px;"><i data-lucide="x" style="width:12px;height:12px;"></i></button></td>
    `;
    document.getElementById('sale-lines').appendChild(tr);
    if (data) tr.querySelector('[data-field=product_type]').value = data.product_type;
    recalcTotal();
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function recalcTotal() {
    let total = 0;
    document.querySelectorAll('#sale-lines tr').forEach(tr => {
        const price = parseFloat(tr.querySelector('[data-field=unit_price]').value || 0);
        const qty = parseFloat(tr.querySelector('[data-field=quantity_crates]').value || 0);
        const lineTotal = price * qty;
        tr.querySelector('.line-total').textContent = 'KES ' + lineTotal.toFixed(2);
        total += lineTotal;
    });
    document.getElementById('sale-total-display').textContent = 'KES ' + total.toFixed(2);
}

function openSaleModal(d) {
    document.getElementById('sale-modal-title').textContent = d?.id ? 'Edit Daily Sales' : 'Daily Sales Reconciliation';
    document.getElementById('s-id').value = d?.id || '';
    document.getElementById('s-date').value = d?.sale_date || new Date().toISOString().split('T')[0];
    document.getElementById('s-open').value = d?.opening_balance_crates || 0;
    document.getElementById('s-prod').value = d?.total_production_crates || 0;
    document.getElementById('s-notes').value = d?.notes || '';
    document.getElementById('sale-lines').innerHTML = '';
    lineIdx = 0;
    if (d?.lines && d.lines.length) {
        d.lines.forEach(l => addLine(l));
    } else {
        // Default 2 lines
        addLine({unit_price: 360});
        addLine({unit_price: 380});
        addLine({unit_price: 400});
    }
    recalcTotal();
    document.getElementById('sale-modal').style.display = 'flex';
}
function closeSaleModal() { document.getElementById('sale-modal').style.display = 'none'; }

document.getElementById('sale-form').addEventListener('submit', async e => {
    e.preventDefault();
    const lines = [];
    document.querySelectorAll('#sale-lines tr').forEach(tr => {
        lines.push({
            product_type: tr.querySelector('[data-field=product_type]').value,
            unit_price: parseFloat(tr.querySelector('[data-field=unit_price]').value || 0),
            quantity_crates: parseFloat(tr.querySelector('[data-field=quantity_crates]').value || 0)
        });
    });
    if (!lines.length) { alert('Add at least one sales line'); return; }
    const fd = new FormData();
    fd.append('id', document.getElementById('s-id').value);
    fd.append('sale_date', document.getElementById('s-date').value);
    fd.append('opening_balance_crates', document.getElementById('s-open').value);
    fd.append('total_production_crates', document.getElementById('s-prod').value);
    fd.append('notes', document.getElementById('s-notes').value);
    fd.append('lines', JSON.stringify(lines));
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=save_daily_sales', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { alert('Saved. Total: KES ' + r.total_sales.toFixed(2) + ', Closing balance: ' + r.closing_balance + ' crates'); closeSaleModal(); loadSales(); }
    else alert('Error: ' + r.message);
});

async function loadSales() {
    const tbody = document.getElementById('sales-body');
    const from = document.getElementById('ds-from')?.value || '';
    const to = document.getElementById('ds-to')?.value || '';
    let url = '/Backend/api/admin_poultry_v2.php?action=get_daily_sales';
    if (from) url += '&from=' + from;
    if (to) url += '&to=' + to;
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch(url);
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        const data = r.data || [];
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8;">No data yet.</td></tr>'; updateKpis([]); return; }
        tbody.innerHTML = data.map(s => `<tr>
            <td><strong>${s.sale_date}</strong></td>
            <td>${parseInt(s.opening_balance_crates||0).toLocaleString()}</td>
            <td>${parseInt(s.total_production_crates||0).toLocaleString()}</td>
            <td><strong>${parseInt(s.total_sold_crates||0).toLocaleString()}</strong></td>
            <td>${parseInt(s.closing_balance_crates||0).toLocaleString()}</td>
            <td><strong>KES ${parseFloat(s.total_sales_amount||0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</strong></td>
            <td>${parseInt(s.total_eggs||0).toLocaleString()}</td>
            <td>${s.lines?.length||0}</td>
            <td>
                <button class="btn btn-trans btn-sm" onclick='openSaleModal(${JSON.stringify(s)})'><i data-lucide="pencil" style="width:13px;height:13px;"></i></button>
                <button class="btn btn-danger btn-sm" onclick="deleteSale(${s.id})"><i data-lucide="trash-2" style="width:13px;height:13px;"></i></button>
            </td>
        </tr>`).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
        updateKpis(data);
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#dc2626;">Network error</td></tr>';
    }
}

function updateKpis(data) {
    const today = new Date().toISOString().split('T')[0];
    const weekAgo = new Date(Date.now() - 7*86400000).toISOString().split('T')[0];
    const monthStart = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
    const todayData = data.find(s => s.sale_date === today) || { total_sold_crates: 0, total_sales_amount: 0 };
    const weekData = data.filter(s => s.sale_date >= weekAgo);
    const monthData = data.filter(s => s.sale_date >= monthStart);
    document.getElementById('ds-kpi-crates').textContent = parseInt(todayData.total_sold_crates||0).toLocaleString();
    document.getElementById('ds-kpi-sales').textContent = 'KES ' + parseFloat(todayData.total_sales_amount||0).toLocaleString();
    document.getElementById('ds-kpi-week').textContent = 'KES ' + weekData.reduce((s,d)=>s+parseFloat(d.total_sales_amount||0),0).toLocaleString();
    document.getElementById('ds-kpi-month').textContent = 'KES ' + monthData.reduce((s,d)=>s+parseFloat(d.total_sales_amount||0),0).toLocaleString();
}

async function deleteSale(id) {
    if (!confirm('Delete this reconciliation?')) return;
    const fd = new FormData(); fd.append('id', id);
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=delete_daily_sales', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) loadSales(); else alert(r.message);
}

document.addEventListener('DOMContentLoaded', () => {
    loadSales();
    if (typeof lucide !== 'undefined') lucide.createIcons();
    // Update export link with current date range
    const exp = document.getElementById('ds-export');
    if (exp) {
        const update = () => {
            const f = document.getElementById('ds-from')?.value || '';
            const t = document.getElementById('ds-to')?.value || '';
            exp.href = '/Backend/api/export.php?module=daily_sales' + (f?'&from='+f:'') + (t?'&to='+t:'');
        };
        document.getElementById('ds-from')?.addEventListener('change', update);
        document.getElementById('ds-to')?.addEventListener('change', update);
    }
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
