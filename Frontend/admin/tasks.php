<?php
/**
 * Admin - Tasks Module
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

$page_title = 'Tasks - Admin';
include __DIR__ . '/includes/admin_header.php';

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager', 'stock_manager', 'sales_staff'], true)) {
    header('Location: /wangariadmin');
    exit;
}

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$message = '';
$error_message = '';
$current_admin_id = (int)($_SESSION['user_id'] ?? 0);
$statuses = ['Pending', 'In Progress', 'Completed', 'Cancelled'];
$staffUsers = [];
$selectedTask = null;

if ($pdo) {
    try {
        $staffUsers = $pdo->query("SELECT id, username, first_name, last_name FROM users WHERE role IN ('super_admin','farm_manager','stock_manager') ORDER BY first_name ASC, last_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = 'Failed to load staff users: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_task'])) {
    $taskId = (int)($_POST['task_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $assignedTo = (int)($_POST['assigned_to'] ?? 0) ?: null;
    $dueDate = trim($_POST['due_date'] ?? '');
    $status = trim($_POST['status'] ?? 'Pending');

    if ($title === '') {
        $error_message = 'Task title is required.';
    } elseif (!in_array($status, $statuses, true)) {
        $error_message = 'Invalid task status selected.';
    } else {
        try {
            if ($taskId > 0) {
                $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ?, assigned_to = ?, due_date = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $description, $assignedTo, $dueDate ?: null, $status, $taskId]);
                $message = 'Task updated successfully.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO tasks (title, description, assigned_to, due_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $assignedTo, $dueDate ?: null, $status, $current_admin_id]);
                $message = 'Task created successfully.';
            }
        } catch (Exception $e) {
            $error_message = 'Unable to save task: ' . $e->getMessage();
        }
    }
}

$tasks = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT t.*, CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS assigned_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id ORDER BY COALESCE(t.due_date, t.created_at) ASC, t.created_at DESC");
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = 'Failed to load tasks: ' . $e->getMessage();
    }
}

if (in_array($action, ['view', 'edit'], true) && $pdo) {
    $taskId = (int)($_GET['id'] ?? 0);
    if ($taskId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $selectedTask = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

function renderInput(string $label, string $name, string $value = '', string $type = 'text'): string {
    return "<div class=\"admin-form-group\"><label class=\"admin-form-label\">" . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</label><input class=\"admin-form-control\" type=\"$type\" name=\"$name\" value=\"" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "\"></div>";
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:16px;">
    <div>
        <h2 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.5rem;color:var(--admin-text-heading);">Tasks</h2>
        <p style="margin:4px 0 0 0;font-size:0.9rem;color:#64748b;">Manage farm tasks and assignments in real time.</p>
    </div>
    <a href="?action=add" class="btn btn-primary" style="border-radius:4px;display:inline-flex;align-items:center;gap:8px;">
        <i data-lucide="plus-circle" style="width:18px;height:18px;"></i>
        <span>Add Task</span>
    </a>
</div>

<?php if ($message): ?>
<div style="padding:14px 18px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:6px;color:#166534;margin-bottom:20px;">
    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>
<?php if ($error_message): ?>
<div style="padding:14px 18px;background:#fee2e2;border:1px solid #fecaca;border-radius:6px;color:#b91c1c;margin-bottom:20px;">
    <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;color:var(--admin-text-heading);">Task List</h3>
        <span style="font-size:0.85rem;color:#64748b;">Real-time task assignments from the database</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Assigned</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding: 20px; color: #64748b;">No tasks have been created yet.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                <tr>
                    <td><?php echo htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($task['description'] ?: 'General', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($task['due_date'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(trim($task['assigned_name']) ?: 'Unassigned', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($task['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <div class="tbl-actions">
                            <a class="btn btn-info btn-sm" href="?action=view&id=<?php echo (int)$task['id']; ?>">
                                <i data-lucide="eye" style="width:13px;height:13px;"></i> View
                            </a>
                            <a class="btn btn-trans btn-sm" href="?action=edit&id=<?php echo (int)$task['id']; ?>">
                                <i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php elseif ($action === 'add' || $action === 'edit'): ?>
<?php
    $formValues = [
        'title' => '',
        'description' => '',
        'assigned_to' => '',
        'due_date' => '',
        'status' => 'Pending',
    ];
    if ($action === 'edit' && $selectedTask) {
        $formValues = [
            'title' => $selectedTask['title'],
            'description' => $selectedTask['description'],
            'assigned_to' => $selectedTask['assigned_to'],
            'due_date' => $selectedTask['due_date'],
            'status' => $selectedTask['status'],
        ];
    }
?>
<div class="admin-card">
    <h3 style="margin:0 0 16px 0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">
        <?php echo $action === 'add' ? 'Add Task' : 'Edit Task'; ?>
    </h3>
    <form method="POST" action="">
        <input type="hidden" name="save_task" value="1">
        <?php if ($action === 'edit' && $selectedTask): ?>
            <input type="hidden" name="task_id" value="<?php echo (int)$selectedTask['id']; ?>">
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
            <?php echo renderInput('Task title', 'title', $formValues['title']); ?>
            <?php echo renderInput('Type / Description', 'description', $formValues['description']); ?>
            <div class="admin-form-group">
                <label class="admin-form-label">Due Date</label>
                <input class="admin-form-control" type="date" name="due_date" value="<?php echo htmlspecialchars($formValues['due_date'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Assigned To</label>
                <select class="admin-form-control" name="assigned_to">
                    <option value="">-- Select Team Member --</option>
                    <?php foreach ($staffUsers as $staff): ?>
                        <option value="<?php echo (int)$staff['id']; ?>" <?php echo $formValues['assigned_to'] === $staff['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars(trim($staff['first_name'] . ' ' . $staff['last_name']) ?: $staff['username'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Status</label>
                <select class="admin-form-control" name="status">
                    <?php foreach ($statuses as $statusOption): ?>
                        <option value="<?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $formValues['status'] === $statusOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save" style="width:16px;height:16px;"></i> Save Task
            </button>
            <a href="/Frontend/admin/tasks.php" class="btn btn-outline">
                <i data-lucide="x" style="width:16px;height:16px;"></i> Cancel
            </a>
        </div>
    </form>
</div>
<?php else: ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">Task Details</h3>
            <p style="margin:6px 0 0 0;color:#64748b;">Review the selected task and assignment details.</p>
        </div>
        <a href="/Frontend/admin/tasks.php" class="btn btn-outline">
            <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Back
        </a>
    </div>
    <?php if ($selectedTask): ?>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
        <div><strong>Title:</strong> <?php echo htmlspecialchars($selectedTask['title'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Status:</strong> <?php echo htmlspecialchars($selectedTask['status'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($selectedTask['description'], ENT_QUOTES, 'UTF-8')); ?></div>
        <div><strong>Due Date:</strong> <?php echo htmlspecialchars($selectedTask['due_date'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Assigned:</strong> <?php echo htmlspecialchars(trim($selectedTask['assigned_name']) ?: 'Unassigned', ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Created By:</strong> <?php
            $creator = $pdo->prepare("SELECT CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) AS name FROM users WHERE id = ?");
            $creator->execute([(int)$selectedTask['created_by']]);
            $creatorName = $creator->fetchColumn() ?: 'System';
            echo htmlspecialchars(trim($creatorName) ?: 'System', ENT_QUOTES, 'UTF-8');
        ?></div>
    </div>
    <?php else: ?>
    <p style="color:#64748b;">Task not found.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
