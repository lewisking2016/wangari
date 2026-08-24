<?php
/**
 * Batch Analytics API
 * Calculates FCR (Feed Conversion Ratio), HDP (Hen-Day Production), 
 * and Batch Profitability for poultry operations
 */
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';

switch ($action) {
    case 'dashboard':
        echo json_encode(getDashboardAnalytics($pdo, $userId));
        break;
    case 'batch':
        $batchId = (int)($_GET['batch_id'] ?? $_POST['batch_id'] ?? 0);
        echo json_encode(getBatchAnalytics($pdo, $userId, $batchId));
        break;
    case 'hdp_history':
        echo json_encode(getHDPHistory($pdo, $userId));
        break;
    case 'fcr_history':
        echo json_encode(getFCRHistory($pdo, $userId));
        break;
    default:
        echo json_encode(['error' => 'Unknown action']);
}

/**
 * Get dashboard-level analytics for all batches
 */
function getDashboardAnalytics(PDO $pdo, int $userId): array
{
    $batches = getAllActiveBatches($pdo, $userId);
    
    $totalFCR = 0;
    $totalHDP = 0;
    $totalProfit = 0;
    $totalRevenue = 0;
    $totalCosts = 0;
    $batchCount = 0;
    $layerBatches = 0;
    $broilerBatches = 0;
    
    $analytics = [];
    
    foreach ($batches as $batch) {
        $batchData = calculateBatchMetrics($pdo, $batch);
        $analytics[] = $batchData;
        
        if ($batchData['fcr'] > 0) {
            $totalFCR += $batchData['fcr'];
            $batchCount++;
        }
        
        if ($batch['batch_type'] === 'layer') {
            $totalHDP += $batchData['hdp'];
            $layerBatches++;
        } else {
            $broilerBatches++;
        }
        
        $totalProfit += $batchData['profit'];
        $totalRevenue += $batchData['revenue'];
        $totalCosts += $batchData['total_costs'];
    }
    
    return [
        'summary' => [
            'avg_fcr' => $batchCount > 0 ? round($totalFCR / $batchCount, 2) : 0,
            'avg_hdp' => $layerBatches > 0 ? round($totalHDP / $layerBatches, 2) : 0,
            'total_profit' => round($totalProfit, 2),
            'total_revenue' => round($totalRevenue, 2),
            'total_costs' => round($totalCosts, 2),
            'active_batches' => count($batches),
            'layer_batches' => $layerBatches,
            'broiler_batches' => $broilerBatches,
            'profit_margin' => $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0,
        ],
        'batches' => $analytics,
    ];
}

/**
 * Get analytics for a single batch
 */
function getBatchAnalytics(PDO $pdo, int $userId, int $batchId): array
{
    $batch = getBatchById($pdo, $userId, $batchId);
    if (!$batch) {
        return ['error' => 'Batch not found'];
    }
    
    return calculateBatchMetrics($pdo, $batch);
}

/**
 * Get HDP history for all layer batches
 */
function getHDPHistory(PDO $pdo, int $userId): array
{
    $sql = "SELECT dbr.record_date, dbr.total_eggs, dbr.closing_birds, 
                   dbr.production_pct, b.batch_name, b.id as batch_id
            FROM daily_batch_records dbr
            JOIN batches b ON dbr.batch_id = b.id
            WHERE b.user_id = ? AND b.batch_type = 'layer'
            ORDER BY dbr.record_date DESC
            LIMIT 90";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $records = $stmt->fetchAll();
    
    $history = [];
    foreach ($records as $record) {
        $hdp = $record['closing_birds'] > 0 
            ? round(($record['total_eggs'] / $record['closing_birds']) * 100, 2)
            : 0;
        
        $history[] = [
            'date' => $record['record_date'],
            'batch' => $record['batch_name'],
            'batch_id' => $record['batch_id'],
            'eggs' => (int)$record['total_eggs'],
            'birds' => (int)$record['closing_birds'],
            'hdp' => $hdp,
            'production_pct' => (float)$record['production_pct'],
        ];
    }
    
    return $history;
}

/**
 * Get FCR history for all batches
 */
