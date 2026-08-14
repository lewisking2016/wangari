<?php
/**
 * Backend API — Unified CSV Export
 * Exports any module's data to a CSV file matching the spreadsheet format
 * the system was designed around.
 *
 * Usage: /Backend/api/export.php?module=<module>[&from=YYYY-MM-DD&to=YYYY-MM-DD]
 *
 * Modules: orders, daily_sales, daily_sales_lines, bulk_sales, stores_movements,
 *          health, batches, daily_records, egg_grades, feed_production,
 *          flocks, customers, raw_materials
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

require_once __DIR__ . '/../config/database.php';

// Auth: admin only
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager', 'stock_manager'], true)) {
    http_response_code(401);
    die('Unauthorized');
}

$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    die('Database connection failed');
}

$module = $_GET['module'] ?? '';
$from   = $_GET['from']   ?? null;
$to     = $_GET['to']     ?? null;
$today  = date('Y-m-d');

function csv_send(string $filename, array $headers, array $rows): never {
    // Clean any output buffer
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    // UTF-8 BOM for Excel
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $r) fputcsv($out, array_map('csv_safe', $r));
    fclose($out);
    exit;
}

function csv_safe($v): string {
    if ($v === null) return '';
    $s = (string)$v;
    // Prevent CSV formula injection
    if (strlen($s) && in_array($s[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        $s = "'" . $s;
    }
    return $s;
}

try {
    switch ($module) {

        // ─────────────────────────────────────────────────────────
        // ORDERS — matches "busia_orders_report_YYYY-MM-DD.csv"
        // ─────────────────────────────────────────────────────────
        case 'orders':
            $sql = "SELECT o.id, o.order_number,
                           CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS customer,
                           u.email, o.total_amount, o.status, o.created_at
                    FROM orders o LEFT JOIN users u ON u.id=o.user_id WHERE 1=1";
            $p = [];
            if ($from) { $sql .= " AND DATE(o.created_at) >= ?"; $p[] = $from; }
            if ($to)   { $sql .= " AND DATE(o.created_at) <= ?"; $p[] = $to; }
            $sql .= " ORDER BY o.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($p);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'Order ID'     => $r['id'],
                    'Order Number' => $r['order_number'],
                    'Customer'     => $r['customer'] ?: 'Guest',
                    'Email'        => $r['email'],
                    'Amount'       => $r['total_amount'],
                    'Status'       => $r['status'],
                    'Date'         => $r['created_at'],
                ];
            }, $rows);
            csv_send('wangari_orders_report_' . $today . '.csv',
                ['Order ID', 'Order Number', 'Customer', 'Email', 'Amount', 'Status', 'Date'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // DAILY SALES RECONCILIATION — mirrors "BATCH 16 DAILY SALES"
        // ─────────────────────────────────────────────────────────
        case 'daily_sales':
            $sql = "SELECT * FROM daily_sales_reconciliation WHERE 1=1";
            $p = [];
            if ($from) { $sql .= " AND sale_date >= ?"; $p[] = $from; }
            if ($to)   { $sql .= " AND sale_date <= ?"; $p[] = $to; }
            $sql .= " ORDER BY sale_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($p);
            $headers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($headers as $h) {
                $lines = $pdo->prepare("SELECT * FROM daily_sales_lines WHERE reconciliation_id=? ORDER BY unit_price DESC");
                $lines->execute([$h['id']]);
                $lineArr = $lines->fetchAll(PDO::FETCH_ASSOC);
                // Format: each day shows the per-tier breakdown as one row
                $tierTxt = [];
                foreach ($lineArr as $l) {
                    $tierTxt[] = $l['quantity_crates'] . ' × KES ' . $l['unit_price'] . ' (' . $l['product_type'] . ') = KES ' . number_format($l['line_total'], 2);
                }
                $out[] = [
                    'Date'             => $h['sale_date'],
                    'Opening Balance'  => $h['opening_balance_crates'],
                    'Total Production' => $h['total_production_crates'],
                    'Total Sold'       => $h['total_sold_crates'],
                    'Closing Balance'  => $h['closing_balance_crates'],
                    'Total Sales'      => $h['total_sales_amount'],
                    'Total Eggs'       => $h['total_eggs'],
                    'Sales Lines'      => implode(' | ', $tierTxt),
                    'Notes'            => $h['notes'],
                ];
            }
            csv_send('daily_sales_reconciliation_' . $today . '.csv',
                ['Date','Opening Balance','Total Production','Total Sold','Closing Balance','Total Sales','Total Eggs','Sales Lines','Notes'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // BULK SALES — selling point / walk-in
        // ─────────────────────────────────────────────────────────
        case 'bulk_sales':
            $sql = "SELECT * FROM bulk_sales WHERE 1=1";
            $p = [];
            if ($from) { $sql .= " AND sale_date >= ?"; $p[] = $from; }
            if ($to)   { $sql .= " AND sale_date <= ?"; $p[] = $to; }
            $sql .= " ORDER BY sale_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($p);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'Sale Date'      => $r['sale_date'],
                    'Sale Number'    => $r['sale_number'],
                    'Customer'       => $r['customer_name'],
                    'Phone'          => $r['customer_phone'],
                    'Product'        => $r['product_type'],
                    'Quantity'       => $r['quantity'],
                    'Unit'           => $r['unit'],
                    'Unit Price'     => $r['unit_price'],
                    'Total'          => $r['total_amount'],
                    'Paid'           => $r['amount_paid'],
                    'Balance'        => $r['balance'],
                    'Payment Method' => $r['payment_method'],
                    'Status'         => $r['payment_status'],
                    'Notes'          => $r['notes'],
                ];
            }, $rows);
            csv_send('bulk_sales_' . $today . '.csv',
                ['Sale Date','Sale Number','Customer','Phone','Product','Quantity','Unit','Unit Price','Total','Paid','Balance','Payment Method','Status','Notes'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // STORES / MOVEMENTS — mirrors "STORES TRACKING 2026"
        // ─────────────────────────────────────────────────────────
        case 'stores_movements':
            $hasCategory = columnExists($pdo, 'raw_materials', 'category');
            $sql = "SELECT m.movement_date, m.movement_type, m.quantity_kg, m.balance_after,
                           m.unit_cost, m.total_cost, m.reference_no, m.description,
                           r.material_name, r.material_code, r.unit" . ($hasCategory ? ", r.category" : "") . "
                    FROM raw_material_movements m
                    LEFT JOIN raw_materials r ON r.id=m.material_id
                    WHERE 1=1";
            $p = [];
            if ($from) { $sql .= " AND m.movement_date >= ?"; $p[] = $from; }
            if ($to)   { $sql .= " AND m.movement_date <= ?"; $p[] = $to; }
            $sql .= " ORDER BY m.movement_date DESC, m.id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($p);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$hasCategory) {
                foreach ($rows as &$r) { $r['category'] = 'feed_ingredient'; }
                unset($r);
            }
            $out = array_map(function($r) {
                return [
                    'Date'          => $r['movement_date'],
                    'Material'      => $r['material_name'],
                    'Code'          => $r['material_code'],
                    'Category'      => $r['category'],
                    'Type'          => $r['movement_type'],
                    'Quantity'      => $r['quantity_kg'],
                    'Unit'          => $r['unit'],
                    'Balance After' => $r['balance_after'],
                    'Unit Cost'     => $r['unit_cost'],
                    'Total Cost'    => $r['total_cost'],
                    'Reference'     => $r['reference_no'],
                    'Description'   => $r['description'],
                ];
            }, $rows);
            csv_send('stores_movements_' . $today . '.csv',
                ['Date','Material','Code','Category','Type','Quantity','Unit','Balance After','Unit Cost','Total Cost','Reference','Description'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // RAW MATERIALS MASTER (current stock snapshot)
        // ─────────────────────────────────────────────────────────
        case 'raw_materials':
            try {
                $rows = $pdo->query("SELECT * FROM raw_materials ORDER BY category, material_name")->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                try {
                    $rows = $pdo->query("SELECT * FROM raw_materials ORDER BY material_name")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($rows as &$r) { if (!isset($r['category'])) $r['category'] = 'feed_ingredient'; }
                } catch (Exception $e2) { $rows = []; }
            }
            $out = array_map(function($r) {
                return [
                    'Material'        => $r['material_name'],
                    'Code'            => $r['material_code'],
                    'Category'        => $r['category'],
                    'Unit'            => $r['unit'],
                    'Opening Balance' => $r['opening_balance'],
                    'Current Stock'   => $r['current_stock'],
                    'Reserved'        => $r['reserved_production_kg'],
                    'Min Level'       => $r['min_stock_level'],
                    'Unit Price'      => $r['current_price_per_unit'],
                    'Stock Value'     => $r['current_stock'] * $r['current_price_per_unit'],
                    'Status'          => $r['current_stock'] <= $r['min_stock_level'] ? 'LOW' : 'OK',
                ];
            }, $rows);
            csv_send('raw_materials_stock_' . $today . '.csv',
                ['Material','Code','Category','Unit','Opening Balance','Current Stock','Reserved','Min Level','Unit Price','Stock Value','Status'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // HEALTH RECORDS
        // ─────────────────────────────────────────────────────────
        case 'health':
            $sql = "SELECT h.*, b.batch_name, f.flock_name
                    FROM health_records h
                    LEFT JOIN batches b ON b.id=h.batch_id
                    LEFT JOIN flocks f ON f.id=h.flock_id
                    WHERE 1=1";
            $p = [];
            if ($from) { $sql .= " AND h.record_date >= ?"; $p[] = $from; }
            if ($to)   { $sql .= " AND h.record_date <= ?"; $p[] = $to; }
            $sql .= " ORDER BY h.record_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($p);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'Date'            => $r['record_date'],
                    'Type'            => $r['record_type'],
                    'Subject'         => $r['subject'],
                    'Product/Vaccine' => $r['product_name'] ?: $r['vaccine_name'],
                    'Dosage'          => $r['dosage'],
                    'Route'           => $r['route'],
                    'Birds Treated'   => $r['birds_treated'],
                    'Mortality'       => $r['mortality_count'],
                    'Reason'          => $r['mortality_reason'],
                    'Vet/Officer'     => $r['vet_name'],
                    'Next Due'        => $r['next_due_date'],
                    'Cost'            => $r['cost'],
                    'Status'          => $r['status'],
                    'Batch'           => $r['batch_name'],
                    'Flock'           => $r['flock_name'],
                    'Notes'           => $r['notes'],
                ];
            }, $rows);
            csv_send('health_records_' . $today . '.csv',
                ['Date','Type','Subject','Product/Vaccine','Dosage','Route','Birds Treated','Mortality','Reason','Vet/Officer','Next Due','Cost','Status','Batch','Flock','Notes'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // BATCHES — batch metadata
        // ─────────────────────────────────────────────────────────
        case 'batches':
            $sql = "SELECT b.*, h.house_name, h.house_code
                    FROM batches b LEFT JOIN houses h ON h.id=b.house_id
                    WHERE 1=1";
            $p = [];
            if ($from) { $sql .= " AND b.placement_date >= ?"; $p[] = $from; }
            if ($to)   { $sql .= " AND b.placement_date <= ?"; $p[] = $to; }
            $sql .= " ORDER BY b.placement_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($p);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'Code'               => $r['batch_code'],
                    'Batch Name'         => $r['batch_name'],
                    'House'              => $r['house_name'],
                    'Type'               => $r['batch_type'],
                    'Breed'              => $r['breed'],
                    'Initial Birds'      => $r['initial_birds'],
                    'Current Birds'      => $r['current_birds'],
                    'Mortality'          => $r['initial_birds'] - $r['current_birds'],
                    'Mortality %'        => $r['initial_birds'] > 0 ? round((($r['initial_birds'] - $r['current_birds']) / $r['initial_birds']) * 100, 2) . '%' : '0%',
                    'Placement Date'     => $r['placement_date'],
                    'Expected Harvest'   => $r['expected_harvest_date'],
                    'Expected Sale'      => $r['expected_sale_date'],
                    'Status'             => $r['status'],
                    'Notes'              => $r['notes'],
                ];
            }, $rows);
            csv_send('batches_' . $today . '.csv',
                ['Code','Batch Name','House','Type','Breed','Initial Birds','Current Birds','Mortality','Mortality %','Placement Date','Expected Harvest','Expected Sale','Status','Notes'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // DAILY BATCH RECORDS — mirrors "Batch 15 2026" spreadsheet
        // ─────────────────────────────────────────────────────────
        case 'daily_records':
            $sql = "SELECT d.*, b.batch_name, b.batch_code, h.house_name
                    FROM daily_batch_records d
                    LEFT JOIN batches b ON b.id=d.batch_id
                    LEFT JOIN houses h ON h.id=b.house_id
                    WHERE 1=1";
            $p = [];
            if ($from) { $sql .= " AND d.record_date >= ?"; $p[] = $from; }
            if ($to)   { $sql .= " AND d.record_date <= ?"; $p[] = $to; }
            $sql .= " ORDER BY d.record_date DESC, d.id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($p);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'Week'         => $r['week_number'],
                    'Date'         => $r['record_date'],
                    'Batch'        => $r['batch_name'],
                    'Code'         => $r['batch_code'],
                    'House'        => $r['house_name'],
                    'Birds'        => $r['opening_birds'],
                    'Mortality'    => $r['mortality'],
                    'M.R.'         => $r['mortality_rate'],
                    'Sold Birds'   => $r['sold_birds'],
                    'Net Birds'    => $r['closing_birds'],
                    'Expected Wt'  => $r['expected_weight_kg'],
                    'Avg Wt'       => $r['average_weight_kg'],
                    'Trays'        => $r['trays'],
                    'No. of Eggs'  => $r['total_eggs'],
                    'Extra Large'  => $r['extra_large_eggs'],
                    'Damaged'      => $r['damaged_eggs'],
                    'Net for Sale' => $r['net_for_sale'],
                    '% Production' => $r['production_pct'],
                    'Notes'        => $r['notes'],
                ];
            }, $rows);
            csv_send('daily_batch_records_' . $today . '.csv',
                ['Week','Date','Batch','Code','House','Birds','Mortality','M.R.','Sold Birds','Net Birds','Expected Wt','Avg Wt','Trays','No. of Eggs','Extra Large','Damaged','Net for Sale','% Production','Notes'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // EGG GRADING
        // ─────────────────────────────────────────────────────────
        case 'egg_grades':
            $sql = "SELECT g.*, eg.grade_code, eg.grade_name, b.batch_name
                    FROM daily_egg_grading g
                    LEFT JOIN egg_grades eg ON eg.id=g.grade_id
                    LEFT JOIN batches b ON b.id=g.batch_id
                    WHERE 1=1";
            $p = [];
            if ($from) { $sql .= " AND g.record_date >= ?"; $p[] = $from; }
            if ($to)   { $sql .= " AND g.record_date <= ?"; $p[] = $to; }
            $sql .= " ORDER BY g.record_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($p);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'Date'        => $r['record_date'],
                    'Batch'       => $r['batch_name'],
                    'Grade'       => $r['grade_name'] . ' (' . $r['grade_code'] . ')',
                    'Total Eggs'  => $r['total_eggs'],
                    'Crates'      => $r['crates_count'],
                    'Damaged'     => $r['damaged'],
                    'Notes'       => $r['notes'],
                ];
            }, $rows);
            csv_send('egg_grading_' . $today . '.csv',
                ['Date','Batch','Grade','Total Eggs','Crates','Damaged','Notes'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // FEED PRODUCTION HISTORY
        // ─────────────────────────────────────────────────────────
        case 'feed_production':
            $sql = "SELECT p.*, r.recipe_name, r.target_species
                    FROM feed_production_batches p
                    LEFT JOIN feed_recipes r ON r.id=p.recipe_id
                    WHERE 1=1";
            $p = [];
            if ($from) { $sql .= " AND p.production_date >= ?"; $p[] = $from; }
            if ($to)   { $sql .= " AND p.production_date <= ?"; $p[] = $to; }
            $sql .= " ORDER BY p.production_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($p);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'Date'         => $r['production_date'],
                    'Recipe'       => $r['recipe_name'],
                    'Target'       => $r['target_species'],
                    'Bags'         => $r['bags_produced'],
                    'Bag Size (kg)'=> $r['bag_size_kg'],
                    'Total kg'     => $r['total_kg'],
                    'Total Cost'   => $r['total_cost'],
                    'Cost per kg'  => $r['cost_per_kg'],
                    'Notes'        => $r['notes'],
                ];
            }, $rows);
            csv_send('feed_production_history_' . $today . '.csv',
                ['Date','Recipe','Target','Bags','Bag Size (kg)','Total kg','Total Cost','Cost per kg','Notes'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // FLOCKS
        // ─────────────────────────────────────────────────────────
        case 'flocks':
            $rows = $pdo->query("SELECT * FROM flocks ORDER BY hatch_date DESC")->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'Flock'         => $r['flock_name'],
                    'Breed'         => $r['breed'],
                    'Initial Count' => $r['initial_count'],
                    'Current Count' => $r['current_count'],
                    'Mortality'     => $r['initial_count'] - $r['current_count'],
                    'Hatch Date'    => $r['hatch_date'],
                    'Status'        => $r['status'],
                ];
            }, $rows);
            csv_send('flocks_' . $today . '.csv',
                ['Flock','Breed','Initial Count','Current Count','Mortality','Hatch Date','Status'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // WALK-IN CUSTOMERS
        // ─────────────────────────────────────────────────────────
        case 'customers':
            $rows = $pdo->query("SELECT * FROM walk_in_customers ORDER BY customer_name")->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'Customer'  => $r['customer_name'],
                    'Phone'     => $r['phone'],
                    'Type'      => $r['customer_type'],
                    'Address'   => $r['address'],
                    'Created'   => $r['created_at'],
                ];
            }, $rows);
            csv_send('walkin_customers_' . $today . '.csv',
                ['Customer','Phone','Type','Address','Created'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // BATCH COSTS
        // ─────────────────────────────────────────────────────────
        case 'batch_costs':
            $sql = "SELECT c.*, b.batch_name, b.batch_code FROM batch_costs c LEFT JOIN batches b ON b.id=c.batch_id WHERE 1=1";
            $p = [];
            if ($from) { $sql .= " AND c.cost_date>=?"; $p[] = $from; }
            if ($to) { $sql .= " AND c.cost_date<=?"; $p[] = $to; }
            $sql .= " ORDER BY c.cost_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($p);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'Date'         => $r['cost_date'],
                    'Batch'        => $r['batch_name'],
                    'Code'         => $r['batch_code'],
                    'What For'     => $r['cost_type'],
                    'Description'  => $r['description'],
                    'Quantity'     => $r['quantity'],
                    'Unit'         => $r['unit'],
                    'Unit Cost'    => $r['unit_cost'],
                    'Total Cost'   => $r['total_cost'],
                    'Paid From'    => $r['paid_from'],
                    'Reference'    => $r['reference_no'],
                ];
            }, $rows);
            csv_send('batch_costs_' . $today . '.csv',
                ['Date','Batch','Code','What For','Description','Quantity','Unit','Unit Cost','Total Cost','Paid From','Reference'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // CASHBOOK
        // ─────────────────────────────────────────────────────────
        case 'cashbook':
            $sql = "SELECT * FROM cashbook_entries WHERE 1=1";
            $p = [];
            if ($from) { $sql .= " AND entry_date>=?"; $p[] = $from; }
            if ($to) { $sql .= " AND entry_date<=?"; $p[] = $to; }
            $sql .= " ORDER BY entry_date DESC, id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($p);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'Date'         => $r['entry_date'],
                    'Direction'    => $r['direction'] === 'in' ? 'Money In' : 'Money Out',
                    'What For'     => $r['money_source'],
                    'Amount (KES)' => $r['amount'],
                    'How Paid'     => $r['paid_through'],
                    'Customer'     => $r['customer_name'],
                    'Supplier'     => $r['supplier_name'],
                    'Reference'    => $r['reference_no'],
                    'Description'  => $r['description'],
                ];
            }, $rows);
            csv_send('cashbook_' . $today . '.csv',
                ['Date','Direction','What For','Amount (KES)','How Paid','Customer','Supplier','Reference','Description'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // CUSTOMER CREDIT
        // ─────────────────────────────────────────────────────────
        case 'credit':
            $rows = $pdo->query("SELECT * FROM customer_credits ORDER BY credit_date DESC")->fetchAll(PDO::FETCH_ASSOC);
            $today_d = date('Y-m-d');
            $out = array_map(function($r) use ($today_d) {
                $isOverdue = $r['status']!=='paid' && $r['due_date'] && $r['due_date'] < $today_d;
                return [
                    'Credit Date' => $r['credit_date'],
                    'Customer'     => $r['customer_name'],
                    'Phone'        => $r['customer_phone'],
                    'What They Took' => $r['item_description'],
                    'Total (KES)'  => $r['total_amount'],
                    'Paid (KES)'   => $r['amount_paid'],
                    'Balance'      => $r['balance'],
                    'Due Date'     => $r['due_date'],
                    'Status'       => $isOverdue ? 'OVERDUE' : $r['status'],
                    'Notes'        => $r['notes'],
                ];
            }, $rows);
            csv_send('customer_credit_' . $today . '.csv',
                ['Credit Date','Customer','Phone','What They Took','Total (KES)','Paid (KES)','Balance','Due Date','Status','Notes'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // FEED ALLOCATIONS
        // ─────────────────────────────────────────────────────────
        case 'feed_allocations':
            $sql = "SELECT a.*, b.batch_name, b.batch_code, b.batch_type FROM feed_allocations a LEFT JOIN batches b ON b.id=a.batch_id WHERE 1=1";
            $p = [];
            if ($from) { $sql .= " AND a.allocation_date>=?"; $p[] = $from; }
            if ($to) { $sql .= " AND a.allocation_date<=?"; $p[] = $to; }
            $sql .= " ORDER BY a.allocation_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($p);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'Date'      => $r['allocation_date'],
                    'Batch'     => $r['batch_name'],
                    'Code'      => $r['batch_code'],
                    'Type'      => $r['batch_type'],
                    'Feed Type' => $r['feed_type'],
                    'KG Fed'    => $r['kg_fed'],
                    'Notes'     => $r['notes'],
                ];
            }, $rows);
            csv_send('feed_allocations_' . $today . '.csv',
                ['Date','Batch','Code','Type','Feed Type','KG Fed','Notes'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // PURCHASE ORDERS
        // ─────────────────────────────────────────────────────────
        case 'purchase_orders':
            $sql = "SELECT po.*, s.supplier_name, s.phone AS supplier_phone FROM purchase_orders po LEFT JOIN suppliers s ON s.id=po.supplier_id WHERE 1=1";
            $p = [];
            if ($from) { $sql .= " AND po.order_date>=?"; $p[] = $from; }
            if ($to) { $sql .= " AND po.order_date<=?"; $p[] = $to; }
            $sql .= " ORDER BY po.order_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($p);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'PO Number'        => $r['po_number'],
                    'Supplier'         => $r['supplier_name'],
                    'Phone'            => $r['supplier_phone'],
                    'Order Date'       => $r['order_date'],
                    'Expected Delivery'=> $r['expected_delivery'],
                    'Received Date'    => $r['received_date'],
                    'Status'           => $r['status'],
                    'Total (KES)'      => $r['total_amount'],
                    'Notes'            => $r['notes'],
                ];
            }, $rows);
            csv_send('purchase_orders_' . $today . '.csv',
                ['PO Number','Supplier','Phone','Order Date','Expected Delivery','Received Date','Status','Total (KES)','Notes'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // LPO DOCUMENTS — quotations, LPOs & invoices
        // ─────────────────────────────────────────────────────────
        case 'lpo_documents':
            $sql = "SELECT d.*, u.username AS created_by_name FROM lpo_documents d LEFT JOIN users u ON u.id=d.created_by WHERE 1=1";
            $p = [];
            if ($from) { $sql .= " AND d.issue_date>=?"; $p[] = $from; }
            if ($to) { $sql .= " AND d.issue_date<=?"; $p[] = $to; }
            $sql .= " ORDER BY d.issue_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($p);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $items = $pdo->prepare('SELECT description, quantity, unit, unit_price, line_total FROM lpo_items WHERE doc_id=?');
                $items->execute([$r['id']]);
                $itemText = implode(' | ', array_map(fn($it) => $it['description'] . ' x' . $it['quantity'] . $it['unit'], $items->fetchAll(PDO::FETCH_ASSOC)));
                $out[] = [
                    'Doc Number'   => $r['doc_number'],
                    'Type'         => $r['doc_type'],
                    'Status'       => $r['status'],
                    'Customer'     => $r['customer_name'],
                    'Phone'        => $r['customer_phone'],
                    'Email'        => $r['customer_email'],
                    'Address'      => $r['customer_address'],
                    'Issue Date'   => $r['issue_date'],
                    'Due Date'     => $r['due_date'],
                    'Subtotal'     => $r['subtotal'],
                    'Tax Rate %'   => $r['tax_rate'],
                    'Tax'          => $r['tax_amount'],
                    'Discount'     => $r['discount'],
                    'Total (KES)'  => $r['total_amount'],
                    'Items'        => $itemText,
                    'Notes'        => $r['notes'],
                    'Created By'   => $r['created_by_name'],
                ];
            }
            csv_send('lpo_documents_' . $today . '.csv',
                ['Doc Number','Type','Status','Customer','Phone','Email','Address','Issue Date','Due Date','Subtotal','Tax Rate %','Tax','Discount','Total (KES)','Items','Notes','Created By'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // BROILER WEIGHINGS
        // ─────────────────────────────────────────────────────────
        case 'weighings':
            $sql = "SELECT w.*, b.batch_name, b.batch_code FROM broiler_weighings w LEFT JOIN batches b ON b.id=w.batch_id WHERE 1=1";
            $p = [];
            if ($from) { $sql .= " AND w.weigh_date>=?"; $p[] = $from; }
            if ($to) { $sql .= " AND w.weigh_date<=?"; $p[] = $to; }
            $sql .= " ORDER BY w.weigh_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($p);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'Date'            => $r['weigh_date'],
                    'Batch'           => $r['batch_name'],
                    'Code'            => $r['batch_code'],
                    'Day #'           => $r['day_number'],
                    'Sample Size'     => $r['sample_size'],
                    'Avg Weight (kg)' => $r['avg_weight_kg'],
                    'Notes'           => $r['notes'],
                ];
            }, $rows);
            csv_send('broiler_weighings_' . $today . '.csv',
                ['Date','Batch','Code','Day #','Sample Size','Avg Weight (kg)','Notes'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // HATCHERY
        // ─────────────────────────────────────────────────────────
        case 'hatchery':
            $rows = $pdo->query("SELECT * FROM hatchery_batches ORDER BY setting_date DESC")->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'Setting Date'      => $r['setting_date'],
                    'Expected Hatch'    => $r['expected_hatch_date'],
                    'Actual Hatch'      => $r['actual_hatch_date'],
                    'Breed'             => $r['breed'],
                    'Eggs Set'          => $r['eggs_set'],
                    'Fertile Eggs'       => $r['fertile_eggs'],
                    'Chicks Hatched'    => $r['chicks_hatched'],
                    'Hatchability %'    => $r['hatchability_pct'],
                    'Destination'       => $r['destination'],
                    'Cost per DOC (KES)'=> $r['cost_per_doc'],
                    'Notes'             => $r['notes'],
                ];
            }, $rows);
            csv_send('hatchery_' . $today . '.csv',
                ['Setting Date','Expected Hatch','Actual Hatch','Breed','Eggs Set','Fertile Eggs','Chicks Hatched','Hatchability %','Destination','Cost per DOC (KES)','Notes'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // EGG LOSSES
        // ─────────────────────────────────────────────────────────
        case 'egg_losses':
            $sql = "SELECT l.*, b.batch_name FROM egg_losses l LEFT JOIN batches b ON b.id=l.batch_id WHERE 1=1";
            $p = [];
            if ($from) { $sql .= " AND l.loss_date>=?"; $p[] = $from; }
            if ($to) { $sql .= " AND l.loss_date<=?"; $p[] = $to; }
            $sql .= " ORDER BY l.loss_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($p);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'Date'         => $r['loss_date'],
                    'Batch'        => $r['batch_name'],
                    'Type'         => $r['loss_type'],
                    'Quantity'     => $r['quantity'],
                    'Value (KES)'  => $r['estimated_value'],
                    'Reason'       => $r['reason'],
                ];
            }, $rows);
            csv_send('egg_losses_' . $today . '.csv',
                ['Date','Batch','Type','Quantity','Value (KES)','Reason'], $out);
            break;

        // ─────────────────────────────────────────────────────────
        // QUALITY TESTS
        // ─────────────────────────────────────────────────────────
        case 'quality_tests':
            $rows = $pdo->query("SELECT q.*, r.material_name FROM quality_tests q LEFT JOIN raw_materials r ON r.id=q.material_id ORDER BY q.test_date DESC")->fetchAll(PDO::FETCH_ASSOC);
            $out = array_map(function($r) {
                return [
                    'Date'         => $r['test_date'],
                    'Material'     => $r['material_name'],
                    'Test Type'    => $r['test_type'],
                    'Result Value' => $r['result_value'],
                    'Unit'         => $r['unit'],
                    'Pass/Fail'    => $r['pass_fail'],
                    'Tested By'    => $r['tested_by'],
                    'Notes'        => $r['notes'],
                ];
            }, $rows);
            csv_send('quality_tests_' . $today . '.csv',
                ['Date','Material','Test Type','Result Value','Unit','Pass/Fail','Tested By','Notes'], $out);
            break;

        default:
            http_response_code(400);
            die('Unknown module: ' . htmlspecialchars($module) . '. Valid modules: orders, daily_sales, bulk_sales, stores_movements, raw_materials, health, batches, daily_records, egg_grades, feed_production, flocks, customers, batch_costs, cashbook, credit, feed_allocations, purchase_orders, weighings, hatchery, egg_losses, quality_tests, lpo_documents.');
    }
} catch (Exception $e) {
    http_response_code(500);
    die('Export error: ' . $e->getMessage());
}
