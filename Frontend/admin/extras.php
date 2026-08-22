<?php
/**
 * Admin — Egg Losses & Quality Tests
 * Two simple tracking modules in one place.
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
$page_title = 'Egg Losses & Quality Tests - Admin';
include __DIR__ . '/includes/admin_header.php';

$pdo = getDB();
$tab = $_GET['tab'] ?? 'losses';
$validTabs = ['losses','quality'];
if (!in_array($tab, $validTabs, true)) $tab = 'losses';

$batches = [];
$materials = [];
if ($pdo) {
    $batches = safeQueryAll($pdo, "SELECT id, batch_name, batch_code FROM batches ORDER BY batch_name");
    $materials = safeQueryAll($pdo, "SELECT id, material_name, unit FROM raw_materials ORDER BY material_name");
}
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Egg Losses & Quality Tests</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Track eggs lost (broken, stolen, fed to animals) and quality tests on raw materials (moisture, aflatoxin, etc.)</p>
    </div>
</div>

<div style="display:flex;gap:4px;background:#f1f5f9;padding:5px;border-radius:10px;margin-bottom:24px;">
    <a href="?tab=losses" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;font-weight:600;font-size:0.86rem;<?= $tab==='losses'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>"><i data-lucide="alert-circle" style="width:15px;height:15px;"></i> Egg Losses</a>
    <a href="?tab=quality" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;font-weight:600;font-size:0.86rem;<?= $tab==='quality'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>"><i data-lucide="flask-conical" style="width:15px;height:15px;"></i> Quality Tests</a>
</div>

<?php if ($tab === 'losses'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Egg Loss Log</h3>
        <div style="display:flex;gap:8px;">
            <a href="/Backend/api/export.php?module=egg_losses" class="btn btn-outline"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
            <button class="btn btn-primary" onclick="openLossModal()"><i data-lucide="plus" style="width:15px;height:15px;"></i> Record Loss</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Batch</th><th>Type</th><th>Where it happened</th><th>Quantity</th><th>Value (KES)</th><th>Reason</th></tr></thead>
            <tbody id="loss-body">
                <tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Material Quality Tests</h3>
        <div style="display:flex;gap:8px;">
            <a href="/Backend/api/export.php?module=quality_tests" class="btn btn-outline"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
            <button class="btn btn-primary" onclick="openQualityModal()"><i data-lucide="plus" style="width:15px;height:15px;"></i> New Test</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Material</th><th>Test</th><th>Result</th><th>Result</th><th>Tested By</th><th>Notes</th></tr></thead>
            <tbody id="q-body">
                <tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Loss Modal -->
<div id="loss-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Record Egg Loss</h3>
        <form id="loss-form">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" id="lo-date" required value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Batch</label>
                    <select class="admin-form-control" id="lo-batch">
                        <option value="">— Optional —</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['batch_name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Type *</label>
                    <select class="admin-form-control" id="lo-type" required>
                        <option value="broken">Broken</option>
                        <option value="cracked">Cracked</option>
                        <option value="stolen">Stolen / missing</option>
                        <option value="eaten_staff">Eaten by staff</option>
                        <option value="fed_to_animals">Fed to animals</option>
                        <option value="expired">Expired (old)</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Where it happened *</label>
                    <select class="admin-form-control" id="lo-stage" required>
                        <option value="collection">During collection</option>
                        <option value="transport">On route (transport)</option>
                        <option value="storage">At storage / farm</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Quantity (eggs) *</label><input class="admin-form-control" type="number" id="lo-qty" required min="1"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Value (KES, optional)</label><input class="admin-form-control" type="number" step="0.01" id="lo-value" min="0" value="0"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Reason / notes</label><input class="admin-form-control" id="lo-reason"></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('loss-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Quality Modal -->
<div id="quality-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 22px;font-family:'Outfit',sans-serif;">New Quality Test</h3>
        <form id="quality-form">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" id="q-date" required value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Material *</label>
                    <select class="admin-form-control" id="q-mat" required>
                        <option value="">Choose material...</option>
                        <?php foreach ($materials as $m): ?>
                            <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['material_name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Test Type *</label>
                    <select class="admin-form-control" id="q-type" required>
                        <option value="moisture">Moisture content</option>
                        <option value="aflatoxin">Aflatoxin</option>
                        <option value="purity">Purity</option>
                        <option value="pesticide">Pesticide residue</option>
                        <option value="visual">Visual inspection</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Result</label>
                    <select class="admin-form-control" id="q-pf">
                        <option value="pass">Pass</option>
                        <option value="borderline">Borderline</option>
                        <option value="fail">Fail</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Value</label><input class="admin-form-control" id="q-val" placeholder="e.g. 12%"></div>
                <div class="admin-form-group"><label class="admin-form-label">Unit</label><input class="admin-form-control" id="q-unit" placeholder="%"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Tested by</label><input class="admin-form-control" id="q-tester" placeholder="Lab or staff name"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" id="q-notes" rows="2"></textarea></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('quality-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
const currentTab = '<?= $tab ?>';

async function loadLosses() {
    const tbody = document.getElementById('loss-body');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch('/Backend/api/admin_business.php?action=list_egg_losses');
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        const data = r.data || [];
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">No losses recorded yet.</td></tr>'; return; }
        const stageMap = {collection:'During collection', transport:'On route (transport)', storage:'At storage / farm', other:'Other'};
        const stagePill = {collection:'badge-pill-warning', transport:'badge-pill-danger', storage:'badge-pill-info', other:'badge-pill-warning'};
        tbody.innerHTML = data.map(l => `<tr>
            <td>${l.loss_date}</td>
            <td>${escapeHtml(l.batch_name||'—')}</td>
            <td><span class="badge-pill badge-pill-warning">${l.loss_type.replace('_',' ')}</span></td>
            <td><span class="badge-pill ${stagePill[l.stage]||'badge-pill-warning'}">${stageMap[l.stage]||l.stage||'—'}</span></td>
            <td><strong>${parseInt(l.quantity).toLocaleString()}</strong> eggs</td>
            <td>KES ${parseFloat(l.estimated_value||0).toFixed(2)}</td>
            <td>${escapeHtml(l.reason||'')}</td>
        </tr>`).join('');
    } catch (e) { tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#dc2626;">Network error</td></tr>'; }
}

function openLossModal() { document.getElementById('loss-modal').style.display = 'flex'; }

document.getElementById('loss-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('loss_date', document.getElementById('lo-date').value);
    fd.append('batch_id', document.getElementById('lo-batch').value);
    fd.append('loss_type', document.getElementById('lo-type').value);
    fd.append('stage', document.getElementById('lo-stage').value);
    fd.append('quantity', document.getElementById('lo-qty').value);
    fd.append('estimated_value', document.getElementById('lo-value').value);
    fd.append('reason', document.getElementById('lo-reason').value);
    const res = await fetch('/Backend/api/admin_business.php?action=add_egg_loss', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { document.getElementById('loss-modal').style.display='none'; loadLosses(); }
    else alert('Error: ' + r.message);
});

async function loadQuality() {
    const tbody = document.getElementById('q-body');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    try {
        const res = await fetch('/Backend/api/admin_business.php?action=list_quality_tests');
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        const data = r.data || [];
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">No tests recorded yet.</td></tr>'; return; }
        tbody.innerHTML = data.map(q => {
            const pf = q.pass_fail==='pass'?'badge-pill-success':(q.pass_fail==='fail'?'badge-pill-danger':'badge-pill-warning');
            return `<tr>
                <td>${q.test_date}</td>
                <td><strong>${escapeHtml(q.material_name||'—')}</strong></td>
                <td>${q.test_type.replace('_',' ')}</td>
                <td>${escapeHtml(q.result_value||'—')} ${escapeHtml(q.unit||'')}</td>
                <td><span class="badge-pill ${pf}">${q.pass_fail}</span></td>
                <td>${escapeHtml(q.tested_by||'—')}</td>
                <td>${escapeHtml(q.notes||'')}</td>
            </tr>`;
        }).join('');
    } catch (e) { tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#dc2626;">Network error</td></tr>'; }
}

function openQualityModal() { document.getElementById('quality-modal').style.display = 'flex'; }

document.getElementById('quality-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('test_date', document.getElementById('q-date').value);
    fd.append('material_id', document.getElementById('q-mat').value);
    fd.append('test_type', document.getElementById('q-type').value);
    fd.append('pass_fail', document.getElementById('q-pf').value);
    fd.append('result_value', document.getElementById('q-val').value);
    fd.append('unit', document.getElementById('q-unit').value);
    fd.append('tested_by', document.getElementById('q-tester').value);
    fd.append('notes', document.getElementById('q-notes').value);
    const res = await fetch('/Backend/api/admin_business.php?action=add_quality_test', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { document.getElementById('quality-modal').style.display='none'; loadQuality(); }
    else alert('Error: ' + r.message);
});

function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

document.addEventListener('DOMContentLoaded', () => {
    if (currentTab === 'losses') loadLosses();
    if (currentTab === 'quality') loadQuality();
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