function getFCRHistory(PDO $pdo, int $userId): array
{
    $sql = "SELECT dbr.record_date, dbr.average_weight_kg, 
                   b.batch_name, b.id as batch_id, b.batch_type,
                   COALESCE(SUM(fl.quantity_kg), 0) as feed_kg
            FROM daily_batch_records dbr
            JOIN batches b ON dbr.batch_id = b.id
            LEFT JOIN feed_logs fl ON fl.record_date = dbr.record_date 
                AND (fl.group_id = b.flock_id OR fl.animal_id = b.id)
            WHERE b.user_id = ?
            GROUP BY dbr.id, b.id
            ORDER BY dbr.record_date DESC
            LIMIT 90";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $records = $stmt->fetchAll();
    
    $history = [];
    foreach ($records as $record) {
        $weightGain = max(0, (float)$record['average_weight_kg']);
        $feedKg = (float)$record['feed_kg'];
        $fcr = $weightGain > 0 ? round($feedKg / $weightGain, 2) : 0;
        
        $history[] = [
            'date' => $record['record_date'],
            'batch' => $record['batch_name'],
            'batch_id' => $record['batch_id'],
            'type' => $record['batch_type'],
            'feed_kg' => $feedKg,
            'weight_kg' => $weightGain,
            'fcr' => $fcr,
        ];
    }
    
    return $history;
}

/**
 * Calculate all metrics for a single batch
 */
function calculateBatchMetrics(PDO $pdo, array $batch): array
{
    $batchId = (int)$batch['id'];
    $batchType = $batch['batch_type'];
    
    // 1. Calculate total costs
    $totalCosts = getBatchTotalCosts($pdo, $batchId);
    $costBreakdown = getBatchCostBreakdown($pdo, $batchId);
    
    // 2. Calculate feed consumed and FCR
    $feedData = getBatchFeedData($pdo, $batchId);
    $totalFeedKg = $feedData['total_kg'];
    $totalFeedCost = $feedData['total_cost'];
    
    // 3. Calculate weight gain / egg production
    $productionData = getBatchProductionData($pdo, $batchId);
    
    // 4. FCR (Feed Conversion Ratio) - for broilers
    $fcr = 0;
    $fcrStatus = 'N/A';
    if ($batchType === 'broiler' || $batchType === 'dual_purpose') {
        $totalWeightGain = $productionData['total_weight_gain_kg'];
        $fcr = $totalWeightGain > 0 ? round($totalFeedKg / $totalWeightGain, 2) : 0;
        
        // Industry benchmarks for broilers
        if ($fcr > 0) {
            if ($fcr <= 1.6) $fcrStatus = 'Excellent';
            elseif ($fcr <= 1.8) $fcrStatus = 'Good';
            elseif ($fcr <= 2.0) $fcrStatus = 'Average';
            else $fcrStatus = 'Poor';
        }
    }
    
    // 5. HDP (Hen-Day Production) - for layers
    $hdp = 0;
    $hdpStatus = 'N/A';
    if ($batchType === 'layer') {
        $totalEggs = $productionData['total_eggs'];
        $avgBirds = $productionData['avg_birds'];
        $daysActive = $productionData['days_active'];
        
        if ($avgBirds > 0 && $daysActive > 0) {
            $hdp = round(($totalEggs / ($avgBirds * $daysActive)) * 100, 2);
        }
        
        // Industry benchmarks for layers
        if ($hdp > 0) {
            if ($hdp >= 90) $hdpStatus = 'Excellent';
            elseif ($hdp >= 80) $hdpStatus = 'Good';
            elseif ($hdp >= 70) $hdpStatus = 'Average';
            else $hdpStatus = 'Poor';
        }
    }
    
    // 6. Revenue & Profit
    $revenue = getBatchRevenue($pdo, $batchId);
    $profit = $revenue - $totalCosts;
    $profitMargin = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;
    
    // 7. Mortality rate
    $mortalityRate = $productionData['mortality_rate'];
    
    // 8. Cost per bird / Cost per egg
    $currentBirds = (int)$batch['current_birds'];
    $costPerBird = $currentBirds > 0 ? round($totalCosts / $currentBirds, 2) : 0;
    $costPerEgg = $productionData['total_eggs'] > 0 ? round($totalCosts / $productionData['total_eggs'], 2) : 0;
    
    // 9. Days since placement
    $placementDate = new DateTime($batch['placement_date']);
    $today = new DateTime();
    $daysActive = max(1, $today->diff($placementDate)->days);
    
    // 10. Expected vs actual
    $expectedHarvest = $batch['expected_harvest_date'] 
        ? (new DateTime($batch['expected_harvest_date']))->diff($today)->days 
        : null;
    
    return [
        'batch_id' => $batchId,
        'batch_name' => $batch['batch_name'],
        'batch_type' => $batchType,
        'status' => $batch['status'],
        'initial_birds' => (int)$batch['initial_birds'],
        'current_birds' => $currentBirds,
        'placement_date' => $batch['placement_date'],
        'days_active' => $daysActive,
        
        // FCR
        'fcr' => $fcr,
        'fcr_status' => $fcrStatus,
        'total_feed_kg' => round($totalFeedKg, 2),
        
        // HDP
        'hdp' => $hdp,
        'hdp_status' => $hdpStatus,
        'total_eggs' => $productionData['total_eggs'],
        
        // Profitability
        'total_costs' => round($totalCosts, 2),
        'total_feed_cost' => round($totalFeedCost, 2),
        'revenue' => round($revenue, 2),
        'profit' => round($profit, 2),
        'profit_margin' => $profitMargin,
        'cost_per_bird' => $costPerBird,
        'cost_per_egg' => $costPerEgg,
        
        // Mortality
        'mortality_count' => $productionData['total_mortality'],
        'mortality_rate' => $mortalityRate,
        
        // Cost breakdown
        'cost_breakdown' => $costBreakdown,
    ];
}

