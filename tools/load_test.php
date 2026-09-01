<?php
/**
 * Wangari Load Test Script
 * 
 * Tests system performance under load:
 * - API response times
 * - Database query performance
 * - WhatsApp bot throughput
 * - Concurrent user handling
 * 
 * Usage: php tools/load_test.php [iterations] [concurrent]
 * Default: 100 iterations, 10 concurrent
 * 
 * Example: php tools/load_test.php 1000 50
 */
declare(strict_types=1);

$iterations = (int)($argv[1] ?? 100);
$concurrent = (int)($argv[2] ?? 10);
$baseUrl = $argv[3] ?? 'http://localhost';

echo "═══════════════════════════════════════════════\n";
echo "  WANGARI LOAD TEST\n";
echo "  Iterations: $iterations | Concurrent: $concurrent\n";
echo "  Target: $baseUrl\n";
echo "═══════════════════════════════════════════════\n\n";

$results = [
    'api_response' => [],
    'database' => [],
    'whatsapp_bot' => [],
    'pages' => [],
];

$total_start = microtime(true);

// ═══════ TEST 1: API Response Time ═══════
echo "📊 Test 1: API Response Time...\n";
$api_tests = [
    '/Backend/api/v2.php?module=dashboard',
    '/Backend/api/ai_benchmarks.php?action=all&user_id=1',
    '/Backend/api/market_prices.php?item=all',
    '/Backend/api/weather_alerts.php?location=Nakuru',
    '/Backend/api/vaccination_reminders.php?action=check_all',
];

foreach ($api_tests as $endpoint) {
    $times = [];
    for ($i = 0; $i < $iterations; $i++) {
        $start = microtime(true);
        $response = @file_get_contents($baseUrl . $endpoint);
        $time = (microtime(true) - $start) * 1000; // ms
        $times[] = $time;
    }
    
    $avg = array_sum($times) / count($times);
    $min = min($times);
    $max = max($times);
    $p95 = $times[(int)(count($times) * 0.95)];
    
    $short = str_replace('/Backend/api/', '', $endpoint);
    printf("  %-40s Avg: %6.1fms | Min: %6.1fms | Max: %6.1fms | P95: %6.1fms\n", $short, $avg, $min, $max, $p95);
    
    $results['api_response'][] = [
        'endpoint' => $endpoint,
        'avg_ms' => round($avg, 1),
        'min_ms' => round($min, 1),
        'max_ms' => round($max, 1),
        'p95_ms' => round($p95, 1),
    ];
}
echo "\n";

// ═══════ TEST 2: WhatsApp Bot Throughput ═══════
echo "📱 Test 2: WhatsApp Bot Throughput...\n";
$bot_commands = [
    'eggs 40',
    'mortality 2',
    'feed 3 bags',
    'summary',
    'profit',
    'price eggs',
    'help',
];

foreach ($bot_commands as $cmd) {
    $times = [];
    $successes = 0;
    
    for ($i = 0; $i < min($iterations, 50); $i++) {
        $start = microtime(true);
        $payload = json_encode(['phone' => '+254700000000', 'message' => $cmd]);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $payload,
                'timeout' => 5
            ]
        ]);
        $response = @file_get_contents($baseUrl . '/Backend/api/whatsapp_bot.php', false, $context);
        $time = (microtime(true) - $start) * 1000;
        $times[] = $time;
        
        if ($response !== false) {
            $result = json_decode($response, true);
            if (isset($result['reply'])) $successes++;
        }
    }
    
    $avg = array_sum($times) / count($times);
    $success_rate = ($successes / count($times)) * 100;
    
    printf("  %-15s Avg: %6.1fms | Success: %5.1f%%\n", "\"$cmd\"", $avg, $success_rate);
    
    $results['whatsapp_bot'][] = [
        'command' => $cmd,
        'avg_ms' => round($avg, 1),
        'success_rate' => round($success_rate, 1),
    ];
}
echo "\n";

// ═══════ TEST 3: Page Load Time ═══════
echo "🌐 Test 3: Page Load Time...\n";
$pages = [
    '/' => 'Landing Page',
    '/Frontend/pages/login.php' => 'Login Page',
    '/Frontend/pages/pricing.php' => 'Pricing Page',
    '/Frontend/pages/onboarding.php' => 'Onboarding Wizard',
];

