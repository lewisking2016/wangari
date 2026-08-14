<?php
/**
 * Admin — Online Orders
 * Mirrors the wangari_orders_report CSV structure:
 *   Order ID, Order Number, Customer, Email, Amount, Status, Date
 */
declare(strict_types=1);
$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();
$page_title = 'Online Orders - Admin';
include __DIR__ . '/includes/admin_header.php';
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Online Orders</h1>                        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Manage orders placed through the public website. Match the wangari_orders_report CSV export structure.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/Backend/api/export.php?module=orders" class="btn btn-outline"><i data-lucide="download" style="width:15px;height:15px;"></i> Export CSV</a>
        <button class="btn btn-primary" onclick="openOrderModal()"><i data-lucide="plus" style="width:15px;height:15px;"></i> New Order</button>
    </div>
</div>

<!-- KPIs -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
    <div class="stat-card"><div class="stat-card-info"><small>Orders Today</small><strong id="ord-kpi-today">—</strong></div><div class="stat-card-icon accent"><i data-lucide="shopping-cart" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Pending</small><strong id="ord-kpi-pending">—</strong></div><div class="stat-card-icon" style="background:#fef3c7;color:#d97706;"><i data-lucide="clock" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Revenue This Month</small><strong id="ord-kpi-month">—</strong></div><div class="stat-card-icon info"><i data-lucide="banknote" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Avg Order Value</small><strong id="ord-kpi-avg">—</strong></div><div class="stat-card-icon" style="background:#dcfce7;color:#16a34a;"><i data-lucide="trending-up" style="width:22px;height:22px;"></i></div></div>
</div>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">All Online Orders</h3>
        <div style="display:flex;gap:8px;">
            <select class="admin-form-control" id="ord-filter-status" onchange="loadOrders()" style="max-width:160px;">
                <option value="">All status</option>
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <input class="admin-form-control" type="date" id="ord-from" onchange="loadOrders()" style="max-width:160px;">
            <input class="admin-form-control" type="date" id="ord-to" onchange="loadOrders()" style="max-width:160px;">
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr>
                <th>Order ID</th><th>Order Number</th><th>Customer</th>
                <th>Email</th><th>Phone</th><th>Amount</th>
                <th>Status</th><th>Date</th><th>Actions</th>
            </tr></thead>
            <tbody id="orders-body">
                <tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
async function loadOrders() {
    const tbody = document.getElementById('orders-body');
    const status = document.getElementById('ord-filter-status').value;
    const from = document.getElementById('ord-from').value;
    const to = document.getElementById('ord-to').value;
    let url = '/Backend/api/admin_actions.php?action=list_orders';
    if (status) url += '&status=' + status;
    if (from) url += '&from=' + from;
    if (to) url += '&to=' + to;
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch(url);
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        const data = r.data || [];
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8;">No orders yet.</td></tr>'; updateKpis([]); return; }
        tbody.innerHTML = data.map(o => {
            const sc = {pending:'badge-pill-warning',paid:'badge-pill-info',processing:'badge-pill-info',shipped:'badge-pill-success',completed:'badge-pill-success',cancelled:'badge-pill-danger'}[o.status] || 'badge-pill-info';
            return `<tr>
                <td>${o.id}</td>
                <td><code style="background:#f1f5f9;padding:2px 8px;border-radius:4px;">${o.order_number}</code></td>
                <td><strong>${escapeHtml(o.customer_name||'Guest')}</strong></td>
                <td>${escapeHtml(o.customer_email||'—')}</td>
                <td>${escapeHtml(o.phone_contact||'—')}</td>
                <td><strong>KES ${parseFloat(o.total_amount).toFixed(2)}</strong></td>
                <td><span class="badge-pill ${sc}">${o.status}</span></td>
                <td>${o.created_at?.split(' ')[0]||o.created_at}</td>
                <td>
                    <button class="btn btn-trans btn-sm" onclick="viewOrder(${o.id})"><i data-lucide="eye" style="width:13px;height:13px;"></i></button>
                    <button class="btn btn-primary btn-sm" onclick="updateOrderStatus(${o.id},'processing')" title="Mark Processing"><i data-lucide="play" style="width:13px;height:13px;"></i></button>
                    <button class="btn btn-primary btn-sm" onclick="updateOrderStatus(${o.id},'completed')" title="Mark Completed"><i data-lucide="check" style="width:13px;height:13px;"></i></button>
                </td>
            </tr>`;
        }).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
        updateKpis(data);
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#dc2626;">Network error</td></tr>';
    }
}

function updateKpis(data) {
    const today = new Date().toISOString().split('T')[0];
    const monthStart = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
    const todayCount = data.filter(o => o.created_at?.startsWith(today)).length;
    const pending = data.filter(o => o.status==='pending' || o.status==='paid').length;
    const monthRev = data.filter(o => o.created_at?.startsWith(monthStart.slice(0,7))).reduce((s,o)=>s+parseFloat(o.total_amount||0),0);
    const totalRev = data.reduce((s,o)=>s+parseFloat(o.total_amount||0),0);
    const avg = data.length ? totalRev/data.length : 0;
    document.getElementById('ord-kpi-today').textContent = todayCount;
    document.getElementById('ord-kpi-pending').textContent = pending;
    document.getElementById('ord-kpi-month').textContent = 'KES ' + monthRev.toFixed(0).toLocaleString();
    document.getElementById('ord-kpi-avg').textContent = 'KES ' + avg.toFixed(0).toLocaleString();
}

async function viewOrder(id) {
    try {
        const res = await fetch('/Backend/api/admin_actions.php?action=get_order&id=' + id);
        const r = await res.json();
        if (!r.success) { alert(r.message); return; }
        const o = r.data;
        let itemsTxt = (o.items||[]).map(i => `• ${i.name} × ${i.quantity} = KES ${parseFloat(i.price_at_purchase * i.quantity).toFixed(2)}`).join('\n');
        alert(`Order ${o.order_number}\nCustomer: ${o.customer_name} (${o.customer_email})\nPhone: ${o.phone_contact}\nAddress: ${o.shipping_address}\n\nItems:\n${itemsTxt}\n\nTotal: KES ${parseFloat(o.total_amount).toFixed(2)}\nStatus: ${o.status}\nDate: ${o.created_at}\nPayment: ${o.payment_method}`);
    } catch (e) { alert('Failed to load order details'); }
}

async function updateOrderStatus(id, status) {
    const fd = new FormData();
    fd.append('id', id);
    fd.append('status', status);
    const res = await fetch('/Backend/api/admin_actions.php?action=update_order_status', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) loadOrders(); else alert(r.message);
}

function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

document.addEventListener('DOMContentLoaded', () => {
    loadOrders();
    if (typeof lucide !== 'undefined') lucide.createIcons();
    // Update orders export link with date range
    const ordExportLink = document.querySelector('a[href*="export.php?module=orders"]');
    if (ordExportLink) {
        const update = () => {
            const f = document.getElementById('ord-from')?.value || '';
            const t = document.getElementById('ord-to')?.value || '';
            ordExportLink.href = '/Backend/api/export.php?module=orders' + (f?'&from='+f:'') + (t?'&to='+t:'');
        };
        document.getElementById('ord-from')?.addEventListener('change', update);
        document.getElementById('ord-to')?.addEventListener('change', update);
    }
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