/**
 * Get all active batches for a user
 */
function getAllActiveBatches(PDO $pdo, int $userId): array
{
    $sql = "SELECT * FROM batches WHERE user_id = ? AND status = 'active' ORDER BY placement_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Get a single batch by ID
 */
function getBatchById(PDO $pdo, int $userId, int $batchId): ?array
{
    $sql = "SELECT * FROM batches WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$batchId, $userId]);
    return $stmt->fetch() ?: null;
}

/**
 * Get total costs for a batch
 */
function getBatchTotalCosts(PDO $pdo, int $batchId): float
{
    $sql = "SELECT COALESCE(SUM(total_cost), 0) as total FROM batch_costs WHERE batch_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$batchId]);
    return (float)$stmt->fetchColumn();
}

/**
 * Get cost breakdown by type
 */
function getBatchCostBreakdown(PDO $pdo, int $batchId): array
{
    $sql = "SELECT cost_type, SUM(total_cost) as amount 
            FROM batch_costs WHERE batch_id = ? 
            GROUP BY cost_type ORDER BY amount DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$batchId]);
    
    $breakdown = [];
    while ($row = $stmt->fetch()) {
        $breakdown[] = [
            'type' => $row['cost_type'],
            'amount' => (float)$row['amount'],
        ];
    }
    return $breakdown;
}

/**
 * Get feed data for a batch
 */
function getBatchFeedData(PDO $pdo, int $batchId): array
{
    // Try batch_costs first (feed costs linked to batch)
    $sql = "SELECT COALESCE(SUM(quantity), 0) as total_kg, 
                   COALESCE(SUM(total_cost), 0) as total_cost
            FROM batch_costs 
            WHERE batch_id = ? AND cost_type = 'feed'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$batchId]);
    $result = $stmt->fetch();
    
    $totalKg = (float)$result['total_kg'];
    $totalCost = (float)$result['total_cost'];
    
    // If no batch_costs, try feed_logs
    if ($totalKg == 0) {
        $sql2 = "SELECT COALESCE(SUM(fl.quantity_kg), 0) as total_kg,
                        COALESCE(SUM(fl.cost), 0) as total_cost
                 FROM feed_logs fl
                 WHERE fl.group_id IN (
                     SELECT flock_id FROM batches WHERE id = ?
                 ) OR fl.animal_id = ?";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$batchId, $batchId]);
        $result2 = $stmt2->fetch();
        $totalKg = (float)$result2['total_kg'];
        $totalCost = (float)$result2['total_cost'];
    }
    
    return ['total_kg' => $totalKg, 'total_cost' => $totalCost];
}

/**
 * Get production data for a batch
 */
