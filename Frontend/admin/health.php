<?php
/**
 * Admin — Health & Veterinary Module
 * Sub-tabs: All Records | Vaccinations | Mortality | Treatments | Checkups
 * Includes: vaccine scheduling, mortality logging, treatment tracking
 */
declare(strict_types=1);
header('Location: /Frontend/admin/hub_operations.php?tab=health', true, 301);
exit;
$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();
$page_title = 'Health & Veterinary - Admin';
include __DIR__ . '/includes/admin_header.php';

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager'], true)) {
    header('Location: /wangariadmin');
    exit;
}

$pdo = getDB();
$tab = $_GET['tab'] ?? 'all';
$validTabs = ['all','vaccination','mortality','treatment','checkup','deworming','vitamins','antibiotic','observation','schedule'];
if (!in_array($tab, $validTabs, true)) $tab = 'all';

$batches = [];
$flocks = [];
if ($pdo) {
    $batches = safeQueryAll($pdo, "SELECT id, batch_name, batch_code FROM batches WHERE status='active' ORDER BY batch_name");
    $flocks = safeQueryAll($pdo, "SELECT id, flock_name FROM flocks WHERE status='active' ORDER BY flock_name");
}

$tabs = [
    'all'         => ['icon'=>'activity',     'label'=>'All Records'],
    'vaccination' => ['icon'=>'syringe',      'label'=>'Vaccinations'],
    'mortality'   => ['icon'=>'skull',        'label'=>'Mortality'],
    'treatment'   => ['icon'=>'pill',         'label'=>'Treatments'],
    'checkup'     => ['icon'=>'stethoscope',  'label'=>'Checkups'],
    'schedule'    => ['icon'=>'calendar-clock','label'=>'Schedule'],
];
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Health & Veterinary</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Track vaccinations, mortality, treatments, and checkups. Plan and schedule recurring health activities.</p>
    </div>
    <button class="btn btn-primary" onclick="openHealthModal()">
        <i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Log Health Event
    </button>
</div>

<!-- KPIs -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
    <div class="stat-card"><div class="stat-card-info"><small>Vaccinations This Month</small><strong id="hp-kpi-vax">—</strong></div><div class="stat-card-icon accent"><i data-lucide="syringe" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Mortality This Week</small><strong id="hp-kpi-mort" style="color:#dc2626;">—</strong></div><div class="stat-card-icon" style="background:#fee2e2;color:#dc2626;"><i data-lucide="skull" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Treatments Logged</small><strong id="hp-kpi-treat">—</strong></div><div class="stat-card-icon info"><i data-lucide="pill" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Scheduled Upcoming</small><strong id="hp-kpi-sched">—</strong></div><div class="stat-card-icon" style="background:#fef3c7;color:#d97706;"><i data-lucide="calendar-clock" style="width:22px;height:22px;"></i></div></div>
</div>

<!-- Tab Bar -->
<div style="display:flex;gap:4px;background:#f1f5f9;padding:5px;border-radius:10px;margin-bottom:24px;overflow-x:auto;scrollbar-width:none;">
<?php foreach ($tabs as $key => $info): ?>
    <a href="?tab=<?= $key ?>" style="display:flex;align-items:center;gap:7px;padding:9px 14px;border-radius:7px;text-decoration:none;white-space:nowrap;font-weight:600;font-size:0.84rem;transition:all 0.18s;<?= $tab===$key ? 'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);' : 'color:#64748b;' ?>">
        <i data-lucide="<?= $info['icon'] ?>" style="width:15px;height:15px;"></i><?= $info['label'] ?>
    </a>
<?php endforeach; ?>
</div>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Health Records</h3>
        <a href="/Backend/api/export.php?module=health" class="btn btn-outline" id="health-export"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Subject</th>
                    <th>Product/Vaccine</th>
                    <th>Birds</th>
                    <th>Cost</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="health-body">
                <tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">Loading health records...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Health Modal -->
