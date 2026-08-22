<?php
/**
 * Admin — Hatchery (Day-Old Chicks)
 * Track eggs in setter, eggs in hatcher, hatchability, DOC sales
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
$page_title = 'Hatchery (Day-Old Chicks) - Admin';
include __DIR__ . '/includes/admin_header.php';
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Hatchery (Day-Old Chicks)</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Track eggs set, expected hatch, actual hatch. See hatchability %. Record DOC sales.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/Backend/api/export.php?module=hatchery" class="btn btn-outline"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
        <button class="btn btn-primary" onclick="openHatchModal()"><i data-lucide="plus" style="width:16px;height:16px;"></i> New Hatch Record</button>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
    <div class="stat-card"><div class="stat-card-info"><small>Hatches This Week</small><strong id="h-week">0</strong></div><div class="stat-card-icon accent"><i data-lucide="egg" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Total Chicks Hatched</small><strong id="h-total">0</strong></div><div class="stat-card-icon info"><i data-lucide="baby" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Avg Hatchability</small><strong id="h-pct">0%</strong></div><div class="stat-card-icon" style="background:#dcfce7;color:#16a34a;"><i data-lucide="percent" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Active Hatches</small><strong id="h-active">0</strong></div><div class="stat-card-icon" style="background:#fef3c7;color:#d97706;"><i data-lucide="clock" style="width:22px;height:22px;"></i></div></div>
</div>

<div class="admin-card">
    <h3 style="margin:0 0 18px;font-family:'Outfit',sans-serif;font-size:1.1rem;">All Hatchery Records</h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr>
                <th>Setting Date</th><th>Expected Hatch</th><th>Actual Hatch</th>
                <th>Breed</th><th>Eggs Set</th><th>Fertile</th>
                <th>Chicks Hatched</th><th>Hatchability</th>
                <th>Destination</th><th>Actions</th>
            </tr></thead>
            <tbody id="h-body">
                <tr><td colspan="10" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Hatch Modal -->
<div id="h-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;overflow-y:auto;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:560px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;margin:20px;">
        <h3 id="h-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">New Hatch Record</h3>
        <form id="h-form">
            <input type="hidden" id="ha-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Setting Date (eggs went in) *</label><input class="admin-form-control" type="date" id="ha-set" required value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Expected Hatch Date *</label><input class="admin-form-control" type="date" id="ha-exp" required value="<?= date('Y-m-d', strtotime('+21 days')) ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Actual Hatch Date</label><input class="admin-form-control" type="date" id="ha-actual"></div>
                <div class="admin-form-group"><label class="admin-form-label">Breed *</label>
                    <select class="admin-form-control" id="ha-breed" required>
                        <option value="">Choose breed...</option>
                        <option value="ISA Brown">ISA Brown</option>
                        <option value="Ross 308">Ross 308</option>
                        <option value="Cobb 500">Cobb 500</option>
                        <option value="Kienyeji">Kienyeji</option>
                        <option value="Local Improved">Local Improved</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Eggs Set *</label><input class="admin-form-control" type="number" id="ha-eggs" required min="1"></div>
                <div class="admin-form-group"><label class="admin-form-label">Fertile Eggs (after candling)</label><input class="admin-form-control" type="number" id="ha-fertile" min="0"></div>
                <div class="admin-form-group"><label class="admin-form-label">Chicks Hatched</label><input class="admin-form-control" type="number" id="ha-hatched" min="0"></div>
                <div class="admin-form-group"><label class="admin-form-label">Where do they go?</label>
                    <select class="admin-form-control" id="ha-dest">
                        <option value="own_farm">To my own farm</option>
                        <option value="sold">Sold to customers</option>
                        <option value="disposed">Disposed (didn't hatch)</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Cost per DOC (KES)</label><input class="admin-form-control" type="number" step="0.01" id="ha-cost" min="0" value="0"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" id="ha-notes" rows="2"></textarea></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('h-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
async function loadHatchery() {
    const tbody = document.getElementById('h-body');
    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch('/Backend/api/admin_business.php?action=list_hatchery');
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        const data = r.data || [];
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:28px;color:#94a3b8;">No hatchery records yet.</td></tr>'; updateKpis([]); return; }
        tbody.innerHTML = data.map(h => {
            const destBadge = h.destination==='sold'?'badge-pill-success':(h.destination==='disposed'?'badge-pill-danger':'badge-pill-info');
            return `<tr>
                <td>${h.setting_date}</td>
                <td>${h.expected_hatch_date}</td>
                <td>${h.actual_hatch_date||'—'}</td>
                <td><strong>${escapeHtml(h.breed)}</strong></td>
                <td>${parseInt(h.eggs_set).toLocaleString()}</td>
                <td>${parseInt(h.fertile_eggs||0).toLocaleString()}</td>
                <td><strong>${parseInt(h.chicks_hatched||0).toLocaleString()}</strong></td>
                <td><strong>${parseFloat(h.hatchability_pct||0).toFixed(1)}%</strong></td>
                <td><span class="badge-pill ${destBadge}">${h.destination.replace('_',' ')}</span></td>
                <td>
                    <button class="btn btn-trans btn-sm" onclick='openHatchModal(${JSON.stringify(h)})'><i data-lucide="pencil" style="width:13px;height:13px;"></i></button>
                </td>
            </tr>`;
        }).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
        updateKpis(data);
    } catch (e) { tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;color:#dc2626;">Network error</td></tr>'; }
}

function updateKpis(data) {
    const today = new Date();
    const weekAgo = new Date(today.getTime() - 7*86400000).toISOString().split('T')[0];
    const weekHatches = data.filter(h => h.actual_hatch_date && h.actual_hatch_date >= weekAgo).length;
    const totalChicks = data.reduce((s,h) => s + parseInt(h.chicks_hatched||0), 0);
    const avgHatch = data.length ? data.reduce((s,h) => s + parseFloat(h.hatchability_pct||0), 0) / data.length : 0;
    const activeHatches = data.filter(h => !h.actual_hatch_date).length;
    document.getElementById('h-week').textContent = weekHatches;
    document.getElementById('h-total').textContent = totalChicks.toLocaleString();
    document.getElementById('h-pct').textContent = avgHatch.toFixed(1) + '%';
    document.getElementById('h-active').textContent = activeHatches;
}

function openHatchModal(d) {
    document.getElementById('h-modal-title').textContent = d?.id ? 'Edit Hatch Record' : 'New Hatch Record';
    document.getElementById('ha-id').value = d?.id || '';
    document.getElementById('ha-set').value = d?.setting_date || new Date().toISOString().split('T')[0];
    document.getElementById('ha-exp').value = d?.expected_hatch_date || '';
    document.getElementById('ha-actual').value = d?.actual_hatch_date || '';
    document.getElementById('ha-breed').value = d?.breed || '';
    document.getElementById('ha-eggs').value = d?.eggs_set || '';
    document.getElementById('ha-fertile').value = d?.fertile_eggs || '';
    document.getElementById('ha-hatched').value = d?.chicks_hatched || '';
    document.getElementById('ha-dest').value = d?.destination || 'own_farm';
    document.getElementById('ha-cost').value = d?.cost_per_doc || 0;
    document.getElementById('ha-notes').value = d?.notes || '';
    document.getElementById('h-modal').style.display = 'flex';
}

document.getElementById('h-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('id', document.getElementById('ha-id').value);
    fd.append('setting_date', document.getElementById('ha-set').value);
    fd.append('expected_hatch_date', document.getElementById('ha-exp').value);
    fd.append('actual_hatch_date', document.getElementById('ha-actual').value);
    fd.append('breed', document.getElementById('ha-breed').value);
    fd.append('eggs_set', document.getElementById('ha-eggs').value);
    fd.append('fertile_eggs', document.getElementById('ha-fertile').value);
    fd.append('chicks_hatched', document.getElementById('ha-hatched').value);
    fd.append('destination', document.getElementById('ha-dest').value);
    fd.append('cost_per_doc', document.getElementById('ha-cost').value);
    fd.append('notes', document.getElementById('ha-notes').value);
    const res = await fetch('/Backend/api/admin_business.php?action=add_hatch', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { document.getElementById('h-modal').style.display='none'; loadHatchery(); }
    else alert('Error: ' + r.message);
});

function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

document.addEventListener('DOMContentLoaded', () => { loadHatchery(); if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
