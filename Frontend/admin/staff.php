<?php
/**
 * Admin - Staff Management Module
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

$page_title = 'Staff - Admin';
include __DIR__ . '/includes/admin_header.php';

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager','sales_staff'], true)) {
    header('Location: /wangariadmin');
    exit;
}

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$message = '';
$error_message = '';
$currentAdminId = (int)($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_staff'])) {
    $staffId = (int)($_POST['staff_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? 'farm_manager');
    $status = trim($_POST['status'] ?? 'active');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $email === '') {
        $error_message = 'Username and email are required.';
    } else {
        try {
            if ($staffId > 0) {
                $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, phone_number = ?, role = ? WHERE id = ?');
                $stmt->execute([$username, $email, $firstName, $lastName, $phone, $role, $staffId]);
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $stmt->execute([$hash, $staffId]);
                }
                $message = 'Staff record updated successfully.';
            } else {
                $hash = password_hash($password !== '' ? $password : 'Staff@123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$username, $email, $hash, $role, $firstName, $lastName, $phone]);
                $message = 'Staff member added successfully.';
            }
        } catch (Exception $e) {
            $error_message = 'Unable to save staff member: ' . $e->getMessage();
        }
    }
}

$users = [];
if ($pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM users WHERE role IN ("super_admin", "farm_manager", "stock_manager", "sales_staff") ORDER BY created_at DESC');
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

$selectedUser = null;
if (in_array($action, ['view', 'edit'], true) && $pdo) {
    $userId = (int)($_GET['id'] ?? 0);
    if ($userId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $selectedUser = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

function renderInput(string $label, string $name, string $value = '', string $type = 'text'): string {
    return '<div class="admin-form-group"><label class="admin-form-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label><input class="admin-form-control" type="' . $type . '" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"></div>';
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:16px;">
    <div>
        <h2 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.5rem;color:var(--admin-text-heading);">Staff</h2>
        <p style="margin:4px 0 0 0;font-size:0.9rem;color:#64748b;">Manage farm staff and team roles.</p>
    </div>
    <a href="?action=add" class="btn btn-primary" style="border-radius:4px;display:inline-flex;align-items:center;gap:8px;">
        <i data-lucide="user-plus" style="width:18px;height:18px;"></i>
        <span>Add Staff</span>
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
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;color:var(--admin-text-heading);">Staff List</h3>
        <span style="font-size:0.85rem;color:#64748b;">Live staff accounts</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr><td colspan="5" style="text-align:center; padding: 20px; color: #64748b;">No staff members found.</td></tr>
                <?php else: ?>
                <?php foreach ($users as $staff): ?>
                <tr>
                    <td><?php echo htmlspecialchars(trim(($staff['first_name'] ?? '') . ' ' . ($staff['last_name'] ?? '')) ?: $staff['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($staff['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($staff['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($staff['phone_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <div class="tbl-actions">
                            <a class="btn btn-info btn-sm" href="?action=view&id=<?php echo (int)$staff['id']; ?>">
                                <i data-lucide="eye" style="width:13px;height:13px;"></i> View
                            </a>
                            <a class="btn btn-trans btn-sm" href="?action=edit&id=<?php echo (int)$staff['id']; ?>">
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
        'username' => '',
        'email' => '',
        'first_name' => '',
        'last_name' => '',
        'phone' => '',
        'role' => 'farm_manager',
        'status' => 'active',
        'password' => '',
    ];
    if ($action === 'edit' && $selectedUser) {
        $formValues = [
            'username' => $selectedUser['username'],
            'email' => $selectedUser['email'],
            'first_name' => $selectedUser['first_name'],
            'last_name' => $selectedUser['last_name'],
            'phone' => $selectedUser['phone_number'] ?? '',
            'role' => $selectedUser['role'],
            'status' => 'active',
            'password' => '',
        ];
    }
?>
<div class="admin-card">
    <h3 style="margin:0 0 16px 0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">
        <?php echo $action === 'add' ? 'Add Staff' : 'Edit Staff'; ?>
    </h3>
    <form method="POST" action="">
        <input type="hidden" name="save_staff" value="1">
        <?php if ($action === 'edit' && $selectedUser): ?>
            <input type="hidden" name="staff_id" value="<?php echo (int)$selectedUser['id']; ?>">
        <?php endif; ?>
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
            <?php echo renderInput('Username', 'username', $formValues['username']); ?>
            <?php echo renderInput('Email', 'email', $formValues['email'], 'email'); ?>
            <?php echo renderInput('First Name', 'first_name', $formValues['first_name']); ?>
            <?php echo renderInput('Last Name', 'last_name', $formValues['last_name']); ?>
            <?php echo renderInput('Phone', 'phone', $formValues['phone'], 'tel'); ?>
            <div class="admin-form-group">
                <label class="admin-form-label">Role</label>
                <select class="admin-form-control" name="role">
                    <?php foreach (['super_admin','farm_manager','stock_manager','sales_staff'] as $roleOption): ?>
                        <option value="<?php echo htmlspecialchars($roleOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $formValues['role'] === $roleOption ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $roleOption)), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php echo renderInput('Password', 'password', '', 'password'); ?>
        </div>
        <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save" style="width:16px;height:16px;"></i> Save Staff
            </button>
            <a href="/Frontend/admin/staff.php" class="btn btn-outline">
                <i data-lucide="x" style="width:16px;height:16px;"></i> Cancel
            </a>
        </div>
    </form>
</div>

<?php else: ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">Staff Details</h3>
            <p style="margin:6px 0 0 0;color:#64748b;">View staff member information.</p>
        </div>
        <a href="/Frontend/admin/staff.php" class="btn btn-outline">
            <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Back
        </a>
    </div>
    <?php if ($selectedUser): ?>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
        <div><strong>Name:</strong> <?php echo htmlspecialchars(trim(($selectedUser['first_name'] ?? '') . ' ' . ($selectedUser['last_name'] ?? '')) ?: $selectedUser['username'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Role:</strong> <?php echo htmlspecialchars($selectedUser['role'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Email:</strong> <?php echo htmlspecialchars($selectedUser['email'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Phone:</strong> <?php echo htmlspecialchars($selectedUser['phone_number'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Created At:</strong> <?php echo htmlspecialchars($selectedUser['created_at'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
    <?php else: ?>
    <p style="color:#64748b;">Staff member not found.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