<div id="health-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;overflow-y:auto;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:680px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;margin:20px;">
        <h3 id="health-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Log Health Event</h3>
        <form id="health-form">
            <input type="hidden" id="hp-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group">
                    <label class="admin-form-label">Date *</label>
                    <input class="admin-form-control" type="date" id="hp-date" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Record Type *</label>
                    <select class="admin-form-control" id="hp-type" required>
                        <option value="vaccination">Vaccination</option>
                        <option value="treatment">Treatment</option>
                        <option value="mortality">Mortality</option>
                        <option value="checkup">Checkup</option>
                        <option value="deworming">Deworming</option>
                        <option value="vitamins">Vitamins/Supplements</option>
                        <option value="antibiotic">Antibiotic</option>
                        <option value="observation">Observation</option>
                    </select>
                </div>
                <div class="admin-form-group" style="grid-column:span 2">
                    <label class="admin-form-label">Subject *</label>
                    <input class="admin-form-control" id="hp-subject" required placeholder="e.g. Newcastle vaccine, Marek's disease, etc.">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Batch</label>
                    <select class="admin-form-control" id="hp-batch">
                        <option value="">— Select batch —</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['batch_name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Flock</label>
                    <select class="admin-form-control" id="hp-flock">
                        <option value="">— Select flock —</option>
                        <?php foreach ($flocks as $f): ?>
                            <option value="<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['flock_name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Product/Vaccine</label>
                    <input class="admin-form-control" id="hp-product" placeholder="e.g. Newcastle Disease Vaccine (LaSota)">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Dosage</label>
                    <input class="admin-form-control" id="hp-dosage" placeholder="e.g. 1 drop per bird">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Route</label>
                    <select class="admin-form-control" id="hp-route">
                        <option value="oral">Oral (in water)</option>
                        <option value="injection">Injection</option>
                        <option value="spray">Spray</option>
                        <option value="eye_drop">Eye Drop</option>
                        <option value="wing_web">Wing Web</option>
                        <option value="feed">In Feed</option>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Birds Treated</label>
                    <input class="admin-form-control" type="number" id="hp-birds" min="0" value="0">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Mortality Count</label>
                    <input class="admin-form-control" type="number" id="hp-mort" min="0" value="0">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Mortality Reason</label>
                    <input class="admin-form-control" id="hp-reason" placeholder="e.g. respiratory infection">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Vet / Officer</label>
                    <input class="admin-form-control" id="hp-vet" placeholder="Vet name or staff">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Next Due Date</label>
                    <input class="admin-form-control" type="date" id="hp-next">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Cost (KES)</label>
                    <input class="admin-form-control" type="number" step="0.01" id="hp-cost" min="0" value="0">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Status</label>
                    <select class="admin-form-control" id="hp-status">
                        <option value="completed">Completed</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="missed">Missed</option>
                    </select>
                </div>
                <div class="admin-form-group" style="grid-column:span 2">
                    <label class="admin-form-label">Notes</label>
                    <textarea class="admin-form-control" id="hp-notes" rows="2"></textarea>
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeHealthModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;" id="hp-submit-btn">
                    <i data-lucide="save" style="width:15px;height:15px;"></i> Save Record
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const CSRF = window.WangariAdmin?.csrfToken || '';
let allHealth = [];
let currentTab = '<?= $tab ?>';

async function loadHealth() {
    const tbody = document.getElementById('health-body');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>';
    let typeParam = '';
    if (currentTab !== 'all' && currentTab !== 'schedule') {
        typeParam = '&type=' + currentTab;
    } else if (currentTab === 'schedule') {
        typeParam = '&type=vaccination';
    }
    try {
        const res = await fetch('/Backend/api/admin_poultry_v2.php?action=get_health_records' + typeParam);
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        allHealth = r.data || [];
        renderHealth();
        updateKpis();
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#dc2626;">Network error</td></tr>';
    }
}

function renderHealth() {
    const tbody = document.getElementById('health-body');
    if (!allHealth.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">No records yet. Click "Log Health Event" to start.</td></tr>';
        return;
    }
    const typeColors = {
        vaccination: 'badge-pill-success',
        mortality: 'badge-pill-danger',
        treatment: 'badge-pill-warning',
        checkup: 'badge-pill-info',
        deworming: 'badge-pill-info',
        vitamins: 'badge-pill-info',
        antibiotic: 'badge-pill-warning',
        observation: 'badge-pill-info'
    };
    tbody.innerHTML = allHealth.map(h => {
        const tc = typeColors[h.record_type] || 'badge-pill-info';
        const sc = h.status==='completed'?'badge-pill-success':(h.status==='scheduled'?'badge-pill-warning':(h.status==='missed'?'badge-pill-danger':'badge-pill-info'));
        return `<tr>
            <td>${h.record_date}</td>
            <td><span class="badge-pill ${tc}">${h.record_type}</span></td>
            <td><strong>${escapeHtml(h.subject)}</strong>${h.notes?'<br><small style="color:#64748b;">'+escapeHtml(h.notes)+'</small>':''}</td>
            <td>${escapeHtml(h.product_name||h.vaccine_name||'—')}</td>
            <td>${parseInt(h.birds_treated||0).toLocaleString()}${parseInt(h.mortality_count||0)>0?` <span style="color:#dc2626;">(−${h.mortality_count})</span>`:''}</td>
            <td>KES ${parseFloat(h.cost||0).toFixed(2)}</td>
            <td><span class="badge-pill ${sc}">${h.status}</span></td>
            <td><div class="tbl-actions">
                <button class="btn btn-trans btn-sm" onclick='openHealthModal(${JSON.stringify(h)})'><i data-lucide="pencil" style="width:13px;height:13px;"></i></button>
                <button class="btn btn-danger btn-sm" onclick="deleteHealth(${h.id})"><i data-lucide="trash-2" style="width:13px;height:13px;"></i></button>
            </div></td>
        </tr>`;
    }).join('');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function updateKpis() {
    const now = new Date();
    const monthStart = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
    const weekAgo = new Date(now.getTime() - 7*86400000).toISOString().split('T')[0];
    const vaxMonth = allHealth.filter(h => h.record_type==='vaccination' && h.record_date >= monthStart).length;
    const mortWeek = allHealth.filter(h => h.record_type==='mortality' && h.record_date >= weekAgo).reduce((s,h)=>s+parseInt(h.mortality_count||0),0);
    const treatTotal = allHealth.filter(h => ['treatment','antibiotic','vitamins','deworming'].includes(h.record_type)).length;
    const upcoming = allHealth.filter(h => h.status==='scheduled' && h.next_due_date && h.next_due_date >= now.toISOString().split('T')[0]).length;
    document.getElementById('hp-kpi-vax').textContent = vaxMonth;
    document.getElementById('hp-kpi-mort').textContent = mortWeek;
    document.getElementById('hp-kpi-treat').textContent = treatTotal;
    document.getElementById('hp-kpi-sched').textContent = upcoming;
}

function openHealthModal(d) {
    document.getElementById('health-modal-title').textContent = d?.id ? 'Edit Health Record' : 'Log Health Event';
    document.getElementById('hp-id').value = d?.id || '';
    document.getElementById('hp-date').value = d?.record_date || new Date().toISOString().split('T')[0];
    document.getElementById('hp-type').value = d?.record_type || 'vaccination';
    document.getElementById('hp-subject').value = d?.subject || '';
    document.getElementById('hp-product').value = d?.product_name || d?.vaccine_name || '';
    document.getElementById('hp-dosage').value = d?.dosage || '';
    document.getElementById('hp-route').value = d?.route || 'oral';
    document.getElementById('hp-birds').value = d?.birds_treated || 0;
    document.getElementById('hp-mort').value = d?.mortality_count || 0;
    document.getElementById('hp-reason').value = d?.mortality_reason || '';
    document.getElementById('hp-vet').value = d?.vet_name || '';
    document.getElementById('hp-next').value = d?.next_due_date || '';
    document.getElementById('hp-cost').value = d?.cost || 0;
    document.getElementById('hp-status').value = d?.status || 'completed';
    document.getElementById('hp-notes').value = d?.notes || '';
    document.getElementById('hp-batch').value = d?.batch_id || '';
    document.getElementById('hp-flock').value = d?.flock_id || '';
    document.getElementById('health-modal').style.display = 'flex';
}
function closeHealthModal() { document.getElementById('health-modal').style.display = 'none'; }

document.getElementById('health-form').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('hp-submit-btn');
    btn.disabled = true; btn.textContent = 'Saving...';
    const fd = new FormData();
    fd.append('id', document.getElementById('hp-id').value);
    fd.append('record_date', document.getElementById('hp-date').value);
    fd.append('record_type', document.getElementById('hp-type').value);
    fd.append('subject', document.getElementById('hp-subject').value);
    fd.append('vaccine_name', document.getElementById('hp-product').value);
    fd.append('product_name', document.getElementById('hp-product').value);
    fd.append('dosage', document.getElementById('hp-dosage').value);
    fd.append('route', document.getElementById('hp-route').value);
    fd.append('birds_treated', document.getElementById('hp-birds').value);
    fd.append('mortality_count', document.getElementById('hp-mort').value);
    fd.append('mortality_reason', document.getElementById('hp-reason').value);
    fd.append('vet_name', document.getElementById('hp-vet').value);
    fd.append('next_due_date', document.getElementById('hp-next').value);
    fd.append('cost', document.getElementById('hp-cost').value);
    fd.append('status', document.getElementById('hp-status').value);
    fd.append('notes', document.getElementById('hp-notes').value);
    fd.append('batch_id', document.getElementById('hp-batch').value);
    fd.append('flock_id', document.getElementById('hp-flock').value);
    try {
        const res = await fetch('/Backend/api/admin_poultry_v2.php?action=save_health_record', {method:'POST', body:fd});
        const r = await res.json();
        if (r.success) { closeHealthModal(); loadHealth(); }
        else alert('Error: ' + (r.message||'Save failed'));
    } catch (e) { alert('Network error.'); }
    finally { btn.disabled = false; btn.innerHTML = '<i data-lucide="save" style="width:15px;height:15px;"></i> Save Record'; if(typeof lucide!=='undefined') lucide.createIcons(); }
});

async function deleteHealth(id) {
    if (!confirm('Delete this health record?')) return;
    const fd = new FormData(); fd.append('id', id);
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=delete_health_record', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) loadHealth(); else alert(r.message);
}

function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }

document.addEventListener('DOMContentLoaded', () => { loadHealth(); if(typeof lucide!=='undefined') lucide.createIcons(); });
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
