<?php
/**
 * Hub: Crops & Fields
 * Tabs: Fields | Plantings | Field Activities | Harvests
 * Research-backed: field history (54%), activity logging (68%), cost per acre (61%).
 */
declare(strict_types=1);
$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','stock_manager'], true)) {
    echo "<script>window.location.href='/wangariadmin';</script>"; exit;
}

$page_title = 'Crops & Fields - Admin';
include __DIR__ . '/includes/admin_header.php';

$tab = $_GET['tab'] ?? 'fields';
$validTabs = ['fields','plantings','activities','harvests','costs','soil'];
if (!in_array($tab, $validTabs, true)) $tab = 'fields';

$pdo = getDB();
$message = ''; $error_message = '';

/* ── POST handlers ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $postAction = $_POST['_action'] ?? '';

    if ($postAction === 'save_field') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $loc  = trim($_POST['location'] ?? '');
        $size = (float)($_POST['size_acres'] ?? 0);
        $soil = trim($_POST['soil_type'] ?? '');
        $stat = trim($_POST['status'] ?? 'active');
        $notes= trim($_POST['notes'] ?? '');
        if (!$name) { $error_message = 'Field name is required.'; }
        else {
            try {
                if ($id > 0) {
                    $pdo->prepare('UPDATE fields SET name=?,location=?,size_acres=?,soil_type=?,status=?,notes=? WHERE id=?')
                        ->execute([$name,$loc,$size,$soil,$stat,$notes,$id]);
                    $message = 'Field updated.';
                } else {
                    $pdo->prepare('INSERT INTO fields (name,location,size_acres,soil_type,status,notes) VALUES (?,?,?,?,?,?)')
                        ->execute([$name,$loc,$size,$soil,$stat,$notes]);
                    $message = 'Field added.';
                }
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'fields';
    }

    if ($postAction === 'delete_field') {
        try {
            $pdo->prepare('DELETE FROM fields WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
            $message = 'Field deleted.';
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'fields';
    }

    if ($postAction === 'save_planting') {
        $id      = (int)($_POST['id'] ?? 0);
        $fieldId = (int)($_POST['field_id'] ?? 0);
        $crop    = trim($_POST['crop'] ?? '');
        $variety = trim($_POST['variety'] ?? '');
        $pdate   = trim($_POST['planting_date'] ?? date('Y-m-d'));
        $area    = (float)($_POST['area_acres'] ?? 0);
        $eharv   = trim($_POST['expected_harvest_date'] ?? '');
        $eyield  = (float)($_POST['expected_yield'] ?? 0);
        $unit    = trim($_POST['yield_unit'] ?? 'kg');
        $stat    = trim($_POST['status'] ?? 'growing');
        $notes   = trim($_POST['notes'] ?? '');
        if (!$fieldId || !$crop) { $error_message = 'Field and crop are required.'; }
        else {
            try {
                if ($id > 0) {
                    $pdo->prepare('UPDATE crop_plantings SET field_id=?,crop=?,variety=?,planting_date=?,area_acres=?,expected_harvest_date=?,expected_yield=?,yield_unit=?,status=?,notes=? WHERE id=?')
                        ->execute([$fieldId,$crop,$variety,$pdate,$area,$eharv?:null,$eyield,$unit,$stat,$notes,$id]);
                    $message = 'Planting updated.';
                } else {
                    $pdo->prepare('INSERT INTO crop_plantings (field_id,crop,variety,planting_date,area_acres,expected_harvest_date,expected_yield,yield_unit,status,notes) VALUES (?,?,?,?,?,?,?,?,?,?)')
                        ->execute([$fieldId,$crop,$variety,$pdate,$area,$eharv?:null,$eyield,$unit,$stat,$notes]);
                    $message = 'Planting recorded.';
                }
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'plantings';
    }

    if ($postAction === 'save_activity') {
        $plantingId = (int)($_POST['planting_id'] ?? 0);
        $type       = trim($_POST['activity_type'] ?? '');
        $date       = trim($_POST['activity_date'] ?? date('Y-m-d'));
        $cost       = (float)($_POST['cost'] ?? 0);
        $desc       = trim($_POST['description'] ?? '');
        if (!$plantingId || !$type) { $error_message = 'Planting and activity type are required.'; }
        else {
            try {
                $pdo->prepare('INSERT INTO crop_activities (planting_id,activity_type,activity_date,cost,description) VALUES (?,?,?,?,?)')
                    ->execute([$plantingId,$type,$date,$cost,$desc]);
                $message = 'Activity logged.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'activities';
    }

    if ($postAction === 'save_harvest') {
        $plantingId = (int)($_POST['planting_id'] ?? 0);
        $hdate      = trim($_POST['harvest_date'] ?? date('Y-m-d'));
        $qty        = (float)($_POST['quantity'] ?? 0);
        $unit       = trim($_POST['unit'] ?? 'kg');
        $price      = (float)($_POST['price_per_unit'] ?? 0);
        $buyer      = trim($_POST['buyer'] ?? '');
        $notes      = trim($_POST['notes'] ?? '');
        if (!$plantingId || $qty <= 0) { $error_message = 'Planting and quantity are required.'; }
        else {
            try {
                $revenue = $qty * $price;
                $pdo->prepare('INSERT INTO crop_harvests (planting_id,harvest_date,quantity,unit,price_per_unit,revenue,buyer,notes) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$plantingId,$hdate,$qty,$unit,$price,$revenue,$buyer,$notes]);
                // Auto-mark planting harvested
                $pdo->prepare('UPDATE crop_plantings SET status="harvested" WHERE id=?')->execute([$plantingId]);
                $message = 'Harvest recorded.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'harvests';
    }

    // Save crop cost
    if ($postAction === 'save_crop_cost') {
        try {
            $id = (int)($_POST['id'] ?? 0);
            $plantingId = (int)($_POST['planting_id'] ?? 0);
            $costType = trim($_POST['cost_type'] ?? '');
            $amount = (float)($_POST['amount'] ?? 0);
            $desc = trim($_POST['description'] ?? '');
            $costDate = trim($_POST['cost_date'] ?? date('Y-m-d'));
            if (!$plantingId || !$costType || $amount <= 0) {
                $error_message = 'Planting, cost type and amount are required.';
            } else {
                if ($id > 0) {
                    $pdo->prepare('UPDATE crop_costs SET planting_id=?,cost_type=?,amount=?,description=?,cost_date=? WHERE id=?')
                        ->execute([$plantingId,$costType,$amount,$desc,$costDate,$id]);
                    $message = 'Cost updated.';
                } else {
                    $pdo->prepare('INSERT INTO crop_costs (planting_id,cost_type,amount,description,cost_date) VALUES (?,?,?,?,?)')
                        ->execute([$plantingId,$costType,$amount,$desc,$costDate]);
                    $message = 'Cost recorded.';
                }
            }
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'costs';
    }

    // Save soil test
    if ($postAction === 'save_soil_test') {
        $id = (int)($_POST['id'] ?? 0);
        $fieldId = (int)($_POST['field_id'] ?? 0) ?: null;
        $testDate = trim($_POST['test_date'] ?? date('Y-m-d'));
        $ph = (float)($_POST['ph_level'] ?? 0) ?: null;
        $nitrogen = (float)($_POST['nitrogen_ppm'] ?? 0) ?: null;
        $phosphorus = (float)($_POST['phosphorus_ppm'] ?? 0) ?: null;
        $potassium = (float)($_POST['potassium_ppm'] ?? 0) ?: null;
        $organicMatter = (float)($_POST['organic_matter_pct'] ?? 0) ?: null;
        $moisture = (float)($_POST['moisture_pct'] ?? 0) ?: null;
        $ec = (float)($_POST['electrical_conductivity'] ?? 0) ?: null;
        $texture = trim($_POST['texture'] ?? '');
        $labName = trim($_POST['lab_name'] ?? '');
        $recs = trim($_POST['recommendations'] ?? '');
        $snotes = trim($_POST['notes'] ?? '');
        if (!$testDate) { $error_message = 'Test date is required.'; }
        else {
            try {
                if ($id > 0) {
                    $pdo->prepare('UPDATE soil_tests SET field_id=?,test_date=?,ph_level=?,nitrogen_ppm=?,phosphorus_ppm=?,potassium_ppm=?,organic_matter_pct=?,moisture_pct=?,electrical_conductivity=?,texture=?,lab_name=?,recommendations=?,notes=? WHERE id=?')->execute([$fieldId,$testDate,$ph,$nitrogen,$phosphorus,$potassium,$organicMatter,$moisture,$ec,$texture,$labName,$recs,$snotes,$id]);
                    $message = 'Soil test updated.';
                } else {
                    $pdo->prepare('INSERT INTO soil_tests (field_id,test_date,ph_level,nitrogen_ppm,phosphorus_ppm,potassium_ppm,organic_matter_pct,moisture_pct,electrical_conductivity,texture,lab_name,recommendations,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$fieldId,$testDate,$ph,$nitrogen,$phosphorus,$potassium,$organicMatter,$moisture,$ec,$texture,$labName,$recs,$snotes]);
                    $message = 'Soil test recorded.';
                }
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'soil';
    }

    // Save soil amendment
    if ($postAction === 'save_amendment') {
        $id = (int)($_POST['id'] ?? 0);
        $fieldId = (int)($_POST['field_id'] ?? 0) ?: null;
        $aDate = trim($_POST['amendment_date'] ?? date('Y-m-d'));
        $aType = trim($_POST['amendment_type'] ?? '');
        $product = trim($_POST['product_name'] ?? '');
        $qty = (float)($_POST['quantity_kg'] ?? 0);
        $method = trim($_POST['application_method'] ?? '');
        $cost = (float)($_POST['cost'] ?? 0);
        $purpose = trim($_POST['purpose'] ?? '');
        $anotes = trim($_POST['notes'] ?? '');
        if (!$aType) { $error_message = 'Amendment type is required.'; }
        else {
            try {
                if ($id > 0) {
                    $pdo->prepare('UPDATE soil_amendments SET field_id=?,amendment_date=?,amendment_type=?,product_name=?,quantity_kg=?,application_method=?,cost=?,purpose=?,notes=? WHERE id=?')->execute([$fieldId,$aDate,$aType,$product,$qty,$method,$cost,$purpose,$anotes,$id]);
                    $message = 'Amendment updated.';
                } else {
                    $pdo->prepare('INSERT INTO soil_amendments (field_id,amendment_date,amendment_type,product_name,quantity_kg,application_method,cost,purpose,notes) VALUES (?,?,?,?,?,?,?,?,?)')->execute([$fieldId,$aDate,$aType,$product,$qty,$method,$cost,$purpose,$anotes]);
                    $message = 'Amendment recorded.';
                }
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'soil';
    }
}

/* ── Load data ── */
$fields = $plantings = $activities = $harvests = $cropCosts = [];
$fieldOptions = [];
if ($pdo) {
    try {
        $fields = $pdo->query('SELECT f.*, (SELECT COUNT(*) FROM crop_plantings cp WHERE cp.field_id=f.id) AS planting_count FROM fields f ORDER BY f.name')->fetchAll();
        foreach ($fields as $f) $fieldOptions[$f['id']] = $f['name'];
        $plantings = $pdo->query('SELECT cp.*, f.name AS field_name FROM crop_plantings cp LEFT JOIN fields f ON f.id=cp.field_id ORDER BY cp.planting_date DESC')->fetchAll();
        $activities = $pdo->query('SELECT ca.*, cp.crop, f.name AS field_name FROM crop_activities ca LEFT JOIN crop_plantings cp ON cp.id=ca.planting_id LEFT JOIN fields f ON f.id=cp.field_id ORDER BY ca.activity_date DESC LIMIT 200')->fetchAll();
        $harvests = $pdo->query('SELECT ch.*, cp.crop, f.name AS field_name FROM crop_harvests ch LEFT JOIN crop_plantings cp ON cp.id=ch.planting_id LEFT JOIN fields f ON f.id=cp.field_id ORDER BY ch.harvest_date DESC LIMIT 200')->fetchAll();
        $cropCosts = $pdo->query('SELECT cc.*, cp.crop, f.name AS field_name, f.size_acres FROM crop_costs cc LEFT JOIN crop_plantings cp ON cp.id=cc.planting_id LEFT JOIN fields f ON f.id=cp.field_id ORDER BY cc.cost_date DESC LIMIT 200')->fetchAll();
        $soilTests = $pdo->query('SELECT st.*, f.name AS field_name FROM soil_tests st LEFT JOIN fields f ON f.id=st.field_id ORDER BY st.test_date DESC LIMIT 100')->fetchAll();
        $soilAmendments = $pdo->query('SELECT sa.*, f.name AS field_name FROM soil_amendments sa LEFT JOIN fields f ON f.id=sa.field_id ORDER BY sa.amendment_date DESC LIMIT 100')->fetchAll();
    } catch (Exception $e) { $error_message = $e->getMessage(); }
}

