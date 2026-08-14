<?php
/**
 * Hub: CRM & Customers
 * Tabs: Customers | Segments | Follow-ups | Contact History
 * Research-backed: farmers hate scattered data (59%); credit control exists but no CRM layer.
 */
declare(strict_types=1);
$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','stock_manager'], true)) {
    echo "<script>window.location.href='/wangariadmin';</script>"; exit;
}

$page_title = 'CRM & Customers - Admin';
include __DIR__ . '/includes/admin_header.php';

$tab = $_GET['tab'] ?? 'customers';
$validTabs = ['customers','segments','followups','contacts'];
if (!in_array($tab, $validTabs, true)) $tab = 'customers';

$pdo = getDB();
$message = ''; $error_message = '';

/* ── POST handlers ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $postAction = $_POST['_action'] ?? '';

    if ($postAction === 'save_segment') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (!$name) { $error_message = 'Segment name is required.'; }
        else {
            try {
                if ($id > 0) {
                    $pdo->prepare('UPDATE crm_segments SET name=?,description=? WHERE id=?')->execute([$name,$desc,$id]);
                    $message = 'Segment updated.';
                } else {
                    $pdo->prepare('INSERT INTO crm_segments (name,description) VALUES (?,?)')->execute([$name,$desc]);
                    $message = 'Segment created.';
                }
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'segments';
    }

    if ($postAction === 'save_followup') {
        $custId = (int)($_POST['customer_id'] ?? 0);
        $ctype  = trim($_POST['customer_type'] ?? 'user');
        $due    = trim($_POST['due_date'] ?? date('Y-m-d'));
        $note   = trim($_POST['note'] ?? '');
        if (!$custId || !$note) { $error_message = 'Customer and note are required.'; }
        else {
            try {
                $pdo->prepare('INSERT INTO crm_followups (customer_id,customer_type,due_date,note,status,created_by) VALUES (?,?,?,?,"open",?)')
                    ->execute([$custId,$ctype,$due,$note,(int)($_SESSION['user_id'] ?? 0)]);
                $message = 'Follow-up scheduled.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'followups';
    }

    if ($postAction === 'complete_followup') {
        try {
            $pdo->prepare('UPDATE crm_followups SET status="done" WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
            $message = 'Follow-up marked done.';
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'followups';
    }

    if ($postAction === 'add_contact_note') {
        $custId = (int)($_POST['customer_id'] ?? 0);
        $ctype  = trim($_POST['customer_type'] ?? 'user');
        $type   = trim($_POST['contact_type'] ?? 'note');
        $note   = trim($_POST['note'] ?? '');
        if (!$custId || !$note) { $error_message = 'Customer and note are required.'; }
        else {
            try {
                $pdo->prepare('INSERT INTO crm_contacts (customer_id,customer_type,contact_type,note,created_by) VALUES (?,?,?,?,?)')
                    ->execute([$custId,$ctype,$type,$note,(int)($_SESSION['user_id'] ?? 0)]);
                $message = 'Contact note added.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'contacts';
    }
}

/* ── Load data ── */
$segments = $followups = $contacts = [];
$users = $walkins = [];
$customerOptions = [];   // id => label
$customerBalances = [];  // customer key => outstanding credit

