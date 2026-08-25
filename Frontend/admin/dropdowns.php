<?php
/**
 * Admin - Dropdown Lists Management
 */
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
if (empty($_SESSION['user_id'])) { header('Location: /Frontend/pages/login.php'); exit; }
$pdo = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['_action'] ?? '';
    if ($action === 'add_dropdown') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'general');
        $value = trim($_POST['value'] ?? '');
        if ($name && $value) {
            try {
                $pdo->prepare('INSERT INTO dropdown_options (dropdown_name, category, option_value, sort_order) VALUES (?,?,?,0) ON DUPLICATE KEY UPDATE option_value=VALUES(option_value)')->execute([$name, $category, $value]);
                $message = "Added \"$value\" to $name";
            } catch (Exception $e) { $message = 'Error: ' . $e->getMessage(); }
        }
    }
    if ($action === 'delete_dropdown') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM dropdown_options WHERE id=?')->execute([$id]);
        $message = 'Option deleted.';
    }
}

$dropdowns = [];
if ($pdo) {
    try {
        if (tableExists($pdo, 'dropdown_options')) {
            $rows = $pdo->query('SELECT * FROM dropdown_options ORDER BY category, dropdown_name, sort_order')->fetchAll();
            foreach ($rows as $r) $dropdowns[$r['dropdown_name']][] = $r;
        }
    } catch (Exception $e) {}
}
?>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;font-family:Outfit,sans-serif;font-size:1.1rem;">Dropdown Lists</h3>
        <span style="color:#64748b;font-size:0.85rem;"><?= count($dropdowns); ?> lists configured</span>
    </div>
    <p style="color:#64748b;font-size:0.85rem;margin-bottom:20px;">Manage dropdown values used in forms across the system: species, payment methods, statuses, etc.</p>
    
    <?php if ($message): ?>
    <div style="padding:10px 16px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;color:#166534;margin-bottom:16px;"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin-bottom:24px;padding:16px;background:#f8fafc;border-radius:10px;">
        <input type="hidden" name="_action" value="add_dropdown">
        <div style="flex:1;min-width:140px;">
            <label style="display:block;font-size:0.8rem;font-weight:600;color:#374151;margin-bottom:4px;">List Name</label>
            <input type="text" name="name" required placeholder="e.g. species" style="width:100%;padding:8px 12px;border:1px solid #E5E7EB;border-radius:8px;">
        </div>
        <div style="flex:0.7;min-width:120px;">
            <label style="display:block;font-size:0.8rem;font-weight:600;color:#374151;margin-bottom:4px;">Category</label>
            <input type="text" name="category" value="general" placeholder="general" style="width:100%;padding:8px 12px;border:1px solid #E5E7EB;border-radius:8px;">
        </div>
        <div style="flex:1;min-width:140px;">
            <label style="display:block;font-size:0.8rem;font-weight:600;color:#374151;margin-bottom:4px;">Option Value</label>
            <input type="text" name="value" required placeholder="e.g. Chicken" style="width:100%;padding:8px 12px;border:1px solid #E5E7EB;border-radius:8px;">
        </div>
        <button type="submit" class="btn btn-primary" style="padding:8px 20px;">Add Option</button>
    </form>
    
    <?php if (empty($dropdowns)): ?>
    <div style="text-align:center;padding:32px;color:#94A3B8;"><p>No dropdown options configured yet. Add options above.</p></div>
    <?php else: ?>
    <?php foreach ($dropdowns as $name => $options): ?>
    <div style="margin-bottom:16px;">
        <h4 style="margin:0 0 8px;font-size:0.95rem;color:#1e293b;"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $name))) ?> <span style="font-weight:400;color:#94A3B8;font-size:0.8rem;">(<?= count($options) ?>)</span></h4>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <?php foreach ($options as $opt): ?>
            <div style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:#f1f5f9;border-radius:8px;font-size:0.85rem;">
                <span><?= htmlspecialchars($opt['option_value']) ?></span>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this option?');">
                    <input type="hidden" name="_action" value="delete_dropdown">
                    <input type="hidden" name="id" value="<?= (int)$opt['id'] ?>">
                    <button type="submit" style="background:none;border:none;color:#dc2626;cursor:pointer;padding:0;font-size:1rem;">&times;</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