function getBatchProductionData(PDO $pdo, int $batchId): array
{
    $result = [
        'total_eggs' => 0,
        'total_weight_gain_kg' => 0,
        'total_mortality' => 0,
        'mortality_rate' => 0,
        'avg_birds' => 0,
        'days_active' => 0,
    ];
    
    // Get initial birds from batch
    $sql0 = "SELECT initial_birds, current_birds, batch_type FROM batches WHERE id = ?";
    $stmt0 = $pdo->prepare($sql0);
    $stmt0->execute([$batchId]);
    $batchInfo = $stmt0->fetch();
    
    if (!$batchInfo) return $result;
    
    $initialBirds = (int)$batchInfo['initial_birds'];
    
    // Get from daily_batch_records
    $sql = "SELECT SUM(total_eggs) as total_eggs, 
                   SUM(mortality) as total_mortality,
                   AVG(closing_birds) as avg_birds,
                   AVG(average_weight_kg) as avg_weight,
                   COUNT(*) as days_active
            FROM daily_batch_records 
            WHERE batch_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$batchId]);
    $row = $stmt->fetch();
    
    if ($row) {
        $result['total_eggs'] = (int)($row['total_eggs'] ?? 0);
        $result['total_mortality'] = (int)($row['total_mortality'] ?? 0);
        $result['avg_birds'] = max(1, (float)($row['avg_birds'] ?? $initialBirds));
        $result['days_active'] = max(1, (int)($row['days_active'] ?? 1));
        
        // Calculate weight gain from production_records
        $sql2 = "SELECT SUM(meat_weight_kg) as total_meat,
                        SUM(feed_consumed_kg) as total_feed,
                        SUM(weight_kg) as total_weight
                 FROM production_records pr
                 JOIN batches b ON pr.flock_id = b.flock_id
                 WHERE b.id = ?";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$batchId]);
        $prodRow = $stmt2->fetch();
        
        if ($prodRow) {
            $result['total_weight_gain_kg'] = (float)($prodRow['total_weight'] ?? $prodRow['total_meat'] ?? 0);
        }
    }
    
    // Calculate mortality rate
    if ($initialBirds > 0) {
        $result['mortality_rate'] = round(($result['total_mortality'] / $initialBirds) * 100, 2);
    }
    
    return $result;
}

/**
 * Get revenue for a batch (from sales linked to this batch's flock)
 */
function getBatchRevenue(PDO $pdo, int $batchId): float
{
    $sql = "SELECT COALESCE(SUM(s.total_amount), 0) as revenue
            FROM sales s
            WHERE s.flock_id IN (SELECT flock_id FROM batches WHERE id = ?)
               OR s.batch_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$batchId, $batchId]);
    return (float)$stmt->fetchColumn();
}

/**
 * Get SACCO/Lender report data
 */
function getSACCOReport(PDO $pdo, int $userId): array
{
    $batches = getAllActiveBatches($pdo, $userId);
    
    $report = [
        'generated_date' => date('Y-m-d H:i:s'),
        'farm_name' => '',
        'owner_name' => '',
        'batches' => [],
        'totals' => [
            'total_investment' => 0,
            'total_revenue' => 0,
            'net_profit' => 0,
            'total_birds' => 0,
            'avg_fcr' => 0,
            'avg_hdp' => 0,
        ],
    ];
    
    // Get farm info
    $sql = "SELECT first_name, last_name FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if ($user) {
        $report['owner_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    }
    
    $fcrSum = 0;
    $fcrCount = 0;
    $hdpSum = 0;
    $hdpCount = 0;
    
    foreach ($batches as $batch) {
        $metrics = calculateBatchMetrics($pdo, $batch);
        $report['batches'][] = $metrics;
        
        $report['totals']['total_investment'] += $metrics['total_costs'];
        $report['totals']['total_revenue'] += $metrics['revenue'];
        $report['totals']['total_birds'] += $metrics['current_birds'];
        
        if ($metrics['fcr'] > 0) {
            $fcrSum += $metrics['fcr'];
            $fcrCount++;
        }
        if ($metrics['hdp'] > 0) {
            $hdpSum += $metrics['hdp'];
            $hdpCount++;
        }
    }
    
    $report['totals']['net_profit'] = $report['totals']['total_revenue'] - $report['totals']['total_investment'];
    $report['totals']['avg_fcr'] = $fcrCount > 0 ? round($fcrSum / $fcrCount, 2) : 0;
    $report['totals']['avg_hdp'] = $hdpCount > 0 ? round($hdpSum / $hdpCount, 2) : 0;
    
    return $report;
}
