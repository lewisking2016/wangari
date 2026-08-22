<?php
/**
 * Admin - Farm Items Module
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

$page_title = 'Farm Items - Admin';
include __DIR__ . '/includes/admin_header.php';

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager'], true)) {
    header('Location: /Frontend/pages/login.php');
    exit;
}

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_item'])) {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $itemType = trim($_POST['item_type'] ?? '');
    $species = trim($_POST['species'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $status = trim($_POST['status'] ?? 'active');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $error_message = 'Item name is required.';
    } else {
        try {
            if ($itemId > 0) {
                $stmt = $pdo->prepare('UPDATE farm_items SET name = ?, item_type = ?, species = ?, price = ?, stock_quantity = ?, status = ?, description = ? WHERE id = ?');
                $stmt->execute([$name, $itemType, $species, $price, $stock, $status, $description, $itemId]);
                $message = 'Farm item updated successfully.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO farm_items (name, item_type, species, price, stock_quantity, status, description) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $itemType, $species, $price, $stock, $status, $description]);
                $message = 'Farm item saved successfully.';
            }
        } catch (Exception $e) {
            $error_message = 'Unable to save farm item: ' . $e->getMessage();
        }
    }
}

$items = [];
if ($pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM farm_items ORDER BY created_at DESC');
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

$selectedItem = null;
if (in_array($action, ['view', 'edit'], true) && $pdo) {
    $itemId = (int)($_GET['id'] ?? 0);
    if ($itemId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM farm_items WHERE id = ?');
        $stmt->execute([$itemId]);
        $selectedItem = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

function renderInput(string $label, string $name, string $value = '', string $type = 'text'): string {
    return '<div class="admin-form-group"><label class="admin-form-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label><input class="admin-form-control" type="' . $type . '" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"></div>';
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:16px;">
    <div>
        <h2 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.5rem;color:var(--admin-text-heading);">Farm Items</h2>
        <p style="margin:4px 0 0 0;font-size:0.9rem;color:#64748b;">Manage products and live animal listings.</p>
    </div>
    <a href="?action=add" class="btn btn-primary" style="border-radius:4px;display:inline-flex;align-items:center;gap:8px;">
        <i data-lucide="plus-circle" style="width:18px;height:18px;"></i>
        <span>Add Item</span>
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
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;color:var(--admin-text-heading);">Item List</h3>
        <span style="font-size:0.85rem;color:#64748b;">Products and animals for sale</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Species</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr><td colspan="6" style="text-align:center; padding: 20px; color: #64748b;">No farm items found.</td></tr>
                <?php else: ?>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($item['item_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($item['species'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>KES <?php echo number_format((float)$item['price'], 2); ?></td>
                    <td><?php echo (int)$item['stock_quantity']; ?></td>
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
        'name' => '',
        'item_type' => '',
        'species' => '',
        'price' => '',
        'stock' => '',
        'status' => 'active',
        'description' => '',
    ];
    if ($action === 'edit' && $selectedItem) {
        $formValues = [
            'name' => $selectedItem['name'],
            'item_type' => $selectedItem['item_type'],
            'species' => $selectedItem['species'],
            'price' => $selectedItem['price'],
            'stock' => $selectedItem['stock_quantity'],
            'status' => $selectedItem['status'],
            'description' => $selectedItem['description'],
        ];
    }
?>
<div class="admin-card">
    <h3 style="margin:0 0 16px 0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">
        <?php echo $action === 'add' ? 'Add Item' : 'Edit Item'; ?>
    </h3>
    <form method="POST" action="">
        <input type="hidden" name="save_item" value="1">
        <?php if ($action === 'edit' && $selectedItem): ?>
            <input type="hidden" name="item_id" value="<?php echo (int)$selectedItem['id']; ?>">
        <?php endif; ?>
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
            <?php echo renderInput('Name', 'name', $formValues['name']); ?>
            <?php echo renderInput('Type', 'item_type', $formValues['item_type']); ?>
            <?php echo renderInput('Species', 'species', $formValues['species']); ?>
            <div class="admin-form-group">
                <label class="admin-form-label">Price</label>
                <input class="admin-form-control" type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($formValues['price'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Stock</label>
                <input class="admin-form-control" type="number" name="stock" value="<?php echo htmlspecialchars($formValues['stock'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Status</label>
                <select class="admin-form-control" name="status">
                    <?php foreach (['active','out_of_stock','inactive'] as $statusOption): ?>
                        <option value="<?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $formValues['status'] === $statusOption ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $statusOption)), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-form-group" style="grid-column: span 2;">
                <label class="admin-form-label">Notes</label>
                <textarea class="admin-form-control" name="description" rows="4"><?php echo htmlspecialchars($formValues['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>
        <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary" style="border-radius:4px;">Save</button>
            <a href="/Frontend/admin/farm_items.php" class="btn btn-outline" style="border-radius:4px;">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>
<?php if ($selectedItem): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">Item Details</h3>
            <p style="margin:6px 0 0 0;color:#64748b;">Details for this farm item.</p>
        </div>
        <a href="/Frontend/admin/farm_items.php" class="btn btn-outline" style="border-radius:4px;">Back</a>
    </div>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
        <div><strong>Name:</strong> <?php echo htmlspecialchars($selectedItem['name'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Type:</strong> <?php echo htmlspecialchars($selectedItem['item_type'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Species:</strong> <?php echo htmlspecialchars($selectedItem['species'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Price:</strong> KES <?php echo number_format((float)$selectedItem['price'], 2); ?></div>
        <div><strong>Stock:</strong> <?php echo (int)$selectedItem['stock_quantity']; ?></div>
        <div><strong>Status:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $selectedItem['status'])), ENT_QUOTES, 'UTF-8'); ?></div>
        <div style="grid-column: span 2;"><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($selectedItem['description'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
    </div>
</div>
<?php else: ?>
<div class="admin-card">
    <p style="color:#64748b;">Item not found.</p>
</div>
<?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
