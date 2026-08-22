<?php
/**
 * Admin — Egg Grading Module
 * Tracks eggs graded by size (Extra Large, B14, B15, Cracked)
 * Mirrors the B14/B15/Extra Large columns from SALES REPORT 2026
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
$page_title = 'Egg Grading - Admin';
include __DIR__ . '/includes/admin_header.php';

$pdo = getDB();
$batches = [];
if ($pdo) {
    $batches = safeQueryAll($pdo, "SELECT id, batch_name, batch_code FROM batches WHERE status='active' ORDER BY batch_name");
}
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Egg Grading</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Grade eggs by size: Extra Large, B14, B15, Cracked. One row per day, per grade.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/Backend/api/export.php?module=egg_grades" class="btn btn-outline"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
        <button class="btn btn-primary" onclick="openGradingModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> New Grading</button>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr>
                <th>Date</th><th>Batch</th><th>Grade</th>
                <th>Total Eggs</th><th>Crates</th><th>Damaged</th><th>Notes</th>
                <th>Actions</th>
            </tr></thead>
            <tbody id="grading-body">
                <tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Grading Modal -->
<div id="grading-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:520px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 id="grading-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">New Grading Entry</h3>
        <form id="grading-form">
            <input type="hidden" id="g-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" id="g-date" required value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Batch</label>
                    <select class="admin-form-control" id="g-batch">
                        <option value="">— Optional —</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['batch_name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Grade *</label>
                    <select class="admin-form-control" id="g-grade" required>
                        <option value="">Loading grades...</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Total Eggs *</label><input class="admin-form-control" type="number" id="g-eggs" min="1" required value="0"></div>
                <div class="admin-form-group"><label class="admin-form-label">Crates</label><input class="admin-form-control" type="number" step="0.01" id="g-crates" min="0" value="0"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Damaged (subtract from total)</label><input class="admin-form-control" type="number" id="g-dmg" min="0" value="0"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" id="g-notes" rows="2"></textarea></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeGradingModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
let grades = [];

async function loadGrades() {
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=get_grades');
    const r = await res.json();
    grades = r.data || [];
    const sel = document.getElementById('g-grade');
    sel.innerHTML = '<option value="">Choose grade...</option>' + grades.map(g => `<option value="${g.id}">${g.grade_name} (${g.grade_code})</option>`).join('');
}

async function loadGrading() {
    const tbody = document.getElementById('grading-body');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch('/Backend/api/admin_poultry_v2.php?action=get_daily_grading');
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        const data = r.data || [];
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">No grading records yet.</td></tr>'; return; }
        tbody.innerHTML = data.map(d => `<tr>
            <td>${d.record_date}</td>
            <td>${escapeHtml(d.batch_name||'—')}</td>
            <td><span class="badge-pill badge-pill-info">${d.grade_name} (${d.grade_code})</span></td>
            <td><strong>${parseInt(d.total_eggs).toLocaleString()}</strong></td>
            <td>${parseFloat(d.crates_count).toFixed(2)}</td>
            <td>${parseInt(d.damaged)}</td>
            <td>${escapeHtml(d.notes||'')}</td>
            <td>
                <button class="btn btn-trans btn-sm" onclick='openGradingModal(${JSON.stringify(d)})'><i data-lucide="pencil" style="width:13px;height:13px;"></i></button>
            </td>
        </tr>`).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#dc2626;">Network error</td></tr>';
    }
}

function openGradingModal(d) {
    document.getElementById('grading-modal-title').textContent = d?.id ? 'Edit Grading' : 'New Grading Entry';
    document.getElementById('g-id').value = d?.id || '';
    document.getElementById('g-date').value = d?.record_date || new Date().toISOString().split('T')[0];
    document.getElementById('g-batch').value = d?.batch_id || '';
    document.getElementById('g-grade').value = d?.grade_id || '';
    document.getElementById('g-eggs').value = d?.total_eggs || 0;
    document.getElementById('g-crates').value = d?.crates_count || 0;
    document.getElementById('g-dmg').value = d?.damaged || 0;
    document.getElementById('g-notes').value = d?.notes || '';
    document.getElementById('grading-modal').style.display = 'flex';
}
function closeGradingModal() { document.getElementById('grading-modal').style.display = 'none'; }

document.getElementById('grading-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('id', document.getElementById('g-id').value);
    fd.append('record_date', document.getElementById('g-date').value);
    fd.append('batch_id', document.getElementById('g-batch').value);
    fd.append('grade_id', document.getElementById('g-grade').value);
    fd.append('total_eggs', document.getElementById('g-eggs').value);
    fd.append('crates_count', document.getElementById('g-crates').value);
    fd.append('damaged', document.getElementById('g-dmg').value);
    fd.append('notes', document.getElementById('g-notes').value);
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=save_daily_grading', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { closeGradingModal(); loadGrading(); }
    else alert('Error: ' + r.message);
});

function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

document.addEventListener('DOMContentLoaded', () => { loadGrades(); loadGrading(); if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