$tabs = [
    'fields'     => ['icon' => 'map',          'label' => 'Fields'],
    'plantings'  => ['icon' => 'sprout',       'label' => 'Plantings'],
    'activities' => ['icon' => 'clipboard-list','label' => 'Field Activities'],
    'harvests'   => ['icon' => 'wheat',        'label' => 'Harvests'],
    'costs'      => ['icon' => 'receipt',      'label' => 'Costs'],
    'soil'       => ['icon' => 'layers',       'label' => 'Soil Health'],
];
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);">Crops &amp; Fields</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Plant, log activities, and know your real cost per acre.</p>
    </div>
    <?php if ($tab === 'fields'): ?><button class="btn btn-primary" onclick="document.getElementById('field-modal').style.display='flex'"><i data-lucide="plus" style="width:15px;height:15px;"></i> Add Field</button><?php endif; ?>
    <?php if ($tab === 'plantings'): ?><button class="btn btn-primary" onclick="document.getElementById('planting-modal').style.display='flex'"><i data-lucide="plus" style="width:15px;height:15px;"></i> New Planting</button><?php endif; ?>
    <?php if ($tab === 'activities'): ?><button class="btn btn-primary" onclick="document.getElementById('activity-modal').style.display='flex'"><i data-lucide="plus" style="width:15px;height:15px;"></i> Log Activity</button><?php endif; ?>
    <?php if ($tab === 'harvests'): ?><button class="btn btn-primary" onclick="document.getElementById('harvest-modal').style.display='flex'"><i data-lucide="plus" style="width:15px;height:15px;"></i> Record Harvest</button><?php endif; ?>
