<?php
/**
 * Hub: Sales & Finance, ALL content inline, no double-includes.
 * Tabs: Orders | Sales Registry | Payments | Expenses | Financial Reports
 */
declare(strict_types=1);
$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','sales_staff'], true)) {
    echo "<script>window.location.href='/wangariadmin';</script>"; exit;
}

$page_title = 'Sales & Finance - Admin';
include __DIR__ . '/includes/admin_header.php';

$tab = $_GET['tab'] ?? 'orders';
$validTabs = ['orders', 'sales', 'payments', 'expenses', 'reports'];
if (!in_array($tab, $validTabs, true)) $tab = 'orders';

$pdo = getDB();
$message = ''; $error_message = '';

/* ── POST handler for Status Updates & Payments ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $postAction = $_POST['_action'] ?? '';

    if ($postAction === 'update_order_status') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $valid = ['pending','paid','processing','shipped','completed','cancelled'];
        if (in_array($newStatus, $valid, true)) {
            try {
                $pdo->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$newStatus, $orderId]);
                $message = 'Order status updated.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'orders';
    }

    if ($postAction === 'save_payment') {
        $id   = (int)($_POST['id'] ?? 0);
        $cat  = trim($_POST['category'] ?? '');
        $amt  = (float)($_POST['amount'] ?? 0);
        $meth = trim($_POST['method'] ?? 'Cash');
        $stat = trim($_POST['status'] ?? 'Pending');
        $date = trim($_POST['date'] ?? date('Y-m-d'));
        $desc = trim($_POST['description'] ?? '');

        if ($cat && $amt > 0) {
            try {
                if ($id > 0) {
                    $pdo->prepare('UPDATE financial_records SET category=?,amount=?,payment_method=?,payment_status=?,transaction_date=?,description=? WHERE id=?')
                        ->execute([$cat,$amt,$meth,$stat,$date,$desc,$id]);
                    $message = 'Payment updated.';
                } else {
                    $pdo->prepare('INSERT INTO financial_records (type,category,amount,payment_method,payment_status,transaction_date,description) VALUES ("income",?,?,?,?,?,?)')
                        ->execute([$cat,$amt,$meth,$stat,$date,$desc]);
                    $message = 'Payment recorded.';
                }
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        } else { $error_message = 'Category and amount are required.'; }
        $tab = 'payments';
    }
}

/* ── Load PHP-based tab data ── */
$orders = $paymentsList = [];
$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

if ($pdo) {
    try {
        if ($tab === 'orders') {
            $q = 'SELECT o.*, u.username, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE 1=1';
            $params = [];
            if ($search !== '') {
                $q .= ' AND (o.order_number LIKE ? OR u.username LIKE ?)';
                $params[] = "%$search%"; $params[] = "%$search%";
            }
            if ($statusFilter !== '') {
                $q .= ' AND o.status = ?';
                $params[] = $statusFilter;
            }
            $q .= ' ORDER BY o.created_at DESC LIMIT 200';
            $orders = safeQueryAll($pdo, $q, $params);
        } elseif ($tab === 'sales') {
            $orders = safeQueryAll($pdo, 'SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.status IN ("completed","paid") ORDER BY o.created_at DESC LIMIT 200');
        } elseif ($tab === 'payments') {
            $paymentsList = safeQueryAll($pdo, 'SELECT * FROM financial_records WHERE type="income" ORDER BY transaction_date DESC, created_at DESC LIMIT 200');
        }
    } catch (Exception $e) {
        error_log('Hub finance page query error: ' . $e->getMessage());
        $error_message = 'Database query error. Please retry.';
        $orders = [];
        $paymentsList = [];
    }
}

$tabs = [
    'orders'   => ['icon' => 'shopping-bag',  'label' => 'Customer Orders'],
    'sales'    => ['icon' => 'receipt',       'label' => 'Sales Summary'],
    'payments' => ['icon' => 'wallet',        'label' => 'Incoming Payments'],
    'expenses' => ['icon' => 'minus-circle',  'label' => 'Outgoing Expenses'],
    'reports'  => ['icon' => 'bar-chart-3',   'label' => 'Reports & Analytics'],
];
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Sales & Finance</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Track orders, payment status, general income, expenses, and generate reports.</p>
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