if ($pdo) {
    try {
        $segments = $pdo->query('SELECT * FROM crm_segments ORDER BY name')->fetchAll();

        // Customers: registered users + walk-ins merged into one list
        $users = $pdo->query('SELECT id, username, email, phone_number, created_at FROM users ORDER BY username')->fetchAll();
        foreach ($users as $u) {
            $label = ($u['username'] ?: $u['email']) . ' <span style="color:#94a3b8;font-weight:400;">(account)</span>';
            $customerOptions['u' . $u['id']] = $label;
        }
        $walkins = $pdo->query('SELECT id, customer_name, phone, created_at FROM walk_in_customers ORDER BY customer_name')->fetchAll();
        foreach ($walkins as $w) {
            $customerOptions['w' . $w['id']] = htmlspecialchars($w['customer_name'] ?: 'Walk-in #' . $w['id'], ENT_QUOTES, 'UTF-8') . ' <span style="color:#94a3b8;font-weight:400;">(walk-in)</span>';
        }

        $followups = $pdo->query('SELECT f.*, u.username AS user_name, w.customer_name AS walkin_name FROM crm_followups f
            LEFT JOIN users u ON (f.customer_type="user" AND u.id=f.customer_id)
            LEFT JOIN walk_in_customers w ON (f.customer_type="walk_in" AND w.id=f.customer_id)
            ORDER BY (f.status="open") DESC, f.due_date ASC LIMIT 100')->fetchAll();

        $contacts = $pdo->query('SELECT c.*, u.username AS user_name, w.customer_name AS walkin_name FROM crm_contacts c
            LEFT JOIN users u ON (c.customer_type="user" AND u.id=c.customer_id)
            LEFT JOIN walk_in_customers w ON (c.customer_type="walk_in" AND w.id=c.customer_id)
            ORDER BY c.created_at DESC LIMIT 100')->fetchAll();

        // Credit aging per customer (existing credit tables)
        $creditRows = $pdo->query('SELECT * FROM customer_credits')->fetchAll();
        foreach ($creditRows as $cr) {
            $key = 'c' . $cr['id'];
            $name = $cr['customer_name'] ?: 'Customer #' . $cr['customer_id'];
            $paid = (float)($cr['amount_paid'] ?? 0);
            $total = (float)($cr['total_amount'] ?? 0);
            $bal = (float)($cr['balance'] ?? 0);
            if ($bal > 0 && !isset($customerBalances[$key])) $customerBalances[$key] = ['name' => $name, 'balance' => $bal, 'last' => $cr['created_at'] ?? ''];
        }
    } catch (Exception $e) { $error_message = $e->getMessage(); }
}

$tabs = [
    'customers'  => ['icon' => 'users',        'label' => 'Customers'],
    'segments'   => ['icon' => 'tags',         'label' => 'Segments'],
    'followups'  => ['icon' => 'calendar-check','label' => 'Follow-ups'],
    'contacts'   => ['icon' => 'message-square','label' => 'Contact History'],
];
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);">CRM &amp; Customers</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Know every customer, follow up on time, and see credit aging at a glance.</p>
    </div>
    <?php if ($tab === 'segments'): ?><button class="btn btn-primary" onclick="document.getElementById('segment-modal').style.display='flex'"><i data-lucide="plus" style="width:15px;height:15px;"></i> New Segment</button><?php endif; ?>
    <?php if ($tab === 'followups'): ?><button class="btn btn-primary" onclick="document.getElementById('followup-modal').style.display='flex'"><i data-lucide="plus" style="width:15px;height:15px;"></i> Schedule Follow-up</button><?php endif; ?>
    <?php if ($tab === 'contacts'): ?><button class="btn btn-primary" onclick="document.getElementById('contact-modal').style.display='flex'"><i data-lucide="plus" style="width:15px;height:15px;"></i> Add Note</button><?php endif; ?>
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

<?php if ($tab === 'customers'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">All Customers</h3>
        <span style="color:#64748b;font-size:0.85rem;"><?= count($customerOptions) ?> customers</span>
    </div>

    <?php if (!empty($customerBalances)): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:12px;margin-bottom:20px;">
        <?php foreach ($customerBalances as $key => $cb): if ($cb['balance'] <= 0) continue; ?>
        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 14px;">
            <div style="font-size:0.78rem;color:#92400e;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;">Outstanding Credit</div>
            <div style="font-family:'Outfit',sans-serif;font-size:1.15rem;font-weight:700;color:#b45309;">KES <?= number_format($cb['balance'], 0) ?></div>
            <div style="font-size:0.8rem;color:#92400e;"><?= htmlspecialchars($cb['name'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Customer</th><th>Phone</th><th>Type</th><th>Outstanding Credit</th><th>Since</th></tr></thead>
            <tbody>
            <?php
            $rows = [];
            foreach ($users as $u) $rows[] = ['name' => $u['username'] ?: $u['email'], 'phone' => $u['phone_number'] ?? '', 'type' => 'Account', 'key' => 'u' . $u['id'], 'since' => substr($u['created_at'] ?? '', 0, 10)];
            foreach ($walkins as $w) $rows[] = ['name' => $w['customer_name'] ?: 'Walk-in #' . $w['id'], 'phone' => $w['phone'] ?? '', 'type' => 'Walk-in', 'key' => 'w' . $w['id'], 'since' => substr($w['created_at'] ?? '', 0, 10)];
            if (empty($rows)): ?>
                <tr><td colspan="5" style="text-align:center;padding:28px;color:#94a3b8;">No customers yet.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($r['phone'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge-pill <?= $r['type']==='Account' ? 'badge-pill-success' : 'badge-pill-warning' ?>"><?= $r['type'] ?></span></td>
                    <td><?php $bal = $customerBalances[$r['key']]['balance'] ?? 0; echo $bal > 0 ? '<strong style="color:#b45309;">KES ' . number_format($bal, 0) . '</strong>' : '<span style="color:#94a3b8;">—</span>'; ?></td>
                    <td><?= htmlspecialchars($r['since'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($tab === 'segments'): ?>
<div class="admin-card">
    <h3 style="margin:0 0 16px;font-family:'Outfit',sans-serif;font-size:1.1rem;">Customer Segments</h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Segment</th><th>Description</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($segments)): ?>
                <tr><td colspan="3" style="text-align:center;padding:28px;color:#94a3b8;">No segments yet.</td></tr>
            <?php else: foreach ($segments as $s): ?>
                <tr>
                    <td><span class="badge-pill badge-pill-success"><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($s['description'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><button class="btn btn-outline btn-sm" onclick='editSegment(<?= json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i data-lucide="edit-3" style="width:13px;height:13px;"></i> Edit</button></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="segment-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:420px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 20px;font-family:'Outfit',sans-serif;" id="segment-modal-title">New Segment</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_segment">
            <input type="hidden" name="id" id="segment-id" value="0">
            <div class="admin-form-group"><label class="admin-form-label">Segment Name *</label>
                <input class="admin-form-control" type="text" name="name" id="segment-name" required placeholder="e.g. Wholesale"></div>
            <div class="admin-form-group"><label class="admin-form-label">Description</label>
                <input class="admin-form-control" type="text" name="description" id="segment-desc" placeholder="e.g. Bulk buyers and distributors"></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('segment-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Save</button>
            </div>
        </form>
    </div>
</div>
<script>
function editSegment(s) {
    document.getElementById('segment-modal-title').textContent = 'Edit Segment';
    document.getElementById('segment-id').value = s.id;
    document.getElementById('segment-name').value = s.name;
    document.getElementById('segment-desc').value = s.description || '';
    document.getElementById('segment-modal').style.display = 'flex';
}
</script>

<?php elseif ($tab === 'followups'): ?>
<div class="admin-card">
    <h3 style="margin:0 0 16px;font-family:'Outfit',sans-serif;font-size:1.1rem;">Follow-ups</h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Due</th><th>Customer</th><th>Note</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php if (empty($followups)): ?>
                <tr><td colspan="5" style="text-align:center;padding:28px;color:#94a3b8;">No follow-ups scheduled. Never lose track of a customer again.</td></tr>
            <?php else: foreach ($followups as $f): ?>
                <?php
                $cname = $f['user_name'] ?: $f['walkin_name'] ?: 'Customer #' . $f['customer_id'];
                $overdue = ($f['status'] === 'open' && strtotime($f['due_date']) < strtotime('today'));
                ?>
                <tr>
                    <td><strong<?= $overdue ? ' style="color:#b91c1c;"' : '' ?>><?= htmlspecialchars($f['due_date'], ENT_QUOTES, 'UTF-8') ?></strong><?= $overdue ? ' <span class="badge-pill badge-pill-danger">Overdue</span>' : '' ?></td>
                    <td><?= htmlspecialchars($cname, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($f['note'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge-pill <?= $f['status']==='done' ? 'badge-pill-success' : ($f['status']==='missed' ? 'badge-pill-danger' : 'badge-pill-warning') ?>"><?= ucfirst($f['status']) ?></span></td>
                    <td>
                        <?php if ($f['status'] !== 'done'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="_action" value="complete_followup">
                            <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                            <button class="btn btn-success btn-sm"><i data-lucide="check" style="width:13px;height:13px;"></i> Done</button>
                        </form>
                        <?php else: ?><span style="color:#94a3b8;">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="followup-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:440px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 20px;font-family:'Outfit',sans-serif;">Schedule Follow-up</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_followup">
            <div class="admin-form-group"><label class="admin-form-label">Customer *</label>
                <select class="admin-form-control" name="customer_id" required>
                    <option value="">Select customer…</option>
                    <?php foreach ($customerOptions as $ckey => $clabel): ?><option value="<?= (int)substr($ckey, 1) ?>" data-type="<?= $ckey[0] === 'w' ? 'walk_in' : 'user' ?>"><?= $clabel ?></option><?php endforeach; ?>
                </select></div>
            <div class="admin-form-group"><label class="admin-form-label">Customer Type</label>
                <select class="admin-form-control" name="customer_type" id="followup-cust-type"><option value="user">Account</option><option value="walk_in">Walk-in</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Due Date</label>
                <input class="admin-form-control" type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+3 days')) ?>"></div>
            <div class="admin-form-group"><label class="admin-form-label">Note *</label>
                <textarea class="admin-form-control" name="note" rows="2" required placeholder="e.g. Call about outstanding credit"></textarea></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('followup-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Schedule</button>
            </div>
        </form>
    </div>
</div>
<script>
document.querySelector('#followup-modal select[name=customer_id]').addEventListener('change', function(){
    const opt = this.options[this.selectedIndex];
    document.getElementById('followup-cust-type').value = opt.dataset.type || 'user';
});
</script>

<?php elseif ($tab === 'contacts'): ?>
<div class="admin-card">
    <h3 style="margin:0 0 16px;font-family:'Outfit',sans-serif;font-size:1.1rem;">Contact History</h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Customer</th><th>Type</th><th>Note</th></tr></thead>
            <tbody>
            <?php if (empty($contacts)): ?>
                <tr><td colspan="4" style="text-align:center;padding:28px;color:#94a3b8;">No contact notes yet. Every call, visit and message lives here.</td></tr>
            <?php else: foreach ($contacts as $c): ?>
                <?php $cname = $c['user_name'] ?: $c['walkin_name'] ?: 'Customer #' . $c['customer_id']; ?>
                <tr>
                    <td><?= htmlspecialchars(substr($c['created_at'], 0, 16), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><strong><?= htmlspecialchars($cname, ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><span class="badge-pill badge-pill-success" style="text-transform:capitalize;"><?= htmlspecialchars($c['contact_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($c['note'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="contact-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:440px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 20px;font-family:'Outfit',sans-serif;">Add Contact Note</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="add_contact_note">
            <div class="admin-form-group"><label class="admin-form-label">Customer *</label>
                <select class="admin-form-control" name="customer_id" required>
                    <option value="">Select customer…</option>
                    <?php foreach ($customerOptions as $ckey => $clabel): ?><option value="<?= (int)substr($ckey, 1) ?>"><?= $clabel ?></option><?php endforeach; ?>
                </select></div>
            <div class="admin-form-group"><label class="admin-form-label">Type</label>
                <select class="admin-form-control" name="contact_type"><option>note</option><option>call</option><option>visit</option><option>message</option><option>order</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Note *</label>
                <textarea class="admin-form-control" name="note" rows="3" required placeholder="What happened in this interaction?"></textarea></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('contact-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Add Note</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