</div>

<?php if ($message): ?>
<div style="padding:13px 18px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;color:#166534;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <i data-lucide="check-circle" style="width:18px;height:18px;"></i> <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>
<?php if ($error_message): ?>
<div style="padding:13px 18px;background:#fee2e2;border:1px solid #fecaca;border-radius:8px;color:#b91c1c;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <i data-lucide="alert-circle" style="width:18px;height:18px;"></i> <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
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

<?php if ($tab === 'fields'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Your Fields</h3>
        <span style="color:#64748b;font-size:0.85rem;"><?= count($fields) ?> fields · total <?= number_format(array_sum(array_column($fields,'size_acres')),1) ?> acres</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Field</th><th>Location</th><th>Size (acres)</th><th>Soil</th><th>Status</th><th>Plantings</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($fields)): ?>
                <tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">No fields yet. Add your first field to start tracking crops.</td></tr>
            <?php else: foreach ($fields as $f): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($f['location'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= number_format((float)$f['size_acres'], 1) ?></td>
                    <td><?= htmlspecialchars($f['soil_type'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge-pill <?= $f['status']==='active' ? 'badge-pill-success' : ($f['status']==='fallow' ? 'badge-pill-warning' : 'badge-pill-danger') ?>"><?= ucfirst($f['status']) ?></span></td>
                    <td><?= (int)$f['planting_count'] ?></td>
                    <td>
                        <div class="tbl-actions">
                            <button class="btn btn-outline btn-sm" onclick='editField(<?= json_encode($f, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i data-lucide="edit-3" style="width:13px;height:13px;"></i> Edit</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this field? Its plantings and history will be removed.');">
                                <input type="hidden" name="_action" value="delete_field">
                                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                <button class="btn btn-danger btn-sm"><i data-lucide="trash-2" style="width:13px;height:13px;"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Field Modal -->
<div id="field-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:460px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 style="margin:0 0 20px;font-family:'Outfit',sans-serif;" id="field-modal-title">Add Field</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_field">
            <input type="hidden" name="id" id="field-id" value="0">
            <div class="admin-form-group"><label class="admin-form-label">Field Name *</label>
                <input class="admin-form-control" type="text" name="name" id="field-name" required placeholder="e.g. Lower Shamba"></div>
            <div class="admin-form-group"><label class="admin-form-label">Location</label>
                <input class="admin-form-control" type="text" name="location" id="field-location" placeholder="e.g. Near the river"></div>
            <div class="admin-form-group"><label class="admin-form-label">Size (acres)</label>
                <input class="admin-form-control" type="number" step="0.01" min="0" name="size_acres" id="field-size" placeholder="0.00"></div>
            <div class="admin-form-group"><label class="admin-form-label">Soil Type</label>
                <input class="admin-form-control" type="text" name="soil_type" id="field-soil" placeholder="e.g. Red loam"></div>
            <div class="admin-form-group"><label class="admin-form-label">Status</label>
                <select class="admin-form-control" name="status" id="field-status">
                    <option value="active">Active</option>
                    <option value="fallow">Fallow</option>
                    <option value="leased_out">Leased Out</option>
                </select></div>
            <div class="admin-form-group"><label class="admin-form-label">Notes</label>
                <textarea class="admin-form-control" name="notes" id="field-notes" rows="2"></textarea></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('field-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Save Field</button>
            </div>
        </form>
    </div>
</div>
<script>
function editField(f) {
    document.getElementById('field-modal-title').textContent = 'Edit Field';
    document.getElementById('field-id').value = f.id;
    document.getElementById('field-name').value = f.name;
    document.getElementById('field-location').value = f.location || '';
    document.getElementById('field-size').value = f.size_acres;
    document.getElementById('field-soil').value = f.soil_type || '';
    document.getElementById('field-status').value = f.status;
    document.getElementById('field-notes').value = f.notes || '';
    document.getElementById('field-modal').style.display = 'flex';
}
</script>

<?php elseif ($tab === 'plantings'): ?>
<div class="admin-card">
    <h3 style="margin:0 0 16px;font-family:'Outfit',sans-serif;font-size:1.1rem;">Crop Plantings</h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Crop</th><th>Variety</th><th>Field</th><th>Planted</th><th>Area (acres)</th><th>Expected Harvest</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($plantings)): ?>
                <tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;"><strong>No plantings yet.</strong><br>Record what you planted and when with <strong>+ New Planting</strong> — seed type, field and expected harvest date.</td></tr>
            <?php else: foreach ($plantings as $p): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['crop'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($p['variety'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($p['field_name'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($p['planting_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= number_format((float)$p['area_acres'], 2) ?></td>
                    <td><?= htmlspecialchars($p['expected_harvest_date'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge-pill <?= $p['status']==='growing' ? 'badge-pill-success' : ($p['status']==='harvested' ? 'badge-pill-warning' : 'badge-pill-danger') ?>"><?= ucfirst($p['status']) ?></span></td>
                    <td>
                        <div class="tbl-actions">
                            <a class="btn btn-outline btn-sm" href="?tab=activities&planting=<?= (int)$p['id'] ?>"><i data-lucide="clipboard-list" style="width:13px;height:13px;"></i> Activities</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Planting Modal -->
<div id="planting-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 style="margin:0 0 20px;font-family:'Outfit',sans-serif;">New Planting</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_planting">
            <div class="admin-form-group"><label class="admin-form-label">Field *</label>
                <select class="admin-form-control" name="field_id" required>
                    <option value="">Select field…</option>
                    <?php foreach ($fieldOptions as $fid => $fname): ?><option value="<?= $fid ?>"><?= htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select></div>
            <div class="admin-form-group"><label class="admin-form-label">Crop *</label>
                <input class="admin-form-control" type="text" name="crop" required list="crop-list" placeholder="e.g. Maize, Coffee, Roses">
                <datalist id="crop-list"><option>Maize</option><option>Beans</option><option>Kale</option><option>Tomatoes</option><option>Cabbage</option><option>Onions</option><option>Avocado</option><option>Napier grass</option><option>Coffee</option><option>Tea</option><option>Wheat</option><option>Millet</option><option>Sorghum</option><option>Sugarcane</option><option>Roses</option><option>Carnations</option><option>Gerbera</option><option>Spinach</option><option>Carrots</option><option>Potatoes</option><option>Cassava</option><option>Cowpeas</option><option>Groundnuts</option><option>Soybeans</option><option>Sesame</option><option>Sunflower</option><option>Cotton</option><option>Pyrethrum</option><option>Sisal</option><option>Banana</option><option>Mango</option><option>Pawpaw</option><option>Passion fruit</option><option>French beans</option><option>Snow peas</option><option>Chili peppers</option><option>Coriander</option><option>Mint</option><option>Lemon grass</option></datalist></div>
            <div class="admin-form-group"><label class="admin-form-label">Variety</label>
                <input class="admin-form-control" type="text" name="variety" placeholder="e.g. H614"></div>
            <div class="admin-form-group"><label class="admin-form-label">Planting Date</label>
                <input class="admin-form-control" type="date" name="planting_date" value="<?= date('Y-m-d') ?>"></div>
            <div class="admin-form-group"><label class="admin-form-label">Area (acres)</label>
                <input class="admin-form-control" type="number" step="0.01" min="0" name="area_acres" placeholder="0.00"></div>
            <div class="admin-form-group"><label class="admin-form-label">Expected Harvest Date</label>
                <input class="admin-form-control" type="date" name="expected_harvest_date"></div>
            <div class="admin-form-group"><label class="admin-form-label">Expected Yield</label>
                <input class="admin-form-control" type="number" step="0.01" min="0" name="expected_yield" placeholder="0"> <small style="color:#94a3b8;">Unit: <input type="text" name="yield_unit" value="kg" style="width:60px;border:1px solid #e2e8f0;border-radius:4px;padding:2px 6px;"></small></div>
            <div class="admin-form-group"><label class="admin-form-label">Notes</label>
                <textarea class="admin-form-control" name="notes" rows="2"></textarea></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('planting-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Save Planting</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($tab === 'activities'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Field Activity Log</h3>
        <span style="color:#64748b;font-size:0.85rem;">Every activity builds your field history, so you always know what was done and what it cost.</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Field</th><th>Crop</th><th>Activity</th><th>Cost (KES)</th><th>Description</th></tr></thead>
            <tbody>
            <?php if (empty($activities)): ?>
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">No activities logged yet.</td></tr>
            <?php else: foreach ($activities as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['activity_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($a['field_name'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($a['crop'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge-pill badge-pill-success" style="text-transform:capitalize;"><?= htmlspecialchars($a['activity_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= $a['cost'] > 0 ? 'KES ' . number_format((float)$a['cost'], 0) : '—' ?></td>
                    <td><?= htmlspecialchars($a['description'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Activity Modal -->
<div id="activity-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:460px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 20px;font-family:'Outfit',sans-serif;">Log Activity</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_activity">
            <div class="admin-form-group"><label class="admin-form-label">Planting *</label>
                <select class="admin-form-control" name="planting_id" required>
                    <option value="">Select planting…</option>
                    <?php foreach ($plantings as $p): ?><option value="<?= (int)$p['id'] ?>" <?= isset($_GET['planting']) && (int)$_GET['planting']===(int)$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['crop'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($p['field_name'] ?: '—', ENT_QUOTES, 'UTF-8') ?>)</option><?php endforeach; ?>
                </select></div>
            <div class="admin-form-group"><label class="admin-form-label">Activity Type *</label>
                <select class="admin-form-control" name="activity_type" required>
                    <?php foreach (['Ploughing','Planting','Irrigation','Weeding','Fertilizing','Spraying','Pruning','Harvesting','Other'] as $t): ?><option><?= $t ?></option><?php endforeach; ?>
                </select></div>
            <div class="admin-form-group"><label class="admin-form-label">Date</label>
                <input class="admin-form-control" type="date" name="activity_date" value="<?= date('Y-m-d') ?>"></div>
            <div class="admin-form-group"><label class="admin-form-label">Cost (KES)</label>
                <input class="admin-form-control" type="number" step="0.01" min="0" name="cost" placeholder="0"></div>
            <div class="admin-form-group"><label class="admin-form-label">Description</label>
                <textarea class="admin-form-control" name="description" rows="2" placeholder="e.g. Applied DAP, 2 bags"></textarea></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('activity-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Log Activity</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($tab === 'harvests'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Harvests</h3>
        <span style="color:#64748b;font-size:0.85rem;">Total revenue: <strong style="color:var(--admin-primary);">KES <?= number_format(array_sum(array_column($harvests,'revenue')),0) ?></strong></span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Field</th><th>Crop</th><th>Quantity</th><th>Price/Unit</th><th>Revenue (KES)</th><th>Buyer</th></tr></thead>
            <tbody>
            <?php if (empty($harvests)): ?>
                <tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;"><strong>No harvests recorded yet.</strong><br>Log yield per field with <strong>+ Record Harvest</strong> to build your production history.</td></tr>
            <?php else: foreach ($harvests as $h): ?>
                <tr>
                    <td><?= htmlspecialchars($h['harvest_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($h['field_name'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><strong><?= htmlspecialchars($h['crop'] ?: '—', ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= number_format((float)$h['quantity'], 1) ?> <?= htmlspecialchars($h['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= number_format((float)$h['price_per_unit'], 0) ?></td>
                    <td><strong>KES <?= number_format((float)$h['revenue'], 0) ?></strong></td>
                    <td><?= htmlspecialchars($h['buyer'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Harvest Modal -->
<div id="harvest-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:460px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 20px;font-family:'Outfit',sans-serif;">Record Harvest</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_harvest">
            <div class="admin-form-group"><label class="admin-form-label">Planting *</label>
                <select class="admin-form-control" name="planting_id" required>
                    <option value="">Select planting…</option>
                    <?php foreach ($plantings as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['crop'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($p['field_name'] ?: '—', ENT_QUOTES, 'UTF-8') ?>)</option><?php endforeach; ?>
                </select></div>
            <div class="admin-form-group"><label class="admin-form-label">Harvest Date</label>
                <input class="admin-form-control" type="date" name="harvest_date" value="<?= date('Y-m-d') ?>"></div>
            <div class="admin-form-group"><label class="admin-form-label">Quantity *</label>
                <input class="admin-form-control" type="number" step="0.01" min="0" name="quantity" required placeholder="0"></div>
            <div class="admin-form-group"><label class="admin-form-label">Unit</label>
                <select class="admin-form-control" name="unit"><option>kg</option><option>bags</option><option>crates</option><option>tonnes</option><option>pieces</option><option>stems</option><option>bunches</option><option>bulbs</option><option>sacks</option><option>litres</option><option>boxes</option><option>trays</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Price per Unit (KES)</label>
                <input class="admin-form-control" type="number" step="0.01" min="0" name="price_per_unit" placeholder="0"></div>
            <div class="admin-form-group"><label class="admin-form-label">Buyer</label>
                <input class="admin-form-control" type="text" name="buyer" placeholder="e.g. Broker from market"></div>
            <div class="admin-form-group"><label class="admin-form-label">Notes</label>
                <textarea class="admin-form-control" name="notes" rows="2"></textarea></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('harvest-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Save Harvest</button>
            </div>
        </form>
    </div>
</div>
<!-- ══════ CROP COSTS TAB ══════ -->
<?php elseif ($tab === 'costs'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Crop Cost Tracking</h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Track costs per planting to calculate cost per acre and profitability.</p>
        </div>
        <button class="btn btn-primary" onclick="openCostModal()"><i data-lucide="plus" style="width:16px;height:16px;"></i> Add Cost</button>
    </div>

    <?php if (!empty($cropCosts)): ?>
    <div style="display:flex;gap:16px;margin-bottom:20px;flex-wrap:wrap;">
        <div class="stat-card"><div class="stat-card-icon"><i data-lucide="calculator"></i></div><div class="stat-card-info"><strong>KES <?= number_format(array_sum(array_column($cropCosts, 'amount')), 0) ?></strong><small>Total Costs</small></div></div>
        <div class="stat-card"><div class="stat-card-icon info"><i data-lucide="receipt"></i></div><div class="stat-card-info"><strong><?= count($cropCosts) ?></strong><small>Cost Entries</small></div></div>
    </div>
    <?php endif; ?>

    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Crop</th><th>Field</th><th>Cost Type</th><th>Amount (KES)</th><th>Cost/Acre</th><th>Description</th></tr></thead>
            <tbody>
            <?php if (empty($cropCosts)): ?>
                <tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">No costs recorded yet. Click <strong>Add Cost</strong> to start tracking expenses.</td></tr>
            <?php else: foreach ($cropCosts as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['cost_date'], ENT_QUOTES) ?></td>
                    <td><strong><?= htmlspecialchars($c['crop'] ?? '—', ENT_QUOTES) ?></strong></td>
                    <td><?= htmlspecialchars($c['field_name'] ?? '—', ENT_QUOTES) ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($c['cost_type'], ENT_QUOTES) ?></span></td>
                    <td><?= number_format((float)$c['amount'], 0) ?></td>
                    <td><?= ($c['size_acres'] > 0) ? number_format((float)$c['amount'] / (float)$c['size_acres'], 0) : '—' ?></td>
                    <td><?= htmlspecialchars($c['description'] ?? '', ENT_QUOTES) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="cost-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add Crop Cost</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_crop_cost"><input type="hidden" name="id" id="c-id">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Planting *</label><select class="admin-form-control" name="planting_id" id="c-planting" required><option value="">-- Select Planting --</option><?php foreach ($plantings as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['crop'], ENT_QUOTES) ?> (<?= htmlspecialchars($p['field_name'] ?? '—', ENT_QUOTES) ?>)</option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Cost Type *</label><select class="admin-form-control" name="cost_type" id="c-type" required><option value="Seeds">Seeds</option><option value="Fertilizer">Fertilizer</option><option value="Pesticide">Pesticide</option><option value="Labor">Labor</option><option value="Irrigation">Irrigation</option><option value="Transport">Transport</option><option value="Equipment">Equipment</option><option value="Other">Other</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Amount (KES) *</label><input class="admin-form-control" type="number" step="0.01" min="0" name="amount" id="c-amount" required placeholder="0"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" name="cost_date" id="c-date" value="<?= date('Y-m-d') ?>" required></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Description</label><textarea class="admin-form-control" name="description" id="c-desc" rows="2" placeholder="Optional notes..."></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('cost-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
        </div></form>
    </div>
</div>
<script>
function openCostModal(){
    document.getElementById('cost-modal').style.display='flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('cost-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>

<!-- ══════ SOIL HEALTH TAB ══════ -->
<?php elseif ($tab === 'soil'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Soil Health Management</h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Track soil tests, amendments, pH levels, and nutrient profiles per field.</p>
        </div>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-outline" onclick="openAmendmentModal()"><i data-lucide="droplets" style="width:15px;height:15px;"></i> Log Amendment</button>
            <button class="btn btn-primary" onclick="openSoilTestModal()"><i data-lucide="plus-circle" style="width:15px;height:15px;"></i> Record Soil Test</button>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:20px;">
        <div class="stat-card" style="min-width:0;"><div class="stat-card-icon"><i data-lucide="flask-conical"></i></div><div class="stat-card-info"><strong><?= count($soilTests ?? []) ?></strong><small>Soil Tests</small></div></div>
        <div class="stat-card" style="min-width:0;"><div class="stat-card-icon accent"><i data-lucide="droplets"></i></div><div class="stat-card-info"><strong><?= count($soilAmendments ?? []) ?></strong><small>Amendments</small></div></div>
        <div class="stat-card" style="min-width:0;"><div class="stat-card-icon info"><i data-lucide="test-tube"></i></div><div class="stat-card-info"><strong><?= (!empty($soilTests) && isset($soilTests[0]['ph_level'])) ? number_format((float)$soilTests[0]['ph_level'], 1) : '—' ?></strong><small>Latest pH</small></div></div>
    </div>
    <h4 style="margin:0 0 12px;font-size:0.95rem;">Soil Test Results</h4>
    <div class="table-responsive" style="margin-bottom:24px;">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Field</th><th>pH</th><th>N (ppm)</th><th>P (ppm)</th><th>K (ppm)</th><th>Organic %</th><th>Texture</th></tr></thead>
            <tbody>
            <?php if (empty($soilTests ?? [])): ?>
                <tr><td colspan="8" style="text-align:center;padding:24px;color:#94a3b8;"><strong>No soil tests recorded yet.</strong><br>Click <strong>+ Record Soil Test</strong> to start tracking soil health.</td></tr>
            <?php else: foreach ($soilTests as $st): ?>
                <tr>
                    <td><?= $st['test_date'] ?></td>
                    <td><strong><?= htmlspecialchars($st['field_name'] ?? 'All Fields', ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?php $ph = (float)($st['ph_level'] ?? 0); $pc = ($ph >= 6.0 && $ph <= 7.5) ? '#16a34a' : '#d97706'; ?><span style="font-weight:700;color:<?= $pc ?>;"><?= $ph > 0 ? number_format($ph, 1) : '—' ?></span></td>
                    <td><?= $st['nitrogen_ppm'] ? number_format((float)$st['nitrogen_ppm'], 1) : '—' ?></td>
                    <td><?= $st['phosphorus_ppm'] ? number_format((float)$st['phosphorus_ppm'], 1) : '—' ?></td>
                    <td><?= $st['potassium_ppm'] ? number_format((float)$st['potassium_ppm'], 1) : '—' ?></td>
                    <td><?= $st['organic_matter_pct'] ? number_format((float)$st['organic_matter_pct'], 1).'%' : '—' ?></td>
                    <td><?= htmlspecialchars($st['texture'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <h4 style="margin:0 0 12px;font-size:0.95rem;">Amendment History</h4>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Field</th><th>Type</th><th>Product</th><th>Qty (kg)</th><th>Cost</th></tr></thead>
            <tbody>
            <?php if (empty($soilAmendments ?? [])): ?>
                <tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;"><strong>No amendments logged yet.</strong></td></tr>
            <?php else: foreach ($soilAmendments as $sa): ?>
                <tr>
                    <td><?= $sa['amendment_date'] ?></td>
                    <td><strong><?= htmlspecialchars($sa['field_name'] ?? 'All Fields', ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><span class="badge-pill badge-pill-success"><?= htmlspecialchars($sa['amendment_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($sa['product_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $sa['quantity_kg'] ? number_format((float)$sa['quantity_kg'], 1) : '—' ?></td>
                    <td><?= $sa['cost'] > 0 ? 'KES '.number_format((float)$sa['cost'], 2) : '—' ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Soil Test Modal -->
<div id="soil-test-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:560px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Record Soil Test</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_soil_test">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Field</label><select class="admin-form-control" name="field_id"><option value="">All Fields</option><?php foreach ($fields as $f): ?><option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="admin-form-group"><label class="admin-form-label">Test Date *</label><input class="admin-form-control" type="date" name="test_date" value="<?= date('Y-m-d') ?>" required></div>
                <div class="admin-form-group"><label class="admin-form-label">pH Level</label><input class="admin-form-control" type="number" step="0.01" name="ph_level" placeholder="6.5"></div>
                <div class="admin-form-group"><label class="admin-form-label">Texture</label><select class="admin-form-control" name="texture"><option value="">Select...</option><option>Sandy</option><option>Clay</option><option>Loam</option><option>Sandy Loam</option><option>Clay Loam</option><option>Silt</option></select></div>
                <div class="admin-form-group"><label class="admin-form-label">Nitrogen (ppm)</label><input class="admin-form-control" type="number" step="0.01" name="nitrogen_ppm"></div>
                <div class="admin-form-group"><label class="admin-form-label">Phosphorus (ppm)</label><input class="admin-form-control" type="number" step="0.01" name="phosphorus_ppm"></div>
                <div class="admin-form-group"><label class="admin-form-label">Potassium (ppm)</label><input class="admin-form-control" type="number" step="0.01" name="potassium_ppm"></div>
                <div class="admin-form-group"><label class="admin-form-label">Organic Matter %</label><input class="admin-form-control" type="number" step="0.01" name="organic_matter_pct"></div>
                <div class="admin-form-group"><label class="admin-form-label">Moisture %</label><input class="admin-form-control" type="number" step="0.01" name="moisture_pct"></div>
                <div class="admin-form-group"><label class="admin-form-label">Lab Name</label><input class="admin-form-control" name="lab_name" placeholder="County Agricultural Lab"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Recommendations</label><textarea class="admin-form-control" name="recommendations" rows="2"></textarea></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" rows="2"></textarea></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('soil-test-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Save Test</button>
            </div>
        </form>
    </div>
</div>

<!-- Amendment Modal -->
<div id="amendment-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Log Amendment</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_amendment">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Field</label><select class="admin-form-control" name="field_id"><option value="">All Fields</option><?php foreach ($fields as $f): ?><option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="admin-form-group"><label class="admin-form-label">Date</label><input class="admin-form-control" type="date" name="amendment_date" value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Type *</label><select class="admin-form-control" name="amendment_type" required><option value="">Select...</option><option>Fertilizer</option><option>Lime</option><option>Compost</option><option>Manure</option><option>Gypsum</option><option>Other</option></select></div>
                <div class="admin-form-group"><label class="admin-form-label">Product Name</label><input class="admin-form-control" name="product_name" placeholder="DAP, CAN, NPK"></div>
                <div class="admin-form-group"><label class="admin-form-label">Quantity (kg)</label><input class="admin-form-control" type="number" step="0.1" name="quantity_kg"></div>
                <div class="admin-form-group"><label class="admin-form-label">Method</label><select class="admin-form-control" name="application_method"><option value="">Select...</option><option>Broadcasting</option><option>Side dressing</option><option>Foliar spray</option><option>Top dressing</option><option>Incorporation</option></select></div>
                <div class="admin-form-group"><label class="admin-form-label">Cost (KES)</label><input class="admin-form-control" type="number" step="0.01" name="cost"></div>
                <div class="admin-form-group"><label class="admin-form-label">Purpose</label><input class="admin-form-control" name="purpose" placeholder="Boost nitrogen before planting"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" rows="2"></textarea></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('amendment-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Save</button>
            </div>
        </form>
    </div>
</div>
<script>
function openSoilTestModal() { document.getElementById('soil-test-modal').style.display = 'flex'; }
function openAmendmentModal() { document.getElementById('amendment-modal').style.display = 'flex'; }
document.addEventListener('click', e => { ['soil-test-modal','amendment-modal'].forEach(id => { const m = document.getElementById(id); if (m && e.target === m) m.style.display = 'none'; }); });
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
