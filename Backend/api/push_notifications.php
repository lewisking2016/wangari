<?php
/**
 * Wangari Push Notification System
 * 
 * Sends notifications for:
 * - Vaccination reminders (2 days before due)
 * - Low stock alerts (below reorder point)
 * - Payment reminders (overdue customer debts)
 * - Weekly profit summary
 * - Inactive user re-engagement
 * 
 * Run via cron:
 *   */30 * * * * php /path/to/push_notifications.php  (every 30 min for urgent alerts)
 *   0 7 * * * php /path/to/push_notifications.php?action=daily  (daily at 7am)
 *   0 18 * * * php /path/to/push_notifications.php?action=summary  (daily at 6pm)
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? 'check';
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$in_2_days = date('Y-m-d', strtotime('+2 days'));

$notifications = [];

switch ($action) {
    case 'check':
        // Check all notification types
        $notifications = array_merge(
            checkVaccinationReminders($pdo, $today, $in_2_days),
            checkLowStock($pdo),
            checkOverduePayments($pdo, $today),
            checkInactiveUsers($pdo, $today)
        );
        break;
    
    case 'daily':
        // Daily morning alerts (7am)
        $notifications = array_merge(
            checkVaccinationReminders($pdo, $today, $in_2_days),
            checkLowStock($pdo),
            checkOverduePayments($pdo, $today)
        );
        break;
    
    case 'summary':
        // Evening profit summary (6pm)
        $notifications = generateDailySummaries($pdo, $today);
        break;
    
    case 'send':
        // Send a specific notification
        $notif_id = (int)($_GET['notif_id'] ?? 0);
        $user_id = (int)($_GET['user_id'] ?? 0);
        if ($notif_id > 0 && $user_id > 0) {
            $result = sendNotification($pdo, $user_id, $notif_id);
            echo json_encode($result);
            exit;
        }
        break;
    
    case 'send_all':
        // Send all pending notifications
        $sent = sendAllPending($pdo);
        echo json_encode(['sent' => $sent]);
        exit;
}

// Store notifications in database for later sending
$inserted = 0;
foreach ($notifications as $n) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO notifications (user_id, type, title, message, priority, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$n['user_id'], $n['type'], $n['title'], $n['message'], $n['priority']]);
    if ($stmt->rowCount() > 0) $inserted++;
}

echo json_encode([
    'action' => $action,
    'notifications_found' => count($notifications),
    'inserted' => $inserted,
    'notifications' => $notifications
]);

// ═══════════════════════════════════════════════════════════════
// CHECK FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function checkVaccinationReminders(PDO $pdo, string $today, string $in_2_days): array {
    $stmt = $pdo->prepare("
        SELECT v.id, v.vaccine_name, v.scheduled_date, v.flock_id,
               f.name as flock_name, f.user_id,
               DATEDIFF(v.scheduled_date, ?) as days_until
        FROM vaccinations v
        JOIN flocks f ON v.flock_id = f.id
        WHERE v.status = 'scheduled'
        AND v.scheduled_date BETWEEN ? AND ?
        ORDER BY v.scheduled_date ASC
    ");
    $stmt->execute([$today, $today, $in_2_days]);
    $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $notifications = [];
    foreach ($upcoming as $v) {
        $days = (int) $v['days_until'];
        $urgency = $days <= 1 ? 'urgent' : 'high';
        $icon = $days <= 1 ? '🔴' : '🟡';
        
        $notifications[] = [
            'user_id' => (int) $v['user_id'],
            'type' => 'vaccination',
            'title' => "$icon Vaccination Due " . ($days <= 1 ? 'Tomorrow' : "in $days Days"),
            'message' => "{$v['vaccine_name']} is due for {$v['flock_name']} on {$v['scheduled_date']}. Don't miss it — prevention is cheaper than treatment.",
            'priority' => $urgency,
            'action_url' => "/Frontend/admin/hub_poultry.php"
        ];
    }
    
    return $notifications;
}

