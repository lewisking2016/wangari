<?php
/**
 * Hub: Labour & Workers
 * Tabs: Workers | Attendance | Wage Payments
 * Research-backed: 52% of farmers want worker / seasonal labour management.
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','stock_manager'], true)) {
    echo "<script>window.location.href='/Frontend/pages/login.php';</script>"; exit;
}

$page_title = 'Labour & Workers - Admin';
include __DIR__ . '/includes/admin_header.php';

$tab = $_GET['tab'] ?? 'workers';
$validTabs = ['workers','attendance','payments','codes','connected'];
if (!in_array($tab, $validTabs, true)) $tab = 'workers';

$pdo = getDB();
$message = ''; $error_message = '';

/* ═══ POST handlers ═══ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $postAction = $_POST['_action'] ?? '';

    if ($postAction === 'save_worker') {
        $id    = (int)($_POST['id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role  = trim($_POST['role'] ?? '');
        $wtype = trim($_POST['wage_type'] ?? 'daily');
        $rate  = (float)($_POST['wage_rate'] ?? 0);
        $stat  = trim($_POST['status'] ?? 'active');
        $notes = trim($_POST['notes'] ?? '');
        if (!$name) { $error_message = 'Worker name is required.'; }
        else {
            try {
                if ($id > 0) {
                    $pdo->prepare('UPDATE labour_workers SET full_name=?,phone=?,role=?,employment_type=?,daily_rate=?,status=?,notes=? WHERE id=?')
                        ->execute([$name,$phone,$role,$wtype,$rate,$stat,$notes,$id]);
                    $message = 'Worker updated.';
                } else {
                    $pdo->prepare('INSERT INTO labour_workers (full_name,phone,role,employment_type,daily_rate,status,notes) VALUES (?,?,?,?,?,?,?)')
                        ->execute([$name,$phone,$role,$wtype,$rate,$stat,$notes]);
                    $message = 'Worker added.';
                }
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'workers';
    }

    if ($postAction === 'save_attendance') {
        $workerId = (int)($_POST['worker_id'] ?? 0);
        $date     = trim($_POST['work_date'] ?? date('Y-m-d'));
        $hours    = (float)($_POST['hours_worked'] ?? 0);
        $task     = trim($_POST['task'] ?? '');
        $loc      = trim($_POST['location'] ?? '');
        $notes    = trim($_POST['notes'] ?? '');
        if (!$workerId) { $error_message = 'Select a worker.'; }
        else {
            try {
                $pdo->prepare('INSERT INTO labour_attendance (worker_id,work_date,hours_worked,task,location,notes) VALUES (?,?,?,?,?,?)')
                    ->execute([$workerId,$date,$hours,$task,$loc,$notes]);
                $message = 'Attendance recorded.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'attendance';
    }

    if ($postAction === 'save_payment') {
        $workerId = (int)($_POST['worker_id'] ?? 0);
        $amount   = (float)($_POST['amount'] ?? 0);
        $pstart   = trim($_POST['period_start'] ?? '');
        $pend     = trim($_POST['period_end'] ?? '');
        $method   = trim($_POST['method'] ?? 'cash');
        $notes    = trim($_POST['notes'] ?? '');
        if (!$workerId || $amount <= 0) { $error_message = 'Worker and amount are required.'; }
        else {
            try {
                $pdo->prepare('INSERT INTO labour_payments (worker_id,amount,period_start,period_end,method,notes) VALUES (?,?,?,?,?,?)')
                    ->execute([$workerId,$amount,$pstart?:null,$pend?:null,$method,$notes]);
                $message = 'Wage payment recorded.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'payments';
    }

    /* ── DELETE HANDLERS ── */
    if ($postAction === 'delete_worker') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM labour_workers WHERE id=?')->execute([$id]);
                $message = 'Worker deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'workers';
    }

    if ($postAction === 'delete_attendance') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM labour_attendance WHERE id=?')->execute([$id]);
                $message = 'Attendance record deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'attendance';
    }

    if ($postAction === 'delete_payment') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM labour_payments WHERE id=?')->execute([$id]);
                $message = 'Payment record deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'payments';
    }

    /* ── Generate Worker Code ── */
    if ($postAction === 'generate_code') {
        $maxUses = (int)($_POST['max_uses'] ?? 10);
        $expiresDays = (int)($_POST['expires_days'] ?? 30);
        $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4) . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 4));
        try {
            $stmt = $pdo->prepare('INSERT INTO worker_connection_codes (farm_user_id, code, max_uses, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))');
            $stmt->execute([(int)$_SESSION['user_id'], $code, $maxUses, $expiresDays]);
            $message = "Code generated: {$code}";
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'codes';
    }

    /* ── Delete Code ── */
    if ($postAction === 'delete_code') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM worker_connection_codes WHERE id=? AND farm_user_id=?')->execute([$id, (int)$_SESSION['user_id']]);
                $message = 'Code deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'codes';
    }

    /* ── Disconnect Worker ── */
    if ($postAction === 'disconnect_worker') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('UPDATE worker_farm_links SET is_active = 0 WHERE id=? AND farm_user_id=?')->execute([$id, (int)$_SESSION['user_id']]);
                $message = 'Worker disconnected.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'connected';
    }
}

