<?php
/**
 * Admin — Batches & Houses Module
 * Mirrors the "Batch 15 2026" spreadsheet structure:
 *   - One row per day, per batch
 *   - Tracks mortality, eggs, weight, sold birds, production %
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
$page_title = 'Batches & Houses - Admin';
include __DIR__ . '/includes/admin_header.php';

$pdo = getDB();
$tab = $_GET['tab'] ?? 'batches';
$validTabs = ['batches','houses','records'];
if (!in_array($tab, $validTabs, true)) $tab = 'batches';

$batches = $pdo ? $pdo->query("SELECT b.*, h.house_name FROM batches b LEFT JOIN houses h ON h.id=b.house_id ORDER BY b.placement_date DESC")->fetchAll(PDO::FETCH_ASSOC) : [];
$houses = $pdo ? $pdo->query("SELECT * FROM houses ORDER BY house_name")->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Batches & Houses</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Track each batch of birds by house — mortality, eggs, weight, sales — on a daily basis.</p>
    </div>
</div>

<!-- Tab Bar -->
<div style="display:flex;gap:4px;background:#f1f5f9;padding:5px;border-radius:10px;margin-bottom:24px;overflow-x:auto;">
    <a href="?tab=batches" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;white-space:nowrap;font-weight:600;font-size:0.86rem;<?= $tab==='batches'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>">
        <i data-lucide="package" style="width:15px;height:15px;"></i> Batches
    </a>
    <a href="?tab=houses" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;white-space:nowrap;font-weight:600;font-size:0.86rem;<?= $tab==='houses'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>">
        <i data-lucide="home" style="width:15px;height:15px;"></i> Houses
    </a>
    <a href="?tab=records" style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;white-space:nowrap;font-weight:600;font-size:0.86rem;<?= $tab==='records'?'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);':'color:#64748b;' ?>">
        <i data-lucide="clipboard-list" style="width:15px;height:15px;"></i> Daily Records
    </a>
</div>

<?php if ($tab === 'batches'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Active & Past Batches</h3>
        <div style="display:flex;gap:8px;">
            <a href="/Backend/api/export.php?module=batches" class="btn btn-outline"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
            <button class="btn btn-primary" onclick="openBatchModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> New Batch</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Code</th><th>Batch Name</th><th>House</th><th>Type</th><th>Placed</th><th>Birds (Start/Current)</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($batches)): ?>
                <tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">No batches yet. Click "New Batch" to create one.</td></tr>
            <?php else: foreach ($batches as $b): ?>
                <tr>
                    <td><code style="background:#f1f5f9;padding:2px 8px;border-radius:4px;"><?= htmlspecialchars($b['batch_code'], ENT_QUOTES, 'UTF-8') ?></code></td>
                    <td><strong><?= htmlspecialchars($b['batch_name'], ENT_QUOTES, 'UTF-8') ?></strong><br><small style="color:#64748b;"><?= htmlspecialchars($b['breed'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                    <td><?= htmlspecialchars($b['house_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge-pill badge-pill-info"><?= htmlspecialchars($b['batch_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($b['placement_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><strong><?= (int)$b['initial_birds'] ?></strong> / <span style="color:<?= $b['current_birds']<$b['initial_birds']*0.9?'#dc2626':'#16a34a' ?>"><?= (int)$b['current_birds'] ?></span></td>
                    <td><span class="badge-pill <?= $b['status']==='active'?'badge-pill-success':($b['status']==='sold'?'badge-pill-warning':'badge-pill-info') ?>"><?= htmlspecialchars($b['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td>
                        <div class="tbl-actions">
                            <button class="btn btn-trans btn-sm" onclick="openBatchModal(<?= htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8') ?>)"><i data-lucide="pencil" style="width:13px;height:13px;"></i></button>
                            <a class="btn btn-trans btn-sm" href="?tab=records&batch_id=<?= (int)$b['id'] ?>"><i data-lucide="clipboard-list" style="width:13px;height:13px;"></i> Records</a>
                            <button class="btn btn-danger btn-sm" onclick="deleteBatch(<?= (int)$b['id'] ?>)"><i data-lucide="trash-2" style="width:13px;height:13px;"></i></button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($tab === 'houses'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Chicken Houses</h3>
        <button class="btn btn-primary" onclick="openHouseModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Add House</button>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Code</th><th>Name</th><th>Location</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($houses)): ?>
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">No houses yet.</td></tr>
            <?php else: foreach ($houses as $h): ?>
                <tr>
                    <td><code style="background:#f1f5f9;padding:2px 8px;border-radius:4px;"><?= htmlspecialchars($h['house_code'], ENT_QUOTES, 'UTF-8') ?></code></td>
                    <td><strong><?= htmlspecialchars($h['house_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($h['location'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)$h['capacity'] ?> birds</td>
                    <td><span class="badge-pill <?= $h['is_active']?'badge-pill-success':'badge-pill-info' ?>"><?= $h['is_active']?'Active':'Inactive' ?></span></td>
                    <td>
                        <button class="btn btn-trans btn-sm" onclick='openHouseModal(<?= json_encode($h) ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="deleteHouse(<?= (int)$h['id'] ?>)"><i data-lucide="trash-2" style="width:13px;height:13px;"></i></button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: /* records */ ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:12px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Daily Batch Records</h3>
        <div style="display:flex;gap:8px;align-items:center;">
            <a href="/Backend/api/export.php?module=daily_records" class="btn btn-outline" id="rec-export"><i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV</a>
            <button class="btn btn-primary" onclick="openRecordModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Log Today's Record</button>
        </div>
    </div>
    <div style="display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
        <select class="admin-form-control" id="rec-filter-batch" style="max-width:280px;" onchange="loadRecords()">
            <option value="">All batches</option>
            <?php foreach ($batches as $b): ?>
                <option value="<?= (int)$b['id'] ?>" <?= ((int)($_GET['batch_id'] ?? 0)===(int)$b['id'])?'selected':'' ?>>
                    <?= htmlspecialchars($b['batch_name'] . ' (' . $b['batch_code'] . ')', ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input class="admin-form-control" type="date" id="rec-from" style="max-width:160px;" onchange="loadRecords()" placeholder="From">
        <input class="admin-form-control" type="date" id="rec-to" style="max-width:160px;" onchange="loadRecords()" placeholder="To">
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr>
                <th>Date</th><th>Wk</th><th>Batch</th>
                <th>Open</th><th>Mort</th><th>Sold</th><th>Close</th>
                <th>Av.Wt</th><th>Trays</th><th>Eggs</th><th>XL</th><th>Dmg</th>
                <th>%Prod</th><th>Actions</th>
            </tr></thead>
            <tbody id="records-body">
                <tr><td colspan="14" style="text-align:center;padding:28px;color:#94a3b8;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Batch Modal -->
<div id="batch-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;overflow-y:auto;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:640px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;margin:20px;">
        <h3 id="batch-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">New Batch</h3>
        <form id="batch-form">
            <input type="hidden" id="b-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Batch Name *</label><input class="admin-form-control" id="b-name" required placeholder="e.g. Layers Tangakona batch 17"></div>
                <div class="admin-form-group"><label class="admin-form-label">Batch Code *</label><input class="admin-form-control" id="b-code" required placeholder="e.g. B17"></div>
                <div class="admin-form-group"><label class="admin-form-label">House *</label>
                    <select class="admin-form-control" id="b-house" required>
                        <option value="">Choose house...</option>
                        <?php foreach ($houses as $h): ?>
                            <option value="<?= (int)$h['id'] ?>"><?= htmlspecialchars($h['house_name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Breed</label><input class="admin-form-control" id="b-breed" placeholder="e.g. ISA Brown"></div>
                <div class="admin-form-group"><label class="admin-form-label">Batch Type</label>
                    <select class="admin-form-control" id="b-type">
                        <option value="layer">Layer (Eggs)</option>
                        <option value="broiler">Broiler (Meat)</option>
                        <option value="kienyeji">Kienyeji</option>
                        <option value="dual_purpose">Dual Purpose</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Initial Birds *</label><input class="admin-form-control" type="number" id="b-birds" min="1" required></div>
                <div class="admin-form-group"><label class="admin-form-label">Placement Date *</label><input class="admin-form-control" type="date" id="b-placement" required value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Expected Harvest</label><input class="admin-form-control" type="date" id="b-harvest"></div>
                <div class="admin-form-group"><label class="admin-form-label">Expected Sale</label><input class="admin-form-control" type="date" id="b-sale"></div>
                <div class="admin-form-group"><label class="admin-form-label">Status</label>
                    <select class="admin-form-control" id="b-status">
                        <option value="active">Active</option>
                        <option value="sold">Sold</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" id="b-notes" rows="2"></textarea></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeBatchModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Batch</button>
            </div>
        </form>
    </div>
</div>

<!-- House Modal -->
<div id="house-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 id="house-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add House</h3>
        <form id="house-form">
            <input type="hidden" id="h-id">
            <div class="admin-form-group"><label class="admin-form-label">House Name *</label><input class="admin-form-control" id="h-name" required placeholder="e.g. Long House"></div>
            <div class="admin-form-group"><label class="admin-form-label">Code *</label><input class="admin-form-control" id="h-code" required placeholder="e.g. LH-01"></div>
            <div class="admin-form-group"><label class="admin-form-label">Location</label><input class="admin-form-control" id="h-loc" placeholder="e.g. North section, Block A"></div>
            <div class="admin-form-group"><label class="admin-form-label">Capacity (birds)</label><input class="admin-form-control" type="number" id="h-cap" min="0" value="5000"></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeHouseModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Daily Record Modal -->
<div id="record-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;overflow-y:auto;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:760px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;margin:20px;">
        <h3 id="record-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Log Daily Record</h3>
        <form id="record-form">
            <input type="hidden" id="r-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Batch *</label>
                    <select class="admin-form-control" id="r-batch" required>
                        <option value="">Choose batch...</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['batch_name'] . ' (' . $b['batch_code'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" id="r-date" required value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Opening Birds</label><input class="admin-form-control" type="number" id="r-open" min="0" value="0"></div>
                <div class="admin-form-group"><label class="admin-form-label">Mortality</label><input class="admin-form-control" type="number" id="r-mort" min="0" value="0"></div>
                <div class="admin-form-group"><label class="admin-form-label">Sold Birds</label><input class="admin-form-control" type="number" id="r-sold" min="0" value="0"></div>
                <div class="admin-form-group"><label class="admin-form-label">Expected Wt (kg)</label><input class="admin-form-control" type="number" step="0.001" id="r-ewt" min="0" value="0"></div>
                <div class="admin-form-group"><label class="admin-form-label">Average Wt (kg)</label><input class="admin-form-control" type="number" step="0.001" id="r-awt" min="0" value="0"></div>
                <div class="admin-form-group"><label class="admin-form-label">Trays</label><input class="admin-form-control" type="number" id="r-trays" min="0" value="0"></div>
                <div class="admin-form-group"><label class="admin-form-label">Total Eggs</label><input class="admin-form-control" type="number" id="r-eggs" min="0" value="0"></div>
                <div class="admin-form-group"><label class="admin-form-label">Extra Large</label><input class="admin-form-control" type="number" id="r-xl" min="0" value="0"></div>
                <div class="admin-form-group"><label class="admin-form-label">Damaged</label><input class="admin-form-control" type="number" id="r-dmg" min="0" value="0"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" id="r-notes" rows="2"></textarea></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeRecordModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Record</button>
            </div>
        </form>
    </div>
</div>

<script>
function openBatchModal(d) {
    document.getElementById('batch-modal-title').textContent = d?.id ? 'Edit Batch' : 'New Batch';
    document.getElementById('b-id').value = d?.id || '';
    document.getElementById('b-name').value = d?.batch_name || '';
    document.getElementById('b-code').value = d?.batch_code || '';
    document.getElementById('b-house').value = d?.house_id || '';
    document.getElementById('b-breed').value = d?.breed || '';
    document.getElementById('b-type').value = d?.batch_type || 'layer';
    document.getElementById('b-birds').value = d?.initial_birds || '';
    document.getElementById('b-placement').value = d?.placement_date || new Date().toISOString().split('T')[0];
    document.getElementById('b-harvest').value = d?.expected_harvest_date || '';
    document.getElementById('b-sale').value = d?.expected_sale_date || '';
    document.getElementById('b-status').value = d?.status || 'active';
    document.getElementById('b-notes').value = d?.notes || '';
    document.getElementById('batch-modal').style.display = 'flex';
}
function closeBatchModal() { document.getElementById('batch-modal').style.display = 'none'; }

document.getElementById('batch-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('id', document.getElementById('b-id').value);
    fd.append('batch_name', document.getElementById('b-name').value);
    fd.append('batch_code', document.getElementById('b-code').value);
    fd.append('house_id', document.getElementById('b-house').value);
    fd.append('breed', document.getElementById('b-breed').value);
    fd.append('batch_type', document.getElementById('b-type').value);
    fd.append('initial_birds', document.getElementById('b-birds').value);
    fd.append('placement_date', document.getElementById('b-placement').value);
    fd.append('expected_harvest_date', document.getElementById('b-harvest').value);
    fd.append('expected_sale_date', document.getElementById('b-sale').value);
    fd.append('status', document.getElementById('b-status').value);
    fd.append('notes', document.getElementById('b-notes').value);
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=save_batch', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) location.reload();
    else alert('Error: ' + r.message);
});

async function deleteBatch(id) {
    if (!confirm('Delete this batch? All daily records will also be removed.')) return;
    const fd = new FormData(); fd.append('id', id);
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=delete_batch', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) location.reload(); else alert(r.message);
}

function openHouseModal(d) {
    document.getElementById('house-modal-title').textContent = d?.id ? 'Edit House' : 'Add House';
    document.getElementById('h-id').value = d?.id || '';
    document.getElementById('h-name').value = d?.house_name || '';
    document.getElementById('h-code').value = d?.house_code || '';
    document.getElementById('h-loc').value = d?.location || '';
    document.getElementById('h-cap').value = d?.capacity || 5000;
    document.getElementById('house-modal').style.display = 'flex';
}
function closeHouseModal() { document.getElementById('house-modal').style.display = 'none'; }

document.getElementById('house-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('id', document.getElementById('h-id').value);
    fd.append('house_name', document.getElementById('h-name').value);
    fd.append('house_code', document.getElementById('h-code').value);
    fd.append('location', document.getElementById('h-loc').value);
    fd.append('capacity', document.getElementById('h-cap').value);
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=save_house', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) location.reload();
    else alert('Error: ' + r.message);
});

async function deleteHouse(id) {
    if (!confirm('Delete this house? Only houses without batches can be deleted.')) return;
    const fd = new FormData(); fd.append('id', id);
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=delete_house', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) location.reload(); else alert(r.message);
}

function openRecordModal(d) {
    document.getElementById('record-modal-title').textContent = d?.id ? 'Edit Daily Record' : 'Log Daily Record';
    document.getElementById('r-id').value = d?.id || '';
    document.getElementById('r-batch').value = d?.batch_id || (new URLSearchParams(location.search)).get('batch_id') || '';
    document.getElementById('r-date').value = d?.record_date || new Date().toISOString().split('T')[0];
    document.getElementById('r-open').value = d?.opening_birds || 0;
    document.getElementById('r-mort').value = d?.mortality || 0;
    document.getElementById('r-sold').value = d?.sold_birds || 0;
    document.getElementById('r-ewt').value = d?.expected_weight_kg || 0;
    document.getElementById('r-awt').value = d?.average_weight_kg || 0;
    document.getElementById('r-trays').value = d?.trays || 0;
    document.getElementById('r-eggs').value = d?.total_eggs || 0;
    document.getElementById('r-xl').value = d?.extra_large_eggs || 0;
    document.getElementById('r-dmg').value = d?.damaged_eggs || 0;
    document.getElementById('r-notes').value = d?.notes || '';
    document.getElementById('record-modal').style.display = 'flex';
}
function closeRecordModal() { document.getElementById('record-modal').style.display = 'none'; }

document.getElementById('record-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('id', document.getElementById('r-id').value);
    fd.append('batch_id', document.getElementById('r-batch').value);
    fd.append('record_date', document.getElementById('r-date').value);
    fd.append('opening_birds', document.getElementById('r-open').value);
    fd.append('mortality', document.getElementById('r-mort').value);
    fd.append('sold_birds', document.getElementById('r-sold').value);
    fd.append('expected_weight_kg', document.getElementById('r-ewt').value);
    fd.append('average_weight_kg', document.getElementById('r-awt').value);
    fd.append('trays', document.getElementById('r-trays').value);
    fd.append('total_eggs', document.getElementById('r-eggs').value);
    fd.append('extra_large_eggs', document.getElementById('r-xl').value);
    fd.append('damaged_eggs', document.getElementById('r-dmg').value);
    fd.append('notes', document.getElementById('r-notes').value);
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=save_batch_record', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) { closeRecordModal(); loadRecords(); }
    else alert('Error: ' + r.message);
});

async function loadRecords() {
    const tbody = document.getElementById('records-body');
    if (!tbody) return;
    const batch = document.getElementById('rec-filter-batch')?.value || '';
    const from = document.getElementById('rec-from')?.value || '';
    const to = document.getElementById('rec-to')?.value || '';
    let url = '/Backend/api/admin_poultry_v2.php?action=get_batch_records';
    if (batch) url += '&batch_id=' + batch;
    if (from) url += '&from=' + from;
    if (to) url += '&to=' + to;
    try {
        const res = await fetch(url);
        const r = await res.json();
        if (!r.success) { tbody.innerHTML = '<tr><td colspan="14" style="text-align:center;color:#dc2626;">' + (r.message||'Failed') + '</td></tr>'; return; }
        const data = r.data || [];
        if (!data.length) { tbody.innerHTML = '<tr><td colspan="14" style="text-align:center;padding:28px;color:#94a3b8;">No records yet.</td></tr>'; return; }
        tbody.innerHTML = data.map(r => {
            const pct = (parseFloat(r.production_pct) * 100).toFixed(2);
            return `<tr>
                <td>${r.record_date}</td>
                <td>${r.week_number||'—'}</td>
                <td><strong>#${r.batch_id}</strong></td>
                <td>${parseInt(r.opening_birds||0).toLocaleString()}</td>
                <td style="color:#dc2626;">${parseInt(r.mortality||0)}</td>
                <td>${parseInt(r.sold_birds||0)}</td>
                <td><strong>${parseInt(r.closing_birds||0).toLocaleString()}</strong></td>
                <td>${parseFloat(r.average_weight_kg||0).toFixed(3)}</td>
                <td>${parseInt(r.trays||0)}</td>
                <td><strong>${parseInt(r.total_eggs||0).toLocaleString()}</strong></td>
                <td>${parseInt(r.extra_large_eggs||0)}</td>
                <td>${parseInt(r.damaged_eggs||0)}</td>
                <td>${pct}%</td>
                <td>
                    <button class="btn btn-trans btn-sm" onclick='openRecordModal(${JSON.stringify(r)})'><i data-lucide="pencil" style="width:13px;height:13px;"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="deleteRecord(${r.id})"><i data-lucide="trash-2" style="width:13px;height:13px;"></i></button>
                </td>
            </tr>`;
        }).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="14" style="text-align:center;color:#dc2626;">Network error</td></tr>';
    }
}

async function deleteRecord(id) {
    if (!confirm('Delete this record?')) return;
    const fd = new FormData(); fd.append('id', id);
    const res = await fetch('/Backend/api/admin_poultry_v2.php?action=delete_batch_record', {method:'POST', body:fd});
    const r = await res.json();
    if (r.success) loadRecords(); else alert(r.message);
}

document.addEventListener('DOMContentLoaded', () => {
    loadRecords();
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php';