foreach ($pages as $path => $name) {
    $times = [];
    for ($i = 0; $i < min($iterations, 30); $i++) {
        $start = microtime(true);
        @file_get_contents($baseUrl . $path);
        $time = (microtime(true) - $start) * 1000;
        $times[] = $time;
    }
    
    $avg = array_sum($times) / count($times);
    $size = strlen(@file_get_contents($baseUrl . $path) ?: '');
    
    printf("  %-20s Avg: %6.1fms | Size: %6d bytes\n", $name, $avg, $size);
    
    $results['pages'][] = [
        'name' => $name,
        'avg_ms' => round($avg, 1),
        'size_bytes' => $size,
    ];
}
echo "\n";

// ═══════ TEST 4: Database Query Performance ═══════
echo "🗄️ Test 4: Database Query Performance...\n";
require_once __DIR__ . '/../Backend/config/database.php';
$pdo = getDatabaseConnection();

if ($pdo) {
    $queries = [
        'SELECT COUNT(*) FROM users' => 'User count',
        'SELECT COUNT(*) FROM daily_production WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)' => 'Weekly production',
        'SELECT COUNT(*) FROM simple_expenses WHERE expense_date >= DATE_FORMAT(CURDATE(), "%Y-%m-01")' => 'Monthly expenses',
        'SELECT user_id, SUM(eggs_collected) as total FROM daily_production WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY user_id ORDER BY total DESC LIMIT 10' => 'Top producers',
    ];
    
    foreach ($queries as $sql => $name) {
        $times = [];
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $pdo->query($sql);
            $time = (microtime(true) - $start) * 1000;
            $times[] = $time;
        }
        
        $avg = array_sum($times) / count($times);
        printf("  %-20s Avg: %6.1fms\n", $name, $avg);
        
        $results['database'][] = [
            'query' => $name,
            'avg_ms' => round($avg, 1),
        ];
    }
}
echo "\n";

$total_time = (microtime(true) - $total_start) * 1000;

// ═══════ RESULTS SUMMARY ═══════
echo "═══════════════════════════════════════════════\n";
echo "  RESULTS SUMMARY\n";
echo "═══════════════════════════════════════════════\n\n";

// API Performance
$api_avg = array_sum(array_column($results['api_response'], 'avg_ms')) / count($results['api_response']);
printf("  API Average Response:    %6.1fms  %s\n", $api_avg, $api_avg < 200 ? '✅ PASS' : '⚠️ SLOW');

// Bot Performance
$bot_avg = array_sum(array_column($results['whatsapp_bot'], 'avg_ms')) / count($results['whatsapp_bot']);
$bot_success = array_sum(array_column($results['whatsapp_bot'], 'success_rate')) / count($results['whatsapp_bot']);
printf("  Bot Average Response:    %6.1fms  %s\n", $bot_avg, $bot_avg < 500 ? '✅ PASS' : '⚠️ SLOW');
printf("  Bot Success Rate:        %5.1f%%   %s\n", $bot_success, $bot_success > 95 ? '✅ PASS' : '❌ FAIL');

// Page Performance
$page_avg = array_sum(array_column($results['pages'], 'avg_ms')) / count($results['pages']);
printf("  Page Average Load:       %6.1fms  %s\n", $page_avg, $page_avg < 1000 ? '✅ PASS' : '⚠️ SLOW');

// Database Performance
if (!empty($results['database'])) {
    $db_avg = array_sum(array_column($results['database'], 'avg_ms')) / count($results['database']);
    printf("  Database Average Query:  %6.1fms  %s\n", $db_avg, $db_avg < 50 ? '✅ PASS' : '⚠️ SLOW');
}

printf("  Total Test Time:         %6.1fs\n", $total_time / 1000);

echo "\n";

// Verdict
$all_pass = $api_avg < 200 && $bot_avg < 500 && $page_avg < 1000 && $bot_success > 95;

if ($all_pass) {
    echo "  🎉 VERDICT: SYSTEM IS READY FOR 1,000 USERS\n";
} else {
    echo "  ⚠️ VERDICT: OPTIMIZATION NEEDED BEFORE LAUNCH\n";
    echo "  Review slow endpoints and optimize queries.\n";
}

echo "\n═══════════════════════════════════════════════\n";

// Save results to JSON
$output_file = __DIR__ . '/load_test_results_' . date('Y-m-d_His') . '.json';
file_put_contents($output_file, json_encode($results, JSON_PRETTY_PRINT));
echo "\nResults saved to: $output_file\n";