/* ═══ Load data ═══ */
$workers = $attendance = $payments = $workerCodes = $connectedWorkers = [];
$workerOptions = [];
$farmUserId = (int)$_SESSION['user_id'];
if ($pdo) {
    try {
        $workers = $pdo->query('SELECT * FROM labour_workers ORDER BY is_active DESC, full_name')->fetchAll();
        foreach ($workers as $w) $workerOptions[$w['id']] = $w['name'];
        $attendance = $pdo->query('SELECT a.*, w.full_name AS worker_name FROM labour_attendance a LEFT JOIN labour_workers w ON w.id=a.worker_id ORDER BY a.work_date DESC LIMIT 200')->fetchAll();
        $payments = $pdo->query('SELECT p.*, w.full_name AS worker_name FROM labour_payments p LEFT JOIN labour_workers w ON w.id=p.worker_id ORDER BY p.payment_date DESC LIMIT 200')->fetchAll();
        // Worker connection codes
        $workerCodes = $pdo->prepare('SELECT * FROM worker_connection_codes WHERE farm_user_id = ? ORDER BY created_at DESC');
        $workerCodes->execute([$farmUserId]);
        $workerCodes = $workerCodes->fetchAll(PDO::FETCH_ASSOC);
        // Connected workers
        $connectedWorkers = $pdo->prepare('SELECT wfl.*, u.full_name, u.username, u.email FROM worker_farm_links wfl JOIN users u ON u.id = wfl.worker_user_id WHERE wfl.farm_user_id = ? AND wfl.is_active = 1 ORDER BY wfl.connected_at DESC');
        $connectedWorkers->execute([$farmUserId]);
        $connectedWorkers = $connectedWorkers->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $error_message = $e->getMessage(); }
}

$tabs = [
    'workers'    => ['icon' => 'users',         'label' => 'Workers'],
    'attendance' => ['icon' => 'calendar-check', 'label' => 'Attendance'],
    'payments'   => ['icon' => 'wallet',         'label' => 'Wage Payments'],
    'codes'      => ['icon' => 'key',            'label' => 'Worker Codes'],
    'connected'  => ['icon' => 'link',           'label' => 'Connected Workers'],
];
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
    <div class="hub-page-header" style="margin-bottom:0;">
        <div class="hub-page-icon"><i data-lucide="hard-hat"></i></div>
        <div>
            <h1 class="hub-page-title">Labour &amp; Workers</h1>
            <p class="hub-page-sub">Track who worked, when, on what, and how much you owe.</p>
        </div>
    </div>
    <div style="display:flex;gap:10px;">
        <?php if ($tab === 'workers'): ?><button class="btn btn-primary" onclick="document.getElementById('worker-modal').style.display='flex'"><i data-lucide="plus" style="width:15px;height:15px;"></i> Add Worker</button><?php endif; ?>
        <?php if ($tab === 'attendance'): ?><button class="btn btn-primary" onclick="document.getElementById('attendance-modal').style.display='flex'"><i data-lucide="plus" style="width:15px;height:15px;"></i> Record Attendance</button><?php endif; ?>
        <?php if ($tab === 'payments'): ?><button class="btn btn-primary" onclick="document.getElementById('payment-modal').style.display='flex'"><i data-lucide="plus" style="width:15px;height:15px;"></i> Record Payment</button><?php endif; ?>
    </div>
</div>

<?php if ($message): ?>
<div class="hub-alert hub-alert-success"><i data-lucide="check-circle-2"></i><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($error_message): ?>
<div class="hub-alert hub-alert-error"><i data-lucide="alert-circle"></i><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Tab Bar -->
<div class="hub-tab-bar">
<?php foreach ($tabs as $key => $info): ?>
    <a href="?tab=<?= $key ?>" class="hub-tab<?= $tab===$key ? ' active' : '' ?>">
        <i data-lucide="<?= $info['icon'] ?>"></i><?= $info['label'] ?>
    </a>
