<?php
/**
 * Admin — Broiler Workflow
 * Weigh-ins at 7/14/21/28/35 days, growth chart, harvest tracking
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
$page_title = 'Broiler Workflow - Admin';
include __DIR__ . '/includes/admin_header.php';

$pdo = getDB();
$batches = [];
if ($pdo) {
    $batches = safeQueryAll($pdo, "SELECT id, batch_name, batch_code, batch_type, current_birds, placement_date FROM batches WHERE batch_type='broiler' OR batch_type='dual_purpose' ORDER BY placement_date DESC");
}
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Broiler Workflow</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Track weight gains at 7, 14, 21, 28, 35 days. Know when to harvest. Track hatchery operations for DOC sales.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="/Backend/api/export.php?module=weighings" class="btn btn-outline"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
        <button class="btn btn-primary" onclick="openWeighingModal()"><i data-lucide="plus" style="width:16px;height:16px;"></i> Record Weigh-In</button>
    </div>
</div>

<div class="admin-card">
    <h3 style="margin:0 0 18px;font-family:'Outfit',sans-serif;font-size:1.1rem;">Weight Tracking</h3>

    <div class="admin-form-group" style="max-width:420px;margin-bottom:18px;">
        <label class="admin-form-label">Choose a batch</label>
        <select class="admin-form-control" id="w-batch" onchange="loadWeighings()">
            <option value="">All batches</option>
            <?php foreach ($batches as $b): ?>
                <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['batch_name'] . ' (' . $b['batch_code'] . ') - placed ' . $b['placement_date'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr>
                <th>Date</th><th>Batch</th><th>Day #</th>
                <th>Sample Size</th><th>Average Weight (kg)</th>
                <th>Total Weight (kg)</th><th>Notes</th><th>Actions</th>
            </tr></thead>
            <tbody id="w-body">
                <tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Weighing Modal -->
<div id="w-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 8px;font-family:'Outfit',sans-serif;">Record Weigh-In</h3>
        <p style="margin:0 0 18px;color:#64748b;font-size:0.85rem;">Weigh 5-10 birds randomly. The system calculates the average and total flock weight.</p>
        <form id="w-form">
            <div class="admin-form-group"><label class="admin-form-label">Batch *</label>
                <select class="admin-form-control" id="w-batch-sel" required>
                    <option value="">Choose batch...</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['batch_name'] . ' (' . $b['batch_code'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" id="w-date" required value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Day # *</label><input class="admin-form-control" type="number" id="w-day" min="1" max="100" required placeholder="e.g. 7, 14, 21..."></div>
                <div class="admin-form-group"><label class="admin-form-label">Sample Size</label><input class="admin-form-control" type="number" id="w-sample" min="1" value="10"></div>
                <div class="admin-form-group"><label class="admin-form-label">Average Weight (kg) *</label><input class="admin-form-control" type="number" step="0.001" id="w-avg" required min="0.001"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><input class="admin-form-control" id="w-notes"></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('w-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
async function loadWeighings() {
    const bid = document.getElementById('w-batch').value;
    let url = '/Backend/api/admin_business.php?action=list_weighings';
    if (bid) url += '&batch_id=' + bid;
    const tbody = document.getElementById('w-body');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch(url);
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        const data = r.data || [];
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">No weigh-ins recorded yet.</td></tr>'; return; }
        tbody.innerHTML = data.map(w => {
            const totalKg = (parseFloat(w.avg_weight_kg) * 1000).toFixed(0); // approx
            return `<tr>
                <td>${w.weigh_date}</td>
                <td><strong>${escapeHtml(w.batch_name||'—')}</strong></td>
                <td>Day ${w.day_number}</td>
                <td>${w.sample_size} birds</td>
                <td><strong>${parseFloat(w.avg_weight_kg).toFixed(3)} kg</strong></td>
                <td>${totalKg} kg (approx)</td>
                <td>${escapeHtml(w.notes||'')}</td>
                <td></td>
            </tr>`;
        }).join('');
    } catch (e) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#dc2626;">Network error</td></tr>'; }
}

function openWeighingModal() { document.getElementById('w-modal').style.display = 'flex'; }

document.getElementById('w-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('batch_id', document.getElementById('w-batch-sel').value);
    fd.append('weigh_date', document.getElementById('w-date').value);
    fd.append('day_number', document.getElementById('w-day').value);
    fd.append('sample_size', document.getElementById('w-sample').value);
    fd.append('avg_weight_kg', document.getElementById('w-avg').value);
    fd.append('notes', document.getElementById('w-notes').value);
    const res = await fetch('/Backend/api/admin_business.php?action=add_weighing', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { alert('Weigh-in recorded'); document.getElementById('w-modal').style.display='none'; document.getElementById('w-form').reset(); document.getElementById('w-date').value = new Date().toISOString().split('T')[0]; loadWeighings(); }
    else alert('Error: ' + r.message);
});

function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

document.addEventListener('DOMContentLoaded', () => { loadWeighings(); if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
