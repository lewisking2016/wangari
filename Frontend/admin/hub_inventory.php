<?php
/**
 * Hub: Inventory & Store, ALL content inline, no double-includes.
 * Tabs: Products | Farm Equipment | Feed & Stock | Alerts
 */
declare(strict_types=1);
$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','stock_manager','sales_staff'], true)) {
    echo "<script>window.location.href='/wangariadmin';</script>"; exit;
}

$page_title = 'Inventory & Store - Admin';
include __DIR__ . '/includes/admin_header.php';

$tab = $_GET['tab'] ?? 'products';
$validTabs = ['products','equipment','maintenance','feedstock','alerts'];
if (!in_array($tab, $validTabs, true)) $tab = 'products';

$pdo = getDB();
$message = ''; $error_message = '';

/* ── POST Handler for Farm Equipment + Maintenance ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $postAction = $_POST['_action'] ?? '';
    if ($postAction === 'save_equipment') {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $qty      = (int)($_POST['quantity'] ?? 1);
        $cond     = trim($_POST['condition_status'] ?? 'Good');
        $pur_date = trim($_POST['purchase_date'] ?? '');
        $cost     = (float)($_POST['cost'] ?? 0);
        $notes    = trim($_POST['notes'] ?? '');

        if ($name === '') {
            $error_message = 'Item name is required.';
        } else {
            try {
                if ($id > 0) {
                    $pdo->prepare('UPDATE farm_equipment SET name=?,category=?,quantity=?,condition_status=?,purchase_date=?,cost=?,notes=? WHERE id=?')
                        ->execute([$name,$category,$qty,$cond,$pur_date?:null,$cost?:null,$notes,$id]);
                    $message = 'Equipment updated successfully.';
                } else {
                    $pdo->prepare('INSERT INTO farm_equipment (name,category,quantity,condition_status,purchase_date,cost,notes) VALUES (?,?,?,?,?,?,?)')
                        ->execute([$name,$category,$qty,$cond,$pur_date?:null,$cost?:null,$notes]);
                    $message = 'Equipment added successfully.';
                }
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'equipment';
    }

    // Save maintenance record
    if ($postAction === 'save_maintenance') {
        $id   = (int)($_POST['id'] ?? 0);
        $eqId = (int)($_POST['equipment_id'] ?? 0);
        $type = trim($_POST['maintenance_type'] ?? 'preventive');
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $schedDate = trim($_POST['scheduled_date'] ?? date('Y-m-d'));
        $compDate = trim($_POST['completed_date'] ?? '');
        $status = trim($_POST['status'] ?? 'scheduled');
        $cost = (float)($_POST['cost'] ?? 0);
        $performedBy = trim($_POST['performed_by'] ?? '');
        $nextDue = trim($_POST['next_due_date'] ?? '');
        $mnotes = trim($_POST['notes'] ?? '');

        if (!$eqId || !$title) {
            $error_message = 'Equipment and title are required.';
        } else {
            try {
                if ($id > 0) {
                    $pdo->prepare('UPDATE equipment_maintenance SET equipment_id=?,maintenance_type=?,title=?,description=?,scheduled_date=?,completed_date=?,status=?,cost=?,performed_by=?,next_due_date=?,notes=? WHERE id=?')
                        ->execute([$eqId,$type,$title,$desc,$schedDate,$compDate?:null,$status,$cost,$performedBy,$nextDue?:null,$mnotes,$id]);
                    $message = 'Maintenance record updated.';
                } else {
                    $pdo->prepare('INSERT INTO equipment_maintenance (equipment_id,maintenance_type,title,description,scheduled_date,completed_date,status,cost,performed_by,next_due_date,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                        ->execute([$eqId,$type,$title,$desc,$schedDate,$compDate?:null,$status,$cost,$performedBy,$nextDue?:null,$mnotes]);
                    $message = 'Maintenance scheduled.';
                }
                // Update equipment last_service_date if completed
                if ($status === 'completed' && $compDate) {
                    $pdo->prepare('UPDATE farm_equipment SET last_service_date=?, condition_status=IF(condition_status="Poor","Fair",condition_status) WHERE id=?')->execute([$compDate, $eqId]);
                }
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'maintenance';
    }

    // Complete maintenance
    if ($postAction === 'complete_maintenance') {
        $mId = (int)($_POST['id'] ?? 0);
        try {
            $pdo->prepare('UPDATE equipment_maintenance SET status="completed", completed_date=CURDATE() WHERE id=?')->execute([$mId]);
            $message = 'Maintenance marked as completed.';
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'maintenance';
    }

    // Save usage log
    if ($postAction === 'save_usage') {
        $eqId = (int)($_POST['equipment_id'] ?? 0);
        $date = trim($_POST['usage_date'] ?? date('Y-m-d'));
        $hours = (float)($_POST['hours_used'] ?? 0);
        $task = trim($_POST['task'] ?? '');
        $operator = trim($_POST['operator'] ?? '');
        $fuel = (float)($_POST['fuel_cost'] ?? 0);
        $unotes = trim($_POST['notes'] ?? '');

        if (!$eqId) {
            $error_message = 'Select equipment.';
        } else {
            try {
                $pdo->prepare('INSERT INTO equipment_usage (equipment_id,usage_date,hours_used,task,operator,fuel_cost,notes) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$eqId,$date,$hours,$task,$operator,$fuel,$unotes]);
                // Update total usage hours on equipment
                $pdo->prepare('UPDATE farm_equipment SET total_usage_hours = total_usage_hours + ? WHERE id=?')->execute([$hours, $eqId]);
                $message = 'Usage logged.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'maintenance';
    }
}

/* ── Load PHP-based tab data ── */
$farmItems = [];
$maintenanceRecords = [];
$usageRecords = [];
$maintenanceStats = ['scheduled' => 0, 'overdue' => 0, 'completed' => 0];
if ($pdo && $tab === 'equipment') {
    try {
        $farmItems = $pdo->query('SELECT * FROM farm_equipment ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $error_message = $e->getMessage(); }
}
if ($pdo && $tab === 'maintenance') {
    try {
        $farmItems = $pdo->query('SELECT * FROM farm_equipment ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
        $maintenanceRecords = $pdo->query('SELECT em.*, fe.name as equipment_name FROM equipment_maintenance em LEFT JOIN farm_equipment fe ON em.equipment_id = fe.id ORDER BY em.scheduled_date DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
        $usageRecords = $pdo->query('SELECT eu.*, fe.name as equipment_name FROM equipment_usage eu LEFT JOIN farm_equipment fe ON eu.equipment_id = fe.id ORDER BY eu.usage_date DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
        // Stats
        $maintenanceStats = $pdo->query("SELECT
            SUM(CASE WHEN status='scheduled' THEN 1 ELSE 0 END) as scheduled,
            SUM(CASE WHEN status='overdue' OR (status='scheduled' AND scheduled_date < CURDATE()) THEN 1 ELSE 0 END) as overdue,
            SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed
            FROM equipment_maintenance")->fetch(PDO::FETCH_ASSOC) ?: $maintenanceStats;
    } catch (Exception $e) { $error_message = $e->getMessage(); }
}

$tabs = [
    'products'     => ['icon' => 'package',       'label' => 'Products Catalog'],
    'equipment'    => ['icon' => 'wrench',        'label' => 'Farm Equipment & Tools'],
    'maintenance'  => ['icon' => 'wrench',        'label' => 'Maintenance & Usage'],
    'feedstock'    => ['icon' => 'layers',        'label' => 'Feed & Raw Stock'],
    'alerts'       => ['icon' => 'bell',           'label' => 'Inventory Alerts'],
];
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Inventory & Store</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Manage store products, farm tools, raw feed materials, and critical reorder alerts.</p>
    </div>
</div>

<?php if ($message): ?>
<div style="padding:13px 18px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;color:#166534;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <i data-lucide="check-circle-2" style="width:18px;height:18px;"></i> <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
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

<!-- ══════ PRODUCTS CATALOG TAB (API / AJAX IFRAME LOGIC) ══════ -->
<?php if ($tab === 'products'): ?>
<div class="admin-card" style="padding:0; overflow:hidden;">
    <iframe src="products.php" style="width:100%; height:800px; border:none; display:block;"></iframe>
</div>

<!-- ══════ EQUIPMENT TAB ══════ -->
<?php elseif ($tab === 'equipment'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Farm Equipment & Tools Registry</h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Track tools, machinery, structures, and their current maintenance status.</p>
        </div>
        <button class="btn btn-primary" onclick="openEquipModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Add Tool / Equipment</button>
    </div>

    <!-- Equipment KPI Row -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:20px;">
        <div class="stat-card" style="min-width:0;"><div class="stat-card-icon"><i data-lucide="wrench"></i></div><div class="stat-card-info"><strong><?= count($farmItems) ?></strong><small>Total Equipment</small></div></div>
        <div class="stat-card" style="min-width:0;"><div class="stat-card-icon accent"><i data-lucide="check-circle"></i></div><div class="stat-card-info"><strong><?= count(array_filter($farmItems, fn($e) => ($e['condition_status'] ?? '') === 'Good' || ($e['condition_status'] ?? '') === 'New')) ?></strong><small>Good Condition</small></div></div>
        <div class="stat-card" style="min-width:0;"><div class="stat-card-icon" style="background:#fee2e2;color:#dc2626;"><i data-lucide="alert-triangle"></i></div><div class="stat-card-info"><strong><?= count(array_filter($farmItems, fn($e) => ($e['condition_status'] ?? '') === 'Poor')) ?></strong><small>Needs Attention</small></div></div>
        <div class="stat-card" style="min-width:0;"><div class="stat-card-icon info"><i data-lucide="clock"></i></div><div class="stat-card-info"><strong><?= number_format(array_sum(array_map(fn($e) => (float)($e['total_usage_hours'] ?? 0), $farmItems)), 1) ?></strong><small>Total Hours Used</small></div></div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Name</th><th>Category</th><th>Quantity</th><th>Condition</th><th>Purchase Date</th><th>Cost</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($farmItems)): ?>
                <tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;"><strong>No equipment logged yet.</strong><br>Register tools, machinery and structures with <strong>+ Add Tool / Equipment</strong> to track condition and value.</td></tr>
            <?php else: foreach ($farmItems as $fi): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($fi['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($fi['category'] ?? 'Other', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)($fi['quantity'] ?? 1) ?></td>
                    <td>
                        <?php
                        $c = $fi['condition_status'] ?? 'Good';
                        $pill = $c==='Good'||$c==='New'?'badge-pill-success':($c==='Fair'?'badge-pill-warning':'badge-pill-danger');
                        ?>
                        <span class="badge-pill <?= $pill ?>"><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td><?= htmlspecialchars($fi['purchase_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $fi['cost'] ? 'KES '.number_format((float)$fi['cost'], 2) : '-' ?></td>
                    <td style="white-space:nowrap;">
                        <?php if ($fi['next_service_date']): ?>
                            <?php if ($fi['next_service_date'] <= date('Y-m-d')): ?>
                                <span class="badge-pill badge-pill-danger" title="Service overdue!">Service Due</span>
                            <?php else: ?>
                                <span style="font-size:0.8rem;color:#64748b;">Next: <?= $fi['next_service_date'] ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openEquipModal(<?= htmlspecialchars(json_encode($fi), ENT_QUOTES, "UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button></div></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="equip-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:520px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 id="equip-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add Equipment / Tool</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_equipment">
            <input type="hidden" name="id" id="eq-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Item Name *</label><input class="admin-form-control" name="name" id="eq-name" required placeholder="e.g. Feed Mixer, Brooder Heater"></div>
                <div class="admin-form-group"><label class="admin-form-label">Category</label>
                    <select class="admin-form-control" name="category" id="eq-cat">
                        <option>Machinery</option><option>Tools</option><option>Feeding Equipment</option><option>Watering Equipment</option><option>Structures</option><option>Other</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Quantity</label><input class="admin-form-control" type="number" name="quantity" id="eq-qty" min="1" value="1"></div>
                <div class="admin-form-group"><label class="admin-form-label">Condition</label>
                    <select class="admin-form-control" name="condition_status" id="eq-cond">
                        <option>New</option><option>Good</option><option>Fair</option><option>Poor</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Purchase Date</label><input class="admin-form-control" type="date" name="purchase_date" id="eq-date"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Estimated Cost (KES)</label><input class="admin-form-control" type="number" step="0.01" name="cost" id="eq-cost"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes / Storage Location</label><textarea class="admin-form-control" name="notes" id="eq-notes" rows="2"></textarea></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('equip-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Tool</button>
            </div>
        </form>
    </div>
</div>
<script>
function openEquipModal(d) {
    document.getElementById('equip-modal-title').textContent = d?.id ? 'Edit Equipment / Tool' : 'Add Equipment / Tool';
    document.getElementById('eq-id').value = d?.id || '';
    document.getElementById('eq-name').value = d?.name || '';
    document.getElementById('eq-cat').value = d?.category || 'Tools';
    document.getElementById('eq-qty').value = d?.quantity || 1;
    document.getElementById('eq-cond').value = d?.condition_status || 'Good';
    document.getElementById('eq-date').value = d?.purchase_date || '';
    document.getElementById('eq-cost').value = d?.cost || '';
    document.getElementById('eq-notes').value = d?.notes || '';
    document.getElementById('equip-modal').style.display = 'flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('equip-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>

<!-- ══════ MAINTENANCE & USAGE TAB ══════ -->
<?php elseif ($tab === 'maintenance'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Equipment Maintenance & Usage</h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Schedule preventive maintenance, track service history, and log equipment usage.</p>
        </div>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-outline" onclick="openUsageModal()"><i data-lucide="clock" style="width:15px;height:15px;"></i> Log Usage</button>
            <button class="btn btn-primary" onclick="openMaintModal()"><i data-lucide="plus-circle" style="width:15px;height:15px;"></i> Schedule Maintenance</button>
        </div>
    </div>

    <!-- Maintenance KPI Row -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:20px;">
        <div class="stat-card" style="min-width:0;"><div class="stat-card-icon"><i data-lucide="calendar"></i></div><div class="stat-card-info"><strong><?= (int)($maintenanceStats['scheduled'] ?? 0) ?></strong><small>Scheduled</small></div></div>
        <div class="stat-card" style="min-width:0;"><div class="stat-card-icon" style="background:#fee2e2;color:#dc2626;"><i data-lucide="alert-triangle"></i></div><div class="stat-card-info"><strong><?= (int)($maintenanceStats['overdue'] ?? 0) ?></strong><small>Overdue</small></div></div>
        <div class="stat-card" style="min-width:0;"><div class="stat-card-icon accent"><i data-lucide="check-circle"></i></div><div class="stat-card-info"><strong><?= (int)($maintenanceStats['completed'] ?? 0) ?></strong><small>Completed</small></div></div>
        <div class="stat-card" style="min-width:0;"><div class="stat-card-icon info"><i data-lucide="bar-chart-2"></i></div><div class="stat-card-info"><strong><?= count($usageRecords) ?></strong><small>Usage Logs</small></div></div>
    </div>

    <!-- Upcoming / Overdue Maintenance -->
    <h4 style="margin:0 0 12px;font-size:0.95rem;color:var(--admin-text-heading);">Maintenance Schedule</h4>
    <div class="table-responsive" style="margin-bottom:24px;">
        <table class="admin-table">
            <thead><tr><th>Equipment</th><th>Type</th><th>Title</th><th>Scheduled</th><th>Status</th><th>Cost</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($maintenanceRecords)): ?>
                <tr><td colspan="7" style="text-align:center;padding:24px;color:#94a3b8;"><strong>No maintenance records yet.</strong><br>Click <strong>+ Schedule Maintenance</strong> to plan preventive servicing.</td></tr>
            <?php else: foreach ($maintenanceRecords as $mr): ?>
                <?php
                    $isOverdue = ($mr['status'] !== 'completed' && $mr['scheduled_date'] < date('Y-m-d'));
                    $typeColors = ['preventive'=>'#dcfce7,#166534','corrective'=>'#fee2e2,#b91c1c','inspection'=>'#dbeafe,#1e40af','calibration'=>'#fef3c7,#92400e'];
                    $tc = $typeColors[$mr['maintenance_type']] ?? '#f1f5f9,#475569';
                    $tcParts = explode(',', $tc);
                ?>
                <tr style="<?= $isOverdue ? 'background:#fef2f2;' : '' ?>">
                    <td><strong><?= htmlspecialchars($mr['equipment_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><span class="badge-pill" style="background:<?= $tcParts[0] ?>;color:<?= $tcParts[1] ?>;"><?= ucfirst(htmlspecialchars($mr['maintenance_type'], ENT_QUOTES, 'UTF-8')) ?></span></td>
                    <td><?= htmlspecialchars($mr['title'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $mr['scheduled_date'] ?></td>
                    <td>
                        <?php if ($mr['status'] === 'completed'): ?>
                            <span class="badge-pill badge-pill-success">Completed</span>
                        <?php elseif ($isOverdue): ?>
                            <span class="badge-pill badge-pill-danger">Overdue</span>
                        <?php else: ?>
                            <span class="badge-pill badge-pill-warning">Scheduled</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $mr['cost'] > 0 ? 'KES '.number_format((float)$mr['cost'], 2) : '-' ?></td>
                    <td>
                        <?php if ($mr['status'] !== 'completed'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="_action" value="complete_maintenance">
                                <input type="hidden" name="id" value="<?= $mr['id'] ?>">
                                <button type="submit" class="btn btn-trans btn-sm" title="Mark completed"><i data-lucide="check" style="width:13px;height:13px;"></i> Done</button>
                            </form>
                        <?php endif; ?>
                        <button class="btn btn-trans btn-sm" onclick='openMaintModal(<?= htmlspecialchars(json_encode($mr), ENT_QUOTES, "UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i></button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Usage Log -->
    <h4 style="margin:0 0 12px;font-size:0.95rem;color:var(--admin-text-heading);">Recent Usage Logs</h4>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Equipment</th><th>Date</th><th>Hours</th><th>Task</th><th>Operator</th><th>Fuel Cost</th></tr></thead>
            <tbody>
            <?php if (empty($usageRecords)): ?>
                <tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;"><strong>No usage logged yet.</strong><br>Click <strong>Log Usage</strong> to track equipment hours and tasks.</td></tr>
            <?php else: foreach ($usageRecords as $ur): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($ur['equipment_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= $ur['usage_date'] ?></td>
                    <td><?= number_format((float)$ur['hours_used'], 1) ?>h</td>
                    <td><?= htmlspecialchars($ur['task'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($ur['operator'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $ur['fuel_cost'] > 0 ? 'KES '.number_format((float)$ur['fuel_cost'], 2) : '-' ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Maintenance Modal -->
<div id="maint-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:560px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 id="maint-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Schedule Maintenance</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_maintenance">
            <input type="hidden" name="id" id="mt-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Equipment *</label>
                    <select class="admin-form-control" name="equipment_id" id="mt-equip" required>
                        <option value="">Select equipment...</option>
                        <?php foreach ($farmItems as $fi): ?>
                            <option value="<?= $fi['id'] ?>"><?= htmlspecialchars($fi['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Type</label>
                    <select class="admin-form-control" name="maintenance_type" id="mt-type">
                        <option value="preventive">Preventive</option><option value="corrective">Corrective</option><option value="inspection">Inspection</option><option value="calibration">Calibration</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Status</label>
                    <select class="admin-form-control" name="status" id="mt-status">
                        <option value="scheduled">Scheduled</option><option value="completed">Completed</option><option value="skipped">Skipped</option>
                    </select>
                </div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Title *</label><input class="admin-form-control" name="title" id="mt-title" required placeholder="e.g. Oil change, Belt replacement"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Description</label><textarea class="admin-form-control" name="description" id="mt-desc" rows="2"></textarea></div>
                <div class="admin-form-group"><label class="admin-form-label">Scheduled Date *</label><input class="admin-form-control" type="date" name="scheduled_date" id="mt-sched" required></div>
                <div class="admin-form-group"><label class="admin-form-label">Completed Date</label><input class="admin-form-control" type="date" name="completed_date" id="mt-comp"></div>
                <div class="admin-form-group"><label class="admin-form-label">Cost (KES)</label><input class="admin-form-control" type="number" step="0.01" name="cost" id="mt-cost"></div>
                <div class="admin-form-group"><label class="admin-form-label">Performed By</label><input class="admin-form-control" name="performed_by" id="mt-by" placeholder="e.g. John, External mechanic"></div>
                <div class="admin-form-group"><label class="admin-form-label">Next Due Date</label><input class="admin-form-control" type="date" name="next_due_date" id="mt-next"></div>
                <div class="admin-form-group"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" id="mt-notes" rows="2"></textarea></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('maint-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Usage Modal -->
<div id="usage-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Log Equipment Usage</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_usage">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Equipment *</label>
                    <select class="admin-form-control" name="equipment_id" required>
                        <option value="">Select equipment...</option>
                        <?php foreach ($farmItems as $fi): ?>
                            <option value="<?= $fi['id'] ?>"><?= htmlspecialchars($fi['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Date</label><input class="admin-form-control" type="date" name="usage_date" value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group"><label class="admin-form-label">Hours Used</label><input class="admin-form-control" type="number" step="0.1" name="hours_used" min="0" required></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Task</label><input class="admin-form-control" name="task" placeholder="e.g. Ploughing field B, Feed mixing"></div>
                <div class="admin-form-group"><label class="admin-form-label">Operator</label><input class="admin-form-control" name="operator" placeholder="Who operated it"></div>
                <div class="admin-form-group"><label class="admin-form-label">Fuel Cost (KES)</label><input class="admin-form-control" type="number" step="0.01" name="fuel_cost" min="0"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" rows="2"></textarea></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('usage-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openMaintModal(d) {
    document.getElementById('maint-modal-title').textContent = d?.id ? 'Edit Maintenance' : 'Schedule Maintenance';
    document.getElementById('mt-id').value = d?.id || '';
    document.getElementById('mt-equip').value = d?.equipment_id || '';
    document.getElementById('mt-type').value = d?.maintenance_type || 'preventive';
    document.getElementById('mt-status').value = d?.status || 'scheduled';
    document.getElementById('mt-title').value = d?.title || '';
    document.getElementById('mt-desc').value = d?.description || '';
    document.getElementById('mt-sched').value = d?.scheduled_date || '';
    document.getElementById('mt-comp').value = d?.completed_date || '';
    document.getElementById('mt-cost').value = d?.cost || '';
    document.getElementById('mt-by').value = d?.performed_by || '';
    document.getElementById('mt-next').value = d?.next_due_date || '';
    document.getElementById('mt-notes').value = d?.notes || '';
    document.getElementById('maint-modal').style.display = 'flex';
}
function openUsageModal() { document.getElementById('usage-modal').style.display = 'flex'; }
document.addEventListener('click', e => {
    ['maint-modal','usage-modal'].forEach(id => { const m = document.getElementById(id); if (m && e.target === m) m.style.display = 'none'; });
});
</script>
<?php elseif ($tab === 'feedstock'): ?>
<div class="admin-card" style="padding:0; overflow:hidden;">
    <iframe src="stock_dashboard.php" style="width:100%; height:850px; border:none; display:block;"></iframe>
</div>

<!-- ══════ INVENTORY ALERTS TAB ══════ -->
<?php elseif ($tab === 'alerts'): ?>
<div class="admin-card" style="padding:0; overflow:hidden;">
    <iframe src="stock_alerts.php" style="width:100%; height:650px; border:none; display:block;"></iframe>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
