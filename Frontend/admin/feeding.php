<?php
/**
 * Admin — Feeding Program
 * Shows how much feed birds need at each age, and tracks what you actually fed.
 * Calculates FCR (Feed Conversion Ratio) — the #1 KPI for any poultry farmer.
 */
declare(strict_types=1);
header('Location: /Frontend/admin/hub_operations.php?tab=feeding', true, 301);
exit;

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
$page_title = 'Feeding Program - Admin';
include __DIR__ . '/includes/admin_header.php';

$pdo = getDB();
$tab = $_GET['tab'] ?? 'standards';
$validTabs = ['standards','allocations','fcr'];
if (!in_array($tab, $validTabs, true)) $tab = 'standards';

$batches = [];
if ($pdo) {
    $batches = safeQueryAll($pdo, "SELECT id, batch_name, batch_code, batch_type, current_birds, placement_date FROM batches WHERE status IN ('active','completed','sold') ORDER BY placement_date DESC");
}
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Feeding Program</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Know how much feed your birds need at each age. See what they actually ate. Get your FCR (Feed Conversion Ratio).</p>
    </div>
</div>

<!-- Tab Bar -->
<div style="display:flex;gap:4px;background:#f1f5f9;padding:5px;border-radius:10px;margin-bottom:24px;">
    <a href="?tab=standards" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;font-weight:600;font-size:0.86rem;<?= $tab==='standards'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>"><i data-lucide="book" style="width:15px;height:15px;"></i> Feeding Standards</a>
    <a href="?tab=allocations" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;font-weight:600;font-size:0.86rem;<?= $tab==='allocations'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>"><i data-lucide="utensils" style="width:15px;height:15px;"></i> What Birds Ate</a>
    <a href="?tab=fcr" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;font-weight:600;font-size:0.86rem;<?= $tab==='fcr'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>"><i data-lucide="bar-chart-3" style="width:15px;height:15px;"></i> FCR Report</a>
</div>

