<?php
/**
 * Admin Analytics API
 * Returns simple analytics data for admin dashboard charts
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/queries.php';

function safeFetchAll(PDO $pdo, string $sql, array $params = []): array {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        @error_log('safeFetchAll failed: ' . $e->getMessage());
        return [];
    }
}
function safeFetchColumn(PDO $pdo, string $sql, array $params = [], $default = 0) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : $value;
    } catch (Exception $e) {
        @error_log('safeFetchColumn failed: ' . $e->getMessage());
        return $default;
    }
}

$response = ['success' => false, 'data' => []];

// Require admin session
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

try {
    $pdo = getDatabaseConnection();

    // Sales last 7 days
    $sales = safeFetchAll($pdo, "SELECT DATE(created_at) AS day, COALESCE(SUM(total_amount),0) AS total FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at) ORDER BY DATE(created_at)");

    // Orders count last 7 days
    $orders = safeFetchAll($pdo, "SELECT DATE(created_at) AS day, COUNT(*) AS cnt FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at) ORDER BY DATE(created_at)");

    // Top products by quantity sold
    $topProducts = safeFetchAll($pdo, "SELECT p.name, SUM(oi.quantity) AS qty FROM order_items oi JOIN products p ON oi.product_id = p.id GROUP BY oi.product_id ORDER BY qty DESC LIMIT 5");

    // Inventory levels (low stock)
    $inventory = safeFetchAll($pdo, "SELECT id, name, stock_quantity FROM products ORDER BY stock_quantity ASC LIMIT 10");

    // Count of low stock alerts (stock <= 15)
    $alertCount = (int)safeFetchColumn($pdo, "SELECT COUNT(*) AS cnt FROM products WHERE stock_quantity <= 15");

    // Recent orders as system events/activity log
    $recentOrders = safeFetchAll($pdo, "SELECT o.id, u.first_name, u.last_name, o.total_amount, o.created_at, o.status FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");

    // ============================================================
    // EXTENDED ANALYTICS — for the dedicated Analytics page
    // ============================================================

    // Production: eggs collected per day, last 30 days
    $prodStmt = $pdo->prepare("
        SELECT record_date AS day, SUM(total_eggs) AS eggs, SUM(mortality) AS mortality
        FROM daily_batch_records
        WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
        GROUP BY record_date ORDER BY record_date");
    $prodStmt->execute();
    $production = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

    // Monthly revenue + cost + profit, last 6 months
    $profitStmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(d, '%Y-%m') AS month,
            COALESCE(rev.revenue, 0) AS revenue,
            COALESCE(cst.cost, 0) AS cost,
            COALESCE(rev.revenue, 0) - COALESCE(cst.cost, 0) AS profit
        FROM (
            SELECT DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL n MONTH), '%Y-%m-01') AS d
            FROM (SELECT 0 AS n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) m
        ) months
        LEFT JOIN (
            SELECT DATE_FORMAT(entry_date, '%Y-%m') AS month, SUM(amount) AS revenue
            FROM cashbook_entries WHERE direction='in'
            GROUP BY DATE_FORMAT(entry_date, '%Y-%m')
        ) rev ON rev.month = DATE_FORMAT(months.d, '%Y-%m')
        LEFT JOIN (
            SELECT DATE_FORMAT(cost_date, '%Y-%m') AS month, SUM(total_cost) AS cost
            FROM batch_costs
            GROUP BY DATE_FORMAT(cost_date, '%Y-%m')
        ) cst ON cst.month = DATE_FORMAT(months.d, '%Y-%m')
        ORDER BY months.d");
    $profitStmt->execute();
    $profit = $profitStmt->fetchAll(PDO::FETCH_ASSOC);

    // Mortality per batch
    $mortStmt = $pdo->prepare("
        SELECT b.batch_name, b.initial_birds, b.current_birds,
               (b.initial_birds - b.current_birds) AS mortality_count,
               CASE WHEN b.initial_birds > 0
                    THEN ROUND(((b.initial_birds - b.current_birds) / b.initial_birds) * 100, 2)
                    ELSE 0 END AS mortality_pct
        FROM batches b
        WHERE b.status IN ('completed','sold','active')
        ORDER BY mortality_pct DESC LIMIT 10");
    $mortStmt->execute();
    $mortalityByBatch = $mortStmt->fetchAll(PDO::FETCH_ASSOC);

    // FCR per batch (with feed allocations)
    $fcrStmt = $pdo->prepare("
        SELECT b.id, b.batch_name, b.batch_type,
               COALESCE(fa.total_feed, 0) AS total_feed_kg,
               COALESCE(dr.total_eggs, 0) AS total_eggs,
               COALESCE(dr.mortality, 0) AS mortality,
               b.initial_birds,
               b.current_birds,
               CASE WHEN b.initial_birds > 0 AND dr.mortality IS NOT NULL
                    THEN b.initial_birds - dr.mortality ELSE b.current_birds END AS live_birds
        FROM batches b
        LEFT JOIN (
            SELECT batch_id, SUM(kg_fed) AS total_feed
            FROM feed_allocations GROUP BY batch_id
        ) fa ON fa.batch_id = b.id
        LEFT JOIN (
            SELECT batch_id, SUM(total_eggs) AS total_eggs, SUM(mortality) AS mortality
            FROM daily_batch_records GROUP BY batch_id
        ) dr ON dr.batch_id = b.id
        WHERE b.status IN ('active','completed','sold')
        ORDER BY b.placement_date DESC LIMIT 10");
    $fcrStmt->execute();
    $fcrBatches = $fcrStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fcrBatches as &$fr) {
        $live = max(1, (int)$fr['live_birds']);
        $meatKg = (float)$fr['current_birds'] > 0 ? (float)$fr['current_birds'] * 1.5 : 0; // avg 1.5kg per broiler
        $fcrVal = $meatKg > 0 ? round((float)$fr['total_feed_kg'] / $meatKg, 2) : 0;
        $fr['fcr'] = $fcrVal;
    }
    unset($fr);

    // Broiler growth curve — last batch with weighings
    $growthStmt = $pdo->prepare("
        SELECT b.id, b.batch_name, w.day_number, w.avg_weight_kg
        FROM broiler_weighings w
        JOIN batches b ON b.id = w.batch_id
        WHERE w.batch_id = (
            SELECT batch_id FROM broiler_weighings
            GROUP BY batch_id
            ORDER BY MAX(weigh_date) DESC LIMIT 1
        )
        ORDER BY w.day_number");
    $growthStmt->execute();
    $growthCurve = $growthStmt->fetchAll(PDO::FETCH_ASSOC);
    // Add batch name
    $growthBatchName = '';
    if ($growthStmt->rowCount() === 0) {
        // Try any batch
        $growthCurve = [];
    } else {
        $growthBatchName = $growthStmt->fetch(PDO::FETCH_ASSOC)['batch_name'] ?? '';
        $growthStmt->execute();
        $growthCurve = $growthStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cashbook: money in vs out per day, last 14 days
    $cashStmt = $pdo->prepare("
        SELECT entry_date AS day,
               SUM(CASE WHEN direction='in' THEN amount ELSE 0 END) AS money_in,
               SUM(CASE WHEN direction='out' THEN amount ELSE 0 END) AS money_out
        FROM cashbook_entries
        WHERE entry_date >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
        GROUP BY entry_date ORDER BY entry_date");
    $cashStmt->execute();
    $cashbookFlow = $cashStmt->fetchAll(PDO::FETCH_ASSOC);

    // Low stock — raw materials at or below min
    $lowStockStmt = $pdo->prepare("
        SELECT material_name, current_stock, min_stock_level, unit
        FROM raw_materials
        WHERE current_stock <= min_stock_level
        ORDER BY (current_stock / GREATEST(min_stock_level, 0.001)) ASC
        LIMIT 10");
    $lowStockStmt->execute();
    $lowStock = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);

    // Credit aging
    $creditAgingStmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN status IN ('unpaid','partial') AND (due_date IS NULL OR due_date >= CURDATE()) THEN balance ELSE 0 END) AS current_due,
            SUM(CASE WHEN status IN ('unpaid','partial') AND due_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN balance ELSE 0 END) AS overdue_30,
            SUM(CASE WHEN status IN ('unpaid','partial') AND due_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN balance ELSE 0 END) AS overdue_1_30,
            SUM(CASE WHEN status = 'paid' AND last_payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN amount_paid ELSE 0 END) AS collected_30d,
            SUM(CASE WHEN status IN ('unpaid','partial') THEN balance ELSE 0 END) AS total_owed
        FROM customer_credits");
    $creditAgingStmt->execute();
    $creditAging = $creditAgingStmt->fetch(PDO::FETCH_ASSOC);

    // Bird count trend (per active batch over time)
    $birdTrend = safeFetchAll($pdo, "SELECT record_date AS day, SUM(closing_birds) AS birds FROM daily_batch_records WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) GROUP BY record_date ORDER BY record_date");

    // Top debtors
    $topDebtorsStmt = $pdo->prepare("
        SELECT customer_name, SUM(balance) AS total_owed, COUNT(*) AS credit_count
        FROM customer_credits
        WHERE status IN ('unpaid','partial')
        GROUP BY customer_name
        ORDER BY total_owed DESC LIMIT 5");
    $topDebtorsStmt->execute();
    $topDebtors = $topDebtorsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Customer type breakdown (for donut)
    $custTypeStmt = $pdo->prepare("
        SELECT customer_type, COUNT(*) AS cnt
        FROM walk_in_customers GROUP BY customer_type");
    $custTypeStmt->execute();
    $customerTypes = $custTypeStmt->fetchAll(PDO::FETCH_ASSOC);

    // Product sales by category (for donut)
    $prodCatStmt = $pdo->prepare("
        SELECT p.product_type, SUM(oi.quantity) AS qty, SUM(oi.quantity * oi.price_at_purchase) AS revenue
        FROM order_items oi JOIN products p ON p.id=oi.product_id
        GROUP BY p.product_type");
    $prodCatStmt->execute();
    $productMix = $prodCatStmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = [
        'sales' => $sales,
        'orders' => $orders,
        'top_products' => $topProducts,
        'inventory' => $inventory,
        'alerts' => $alertCount,
        'recent_orders' => $recentOrders,
        'production' => $production,
        'profit' => $profit,
        'mortality_by_batch' => $mortalityByBatch,
        'fcr_batches' => $fcrBatches,
        'growth_curve' => $growthCurve,
        'growth_batch_name' => $growthBatchName,
        'cashbook_flow' => $cashbookFlow,
        'low_stock' => $lowStock,
        'credit_aging' => $creditAging,
        'bird_trend' => $birdTrend,
        'top_debtors' => $topDebtors,
        'customer_types' => $customerTypes,
        'product_mix' => $productMix,
    ];

} catch (Exception $e) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Server error';
    if (defined('APP_DEBUG') && APP_DEBUG) $response['error'] = $e->getMessage();
}

echo json_encode($response);

?>
