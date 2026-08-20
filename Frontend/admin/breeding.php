<?php
/**
 * Admin - Breeding Module
 */
declare(strict_types=1);
header('Location: /Frontend/admin/hub_operations.php?tab=breeding', true, 301);
exit;

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

$page_title = 'Breeding - Admin';
include __DIR__ . '/includes/admin_header.php';

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager'], true)) {
    header('Location: /wangariadmin');
    exit;
}

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_breeding'])) {
    $recordId = (int)($_POST['record_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $maleParent = trim($_POST['male_parent'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $dueDate = trim($_POST['due_date'] ?? '');
    $status = trim($_POST['status'] ?? 'Pending');
    $notes = trim($_POST['notes'] ?? '');

    if ($subject === '' || $type === '') {
        $error_message = 'Subject and type are required.';
    } else {
        try {
            if ($recordId > 0) {
                $stmt = $pdo->prepare('UPDATE breeding_records SET subject = ?, type = ?, male_parent = ?, date = ?, due_date = ?, status = ?, notes = ? WHERE id = ?');
                $stmt->execute([$subject, $type, $maleParent, $date ?: null, $dueDate ?: null, $status, $notes, $recordId]);
                $message = 'Breeding record updated successfully.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO breeding_records (subject, type, male_parent, date, due_date, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$subject, $type, $maleParent, $date ?: null, $dueDate ?: null, $status, $notes]);
                $message = 'Breeding record saved successfully.';
            }
        } catch (Exception $e) {
            $error_message = 'Unable to save breeding record: ' . $e->getMessage();
        }
    }
}

$statuses = ['Pending', 'Insemination', 'Pregnant', 'Confirmed', 'Completed', 'Cancelled'];
$records = [];
if ($pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM breeding_records ORDER BY date DESC, created_at DESC');
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

$selectedRecord = null;
if (in_array($action, ['view', 'edit'], true) && $pdo) {
    $recordId = (int)($_GET['id'] ?? 0);
    if ($recordId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM breeding_records WHERE id = ?');
        $stmt->execute([$recordId]);
        $selectedRecord = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

function renderInput(string $label, string $name, string $value = '', string $type = 'text'): string {
    return '<div class="admin-form-group"><label class="admin-form-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label><input class="admin-form-control" type="' . $type . '" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"></div>';
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:16px;">
    <div>
        <h2 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.5rem;color:var(--admin-text-heading);">Breeding</h2>
        <p style="margin:4px 0 0 0;font-size:0.9rem;color:#64748b;">Track mating, pregnancy and birth plans.</p>
    </div>
    <a href="?action=add" class="btn btn-primary" style="border-radius:4px;display:inline-flex;align-items:center;gap:8px;">
        <i data-lucide="plus-circle" style="width:18px;height:18px;"></i>
        <span>Add Record</span>
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
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;color:var(--admin-text-heading);">Breeding Records</h3>
        <span style="font-size:0.85rem;color:#64748b;">Actual breeding records from the database</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding: 20px; color: #64748b;">No breeding records available.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($records as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['subject'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($item['type'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($item['date'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($item['due_date'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td style="text-align:right;">
                        <a class="btn btn-outline btn-sm" href="?action=view&id=<?php echo (int)$item['id']; ?>">View</a>
                        <a class="btn btn-outline btn-sm" href="?action=edit&id=<?php echo (int)$item['id']; ?>">Edit</a>
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
        'subject' => '',
        'type' => '',
        'male_parent' => '',
        'date' => '',
        'due_date' => '',
        'status' => 'Pending',
        'notes' => '',
    ];
    if ($action === 'edit' && $selectedRecord) {
        $formValues = [
            'subject' => $selectedRecord['subject'],
            'type' => $selectedRecord['type'],
            'male_parent' => $selectedRecord['male_parent'],
            'date' => $selectedRecord['date'],
            'due_date' => $selectedRecord['due_date'],
            'status' => $selectedRecord['status'],
            'notes' => $selectedRecord['notes'],
        ];
    }
?>
<div class="admin-card">
    <h3 style="margin:0 0 16px 0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">
        <?php echo $action === 'add' ? 'Add Breeding' : 'Edit Breeding'; ?>
    </h3>
    <form method="POST" action="">
        <input type="hidden" name="save_breeding" value="1">
        <?php if ($action === 'edit' && $selectedRecord): ?>
            <input type="hidden" name="record_id" value="<?php echo (int)$selectedRecord['id']; ?>">
        <?php endif; ?>
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
            <?php echo renderInput('Animal / Herd', 'subject', $formValues['subject']); ?>
            <?php echo renderInput('Type', 'type', $formValues['type']); ?>
            <?php echo renderInput('Male Parent', 'male_parent', $formValues['male_parent']); ?>
            <div class="admin-form-group">
                <label class="admin-form-label">Date</label>
                <input class="admin-form-control" type="date" name="date" value="<?php echo htmlspecialchars($formValues['date'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Due Date</label>
                <input class="admin-form-control" type="date" name="due_date" value="<?php echo htmlspecialchars($formValues['due_date'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Status</label>
                <select class="admin-form-control" name="status">
                    <?php foreach ($statuses as $statusOption): ?>
                        <option value="<?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $formValues['status'] === $statusOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-form-group" style="grid-column: span 2;">
                <label class="admin-form-label">Notes</label>
                <textarea class="admin-form-control" name="notes" rows="4"><?php echo htmlspecialchars($formValues['notes'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>
        <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary" style="border-radius:4px;">Save</button>
            <a href="/Frontend/admin/breeding.php" class="btn btn-outline" style="border-radius:4px;">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">Breeding Record</h3>
            <p style="margin:6px 0 0 0;color:#64748b;">View breeding details and due date.</p>
        </div>
        <a href="/Frontend/admin/breeding.php" class="btn btn-outline" style="border-radius:4px;">Back</a>
    </div>
    <?php if ($selectedRecord): ?>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
        <div><strong>Animal / Herd:</strong> <?php echo htmlspecialchars($selectedRecord['subject'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Type:</strong> <?php echo htmlspecialchars($selectedRecord['type'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Date:</strong> <?php echo htmlspecialchars($selectedRecord['date'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Due:</strong> <?php echo htmlspecialchars($selectedRecord['due_date'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Status:</strong> <?php echo htmlspecialchars($selectedRecord['status'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Male Parent:</strong> <?php echo htmlspecialchars($selectedRecord['male_parent'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div style="grid-column: span 2;"><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($selectedRecord['notes'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
    </div>
    <?php else: ?>
    <p style="color:#64748b;">Record not found.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
