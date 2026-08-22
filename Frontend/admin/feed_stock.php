<?php
/**
 * Admin - Feed Stock Module
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();

$page_title = 'Feed Stock - Admin';
include __DIR__ . '/includes/admin_header.php';

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager', 'stock_manager'], true)) {
    header('Location: /Frontend/pages/login.php');
    exit;
}

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_feed'])) {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $feedType = trim($_POST['feed_type'] ?? 'Feed');
    $stockTons = (float)($_POST['stock_tons'] ?? 0);
    $pricePerTon = (float)($_POST['price_per_ton'] ?? 0);
    $minStockLevel = (float)($_POST['min_stock_level'] ?? 0);

    if ($name === '') {
        $error_message = 'Feed item name is required.';
    } else {
        try {
            if ($itemId > 0) {
                $stmt = $pdo->prepare('UPDATE raw_materials SET name = ?, feed_type = ?, stock_tons = ?, current_price_per_ton = ?, min_stock_level = ? WHERE id = ?');
                $stmt->execute([$name, $feedType, $stockTons, $pricePerTon, $minStockLevel, $itemId]);
                $message = 'Feed item updated successfully.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO raw_materials (name, feed_type, stock_tons, current_price_per_ton, min_stock_level) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$name, $feedType, $stockTons, $pricePerTon, $minStockLevel]);
                $message = 'Feed item saved successfully.';
            }
        } catch (Exception $e) {
            $error_message = 'Unable to save feed item: ' . $e->getMessage();
        }
    }
}

$feedItems = [];
if ($pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM raw_materials ORDER BY name ASC');
        $feedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

$selectedItem = null;
if (in_array($action, ['view', 'edit'], true) && $pdo) {
    $itemId = (int)($_GET['id'] ?? 0);
    if ($itemId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM raw_materials WHERE id = ?');
        $stmt->execute([$itemId]);
        $selectedItem = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

function renderInput(string $label, string $name, string $value = '', string $type = 'text'): string {
    return '<div class="admin-form-group"><label class="admin-form-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label><input class="admin-form-control" type="' . $type . '" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"></div>';
}

function calculateStatus(array $item): string {
    return ((float)$item['stock_tons'] <= (float)$item['min_stock_level']) ? 'Low' : 'Good';
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:16px;">
    <div>
        <h2 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.5rem;color:var(--admin-text-heading);">Feed Stock</h2>
        <p style="margin:4px 0 0 0;font-size:0.9rem;color:#64748b;">Control feed items and raw materials.</p>
    </div>
    <a href="?action=add" class="btn btn-primary" style="border-radius:4px;display:inline-flex;align-items:center;gap:8px;">
        <i data-lucide="plus-circle" style="width:18px;height:18px;"></i>
        <span>Add Feed Item</span>
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
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;color:var(--admin-text-heading);">Feed Items</h3>
        <span style="font-size:0.85rem;color:#64748b;">Inventory of raw materials and feed ingredients</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Stock</th>
                    <th>Unit</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($feedItems)): ?>
                <tr><td colspan="6" style="text-align:center; padding: 20px; color: #64748b;">No feed stock items found.</td></tr>
                <?php else: ?>
                <?php foreach ($feedItems as $feed): ?>
                <tr>
                    <td><?php echo htmlspecialchars($feed['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($feed['feed_type'] ?? 'Feed', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo number_format((float)$feed['stock_tons'], 3); ?></td>
                    <td>tons</td>
                    <td><?php echo calculateStatus($feed); ?></td>
                    <td style="text-align:right;">
                        <a class="btn btn-outline btn-sm" href="?action=view&id=<?php echo (int)$feed['id']; ?>">View</a>
                        <a class="btn btn-outline btn-sm" href="?action=edit&id=<?php echo (int)$feed['id']; ?>">Edit</a>
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
        'feed_type' => 'Feed',
        'stock_tons' => '',
        'price_per_ton' => '',
        'min_stock_level' => '',
    ];
    if ($action === 'edit' && $selectedItem) {
        $formValues = [
            'name' => $selectedItem['name'],
            'feed_type' => $selectedItem['feed_type'] ?? 'Feed',
            'stock_tons' => $selectedItem['stock_tons'],
            'price_per_ton' => $selectedItem['current_price_per_ton'],
            'min_stock_level' => $selectedItem['min_stock_level'],
        ];
    }
?>
<div class="admin-card">
    <h3 style="margin:0 0 16px 0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">
        <?php echo $action === 'add' ? 'Add Feed Item' : 'Edit Feed Item'; ?>
    </h3>
    <form method="POST" action="">
        <input type="hidden" name="save_feed" value="1">
        <?php if ($action === 'edit' && $selectedItem): ?>
            <input type="hidden" name="item_id" value="<?php echo (int)$selectedItem['id']; ?>">
        <?php endif; ?>
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
            <?php echo renderInput('Item Name', 'name', $formValues['name']); ?>
            <?php echo renderInput('Feed Type', 'feed_type', $formValues['feed_type']); ?>
            <div class="admin-form-group">
                <label class="admin-form-label">Stock (tons)</label>
                <input class="admin-form-control" type="number" step="0.001" name="stock_tons" value="<?php echo htmlspecialchars($formValues['stock_tons'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Price per Ton</label>
                <input class="admin-form-control" type="number" step="0.01" name="price_per_ton" value="<?php echo htmlspecialchars($formValues['price_per_ton'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Minimum Stock Level</label>
                <input class="admin-form-control" type="number" step="0.001" name="min_stock_level" value="<?php echo htmlspecialchars($formValues['min_stock_level'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>
        <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary" style="border-radius:4px;">Save</button>
            <a href="/Frontend/admin/feed_stock.php" class="btn btn-outline" style="border-radius:4px;">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">Feed Item</h3>
            <p style="margin:6px 0 0 0;color:#64748b;">Detail view for this feed record.</p>
        </div>
        <a href="/Frontend/admin/feed_stock.php" class="btn btn-outline" style="border-radius:4px;">Back</a>
    </div>
    <?php if ($selectedItem): ?>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
        <div><strong>Name:</strong> <?php echo htmlspecialchars($selectedItem['name'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Type:</strong> <?php echo htmlspecialchars($selectedItem['feed_type'] ?? 'Feed', ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Stock:</strong> <?php echo number_format((float)$selectedItem['stock_tons'], 3); ?> tons</div>
        <div><strong>Price / ton:</strong> KES <?php echo number_format((float)$selectedItem['current_price_per_ton'], 2); ?></div>
        <div><strong>Status:</strong> <?php echo calculateStatus($selectedItem); ?></div>
        <div><strong>Min Stock:</strong> <?php echo number_format((float)$selectedItem['min_stock_level'], 3); ?> tons</div>
    </div>
    <?php else: ?>
    <p style="color:#64748b;">Feed item not found.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
