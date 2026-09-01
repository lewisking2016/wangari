<?php
/**
 * Wangari USSD Bot
 * 
 * For farmers with basic feature phones (no smartphone needed).
 * Works on ANY phone — no internet required.
 * 
 * USSD Menu Tree:
 * *123# (or configured short code)
 * 
 * 1. Enter Production
 * 2. View Summary
 * 3. View Stock
 * 4. Get Advice
 * 5. My Account
 * 
 * Integration: Africa's Talking USSD gateway
 * Endpoint: POST /Backend/api/ussd_bot.php
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "END Could not connect to system. Try again later.";
    exit;
}

// Africa's Talking USSD sends these parameters
$sessionId = $_POST['sessionId'] ?? $_GET['sessionId'] ?? '';
$serviceCode = $_POST['serviceCode'] ?? $_GET['serviceCode'] ?? '';
$phoneNumber = $_POST['phoneNumber'] ?? $_GET['phoneNumber'] ?? '';
$text = $_POST['text'] ?? $_GET['text'] ?? '';

// Parse USSD input
$parts = explode('*', $text);
$action = $parts[0] ?? '';
$subAction = $parts[1] ?? '';
$subSubAction = $parts[2] ?? '';
$nodeValue = $parts[3] ?? '';

// Find user by phone
$user = findUserByPhone($pdo, $phoneNumber);

if (!$user) {
    // New user — show welcome
    if ($action === '' || $action === '1') {
        echo "CON Welcome to Wangari! 🌱\n";
        echo "The free farm management system.\n\n";
        echo "1. Register (Free)\n";
        echo "2. I already have an account\n";
        echo "3. What is Wangari?";
        exit;
    }
    
    if ($action === '1' && $subAction === '') {
        echo "CON To register, we need:\n";
        echo "Your full name:";
        exit;
    }
    
    if ($action === '1' && $subAction === '1') {
        // Register step 2 — phone is already captured
        $fullName = $subAction;
        echo "CON Great, $fullName!\n\n";
        echo "What do you farm?\n";
        echo "1. Poultry\n";
        echo "2. Dairy/Cattle\n";
        echo "3. Crops\n";
        echo "4. Mixed";
        exit;
    }
    
    if ($action === '2') {
        echo "CON Enter your Wangari PIN:\n";
        echo "(If you forgot it, call our support)";
        exit;
    }
    
    echo "END Thank you for your interest in Wangari!\n";
    echo "Visit wangari.imeantech.com to sign up.\n";
    echo "Or SMS 'JOIN' to 22345.";
    exit;
}

// ══════════════════════════════════════════════════════════════
// REGISTERED USER — MAIN MENU
// ══════════════════════════════════════════════════════════════

$userId = (int) $user['id'];

if ($action === '' || $action === '0') {
    // Main Menu
    echo "CON Welcome back!\n";
    echo "What would you like to do?\n\n";
    echo "1. Enter Production\n";
    echo "2. View Summary\n";
    echo "3. View Stock\n";
    echo "4. Get Advice\n";
    echo "5. My Account";
    exit;
}

// ══════════════════════════════════════════════════════════════
// 1. ENTER PRODUCTION
// ══════════════════════════════════════════════════════════════

if ($action === '1') {
    if ($subAction === '') {
        echo "CON What would you like to enter?\n\n";
        echo "1. Eggs collected\n";
        echo "2. Mortality\n";
        echo "3. Milk (litres)\n";
        echo "4. Feed used (bags)\n";
        echo "5. Sale\n";
        echo "6. Back to menu";
        exit;
    }
    
    if ($subAction === '1') {
        // Eggs
        if ($subSubAction === '') {
            echo "CON Enter number of eggs collected today:";
            exit;
        }
        $qty = (int) $subSubAction;
        if ($qty > 0) {
            recordProduction($pdo, $userId, 'eggs_collected', $qty);
            echo "CON ✅ Recorded: $qty eggs\n\n";
            echo "1. Enter more\n";
            echo "2. Main menu";
            exit;
        }
        echo "CON ❌ Invalid number. Enter eggs (e.g., 40):";
        exit;
    }
    
    if ($subAction === '2') {
        // Mortality
        if ($subSubAction === '') {
            echo "CON Enter number of mortalities:";
            exit;
        }
        $qty = (int) $subSubAction;
        if ($qty >= 0) {
            recordProduction($pdo, $userId, 'mortality', $qty);
            $msg = $qty > 0 ? "⚠️ Recorded: $qty deaths" : "✅ No mortality today";
            echo "CON $msg\n\n";
            echo "1. Enter more\n";
            echo "2. Main menu";
            exit;
        }
        echo "CON ❌ Enter a valid number:";
        exit;
    }
    
    if ($subAction === '3') {
        // Milk
        if ($subSubAction === '') {
            echo "CON Enter milk yield (litres, e.g., 15):";
            exit;
        }
        $qty = (float) $subSubAction;
        if ($qty > 0) {
            recordProduction($pdo, $userId, 'milk_litres', $qty);
            echo "CON ✅ Recorded: $qty litres\n\n";
            echo "1. Enter more\n";
            echo "2. Main menu";
            exit;
        }
        echo "CON ❌ Enter valid litres:";
        exit;
    }
    
    if ($subAction === '4') {
        // Feed
        if ($subSubAction === '') {
            echo "CON Enter bags of feed used:";
            exit;
        }
        $bags = (int) $subSubAction;
        if ($bags > 0) {
            $cost = $bags * 500;
            recordExpense($pdo, $userId, 'feed', "$bags bags feed", $cost);
            echo "CON ✅ Logged: $bags bags (KES " . number_format($cost) . ")\n\n";
            echo "1. Enter more\n";
            echo "2. Main menu";
            exit;
        }
        echo "CON ❌ Enter valid number of bags:";
        exit;
    }
    
    if ($subAction === '5') {
        // Sale
        if ($subSubAction === '' && $nodeValue === '') {
            echo "CON Enter quantity sold (e.g., 10):";
            exit;
        }
        $qty = (int) $subSubAction;
        if ($qty > 0) {
            recordIncome($pdo, $userId, 'sale', "Sold $qty units", $qty * 400);
            echo "CON ✅ Recorded sale: $qty units\n\n";
            echo "1. Enter more\n";
            echo "2. Main menu";
            exit;
        }
        echo "CON ❌ Enter valid quantity:";
        exit;
    }
    
    if ($subAction === '6') {
        // Back to menu
        echo "CON Main Menu\n\n1. Enter Production\n2. View Summary\n3. View Stock\n4. Get Advice\n5. My Account";
        exit;
    }
}

// ══════════════════════════════════════════════════════════════
// 2. VIEW SUMMARY
// ══════════════════════════════════════════════════════════════

if ($action === '2') {
    if ($subAction === '') {
        $today = date('Y-m-d');
        $month_start = date('Y-m-01');
        
        // Today's production
        $stmt = $pdo->prepare("SELECT eggs_collected, mortality, milk_litres FROM daily_production WHERE user_id = ? AND record_date = ? LIMIT 1");
        $stmt->execute([$userId, $today]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $eggs = $prod['eggs_collected'] ?? 0;
        $mortality = $prod['mortality'] ?? 0;
        $milk = $prod['milk_litres'] ?? 0;
        
        // Today's profit
        $stmt = $pdo->prepare("SELECT (SELECT COALESCE(SUM(amount),0) FROM simple_income WHERE user_id=? AND income_date=?) - (SELECT COALESCE(SUM(amount),0) FROM simple_expenses WHERE user_id=? AND expense_date=?) as profit");
        $stmt->execute([$userId, $today, $userId, $today]);
        $profit = (float) $stmt->fetchColumn();
        
        echo "CON TODAY ($today)\n";
        echo "━━━━━━━━━━━━━━━━━━━━\n";
        if ($eggs > 0) echo "Eggs: $eggs\n";
        if ($mortality > 0) echo "Mortality: $mortality\n";
        if ($milk > 0) echo "Milk: " . number_format($milk, 1) . "L\n";
        if ($eggs == 0 && $mortality == 0 && $milk == 0) echo "No data entered today\n";
        echo "\nProfit: KES " . number_format($profit) . "\n\n";
        echo "1. This week\n";
        echo "2. This month\n";
        echo "3. Back";
        exit;
    }
    
    if ($subAction === '1') {
        // Weekly summary
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $today = date('Y-m-d');
        
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(eggs_collected), 0) as eggs FROM daily_production WHERE user_id = ? AND record_date BETWEEN ? AND ?");
        $stmt->execute([$userId, $week_start, $today]);
        $eggs = (int) $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT (SELECT COALESCE(SUM(amount),0) FROM simple_income WHERE user_id=? AND income_date BETWEEN ? AND ?) - (SELECT COALESCE(SUM(amount),0) FROM simple_expenses WHERE user_id=? AND expense_date BETWEEN ? AND ?) as profit");
        $stmt->execute([$userId, $week_start, $today, $userId, $week_start, $today]);
        $profit = (float) $stmt->fetchColumn();
        
        echo "CON THIS WEEK ($week_start - $today)\n";
        echo "━━━━━━━━━━━━━━━━━━━━\n";
        echo "Eggs: " . number_format($eggs) . "\n";
        echo "Profit: KES " . number_format($profit) . "\n\n";
        echo "1. Back to menu";
        exit;
    }
    
    if ($subAction === '2') {
        // Monthly summary
        $month_start = date('Y-m-01');
        $today = date('Y-m-d');
        $month_name = date('F Y');
        
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(eggs_collected), 0) as eggs FROM daily_production WHERE user_id = ? AND record_date BETWEEN ? AND ?");
        $stmt->execute([$userId, $month_start, $today]);
        $eggs = (int) $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT (SELECT COALESCE(SUM(amount),0) FROM simple_income WHERE user_id=? AND income_date BETWEEN ? AND ?) - (SELECT COALESCE(SUM(amount),0) FROM simple_expenses WHERE user_id=? AND expense_date BETWEEN ? AND ?) as profit");
        $stmt->execute([$userId, $month_start, $today, $userId, $month_start, $today]);
        $profit = (float) $stmt->fetchColumn();
        
        echo "CON $month_name\n";
        echo "━━━━━━━━━━━━━━━━━━━━\n";
        echo "Eggs: " . number_format($eggs) . "\n";
        echo "Profit: KES " . number_format($profit) . "\n\n";
        echo "1. Back to menu";
        exit;
    }
}

// ══════════════════════════════════════════════════════════════
// 3. VIEW STOCK
// ══════════════════════════════════════════════════════════════

if ($action === '3') {
    $stmt = $pdo->prepare("SELECT item_name, quantity, reorder_point FROM simple_inventory WHERE user_id = ? ORDER BY item_name");
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "CON INVENTORY\n";
    echo "━━━━━━━━━━━━━━━━━━━━\n";
    
    if (empty($items)) {
        echo "No items tracked yet.\n";
    } else {
        foreach ($items as $item) {
            $status = '✅';
            if ($item['reorder_point'] > 0 && $item['quantity'] <= $item['reorder_point']) {
                $status = '⚠️ LOW';
            }
            echo "$status {$item['item_name']}: {$item['quantity']}\n";
        }
    }
    
    echo "\n1. Back to menu";
    exit;
}

// ══════════════════════════════════════════════════════════════
// 4. GET ADVICE
// ══════════════════════════════════════════════════════════════

if ($action === '4') {
    if ($subAction === '') {
        echo "CON What advice do you need?\n\n";
        echo "1. Vaccination reminder\n";
        echo "2. Market prices\n";
        echo "3. Profit tip\n";
        echo "4. Back";
        exit;
    }
    
    if ($subAction === '1') {
        // Vaccination reminder
        $stmt = $pdo->prepare("SELECT v.vaccine_name, v.scheduled_date, DATEDIFF(v.scheduled_date, CURDATE()) as days FROM vaccinations v JOIN flocks f ON v.flock_id = f.id WHERE f.user_id = ? AND v.status = 'scheduled' AND v.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY) ORDER BY v.scheduled_date LIMIT 3");
        $stmt->execute([$userId]);
        $vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "CON VACCINATION SCHEDULE\n";
        echo "━━━━━━━━━━━━━━━━━━━━\n";
        
        if (empty($vaccines)) {
            echo "No vaccinations due in next 14 days.\n";
        } else {
            foreach ($vaccines as $v) {
                $days = (int) $v['days'];
                $urgency = $days <= 1 ? '⚠️' : '📅';
                echo "$urgency {$v['vaccine_name']}\n";
                echo "   Due: {$v['scheduled_date']} ({$days} days)\n";
            }
        }
        
        echo "\n1. Back to menu";
        exit;
    }
    
    if ($subAction === '2') {
        // Market prices
        echo "CON MARKET PRICES (This Week)\n";
        echo "━━━━━━━━━━━━━━━━━━━━\n";
        echo "Eggs: KES 370-420/crate\n";
        echo "Feed: KES 4,500-5,500/bag\n";
        echo "Broiler: KES 350-450/kg\n";
        echo "Milk: KES 40-55/litre\n\n";
        echo "📈 Egg prices rising.\n\n";
        echo "1. Back to menu";
        exit;
    }
    
    if ($subAction === '3') {
        echo "CON FARMING TIP OF THE DAY\n";
        echo "━━━━━━━━━━━━━━━━━━━━\n\n";
        $tips = [
            "Clean water is the #1 factor in poultry health. Check drinkers 3x daily.",
            "Store feed in a cool, dry place. Wet feed grows mold fast.",
            "Layers need 14-16 hours of light to maintain egg production.",
            "Record everything. If it's not written down, it didn't happen.",
            "Buy feed in bulk during planting season when maize is cheap.",
            "Vaccination prevents 80% of common poultry diseases. Don't skip it.",
            "Check your mortality rate weekly. Anything above 2% needs investigation.",
            "Feed conversion ratio (FCR) tells you efficiency. Target: 1.8-2.0 for layers.",
        ];
        echo $tips[array_rand($tips)] . "\n\n";
        echo "1. Back to menu";
        exit;
    }
}

// ══════════════════════════════════════════════════════════════
// 5. MY ACCOUNT
// ══════════════════════════════════════════════════════════════

if ($action === '5') {
    $stmt = $pdo->prepare("SELECT full_name, email, phone_number FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "CON MY ACCOUNT\n";
    echo "━━━━━━━━━━━━━━━━━━━━\n";
    echo "Name: " . ($u['full_name'] ?? 'Unknown') . "\n";
    echo "Phone: " . ($u['phone_number'] ?? 'Unknown') . "\n\n";
    echo "For help, call: 0700-WANGARI\n";
    echo "Website: wangari.imeantech.com\n\n";
    echo "1. Back to menu";
    exit;
}

// Fallback
echo "CON Main Menu\n\n1. Enter Production\n2. View Summary\n3. View Stock\n4. Get Advice\n5. My Account";

// ══════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ══════════════════════════════════════════════════════════════

function findUserByPhone(PDO $pdo, string $phone): ?array {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    $variants = [$phone, '0' . substr($phone, -9), '+254' . substr($phone, -9)];
    $placeholders = implode(',', array_fill(0, count($variants), '?'));
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE phone_number IN ($placeholders) LIMIT 1");
    $stmt->execute($variants);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function recordProduction(PDO $pdo, int $userId, string $field, float $value): void {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT id FROM daily_production WHERE user_id = ? AND record_date = ? LIMIT 1");
    $stmt->execute([$userId, $today]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        $pdo->prepare("UPDATE daily_production SET $field = ? WHERE id = ?")->execute([$value, $existing['id']]);
    } else {
        $cols = ['user_id' => $userId, 'record_date' => $today, $field => $value];
        $c = implode(',', array_keys($cols));
        $p = implode(',', array_fill(0, count($cols), '?'));
        $pdo->prepare("INSERT INTO daily_production ($c) VALUES ($p)")->execute(array_values($cols));
    }
}

function recordExpense(PDO $pdo, int $userId, string $category, string $desc, float $amount): void {
    $pdo->prepare("INSERT INTO simple_expenses (user_id, expense_date, category, description, amount) VALUES (?, CURDATE(), ?, ?, ?)")
        ->execute([$userId, $category, $desc, $amount]);
}

function recordIncome(PDO $pdo, int $userId, string $category, string $desc, float $amount): void {
    $pdo->prepare("INSERT INTO simple_income (user_id, income_date, category, description, amount) VALUES (?, CURDATE(), ?, ?, ?)")
        ->execute([$userId, $category, $desc, $amount]);
}
