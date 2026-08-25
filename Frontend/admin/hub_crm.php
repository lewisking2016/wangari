<?php
/**
 * Hub: CRM & Customers
 * Tabs: Customers | Segments | Follow-ups | Contact History
 * Research-backed: farmers hate scattered data (59%); credit control exists but no CRM layer.
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','stock_manager'], true)) {
    echo "<script>window.location.href='/Frontend/pages/login.php';</script>"; exit;
}

$page_title = 'CRM & Customers - Admin';
include __DIR__ . '/includes/admin_header.php';

$tab = $_GET['tab'] ?? 'customers';
$validTabs = ['customers','segments','followups','contacts'];
if (!in_array($tab, $validTabs, true)) $tab = 'customers';

$pdo = getDB();
$message = ''; $error_message = '';

/* ═══ POST handlers ═══ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $postAction = $_POST['_action'] ?? '';

    if ($postAction === 'save_customer') {
        $custId = (int)($_POST['id'] ?? 0);
        $name   = trim($_POST['customer_name'] ?? '');
        $phone  = trim($_POST['phone'] ?? '');
        $type   = trim($_POST['customer_type'] ?? 'retail');
        $addr   = trim($_POST['address'] ?? '');
        $notes  = trim($_POST['notes'] ?? '');
        if (!$name) { $error_message = 'Customer name is required.'; }
        else {
            try {
                if ($custId > 0) {
                    $pdo->prepare('UPDATE walk_in_customers SET customer_name=?,phone=?,customer_type=?,address=?,notes=? WHERE id=?')
                        ->execute([$name,$phone,$type,$addr,$notes,$custId]);
                    $message = 'Customer updated.';
                } else {
                    $pdo->prepare('INSERT INTO walk_in_customers (customer_name,phone,customer_type,address,notes) VALUES (?,?,?,?,?)')
                        ->execute([$name,$phone,$type,$addr,$notes]);
                    $message = 'Customer added.';
                }
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'customers';
    }

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

    /* ── DELETE HANDLERS ── */
    if ($postAction === 'delete_customer') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM walk_in_customers WHERE id=?')->execute([$id]);
                $message = 'Customer deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'customers';
    }

    if ($postAction === 'delete_contact') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM crm_contacts WHERE id=?')->execute([$id]);
                $message = 'Contact deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'contacts';
    }

    if ($postAction === 'delete_followup') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM crm_followups WHERE id=?')->execute([$id]);
                $message = 'Follow-up deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'followups';
    }

    if ($postAction === 'delete_segment') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM crm_segments WHERE id=?')->execute([$id]);
                $message = 'Segment deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'segments';
    }
}

/* ═══ Load data ═══ */
$segments = $followups = $contacts = [];
$users = $walkins = [];
$customerOptions = [];   // id => label
$customerBalances = [];  // customer key => outstanding credit

