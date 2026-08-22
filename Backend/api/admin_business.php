<?php
/**
 * Backend API — Business & Operations
 * Plain-English actions: list_costs, add_cost, list_cashbook, add_money_in,
 * add_money_out, list_credit, add_credit, add_payment, list_feeding,
 * add_allocation, list_purchase_orders, create_po, receive_po,
 * list_weighings, add_weighing, list_hatchery, add_hatch,
 * list_egg_losses, add_egg_loss, list_quality_tests, add_quality_test,
 * list_alerts, mark_alert_read, dashboard_overview
 *
 * All simple language. No jargon.
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();

require_once __DIR__ . '/../config/database.php';

function ok(array $d = []): never { echo json_encode(array_merge(['success' => true], $d)); exit; }
function err(string $m, int $c = 400): never { http_response_code($c); echo json_encode(['success' => false, 'message' => $m]); exit; }

// Auto-run new tables if missing — self-healing
require_once __DIR__ . '/../config/auto_migrate.php';

try {
    if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager', 'stock_manager', 'sales_staff'], true)) {
        err('You must be logged in', 401);
    }
    $pdo = getDatabaseConnection();
    if (!$pdo) err('Database error', 500);

    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    $user_id = (int)$_SESSION['user_id'];
    $today = date('Y-m-d');

    if ($method !== 'GET' && $method !== 'POST') err('Bad method', 405);

    // ─────────────────────────────────────────────────────────
    // BATCH COSTS
    // ─────────────────────────────────────────────────────────
    if ($action === 'list_costs') {
        $batch = (int)($_GET['batch_id'] ?? 0);
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $sql = "SELECT c.*, b.batch_name, b.batch_code FROM batch_costs c
                LEFT JOIN batches b ON b.id=c.batch_id WHERE 1=1";
        $p = [];
        if ($batch) { $sql .= " AND c.batch_id=?"; $p[] = $batch; }
        if ($from) { $sql .= " AND c.cost_date>=?"; $p[] = $from; }
        if ($to) { $sql .= " AND c.cost_date<=?"; $p[] = $to; }
        $sql .= " ORDER BY c.cost_date DESC LIMIT 500";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($p);
        ok(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    if ($action === 'add_cost' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $batch_id = (int)($_POST['batch_id'] ?? 0);
        $date = $_POST['cost_date'] ?? $today;
        $type = $_POST['cost_type'] ?? 'misc';
        $desc = trim($_POST['description'] ?? '');
        $qty = (float)($_POST['quantity'] ?? 0);
        $unit = $_POST['unit'] ?? 'unit';
        $unit_cost = (float)($_POST['unit_cost'] ?? 0);
        $total = (float)($_POST['total_cost'] ?? ($qty * $unit_cost));
        $paid_from = $_POST['paid_from'] ?? 'cash';
        $ref = trim($_POST['reference_no'] ?? '');
        if ($batch_id === 0) err('Choose a batch');
        if ($total <= 0) err('Enter the amount');
        if ($id > 0) {
            $pdo->prepare("UPDATE batch_costs SET batch_id=?, cost_date=?, cost_type=?, description=?, quantity=?, unit=?, unit_cost=?, total_cost=?, paid_from=?, reference_no=?, recorded_by=? WHERE id=?")
                ->execute([$batch_id, $date, $type, $desc, $qty, $unit, $unit_cost, $total, $paid_from, $ref, $user_id, $id]);
        } else {
            $pdo->prepare("INSERT INTO batch_costs (batch_id, cost_date, cost_type, description, quantity, unit, unit_cost, total_cost, paid_from, reference_no, recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$batch_id, $date, $type, $desc, $qty, $unit, $unit_cost, $total, $paid_from, $ref, $user_id]);
            $id = (int)$pdo->lastInsertId();
        }
        // Auto-create cashbook entry for the expense
        $pdo->prepare("INSERT INTO cashbook_entries (entry_date, direction, money_source, amount, paid_through, supplier_name, reference_no, description, recorded_by) VALUES (?, 'out', ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$date, $type, $total, $paid_from === 'cash' ? 'cash' : ($paid_from === 'mpesa' ? 'mpesa' : 'bank'), null, $ref, "[Batch cost #$id] $desc", $user_id]);
        logActivity($pdo, 'save', 'costs', "Batch cost KES {$total} ($desc)", $id, 'batch_cost');
        ok(['message' => 'Cost saved', 'id' => $id]);
    }
    if ($action === 'delete_cost' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM batch_costs WHERE id=?")->execute([$id]);
        logActivity($pdo, 'delete', 'costs', "Deleted batch cost #{$id}", $id, 'batch_cost');
        ok(['message' => 'Cost deleted']);
    }
    if ($action === 'batch_profit' && $method === 'GET') {
        $batch_id = (int)($_GET['batch_id'] ?? 0);
        if (!$batch_id) err('Batch required');
        $batch = $pdo->query("SELECT * FROM batches WHERE id=$batch_id")->fetch(PDO::FETCH_ASSOC);
        $costs = $pdo->query("SELECT cost_type, SUM(total_cost) AS total FROM batch_costs WHERE batch_id=$batch_id GROUP BY cost_type")->fetchAll(PDO::FETCH_ASSOC);
        $costByType = [];
        $totalCost = 0;
        foreach ($costs as $c) { $costByType[$c['cost_type']] = (float)$c['total']; $totalCost += (float)$c['total']; }
        $revenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bulk_sales WHERE notes LIKE '%batch #$batch_id%'")->fetchColumn();
        // Fallback: sum from feed_production_batches sold? Use sold_birds * estimated price
        $profit = $revenue - $totalCost;
        ok(['data' => [
            'batch' => $batch,
            'total_cost' => $totalCost,
            'cost_breakdown' => $costByType,
            'revenue' => $revenue,
            'profit' => $profit,
            'profit_per_bird' => $batch['current_birds'] > 0 ? $profit / $batch['current_birds'] : 0,
            'cost_per_bird' => $batch['current_birds'] > 0 ? $totalCost / $batch['current_birds'] : 0,
        ]]);
    }

    // ─────────────────────────────────────────────────────────
    // CASHBOOK
    // ─────────────────────────────────────────────────────────
    if ($action === 'list_cashbook') {
        if (!tableExists($pdo, 'cashbook_entries')) {
            ok(['data' => [], 'closing_balance' => 0]);
        }
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $dir = $_GET['direction'] ?? null;
        $sql = "SELECT * FROM cashbook_entries WHERE 1=1";
        $p = [];
        if ($from) { $sql .= " AND entry_date>=?"; $p[] = $from; }
        if ($to) { $sql .= " AND entry_date<=?"; $p[] = $to; }
        if ($dir) { $sql .= " AND direction=?"; $p[] = $dir; }
        $sql .= " ORDER BY entry_date DESC, id DESC LIMIT 500";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($p);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Compute running balance
        $bal = 0;
        $rowsReversed = array_reverse($rows);
        foreach ($rowsReversed as &$r) { $bal += $r['direction']==='in' ? (float)$r['amount'] : -(float)$r['amount']; $r['running_balance'] = $bal; }
        ok(['data' => array_reverse($rowsReversed), 'closing_balance' => $bal]);
    }
    if ($action === 'add_money_in' && $method === 'POST') {
        $date = $_POST['entry_date'] ?? $today;
        $source = $_POST['money_source'] ?? 'other_in';
        $amount = (float)($_POST['amount'] ?? 0);
        $paid = $_POST['paid_through'] ?? 'cash';
        $customer = trim($_POST['customer_name'] ?? '');
        $ref = trim($_POST['reference_no'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($amount <= 0) err('Enter amount received');
        $pdo->prepare("INSERT INTO cashbook_entries (entry_date, direction, money_source, amount, paid_through, customer_name, reference_no, description, recorded_by) VALUES (?, 'in', ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$date, $source, $amount, $paid, $customer, $ref, $desc, $user_id]);
        logActivity($pdo, 'add', 'cashbook', "Money in KES {$amount} ($source)", (int)$pdo->lastInsertId(), 'cashbook_entry');
        ok(['message' => 'Money in recorded', 'id' => $pdo->lastInsertId()]);
    }
    if ($action === 'add_money_out' && $method === 'POST') {
        $date = $_POST['entry_date'] ?? $today;
        $source = $_POST['money_source'] ?? 'other_out';
        $amount = (float)($_POST['amount'] ?? 0);
        $paid = $_POST['paid_through'] ?? 'cash';
        $supplier = trim($_POST['supplier_name'] ?? '');
        $ref = trim($_POST['reference_no'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($amount <= 0) err('Enter amount paid');
        $pdo->prepare("INSERT INTO cashbook_entries (entry_date, direction, money_source, amount, paid_through, supplier_name, reference_no, description, recorded_by) VALUES (?, 'out', ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$date, $source, $amount, $paid, $supplier, $ref, $desc, $user_id]);
        logActivity($pdo, 'add', 'cashbook', "Money out KES {$amount} ($source)", (int)$pdo->lastInsertId(), 'cashbook_entry');
        ok(['message' => 'Money out recorded', 'id' => $pdo->lastInsertId()]);
    }
    if ($action === 'delete_cashbook' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM cashbook_entries WHERE id=?")->execute([$id]);
        logActivity($pdo, 'delete', 'cashbook', "Deleted cashbook entry #{$id}", $id, 'cashbook_entry');
        ok(['message' => 'Entry deleted']);
    }
    if ($action === 'cashbook_summary' && $method === 'GET') {
        $month = $_GET['month'] ?? date('Y-m');
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));
        $moneyIn = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM cashbook_entries WHERE direction='in' AND entry_date BETWEEN '$start' AND '$end'")->fetchColumn();
        $moneyOut = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM cashbook_entries WHERE direction='out' AND entry_date BETWEEN '$start' AND '$end'")->fetchColumn();
        $bySource = $pdo->query("SELECT money_source, SUM(amount) AS total FROM cashbook_entries WHERE direction='in' AND entry_date BETWEEN '$start' AND '$end' GROUP BY money_source")->fetchAll(PDO::FETCH_ASSOC);
        $byExpense = $pdo->query("SELECT money_source, SUM(amount) AS total FROM cashbook_entries WHERE direction='out' AND entry_date BETWEEN '$start' AND '$end' GROUP BY money_source")->fetchAll(PDO::FETCH_ASSOC);
        ok(['data' => [
            'month' => $month,
            'money_in' => $moneyIn,
            'money_out' => $moneyOut,
            'profit' => $moneyIn - $moneyOut,
            'income_breakdown' => $bySource,
            'expense_breakdown' => $byExpense,
        ]]);
    }

    // ─────────────────────────────────────────────────────────
    // CUSTOMER CREDIT
    // ─────────────────────────────────────────────────────────
    if ($action === 'list_credit') {
        if (!tableExists($pdo, 'customer_credits')) {
            ok(['data' => []]);
        }
        $status = $_GET['status'] ?? null;
        $sql = "SELECT c.*, (SELECT COALESCE(SUM(amount),0) FROM credit_payments WHERE credit_id=c.id) AS total_paid FROM customer_credits c WHERE 1=1";
        $p = [];
        if ($status) { $sql .= " AND c.status=?"; $p[] = $status; }
        $sql .= " ORDER BY c.credit_date DESC LIMIT 500";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($p);
        ok(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    if ($action === 'add_credit' && $method === 'POST') {
        $cust_id = (int)($_POST['customer_id'] ?? 0) ?: null;
        $name = trim($_POST['customer_name'] ?? '');
        $phone = trim($_POST['customer_phone'] ?? '');
        $date = $_POST['credit_date'] ?? $today;
        $due = $_POST['due_date'] ?? null;
        $item = trim($_POST['item_description'] ?? '');
        $total = (float)($_POST['total_amount'] ?? 0);
        $paid = (float)($_POST['amount_paid'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        if ($name === '' || $item === '' || $total <= 0) err('Customer, item, and amount are required');
        $balance = $total - $paid;
        $status = $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
        $pdo->prepare("INSERT INTO customer_credits (customer_id, customer_name, customer_phone, credit_date, due_date, item_description, total_amount, amount_paid, balance, status, notes, recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$cust_id, $name, $phone, $date, $due, $item, $total, $paid, $balance, $status, $notes, $user_id]);
        $id = (int)$pdo->lastInsertId();
        if ($paid > 0) {
            $pdo->prepare("INSERT INTO credit_payments (credit_id, payment_date, amount, paid_through, received_by, notes) VALUES (?,?,?,?,?,?)")
                ->execute([$id, $date, $paid, 'cash', $user_id, 'Initial payment']);
        }
        logActivity($pdo, 'add', 'credit', "Credit sale KES {$total} to {$name} (balance KES {$balance})", $id, 'customer_credit');
        ok(['message' => 'Credit saved', 'id' => $id, 'balance' => $balance]);
    }
    if ($action === 'add_credit_payment' && $method === 'POST') {
        $credit_id = (int)($_POST['credit_id'] ?? 0);
        $date = $_POST['payment_date'] ?? $today;
        $amount = (float)($_POST['amount'] ?? 0);
        $paid = $_POST['paid_through'] ?? 'cash';
        $ref = trim($_POST['reference_no'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if ($credit_id === 0 || $amount <= 0) err('Credit and amount required');
        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO credit_payments (credit_id, payment_date, amount, paid_through, reference_no, received_by, notes) VALUES (?,?,?,?,?,?,?)")
                ->execute([$credit_id, $date, $amount, $paid, $ref, $user_id, $notes]);
            $pdo->prepare("UPDATE customer_credits SET amount_paid = amount_paid + ?, last_payment_date = ?, status = CASE WHEN amount_paid + ? >= total_amount THEN 'paid' ELSE 'partial' END WHERE id=?")
                ->execute([$amount, $date, $amount, $credit_id]);
            // Add to cashbook
            $cust = $pdo->query("SELECT customer_name FROM customer_credits WHERE id=$credit_id")->fetch(PDO::FETCH_ASSOC);
            $pdo->prepare("INSERT INTO cashbook_entries (entry_date, direction, money_source, amount, paid_through, customer_name, reference_no, description, recorded_by) VALUES (?, 'in', 'credit_payment', ?, ?, ?, ?, ?, ?)")
                ->execute([$date, $amount, $paid, $cust['customer_name'] ?? '', $ref, "Credit payment #$credit_id", $user_id]);
            $pdo->commit();
            logActivity($pdo, 'add', 'credit', "Credit payment KES {$amount} on credit #{$credit_id}", $credit_id, 'credit_payment');
            ok(['message' => 'Payment recorded']);
        } catch (Exception $e) { $pdo->rollBack(); err('Failed: ' . $e->getMessage()); }
    }
    if ($action === 'credit_summary' && $method === 'GET') {
        if (!tableExists($pdo, 'customer_credits')) {
            ok(['data' => ['total_owed' => 0, 'overdue' => 0, 'top_debtors' => []]]);
        }
        $totalOwed = (float)$pdo->query("SELECT COALESCE(SUM(balance),0) FROM customer_credits WHERE status IN ('unpaid','partial')")->fetchColumn();
        $overdue = (float)$pdo->query("SELECT COALESCE(SUM(balance),0) FROM customer_credits WHERE status IN ('unpaid','partial') AND due_date < CURDATE()")->fetchColumn();
        $byCustomer = $pdo->query("SELECT customer_name, SUM(balance) AS total FROM customer_credits WHERE status IN ('unpaid','partial') GROUP BY customer_name ORDER BY total DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        ok(['data' => ['total_owed' => $totalOwed, 'overdue' => $overdue, 'top_debtors' => $byCustomer]]);
    }

    // ─────────────────────────────────────────────────────────
    // FEEDING PROGRAM
    // ─────────────────────────────────────────────────────────
    if ($action === 'list_feeding_standards') {
        $rows = $pdo->query("SELECT * FROM feeding_standards ORDER BY bird_type, week_number")->fetchAll(PDO::FETCH_ASSOC);
        ok(['data' => $rows]);
    }
    if ($action === 'save_feeding_standard' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $type = $_POST['bird_type'] ?? 'layer';
        $week = (int)($_POST['week_number'] ?? 0);
        $g = (float)($_POST['feed_per_bird_per_day_grams'] ?? 0);
        $feed = trim($_POST['feed_type'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if ($week === 0 || $g === 0) err('Week and grams required');
        if ($id > 0) {
            $pdo->prepare("UPDATE feeding_standards SET bird_type=?, week_number=?, feed_per_bird_per_day_grams=?, feed_type=?, notes=? WHERE id=?")
                ->execute([$type, $week, $g, $feed, $notes, $id]);
        } else {
            $pdo->prepare("INSERT INTO feeding_standards (bird_type, week_number, feed_per_bird_per_day_grams, feed_type, notes) VALUES (?,?,?,?,?)")
                ->execute([$type, $week, $g, $feed, $notes]);
        }
        logActivity($pdo, 'save', 'feeding', "Feeding standard: {$type} week {$week} — {$g}g/bird/day");
        ok(['message' => 'Standard saved']);
    }
    if ($action === 'list_feed_allocations') {
        $batch = (int)($_GET['batch_id'] ?? 0);
        $sql = "SELECT a.*, b.batch_name, b.batch_code, b.batch_type FROM feed_allocations a
                LEFT JOIN batches b ON b.id=a.batch_id WHERE 1=1";
        $p = [];
        if ($batch) { $sql .= " AND a.batch_id=?"; $p[] = $batch; }
        $sql .= " ORDER BY a.allocation_date DESC LIMIT 500";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($p);
        ok(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    if ($action === 'add_feed_allocation' && $method === 'POST') {
        $batch_id = (int)($_POST['batch_id'] ?? 0);
        $date = $_POST['allocation_date'] ?? $today;
        $feed = trim($_POST['feed_type'] ?? '');
        $kg = (float)($_POST['kg_fed'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        if ($batch_id === 0 || $kg <= 0) err('Batch and kg required');
        $pdo->prepare("INSERT INTO feed_allocations (batch_id, allocation_date, feed_type, kg_fed, notes, recorded_by) VALUES (?,?,?,?,?,?)")
            ->execute([$batch_id, $date, $feed, $kg, $notes, $user_id]);
        logActivity($pdo, 'add', 'feeding', "Feed allocation: {$kg}kg {$feed} to batch #{$batch_id}", $batch_id, 'batch');
        ok(['message' => 'Feed allocation saved']);
    }
    if ($action === 'fcr_report' && $method === 'GET') {
        $batch_id = (int)($_GET['batch_id'] ?? 0);
        $batch = $pdo->query("SELECT * FROM batches WHERE id=$batch_id")->fetch(PDO::FETCH_ASSOC);
        if (!$batch) err('Batch not found');
        $totalFeed = (float)$pdo->query("SELECT COALESCE(SUM(kg_fed),0) FROM feed_allocations WHERE batch_id=$batch_id")->fetchColumn();
        $totalEggs = (int)$pdo->query("SELECT COALESCE(SUM(total_eggs),0) FROM daily_batch_records WHERE batch_id=$batch_id")->fetchColumn();
        $mortality = (int)$pdo->query("SELECT COALESCE(SUM(mortality),0) FROM daily_batch_records WHERE batch_id=$batch_id")->fetchColumn();
        $avgWeight = (float)$pdo->query("SELECT COALESCE(AVG(average_weight_kg),0) FROM daily_batch_records WHERE batch_id=$batch_id")->fetchColumn();
        $totalMeatKg = $avgWeight * max(0, $batch['initial_birds'] - $mortality);
        $fcr = $totalMeatKg > 0 ? $totalFeed / $totalMeatKg : 0;
        $eggsPerFeed = $totalFeed > 0 ? $totalEggs / $totalFeed : 0;
        ok(['data' => [
            'batch' => $batch,
            'total_feed_kg' => $totalFeed,
            'total_eggs' => $totalEggs,
            'total_mortality' => $mortality,
            'avg_weight_kg' => $avgWeight,
            'total_meat_kg' => $totalMeatKg,
            'fcr' => round($fcr, 2),
            'eggs_per_kg_feed' => round($eggsPerFeed, 2),
        ]]);
    }

    // ─────────────────────────────────────────────────────────
    // PURCHASE ORDERS
    // ─────────────────────────────────────────────────────────
    if ($action === 'list_purchase_orders') {
        if (!tableExists($pdo, 'purchase_orders')) {
            ok(['data' => []]);
        }
        $sql = "SELECT po.*, s.supplier_name, s.phone AS supplier_phone,
                       (SELECT COUNT(*) FROM purchase_order_items WHERE po_id=po.id) AS item_count
                FROM purchase_orders po
                LEFT JOIN suppliers s ON s.id=po.supplier_id
                ORDER BY po.order_date DESC LIMIT 200";
        ok(['data' => $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC)]);
    }
    if ($action === 'get_purchase_order') {
        $id = (int)($_GET['id'] ?? 0);
        $po = $pdo->query("SELECT po.*, s.supplier_name, s.phone AS supplier_phone, s.email AS supplier_email, s.address AS supplier_address FROM purchase_orders po LEFT JOIN suppliers s ON s.id=po.supplier_id WHERE po.id=$id")->fetch(PDO::FETCH_ASSOC);
        if (!$po) err('Not found');
        $items = $pdo->query("SELECT pi.*, rm.material_name, rm.unit AS stock_unit FROM purchase_order_items pi LEFT JOIN raw_materials rm ON rm.id=pi.material_id WHERE pi.po_id=$id")->fetchAll(PDO::FETCH_ASSOC);
        $po['items'] = $items;
        ok(['data' => $po]);
    }
    if ($action === 'create_po' && $method === 'POST') {
        $pdo->beginTransaction();
        try {
            $id = (int)($_POST['id'] ?? 0);
            $supplier = (int)($_POST['supplier_id'] ?? 0);
            $date = $_POST['order_date'] ?? $today;
            $expected = $_POST['expected_delivery'] ?? null;
            $status = $_POST['status'] ?? 'draft';
            $notes = trim($_POST['notes'] ?? '');
            $items = json_decode($_POST['items'] ?? '[]', true);
            if ($supplier === 0) err('Choose a supplier');
            if (!is_array($items) || count($items) === 0) err('Add at least one item');
            $total = 0;
            foreach ($items as $i) $total += (float)$i['quantity'] * (float)$i['unit_price'];
            if ($id > 0) {
                $pdo->prepare("UPDATE purchase_orders SET supplier_id=?, order_date=?, expected_delivery=?, status=?, total_amount=?, notes=? WHERE id=?")
                    ->execute([$supplier, $date, $expected, $status, $total, $notes, $id]);
                $pdo->prepare("DELETE FROM purchase_order_items WHERE po_id=?")->execute([$id]);
            } else {
                $num = 'PO' . date('Ymd') . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                $pdo->prepare("INSERT INTO purchase_orders (po_number, supplier_id, order_date, expected_delivery, status, total_amount, notes, created_by) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$num, $supplier, $date, $expected, $status, $total, $notes, $user_id]);
                $id = (int)$pdo->lastInsertId();
            }
            $ins = $pdo->prepare("INSERT INTO purchase_order_items (po_id, material_id, quantity, unit, unit_price, line_total) VALUES (?,?,?,?,?,?)");
            foreach ($items as $i) {
                $q = (float)$i['quantity'];
                $p = (float)$i['unit_price'];
                $ins->execute([$id, (int)$i['material_id'], $q, $i['unit'] ?? 'kg', $p, $q*$p]);
            }
            $pdo->commit();
            logActivity($pdo, 'save', 'purchase_orders', "Purchase order saved (KES {$total})", $id, 'purchase_order');
            ok(['message' => 'Purchase order saved', 'id' => $id, 'po_number' => $id > 0 ? null : null]);
        } catch (Exception $e) { $pdo->rollBack(); err('Save failed: ' . $e->getMessage()); }
    }
    if ($action === 'receive_po' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->beginTransaction();
        try {
            $items = $pdo->query("SELECT * FROM purchase_order_items WHERE po_id=$id")->fetchAll(PDO::FETCH_ASSOC);
            $supplier = $pdo->query("SELECT s.supplier_name, po.total_amount, po.po_number FROM purchase_orders po LEFT JOIN suppliers s ON s.id=po.supplier_id WHERE po.id=$id")->fetch(PDO::FETCH_ASSOC);
            foreach ($items as $it) {
                $qty = (float)$it['quantity'];
                $unitCost = (float)$it['unit_price'];
                $sel = $pdo->prepare("SELECT current_stock FROM raw_materials WHERE id=?");
                $sel->execute([(int)$it['material_id']]);
                $cur = (float)$sel->fetchColumn();
                $newBal = $cur + $qty;
                $pdo->prepare("UPDATE raw_materials SET current_stock=?, current_price_per_unit=? WHERE id=?")
                    ->execute([$newBal, $unitCost, (int)$it['material_id']]);
                $pdo->prepare("INSERT INTO raw_material_movements (movement_date, material_id, movement_type, quantity_kg, balance_after, unit_cost, total_cost, reference_no, description, recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$today, (int)$it['material_id'], 'received', $qty, $newBal, $unitCost, $qty*$unitCost, $supplier['po_number'], "Received from PO #$id", $user_id]);
            }
            $pdo->prepare("UPDATE purchase_orders SET status='received', received_date=CURDATE() WHERE id=?")->execute([$id]);
            // Cashbook entry
            $pdo->prepare("INSERT INTO cashbook_entries (entry_date, direction, money_source, amount, paid_through, supplier_name, reference_no, description, recorded_by) VALUES (?, 'out', 'raw_material_purchase', ?, ?, ?, ?, ?, ?)")
                ->execute([$today, $supplier['total_amount'], 'cash', $supplier['supplier_name'], $supplier['po_number'], "PO #$id received", $user_id]);
            $pdo->commit();
            logActivity($pdo, 'receive', 'purchase_orders', "PO #{$id} received, stock updated", $id, 'purchase_order');
            ok(['message' => 'PO received and stock updated']);
        } catch (Exception $e) { $pdo->rollBack(); err('Receive failed: ' . $e->getMessage()); }
    }
    if ($action === 'list_suppliers') {
        ok(['data' => $pdo->query("SELECT * FROM suppliers ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC)]);
    }
    if ($action === 'save_supplier' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['supplier_name'] ?? '');
        $contact = trim($_POST['contact_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $lead = (int)($_POST['lead_time_days'] ?? 5);
        $notes = trim($_POST['notes'] ?? '');
        if ($name === '') err('Supplier name required');
        if ($id > 0) {
            $pdo->prepare("UPDATE suppliers SET supplier_name=?, contact_name=?, phone=?, email=?, address=?, lead_time_days=?, notes=? WHERE id=?")
                ->execute([$name, $contact, $phone, $email, $address, $lead, $notes, $id]);
        } else {
            $pdo->prepare("INSERT INTO suppliers (supplier_name, contact_name, phone, email, address, lead_time_days, notes) VALUES (?,?,?,?,?,?,?)")
                ->execute([$name, $contact, $phone, $email, $address, $lead, $notes]);
            $id = (int)$pdo->lastInsertId();
        }
        logActivity($pdo, 'save', 'suppliers', "Supplier: {$name}", $id > 0 ? $id : null, 'supplier');
        ok(['message' => 'Supplier saved']);
    }

    // ─────────────────────────────────────────────────────────
    // BROILER WEIGH-INS
    // ─────────────────────────────────────────────────────────
    if ($action === 'list_weighings') {
        $batch = (int)($_GET['batch_id'] ?? 0);
        $sql = "SELECT w.*, b.batch_name, b.batch_code FROM broiler_weighings w LEFT JOIN batches b ON b.id=w.batch_id WHERE 1=1";
        $p = [];
        if ($batch) { $sql .= " AND w.batch_id=?"; $p[] = $batch; }
        $sql .= " ORDER BY w.weigh_date ASC, w.day_number ASC LIMIT 200";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($p);
        ok(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    if ($action === 'add_weighing' && $method === 'POST') {
        $batch_id = (int)($_POST['batch_id'] ?? 0);
        $date = $_POST['weigh_date'] ?? $today;
        $day = (int)($_POST['day_number'] ?? 0);
        $sample = (int)($_POST['sample_size'] ?? 10);
        $avg = (float)($_POST['avg_weight_kg'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        if ($batch_id === 0 || $day === 0 || $avg === 0) err('Batch, day, and weight required');
        $pdo->prepare("INSERT INTO broiler_weighings (batch_id, weigh_date, day_number, sample_size, avg_weight_kg, notes, recorded_by) VALUES (?,?,?,?,?,?,?)")
            ->execute([$batch_id, $date, $day, $sample, $avg, $notes, $user_id]);
        logActivity($pdo, 'add', 'broiler', "Weighing batch #{$batch_id} day {$day}: {$avg}kg avg", $batch_id, 'batch');
        ok(['message' => 'Weighing recorded']);
    }

    // ─────────────────────────────────────────────────────────
    // HATCHERY
    // ─────────────────────────────────────────────────────────
    if ($action === 'list_hatchery') {
        if (!tableExists($pdo, 'hatchery_batches')) {
            ok(['data' => []]);
        }
        ok(['data' => $pdo->query("SELECT * FROM hatchery_batches ORDER BY setting_date DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC)]);
    }
    if ($action === 'add_hatch' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $set = $_POST['setting_date'] ?? $today;
        $expected = $_POST['expected_hatch_date'] ?? $today;
        $actual = $_POST['actual_hatch_date'] ?? null;
        $breed = trim($_POST['breed'] ?? '');
        $eggs = (int)($_POST['eggs_set'] ?? 0);
        $fertile = (int)($_POST['fertile_eggs'] ?? 0);
        $hatched = (int)($_POST['chicks_hatched'] ?? 0);
        $dest = $_POST['destination'] ?? 'own_farm';
        $cost = (float)($_POST['cost_per_doc'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $hatchPct = $fertile > 0 ? round(($hatched / $fertile) * 100, 2) : 0;
        if ($id > 0) {
            $pdo->prepare("UPDATE hatchery_batches SET setting_date=?, expected_hatch_date=?, actual_hatch_date=?, breed=?, eggs_set=?, fertile_eggs=?, chicks_hatched=?, hatchability_pct=?, destination=?, cost_per_doc=?, notes=? WHERE id=?")
                ->execute([$set, $expected, $actual, $breed, $eggs, $fertile, $hatched, $hatchPct, $dest, $cost, $notes, $id]);
        } else {
            $pdo->prepare("INSERT INTO hatchery_batches (setting_date, expected_hatch_date, actual_hatch_date, breed, eggs_set, fertile_eggs, chicks_hatched, hatchability_pct, destination, cost_per_doc, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$set, $expected, $actual, $breed, $eggs, $fertile, $hatched, $hatchPct, $dest, $cost, $notes]);
            $id = (int)$pdo->lastInsertId();
        }
        logActivity($pdo, 'save', 'hatchery', "Hatchery batch: {$eggs} eggs set, {$hatched} hatched", $id, 'hatchery_batch');
        ok(['message' => 'Hatchery record saved', 'id' => $id]);
    }

    // ─────────────────────────────────────────────────────────
    // EGG LOSSES
    // ─────────────────────────────────────────────────────────
    if ($action === 'list_egg_losses') {
        $sql = "SELECT l.*, b.batch_name FROM egg_losses l LEFT JOIN batches b ON b.id=l.batch_id ORDER BY l.loss_date DESC LIMIT 500";
        ok(['data' => $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC)]);
    }
    if ($action === 'add_egg_loss' && $method === 'POST') {
        $date = $_POST['loss_date'] ?? $today;
        $batch = (int)($_POST['batch_id'] ?? 0) ?: null;
        $type = $_POST['loss_type'] ?? 'broken';
        $stage = $_POST['stage'] ?? 'collection';
        if (!in_array($stage, ['collection', 'transport', 'storage', 'other'], true)) $stage = 'collection';
        $qty = (int)($_POST['quantity'] ?? 0);
        $value = (float)($_POST['estimated_value'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        if (!columnExists($pdo, 'egg_losses', 'stage')) {
            $stage = 'collection'; // very old DB before the auto-migrate ran
        }
        $pdo->prepare("INSERT INTO egg_losses (loss_date, batch_id, loss_type, stage, quantity, estimated_value, reason, recorded_by) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$date, $batch, $type, $stage, $qty, $value, $reason, $user_id]);
        logActivity($pdo, 'add', 'egg_grading', "Egg loss: {$qty} {$type} during {$stage} (KES {$value})", $batch, 'batch');
        ok(['message' => 'Loss recorded']);
    }

    // ─────────────────────────────────────────────────────────
    // QUALITY TESTS
    // ─────────────────────────────────────────────────────────
    if ($action === 'list_quality_tests') {
        $sql = "SELECT q.*, r.material_name FROM quality_tests q LEFT JOIN raw_materials r ON r.id=q.material_id ORDER BY q.test_date DESC LIMIT 200";
        ok(['data' => $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC)]);
    }
    if ($action === 'add_quality_test' && $method === 'POST') {
        $mat = (int)($_POST['material_id'] ?? 0);
        $date = $_POST['test_date'] ?? $today;
        $type = $_POST['test_type'] ?? 'visual';
        $val = trim($_POST['result_value'] ?? '');
        $unit = trim($_POST['unit'] ?? '');
        $pf = $_POST['pass_fail'] ?? 'pass';
        $tester = trim($_POST['tested_by'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if ($mat === 0) err('Material required');
        $pdo->prepare("INSERT INTO quality_tests (material_id, test_date, test_type, result_value, unit, pass_fail, tested_by, notes) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$mat, $date, $type, $val, $unit, $pf, $tester, $notes]);
        logActivity($pdo, 'add', 'quality', "Quality test on material #{$mat}: {$pf}", $mat, 'raw_material');
        ok(['message' => 'Test recorded']);
    }

    // ─────────────────────────────────────────────────────────
    // LPO DOCUMENTS — Quotations, Local Purchase Orders & Invoices
    // ─────────────────────────────────────────────────────────
    if ($action === 'list_lpo_documents') {
        if (!tableExists($pdo, 'lpo_documents')) err('LPO module not ready yet — refresh and try again', 500);
        $type = $_GET['type'] ?? '';
        $q = "SELECT d.*, u.username AS created_by_name FROM lpo_documents d LEFT JOIN users u ON u.id=d.created_by WHERE 1=1";
        $p = [];
        if (in_array($type, ['quotation', 'lpo', 'invoice'], true)) { $q .= ' AND d.doc_type=?'; $p[] = $type; }
        $q .= ' ORDER BY d.issue_date DESC, d.id DESC LIMIT 500';
        $stmt = $pdo->prepare($q);
        $stmt->execute($p);
        ok(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    if ($action === 'get_lpo_document') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) err('Document id required');
        $doc = $pdo->prepare('SELECT * FROM lpo_documents WHERE id=?');
        $doc->execute([$id]);
        $d = $doc->fetch(PDO::FETCH_ASSOC);
        if (!$d) err('Document not found', 404);
        $items = $pdo->prepare('SELECT * FROM lpo_items WHERE doc_id=? ORDER BY id');
        $items->execute([$id]);
        $d['items'] = $items->fetchAll(PDO::FETCH_ASSOC);
        ok(['data' => $d]);
    }
    if ($action === 'save_lpo_document' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $docType = $_POST['doc_type'] ?? 'quotation';
        if (!in_array($docType, ['quotation', 'lpo', 'invoice'], true)) err('Invalid document type');
        $status = $_POST['status'] ?? 'draft';
        if (!in_array($status, ['draft', 'sent', 'accepted', 'invoiced', 'paid', 'cancelled'], true)) err('Invalid status');
        $custName = trim($_POST['customer_name'] ?? '');
        if ($custName === '') err('Customer name is required');
        $issueDate = $_POST['issue_date'] ?? $today;
        $dueDate = trim($_POST['due_date'] ?? '') ?: null;
        $taxRate = (float)($_POST['tax_rate'] ?? 0);
        $discount = (float)($_POST['discount'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        // Items: JSON array [{description, quantity, unit, unit_price}]
        $itemsJson = $_POST['items'] ?? '[]';
        $items = json_decode($itemsJson, true);
        if (!is_array($items)) err('Invalid items');
        $items = array_values(array_filter($items, fn($it) => trim((string)($it['description'] ?? '')) !== '' && (float)($it['quantity'] ?? 0) > 0));
        if (empty($items)) err('Add at least one item');

        $subtotal = 0.0;
        foreach ($items as &$it) {
            $it['quantity'] = (float)($it['quantity'] ?? 1);
            $it['unit_price'] = (float)($it['unit_price'] ?? 0);
            $it['line_total'] = round($it['quantity'] * $it['unit_price'], 2);
            $subtotal += $it['line_total'];
        }
        unset($it);
        $taxAmount = round($subtotal * $taxRate / 100, 2);
        $total = round($subtotal + $taxAmount - $discount, 2);

        if ($id > 0) {
            $pdo->prepare('UPDATE lpo_documents SET doc_type=?, status=?, customer_name=?, customer_phone=?, customer_email=?, customer_address=?, issue_date=?, due_date=?, subtotal=?, tax_rate=?, tax_amount=?, discount=?, total_amount=?, notes=? WHERE id=?')
                ->execute([$docType, $status, $custName, trim($_POST['customer_phone'] ?? ''), trim($_POST['customer_email'] ?? ''), trim($_POST['customer_address'] ?? ''), $issueDate, $dueDate, $subtotal, $taxRate, $taxAmount, $discount, $total, $notes, $id]);
            $pdo->prepare('DELETE FROM lpo_items WHERE doc_id=?')->execute([$id]);
        } else {
            $prefix = ['quotation' => 'QT', 'lpo' => 'LPO', 'invoice' => 'INV'][$docType];
            $docNumber = $prefix . '-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));
            $pdo->prepare('INSERT INTO lpo_documents (doc_number, doc_type, status, customer_name, customer_phone, customer_email, customer_address, issue_date, due_date, subtotal, tax_rate, tax_amount, discount, total_amount, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$docNumber, $docType, $status, $custName, trim($_POST['customer_phone'] ?? ''), trim($_POST['customer_email'] ?? ''), trim($_POST['customer_address'] ?? ''), $issueDate, $dueDate, $subtotal, $taxRate, $taxAmount, $discount, $total, $notes, $user_id]);
            $id = (int)$pdo->lastInsertId();
        }

        $ins = $pdo->prepare('INSERT INTO lpo_items (doc_id, description, quantity, unit, unit_price, line_total) VALUES (?,?,?,?,?,?)');
        foreach ($items as $it) {
            $ins->execute([$id, trim($it['description']), $it['quantity'], trim($it['unit'] ?? 'pcs') ?: 'pcs', $it['unit_price'], $it['line_total']]);
        }
        logActivity($pdo, 'save', 'lpo', "{$docType} document for {$custName} saved (KES {$total})", $id, 'lpo_document');
        ok(['message' => 'Document saved', 'id' => $id]);
    }
    if ($action === 'set_lpo_status' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['draft', 'sent', 'accepted', 'invoiced', 'paid', 'cancelled'], true)) err('Invalid status');
        $pdo->prepare('UPDATE lpo_documents SET status=? WHERE id=?')->execute([$status, $id]);
        logActivity($pdo, 'update', 'lpo', "LPO document #{$id} marked as {$status}", $id, 'lpo_document');
        ok(['message' => 'Status updated']);
    }
    if ($action === 'delete_lpo_document' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) err('Invalid id');
        $pdo->prepare('DELETE FROM lpo_documents WHERE id=?')->execute([$id]); // items cascade
        logActivity($pdo, 'delete', 'lpo', "LPO document #{$id} deleted", null, 'lpo_document');
        ok(['message' => 'Document deleted']);
    }

    // ─────────────────────────────────────────────────────────
    // ALERTS
    // ─────────────────────────────────────────────────────────
    if ($action === 'list_alerts') {
        $sql = "SELECT * FROM system_alerts WHERE is_dismissed=0 ORDER BY alert_date DESC LIMIT 50";
        ok(['data' => $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC)]);
    }
    if ($action === 'mark_alert_read' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE system_alerts SET is_read=1 WHERE id=?")->execute([$id]);
        ok(['message' => 'OK']);
    }
    if ($action === 'dismiss_alert' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE system_alerts SET is_dismissed=1, dismissed_by=?, dismissed_at=NOW() WHERE id=?")->execute([$user_id, $id]);
        ok(['message' => 'OK']);
    }

    // ─────────────────────────────────────────────────────────
    // DASHBOARD OVERVIEW (simple, plain numbers)
    // ─────────────────────────────────────────────────────────
    if ($action === 'dashboard_overview') {
        $todayIn = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM cashbook_entries WHERE direction='in' AND entry_date=CURDATE()")->fetchColumn();
        $todayOut = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM cashbook_entries WHERE direction='out' AND entry_date=CURDATE()")->fetchColumn();
        $totalOwed = (float)$pdo->query("SELECT COALESCE(SUM(balance),0) FROM customer_credits WHERE status IN ('unpaid','partial')")->fetchColumn();
        $overdue = (float)$pdo->query("SELECT COALESCE(SUM(balance),0) FROM customer_credits WHERE status IN ('unpaid','partial') AND due_date < CURDATE()")->fetchColumn();
        $eggsToday = (int)$pdo->query("SELECT COALESCE(SUM(total_eggs),0) FROM daily_batch_records WHERE record_date=CURDATE()")->fetchColumn();
        $mortalityToday = (int)$pdo->query("SELECT COALESCE(SUM(mortality),0) FROM daily_batch_records WHERE record_date=CURDATE()")->fetchColumn();
        $lowStock = (int)$pdo->query("SELECT COUNT(*) FROM raw_materials WHERE current_stock <= min_stock_level")->fetchColumn();
        $pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','paid')")->fetchColumn();
        $activeBatches = (int)$pdo->query("SELECT COUNT(*) FROM batches WHERE status='active'")->fetchColumn();
        $totalBirds = (int)$pdo->query("SELECT COALESCE(SUM(current_birds),0) FROM batches WHERE status='active'")->fetchColumn();
        $alerts = (int)$pdo->query("SELECT COUNT(*) FROM system_alerts WHERE is_dismissed=0")->fetchColumn();
        $hatchesThisWeek = (int)$pdo->query("SELECT COUNT(*) FROM hatchery_batches WHERE actual_hatch_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()")->fetchColumn();
        ok(['data' => [
            'today_in' => $todayIn,
            'today_out' => $todayOut,
            'today_profit' => $todayIn - $todayOut,
            'total_owed' => $totalOwed,
            'overdue' => $overdue,
            'eggs_today' => $eggsToday,
            'mortality_today' => $mortalityToday,
            'low_stock_items' => $lowStock,
            'pending_orders' => $pendingOrders,
            'active_batches' => $activeBatches,
            'total_birds' => $totalBirds,
            'alerts' => $alerts,
            'hatches_this_week' => $hatchesThisWeek,
        ]]);
    }

    err('Unknown action: ' . htmlspecialchars($action), 400);
} catch (Exception $e) {
    err('Server error: ' . $e->getMessage(), 500);
}
