<?php
/**
 * Admin - Sales Module
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

$page_title = 'Sales - Admin';
include __DIR__ . '/includes/admin_header.php';

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager','sales_staff'], true)) {
    header('Location: /Frontend/pages/login.php');
    exit;
}

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$message = '';
$error_message = '';

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $validStatuses = ['pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled'];

    if (in_array($newStatus, $validStatuses, true)) {
        try {
            $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $orderId]);
            $message = 'Order status updated successfully.';
        } catch (Exception $e) {
            $error_message = 'Unable to update order status: ' . $e->getMessage();
        }
    } else {
        $error_message = 'Invalid order status selected.';
    }
}

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$orders = [];
if ($pdo) {
    try {
        $query = 'SELECT o.*, u.username, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE 1=1';
        $params = [];
        if ($search !== '') {
            $query .= ' AND (o.order_number LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        if ($statusFilter !== '') {
            $query .= ' AND o.status = ?';
            $params[] = $statusFilter;
        }
        $query .= ' ORDER BY o.created_at DESC';
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

$selectedOrder = null;
if ($action === 'view' && $pdo) {
    $orderId = (int)($_GET['id'] ?? 0);
    if ($orderId > 0) {
        $stmt = $pdo->prepare('SELECT o.*, u.username, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?');
        $stmt->execute([$orderId]);
        $selectedOrder = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($selectedOrder) {
            $stmt = $pdo->prepare('SELECT oi.*, p.name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
            $stmt->execute([$orderId]);
            $selectedOrder['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

function renderInput(string $label, string $name, string $value = '', string $type = 'text'): string {
    return '<div class="admin-form-group"><label class="admin-form-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label><input class="admin-form-control" type="' . $type . '" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"></div>';
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:16px;">
    <div>
        <h2 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.5rem;color:var(--admin-text-heading);">Sales</h2>
        <p style="margin:4px 0 0 0;font-size:0.9rem;color:#64748b;">Manage customer sales and invoices.</p>
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

<?php if ($action === 'list'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;color:var(--admin-text-heading);">Sales List</h3>
        <span style="font-size:0.85rem;color:#64748b;">Recent customer sales</span>
    </div>
    <div style="margin-bottom:16px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <input type="text" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" style="padding:10px;border:1px solid #cbd5e1;border-radius:6px;min-width:220px;">
            <select name="status" style="padding:10px;border:1px solid #cbd5e1;border-radius:6px;">
                <option value="">All Statuses</option>
                <?php foreach (['pending','paid','processing','shipped','completed','cancelled'] as $statusOption): ?>
                <option value="<?php echo $statusOption; ?>" <?php echo $statusFilter === $statusOption ? 'selected' : ''; ?>><?php echo ucfirst($statusOption); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline" style="border-radius:4px;">Filter</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr><td colspan="6" style="text-align:center; padding: 20px; color: #64748b;">No sales orders found.</td></tr>
                <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?php echo htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($order['username'] ?: $order['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>KES <?php echo number_format((float)$order['total_amount'], 2); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($order['status']), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($order['created_at'] ?? 'now')), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td style="text-align:right;">
                        <a class="btn btn-outline btn-sm" href="?action=view&id=<?php echo (int)$order['id']; ?>">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($action === 'view'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">Order Details</h3>
            <p style="margin:6px 0 0 0;color:#64748b;">Review customer sales order information.</p>
        </div>
        <a href="/Frontend/admin/sales.php" class="btn btn-outline" style="border-radius:4px;">Back</a>
    </div>
    <?php if ($selectedOrder): ?>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;margin-bottom:20px;">
        <div><strong>Order #:</strong> <?php echo htmlspecialchars($selectedOrder['order_number'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($selectedOrder['status']), ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Customer:</strong> <?php echo htmlspecialchars($selectedOrder['username'] ?: 'Guest', ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Email:</strong> <?php echo htmlspecialchars($selectedOrder['email'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Total:</strong> KES <?php echo number_format((float)$selectedOrder['total_amount'], 2); ?></div>
        <div><strong>Payment:</strong> <?php echo htmlspecialchars($selectedOrder['payment_method'], ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($selectedOrder['items'])): ?>
                <tr><td colspan="4" style="text-align:center; padding: 20px; color: #64748b;">No products found for this order.</td></tr>
                <?php else: ?>
                <?php foreach ($selectedOrder['items'] as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo (int)$item['quantity']; ?></td>
                    <td>KES <?php echo number_format((float)$item['price_at_purchase'], 2); ?></td>
                    <td>KES <?php echo number_format((float)$item['quantity'] * (float)$item['price_at_purchase'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <p style="color:#64748b;">Order not found.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