if ($pdo) {
    try {
        $segments = $pdo->query('SELECT * FROM crm_segments ORDER BY name')->fetchAll();

        // Customers: only walk-in customers and customers with credits
        $users = [];
        $walkins = $pdo->query('SELECT id, customer_name, phone, customer_type, address, notes, created_at FROM walk_in_customers ORDER BY customer_name DESC')->fetchAll();
        foreach ($walkins as $w) {
            $label = htmlspecialchars($w['customer_name'] ?: 'Walk-in #' . $w['id'], ENT_QUOTES, 'UTF-8');
            $customerOptions['w' . $w['id']] = $label;
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

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
    <div class="hub-page-header" style="margin-bottom:0;">
        <div class="hub-page-icon"><i data-lucide="users"></i></div>
        <div>
            <h1 class="hub-page-title">CRM &amp; Customers</h1>
            <p class="hub-page-sub">Know every customer, follow up on time, and see credit aging at a glance.</p>
        </div>
    </div>
    <div style="display:flex;gap:10px;">
        <?php if ($tab === 'segments'): ?><button class="btn btn-primary" onclick="document.getElementById('segment-modal').style.display='flex'"><i data-lucide="plus" style="width:15px;height:15px;"></i> New Segment</button><?php endif; ?>
        <?php if ($tab === 'followups'): ?><button class="btn btn-primary" onclick="document.getElementById('followup-modal').style.display='flex'"><i data-lucide="plus" style="width:15px;height:15px;"></i> Schedule Follow-up</button><?php endif; ?>
        <?php if ($tab === 'contacts'): ?><button class="btn btn-primary" onclick="document.getElementById('contact-modal').style.display='flex'"><i data-lucide="plus" style="width:15px;height:15px;"></i> Add Note</button><?php endif; ?>
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

<?php if ($tab === 'customers'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
        <div style="display:flex;align-items:center;gap:12px;">
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">All Customers <?= helpTip('Every person and business that buys from your farm. Track their contact details, purchase history, and preferences.') ?></h3>
            <button class="btn btn-primary" onclick="document.getElementById('customer-modal').style.display='flex'" style="white-space:nowrap;"><i data-lucide="plus" style="width:15px;height:15px;"></i> Add Customer</button>
        </div>
        <span style="color:#64748b;font-size:0.85rem;"><?= count($walkins) ?> customers</span>
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
            foreach ($walkins as $w) $rows[] = ['name' => $w['customer_name'] ?: 'Walk-in #' . $w['id'], 'phone' => $w['phone'] ?? '', 'type' => ucfirst($w['customer_type'] ?? 'Walk-in'), 'key' => 'w' . $w['id'], 'since' => substr($w['created_at'] ?? '', 0, 10)];
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
    <h3 style="margin:0 0 16px;font-family:'Outfit',sans-serif;font-size:1.1rem;">Customer Segments <?= helpTip('Group your customers by type: wholesale buyers, retail customers, restaurants, hotels. Helps you target the right people.') ?></h3>
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
                    <td><button class="btn btn-outline btn-sm" onclick='editSegment(<?= json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i data-lucide="edit-3" style="width:13px;height:13px;"></i> Edit</button><form method="POST" style="display:inline;" onsubmit="return confirm('Delete this segment?');"><input type="hidden" name="_action" value="delete_segment"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="btn btn-danger btn-sm"><i data-lucide="trash-2" style="width:13px;height:13px;"></i></button></form></td>
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
    <h3 style="margin:0 0 16px;font-family:'Outfit',sans-serif;font-size:1.1rem;">Follow-ups <?= helpTip('Reminders to contact customers: check if they need a repeat order, resolve complaints, or share new products.') ?></h3>
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
    <h3 style="margin:0 0 16px;font-family:'Outfit',sans-serif;font-size:1.1rem;">Contact History <?= helpTip('A timeline of every conversation and interaction with your customers. Never forget what was discussed.') ?></h3>
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

<!-- Add/Edit Customer Modal -->
<div id="customer-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px;max-width:480px;width:90%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.15rem;">Add Customer</h3>
            <button onclick="document.getElementById('customer-modal').style.display='none'" style="background:none;border:none;cursor:pointer;font-size:1.3rem;color:#64748b;">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="_action" value="save_customer">
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;">Customer Name *</label>
                <input type="text" name="customer_name" required placeholder="e.g. John Kamau" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.95rem;box-sizing:border-box;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;">Phone</label>
                    <input type="text" name="phone" placeholder="+254 712 345 678" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.95rem;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;">Type</label>
                    <select name="customer_type" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.95rem;box-sizing:border-box;">
                        <option value="retail">Retail</option>
                        <option value="wholesale">Wholesale</option>
                        <option value="institution">Institution</option>
                        <option value="agent">Agent</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;">Address</label>
                <input type="text" name="address" placeholder="Town / Area" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.95rem;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;">Notes</label>
                <textarea name="notes" rows="2" placeholder="Optional notes about this customer" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.95rem;box-sizing:border-box;resize:vertical;"></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('customer-modal').style.display='none'" style="padding:10px 20px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;font-size:0.9rem;">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Customer</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