<?php endforeach; ?>
</div>

<?php if ($tab === 'workers'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Workers <?= helpTip('All people who work on your farm: permanent staff, casual workers, managers. Track names, roles, phone numbers, and wages.') ?></h3>
        <span style="color:#64748b;font-size:0.85rem;"><?= count(array_filter($workers, fn($w) => $w['is_active']==='active')) ?> active ┬╖ <?= count($workers) ?> total</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Worker</th><th>Phone</th><th>Role</th><th>Wage</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($workers)): ?>
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">No workers yet. Add your team to start tracking attendance and wages.</td></tr>
            <?php else: foreach ($workers as $w): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($w['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($w['phone'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($w['role'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span style="text-transform:capitalize;"><?= htmlspecialchars($w['wage_type'], ENT_QUOTES, 'UTF-8') ?></span> ┬╖ KES <?= number_format((float)$w['wage_rate'], 0) ?></td>
                    <td><span class="badge-pill <?= $w['is_active']==='active' ? 'badge-pill-success' : 'badge-pill-danger' ?>"><?= ((int)$w['is_active'] ? 'Active' : 'Inactive') ?></span></td>
                    <td>
                        <div class="tbl-actions">
                            <button class="btn btn-outline btn-sm" onclick='editWorker(<?= json_encode($w, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i data-lucide="edit-3" style="width:13px;height:13px;"></i> Edit</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this worker?');"><input type="hidden" name="_action" value="delete_worker"><input type="hidden" name="id" value="<?= (int)$w['id'] ?>"><button class="btn btn-danger btn-sm"><i data-lucide="trash-2" style="width:13px;height:13px;"></i></button></form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="worker-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:460px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 20px;font-family:'Outfit',sans-serif;" id="worker-modal-title">Add Worker</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_worker">
            <input type="hidden" name="id" id="worker-id" value="0">
            <div class="admin-form-group"><label class="admin-form-label">Full Name *</label>
                <input class="admin-form-control" type="text" name="full_name" id="worker-name" required></div>
            <div class="admin-form-group"><label class="admin-form-label">Phone</label>
                <input class="admin-form-control" type="text" name="phone" id="worker-phone" placeholder="+254 7XX XXX XXX"></div>
            <div class="admin-form-group"><label class="admin-form-label">Role</label>
                <input class="admin-form-control" type="text" name="role" id="worker-role" placeholder="e.g. General farm hand"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="admin-form-group"><label class="admin-form-label">Wage Type</label>
                    <select class="admin-form-control" name="employment_type" id="worker-wtype"><option>daily</option><option>piecework</option><option>monthly</option></select></div>
                <div class="admin-form-group"><label class="admin-form-label">Rate (KES)</label>
                    <input class="admin-form-control" type="number" step="0.01" min="0" name="daily_rate" id="worker-rate" placeholder="0"></div>
            </div>
            <div class="admin-form-group"><label class="admin-form-label">Status</label>
                <select class="admin-form-control" name="is_active" id="worker-status"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Notes</label>
                <textarea class="admin-form-control" name="notes" id="worker-notes" rows="2"></textarea></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('worker-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Save Worker</button>
            </div>
        </form>
    </div>
</div>
<script>
function editWorker(w) {
    document.getElementById('worker-modal-title').textContent = 'Edit Worker';
    document.getElementById('worker-id').value = w.id;
    document.getElementById('worker-name').value = w.full_name;
    document.getElementById('worker-phone').value = w.phone || '';
    document.getElementById('worker-role').value = w.role || '';
    document.getElementById('worker-wtype').value = w.employment_type;
    document.getElementById('worker-rate').value = w.daily_rate;
    document.getElementById('worker-status').value = w.is_active;
    document.getElementById('worker-notes').value = w.notes || '';
    document.getElementById('worker-modal').style.display = 'flex';
}
</script>

<?php elseif ($tab === 'attendance'): ?>
<div class="admin-card">
    <h3 style="margin:0 0 16px;font-family:'Outfit',sans-serif;font-size:1.1rem;">Attendance Log <?= helpTip('Who came to work today? Record clock-in and clock-out times to track attendance and calculate pay.') ?></h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Worker</th><th>Hours</th><th>Task</th><th>Location</th><th>Notes</th></tr></thead>
            <tbody>
            <?php if (empty($attendance)): ?>
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;"><strong>No attendance yet.</strong><br>Record who worked, hours and task with <strong>+ Record Attendance</strong> — wages calculate automatically from rates.</td></tr>
            <?php else: foreach ($attendance as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['work_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><strong><?= htmlspecialchars($a['worker_name'] ?: '—', ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= number_format((float)$a['hours_worked'], 1) ?></td>
                    <td><?= htmlspecialchars($a['task'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($a['location'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($a['notes'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="attendance-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:460px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 20px;font-family:'Outfit',sans-serif;">Record Attendance</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_attendance">
            <div class="admin-form-group"><label class="admin-form-label">Worker *</label>
                <select class="admin-form-control" name="worker_id" required>
                    <option value="">Select worker…</option>
                    <?php foreach ($workerOptions as $wid => $wname): ?><option value="<?= $wid ?>"><?= htmlspecialchars($wname, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select></div>
            <div class="admin-form-group"><label class="admin-form-label">Date</label>
                <input class="admin-form-control" type="date" name="work_date" value="<?= date('Y-m-d') ?>"></div>
            <div class="admin-form-group"><label class="admin-form-label">Hours Worked</label>
                <input class="admin-form-control" type="number" step="0.5" min="0" max="24" name="hours_worked" value="8"></div>
            <div class="admin-form-group"><label class="admin-form-label">Task</label>
                <input class="admin-form-control" type="text" name="task" placeholder="e.g. Feeding, weeding, cleaning"></div>
            <div class="admin-form-group"><label class="admin-form-label">Location</label>
                <input class="admin-form-control" type="text" name="location" placeholder="e.g. House 2"></div>
            <div class="admin-form-group"><label class="admin-form-label">Notes</label>
                <textarea class="admin-form-control" name="notes" rows="2"></textarea></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('attendance-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Save</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($tab === 'payments'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Wage Payments <?= helpTip('Record every payment made to workers. Shows amount, date, and period covered so you never overpay.') ?></h3>
        <span style="color:#64748b;font-size:0.85rem;">Total paid: <strong style="color:var(--admin-primary);">KES <?= number_format(array_sum(array_column($payments,'amount')),0) ?></strong></span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Worker</th><th>Amount (KES)</th><th>Period</th><th>Method</th><th>Notes</th></tr></thead>
            <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;"><strong>No wage payments yet.</strong><br>Record money paid to workers with <strong>+ Record Payment</strong> to keep your labour costs clear.</td></tr>
            <?php else: foreach ($payments as $p): ?>
                <tr>
                    <td><?= htmlspecialchars(substr($p['paid_at'], 0, 10), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><strong><?= htmlspecialchars($p['worker_name'] ?: '—', ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><strong>KES <?= number_format((float)$p['amount'], 0) ?></strong></td>
                    <td><?= htmlspecialchars($p['period_start'] ?: '—', ENT_QUOTES, 'UTF-8') ?><?= $p['period_end'] ? ' ΓåÆ ' . htmlspecialchars($p['period_end'], ENT_QUOTES, 'UTF-8') : '' ?></td>
                    <td><span class="badge-pill badge-pill-warning" style="text-transform:capitalize;"><?= htmlspecialchars($p['method'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($p['notes'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="payment-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:460px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 20px;font-family:'Outfit',sans-serif;">Record Wage Payment</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_payment">
            <div class="admin-form-group"><label class="admin-form-label">Worker *</label>
                <select class="admin-form-control" name="worker_id" required>
                    <option value="">Select worker…</option>
                    <?php foreach ($workerOptions as $wid => $wname): ?><option value="<?= $wid ?>"><?= htmlspecialchars($wname, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select></div>
            <div class="admin-form-group"><label class="admin-form-label">Amount (KES) *</label>
                <input class="admin-form-control" type="number" step="0.01" min="0" name="amount" required placeholder="0"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="admin-form-group"><label class="admin-form-label">Period Start</label>
                    <input class="admin-form-control" type="date" name="period_start"></div>
                <div class="admin-form-group"><label class="admin-form-label">Period End</label>
                    <input class="admin-form-control" type="date" name="period_end"></div>
            </div>
            <div class="admin-form-group"><label class="admin-form-label">Method</label>
                <select class="admin-form-control" name="method"><option>cash</option><option>M-Pesa</option><option>bank transfer</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Notes</label>
                <textarea class="admin-form-control" name="notes" rows="2"></textarea></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('payment-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Record Payment</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>

<?php if ($tab === 'codes'): ?>
<?php include __DIR__ . '/labour_codes_tab.php'; ?>
<?php elseif ($tab === 'connected'): ?>
<?php include __DIR__ . '/labour_connected_tab.php'; ?>
<?php endif; ?>