function checkLowStock(PDO $pdo): array {
    $stmt = $pdo->prepare("
        SELECT id, user_id, item_name, quantity, reorder_point 
        FROM simple_inventory 
        WHERE reorder_point > 0 AND quantity <= reorder_point
    ");
    $stmt->execute();
    $low_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $notifications = [];
    foreach ($low_items as $item) {
        $notifications[] = [
            'user_id' => (int) $item['user_id'],
            'type' => 'low_stock',
            'title' => "📦 Low Stock: {$item['item_name']}",
            'message' => "{$item['item_name']} is running low ({$item['quantity']} left, reorder at {$item['reorder_point']}). Order now to avoid running out.",
            'priority' => 'high',
            'action_url' => "/Frontend/admin/hub_stock.php"
        ];
    }
    
    return $notifications;
}

function checkOverduePayments(PDO $pdo, string $today): array {
    $stmt = $pdo->prepare("
        SELECT id, user_id, customer_name, amount, sale_date,
               DATEDIFF(?, sale_date) as days_overdue
        FROM customer_debts
        WHERE status IN ('pending', 'overdue')
        AND DATEDIFF(?, sale_date) > 14
        ORDER BY sale_date ASC
    ");
    $stmt->execute([$today, $today]);
    $overdue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $notifications = [];
    foreach ($overdue as $d) {
        $days = (int) $d['days_overdue'];
        $urgency = $days > 30 ? 'urgent' : 'medium';
        $icon = $days > 30 ? '🔴' : '🟡';
        
        $notifications[] = [
            'user_id' => (int) $d['user_id'],
            'type' => 'payment_overdue',
            'title' => "$icon Payment Overdue: {$d['customer_name']}",
            'message' => "{$d['customer_name']} owes KES " . number_format($d['amount']) . " ({$days} days overdue). Send a reminder to collect.",
            'priority' => $urgency,
            'action_url' => "/Frontend/admin/hub_sales.php"
        ];
    }
    
    return $notifications;
}

function checkInactiveUsers(PDO $pdo, string $today): array {
    // Users who haven't logged data in 3+ days
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.phone_number,
               MAX(dp.record_date) as last_entry,
               DATEDIFF(?, MAX(dp.record_date)) as days_inactive
        FROM users u
        LEFT JOIN daily_production dp ON u.id = dp.user_id
        LEFT JOIN farm_members fm ON u.id = fm.user_id
        WHERE fm.role = 'farm_owner'
        GROUP BY u.id
        HAVING days_inactive >= 3 OR last_entry IS NULL
        ORDER BY days_inactive DESC
        LIMIT 50
    ");
    $stmt->execute([$today]);
    $inactive = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $notifications = [];
    foreach ($inactive as $u) {
        $days = $u['days_inactive'] ?? 7;
        $notifications[] = [
            'user_id' => (int) $u['id'],
            'type' => 're_engagement',
            'title' => "👋 We miss you, " . ($u['full_name'] ?? 'Farmer') . "!",
            'message' => "You haven't logged data for {$days} days. Your farm data is waiting! Send 'summary' on WhatsApp to see your latest numbers.",
            'priority' => 'low',
            'action_url' => null
        ];
    }
    
    return $notifications;
}

// ═══════════════════════════════════════════════════════════════
// DAILY SUMMARY GENERATOR
// ═══════════════════════════════════════════════════════════════

function generateDailySummaries(PDO $pdo, string $today): array {
    // Get active users (entered data in last 7 days)
    $stmt = $pdo->prepare("
        SELECT DISTINCT dp.user_id 
        FROM daily_production dp 
        WHERE dp.record_date >= DATE_SUB(?, INTERVAL 7 DAY)
    ");
    $stmt->execute([$today]);
    $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $notifications = [];
    foreach ($user_ids as $uid) {
        $summary = buildQuickSummary($pdo, (int)$uid, $today);
        $notifications[] = [
            'user_id' => (int) $uid,
            'type' => 'daily_summary',
            'title' => "📊 Daily Summary — $today",
            'message' => $summary,
            'priority' => 'info',
            'action_url' => null
        ];
    }
    
    return $notifications;
}

function buildQuickSummary(PDO $pdo, int $user_id, string $today): string {
    $month_start = date('Y-m-01');
    
    // Today's data
    $stmt = $pdo->prepare("SELECT eggs_collected, mortality FROM daily_production WHERE user_id = ? AND record_date = ? LIMIT 1");
    $stmt->execute([$user_id, $today]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $eggs = $prod['eggs_collected'] ?? 0;
    $mortality = $prod['mortality'] ?? 0;
    
    // Profit
    $stmt = $pdo->prepare("SELECT (SELECT COALESCE(SUM(amount),0) FROM simple_income WHERE user_id=? AND income_date=?) - (SELECT COALESCE(SUM(amount),0) FROM simple_expenses WHERE user_id=? AND expense_date=?) as profit");
    $stmt->execute([$user_id, $today, $user_id, $today]);
    $profit = (float) $stmt->fetchColumn();
    
    $msg = "";
    if ($eggs > 0) $msg .= "Eggs: $eggs | ";
    if ($mortality > 0) $msg .= "Mortality: $mortality | ";
    $msg .= "Profit: KES " . number_format($profit);
    
    return $msg;
}

// ═══════════════════════════════════════════════════════════════
// SEND FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function sendNotification(PDO $pdo, int $user_id, int $notif_id): array {
    // Get notification
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ? AND user_id = ? AND sent = 0 LIMIT 1");
    $stmt->execute([$notif_id, $user_id]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$notif) {
        return ['error' => 'Notification not found or already sent'];
    }
    
    // Get user phone
    $stmt = $pdo->prepare("SELECT phone_number FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || empty($user['phone_number'])) {
        return ['error' => 'User has no phone number'];
    }
    
    // Send via WhatsApp (Evolution API)
    $message = "*{$notif['title']}*\n\n{$notif['message']}";
    
    $sent = sendWhatsApp($user['phone_number'], $message);
    
    if ($sent) {
        $pdo->prepare("UPDATE notifications SET sent = 1, sent_at = NOW() WHERE id = ?")->execute([$notif_id]);
        return ['success' => true, 'sent_via' => 'whatsapp'];
    }
    
    return ['error' => 'Failed to send'];
}

function sendAllPending(PDO $pdo): int {
    $stmt = $pdo->prepare("SELECT id, user_id FROM notifications WHERE sent = 0 AND priority IN ('urgent', 'high') ORDER BY created_at ASC LIMIT 50");
    $stmt->execute();
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $sent = 0;
    foreach ($pending as $n) {
        $result = sendNotification($pdo, (int)$n['user_id'], (int)$n['id']);
        if (isset($result['success'])) $sent++;
    }
    
    return $sent;
}

function sendWhatsApp(string $phone, string $message): bool {
    $api_url = 'http://localhost:8081/message/sendText/';
    $instance_name = 'wangari';
    
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
    if ($response === false) return false;
    
    $result = json_decode($response, true);
    return isset($result['key']['id']) || isset($result['messageId']);
}
