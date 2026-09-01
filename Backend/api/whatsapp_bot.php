<?php
/**
 * Wangari WhatsApp Bot — MVP Backend
 * 
 * Handles WhatsApp messages from farmers and returns structured responses.
 * 
 * Supported commands (V1):
 *   eggs <number>           — Log egg collection
 *   mortality <number>      — Log mortality
 *   feed <number> bags      — Log feed usage
 *   milk <number>           — Log milk yield (litres)
 *   sold <qty> <unit> @ <price> [customer] — Log sale
 *   summary                 — Today's summary
 *   week                    — Weekly summary
 *   month                   — Monthly summary
 *   stock                   — Current inventory
 *   profit                  — Running profit calculation
 *   fcr                     — Feed conversion ratio
 *   credit                  — Outstanding customer debts
 *   help                    — Show all commands
 * 
 * Endpoint: POST /Backend/api/whatsapp_bot.php
 * Body: { "phone": "+254712345678", "message": "eggs 40" }
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$phone = trim($input['phone'] ?? '');
$message = trim($input['message'] ?? '');

if (empty($phone) || empty($message)) {
    echo json_encode(['error' => 'phone and message are required']);
    exit;
}

// Normalize phone number
$phone = normalizePhone($phone);

// Find user by phone
$user = findUserByPhone($pdo, $phone);
if (!$user) {
    echo json_encode([
        'reply' => "Welcome to Wangari! 🌱\n\nYou're not registered yet. Sign up at wangari.imeantech.com to start tracking your farm profits.\n\nType 'help' after signing up to see available commands."
    ]);
    exit;
}

$user_id = (int) $user['id'];
$farm_id = (int) ($user['farm_id'] ?? 0);
$message_lower = strtolower(trim($message));

// Parse and handle commands
$response = handleCommand($pdo, $user_id, $farm_id, $message_lower, $message);

echo json_encode(['reply' => $response]);

// ═══════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function normalizePhone(string $phone): string {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if (strpos($phone, '+254') === 0) return $phone;
    if (strpos($phone, '254') === 0) return '+' . $phone;
    if (strpos($phone, '0') === 0) return '+254' . substr($phone, 1);
    return $phone;
}

function findUserByPhone(PDO $pdo, string $phone): ?array {
    // Try exact match on phone_number column
    $stmt = $pdo->prepare("SELECT id, current_farm_id as farm_id FROM users WHERE phone_number = ? LIMIT 1");
    $stmt->execute([$phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) return $user;
    
    // Try normalized variations
    $variants = [
        $phone,
        str_replace('+', '', $phone),
        '0' . substr($phone, -9),
        '+254' . substr($phone, -9),
    ];
    $placeholders = implode(',', array_fill(0, count($variants), '?'));
    $stmt = $pdo->prepare("SELECT id, current_farm_id as farm_id FROM users WHERE phone_number IN ($placeholders) LIMIT 1");
    $stmt->execute($variants);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function handleCommand(PDO $pdo, int $user_id, int $farm_id, string $cmd, string $raw): string {
    $today = date('Y-m-d');
    
    // ══════ DATA ENTRY COMMANDS ══════
    
    // eggs <number>
    if (preg_match('/^eggs?\s+(\d+)/', $cmd, $m)) {
        $qty = (int) $m[1];
        return logProduction($pdo, $user_id, $farm_id, $today, 'eggs', $qty);
    }
    
    // mortality <number>
    if (preg_match('/^mortal(ity|ities|s?)\s+(\d+)/', $cmd, $m)) {
        $qty = (int) $m[2];
        return logProduction($pdo, $user_id, $farm_id, $today, 'mortality', $qty);
    }
    
    // feed <number> bags
    if (preg_match('/^feed\s+(\d+)\s*bags?/', $cmd, $m)) {
        $bags = (int) $m[1];
        return logFeedUsage($pdo, $user_id, $farm_id, $today, $bags);
    }
    
    // milk <number>
    if (preg_match('/^milk\s+(\d+\.?\d*)/', $cmd, $m)) {
        $litres = (float) $m[1];
        return logProduction($pdo, $user_id, $farm_id, $today, 'milk', $litres);
    }
    
    // sold <qty> <unit> @ <price> [customer]
    if (preg_match('/^sold\s+(\d+)\s*(crates?|kgs?|bags?|units?)?\s*@\s*(\d+)(?:\s+(.+))?/', $cmd, $m)) {
        $qty = (int) $m[1];
        $unit = $m[2] ?? 'crates';
        $price = (int) $m[3];
        $customer = trim($m[4] ?? '');
        return logSale($pdo, $user_id, $farm_id, $today, $qty, $unit, $price, $customer);
    }
    
    // buy <item> <qty> bags @ <price>
    if (preg_match('/^buy\s+(\w+)\s+(\d+)\s*bags?\s*@\s*(\d+)/', $cmd, $m)) {
        $item = $m[1];
        $qty = (int) $m[2];
        $price = (int) $m[3];
        return logPurchase($pdo, $user_id, $farm_id, $today, $item, $qty, $price);
    }
    
    // expense <amount> <description>
    if (preg_match('/^expense[s]?\s+(\d+)\s+(.+)/', $cmd, $m)) {
        $amount = (int) $m[1];
        $desc = trim($m[2]);
        return logExpense($pdo, $user_id, $farm_id, $today, $amount, $desc);
    }
    
    // ══════ QUERY COMMANDS ══════
    
    if ($cmd === 'summary' || $cmd === 'today') {
        return getDailySummary($pdo, $user_id, $farm_id, $today);
    }
    
    if ($cmd === 'week' || $cmd === 'weekly') {
        return getWeeklySummary($pdo, $user_id, $farm_id);
    }
    
    if ($cmd === 'month' || $cmd === 'monthly') {
        return getMonthlySummary($pdo, $user_id, $farm_id);
    }
    
    if ($cmd === 'stock' || $cmd === 'inventory') {
        return getInventory($pdo, $user_id, $farm_id);
    }
    
    if ($cmd === 'profit' || $cmd === 'earnings') {
        return getProfit($pdo, $user_id, $farm_id);
    }
    
    if ($cmd === 'fcr' || $cmd === 'conversion') {
        return getFCR($pdo, $user_id, $farm_id);
    }
    
    if ($cmd === 'credit' || $cmd === 'debt' || $cmd === 'owing') {
        return getCredits($pdo, $user_id, $farm_id);
    }
    
    if ($cmd === 'mortality') {
        return getMortality($pdo, $user_id, $farm_id);
    }
    
    if ($cmd === 'help' || $cmd === 'commands') {
        return getHelp();
    }
    
    // ══════ MARKET PRICE COMMANDS ══════
    
    if (preg_match('/(price|prices|market).*egg/i', $cmd) || $cmd === 'price eggs' || $cmd === 'egg price') {
        return getMarketPrice('eggs');
    }
    
    if (preg_match('/(price|prices|market).*feed/i', $cmd) || $cmd === 'price feed' || $cmd === 'feed price') {
        return getMarketPrice('feed');
    }
    
    if (preg_match('/(price|prices|market).*(chicken|bird|poultry)/i', $cmd) || $cmd === 'price chicken') {
        return getMarketPrice('poultry');
    }
    
    if (preg_match('/(price|prices|market).*(milk|dairy)/i', $cmd) || $cmd === 'price milk') {
        return getMarketPrice('dairy');
    }
    
    if ($cmd === 'price' || $cmd === 'prices' || $cmd === 'market') {
        return getMarketPricesAll();
    }
    
    // ══════ AI ADVICE COMMANDS ══════
    
    if (preg_match('/^why.*(low|drop|less|fewer|losing).*egg/i', $cmd)) {
        return getAIFeedback($pdo, $user_id, 'eggs');
    }
    
    if (preg_match('/^why.*(high|mortality|death|dying)/i', $cmd)) {
        return getAIFeedback($pdo, $user_id, 'mortality');
    }
    
    if (preg_match('/^(when|schedule).*vaccin/i', $cmd)) {
        return getVaccinationReminder($pdo, $user_id);
    }
    
    // Unknown command
    return "Sorry, I didn't understand that. 😕\n\nTry:\n• eggs 40\n• mortality 2\n• feed 3 bags\n• summary\n• profit\n• price eggs\n• price feed\n• help";
}

// ═══════════════════════════════════════════════════════════════
// DATA ENTRY FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function logProduction(PDO $pdo, int $user_id, int $farm_id, string $date, string $type, float $value): string {
    // Check if record exists for today
    $stmt = $pdo->prepare("SELECT id, eggs_collected, mortality, milk_litres FROM daily_production WHERE user_id = ? AND record_date = ? LIMIT 1");
    $stmt->execute([$user_id, $date]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing record
        $field = null;
        switch($type) {
            case 'eggs': $field = 'eggs_collected'; break;
            case 'mortality': $field = 'mortality'; break;
            case 'milk': $field = 'milk_litres'; break;
        };
        if ($field) {
            $stmt = $pdo->prepare("UPDATE daily_production SET $field = ? WHERE id = ?");
            $stmt->execute([$value, $existing['id']]);
        }
    } else {
        // Insert new record
        $fields = ['user_id' => $user_id, 'farm_id' => $farm_id, 'record_date' => $date];
        $field = null;
        switch($type) {
            case 'eggs': $field = 'eggs_collected'; break;
            case 'mortality': $field = 'mortality'; break;
            case 'milk': $field = 'milk_litres'; break;
        };
        if ($field) {
            $fields[$field] = $value;
            $cols = implode(',', array_keys($fields));
            $ph = implode(',', array_fill(0, count($fields), '?'));
            $pdo->prepare("INSERT INTO daily_production ($cols) VALUES ($ph)")->execute(array_values($fields));
        }
    }
    
    $label = ucfirst($type);
    switch($type) {
        case 'eggs': $label = '🥚 Eggs'; break;
        case 'mortality': $label = '⚠️ Mortality'; break;
        case 'milk': $label = '🥛 Milk'; break;
    };
    
    $display_val = $type === 'milk' ? number_format($value, 1) . 'L' : (int)$value;
    
    return "✅ Recorded!\n\n$label: $display_val logged for today.\n\nSend 'summary' to see today's full report.";
}

function logFeedUsage(PDO $pdo, int $user_id, int $farm_id, string $date, int $bags): string {
    // Get cost per bag (default KES 500 if not set)
    $cost_per_bag = 500;
    $stmt = $pdo->prepare("SELECT cost_per_unit FROM inventory_items WHERE user_id = ? AND item_name LIKE '%feed%' LIMIT 1");
    $stmt->execute([$user_id]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($inv) $cost_per_bag = (float) $inv['cost_per_unit'];
    
    $total_cost = $bags * $cost_per_bag;
    
    // Log as expense
    $pdo->prepare("INSERT INTO simple_expenses (user_id, farm_id, expense_date, category, description, amount) VALUES (?, ?, ?, 'feed', ?, ?)")
        ->execute([$user_id, $farm_id, $date, "$bags bags of feed", $total_cost]);
    
    // Update inventory if exists
    $pdo->prepare("UPDATE simple_inventory SET quantity = GREATEST(quantity - ?, 0) WHERE user_id = ? AND item_name LIKE '%feed%' LIMIT 1")
        ->execute([$bags, $user_id]);
    
    // Get remaining stock
    $remaining = '?';
    $stmt = $pdo->prepare("SELECT quantity FROM simple_inventory WHERE user_id = ? AND item_name LIKE '%feed%' LIMIT 1");
    $stmt->execute([$user_id]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($inv) $remaining = (int) $inv['quantity'];
    
    return "📦 Feed logged!\n\n• Used: $bags bags\n• Cost: KES " . number_format($total_cost) . "\n• Remaining: $remaining bags\n\nSend 'stock' to see full inventory.";
}

function logSale(PDO $pdo, int $user_id, int $farm_id, string $date, int $qty, string $unit, int $price, string $customer): string {
    $total = $qty * $price;
    
    // Log as income
    $desc = "$qty $unit @ KES $price" . ($customer ? " — $customer" : '');
    $pdo->prepare("INSERT INTO simple_income (user_id, farm_id, income_date, category, description, amount, customer_name) VALUES (?, ?, ?, 'sales', ?, ?, ?)")
        ->execute([$user_id, $farm_id, $date, $desc, $total, $customer ?: null]);
    
    // Log customer debt if customer specified
    if ($customer) {
        $pdo->prepare("INSERT INTO customer_debts (user_id, farm_id, customer_name, amount, sale_date, status) VALUES (?, ?, ?, ?, ?, 'pending')")
            ->execute([$user_id, $farm_id, $customer, $total, $date]);
    }
    
    $reply = "💰 Sale recorded!\n\n• Sold: $qty $unit × KES $price\n• Total: KES " . number_format($total);
    if ($customer) {
        $reply .= "\n• Customer: $customer";
    }
    $reply .= "\n\nSend 'profit' to see your running profit.";
    return $reply;
}

function logPurchase(PDO $pdo, int $user_id, int $farm_id, string $date, string $item, int $qty, int $price): string {
    $total = $qty * $price;
    
    // Log as expense
    $pdo->prepare("INSERT INTO simple_expenses (user_id, farm_id, expense_date, category, description, amount) VALUES (?, ?, ?, 'purchase', ?, ?)")
        ->execute([$user_id, $farm_id, $date, "Bought $qty $item bags @ KES $price", $total]);
    
    // Update or insert inventory
    $stmt = $pdo->prepare("SELECT id, quantity FROM simple_inventory WHERE user_id = ? AND item_name LIKE ? LIMIT 1");
    $stmt->execute([$user_id, "%$item%"]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($inv) {
        $pdo->prepare("UPDATE simple_inventory SET quantity = quantity + ? WHERE id = ?")->execute([$qty, $inv['id']]);
    } else {
        $pdo->prepare("INSERT INTO simple_inventory (user_id, farm_id, item_name, quantity, cost_per_unit) VALUES (?, ?, ?, ?, ?)")
            ->execute([$user_id, $farm_id, ucfirst($item), $qty, $price]);
    }
    
    return "📥 Purchase logged!\n\n• Bought: $qty $item bags\n• Cost: KES " . number_format($total) . "\n• New stock: " . ($inv ? (int)$inv['quantity'] + $qty : $qty) . " bags";
}

function logExpense(PDO $pdo, int $user_id, int $farm_id, string $date, int $amount, string $desc): string {
    $pdo->prepare("INSERT INTO simple_expenses (user_id, farm_id, expense_date, category, description, amount) VALUES (?, ?, ?, 'misc', ?, ?)")
        ->execute([$user_id, $farm_id, $date, $desc, $amount]);
    
    return "💸 Expense logged!\n\n• $desc: KES " . number_format($amount) . "\n\nSend 'profit' to see your running total.";
}

// ═══════════════════════════════════════════════════════════════
// QUERY FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function getDailySummary(PDO $pdo, int $user_id, int $farm_id, string $date): string {
    // Get production
    $stmt = $pdo->prepare("SELECT eggs_collected, mortality, milk_litres FROM daily_production WHERE user_id = ? AND record_date = ? LIMIT 1");
    $stmt->execute([$user_id, $date]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get income
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM simple_income WHERE user_id = ? AND income_date = ?");
    $stmt->execute([$user_id, $date]);
    $income = (float) $stmt->fetchColumn();
    
    // Get expenses
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM simple_expenses WHERE user_id = ? AND expense_date = ?");
    $stmt->execute([$user_id, $date]);
    $expenses = (float) $stmt->fetchColumn();
    
    $eggs = $prod['eggs_collected'] ?? 0;
    $mort = $prod['mortality'] ?? 0;
    $milk = $prod['milk_litres'] ?? 0;
    $profit = $income - $expenses;
    
    $reply = "📊 TODAY ($date)\n";
    $reply .= "━━━━━━━━━━━━━━━━━━━━\n";
    
    if ($eggs > 0) $reply .= "🥚 Eggs: $eggs\n";
    if ($mort > 0) $reply .= "⚠️ Mortality: $mort\n";
    if ($milk > 0) $reply .= "🥛 Milk: " . number_format($milk, 1) . "L\n";
    if ($eggs == 0 && $mort == 0 && $milk == 0) $reply .= "📝 No production recorded yet today\n";
    
    $reply .= "\n💰 Revenue: KES " . number_format($income) . "\n";
    $reply .= "💸 Costs: KES " . number_format($expenses) . "\n";
    
    $profit_color = $profit >= 0 ? '✅' : '❌';
    $reply .= "\n$profit_color Net: KES " . number_format($profit);
    
    return $reply;
}

function getWeeklySummary(PDO $pdo, int $user_id, int $farm_id): string {
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(eggs_collected), 0) as eggs, COALESCE(SUM(mortality), 0) as mortality FROM daily_production WHERE user_id = ? AND record_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $week_start, $today]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM simple_income WHERE user_id = ? AND income_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $week_start, $today]);
    $income = (float) $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM simple_expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $week_start, $today]);
    $expenses = (float) $stmt->fetchColumn();
    
    $profit = $income - $expenses;
    $days = (int) ((strtotime($today) - strtotime($week_start)) / 86400) + 1;
    
    $reply = "📊 THIS WEEK ($week_start → $today)\n";
    $reply .= "━━━━━━━━━━━━━━━━━━━━\n";
    $reply .= "🥚 Eggs: " . number_format($prod['eggs']) . " ($days days)\n";
    $reply .= "⚠️ Mortality: " . number_format($prod['mortality']) . "\n";
    $reply .= "💰 Revenue: KES " . number_format($income) . "\n";
    $reply .= "💸 Costs: KES " . number_format($expenses) . "\n";
    
    $profit_color = $profit >= 0 ? '✅' : '❌';
    $reply .= "\n$profit_color Net Profit: KES " . number_format($profit);
    
    return $reply;
}

function getMonthlySummary(PDO $pdo, int $user_id, int $farm_id): string {
    $month_start = date('Y-m-01');
    $today = date('Y-m-d');
    $month_name = date('F Y');
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(eggs_collected), 0) as eggs, COALESCE(SUM(mortality), 0) as mortality FROM daily_production WHERE user_id = ? AND record_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $month_start, $today]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM simple_income WHERE user_id = ? AND income_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $month_start, $today]);
    $income = (float) $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM simple_expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $month_start, $today]);
    $expenses = (float) $stmt->fetchColumn();
    
    $profit = $income - $expenses;
    
    $reply = "📊 $month_name\n";
    $reply .= "━━━━━━━━━━━━━━━━━━━━\n";
    $reply .= "🥚 Eggs: " . number_format($prod['eggs']) . "\n";
    $reply .= "⚠️ Mortality: " . number_format($prod['mortality']) . "\n";
    $reply .= "💰 Revenue: KES " . number_format($income) . "\n";
    $reply .= "💸 Costs: KES " . number_format($expenses) . "\n";
    
    $profit_color = $profit >= 0 ? '✅' : '❌';
    $reply .= "\n$profit_color Net Profit: KES " . number_format($profit);
    
    // Compare to last month
    $last_month = date('Y-m-01', strtotime('first day of last month'));
    $last_month_end = date('Y-m-t', strtotime('first day of last month'));
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) - (SELECT COALESCE(SUM(amount), 0) FROM simple_expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ?) as profit FROM simple_income WHERE user_id = ? AND income_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $last_month, $last_month_end, $user_id, $last_month, $last_month_end]);
    $last_profit = (float) $stmt->fetchColumn();
    
    if ($last_profit > 0) {
        $change = (($profit - $last_profit) / $last_profit) * 100;
        $arrow = $change >= 0 ? '📈' : '📉';
        $reply .= "\n$arrow vs last month: " . ($change >= 0 ? '+' : '') . number_format($change, 1) . "%";
    }
    
    return $reply;
}

function getInventory(PDO $pdo, int $user_id, int $farm_id): string {
    $stmt = $pdo->prepare("SELECT item_name, quantity, cost_per_unit, reorder_point FROM simple_inventory WHERE user_id = ? ORDER BY item_name");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        return "📦 INVENTORY\n━━━━━━━━━━━━━━━━━━━━\nNo items tracked yet.\n\nBuy stock: buy feed 20 bags @ 500";
    }
    
    $reply = "📦 INVENTORY\n━━━━━━━━━━━━━━━━━━━━\n";
    $alerts = [];
    
    foreach ($items as $item) {
        $name = $item['item_name'];
        $qty = (int) $item['quantity'];
        $reorder = (int) ($item['reorder_point'] ?? 0);
        
        $status = '✅';
        if ($reorder > 0 && $qty <= $reorder) {
            $status = '⚠️ LOW';
            $alerts[] = $name;
        }
        if ($qty == 0) {
            $status = '❌ OUT';
        }
        
        $reply .= "$status $name: $qty\n";
    }
    
    if (!empty($alerts)) {
        $reply .= "\n⚠️ Low stock: " . implode(', ', $alerts);
        $reply .= "\nReorder soon to avoid running out!";
    }
    
    return $reply;
}

function getProfit(PDO $pdo, int $user_id, int $farm_id): string {
    $month_start = date('Y-m-01');
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM simple_income WHERE user_id = ? AND income_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $month_start, $today]);
    $income = (float) $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM simple_expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $month_start, $today]);
    $expenses = (float) $stmt->fetchColumn();
    
    $profit = $income - $expenses;
    $month_name = date('F Y');
    
    $reply = "💰 PROFIT ($month_name)\n";
    $reply .= "━━━━━━━━━━━━━━━━━━━━\n";
    $reply .= "Income:  KES " . number_format($income) . "\n";
    $reply .= "Costs:   KES " . number_format($expenses) . "\n";
    
    $profit_color = $profit >= 0 ? '✅' : '❌';
    $reply .= "\n$profit_color PROFIT: KES " . number_format($profit);
    
    if ($income > 0) {
        $margin = ($profit / $income) * 100;
        $reply .= "\n📊 Margin: " . number_format($margin, 1) . "%";
    }
    
    return $reply;
}

function getFCR(PDO $pdo, int $user_id, int $farm_id): string {
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $today = date('Y-m-d');
    
    // Get feed used this week
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM simple_expenses WHERE user_id = ? AND category = 'feed' AND expense_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $week_start, $today]);
    $feed_cost = (float) $stmt->fetchColumn();
    
    // Get eggs produced this week
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(eggs_collected), 0) FROM daily_production WHERE user_id = ? AND record_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $week_start, $today]);
    $eggs = (int) $stmt->fetchColumn();
    
    if ($eggs == 0) {
        return "📈 FCR\n━━━━━━━━━━━━━━━━━━━━\nNo production data this week.\n\nSend: eggs <number> to log today's eggs.";
    }
    
    // Simple FCR calculation: feed cost per egg
    $cost_per_egg = $eggs > 0 ? $feed_cost / $eggs : 0;
    
    $reply = "📈 FCR (This Week)\n";
    $reply .= "━━━━━━━━━━━━━━━━━━━━\n";
    $reply .= "Feed cost: KES " . number_format($feed_cost) . "\n";
    $reply .= "Eggs: " . number_format($eggs) . "\n";
    $reply .= "Cost/egg: KES " . number_format($cost_per_egg, 2) . "\n";
    
    // Benchmark (Kenya average: KES 3-5 per egg for layers)
    if ($cost_per_egg > 5) {
        $reply .= "\n⚠️ Above average. Top farms: KES 3-4/egg.\nTip: Check feed quality and wastage.";
    } elseif ($cost_per_egg < 3) {
        $reply .= "\n🌟 Excellent! Below average cost.";
    } else {
        $reply .= "\n✅ Average. Room to improve.";
    }
    
    return $reply;
}

function getMortality(PDO $pdo, int $user_id, int $farm_id): string {
    $month_start = date('Y-m-01');
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(mortality), 0) FROM daily_production WHERE user_id = ? AND record_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $month_start, $today]);
    $mortality = (int) $stmt->fetchColumn();
    
    $days = (int) ((strtotime($today) - strtotime($month_start)) / 86400) + 1;
    
    $reply = "⚠️ MORTALITY (This Month)\n";
    $reply .= "━━━━━━━━━━━━━━━━━━━━\n";
    $reply .= "Total deaths: $mortality\n";
    $reply .= "Days tracked: $days\n";
    
    if ($mortality == 0) {
        $reply .= "\n🌟 Zero mortality! Excellent management.";
    } elseif ($mortality <= 5) {
        $reply .= "\n✅ Low mortality. Keep it up.";
    } else {
        $reply .= "\n⚠️ High mortality. Check for:\n• Disease (vaccination schedule)\n• Feed quality\n• Water supply\n• Ventilation";
    }
    
    return $reply;
}

function getCredits(PDO $pdo, int $user_id, int $farm_id): string {
    $stmt = $pdo->prepare("SELECT customer_name, amount, sale_date, amount_paid FROM customer_debts WHERE user_id = ? AND status IN ('pending', 'partial', 'overdue') ORDER BY sale_date DESC");
    $stmt->execute([$user_id]);
    $debts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($debts)) {
        return "💳 CREDITS\n━━━━━━━━━━━━━━━━━━━━\nNo outstanding debts. 🎉";
    }
    
    $reply = "💳 CREDITS OWED\n";
    $reply .= "━━━━━━━━━━━━━━━━━━━━\n";
    $total = 0;
    
    foreach ($debts as $debt) {
        $days_ago = (int) ((time() - strtotime($debt['sale_date'])) / 86400);
        $status = $days_ago > 30 ? '⚠️ OVERDUE' : '📋 Pending';
        $reply .= "• {$debt['customer_name']}: KES " . number_format($debt['amount']) . " ($days_ago days) $status\n";
        $total += $debt['amount'];
    }
    
    $reply .= "\n💰 Total owed: KES " . number_format($total);
    
    return $reply;
}

function getHelp(): string {
    return "📖 WANGARI BOT COMMANDS\n━━━━━━━━━━━━━━━━━━━━\n\n📝 DATA ENTRY:\neggs 40 — Log egg count\nmortality 2 — Log deaths\nfeed 3 bags — Log feed usage\nmilk 15 — Log milk (litres)\nsold 10 crates @ 400 — Log sale\nbuy feed 20 bags @ 500 — Log purchase\nexpense 2000 transport — Log expense\n\n📊 VIEW DATA:\nsummary — Today's report\nweek — This week's summary\nmonth — This month's summary\nstock — Current inventory\nprofit — Running profit/loss\nfcr — Feed conversion ratio\nmortality — Mortality report\ncredit — Customer debts\n\n💰 MARKET PRICES:\nprice eggs — Current egg prices\nprice feed — Current feed prices\nprice chicken — Poultry prices\nprice milk — Dairy milk prices\nprice — All market prices\n\n🤖 AI QUESTIONS:\nwhy low eggs? — Production analysis\nwhy high mortality? — Health check\nwhen vaccinate? — Schedule\n\n💡 TIPS:\n• Send commands anytime\n• Data syncs to your dashboard\n• Prices updated weekly\n\nNeed help? WhatsApp us.";
}

// ═══════════════════════════════════════════════════════════════
// MARKET PRICE FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function getMarketPrice(string $item): string {
    $prices = [
        'eggs' => [
            'title' => '🥚 EGG PRICES (This Week)',
            'wholesale' => 'KES 370-420/crate',
            'retail' => 'KES 440-530/crate',
            'trend' => '📈 Rising (holiday demand approaching)',
            'tip' => 'Hold stock 1 week if possible. Prices typically rise 15-25% in Dec-Jan.',
        ],
        'feed' => [
            'title' => '📦 FEED PRICES (This Week)',
            'layers_mash' => 'KES 4,500-5,500/bag',
            'broiler_starter' => 'KES 4,800-5,800/bag',
            'broiler_finisher' => 'KES 4,200-5,200/bag',
            'trend' => '📊 Stable',
            'tip' => 'Buy in bulk during planting season (Mar-May) when maize is scarce and prices rise.',
        ],
        'poultry' => [
            'title' => '🐔 POULTRY PRICES (This Week)',
            'broiler_live' => 'KES 350-450/kg',
            'broiler_dressed' => 'KES 500-650/kg',
            'spent_layer' => 'KES 150-250/kg',
            'trend' => '📊 Stable',
            'tip' => 'Dressed birds fetch 30-40% more than live weight.',
        ],
        'dairy' => [
            'title' => '🥛 DAIRY PRICES (This Week)',
            'farm_gate' => 'KES 40-55/litre',
            'pasteurized' => 'KES 60-80/litre',
            'supermarket' => 'KES 70-95/litre',
            'trend' => '📊 Stable',
            'tip' => 'Milk prices dip during flush season (Mar-May). Store value by processing into yoghurt.',
        ],
    ];
    
    $p = $prices[$item] ?? null;
    if (!$p) return "Unknown item. Try: price eggs, price feed, price chicken, price milk";
    
    $reply = $p['title'] . "\n";
    $reply .= "━━━━━━━━━━━━━━━━━━━━\n";
    
    foreach ($p as $key => $val) {
        if (in_array($key, ['title', 'tip', 'trend'])) continue;
        $label = str_replace('_', ' ', ucfirst($key));
        $reply .= "$label: $val\n";
    }
    
    $reply .= "\n" . $p['trend'] . "\n";
    $reply .= "\n💡 " . $p['tip'];
    
    return $reply;
}

function getMarketPricesAll(): string {
    $reply = "💰 MARKET PRICES (This Week)\n";
    $reply .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    $reply .= "🥚 EGGS: KES 370-420/wholesale, 440-530/retail\n";
    $reply .= "📦 LAYERS MASH: KES 4,500-5,500/bag\n";
    $reply .= "🐔 BROILER (live): KES 350-450/kg\n";
    $reply .= "🥛 MILK (farm gate): KES 40-55/litre\n";
    $reply .= "\n📈 Egg prices rising. Feed stable.\n";
    $reply .= "\n💡 Send 'price eggs' or 'price feed' for details.";
    
    return $reply;
}

// ═══════════════════════════════════════════════════════════════
// AI FEEDBACK FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function getAIFeedback(PDO $pdo, int $user_id, string $type): string {
    $today = date('Y-m-d');
    $week_ago = date('Y-m-d', strtotime('-7 days'));
    
    if ($type === 'eggs') {
        $stmt = $pdo->prepare("SELECT AVG(eggs_collected) as avg_eggs FROM daily_production WHERE user_id = ? AND record_date BETWEEN ? AND ? AND eggs_collected > 0");
        $stmt->execute([$user_id, $week_ago, $today]);
        $avg = (float) $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT eggs_collected, record_date FROM daily_production WHERE user_id = ? AND record_date = ? LIMIT 1");
        $stmt->execute([$user_id, $today]);
        $today_prod = $stmt->fetch(PDO::FETCH_ASSOC);
        $today_eggs = $today_prod['eggs_collected'] ?? 0;
        
        if ($avg > 0 && $today_eggs < $avg * 0.7) {
            $drop = round((1 - $today_eggs / $avg) * 100);
            return "📊 AI Analysis:\n\nEgg production dropped {$drop}% today ($today_eggs vs avg " . round($avg) . ").\n\nPossible causes:\n1) Heat stress — provide shade + extra water\n2) Feed quality — check for moisture/mold\n3) Disease — check mortality rate, watch for Newcastle signs\n4) Lighting — layers need 14-16 hours of light\n\n💡 Check your 'mortality' and 'stock' to rule out disease.";
        }
        
        return "📊 Your egg production looks normal this week (avg: " . round($avg) . "/day). Keep it up!";
    }
    
    if ($type === 'mortality') {
        $stmt = $pdo->prepare("SELECT SUM(mortality) as total FROM daily_production WHERE user_id = ? AND record_date >= ?");
        $stmt->execute([$user_id, date('Y-m-01')]);
        $mortality = (int) $stmt->fetchColumn();
        
        if ($mortality > 5) {
            return "📊 AI Analysis:\n\n⚠️ High mortality this month ($mortality deaths).\n\nCheck immediately:\n1) Water supply — is it clean and available?\n2) Ventilation — is the house too hot/stuffy?\n3) Feed — any mold or contamination?\n4) Vaccination — any missed doses?\n5) New birds — did you introduce new stock?\n\n🚨 If mortality continues, contact a vet TODAY.";
        }
        
        return "📊 Your mortality rate is normal this month ($mortality deaths). Good management!";
    }
    
    return "I can help analyze your egg production or mortality trends. Try 'why low eggs?' or 'why high mortality?'";
}

function getVaccinationReminder(PDO $pdo, int $user_id): string {
    $stmt = $pdo->prepare("SELECT v.vaccine_name, v.scheduled_date, f.name as flock_name, DATEDIFF(v.scheduled_date, CURDATE()) as days_until FROM vaccinations v JOIN flocks f ON v.flock_id = f.id WHERE f.user_id = ? AND v.status = 'scheduled' AND v.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY) ORDER BY v.scheduled_date ASC");
    $stmt->execute([$user_id]);
    $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($upcoming)) {
        return "💉 No vaccinations scheduled in the next 14 days.\n\nTip: Set up a vaccination schedule in Wangari to get reminders.";
    }
    
    $reply = "💉 UPCOMING VACCINATIONS\n";
    $reply .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    
    foreach ($upcoming as $v) {
        $days = (int) $v['days_until'];
        $urgency = $days <= 2 ? '🔴' : ($days <= 5 ? '🟡' : '🟢');
        $reply .= "$urgency {$v['vaccine_name']}\n";
        $reply .= "   📅 {$v['scheduled_date']} ({$days} days)\n";
        $reply .= "   🐔 {$v['flock_name']}\n\n";
    }
    
    $reply .= "⚠️ Don't miss these! Vaccination prevents 80%+ of common diseases.";
    
    return $reply;
}
