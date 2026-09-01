<?php
/**
 * Wangari Daily Summary Cron Job
 * 
 * Sends daily profit summary to all active users via WhatsApp.
 * Run daily at 6pm via cron:
 *   0 18 * * * php /path/to/daily_summary_cron.php
 * 
 * Or trigger manually:
 *   GET /Backend/api/daily_summary_cron.php?test=1
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$test_mode = isset($_GET['test']);
$today = date('Y-m-d');
$month_start = date('Y-m-01');

// Get all users who have entered data in the last 7 days (active users)
$stmt = $pdo->prepare("
    SELECT DISTINCT dp.user_id 
    FROM daily_production dp 
    WHERE dp.record_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");
$stmt->execute();
$user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

$sent = 0;
$failed = 0;
$results = [];

foreach ($user_ids as $user_id) {
    $user_id = (int) $user_id;
    
    // Get user phone
    $stmt = $pdo->prepare("SELECT phone_number, full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || empty($user['phone_number'])) {
        $failed++;
        continue;
    }
    
    // Build summary
    $summary = buildDailySummary($pdo, $user_id, $today, $month_start);
    
    if ($test_mode) {
        $results[] = [
            'user_id' => $user_id,
            'name' => $user['full_name'],
            'phone' => $user['phone_number'],
            'summary' => $summary
        ];
    } else {
        // Send via WhatsApp (Evolution API)
        $sent_ok = sendWhatsAppMessage($user['phone_number'], $summary);
        if ($sent_ok) $sent++;
        else $failed++;
    }
}

echo json_encode([
    'success' => true,
    'date' => $today,
    'total_users' => count($user_ids),
    'sent' => $sent,
    'failed' => $failed,
    'test_mode' => $test_mode,
    'results' => $test_mode ? $results : null
]);

// ═══════════════════════════════════════════════════════════════
// FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function buildDailySummary(PDO $pdo, int $user_id, string $today, string $month_start): string {
    // Today's production
    $stmt = $pdo->prepare("SELECT eggs_collected, mortality, milk_litres FROM daily_production WHERE user_id = ? AND record_date = ? LIMIT 1");
    $stmt->execute([$user_id, $today]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $eggs = $prod['eggs_collected'] ?? 0;
    $mortality = $prod['mortality'] ?? 0;
    $milk = $prod['milk_litres'] ?? 0;
    
    // Today's income
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM simple_income WHERE user_id = ? AND income_date = ?");
    $stmt->execute([$user_id, $today]);
    $income = (float) $stmt->fetchColumn();
    
    // Today's expenses
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM simple_expenses WHERE user_id = ? AND expense_date = ?");
    $stmt->execute([$user_id, $today]);
    $expenses = (float) $stmt->fetchColumn();
    
    // Monthly totals
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM simple_income WHERE user_id = ? AND income_date >= ?");
    $stmt->execute([$user_id, $month_start]);
    $month_income = (float) $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM simple_expenses WHERE user_id = ? AND expense_date >= ?");
    $stmt->execute([$user_id, $month_start]);
    $month_expenses = (float) $stmt->fetchColumn();
    
    $today_profit = $income - $expenses;
    $month_profit = $month_income - $month_expenses;
    
    // Last month comparison
    $last_month = date('Y-m-01', strtotime('first day of last month'));
    $last_month_end = date('Y-m-t', strtotime('first day of last month'));
    $stmt = $pdo->prepare("SELECT (COALESCE((SELECT SUM(amount) FROM simple_income WHERE user_id = ? AND income_date BETWEEN ? AND ?), 0)) - (COALESCE((SELECT SUM(amount) FROM simple_expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ?), 0))");
    $stmt->execute([$user_id, $last_month, $last_month_end, $user_id, $last_month, $last_month_end]);
    $last_month_profit = (float) $stmt->fetchColumn();
    
    // Build message
    $name = 'Farmer';
    $stmt = $pdo->prepare("SELECT COALESCE(first_name, 'Farmer') as name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $name = $row['name'];
    
    $msg = "🌅 *Daily Summary — $today*\n";
    $msg .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    
    if ($eggs > 0) $msg .= "🥚 Eggs: *$eggs*\n";
    if ($mortality > 0) $msg .= "⚠️ Mortality: *$mortality*\n";
    if ($milk > 0) $msg .= "🥛 Milk: *" . number_format($milk, 1) . "L*\n";
    if ($eggs == 0 && $mortality == 0 && $milk == 0) $msg .= "📝 No production recorded today\n";
    
    $msg .= "\n💰 Today:\n";
    $msg .= "   Revenue: KES " . number_format($income) . "\n";
    $msg .= "   Costs: KES " . number_format($expenses) . "\n";
    $profit_icon = $today_profit >= 0 ? '✅' : '❌';
    $msg .= "   $profit_icon Profit: *KES " . number_format($today_profit) . "*\n";
    
    $msg .= "\n📊 This Month:\n";
    $msg .= "   Revenue: KES " . number_format($month_income) . "\n";
    $msg .= "   Costs: KES " . number_format($month_expenses) . "\n";
    $month_icon = $month_profit >= 0 ? '✅' : '❌';
    $msg .= "   $month_icon Profit: *KES " . number_format($month_profit) . "*\n";
    
    if ($last_month_profit != 0) {
        $change = $last_month_profit != 0 ? (($month_profit - $last_month_profit) / abs($last_month_profit)) * 100 : 0;
        $arrow = $change >= 0 ? '📈' : '📉';
        $msg .= "\n$arrow vs last month: " . ($change >= 0 ? '+' : '') . number_format($change, 1) . "%\n";
    }
    
    $msg .= "\n_Tip: Send 'summary' anytime for a full report._\n";
    $msg .= "_Send 'profit' to see your running profit._";
    
    return $msg;
}

function sendWhatsAppMessage(string $phone, string $message): bool {
    // Evolution API endpoint (self-hosted, FREE)
    $api_url = 'http://localhost:8081/message/sendText/';
    $instance_name = 'wangari';  // Your Evolution API instance name
    
    $payload = json_encode([
        'number' => $phone,
        'text' => $message
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $payload,
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($api_url . $instance_name, false, $context);
    
    if ($response === false) {
        // Log the message for retry
        error_log("Wangari: Failed to send WhatsApp to $phone: " . substr($message, 0, 50));
        return false;
    }
    
    $result = json_decode($response, true);
    return isset($result['key']['id']) || isset($result['messageId']);
}
