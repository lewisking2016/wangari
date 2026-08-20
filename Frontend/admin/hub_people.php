<?php
/**
 * Hub: Team & Messages, ALL content inline, no double-includes.
 * Tabs: Staff Members | All Users | Assign Tasks | Inbox Messages
 */
declare(strict_types=1);
$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','sales_staff'], true)) {
    echo "<script>window.location.href='/wangariadmin';</script>"; exit;
}

$page_title = 'Team & Messages - Admin';
include __DIR__ . '/includes/admin_header.php';

$tab = $_GET['tab'] ?? 'staff';
$validTabs = ['staff', 'users', 'tasks', 'messages'];
if (!in_array($tab, $validTabs, true)) $tab = 'staff';

$pdo = getDB();
$message = ''; $error_message = '';
$currentAdminId = (int)($_SESSION['user_id'] ?? 0);

/* ── POST handler for Staff, Tasks, Messages ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $postAction = $_POST['_action'] ?? '';

    if ($postAction === 'save_staff') {
        $id        = (int)($_POST['id'] ?? 0);
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $role      = trim($_POST['role'] ?? 'farm_manager');
        $password  = trim($_POST['password'] ?? '');

        if ($username === '' || $email === '') {
            $error_message = 'Username and email are required.';
        } else {
            try {
                if ($id > 0) {
                    $pdo->prepare('UPDATE users SET username=?,email=?,first_name=?,last_name=?,phone_number=?,role=? WHERE id=?')
                        ->execute([$username,$email,$firstName,$lastName,$phone,$role,$id]);
                    if ($password !== '') {
                        $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
                    }
                    $message = 'Staff member updated.';
                } else {
                    $hash = password_hash($password !== '' ? $password : 'Staff@123', PASSWORD_DEFAULT);
                    $pdo->prepare('INSERT INTO users (username,email,password_hash,role,first_name,last_name,phone_number) VALUES (?,?,?,?,?,?,?)')
                        ->execute([$username,$email,$hash,$role,$firstName,$lastName,$phone]);
                    $message = 'Staff member added. Default password: Staff@123';
                }
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'staff';
    }

    if ($postAction === 'save_task') {
        $id         = (int)($_POST['id'] ?? 0);
        $title      = trim($_POST['title'] ?? '');
        $desc       = trim($_POST['description'] ?? '');
        $assignedTo = (int)($_POST['assigned_to'] ?? 0) ?: null;
        $dueDate    = trim($_POST['due_date'] ?? '');
        $status     = trim($_POST['status'] ?? 'Pending');

        if ($title === '') {
            $error_message = 'Task title is required.';
        } else {
            try {
                if ($id > 0) {
                    $pdo->prepare('UPDATE tasks SET title=?,description=?,assigned_to=?,due_date=?,status=? WHERE id=?')
                        ->execute([$title,$desc,$assignedTo,$dueDate?:null,$status,$id]);
                    $message = 'Task updated.';
                } else {
                    $pdo->prepare('INSERT INTO tasks (title,description,assigned_to,due_date,status,created_by) VALUES (?,?,?,?,?,?)')
                        ->execute([$title,$desc,$assignedTo,$dueDate?:null,$status,$currentAdminId]);
                    $message = 'Task created.';
                }
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'tasks';
    }

    if ($postAction === 'send_message') {
        $recipientId = (int)($_POST['recipient_id'] ?? 0);
        $subject     = trim($_POST['subject'] ?? '');
        $body        = trim($_POST['body'] ?? '');
        if ($recipientId <= 0 || $subject === '' || $body === '') {
            $error_message = 'Recipient, subject, and message body are required.';
        } else {
            try {
                $pdo->prepare('INSERT INTO messages (sender_id,recipient_id,subject,body,status) VALUES (?,?,?,?,"pending")')
                    ->execute([$currentAdminId,$recipientId,$subject,$body]);
                $message = 'Message sent successfully.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'messages';
    }
}

/* ── Load PHP-based tab data ── */
$staffList = $taskList = $messageList = [];
if ($pdo) {
    try {
        $staffList = $pdo->query("SELECT * FROM users WHERE role IN ('super_admin','farm_manager','stock_manager') ORDER BY first_name ASC, last_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        if ($tab === 'tasks') {
            $taskList = $pdo->query("SELECT t.*, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS assigned_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id ORDER BY COALESCE(t.due_date,t.created_at) ASC")->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($tab === 'messages') {
            $stmt = $pdo->prepare("SELECT m.*, su.username AS from_user, ru.username AS to_user FROM messages m LEFT JOIN users su ON m.sender_id=su.id LEFT JOIN users ru ON m.recipient_id=ru.id WHERE m.sender_id=? OR m.recipient_id=? ORDER BY m.created_at DESC");
            $stmt->execute([$currentAdminId, $currentAdminId]);
            $messageList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) { $error_message = $e->getMessage(); }
}

$tabs = [
    'staff'    => ['icon' => 'user-check',  'label' => 'Staff Accounts'],
    'users'    => ['icon' => 'users',       'label' => 'Customer List'],
    'tasks'    => ['icon' => 'clipboard-list','label' => 'Assigned Tasks'],
    'messages' => ['icon' => 'mail',        'label' => 'Team Messages'],
];
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Team & Messages</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Configure user logins, dispatch daily checklists, and send secure notices.</p>
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

<!-- ══════ STAFF ACCOUNTS TAB ══════ -->
<?php if ($tab === 'staff'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Farm Staff Members <?= helpTip("All people on your farm: managers, supervisors, workers. Track roles, contacts, and pay rates.") ?></h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Manage logins and security levels for managers and handlers.</p>
        </div>
        <button class="btn btn-primary" onclick="openStaffModal()"><i data-lucide="user-plus" style="width:16px;height:16px;"></i> Add Staff Member</button>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Email</th><th>Phone</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($staffList)): ?>
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">No administrative accounts set up.</td></tr>
            <?php else: foreach ($staffList as $s): ?>
                <tr>
                    <td><strong><?= htmlspecialchars(trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')) ?: $s['username'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($s['username'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge-pill badge-pill-warning"><?= htmlspecialchars(ucwords(str_replace('_',' ',$s['role'])), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($s['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($s['phone_number'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openStaffModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES, "UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button></div></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="staff-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:92vh;overflow-y:auto;">
        <h3 id="staff-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add Staff</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_staff">
            <input type="hidden" name="id" id="staff-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">First Name</label><input class="admin-form-control" name="first_name" id="st-fname"></div>
                <div class="admin-form-group"><label class="admin-form-label">Last Name</label><input class="admin-form-control" name="last_name" id="st-lname"></div>
                <div class="admin-form-group"><label class="admin-form-label">Username *</label><input class="admin-form-control" name="username" id="st-uname" required></div>
                <div class="admin-form-group"><label class="admin-form-label">Email *</label><input class="admin-form-control" type="email" name="email" id="st-email" required></div>
                <div class="admin-form-group"><label class="admin-form-label">Phone</label><input class="admin-form-control" name="phone" id="st-phone"></div>
                <div class="admin-form-group"><label class="admin-form-label">Role</label>
                    <select class="admin-form-control" name="role" id="st-role">
                        <option value="farm_manager">Farm Manager</option>
                        <option value="stock_manager">Stock Manager</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Password (Keep empty to preserve current)</label><input class="admin-form-control" type="password" name="password" id="st-pass"></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('staff-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>
<script>
function openStaffModal(d) {
    document.getElementById('staff-modal-title').textContent = d?.id ? 'Edit Staff Account' : 'Add Staff Account';
    document.getElementById('staff-id').value = d?.id || '';
    document.getElementById('st-fname').value = d?.first_name || '';
    document.getElementById('st-lname').value = d?.last_name || '';
    document.getElementById('st-uname').value = d?.username || '';
    document.getElementById('st-email').value = d?.email || '';
    document.getElementById('st-phone').value = d?.phone_number || '';
    document.getElementById('st-role').value = d?.role || 'farm_manager';
    document.getElementById('st-pass').value = '';
    document.getElementById('staff-modal').style.display = 'flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('staff-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>

<!-- ══════ CUSTOMER LIST TAB ══════ -->
<?php elseif ($tab === 'users'): ?>
<div class="admin-card" style="padding:0; overflow:hidden;">
    <iframe src="users.php" style="width:100%; height:800px; border:none; display:block;"></iframe>
</div>

<!-- ══════ TASKS TAB ══════ -->
<?php elseif ($tab === 'tasks'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Assigned Tasks <?= helpTip("Work given to each staff member. Who does what, when it is due, and whether it is done.") ?></h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Schedule routines, feeding schedules, or vet visits for specific employees.</p>
        </div>
        <button class="btn btn-primary" onclick="openTaskModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Assign Task</button>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Title</th><th>Staff Assigned</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($taskList)): ?>
                <tr><td colspan="5" style="text-align:center;padding:28px;color:#94a3b8;">No tasks configured.</td></tr>
            <?php else: foreach ($taskList as $t): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></strong><br><small style="color:#64748b;"><?= htmlspecialchars($t['description']??'', ENT_QUOTES, 'UTF-8') ?></small></td>
                    <td><?= htmlspecialchars($t['assigned_name'] ?: 'Unassigned', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($t['due_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php
                        $ts = $t['status'] ?? 'Pending';
                        $pill = $ts==='Completed'?'badge-pill-success':($ts==='In Progress'?'badge-pill-warning':'badge-pill-warning');
                        ?>
                        <span class="badge-pill <?= $pill ?>"><?= htmlspecialchars($ts, ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openTaskModal(<?= htmlspecialchars(json_encode($t), ENT_QUOTES, "UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button></div></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="task-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:500px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 id="task-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Assign Task</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_task">
            <input type="hidden" name="id" id="task-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Task Title *</label><input class="admin-form-control" name="title" id="task-title" required></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Details / Instructions</label><input class="admin-form-control" name="description" id="task-desc"></div>
                <div class="admin-form-group"><label class="admin-form-label">Assign To</label>
                    <select class="admin-form-control" name="assigned_to" id="task-assign">
                        <option value="">-- Choose Staff --</option>
                        <?php foreach ($staffList as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars(trim($s['first_name'] . ' ' . $s['last_name']) ?: $s['username'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Due Date</label><input class="admin-form-control" type="date" name="due_date" id="task-due"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Status</label>
                    <select class="admin-form-control" name="status" id="task-status">
                        <option>Pending</option><option>In Progress</option><option>Completed</option><option>Cancelled</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('task-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>
<script>
function openTaskModal(d) {
    document.getElementById('task-modal-title').textContent = d?.id ? 'Edit Task Assignment' : 'Assign Task';
    document.getElementById('task-id').value = d?.id || '';
    document.getElementById('task-title').value = d?.title || '';
    document.getElementById('task-desc').value = d?.description || '';
    document.getElementById('task-assign').value = d?.assigned_to || '';
    document.getElementById('task-due').value = d?.due_date || '';
    document.getElementById('task-status').value = d?.status || 'Pending';
    document.getElementById('task-modal').style.display = 'flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('task-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>

<!-- ══════ TEAM MESSAGES TAB ══════ -->
<?php elseif ($tab === 'messages'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Secure Team Messages <?= helpTip("Private messages between team members. Keep communication on record so nothing is lost.") ?></h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Dispatch general announcements or individual memos.</p>
        </div>
        <button class="btn btn-primary" onclick="openMsgModal()"><i data-lucide="send" style="width:16px;height:16px;"></i> Send Message</button>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>From</th><th>To</th><th>Subject</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($messageList)): ?>
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">No messages sent or received.</td></tr>
            <?php else: foreach ($messageList as $msg): ?>
                <tr>
                    <td><?= htmlspecialchars($msg['from_user'] ?? 'System', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($msg['to_user'] ?? 'All', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><strong><?= htmlspecialchars($msg['subject'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars(substr($msg['created_at'] ?? '', 0, 16), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge-pill badge-pill-success"><?= ucfirst(htmlspecialchars($msg['status'], ENT_QUOTES, 'UTF-8')) ?></span></td>
                    <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openMsgView(<?= htmlspecialchars(json_encode($msg), ENT_QUOTES, "UTF-8") ?>)'><i data-lucide="eye" style="width:13px;height:13px;"></i> View</button></div></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="msgview-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:500px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 4px;font-family:'Outfit',sans-serif;" id="msgview-subject"></h3>
        <p style="margin:0 0 18px;font-size:0.85rem;color:#64748b;" id="msgview-meta"></p>
        <div id="msgview-body" style="padding:16px;background:#f8fafc;border-radius:8px;border:1px solid var(--admin-border);white-space:pre-wrap;line-height:1.7;margin-bottom:20px;"></div>
        <div style="display:flex;gap:12px;">
            <button class="btn btn-outline" style="flex:1;" onclick="document.getElementById('msgview-modal').style.display='none'">Close</button>
            <button class="btn btn-primary" style="flex:1;" onclick="replyToMessage()"><i data-lucide="reply" style="width:15px;height:15px;"></i> Reply</button>
        </div>
    </div>
</div>

<div id="compose-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Send Message</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="send_message">
            <div class="admin-form-group">
                <label class="admin-form-label">Send To *</label>
                <select class="admin-form-control" name="recipient_id" id="msg-recipient" required>
                    <option value="">-- Choose recipient --</option>
                    <?php foreach ($staffList as $s): ?>
                    <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars(trim($s['first_name'] . ' ' . $s['last_name']) ?: $s['username'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-form-group"><label class="admin-form-label">Subject *</label><input class="admin-form-control" name="subject" id="msg-subject" required></div>
            <div class="admin-form-group"><label class="admin-form-label">Message *</label><textarea class="admin-form-control" name="body" rows="4" required></textarea></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('compose-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="send" style="width:15px;height:15px;"></i> Send</button>
            </div>
        </form>
    </div>
</div>
<script>
let _replyRecipientId = 0;
function openMsgModal() {
    document.getElementById('msg-recipient').value = '';
    document.getElementById('msg-subject').value = '';
    document.getElementById('compose-modal').style.display = 'flex';
}
function openMsgView(m) {
    _replyRecipientId = m.sender_id;
    document.getElementById('msgview-subject').textContent = m.subject;
    document.getElementById('msgview-meta').textContent = 'From: ' + (m.from_user || 'System') + ' | ' + m.created_at;
    document.getElementById('msgview-body').textContent = m.body;
    document.getElementById('msgview-modal').style.display = 'flex';
}
function replyToMessage() {
    document.getElementById('msgview-modal').style.display = 'none';
    document.getElementById('msg-recipient').value = _replyRecipientId;
    document.getElementById('msg-subject').value = 'Re: ' + document.getElementById('msgview-subject').textContent;
    document.getElementById('compose-modal').style.display = 'flex';
}
document.addEventListener('click',e=>{
    ['msgview-modal','compose-modal'].forEach(id=>{
        const el=document.getElementById(id);
        if(el&&e.target===el) el.style.display='none';
    });
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
