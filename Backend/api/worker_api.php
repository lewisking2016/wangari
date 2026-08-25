<?php
/**
 * Worker API - Notifications, Tasks, Reminders
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../Frontend/includes/config.php';

header('Content-Type: application/json');

// Must be logged in
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$farmUserId = (int)($_SESSION['farm_id'] ?? 0);
$action = $_GET['action'] ?? '';

$pdo = getDB();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

switch ($action) {
    case 'check_reminders':
        // Get pending reminders for this worker's farm
        $stmt = $pdo->prepare("
            SELECT * FROM reminders 
            WHERE farm_id = ? 
            AND status = 'pending' 
            AND remind_at <= NOW()
            ORDER BY remind_at ASC 
            LIMIT 10
        ");
        $stmt->execute([$farmUserId]);
        $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'reminders' => $reminders]);
        break;

    case 'check_tasks':
        // Get pending tasks for this worker
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT * FROM worker_tasks 
            WHERE (assigned_to_worker_id = ? OR assigned_to_worker_id IS NULL)
            AND farm_id = ?
            AND task_date = ?
            AND status IN ('pending', 'in_progress')
            AND due_time <= DATE_ADD(NOW(), INTERVAL 30 MINUTE)
            ORDER BY due_time ASC 
            LIMIT 10
        ");
        $stmt->execute([$userId, $farmUserId, $today]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'tasks' => $tasks]);
        break;

    case 'get_notifications':
        // Get all notifications for this worker
        $notifications = [];
        
        // Pending reminders
        $stmt = $pdo->prepare("
            SELECT 'reminder' as type, id, title, description as message, remind_at as time
            FROM reminders 
            WHERE farm_id = ? AND status = 'pending'
            ORDER BY remind_at DESC LIMIT 5
        ");
        $stmt->execute([$farmUserId]);
        $notifications = array_merge($notifications, $stmt->fetchAll(PDO::FETCH_ASSOC));
        
        // Pending tasks
        $stmt = $pdo->prepare("
            SELECT 'task' as type, id, title, CONCAT('Due: ', due_time) as message, created_at as time
            FROM worker_tasks 
            WHERE assigned_to_worker_id = ? AND status = 'pending'
            ORDER BY created_at DESC LIMIT 5
        ");
        $stmt->execute([$userId]);
        $notifications = array_merge($notifications, $stmt->fetchAll(PDO::FETCH_ASSOC));
        
        // Sort by time
        usort($notifications, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        echo json_encode(['success' => true, 'notifications' => array_slice($notifications, 0, 10)]);
        break;

    case 'mark_notification_read':
        $type = $_POST['type'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        
        if ($type === 'reminder' && $id > 0) {
            $pdo->prepare("UPDATE reminders SET status = 'done' WHERE id = ? AND farm_id = ?")
                ->execute([$id, $farmUserId]);
        }
        
        echo json_encode(['success' => true]);
        break;

    case 'get_clock_status':
        // Get current clock status
        $stmt = $pdo->prepare("
            SELECT * FROM worker_clock_records 
            WHERE worker_user_id = ? AND farm_id = ? 
            AND clock_out IS NULL 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$userId, $farmUserId]);
        $clock = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'clocked_in' => $clock !== false,
            'clock' => $clock
        ]);
        break;

    case 'get_today_stats':
        $today = date('Y-m-d');
        
        // Tasks completed today
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as completed 
            FROM worker_tasks 
            WHERE assigned_to_worker_id = ? AND task_date = ? AND status = 'completed'
        ");
        $stmt->execute([$userId, $today]);
        $completed = $stmt->fetchColumn();
        
        // Tasks pending
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as pending 
            FROM worker_tasks 
            WHERE assigned_to_worker_id = ? AND task_date = ? AND status IN ('pending', 'in_progress')
        ");
        $stmt->execute([$userId, $today]);
        $pending = $stmt->fetchColumn();
        
        // Hours worked today
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND, clock_in, COALESCE(clock_out, NOW())) - (break_minutes * 60)), 0) as seconds
            FROM worker_clock_records 
            WHERE worker_user_id = ? AND farm_id = ? AND DATE(clock_in) = ?
        ");
        $stmt->execute([$userId, $farmUserId, $today]);
        $hours = round($stmt->fetchColumn() / 3600, 1);
        
        echo json_encode([
            'success' => true,
            'completed' => (int)$completed,
            'pending' => (int)$pending,
            'hours' => $hours
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