<?php if ($tab === 'standards'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">How much should each bird eat per day?</h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Pre-loaded with standard feeding programs. Edit as needed for your breed.</p>
        </div>
        <button class="btn btn-primary" onclick="openStandardModal()"><i data-lucide="plus" style="width:15px;height:15px;"></i> Add Week</button>
    </div>

    <div style="display:flex;gap:8px;margin-bottom:14px;">
        <select class="admin-form-control" id="std-type" onchange="loadStandards()" style="max-width:200px;">
            <option value="layer">Layers</option>
            <option value="broiler">Broilers</option>
            <option value="kienyeji">Kienyeji</option>
            <option value="dual_purpose">Dual Purpose</option>
        </select>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr>
                <th>Week</th>
                <th>Feed per bird per day (grams)</th>
                <th>Feed per bird per week (kg)</th>
                <th>For 1000 birds (kg/day)</th>
                <th>Feed Type</th>
                <th>Actions</th>
            </tr></thead>
            <tbody id="standards-body">
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($tab === 'allocations'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">What Birds Ate</h3>
        <div style="display:flex;gap:8px;">
            <a href="/Backend/api/export.php?module=feed_allocations" class="btn btn-outline"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
            <button class="btn btn-primary" onclick="openAllocationModal()"><i data-lucide="plus" style="width:15px;height:15px;"></i> Record Feeding</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr>
                <th>Date</th><th>Batch</th><th>Feed Type</th>
                <th>Kilograms Fed</th><th>Notes</th><th>Actions</th>
            </tr></thead>
            <tbody id="alloc-body">
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php else: /* FCR */ ?>
<div class="admin-card">
    <h3 style="margin:0 0 8px;font-family:'Outfit',sans-serif;font-size:1.1rem;">FCR Report (How efficient is your feeding?)</h3>
    <p style="margin:0 0 18px;color:#64748b;font-size:0.85rem;"><strong>FCR = Total feed (kg) ÷ Total meat or eggs produced (kg)</strong>. Lower is better. Broilers: 1.5-1.8 is good. Layers: aim for 2.0-2.2 kg feed per kg eggs.</p>

    <div class="admin-form-group" style="max-width:420px;margin-bottom:18px;">
        <label class="admin-form-label">Choose a batch</label>
        <select class="admin-form-control" id="fcr-batch" onchange="loadFcr()">
            <option value="">Choose batch...</option>
            <?php foreach ($batches as $b): ?>
                <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['batch_name'] . ' (' . $b['batch_code'] . ') - ' . $b['batch_type'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="fcr-result" style="display:none;">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
            <div class="stat-card"><div class="stat-card-info"><small>Total Feed</small><strong id="fcr-feed">—</strong></div><div class="stat-card-icon accent"><i data-lucide="wheat" style="width:22px;height:22px;"></i></div></div>
            <div class="stat-card"><div class="stat-card-info"><small>Total Eggs</small><strong id="fcr-eggs">—</strong></div><div class="stat-card-icon info"><i data-lucide="egg" style="width:22px;height:22px;"></i></div></div>
            <div class="stat-card"><div class="stat-card-info"><small>Total Meat (kg)</small><strong id="fcr-meat">—</strong></div><div class="stat-card-icon" style="background:#fef3c7;color:#d97706;"><i data-lucide="drumstick" style="width:22px;height:22px;"></i></div></div>
            <div class="stat-card"><div class="stat-card-info"><small>FCR</small><strong id="fcr-ratio" style="font-size:1.6rem;">—</strong><small id="fcr-grade" style="display:block;margin-top:4px;"></small></div><div class="stat-card-icon" style="background:#dcfce7;color:#16a34a;"><i data-lucide="bar-chart-3" style="width:22px;height:22px;"></i></div></div>
        </div>
        <div class="admin-card" style="background:#f8fafc;border:1px solid #e2e8f0;">
            <h4 style="margin:0 0 10px;font-family:'Outfit',sans-serif;">What this means</h4>
            <p id="fcr-explanation" style="margin:0;color:#475569;font-size:0.9rem;line-height:1.6;"></p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Standard Modal -->
<div id="std-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:420px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add Feeding Standard</h3>
        <form id="std-form">
            <input type="hidden" id="s-id">
            <div class="admin-form-group"><label class="admin-form-label">Bird Type</label>
                <select class="admin-form-control" id="s-type">
                    <option value="layer">Layers</option>
                    <option value="broiler">Broilers</option>
                    <option value="kienyeji">Kienyeji</option>
                    <option value="dual_purpose">Dual Purpose</option>
                </select>
            </div>
            <div class="admin-form-group"><label class="admin-form-label">Week Number *</label><input class="admin-form-control" type="number" id="s-week" min="1" max="80" required></div>
            <div class="admin-form-group"><label class="admin-form-label">Grams per bird per day *</label><input class="admin-form-control" type="number" step="0.01" id="s-grams" required></div>
            <div class="admin-form-group"><label class="admin-form-label">Feed Type</label><input class="admin-form-control" id="s-feed" placeholder="e.g. Growers Mash"></div>
            <div class="admin-form-group"><label class="admin-form-label">Notes</label><input class="admin-form-control" id="s-notes"></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('std-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Allocation Modal -->
<div id="alloc-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 8px;font-family:'Outfit',sans-serif;">Record Feeding</h3>
        <p style="margin:0 0 18px;color:#64748b;font-size:0.85rem;">How much feed did you give the birds today?</p>
        <form id="alloc-form">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Batch *</label>
                    <select class="admin-form-control" id="al-batch" required>
                        <option value="">Choose batch...</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['batch_name'] . ' (' . $b['batch_code'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" id="al-date" required value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Kilograms Fed *</label><input class="admin-form-control" type="number" step="0.001" id="al-kg" required min="0.001"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Feed Type</label><input class="admin-form-control" id="al-feed" placeholder="e.g. Growers Mash"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><input class="admin-form-control" id="al-notes"></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('alloc-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
const currentTab = '<?= $tab ?>';

function openStandardModal(d) {
    document.getElementById('s-id').value = d?.id || '';
    document.getElementById('s-type').value = d?.bird_type || 'layer';
    document.getElementById('s-week').value = d?.week_number || '';
    document.getElementById('s-grams').value = d?.feed_per_bird_per_day_grams || '';
    document.getElementById('s-feed').value = d?.feed_type || '';
    document.getElementById('s-notes').value = d?.notes || '';
    document.getElementById('std-modal').style.display = 'flex';
}

document.getElementById('std-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('id', document.getElementById('s-id').value);
    fd.append('bird_type', document.getElementById('s-type').value);
    fd.append('week_number', document.getElementById('s-week').value);
    fd.append('feed_per_bird_per_day_grams', document.getElementById('s-grams').value);
    fd.append('feed_type', document.getElementById('s-feed').value);
    fd.append('notes', document.getElementById('s-notes').value);
    const res = await fetch('/Backend/api/admin_business.php?action=save_feeding_standard', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { document.getElementById('std-modal').style.display='none'; loadStandards(); }
    else alert('Error: ' + r.message);
});

async function loadStandards() {
    const type = document.getElementById('std-type')?.value || 'layer';
    const tbody = document.getElementById('standards-body');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch('/Backend/api/admin_business.php?action=list_feeding_standards');
        const r = await res.json();
        let data = (r.data || []).filter(s => s.bird_type === type);
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">No standards for this type yet.</td></tr>'; return; }
        tbody.innerHTML = data.map(s => `<tr>
            <td><strong>Week ${s.week_number}</strong></td>
            <td>${parseFloat(s.feed_per_bird_per_day_grams).toFixed(1)} g</td>
            <td>${(parseFloat(s.feed_per_bird_per_day_grams) * 7 / 1000).toFixed(2)} kg</td>
            <td>${(parseFloat(s.feed_per_bird_per_day_grams) * 1000 / 1000).toFixed(1)} kg</td>
            <td>${escapeHtml(s.feed_type||'—')}</td>
            <td>
                <button class="btn btn-trans btn-sm" onclick='openStandardModal(${JSON.stringify(s)})'><i data-lucide="pencil" style="width:13px;height:13px;"></i></button>
            </td>
        </tr>`).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (e) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#dc2626;">Network error</td></tr>'; }
}

function openAllocationModal() { document.getElementById('alloc-modal').style.display = 'flex'; }

document.getElementById('alloc-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('batch_id', document.getElementById('al-batch').value);
    fd.append('allocation_date', document.getElementById('al-date').value);
    fd.append('kg_fed', document.getElementById('al-kg').value);
    fd.append('feed_type', document.getElementById('al-feed').value);
    fd.append('notes', document.getElementById('al-notes').value);
    const res = await fetch('/Backend/api/admin_business.php?action=add_feed_allocation', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { alert('Feeding recorded'); document.getElementById('alloc-modal').style.display='none'; document.getElementById('alloc-form').reset(); document.getElementById('al-date').value = new Date().toISOString().split('T')[0]; loadAllocations(); }
    else alert('Error: ' + r.message);
});

async function loadAllocations() {
    const tbody = document.getElementById('alloc-body');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch('/Backend/api/admin_business.php?action=list_feed_allocations');
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        const data = r.data || [];
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">No feedings recorded yet.</td></tr>'; return; }
        tbody.innerHTML = data.map(a => `<tr>
            <td>${a.allocation_date}</td>
            <td><strong>${escapeHtml(a.batch_name||'—')}</strong></td>
            <td>${escapeHtml(a.feed_type||'—')}</td>
            <td><strong>${parseFloat(a.kg_fed).toFixed(2)} kg</strong></td>
            <td>${escapeHtml(a.notes||'')}</td>
            <td></td>
        </tr>`).join('');
    } catch (e) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#dc2626;">Network error</td></tr>'; }
}

async function loadFcr() {
    const bid = document.getElementById('fcr-batch').value;
    if (!bid) { document.getElementById('fcr-result').style.display='none'; return; }
    try {
        const res = await fetch('/Backend/api/admin_business.php?action=fcr_report&batch_id=' + bid);
        const r = await res.json();
        if (!r.success) { alert(r.message); return; }
        const d = r.data;
        document.getElementById('fcr-feed').textContent = d.total_feed_kg.toFixed(1) + ' kg';
        document.getElementById('fcr-eggs').textContent = d.total_eggs.toLocaleString();
        document.getElementById('fcr-meat').textContent = d.total_meat_kg.toFixed(1) + ' kg';
        const ratio = d.fcr;
        let grade = 'Average';
        let gradeColor = '#d97706';
        let explanation = '';
        if (d.batch.batch_type === 'broiler') {
            if (ratio > 0 && ratio <= 1.6) { grade = 'Excellent'; gradeColor = '#16a34a'; explanation = 'FCR of ' + ratio + ' is excellent for broilers. You are using feed very efficiently. Keep it up!'; }
            else if (ratio > 0 && ratio <= 1.9) { grade = 'Good'; gradeColor = '#16a34a'; explanation = 'FCR of ' + ratio + ' is good for broilers. Industry standard is 1.6-1.9.'; }
            else if (ratio > 0 && ratio <= 2.2) { grade = 'Average'; gradeColor = '#d97706'; explanation = 'FCR of ' + ratio + ' is average. Try to improve feed quality and reduce mortality.'; }
            else { grade = 'Needs Work'; gradeColor = '#dc2626'; explanation = 'FCR of ' + ratio + ' is high. Look at feed quality, mortality, and water intake.'; }
        } else {
            if (ratio > 0 && ratio <= 2.0) { grade = 'Excellent'; gradeColor = '#16a34a'; explanation = 'FCR of ' + ratio + ' is excellent for layers. Very efficient feed conversion.'; }
            else if (ratio > 0 && ratio <= 2.4) { grade = 'Good'; gradeColor = '#16a34a'; explanation = 'FCR of ' + ratio + ' is good. You are in a healthy range.'; }
            else { grade = 'Needs Work'; gradeColor = '#d97706'; explanation = 'FCR of ' + ratio + ' is high. Check for feed wastage, disease, or poor quality feed.'; }
        }
        document.getElementById('fcr-ratio').textContent = ratio > 0 ? ratio.toFixed(2) : '—';
        document.getElementById('fcr-ratio').style.color = gradeColor;
        document.getElementById('fcr-grade').textContent = grade;
        document.getElementById('fcr-grade').style.color = gradeColor;
        document.getElementById('fcr-explanation').textContent = explanation;
        document.getElementById('fcr-result').style.display = 'block';
    } catch (e) { alert('Failed to load FCR'); }
}

function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

document.addEventListener('DOMContentLoaded', () => {
    if (currentTab === 'standards') loadStandards();
    if (currentTab === 'allocations') loadAllocations();
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
