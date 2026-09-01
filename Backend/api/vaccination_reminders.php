<?php
/**
 * Wangari Vaccination Reminder System
 * 
 * Checks for upcoming vaccinations and generates alerts.
 * Can be called via cron job or triggered manually.
 * 
 * Schedule: Run daily at 7am via cron
 *   0 7 * * * php /path/to/vaccination_reminders.php
 * 
 * Also callable via API:
 *   GET /Backend/api/vaccination_reminders.php?action=check&user_id=123
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

$action = $_GET['action'] ?? 'check_all';
$user_id = (int)($_GET['user_id'] ?? 0);

switch ($action) {
    case 'check':
        // Check reminders for a specific user
        if ($user_id <= 0) {
            echo json_encode(['error' => 'user_id required']);
            exit;
        }
        $reminders = getUpcomingReminders($pdo, $user_id);
        echo json_encode(['reminders' => $reminders, 'count' => count($reminders)]);
        break;
    
    case 'check_all':
        // Check all users (for cron job)
        $allReminders = getAllUpcomingReminders($pdo);
        echo json_encode([
            'total_users' => count($allReminders),
            'total_reminders' => array_sum(array_map('count', $allReminders)),
            'reminders' => $allReminders
        ]);
        break;
    
    case 'mark_done':
        // Mark a vaccination as completed
        $vaccination_id = (int)($_GET['vaccination_id'] ?? 0);
        if ($vaccination_id <= 0) {
            echo json_encode(['error' => 'vaccination_id required']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE vaccinations SET status = 'completed', administered_date = CURDATE() WHERE id = ?");
        $stmt->execute([$vaccination_id]);
        echo json_encode(['success' => true]);
        break;
    
    case 'add':
        // Add a new vaccination schedule
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $flock_id = (int)($input['flock_id'] ?? 0);
        $vaccine_name = trim($input['vaccine_name'] ?? '');
        $scheduled_date = trim($input['scheduled_date'] ?? '');
        
        if ($flock_id <= 0 || empty($vaccine_name) || empty($scheduled_date)) {
            echo json_encode(['error' => 'flock_id, vaccine_name, and scheduled_date required']);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO vaccinations (flock_id, vaccine_name, scheduled_date, status) VALUES (?, ?, ?, 'scheduled')");
        $stmt->execute([$flock_id, $vaccine_name, $scheduled_date]);
        $id = $pdo->lastInsertId();
        
        echo json_encode(['success' => true, 'id' => (int)$id]);
        break;
    
    case 'schedule_defaults':
        // Auto-schedule default vaccinations for a new flock
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $flock_id = (int)($input['flock_id'] ?? 0);
        $hatch_date = trim($input['hatch_date'] ?? '');
        $bird_type = trim($input['bird_type'] ?? 'layers');
        
        if ($flock_id <= 0 || empty($hatch_date)) {
            echo json_encode(['error' => 'flock_id and hatch_date required']);
            exit;
        }
        
        $schedule = getDefaultSchedule($bird_type, $hatch_date);
        $inserted = 0;
        
        foreach ($schedule as $vax) {
            $stmt = $pdo->prepare("INSERT INTO vaccinations (flock_id, vaccine_name, scheduled_date, status) VALUES (?, ?, ?, 'scheduled')");
            $stmt->execute([$flock_id, $vax['name'], $vax['date']]);
            $inserted++;
        }
        
        echo json_encode(['success' => true, 'scheduled' => $inserted, 'schedule' => $schedule]);
        break;
    
    default:
        echo json_encode(['error' => 'Unknown action']);
}

// ═══════════════════════════════════════════════════════════════
// FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function getUpcomingReminders(PDO $pdo, int $user_id): array {
    $stmt = $pdo->prepare("
        SELECT v.id, v.vaccine_name, v.scheduled_date, v.status,
               f.name as flock_name, f.bird_type,
               DATEDIFF(v.scheduled_date, CURDATE()) as days_until
        FROM vaccinations v
        JOIN flocks f ON v.flock_id = f.id
        WHERE f.user_id = ? 
        AND v.status = 'scheduled'
        AND v.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
        ORDER BY v.scheduled_date ASC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllUpcomingReminders(PDO $pdo): array {
    $stmt = $pdo->prepare("
        SELECT v.id, v.vaccine_name, v.scheduled_date, v.status,
               f.name as flock_name, f.bird_type, f.user_id,
               u.full_name, u.email, u.phone_number,
               DATEDIFF(v.scheduled_date, CURDATE()) as days_until
        FROM vaccinations v
        JOIN flocks f ON v.flock_id = f.id
        JOIN users u ON f.user_id = u.id
        WHERE v.status = 'scheduled'
        AND v.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY v.scheduled_date ASC
    ");
    $stmt->execute();
    
    $by_user = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $uid = (int)$row['user_id'];
        if (!isset($by_user[$uid])) {
            $by_user[$uid] = [
                'user_name' => $row['full_name'],
                'email' => $row['email'],
                'phone' => $row['phone_number'],
                'reminders' => []
            ];
        }
        $by_user[$uid]['reminders'][] = [
            'id' => (int)$row['id'],
            'vaccine' => $row['vaccine_name'],
            'date' => $row['scheduled_date'],
            'flock' => $row['flock_name'],
            'days_until' => (int)$row['days_until']
        ];
    }
    
    return $by_user;
}

function getDefaultSchedule(string $bird_type, string $hatch_date): array {
    $date = new DateTime($hatch_date);
    $schedule = [];
    
    if ($bird_type === 'layers' || $bird_type === 'broilers') {
        $vaccinations = [
            ['name' => 'Marek\'s Disease', 'day' => 0, 'note' => 'At hatchery'],
            ['name' => 'Newcastle Disease (NDV) — Eye Drop', 'day' => 1, 'note' => 'Day 1'],
            ['name' => 'Infectious Bronchitis (IB)', 'day' => 7, 'note' => 'Week 1'],
            ['name' => 'Newcastle + IB Booster', 'day' => 14, 'note' => 'Week 2'],
            ['name' => 'Gumboro (IBD)', 'day' => 14, 'note' => 'Week 2 — Drinking water'],
            ['name' => 'Fowl Pox', 'day' => 28, 'note' => 'Week 4 — Wing web'],
            ['name' => 'Newcastle Booster', 'day' => 35, 'note' => 'Week 5'],
            ['name' => 'Gumboro Booster', 'day' => 42, 'note' => 'Week 6'],
            ['name' => 'Newcastle + IB Final', 'day' => 56, 'note' => 'Week 8'],
        ];
        
        if ($bird_type === 'layers') {
            $vaccinations[] = ['name' => 'Fowl Pox Booster (at point of lay)', 'day' => 120, 'note' => '~4 months'];
        }
    } else {
        // Default for other types
        $vaccinations = [
            ['name' => 'Newcastle Disease', 'day' => 7, 'note' => 'Week 1'],
            ['name' => 'Newcastle Booster', 'day' => 28, 'note' => 'Week 4'],
        ];
    }
    
    foreach ($vaccinations as $vax) {
        $sched_date = clone $date;
        $sched_date->modify("+{$vax['day']} days");
        $schedule[] = [
            'name' => $vax['name'],
            'date' => $sched_date->format('Y-m-d'),
            'note' => $vax['note'],
            'day' => $vax['day']
        ];
    }
    
    return $schedule;
}
