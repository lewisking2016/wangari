<?php
/**
 * Admin - Setup Module
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

$page_title = 'Setup - Admin';
include __DIR__ . '/includes/admin_header.php';

if (empty($_SESSION['user_id']) || !in_array(isset($_SESSION['role']) ? $_SESSION['role'] : '', ['super_admin', 'farm_manager'], true)) {
    header('Location: /Frontend/pages/login.php');
    exit;
}

$pdo = getDB();
$section = isset($_GET['section']) ? $_GET['section'] : 'species';
$message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_setup'])) {
    $groupKey = trim($_POST['group_key'] ?? $section);
    $optionLabel = trim($_POST['option_label'] ?? '');
    $optionValue = trim($_POST['option_value'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 10);

    if ($groupKey === '' || $optionLabel === '') {
        $error_message = 'Group key and option label are required.';
    } else {
        if ($optionValue === '') {
            $optionValue = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $optionLabel));
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO system_dropdowns (group_key, group_label, option_value, option_label, sort_order, is_active, is_system) VALUES (?, ?, ?, ?, ?, 1, 0)');
            $label = ucwords(str_replace('_', ' ', $groupKey));
            $stmt->execute([$groupKey, $label, $optionValue, $optionLabel, $sortOrder]);
            $message = 'Setup option added successfully.';
        } catch (Exception $e) {
            $error_message = 'Unable to save setup option: ' . $e->getMessage();
        }
    }
}

$groupMap = [
    'species' => 'Species',
    'breeds' => 'Breeds',
    'vaccines' => 'Vaccines',
];
$selectedGroupLabel = $groupMap[$section] ?? 'Setup Group';

$options = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT id, option_label, option_value, sort_order, is_active FROM system_dropdowns WHERE group_key = ? ORDER BY sort_order ASC, option_label ASC');
        $stmt->execute([$section]);
        $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:16px;">
    <div>
        <h2 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.5rem;color:var(--admin-text-heading);">Setup</h2>
        <p style="margin:4px 0 0 0;font-size:0.9rem;color:#64748b;">Configure species, breeds, and health setup options.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="?section=species" class="btn <?php echo $section === 'species' ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius:4px;">Species</a>
        <a href="?section=breeds" class="btn <?php echo $section === 'breeds' ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius:4px;">Breeds</a>
        <a href="?section=vaccines" class="btn <?php echo $section === 'vaccines' ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius:4px;">Vaccines</a>
    </div>
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

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;color:var(--admin-text-heading);"><?php echo htmlspecialchars($selectedGroupLabel, ENT_QUOTES, 'UTF-8'); ?></h3>
        <span style="font-size:0.85rem;color:#64748b;">Manage dropdown values stored in system dropdowns</span>
    </div>
    <?php if (empty($options)): ?>
        <p style="color:#64748b;">No setup values found for this section yet.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Label</th>
                    <th>Value</th>
                    <th>Order</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($options as $opt): ?>
                <tr>
                    <td><?php echo htmlspecialchars($opt['option_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($opt['option_value'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo (int)$opt['sort_order']; ?></td>
                    <td><?php echo (int)$opt['is_active'] === 1 ? 'Active' : 'Inactive'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <form method="POST" style="margin-top:24px;">
        <input type="hidden" name="save_setup" value="1">
        <input type="hidden" name="group_key" value="<?php echo htmlspecialchars($section, ENT_QUOTES, 'UTF-8'); ?>">
        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px;">
            <div class="admin-form-group">
                <label class="admin-form-label">Option Label</label>
                <input class="admin-form-control" type="text" name="option_label" required>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Option Value</label>
                <input class="admin-form-control" type="text" name="option_value" placeholder="Auto-generated if blank">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Sort Order</label>
                <input class="admin-form-control" type="number" name="sort_order" value="10">
            </div>
        </div>
        <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary" style="border-radius:4px;">Add Option</button>
            <a href="/Frontend/admin/setup.php?section=<?php echo htmlspecialchars($section, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline" style="border-radius:4px;">Refresh</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