<!-- ══════ ORDERS TAB ══════ -->
<?php if ($tab === 'orders'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Customer Orders</h3>
        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
            <input type="hidden" name="tab" value="orders">
            <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search Order # or Client..." style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:0.88rem;outline:none;">
            <select name="status" style="padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:0.88rem;">
                <option value="">All Statuses</option>
                <?php foreach (['pending','paid','processing','shipped','completed','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $statusFilter===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline"><i data-lucide="search" style="width:15px;height:15px;"></i> Filter</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Order #</th><th>Customer</th><th>Total (KES)</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">No customer orders found.</td></tr>
            <?php else: foreach ($orders as $o): ?>
                <tr>
                    <td><strong>#<?= htmlspecialchars($o['order_number'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($o['username'] ?: ($o['email'] ?? 'Guest'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>KES <?= number_format((float)$o['total_amount'], 2) ?></td>
                    <td>
                        <?php
                        $sc = ['pending'=>'badge-pill-warning','paid'=>'badge-pill-success','completed'=>'badge-pill-success','cancelled'=>'badge-pill-danger','processing'=>'badge-pill-warning','shipped'=>'badge-pill-warning'];
                        $cls = $sc[$o['status']] ?? 'badge-pill-warning';
                        ?>
                        <span class="badge-pill <?= $cls ?>"><?= ucfirst(htmlspecialchars($o['status'], ENT_QUOTES, 'UTF-8')) ?></span>
                    </td>
                    <td><?= htmlspecialchars(substr($o['created_at'] ?? '', 0, 10), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div class="tbl-actions">
                            <button class="btn btn-info btn-sm" onclick="openOrderModal(<?= (int)$o['id'] ?>, '<?= htmlspecialchars($o['order_number'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($o['status'], ENT_QUOTES, 'UTF-8') ?>')">
                                <i data-lucide="edit-3" style="width:13px;height:13px;"></i> Status
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="order-status-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:400px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 8px;font-family:'Outfit',sans-serif;">Update Order Status</h3>
        <p id="order-label-display" style="margin:0 0 20px;color:#64748b;font-size:0.9rem;"></p>
        <form method="POST">
            <input type="hidden" name="_action" value="update_order_status">
            <input type="hidden" name="order_id" id="status-order-id">
            <div class="admin-form-group">
                <label class="admin-form-label">New Status</label>
                <select class="admin-form-control" name="status" id="status-select">
                    <?php foreach (['pending','paid','processing','shipped','completed','cancelled'] as $s): ?>
                    <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('order-status-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="check" style="width:15px;height:15px;"></i> Update</button>
            </div>
        </form>
    </div>
</div>
<script>
function openOrderModal(id, num, status) {
    document.getElementById('status-order-id').value = id;
    document.getElementById('order-label-display').textContent = 'Order #' + num;
    document.getElementById('status-select').value = status;
    document.getElementById('order-status-modal').style.display = 'flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('order-status-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>

<!-- ══════ SALES REGISTER ══════ -->
<?php elseif ($tab === 'sales'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Sales Register</h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Completed or fully paid client orders.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Order #</th><th>Customer</th><th>Amount (KES)</th><th>Transaction Date</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="5" style="text-align:center;padding:28px;color:#94a3b8;">No completed sales tracked yet.</td></tr>
            <?php else: foreach ($orders as $o): ?>
                <tr>
                    <td><strong>#<?= htmlspecialchars($o['order_number'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($o['username'] ?? 'Guest', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>KES <?= number_format((float)$o['total_amount'], 2) ?></td>
                    <td><?= htmlspecialchars(substr($o['created_at'] ?? '', 0, 16), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge-pill badge-pill-success">Completed</span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══════ PAYMENTS TAB ══════ -->
<?php elseif ($tab === 'payments'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Incoming Payments Ledger</h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Log physical collections, direct bank transfers, and M-Pesa receipts.</p>
        </div>
        <button class="btn btn-primary" onclick="openPayModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Log Payment</button>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Category</th><th>Amount</th><th>Method</th><th>Status</th><th>Notes</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($paymentsList)): ?>
                <tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">No payment logs yet.</td></tr>
            <?php else: foreach ($paymentsList as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['transaction_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($p['category'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><strong>KES <?= number_format((float)$p['amount'], 2) ?></strong></td>
                    <td><?= htmlspecialchars($p['payment_method'] ?? 'Cash', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php
                        $st = $p['payment_status'] ?? 'Pending';
                        $pill = $st==='Approved'||$st==='Completed'?'badge-pill-success':'badge-pill-warning';
                        ?>
                        <span class="badge-pill <?= $pill ?>"><?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td><?= htmlspecialchars($p['description'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openPayModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, "UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button></div></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="payment-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:500px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 id="pay-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Log Collection</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_payment">
            <input type="hidden" name="id" id="pay-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group"><label class="admin-form-label">Category *</label><input class="admin-form-control" name="category" id="pay-cat" required placeholder="e.g. Broiler Sale"></div>
                <div class="admin-form-group"><label class="admin-form-label">Amount (KES) *</label><input class="admin-form-control" type="number" step="0.01" name="amount" id="pay-amt" required></div>
                <div class="admin-form-group"><label class="admin-form-label">Payment Method</label>
                    <select class="admin-form-control" name="method" id="pay-meth">
                        <option>Cash</option><option>M-Pesa</option><option>Bank Transfer</option><option>Cheque</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Status</label>
                    <select class="admin-form-control" name="status" id="pay-stat">
                        <option>Approved</option><option>Pending</option><option>Failed</option>
                    </select>
                </div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Date</label><input class="admin-form-control" type="date" name="date" id="pay-date" value="<?= date('Y-m-d') ?>"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Remarks / Description</label><textarea class="admin-form-control" name="description" id="pay-desc" rows="2"></textarea></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('payment-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Log</button>
            </div>
        </form>
    </div>
</div>
<script>
function openPayModal(d) {
    document.getElementById('pay-modal-title').textContent = d?.id ? 'Edit Payment Log' : 'Log Collection';
    document.getElementById('pay-id').value = d?.id || '';
    document.getElementById('pay-cat').value = d?.category || '';
    document.getElementById('pay-amt').value = d?.amount || '';
    document.getElementById('pay-meth').value = d?.payment_method || 'Cash';
    document.getElementById('pay-stat').value = d?.payment_status || 'Approved';
    document.getElementById('pay-date').value = d?.transaction_date || '<?= date("Y-m-d") ?>';
    document.getElementById('pay-desc').value = d?.description || '';
    document.getElementById('payment-modal').style.display = 'flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('payment-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>

<!-- ══════ EXPENSES TAB ══════ -->
<?php elseif ($tab === 'expenses'): ?>
<div class="admin-card" style="padding:0; overflow:hidden;">
    <iframe src="expenses.php" style="width:100%; height:800px; border:none; display:block;"></tbody></table></iframe>
</div>

<!-- ══════ REPORTS TAB ══════ -->
<?php elseif ($tab === 'reports'): ?>
<div class="admin-card" style="padding:0; overflow:hidden;">
    <iframe src="reports.php" style="width:100%; height:800px; border:none; display:block;"></iframe>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
