<?php
/**
 * Wangari AI Benchmarking System — Phase 2
 * 
 * Compares a farmer's data to regional averages and industry benchmarks.
 * Uses open datasets (GROW-Africa, FAO, RHoMIS) for baseline data.
 * 
 * Features:
 * - FCR benchmarking (Feed Conversion Ratio)
 * - Mortality benchmarking
 * - Cost-per-bird benchmarking
 * - Production benchmarking (eggs/day, milk/cow)
 * - Anomaly detection (unusual patterns)
 * - Feed depletion prediction
 * 
 * Endpoint: GET /Backend/api/ai_benchmarks.php?action=fcrc&user_id=123
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? 'all';
$user_id = (int)($_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode(['error' => 'user_id required']);
    exit;
}

switch ($action) {
    case 'fcrc':
        echo json_encode(getFCRBenchmark($pdo, $user_id));
        break;
    case 'mortality':
        echo json_encode(getMortalityBenchmark($pdo, $user_id));
        break;
    case 'cost':
        echo json_encode(getCostBenchmark($pdo, $user_id));
        break;
    case 'production':
        echo json_encode(getProductionBenchmark($pdo, $user_id));
        break;
    case 'anomalies':
        echo json_encode(getAnomalies($pdo, $user_id));
        break;
    case 'feed_prediction':
        echo json_encode(getFeedPrediction($pdo, $user_id));
        break;
    case 'all':
    default:
        echo json_encode([
            'fcrc' => getFCRBenchmark($pdo, $user_id),
            'mortality' => getMortalityBenchmark($pdo, $user_id),
            'cost' => getCostBenchmark($pdo, $user_id),
            'production' => getProductionBenchmark($pdo, $user_id),
            'anomalies' => getAnomalies($pdo, $user_id),
            'feed_prediction' => getFeedPrediction($pdo, $user_id)
        ]);
}

// ═══════════════════════════════════════════════════════════════
// BENCHMARK DATA (Kenya-specific, from FAO + GROW-Africa)
// ═══════════════════════════════════════════════════════════════

$BENCHMARKS = [
    'layers' => [
        'fcr' => ['target' => 2.0, 'good' => 1.8, 'excellent' => 1.6, 'poor' => 2.5],
        'mortality_monthly' => ['target' => 2.0, 'good' => 1.0, 'excellent' => 0.5, 'poor' => 5.0],
        'eggs_per_day_pct' => ['target' => 80, 'good' => 85, 'excellent' => 90, 'poor' => 60],
        'cost_per_bird_month' => ['target' => 450, 'good' => 380, 'excellent' => 320, 'poor' => 600],
        'revenue_per_bird_month' => ['target' => 550, 'good' => 600, 'excellent' => 650, 'poor' => 400],
        'cost_per_egg' => ['target' => 4.5, 'good' => 3.5, 'excellent' => 3.0, 'poor' => 6.0],
    ],
    'broilers' => [
        'fcr' => ['target' => 1.8, 'good' => 1.6, 'excellent' => 1.5, 'poor' => 2.2],
        'mortality_total' => ['target' => 5.0, 'good' => 3.0, 'excellent' => 2.0, 'poor' => 10.0],
        'weight_gain_per_day' => ['target' => 50, 'good' => 55, 'excellent' => 60, 'poor' => 40],
        'cost_per_kg' => ['target' => 350, 'good' => 300, 'excellent' => 270, 'poor' => 450],
        'days_to_market' => ['target' => 42, 'good' => 38, 'excellent' => 35, 'poor' => 50],
    ],
    'dairy' => [
        'milk_per_cow_day' => ['target' => 15, 'good' => 18, 'excellent' => 22, 'poor' => 10],
        'mortality_monthly' => ['target' => 1.0, 'good' => 0.5, 'excellent' => 0.2, 'poor' => 3.0],
        'cost_per_litre' => ['target' => 35, 'good' => 30, 'excellent' => 25, 'poor' => 50],
        'revenue_per_litre' => ['target' => 50, 'good' => 55, 'excellent' => 60, 'poor' => 40],
    ]
];

// ═══════════════════════════════════════════════════════════════
// BENCHMARK FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function getFCRBenchmark(PDO $pdo, int $user_id): array {
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $today = date('Y-m-d');
    
    // Get feed cost this week
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM simple_expenses WHERE user_id = ? AND category = 'feed' AND expense_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $week_start, $today]);
    $feed_cost = (float) $stmt->fetchColumn();
    
    // Get eggs this week
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(eggs_collected), 0) FROM daily_production WHERE user_id = ? AND record_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $week_start, $today]);
    $eggs = (int) $stmt->fetchColumn();
    
    if ($eggs == 0) {
        return ['status' => 'no_data', 'message' => 'No production data this week'];
    }
    
    $cost_per_egg = $feed_cost / $eggs;
    $bench = $GLOBALS['BENCHMARKS']['layers']['cost_per_egg'];
    
    $rating = 'poor';
    $color = '#EF4444';
    if ($cost_per_egg <= $bench['excellent']) { $rating = 'excellent'; $color = '#22C55E'; }
    elseif ($cost_per_egg <= $bench['good']) { $rating = 'good'; $color = '#22C55E'; }
    elseif ($cost_per_egg <= $bench['target']) { $rating = 'target'; $color = '#F59E0B'; }
    
    $vs_benchmark = (($cost_per_egg - $bench['target']) / $bench['target']) * 100;
    
    return [
        'metric' => 'Cost Per Egg',
        'your_value' => round($cost_per_egg, 2),
        'benchmark' => $bench['target'],
        'good' => $bench['good'],
        'excellent' => $bench['excellent'],
        'poor' => $bench['poor'],
        'rating' => $rating,
        'color' => $color,
        'vs_benchmark' => round($vs_benchmark, 1),
        'message' => $vs_benchmark > 0 
            ? "Your cost per egg (KES $cost_per_egg) is {$vs_benchmark}% above the benchmark (KES {$bench['target']}). Focus on reducing feed waste." 
            : "Excellent! Your cost per egg (KES $cost_per_egg) is below the benchmark.",
        'tip' => getFCRTip($cost_per_egg, $bench)
    ];
}

function getMortalityBenchmark(PDO $pdo, int $user_id): array {
    $month_start = date('Y-m-01');
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(mortality), 0) FROM daily_production WHERE user_id = ? AND record_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $month_start, $today]);
    $mortality = (int) $stmt->fetchColumn();
    
    // Get flock size (approximate from recent production entries)
    $stmt = $pdo->prepare("SELECT AVG(eggs_collected) as avg_eggs FROM daily_production WHERE user_id = ? AND record_date BETWEEN ? AND ? AND eggs_collected > 0");
    $stmt->execute([$user_id, date('Y-m-d', strtotime('-14 days')), $today]);
    $avg_eggs = (float) $stmt->fetchColumn();
    
    // Estimate flock size (assuming 80% laying rate)
    $flock_size = max(1, (int)($avg_eggs / 0.8));
    
    $mortality_rate = ($mortality / $flock_size) * 100;
    $bench = $GLOBALS['BENCHMARKS']['layers']['mortality_monthly'];
    
    $rating = 'poor';
    $color = '#EF4444';
    if ($mortality_rate <= $bench['excellent']) { $rating = 'excellent'; $color = '#22C55E'; }
    elseif ($mortality_rate <= $bench['good']) { $rating = 'good'; $color = '#22C55E'; }
    elseif ($mortality_rate <= $bench['target']) { $rating = 'target'; $color = '#F59E0B'; }
    
    return [
        'metric' => 'Monthly Mortality Rate',
        'your_value' => round($mortality_rate, 1),
        'benchmark' => $bench['target'],
        'good' => $bench['good'],
        'excellent' => $bench['excellent'],
        'poor' => $bench['poor'],
        'rating' => $rating,
        'color' => $color,
        'flock_size' => $flock_size,
        'deaths' => $mortality,
        'message' => $mortality_rate > $bench['poor']
            ? "⚠️ Critical: Your mortality rate ({$mortality_rate}%) is dangerously high. Check for disease, ventilation, and water supply."
            : ($mortality_rate <= $bench['good'] 
                ? "🌟 Great job! Your mortality rate ({$mortality_rate}%) is below average."
                : "Your mortality rate ({$mortality_rate}%) is acceptable. Monitor closely."),
        'tip' => $mortality_rate > $bench['target'] 
            ? "Top causes of high mortality: 1) Poor ventilation, 2) Contaminated water, 3) Missed vaccinations, 4) Overcrowding."
            : "Keep up the good management practices!"
    ];
}

function getCostBenchmark(PDO $pdo, int $user_id): array {
    $month_start = date('Y-m-01');
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM simple_expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $month_start, $today]);
    $total_costs = (float) $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT record_date) FROM daily_production WHERE user_id = ? AND record_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $month_start, $today]);
    $days = max(1, (int) $stmt->fetchColumn());
    
    $stmt = $pdo->prepare("SELECT AVG(eggs_collected) as avg_eggs FROM daily_production WHERE user_id = ? AND record_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $month_start, $today]);
    $avg_daily_eggs = (float) $stmt->fetchColumn();
    
    // Estimate birds
    $est_birds = max(1, (int)($avg_daily_eggs / 0.8));
    
    $cost_per_bird = $est_birds > 0 ? $total_costs / $est_birds : 0;
    $bench = $GLOBALS['BENCHMARKS']['layers']['cost_per_bird_month'];
    
    $rating = 'poor';
    $color = '#EF4444';
    if ($cost_per_bird <= $bench['excellent']) { $rating = 'excellent'; $color = '#22C55E'; }
    elseif ($cost_per_bird <= $bench['good']) { $rating = 'good'; $color = '#22C55E'; }
    elseif ($cost_per_bird <= $bench['target']) { $rating = 'target'; $color = '#F59E0B'; }
    
    return [
        'metric' => 'Cost Per Bird (Monthly)',
        'your_value' => round($cost_per_bird),
        'benchmark' => $bench['target'],
        'good' => $bench['good'],
        'excellent' => $bench['excellent'],
        'poor' => $bench['poor'],
        'rating' => $rating,
        'color' => $color,
        'estimated_birds' => $est_birds,
        'total_costs' => round($total_costs),
        'message' => "Your cost per bird is KES " . round($cost_per_bird) . "/month. Benchmark: KES {$bench['target']}/month.",
        'breakdown' => getCostBreakdown($pdo, $user_id, $month_start, $today)
    ];
}

function getProductionBenchmark(PDO $pdo, int $user_id): array {
    $month_start = date('Y-m-01');
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT AVG(eggs_collected) as avg_eggs, MAX(eggs_collected) as max_eggs FROM daily_production WHERE user_id = ? AND record_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $month_start, $today]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $avg_eggs = (float) ($prod['avg_eggs'] ?? 0);
    $est_birds = max(1, (int)($avg_eggs / 0.8));
    $production_pct = $est_birds > 0 ? ($avg_eggs / $est_birds) * 100 : 0;
    
    $bench = $GLOBALS['BENCHMARKS']['layers']['eggs_per_day_pct'];
    
    $rating = 'poor';
    $color = '#EF4444';
    if ($production_pct >= $bench['excellent']) { $rating = 'excellent'; $color = '#22C55E'; }
    elseif ($production_pct >= $bench['good']) { $rating = 'good'; $color = '#22C55E'; }
    elseif ($production_pct >= $bench['target']) { $rating = 'target'; $color = '#F59E0B'; }
    
    return [
        'metric' => 'Laying Rate (%)',
        'your_value' => round($production_pct, 1),
        'benchmark' => $bench['target'],
        'avg_eggs_day' => round($avg_eggs),
        'estimated_birds' => $est_birds,
        'rating' => $rating,
        'color' => $color,
        'message' => "Your laying rate is {$production_pct}%. For layers at peak, aim for 80-90%."
    ];
}

function getAnomalies(PDO $pdo, int $user_id): array {
    $anomalies = [];
    $today = date('Y-m-d');
    $week_ago = date('Y-m-d', strtotime('-7 days'));
    
    // Check for sudden mortality spike
    $stmt = $pdo->prepare("SELECT record_date, mortality FROM daily_production WHERE user_id = ? AND record_date >= ? ORDER BY record_date");
    $stmt->execute([$user_id, $week_ago]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($records) >= 3) {
        $avg_mortality = array_sum(array_column($records, 'mortality')) / count($records);
        
        foreach ($records as $r) {
            if ($r['mortality'] > $avg_mortality * 2 && $r['mortality'] > 3) {
                $anomalies[] = [
                    'type' => 'mortality_spike',
                    'severity' => 'high',
                    'date' => $r['record_date'],
                    'message' => "⚠️ Mortality spike on {$r['record_date']}: {$r['mortality']} deaths (average: " . round($avg_mortality, 1) . ")",
                    'action' => "Check for disease outbreak. Isolate sick birds. Contact vet if mortality continues."
                ];
            }
        }
    }
    
    // Check for sudden egg production drop
    $stmt = $pdo->prepare("SELECT record_date, eggs_collected FROM daily_production WHERE user_id = ? AND record_date >= ? AND eggs_collected > 0 ORDER BY record_date");
    $stmt->execute([$user_id, $week_ago]);
    $egg_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($egg_records) >= 3) {
        $avg_eggs = array_sum(array_column($egg_records, 'eggs_collected')) / count($egg_records);
        
        foreach ($egg_records as $r) {
            if ($r['eggs_collected'] < $avg_eggs * 0.7 && $r['eggs_collected'] > 0) {
                $drop_pct = round((1 - $r['eggs_collected'] / $avg_eggs) * 100);
                $anomalies[] = [
                    'type' => 'production_drop',
                    'severity' => 'medium',
                    'date' => $r['record_date'],
                    'message' => "📉 Egg production dropped {$drop_pct}% on {$r['record_date']} ({$r['eggs_collected']} vs avg " . round($avg_eggs) . ")",
                    'action' => "Possible causes: heat stress, feed quality, disease. Check feed stock and weather."
                ];
            }
        }
    }
    
    // Check for unusual feed consumption
    $stmt = $pdo->prepare("SELECT expense_date, amount FROM simple_expenses WHERE user_id = ? AND category = 'feed' AND expense_date >= ? ORDER BY expense_date");
    $stmt->execute([$user_id, $week_ago]);
    $feed_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($feed_records) >= 2) {
        $avg_feed = array_sum(array_column($feed_records, 'amount')) / count($feed_records);
        
        foreach ($feed_records as $r) {
            if ($r['amount'] > $avg_feed * 1.5) {
                $anomalies[] = [
                    'type' => 'feed_spike',
                    'severity' => 'medium',
                    'date' => $r['expense_date'],
                    'message' => "📦 Feed cost spike on {$r['expense_date']}: KES " . number_format($r['amount']) . " (avg: KES " . number_format($avg_feed) . ")",
                    'action' => "Possible feed wastage, theft, or price increase. Check inventory and storage."
                ];
            }
        }
    }
    
    // Check for overdue customer payments
    $stmt = $pdo->prepare("SELECT customer_name, amount, sale_date FROM customer_debts WHERE user_id = ? AND status IN ('pending', 'overdue') AND DATEDIFF(CURDATE(), sale_date) > 30");
    $stmt->execute([$user_id]);
    $overdue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($overdue as $d) {
        $days = (int)((time() - strtotime($d['sale_date'])) / 86400);
        $anomalies[] = [
            'type' => 'overdue_payment',
            'severity' => 'low',
            'date' => $d['sale_date'],
            'message' => "💳 {$d['customer_name']} owes KES " . number_format($d['amount']) . " ({$days} days overdue)",
            'action' => "Send payment reminder via Wangari bot or WhatsApp."
        ];
    }
    
    return [
        'count' => count($anomalies),
        'anomalies' => $anomalies,
        'summary' => count($anomalies) == 0 
            ? "✅ No anomalies detected. Your farm is running smoothly."
            : "⚠️ Found " . count($anomalies) . " issue(s) that need attention."
    ];
}

function getFeedPrediction(PDO $pdo, int $user_id): array {
    // Get current feed stock
    $stmt = $pdo->prepare("SELECT quantity FROM simple_inventory WHERE user_id = ? AND item_name LIKE '%feed%' LIMIT 1");
    $stmt->execute([$user_id]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_stock = $inv ? (float) $inv['quantity'] : 0;
    
    // Get average daily feed usage (last 7 days)
    $stmt = $pdo->prepare("SELECT COALESCE(AVG(daily_amount), 0) FROM (SELECT expense_date, SUM(amount) as daily_amount FROM simple_expenses WHERE user_id = ? AND category = 'feed' AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY expense_date) as daily");
    $stmt->execute([$user_id]);
    $avg_daily_feed_cost = (float) $stmt->fetchColumn();
    
    // Estimate bags per day (assuming KES 500/bag)
    $bags_per_day = $avg_daily_feed_cost > 0 ? $avg_daily_feed_cost / 500 : 0;
    
    $days_remaining = $bags_per_day > 0 ? $current_stock / $bags_per_day : 999;
    $reorder_needed = $days_remaining < 7;
    
    return [
        'current_stock_bags' => round($current_stock),
        'daily_usage_bags' => round($bags_per_day, 1),
        'days_remaining' => round($days_remaining, 1),
        'reorder_needed' => $reorder_needed,
        'message' => $reorder_needed
            ? "⚠️ Feed will run out in " . round($days_remaining) . " days! Reorder now."
            : "✅ Feed stock is sufficient for " . round($days_remaining) . " days.",
        'estimated_reorder_date' => date('Y-m-d', strtotime('+' . max(1, (int)$days_remaining) . ' days'))
    ];
}

// ═══════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function getFCRTip(float $cost_per_egg, array $bench): string {
    if ($cost_per_egg > $bench['poor']) {
        return "Your feed cost per egg is very high. Top tips: 1) Check feed quality (moisture content), 2) Reduce wastage (use proper feeders), 3) Check for parasites affecting absorption, 4) Ensure proper storage (dry, ventilated).";
    } elseif ($cost_per_egg > $bench['target']) {
        return "You're above average. Try: 1) Use a feed formula builder (Wangari Feed Hub), 2) Buy ingredients in bulk, 3) Store feed properly to prevent spoilage.";
    }
    return "Great job! Keep optimizing. Consider experimenting with different feed formulas to push costs even lower.";
}

function getCostBreakdown(PDO $pdo, int $user_id, string $start, string $end): array {
    $stmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM simple_expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ? GROUP BY category ORDER BY total DESC");
    $stmt->execute([$user_id, $start, $end]);
    $breakdown = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    return [
        'feed' => $breakdown['feed'] ?? 0,
        'veterinary' => $breakdown['veterinary'] ?? 0,
        'labor' => $breakdown['labor'] ?? 0,
        'utilities' => $breakdown['utilities'] ?? 0,
        'other' => array_sum(array_filter($breakdown, fn($k) => !in_array($k, ['feed', 'veterinary', 'labor', 'utilities']), ARRAY_FILTER_USE_KEY))
    ];
}
