<?php
/**
 * Hub: Farm Operations, ALL content inline, no double-includes.
 * Tabs: Flocks | Production | Vaccinations | Animals | Health | Breeding | Herds
 */
declare(strict_types=1);
$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','stock_manager','sales_staff'], true)) {
    echo "<script>window.location.href='/wangariadmin';</script>"; exit;
}

$page_title = 'Farm Operations - Admin';
include __DIR__ . '/includes/admin_header.php';

$tab = $_GET['tab'] ?? 'flocks';
$validTabs = ['flocks','production','vaccinations','animals','health','breeding','herds'];
if (!in_array($tab, $validTabs, true)) $tab = 'flocks';

$pdo = getDB();
$message = ''; $error_message = '';

/* ── POST handlers (PHP forms only, not for API tabs) ─── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $postAction = $_POST['_action'] ?? '';

    if ($postAction === 'save_animal') {
        $id = (int)($_POST['id'] ?? 0);
        $fields = [$_POST['tag_id']??'',$_POST['name']??'',$_POST['species']??'',$_POST['breed']??'',$_POST['gender']??'',$_POST['dob']??'',$_POST['status']??'alive',$_POST['notes']??''];
        $fields = array_map('trim', $fields);
        try {
            if ($id > 0) {
                $pdo->prepare('UPDATE animals SET tag_id=?,name=?,species=?,breed=?,gender=?,date_of_birth=?,status=?,notes=? WHERE id=?')
                    ->execute(array_merge($fields, [$id]));
                $message = 'Animal updated.';
            } else {
                $pdo->prepare('INSERT INTO animals (tag_id,name,species,breed,gender,date_of_birth,status,notes) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute($fields);
                $message = 'Animal added.';
            }
        } catch(Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'animals';
    }

    if ($postAction === 'save_herd') {
        $id = (int)($_POST['id'] ?? 0);
        $n=$_POST['name']??''; $t=$_POST['type']??''; $l=$_POST['location']??''; $c=(int)($_POST['head_count']??0);
        try {
            $id > 0
                ? $pdo->prepare('UPDATE herds SET name=?,type=?,location=?,head_count=? WHERE id=?')->execute([$n,$t,$l,$c,$id])
                : $pdo->prepare('INSERT INTO herds (name,type,location,head_count) VALUES (?,?,?,?)')->execute([$n,$t,$l,$c]);
            $message = $id > 0 ? 'Herd updated.' : 'Herd created.';
        } catch(Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'herds';
    }

    if ($postAction === 'save_health') {
        $id=(int)($_POST['id']??0);
        $v=[$_POST['animal_id']??null,$_POST['record_date']??date('Y-m-d'),$_POST['diagnosis']??'',$_POST['treatment']??'',$_POST['vet_name']??'',(float)($_POST['cost']??0)?:(null),$_POST['notes']??''];
        try {
            $id > 0
                ? $pdo->prepare('UPDATE health_records SET animal_id=?,record_date=?,diagnosis=?,treatment=?,vet_name=?,cost=?,notes=? WHERE id=?')->execute(array_merge($v,[$id]))
                : $pdo->prepare('INSERT INTO health_records (animal_id,record_date,diagnosis,treatment,vet_name,cost,notes) VALUES (?,?,?,?,?,?,?)')->execute($v);
            $message = $id > 0 ? 'Health record updated.' : 'Health record logged.';
        } catch(Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'health';
    }

    if ($postAction === 'save_breeding') {
        $id=(int)($_POST['id']??0);
        $v=[$_POST['sire']??'',$_POST['dam']??'',$_POST['breeding_date']??date('Y-m-d'),$_POST['expected_birth']??null,$_POST['status']??'Pending',$_POST['notes']??''];
        try {
            $id > 0
                ? $pdo->prepare('UPDATE breeding_records SET sire=?,dam=?,breeding_date=?,expected_birth=?,status=?,notes=? WHERE id=?')->execute(array_merge($v,[$id]))
                : $pdo->prepare('INSERT INTO breeding_records (sire,dam,breeding_date,expected_birth,status,notes) VALUES (?,?,?,?,?,?)')->execute($v);
            $message = $id > 0 ? 'Breeding record updated.' : 'Breeding recorded.';
        } catch(Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'breeding';
    }
}

/* ── Load PHP-tab data ─── */
$animals = $herds = $healthRecs = $breedingRecs = $animalList = [];
if ($pdo) {
    try {
        if ($tab === 'animals')     $animals     = $pdo->query('SELECT * FROM animals ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
        if ($tab === 'herds')       $herds       = $pdo->query('SELECT * FROM herds ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
        if ($tab === 'health')      $healthRecs  = $pdo->query('SELECT hr.*, a.name AS aname, a.tag_id FROM health_records hr LEFT JOIN animals a ON hr.animal_id=a.id ORDER BY hr.record_date DESC')->fetchAll(PDO::FETCH_ASSOC);
        if ($tab === 'breeding')    $breedingRecs= $pdo->query('SELECT * FROM breeding_records ORDER BY breeding_date DESC')->fetchAll(PDO::FETCH_ASSOC);
        $animalList = $pdo->query("SELECT id, CONCAT(COALESCE(tag_id,''), ' - ', COALESCE(name,'?')) AS label FROM animals ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) { /* non-fatal */ }
}

$tabs = [
    'flocks'      => ['icon'=>'layers',        'label'=>'Flocks'],
    'production'  => ['icon'=>'egg',           'label'=>'Daily Production'],
    'vaccinations'=> ['icon'=>'syringe',       'label'=>'Vaccinations'],
    'animals'     => ['icon'=>'paw-print',     'label'=>'Animals'],
    'health'      => ['icon'=>'heart-pulse',   'label'=>'Health'],
    'breeding'    => ['icon'=>'dna',           'label'=>'Breeding'],
    'herds'       => ['icon'=>'users',         'label'=>'Herds / Pens'],
];
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Farm Operations</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Manage flocks, daily egg collection, vaccinations, animals, health records, and breeding.</p>
    </div>
</div>

<?php if ($message): ?>
<div style="padding:13px 18px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;color:#166534;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <i data-lucide="check-circle-2" style="width:18px;height:18px;"></i> <?= htmlspecialchars($message, ENT_QUOTES,'UTF-8') ?>
</div>
<?php endif; ?>
<?php if ($error_message): ?>
<div style="padding:13px 18px;background:#fee2e2;border:1px solid #fecaca;border-radius:8px;color:#b91c1c;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <i data-lucide="alert-circle" style="width:18px;height:18px;"></i> <?= htmlspecialchars($error_message, ENT_QUOTES,'UTF-8') ?>
</div>
<?php endif; ?>

<!-- Tab Bar -->
<div style="display:flex;gap:4px;background:#f1f5f9;padding:5px;border-radius:10px;margin-bottom:24px;overflow-x:auto;scrollbar-width:none;-ms-overflow-style:none;">
<?php foreach ($tabs as $key => $info): ?>
    <a href="?tab=<?= $key ?>" style="display:flex;align-items:center;gap:7px;padding:9px 14px;border-radius:7px;text-decoration:none;white-space:nowrap;font-weight:600;font-size:0.84rem;transition:all 0.18s;<?= $tab===$key ? 'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);' : 'color:#64748b;' ?>">
        <i data-lucide="<?= $info['icon'] ?>" style="width:15px;height:15px;"></i><?= $info['label'] ?>
    </a>
<?php endforeach; ?>
</div>

<?php /* ══════════════════ FLOCKS TAB (API-driven JS) ══════════════════ */ ?>
<?php if ($tab === 'flocks'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Flock Management</h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Add and manage your chicken flocks, track bird counts and status.</p>
        </div>
        <button class="btn btn-primary" onclick="openFlockModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Add Flock</button>
    </div>
    <!-- Summary KPIs -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
        <div class="stat-card"><div class="stat-card-info"><small>Total Flocks</small><strong id="kpi-flock-count">-</strong></div><div class="stat-card-icon"><i data-lucide="layers" style="width:22px;height:22px;"></i></div></div>
        <div class="stat-card"><div class="stat-card-info"><small>Live Birds</small><strong id="kpi-live-birds">-</strong></div><div class="stat-card-icon accent"><i data-lucide="bird" style="width:22px;height:22px;"></i></div></div>
        <div class="stat-card"><div class="stat-card-info"><small>Eggs Today</small><strong id="kpi-eggs-today">-</strong></div><div class="stat-card-icon info"><i data-lucide="egg" style="width:22px;height:22px;"></i></div></div>
        <div class="stat-card"><div class="stat-card-info"><small>Mortality This Week</small><strong id="kpi-mortality-week" style="color:#dc2626;">-</strong></div><div class="stat-card-icon" style="background:#fee2e2;color:#dc2626;"><i data-lucide="alert-triangle" style="width:22px;height:22px;"></i></div></div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Flock Name</th><th>Breed / Type</th><th>Birds</th><th>Eggs Today</th><th>Age (wks)</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="flocks-body"><tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">Loading flocks...</td></tr></tbody>
        </table>
    </div>
</div>

<!-- Flock Modal -->
<div id="flock-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 id="flock-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add Flock</h3>
        <form id="flock-form">
            <input type="hidden" id="flock-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Flock Name *</label><input class="admin-form-control" id="flock-name" placeholder="e.g. Layer Block A" required></div>
                <div class="admin-form-group"><label class="admin-form-label">Breed</label><input class="admin-form-control" id="flock-breed" placeholder="e.g. ISA Brown"></div>
                <div class="admin-form-group"><label class="admin-form-label">Flock Type</label>
                    <select class="admin-form-control" id="flock-type">
                        <option value="layer">Layer (Eggs)</option><option value="broiler">Broiler (Meat)</option><option value="kienyeji">Kienyeji</option><option value="dual_purpose">Dual Purpose</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Bird Count</label><input class="admin-form-control" type="number" id="flock-count" min="0" value="0"></div>
                <div class="admin-form-group"><label class="admin-form-label">Date Acquired</label><input class="admin-form-control" type="date" id="flock-date"></div>
                <div class="admin-form-group"><label class="admin-form-label">Location / Pen</label><input class="admin-form-control" id="flock-location" placeholder="e.g. Block B, Pen 2"></div>
                <div class="admin-form-group"><label class="admin-form-label">Status</label>
                    <select class="admin-form-control" id="flock-status"><option value="active">Active</option><option value="sold">Sold</option><option value="closed">Closed</option></select>
                </div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" id="flock-notes" rows="3"></textarea></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeFlockModal()"><i data-lucide="x" style="width:15px;height:15px;"></i> Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;" id="flock-submit-btn"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Flock</button>
            </div>
        </form>
    </div>
</div>

<script>
window.flocks_list = [];
const CSRF = window.WangariAdmin?.csrfToken || '';

async function loadFlocks() {
    setTbLoading('flocks-body', 7, 'Loading flocks...');
    try {
        const [flockRes, prodRes] = await Promise.all([
            fetch('/Backend/api/admin_poultry.php?action=get_flocks'),
            fetch('/Backend/api/admin_poultry.php?action=get_production')
        ]);
        const flockData = await flockRes.json();
        const prodData  = await prodRes.json().catch(()=>({success:false,data:[]}));

        if (!flockData.success) { setTbError('flocks-body',7,flockData.message||'Failed to load.'); return; }
        window.flocks_list = flockData.data;

        // Build today's egg map
        const today = new Date().toISOString().split('T')[0];
        const eggMap = {}, mortalityMap = {};
        (prodData.data||[]).forEach(p => {
            if (p.record_date === today) eggMap[p.flock_id] = (eggMap[p.flock_id]||0) + parseInt(p.eggs_collected||0);
            mortalityMap[p.flock_id] = (mortalityMap[p.flock_id]||0) + parseInt(p.mortality||0);
        });

        const activeFk = window.flocks_list.filter(f=>f.status==='active');
        document.getElementById('kpi-flock-count').textContent = activeFk.length;
        document.getElementById('kpi-live-birds').textContent  = activeFk.reduce((s,f)=>s+parseInt(f.current_count||0),0).toLocaleString();
        document.getElementById('kpi-eggs-today').textContent  = Object.values(eggMap).reduce((s,v)=>s+v,0).toLocaleString();
        document.getElementById('kpi-mortality-week').textContent = Object.values(mortalityMap).reduce((s,v)=>s+v,0);

        renderFlocks(eggMap);
    } catch(e) { setTbError('flocks-body',7,'Network error.'); console.error(e); }
}

function renderFlocks(eggMap={}) {
    const tbody = document.getElementById('flocks-body');
    if (!window.flocks_list.length) { tbody.innerHTML='<tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">No flocks yet. Click "Add Flock" to create one.</td></tr>'; return; }

    tbody.innerHTML = window.flocks_list.map(f => {
        const sc = f.status==='active'?'badge-pill-success':(f.status==='sold'?'badge-pill-warning':'badge-pill-danger');
        const ageWks = f.date_acquired ? Math.round((Date.now()-new Date(f.date_acquired).getTime())/(7*86400000)) : '-';
        const eggsToday = eggMap[f.id] || 0;
        return `<tr>
            <td><strong>${f.flock_name}</strong><br><small style="color:#64748b;">${f.location||'-'}</small></td>
            <td>${f.breed||'-'}<br><small style="color:#94a3b8;text-transform:capitalize;">${f.flock_type||'layer'}</small></td>
            <td><strong>${Number(f.current_count||0).toLocaleString()}</strong></td>
            <td><span style="color:${eggsToday>0?'#16a34a':'#94a3b8'};font-weight:700;">${eggsToday.toLocaleString()}</span></td>
            <td>${ageWks}</td>
            <td><span class="badge-pill ${sc}">${f.status}</span></td>
            <td><div class="tbl-actions">
                <button class="btn btn-trans btn-sm" onclick='openFlockModal(${JSON.stringify(f)})'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button>
                <button class="btn btn-danger btn-sm" onclick="deleteFlock(${f.id})"><i data-lucide="trash-2" style="width:13px;height:13px;"></i></button>
            </div></td>
        </tr>`;
    }).join('');
    if (typeof lucide!=='undefined') lucide.createIcons();
}

function openFlockModal(data) {
    const isEdit = data && data.id;
    document.getElementById('flock-modal-title').textContent = isEdit ? 'Edit Flock' : 'Add Flock';
    document.getElementById('flock-id').value       = isEdit ? data.id : '';
    document.getElementById('flock-name').value     = data?.flock_name || '';
    document.getElementById('flock-breed').value    = data?.breed || '';
    document.getElementById('flock-type').value     = data?.flock_type || 'layer';
    document.getElementById('flock-count').value    = data?.current_count || 0;
    document.getElementById('flock-date').value     = data?.date_acquired || '';
    document.getElementById('flock-location').value = data?.location || '';
    document.getElementById('flock-status').value   = data?.status || 'active';
    document.getElementById('flock-notes').value    = data?.notes || '';
    document.getElementById('flock-modal').style.display = 'flex';
}
function closeFlockModal(){ document.getElementById('flock-modal').style.display='none'; }

document.getElementById('flock-form').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('flock-submit-btn');
    btn.disabled = true; btn.textContent = 'Saving...';
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('id',          document.getElementById('flock-id').value);
    fd.append('flock_name',  document.getElementById('flock-name').value);
    fd.append('breed',       document.getElementById('flock-breed').value);
    fd.append('flock_type',  document.getElementById('flock-type').value);
    fd.append('current_count',document.getElementById('flock-count').value);
    fd.append('date_acquired',document.getElementById('flock-date').value);
    fd.append('location',    document.getElementById('flock-location').value);
    fd.append('status',      document.getElementById('flock-status').value);
    fd.append('notes',       document.getElementById('flock-notes').value);
    try {
        const res = await fetch('/Backend/api/admin_poultry.php?action=save_flock',{method:'POST',body:fd});
        const r = await res.json();
        if (r.success) { closeFlockModal(); loadFlocks(); }
        else alert('Error: '+(r.message||'Could not save.'));
    } catch(e){ alert('Network error.'); } finally {
        btn.disabled=false; btn.innerHTML='<i data-lucide="save" style="width:15px;height:15px;"></i> Save Flock';
        if(typeof lucide!=='undefined') lucide.createIcons();
    }
});

async function deleteFlock(id) {
    if (!confirm('Delete this flock? This cannot be undone.')) return;
    const fd = new FormData(); fd.append('id',id); fd.append('csrf_token',CSRF);
    const res = await fetch('/Backend/api/admin_poultry.php?action=delete_flock',{method:'POST',body:fd});
    const r = await res.json();
    r.success ? loadFlocks() : alert(r.message);
}

document.addEventListener('click',e=>{ const m=document.getElementById('flock-modal'); if(m&&e.target===m) m.style.display='none'; });
document.addEventListener('DOMContentLoaded', loadFlocks);
</script>

<?php /* ══════════════════ PRODUCTION TAB (Daily Egg / Feed / Mortality) ══════════════════ */ ?>
<?php elseif ($tab === 'production'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Daily Production Log</h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Record daily egg collection, feed consumed, and bird mortality per flock.</p>
        </div>
        <button class="btn btn-primary" onclick="openProductionModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Log Today's Production</button>
    </div>
    <!-- Weekly KPIs -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
        <div class="stat-card"><div class="stat-card-info"><small>Eggs This Week</small><strong id="prod-kpi-eggs">-</strong></div><div class="stat-card-icon accent"><i data-lucide="egg" style="width:22px;height:22px;"></i></div></div>
        <div class="stat-card"><div class="stat-card-info"><small>Feed This Week (kg)</small><strong id="prod-kpi-feed">-</strong></div><div class="stat-card-icon info"><i data-lucide="layers" style="width:22px;height:22px;"></i></div></div>
        <div class="stat-card"><div class="stat-card-info"><small>Mortality This Week</small><strong id="prod-kpi-mort" style="color:#dc2626;">-</strong></div><div class="stat-card-icon" style="background:#fee2e2;color:#dc2626;"><i data-lucide="alert-triangle" style="width:22px;height:22px;"></i></div></div>
        <div class="stat-card"><div class="stat-card-info"><small>Cracked / Rejected</small><strong id="prod-kpi-cracked">-</strong></div><div class="stat-card-icon" style="background:#fef3c7;color:#d97706;"><i data-lucide="x-circle" style="width:22px;height:22px;"></i></div></div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Flock</th><th>Eggs</th><th>Cracked</th><th>Feed (kg)</th><th>Mortality</th><th>Notes</th><th>Actions</th></tr></thead>
            <tbody id="production-body"><tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr></tbody>
        </table>
    </div>
</div>

<!-- Production Modal -->
<div id="production-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:520px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 id="prod-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Log Daily Production</h3>
        <form id="production-form">
            <input type="hidden" id="prod-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Flock *</label>
                    <select class="admin-form-control" id="prod-flock" required><option value="">Choose flock...</option></select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" id="prod-date" required></div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                <div class="admin-form-group">
                    <label class="admin-form-label">Eggs Collected (Pieces)</label>
                    <input class="admin-form-control" type="number" id="prod-eggs" min="0" value="0">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Cracked / Rejected Eggs</label>
                    <input class="admin-form-control" type="number" id="prod-cracked" min="0" value="0">
                </div>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                <div class="admin-form-group">
                    <label class="admin-form-label">Milk Yield (Litres)</label>
                    <input class="admin-form-control" type="number" step="0.1" id="prod-milk" min="0" value="0" placeholder="For Cows/Goats">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Feed Consumed (kg)</label>
                    <input class="admin-form-control" type="number" step="0.1" id="prod-feed" min="0" value="0">
                </div>
            </div>
            <div class="admin-form-group"><label class="admin-form-label">Mortality (Animals Lost Today)</label><input class="admin-form-control" type="number" id="prod-mortality" min="0" value="0" style="border-color:#fca5a5;"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes / Observations</label><textarea class="admin-form-control" id="prod-notes" rows="2" placeholder="Any remarks, cause of mortality, observations..."></textarea></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeProductionModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;" id="prod-submit-btn"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Log</button>
            </div>
        </form>
    </div>
</div>

<script>
window.production_list = []; window.flocks_prod = [];

async function loadProductionData() {
    setTbLoading('production-body', 8, 'Loading production records...');
    try {
        const [fr, pr] = await Promise.all([
            fetch('/Backend/api/admin_poultry.php?action=get_flocks'),
            fetch('/Backend/api/admin_poultry.php?action=get_production')
        ]);
        const fd = await fr.json(); const pd = await pr.json();
        if (fd.success) { window.flocks_prod = fd.data; populateProdFlocks(); }
        if (!pd.success) { setTbError('production-body',8,pd.message||'Failed to load.'); return; }
        window.production_list = pd.data;

        // Weekly KPIs
        const weekAgo = new Date(); weekAgo.setDate(weekAgo.getDate()-7);
        const weekly = window.production_list.filter(p=>new Date(p.record_date)>=weekAgo);
        document.getElementById('prod-kpi-eggs').textContent    = weekly.reduce((s,p)=>s+parseInt(p.eggs_collected||0),0).toLocaleString();
        document.getElementById('prod-kpi-feed').textContent    = weekly.reduce((s,p)=>s+parseFloat(p.feed_consumed_kg||0),0).toFixed(1);
        document.getElementById('prod-kpi-mort').textContent    = weekly.reduce((s,p)=>s+parseInt(p.mortality||0),0);
        document.getElementById('prod-kpi-cracked').textContent = weekly.reduce((s,p)=>s+parseInt(p.cracked_eggs||0),0).toLocaleString();

        renderProduction();
    } catch(e){ setTbError('production-body',8,'Network error.'); console.error(e); }
}

function populateProdFlocks(){
    const sel = document.getElementById('prod-flock');
    sel.innerHTML = '<option value="">Choose flock...</option>' +
        window.flocks_prod.filter(f=>f.status==='active').map(f=>`<option value="${f.id}">${f.flock_name}</option>`).join('');
}

function renderProduction(){
    const tbody = document.getElementById('production-body');
    if (!window.production_list.length){ tbody.innerHTML='<tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">No production logs yet. Click "Log Today\'s Production" to start.</td></tr>'; return; }
    tbody.innerHTML = window.production_list.map(p=>`<tr>
        <td>${p.record_date}</td>
        <td><strong>${p.flock_name||'-'}</strong></td>
        <td><strong>${Number(p.eggs_collected).toLocaleString()}</strong></td>
        <td>${p.cracked_eggs||0}</td>
        <td>${parseFloat(p.feed_consumed_kg||0).toFixed(1)}</td>
        <td><strong style="color:${parseInt(p.mortality)>0?'#dc2626':'#1e293b'}">${p.mortality||0}</strong></td>
        <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${p.notes||'-'}</td>
        <td><div class="tbl-actions">
            <button class="btn btn-trans btn-sm" onclick="editProduction(${p.id})"><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button>
            <button class="btn btn-danger btn-sm" onclick="deleteProduction(${p.id})"><i data-lucide="trash-2" style="width:13px;height:13px;"></i></button>
        </div></td>
    </tr>`).join('');
    if(typeof lucide!=='undefined') lucide.createIcons();
}

function openProductionModal(data){
    const isEdit = data&&data.id;
    document.getElementById('prod-modal-title').textContent = isEdit?'Edit Production Log':'Log Daily Production';
    document.getElementById('prod-id').value       = isEdit?data.id:'';
    document.getElementById('prod-flock').value    = data?.flock_id||'';
    document.getElementById('prod-date').value     = data?.record_date||new Date().toISOString().split('T')[0];
    document.getElementById('prod-eggs').value     = data?.eggs_collected||0;
    document.getElementById('prod-cracked').value  = data?.cracked_eggs||0;
    document.getElementById('prod-feed').value     = data?.feed_consumed_kg||0;
    document.getElementById('prod-mortality').value= data?.mortality||0;
    document.getElementById('prod-notes').value    = data?.notes||'';
    document.getElementById('production-modal').style.display='flex';
}
function closeProductionModal(){ document.getElementById('production-modal').style.display='none'; }
function editProduction(id){ openProductionModal(window.production_list.find(p=>p.id==id)); }

async function deleteProduction(id){
    if(!confirm('Delete this production log?')) return;
    const fd=new FormData(); fd.append('id',id); fd.append('csrf_token',CSRF);
    const r=await(await fetch('/Backend/api/admin_poultry.php?action=delete_production',{method:'POST',body:fd})).json();
    r.success?loadProductionData():alert(r.message);
}

document.getElementById('production-form').addEventListener('submit',async e=>{
    e.preventDefault(); const btn=document.getElementById('prod-submit-btn');
    btn.disabled=true; btn.textContent='Saving...';
    const fd=new FormData();
    fd.append('csrf_token',CSRF);
    fd.append('id',             document.getElementById('prod-id').value);
    fd.append('flock_id',       document.getElementById('prod-flock').value);
    fd.append('record_date',    document.getElementById('prod-date').value);
    fd.append('eggs_collected', document.getElementById('prod-eggs').value);
    fd.append('cracked_eggs',   document.getElementById('prod-cracked').value);
    fd.append('feed_consumed_kg',document.getElementById('prod-feed').value);
    fd.append('meat_weight_kg', 0);
    fd.append('mortality',      document.getElementById('prod-mortality').value);
    fd.append('notes',          document.getElementById('prod-notes').value);
    try{
        const r=await(await fetch('/Backend/api/admin_poultry.php?action=save_production',{method:'POST',body:fd})).json();
        if(r.success){ closeProductionModal(); loadProductionData(); }
        else alert('Error: '+(r.message||'Could not save.'));
    }catch(e){alert('Network error.');}
    finally{ btn.disabled=false; btn.innerHTML='<i data-lucide="save" style="width:15px;height:15px;"></i> Save Log'; if(typeof lucide!=='undefined')lucide.createIcons(); }
});

document.addEventListener('click',e=>{ const m=document.getElementById('production-modal'); if(m&&e.target===m) m.style.display='none'; });
document.addEventListener('DOMContentLoaded',loadProductionData);
</script>

<?php /* ══════════════════ VACCINATIONS TAB (API JS) ══════════════════ */ ?>
<?php elseif ($tab === 'vaccinations'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Vaccination Schedule</h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Track and schedule all flock vaccinations. Never miss a dose.</p>
        </div>
        <button class="btn btn-primary" onclick="openVacModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Schedule Vaccine</button>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Scheduled Date</th><th>Flock</th><th>Vaccine / Treatment</th><th>Administered Date</th><th>Next Due</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="vac-body"><tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr></tbody>
        </table>
    </div>
</div>

<!-- Vaccination Modal -->
<div id="vac-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:520px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 id="vac-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Schedule Vaccination</h3>
        <form id="vac-form">
            <input type="hidden" id="vac-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Target Flock *</label>
                    <select class="admin-form-control" id="vac-flock" required><option value="">Select flock...</option></select>
                </div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Vaccine / Treatment Name *</label>
                    <input class="admin-form-control" id="vac-name" required placeholder="e.g. Newcastle (Lasota), Gumboro, Marek's">
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Scheduled Date *</label><input class="admin-form-control" type="date" id="vac-sched" required></div>
                <div class="admin-form-group"><label class="admin-form-label">Next Due Date</label><input class="admin-form-control" type="date" id="vac-next"></div>
                <div class="admin-form-group"><label class="admin-form-label">Administered Date</label><input class="admin-form-control" type="date" id="vac-admin-date"></div>
                <div class="admin-form-group"><label class="admin-form-label">Status</label>
                    <select class="admin-form-control" id="vac-status">
                        <option value="scheduled">Scheduled</option><option value="completed">Completed</option><option value="missed">Missed</option>
                    </select>
                </div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Dosage / Notes</label><textarea class="admin-form-control" id="vac-notes" rows="2" placeholder="Dosage, route, administered by..."></textarea></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeVacModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;" id="vac-submit-btn"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
window.vac_list=[]; window.flocks_vac=[];

async function loadVaccinations(){
    setTbLoading('vac-body',7,'Loading vaccination schedule...');
    try{
        const [fr,vr]=await Promise.all([
            fetch('/Backend/api/admin_poultry.php?action=get_flocks'),
            fetch('/Backend/api/admin_poultry.php?action=get_vaccinations')
        ]);
        const fd=await fr.json(); const vd=await vr.json();
        if(fd.success){ window.flocks_vac=fd.data; populateVacFlocks(); }
        if(!vd.success){ setTbError('vac-body',7,vd.message||'Failed to load.'); return; }
        window.vac_list=vd.data; renderVaccinations();
    }catch(e){ setTbError('vac-body',7,'Network error.'); console.error(e); }
}
function populateVacFlocks(){
    document.getElementById('vac-flock').innerHTML='<option value="">Select flock...</option>'+
        window.flocks_vac.filter(f=>f.status==='active').map(f=>`<option value="${f.id}">${f.flock_name}</option>`).join('');
}
function renderVaccinations(){
    const tbody=document.getElementById('vac-body');
    if(!window.vac_list.length){ tbody.innerHTML='<tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">No vaccinations scheduled. Click "Schedule Vaccine" to start.</td></tr>'; return; }
    const today=new Date().toISOString().split('T')[0];
    tbody.innerHTML=window.vac_list.map(v=>{
        const sc=v.status==='completed'?'badge-pill-success':(v.status==='missed'?'badge-pill-danger':'badge-pill-warning');
        const overdue=v.status==='scheduled'&&v.scheduled_date<today;
        return `<tr style="${overdue?'background:#fff7ed;':''}">
            <td>${overdue?'<span style="color:#dc2626;font-weight:700;">⚠ </span>':''}${v.scheduled_date}</td>
            <td><strong>${v.flock_name||'-'}</strong></td>
            <td>${v.vaccine_name}</td>
            <td>${v.administered_date||'<span style="color:#94a3b8;">Pending</span>'}</td>
            <td>${v.next_due_date||'-'}</td>
            <td><span class="badge-pill ${sc}">${v.status}</span></td>
            <td><div class="tbl-actions">
                <button class="btn btn-trans btn-sm" onclick="editVac(${v.id})"><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button>
                <button class="btn btn-danger btn-sm" onclick="deleteVac(${v.id})"><i data-lucide="trash-2" style="width:13px;height:13px;"></i></button>
            </div></td>
        </tr>`;
    }).join('');
    if(typeof lucide!=='undefined') lucide.createIcons();
}
function openVacModal(data){
    const isEdit=data&&data.id;
    document.getElementById('vac-modal-title').textContent=isEdit?'Edit Vaccination':'Schedule Vaccination';
    document.getElementById('vac-id').value        =isEdit?data.id:'';
    document.getElementById('vac-flock').value     =data?.flock_id||'';
    document.getElementById('vac-name').value      =data?.vaccine_name||'';
    document.getElementById('vac-sched').value     =data?.scheduled_date||'';
    document.getElementById('vac-next').value      =data?.next_due_date||'';
    document.getElementById('vac-admin-date').value=data?.administered_date||'';
    document.getElementById('vac-status').value    =data?.status||'scheduled';
    document.getElementById('vac-notes').value     =data?.notes||'';
    document.getElementById('vac-modal').style.display='flex';
}
function closeVacModal(){ document.getElementById('vac-modal').style.display='none'; }
function editVac(id){ openVacModal(window.vac_list.find(v=>v.id==id)); }

async function deleteVac(id){
    if(!confirm('Delete this vaccination record?')) return;
    const fd=new FormData(); fd.append('id',id); fd.append('csrf_token',CSRF);
    const r=await(await fetch('/Backend/api/admin_poultry.php?action=delete_vaccination',{method:'POST',body:fd})).json();
    r.success?loadVaccinations():alert(r.message);
}

document.getElementById('vac-form').addEventListener('submit',async e=>{
    e.preventDefault(); const btn=document.getElementById('vac-submit-btn');
    btn.disabled=true; btn.textContent='Saving...';
    const fd=new FormData();
    fd.append('csrf_token',CSRF);
    fd.append('id',               document.getElementById('vac-id').value);
    fd.append('flock_id',         document.getElementById('vac-flock').value);
    fd.append('vaccine_name',     document.getElementById('vac-name').value);
    fd.append('scheduled_date',   document.getElementById('vac-sched').value);
    fd.append('next_due_date',    document.getElementById('vac-next').value);
    fd.append('administered_date',document.getElementById('vac-admin-date').value);
    fd.append('status',           document.getElementById('vac-status').value);
    fd.append('notes',            document.getElementById('vac-notes').value);
    try{
        const r=await(await fetch('/Backend/api/admin_poultry.php?action=save_vaccination',{method:'POST',body:fd})).json();
        if(r.success){ closeVacModal(); loadVaccinations(); }
        else alert('Error: '+(r.message||'Could not save.'));
    }catch(e){alert('Network error.');}
    finally{ btn.disabled=false; btn.innerHTML='<i data-lucide="save" style="width:15px;height:15px;"></i> Save'; if(typeof lucide!=='undefined')lucide.createIcons(); }
});
document.addEventListener('click',e=>{ const m=document.getElementById('vac-modal'); if(m&&e.target===m) m.style.display='none'; });
document.addEventListener('DOMContentLoaded',loadVaccinations);
</script>

<?php /* ══════════════════ ANIMALS TAB ══════════════════ */ ?>
<?php elseif ($tab === 'animals'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Animal Records</h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Track individual animals with tag IDs, breed, gender, and health status.</p></div>
        <button class="btn btn-primary" onclick="openAnimalModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Add Animal</button>
    </div>
    <div class="table-responsive"><table class="admin-table">
        <thead><tr><th>Tag / ID</th><th>Name</th><th>Species</th><th>Breed</th><th>Gender</th><th>DOB</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody><?php if(empty($animals)): ?><tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">No animals registered yet.</td></tr><?php else: foreach($animals as $a): ?>
        <tr>
            <td><strong><?= htmlspecialchars($a['tag_id']??'-',ENT_QUOTES,'UTF-8') ?></strong></td>
            <td><?= htmlspecialchars($a['name']??'-',ENT_QUOTES,'UTF-8') ?></td>
            <td><?= htmlspecialchars($a['species']??'-',ENT_QUOTES,'UTF-8') ?></td>
            <td><?= htmlspecialchars($a['breed']??'-',ENT_QUOTES,'UTF-8') ?></td>
            <td><?= htmlspecialchars(ucfirst($a['gender']??'-'),ENT_QUOTES,'UTF-8') ?></td>
            <td><?= htmlspecialchars($a['date_of_birth']??'-',ENT_QUOTES,'UTF-8') ?></td>
            <td><span class="badge-pill <?= $a['status']==='alive'?'badge-pill-success':($a['status']==='sick'?'badge-pill-warning':'badge-pill-danger') ?>"><?= ucfirst(htmlspecialchars($a['status']??'alive',ENT_QUOTES,'UTF-8')) ?></span></td>
            <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openAnimalModal(<?= htmlspecialchars(json_encode($a),ENT_QUOTES,"UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button></div></td>
        </tr>
        <?php endforeach; endif; ?></tbody>
    </table></div>
</div>
<div id="animal-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 id="animal-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add Animal</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_animal"><input type="hidden" name="id" id="a-id">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group"><label class="admin-form-label">Tag / ID</label><input class="admin-form-control" name="tag_id" id="a-tag" placeholder="e.g. A-001"></div>
            <div class="admin-form-group"><label class="admin-form-label">Name</label><input class="admin-form-control" name="name" id="a-name" placeholder="e.g. Bessie"></div>
            <div class="admin-form-group"><label class="admin-form-label">Species</label><select class="admin-form-control" name="species" id="a-species"><?php foreach(['Chicken','Cow','Goat','Pig','Sheep','Duck','Rabbit','Other'] as $sp): ?><option><?= $sp ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Breed</label><input class="admin-form-control" name="breed" id="a-breed"></div>
            <div class="admin-form-group"><label class="admin-form-label">Gender</label><select class="admin-form-control" name="gender" id="a-gender"><option value="male">Male</option><option value="female">Female</option><option value="unknown">Unknown</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Date of Birth</label><input class="admin-form-control" type="date" name="dob" id="a-dob"></div>
            <div class="admin-form-group"><label class="admin-form-label">Status</label><select class="admin-form-control" name="status" id="a-status"><option value="alive">Alive</option><option value="sick">Sick</option><option value="sold">Sold</option><option value="deceased">Deceased</option></select></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" id="a-notes" rows="3"></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('animal-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Animal</button>
        </div></form>
    </div>
</div>
<script>
function openAnimalModal(d){
    document.getElementById('animal-modal-title').textContent=d?.id?'Edit Animal':'Add Animal';
    document.getElementById('a-id').value=d?.id||''; document.getElementById('a-tag').value=d?.tag_id||'';
    document.getElementById('a-name').value=d?.name||''; document.getElementById('a-species').value=d?.species||'Chicken';
    document.getElementById('a-breed').value=d?.breed||''; document.getElementById('a-gender').value=d?.gender||'female';
    document.getElementById('a-dob').value=d?.date_of_birth||''; document.getElementById('a-status').value=d?.status||'alive';
    document.getElementById('a-notes').value=d?.notes||''; document.getElementById('animal-modal').style.display='flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('animal-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>

<?php /* ══════════════════ HEALTH TAB ══════════════════ */ ?>
<?php elseif ($tab === 'health'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Health Records</h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Log diagnoses, treatments, vet visits and medication costs.</p></div>
        <button class="btn btn-primary" onclick="openHealthModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Log Health Record</button>
    </div>
    <div class="table-responsive"><table class="admin-table">
        <thead><tr><th>Date</th><th>Animal / Flock</th><th>Diagnosis</th><th>Treatment</th><th>Vet</th><th>Cost (KES)</th><th>Actions</th></tr></thead>
        <tbody><?php if(empty($healthRecs)): ?><tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">No health records yet.</td></tr><?php else: foreach($healthRecs as $h): ?>
        <tr>
            <td><?= htmlspecialchars($h['record_date']??'-',ENT_QUOTES,'UTF-8') ?></td>
            <td><?= htmlspecialchars(trim(($h['tag_id']??'').' '.($h['aname']??'General')),ENT_QUOTES,'UTF-8') ?></td>
            <td><?= htmlspecialchars($h['diagnosis']??'-',ENT_QUOTES,'UTF-8') ?></td>
            <td><?= htmlspecialchars($h['treatment']??'-',ENT_QUOTES,'UTF-8') ?></td>
            <td><?= htmlspecialchars($h['vet_name']??'-',ENT_QUOTES,'UTF-8') ?></td>
            <td><?= $h['cost']?number_format((float)$h['cost'],2):'-' ?></td>
            <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openHealthModal(<?= htmlspecialchars(json_encode($h),ENT_QUOTES,"UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button></div></td>
        </tr>
        <?php endforeach; endif; ?></tbody>
    </table></div>
</div>
<div id="health-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 id="health-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Log Health Record</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_health"><input type="hidden" name="id" id="h-id">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group"><label class="admin-form-label">Date</label><input class="admin-form-control" type="date" name="record_date" id="h-date" value="<?= date('Y-m-d') ?>"></div>
            <div class="admin-form-group"><label class="admin-form-label">Animal (optional)</label><select class="admin-form-control" name="animal_id" id="h-animal"><option value="">-- General / Flock --</option><?php foreach($animalList as $al): ?><option value="<?= (int)$al['id'] ?>"><?= htmlspecialchars($al['label'],ENT_QUOTES,'UTF-8') ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Diagnosis / Condition</label><input class="admin-form-control" name="diagnosis" id="h-diag" placeholder="e.g. Newcastle Disease, Coccidiosis"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Treatment / Medication</label><input class="admin-form-control" name="treatment" id="h-treat" placeholder="e.g. Lasota Vaccine 3ml IM, Amprolium in water"></div>
            <div class="admin-form-group"><label class="admin-form-label">Vet Name</label><input class="admin-form-control" name="vet_name" id="h-vet" placeholder="Dr. Kamau"></div>
            <div class="admin-form-group"><label class="admin-form-label">Cost (KES)</label><input class="admin-form-control" type="number" step="0.01" name="cost" id="h-cost" placeholder="0.00"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" id="h-notes" rows="3"></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('health-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Record</button>
        </div></form>
    </div>
</div>
<script>
function openHealthModal(d){
    document.getElementById('health-modal-title').textContent=d?.id?'Edit Health Record':'Log Health Record';
    document.getElementById('h-id').value=d?.id||''; document.getElementById('h-date').value=d?.record_date||'<?= date("Y-m-d") ?>';
    document.getElementById('h-animal').value=d?.animal_id||''; document.getElementById('h-diag').value=d?.diagnosis||'';
    document.getElementById('h-treat').value=d?.treatment||''; document.getElementById('h-vet').value=d?.vet_name||'';
    document.getElementById('h-cost').value=d?.cost||''; document.getElementById('h-notes').value=d?.notes||'';
    document.getElementById('health-modal').style.display='flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('health-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>

<?php /* ══════════════════ BREEDING TAB ══════════════════ */ ?>
<?php elseif ($tab === 'breeding'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Breeding Records</h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Track mating events, expected births, and offspring outcomes.</p></div>
        <button class="btn btn-primary" onclick="openBreedingModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Record Breeding</button>
    </div>
    <div class="table-responsive"><table class="admin-table">
        <thead><tr><th>Date</th><th>Sire (Father)</th><th>Dam (Mother)</th><th>Expected Birth</th><th>Status</th><th>Notes</th><th>Actions</th></tr></thead>
        <tbody><?php if(empty($breedingRecs)): ?><tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">No breeding records yet.</td></tr><?php else: foreach($breedingRecs as $b): ?>
        <tr>
            <td><?= htmlspecialchars($b['breeding_date']??'-',ENT_QUOTES,'UTF-8') ?></td>
            <td><?= htmlspecialchars($b['sire']??'-',ENT_QUOTES,'UTF-8') ?></td>
            <td><?= htmlspecialchars($b['dam']??'-',ENT_QUOTES,'UTF-8') ?></td>
            <td><?= htmlspecialchars($b['expected_birth']??'-',ENT_QUOTES,'UTF-8') ?></td>
            <td><span class="badge-pill <?= $b['status']==='Born'?'badge-pill-success':($b['status']==='Pending'?'badge-pill-warning':'badge-pill-danger') ?>"><?= htmlspecialchars($b['status']??'Pending',ENT_QUOTES,'UTF-8') ?></span></td>
            <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($b['notes']??'-',ENT_QUOTES,'UTF-8') ?></td>
            <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openBreedingModal(<?= htmlspecialchars(json_encode($b),ENT_QUOTES,"UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button></div></td>
        </tr>
        <?php endforeach; endif; ?></tbody>
    </table></div>
</div>
<div id="breeding-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:500px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 id="breeding-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Record Breeding Event</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_breeding"><input type="hidden" name="id" id="br-id">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Livestock Species</label>
                <select class="admin-form-control" id="br-species" onchange="calculateExpectedBirth()">
                    <option value="chicken">Chicken (21 Days)</option>
                    <option value="cow">Cow (283 Days)</option>
                    <option value="goat">Goat (150 Days)</option>
                    <option value="pig">Pig (114 Days)</option>
                    <option value="sheep">Sheep (150 Days)</option>
                    <option value="other">Other / Custom</option>
                </select>
            </div>
            <div class="admin-form-group"><label class="admin-form-label">Sire (Father) ID/Tag</label><input class="admin-form-control" name="sire" id="br-sire" placeholder="e.g. A-003"></div>
            <div class="admin-form-group"><label class="admin-form-label">Dam (Mother) ID/Tag</label><input class="admin-form-control" name="dam" id="br-dam" placeholder="e.g. A-007"></div>
            <div class="admin-form-group"><label class="admin-form-label">Breeding Date</label><input class="admin-form-control" type="date" name="breeding_date" id="br-date" value="<?= date('Y-m-d') ?>" onchange="calculateExpectedBirth()"></div>
            <div class="admin-form-group"><label class="admin-form-label">Expected Birth</label><input class="admin-form-control" type="date" name="expected_birth" id="br-exp"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Status</label><select class="admin-form-control" name="status" id="br-status"><option>Pending</option><option>Born</option><option>Failed</option><option>Aborted</option></select></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" id="br-notes" rows="3"></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('breeding-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Record</button>
        </div></form>
    </div>
</div>
<script>
function calculateExpectedBirth() {
    const breedDateVal = document.getElementById('br-date').value;
    if (!breedDateVal) return;
    const species = document.getElementById('br-species').value;
    let days = 21;
    if (species === 'cow') days = 283;
    else if (species === 'goat' || species === 'sheep') days = 150;
    else if (species === 'pig') days = 114;
    else if (species === 'other') return; // let user enter custom

    const d = new Date(breedDateVal);
    d.setDate(d.getDate() + days);
    document.getElementById('br-exp').value = d.toISOString().split('T')[0];
}
function openBreedingModal(d){
    document.getElementById('breeding-modal-title').textContent=d?.id?'Edit Breeding Record':'Record Breeding Event';
    document.getElementById('br-id').value=d?.id||''; document.getElementById('br-sire').value=d?.sire||'';
    document.getElementById('br-dam').value=d?.dam||''; document.getElementById('br-date').value=d?.breeding_date||'<?= date("Y-m-d") ?>';
    document.getElementById('br-exp').value=d?.expected_birth||''; document.getElementById('br-status').value=d?.status||'Pending';
    document.getElementById('br-notes').value=d?.notes||''; 
    document.getElementById('br-species').value = 'cow';
    document.getElementById('breeding-modal').style.display='flex';
    calculateExpectedBirth();
}
document.addEventListener('click',e=>{ const m=document.getElementById('breeding-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>

<?php /* ══════════════════ HERDS TAB ══════════════════ */ ?>
<?php elseif ($tab === 'herds'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Herd / Pen Groups</h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Organise animals into manageable groups, pens or herds.</p></div>
        <button class="btn btn-primary" onclick="openHerdModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Add Herd</button>
    </div>
    <div class="table-responsive"><table class="admin-table">
        <thead><tr><th>Name</th><th>Type</th><th>Location / Pen</th><th>Head Count</th><th>Actions</th></tr></thead>
        <tbody><?php if(empty($herds)): ?><tr><td colspan="5" style="text-align:center;padding:28px;color:#94a3b8;">No herds created yet.</td></tr><?php else: foreach($herds as $hd): ?>
        <tr>
            <td><strong><?= htmlspecialchars($hd['name'],ENT_QUOTES,'UTF-8') ?></strong></td>
            <td><?= htmlspecialchars($hd['type']??'-',ENT_QUOTES,'UTF-8') ?></td>
            <td><?= htmlspecialchars($hd['location']??'-',ENT_QUOTES,'UTF-8') ?></td>
            <td><strong><?= (int)($hd['head_count']??0) ?></strong></td>
            <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openHerdModal(<?= htmlspecialchars(json_encode($hd),ENT_QUOTES,"UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button></div></td>
        </tr>
        <?php endforeach; endif; ?></tbody>
    </table></div>
</div>
<div id="herd-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:460px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 id="herd-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add Herd / Group</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_herd"><input type="hidden" name="id" id="herd-id">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Herd / Group Name *</label><input class="admin-form-control" name="name" id="herd-name" required placeholder="e.g. Pen A - Layers"></div>
            <div class="admin-form-group"><label class="admin-form-label">Animal Type</label><select class="admin-form-control" name="type" id="herd-type"><?php foreach(['Chicken','Cow','Goat','Pig','Sheep','Mixed','Other'] as $ht): ?><option><?= $ht ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Location / Pen</label><input class="admin-form-control" name="location" id="herd-loc" placeholder="e.g. Block B, Pen 3"></div>
            <div class="admin-form-group"><label class="admin-form-label">Head Count</label><input class="admin-form-control" type="number" name="head_count" id="herd-cnt" min="0" value="0"></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('herd-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Herd</button>
        </div></form>
    </div>
</div>
<script>
function openHerdModal(d){
    document.getElementById('herd-modal-title').textContent=d?.id?'Edit Herd':'Add Herd / Group';
    document.getElementById('herd-id').value=d?.id||''; document.getElementById('herd-name').value=d?.name||'';
    document.getElementById('herd-type').value=d?.type||'Chicken'; document.getElementById('herd-loc').value=d?.location||'';
    document.getElementById('herd-cnt').value=d?.head_count||0; document.getElementById('herd-modal').style.display='flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('herd-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>

<?php endif; ?>

<!-- Shared JS helpers -->
<script>
function setTbLoading(id,cols,msg){ const el=document.getElementById(id); if(el) el.innerHTML=`<tr><td colspan="${cols}" style="text-align:center;padding:28px;color:#64748b;"><div style="display:inline-flex;align-items:center;gap:10px;"><div style="width:20px;height:20px;border:2px solid #cbd5e1;border-top-color:var(--admin-primary);border-radius:50%;animation:spin 0.8s linear infinite;"></div>${msg}</div></td></tr>`; }
function setTbError(id,cols,msg){ const el=document.getElementById(id); if(el) el.innerHTML=`<tr><td colspan="${cols}" style="text-align:center;padding:28px;"><div style="display:inline-flex;align-items:center;gap:10px;color:#dc2626;background:#fef2f2;border:1px solid #fecaca;padding:14px 24px;border-radius:8px;font-weight:600;">${msg}</div></td></tr>`; }
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
