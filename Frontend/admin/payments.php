<?php
/**
 * Admin - Payments Module
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

$page_title = 'Payments - Admin';
include __DIR__ . '/includes/admin_header.php';

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager','sales_staff'], true)) {
    header('Location: /wangariadmin');
    exit;
}

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment'])) {
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $category = trim($_POST['category'] ?? 'Supplier');
    $amount = (float)($_POST['amount'] ?? 0);
    $method = trim($_POST['method'] ?? 'Cash');
    $status = trim($_POST['status'] ?? 'Pending');
    $date = trim($_POST['date'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($category === '' || $amount <= 0) {
        $error_message = 'Category and positive amount are required.';
    } else {
        try {
            if ($paymentId > 0) {
                $stmt = $pdo->prepare('UPDATE financial_records SET category = ?, amount = ?, payment_method = ?, payment_status = ?, transaction_date = ?, description = ? WHERE id = ?');
                $stmt->execute([$category, $amount, $method, $status, $date ?: null, $description, $paymentId]);
                $message = 'Payment record updated successfully.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO financial_records (type, category, amount, transaction_date, description, payment_method, payment_status) VALUES ("expense", ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$category, $amount, $date ?: null, $description, $method, $status]);
                $message = 'Payment record saved successfully.';
            }
        } catch (Exception $e) {
            $error_message = 'Unable to save payment record: ' . $e->getMessage();
        }
    }
}

$payments = [];
if ($pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM financial_records WHERE type = "expense" ORDER BY transaction_date DESC, created_at DESC');
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

$selectedPayment = null;
if (in_array($action, ['view', 'edit'], true) && $pdo) {
    $paymentId = (int)($_GET['id'] ?? 0);
    if ($paymentId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM financial_records WHERE id = ?');
        $stmt->execute([$paymentId]);
        $selectedPayment = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

function renderInput(string $label, string $name, string $value = '', string $type = 'text'): string {
    return '<div class="admin-form-group"><label class="admin-form-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label><input class="admin-form-control" type="' . $type . '" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"></div>';
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:16px;">
    <div>
        <h2 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.5rem;color:var(--admin-text-heading);">Payments</h2>
        <p style="margin:4px 0 0 0;font-size:0.9rem;color:#64748b;">Track payments and approvals.</p>
    </div>
    <a href="?action=add" class="btn btn-primary" style="border-radius:4px;display:inline-flex;align-items:center;gap:8px;">
        <i data-lucide="plus-circle" style="width:18px;height:18px;"></i>
        <span>Add Payment</span>
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
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;color:var(--admin-text-heading);">Payment List</h3>
        <span style="font-size:0.85rem;color:#64748b;">See pending and approved payments</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                <tr><td colspan="6" style="text-align:center; padding: 20px; color: #64748b;">No payments found.</td></tr>
                <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?php echo htmlspecialchars($payment['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>KES <?php echo number_format((float)$payment['amount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'Cash', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($payment['payment_status'] ?? 'Pending', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($payment['transaction_date'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td style="text-align:right;">
                        <a class="btn btn-outline btn-sm" href="?action=view&id=<?php echo (int)$payment['id']; ?>">View</a>
                        <a class="btn btn-outline btn-sm" href="?action=edit&id=<?php echo (int)$payment['id']; ?>">Edit</a>
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
        'category' => 'Supplier',
        'amount' => '',
        'method' => 'Cash',
        'status' => 'Pending',
        'date' => '',
        'description' => '',
    ];
    if ($action === 'edit' && $selectedPayment) {
        $formValues = [
            'category' => $selectedPayment['category'],
            'amount' => $selectedPayment['amount'],
            'method' => $selectedPayment['payment_method'] ?? 'Cash',
            'status' => $selectedPayment['payment_status'] ?? 'Pending',
            'date' => $selectedPayment['transaction_date'],
            'description' => $selectedPayment['description'],
        ];
    }
?>
<div class="admin-card">
    <h3 style="margin:0 0 16px 0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">
        <?php echo $action === 'add' ? 'Add Payment' : 'Edit Payment'; ?>
    </h3>
    <form method="POST" action="">
        <input type="hidden" name="save_payment" value="1">
        <?php if ($action === 'edit' && $selectedPayment): ?>
            <input type="hidden" name="payment_id" value="<?php echo (int)$selectedPayment['id']; ?>">
        <?php endif; ?>
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
            <?php echo renderInput('Payment Category', 'category', $formValues['category']); ?>
            <div class="admin-form-group">
                <label class="admin-form-label">Amount</label>
                <input class="admin-form-control" type="number" step="0.01" name="amount" value="<?php echo htmlspecialchars($formValues['amount'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <?php echo renderInput('Payment Method', 'method', $formValues['method']); ?>
            <div class="admin-form-group">
                <label class="admin-form-label">Status</label>
                <select class="admin-form-control" name="status">
                    <?php foreach (['Pending', 'Approved', 'Failed', 'Completed'] as $paymentStatus): ?>
                        <option value="<?php echo htmlspecialchars($paymentStatus, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $formValues['status'] === $paymentStatus ? 'selected' : ''; ?>><?php echo htmlspecialchars($paymentStatus, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Date</label>
                <input class="admin-form-control" type="date" name="date" value="<?php echo htmlspecialchars($formValues['date'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="admin-form-group" style="grid-column: span 2;">
                <label class="admin-form-label">Notes</label>
                <textarea class="admin-form-control" name="description" rows="4"><?php echo htmlspecialchars($formValues['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>
        <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary" style="border-radius:4px;">Save</button>
            <a href="/Frontend/admin/payments.php" class="btn btn-outline" style="border-radius:4px;">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">Payment Details</h3>
            <p style="margin:6px 0 0 0;color:#64748b;">View payment status and notes.</p>
        </div>
        <a href="/Frontend/admin/payments.php" class="btn btn-outline" style="border-radius:4px;">Back</a>
    </div>
    <?php if ($selectedPayment): ?>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
        <div><strong>Category:</strong> <?php echo htmlspecialchars($selectedPayment['category'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Amount:</strong> KES <?php echo number_format((float)$selectedPayment['amount'], 2); ?></div>
        <div><strong>Method:</strong> <?php echo htmlspecialchars($selectedPayment['payment_method'] ?? 'Cash', ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Status:</strong> <?php echo htmlspecialchars($selectedPayment['payment_status'] ?? 'Pending', ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Date:</strong> <?php echo htmlspecialchars($selectedPayment['transaction_date'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
        <div style="grid-column: span 2;"><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($selectedPayment['description'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
    </div>
    <?php else: ?>
    <p style="color:#64748b;">Payment record not found.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
