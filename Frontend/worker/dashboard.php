<?php
/**
 * Worker Dashboard - Clock In/Out, Tasks, Attendance
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

// Must be logged in as worker
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'field_worker') {
    header('Location: /Frontend/worker/login.php');
    exit;
}

$workerId = (int)$_SESSION['user_id'];
$farmUserId = (int)($_SESSION['farm_user_id'] ?? 0);
$fullName = $_SESSION['full_name'] ?? 'Worker';

if (!$farmUserId) {
    header('Location: /Frontend/worker/login.php');
    exit;
}

$pdo = getDB();
$message = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';
    
    // Clock In
    if ($action === 'clock_in') {
        $lat = (float)($_POST['lat'] ?? 0);
        $lng = (float)($_POST['lng'] ?? 0);
        try {
            $stmt = $pdo->prepare("INSERT INTO worker_clock_records (worker_user_id, farm_user_id, clock_in, location_lat, location_lng) VALUES (?, ?, NOW(), ?, ?)");
            $stmt->execute([$workerId, $farmUserId, $lat ?: null, $lng ?: null]);
            $message = 'Clocked in successfully!';
        } catch (Exception $e) { $error = $e->getMessage(); }
    }
    
    // Clock Out
    if ($action === 'clock_out') {
        try {
            $stmt = $pdo->prepare("UPDATE worker_clock_records SET clock_out = NOW() WHERE worker_user_id = ? AND farm_user_id = ? AND clock_out IS NULL ORDER BY id DESC LIMIT 1");
            $stmt->execute([$workerId, $farmUserId]);
            $message = 'Clocked out successfully!';
        } catch (Exception $e) { $error = $e->getMessage(); }
    }
    
    // Complete Task
    if ($action === 'complete_task') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        try {
            $stmt = $pdo->prepare("UPDATE worker_tasks SET status = 'completed', completed_at = NOW(), completion_notes = ? WHERE id = ? AND assigned_to_worker_id = ?");
            $stmt->execute([$notes, $taskId, $workerId]);
            $message = 'Task completed!';
        } catch (Exception $e) { $error = $e->getMessage(); }
    }
    
    // Start Task
    if ($action === 'start_task') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE worker_tasks SET status = 'in_progress' WHERE id = ? AND assigned_to_worker_id = ?");
            $stmt->execute([$taskId, $workerId]);
            $message = 'Task started!';
        } catch (Exception $e) { $error = $e->getMessage(); }
    }
}

// Load data
$today = date('Y-m-d');
$clockedIn = false;
$todayClock = null;
$todayTasks = [];
$thisWeekHours = 0;
$totalHours = 0;

if ($pdo) {
    // Check if currently clocked in
    $stmt = $pdo->prepare("SELECT * FROM worker_clock_records WHERE worker_user_id = ? AND farm_user_id = ? AND clock_out IS NULL ORDER BY id DESC LIMIT 1");
    $stmt->execute([$workerId, $farmUserId]);
    $todayClock = $stmt->fetch(PDO::FETCH_ASSOC);
    $clockedIn = $todayClock !== false;
    
    // Get today's tasks
    $stmt = $pdo->prepare("SELECT * FROM worker_tasks WHERE farm_user_id = ? AND (assigned_to_worker_id = ? OR assigned_to_worker_id IS NULL) AND task_date = ? ORDER BY FIELD(priority, 'urgent', 'high', 'medium', 'low'), due_time ASC");
    $stmt->execute([$farmUserId, $workerId, $today]);
    $todayTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate this week's hours
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND, clock_in, COALESCE(clock_out, NOW())) - (break_minutes * 60)), 0) as total_seconds FROM worker_clock_records WHERE worker_user_id = ? AND farm_user_id = ? AND clock_in >= ?");
    $stmt->execute([$workerId, $farmUserId, $weekStart]);
    $thisWeekHours = round($stmt->fetchColumn() / 3600, 1);
    
    // Total hours all time
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND, clock_in, COALESCE(clock_out, NOW())) - (break_minutes * 60)), 0) as total_seconds FROM worker_clock_records WHERE worker_user_id = ? AND farm_user_id = ?");
    $stmt->execute([$workerId, $farmUserId]);
    $totalHours = round($stmt->fetchColumn() / 3600, 1);
    
    // Recent attendance
    $stmt = $pdo->prepare("SELECT * FROM worker_clock_records WHERE worker_user_id = ? AND farm_user_id = ? ORDER BY clock_in DESC LIMIT 7");
    $stmt->execute([$workerId, $farmUserId]);
    $recentAttendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pending tasks count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM worker_tasks WHERE assigned_to_worker_id = ? AND task_date = ? AND status IN ('pending', 'in_progress')");
    $stmt->execute([$workerId, $today]);
    $pendingTasks = $stmt->fetchColumn();
    
    // Completed tasks today
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM worker_tasks WHERE assigned_to_worker_id = ? AND task_date = ? AND status = 'completed'");
    $stmt->execute([$workerId, $today]);
    $completedTasks = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Worker Dashboard — Wangari</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #F8FAFC; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #0B1220 0%, #14532D 100%); color: #fff; padding: 20px; }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .header h1 { font-size: 1.3rem; font-weight: 700; }
        .header p { font-size: 0.85rem; opacity: 0.8; }
        .logout-btn { background: rgba(255,255,255,0.15); color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; cursor: pointer; text-decoration: none; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        
        /* Clock Card */
        .clock-card { background: #fff; border-radius: 16px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); text-align: center; }
        .clock-time { font-size: 3rem; font-weight: 800; color: #0F172A; margin: 16px 0; }
        .clock-status { font-size: 0.9rem; color: #64748B; margin-bottom: 16px; }
        .clock-status.active { color: #22C55E; font-weight: 600; }
        .clock-btn { width: 120px; height: 120px; border-radius: 50%; border: none; font-size: 1rem; font-weight: 700; cursor: pointer; transition: all 0.2s; margin: 0 auto; }
        .clock-btn.in { background: #22C55E; color: #fff; }
        .clock-btn.in:hover { background: #16A34A; transform: scale(1.05); }
        .clock-btn.out { background: #DC2626; color: #fff; }
        .clock-btn.out:hover { background: #B91C1C; transform: scale(1.05); }
        
        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
        .stat-card { background: #fff; border-radius: 12px; padding: 16px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .stat-card .number { font-size: 1.5rem; font-weight: 700; color: #0F172A; }
        .stat-card .label { font-size: 0.75rem; color: #64748B; margin-top: 4px; }
        
        /* Tasks */
        .section { background: #fff; border-radius: 16px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .section-title { font-size: 1rem; font-weight: 700; color: #0F172A; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .task-item { border: 1px solid #E5E7EB; border-radius: 12px; padding: 14px; margin-bottom: 10px; }
        .task-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px; }
        .task-title { font-weight: 600; color: #0F172A; font-size: 0.95rem; }
        .task-priority { padding: 3px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; }
        .priority-urgent { background: #FEE2E2; color: #991B1B; }
        .priority-high { background: #FEF3C7; color: #92400E; }
        .priority-medium { background: #E0E7FF; color: #3730A3; }
        .priority-low { background: #F3F4F6; color: #6B7280; }
        .task-meta { font-size: 0.8rem; color: #64748B; margin-bottom: 10px; }
        .task-actions { display: flex; gap: 8px; }
        .task-btn { padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; cursor: pointer; border: none; }
        .task-btn.start { background: #DBEAFE; color: #1D4ED8; }
        .task-btn.complete { background: #D1FAE5; color: #065F46; }
        .task-btn.complete:hover { background: #A7F3D0; }
        
        /* Attendance */
        .attendance-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #F3F4F6; }
        .attendance-item:last-child { border-bottom: none; }
        .attendance-date { font-weight: 600; color: #0F172A; }
        .attendance-hours { color: #64748B; font-size: 0.9rem; }
        
        /* Alerts */
        .alert { padding: 12px 16px; border-radius: 12px; margin-bottom: 16px; font-size: 0.9rem; }
        .alert-success { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
        .alert-error { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
        
        @media (max-width: 640px) {
            .stats-grid { grid-template-columns: 1fr; }
            .clock-btn { width: 100px; height: 100px; font-size: 0.9rem; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <div>
                <h1>Wangari Worker</h1>
                <p>Welcome, <?= htmlspecialchars($fullName) ?></p>
            </div>
            <a href="/Frontend/worker/logout.php" class="logout-btn">Sign Out</a>
        </div>
        <div style="display: flex; gap: 16px; font-size: 0.85rem; opacity: 0.9;">
            <span>📅 <?= date('l, M j, Y') ?></span>
            <span>⏱ <?= date('H:i') ?></span>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Clock In/Out -->
        <div class="clock-card">
            <div class="clock-time" id="clockDisplay"><?= date('H:i:s') ?></div>
            <div class="clock-status <?= $clockedIn ? 'active' : '' ?>">
                <?= $clockedIn ? '● Clocked In' : '○ Not Clocked In' ?>
            </div>
            <form method="POST" id="clockForm">
                <input type="hidden" name="action" value="<?= $clockedIn ? 'clock_out' : 'clock_in' ?>">
                <input type="hidden" name="lat" id="lat" value="">
                <input type="hidden" name="lng" id="lng" value="">
                <button type="submit" class="clock-btn <?= $clockedIn ? 'out' : 'in' ?>" onclick="getLocation()">
                    <?= $clockedIn ? 'Clock Out' : 'Clock In' ?>
                </button>
            </form>
            <?php if ($clockedIn && $todayClock): ?>
                <div style="margin-top: 12px; font-size: 0.85rem; color: #64748B;">
                    Clocked in at <?= date('H:i', strtotime($todayClock['clock_in'])) ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?= $pendingTasks ?></div>
                <div class="label">Pending Tasks</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $completedTasks ?></div>
                <div class="label">Completed Today</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $thisWeekHours ?>h</div>
                <div class="label">This Week</div>
            </div>
        </div>
        
        <!-- Today's Tasks -->
        <div class="section">
            <div class="section-title">📋 Today's Tasks</div>
            <?php if (empty($todayTasks)): ?>
                <div style="text-align: center; padding: 24px; color: #94A3B8;">
                    No tasks assigned for today
                </div>
            <?php else: ?>
                <?php foreach ($todayTasks as $task): ?>
                    <div class="task-item">
                        <div class="task-header">
                            <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                            <span class="task-priority priority-<?= $task['priority'] ?>"><?= $task['priority'] ?></span>
                        </div>
                        <?php if ($task['description']): ?>
                            <div class="task-meta"><?= htmlspecialchars($task['description']) ?></div>
                        <?php endif; ?>
                        <?php if ($task['due_time']): ?>
                            <div class="task-meta">⏰ Due: <?= date('H:i', strtotime($task['due_time'])) ?></div>
                        <?php endif; ?>
                        <div class="task-actions">
                            <?php if ($task['status'] === 'pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="start_task">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <button type="submit" class="task-btn start">Start Task</button>
                                </form>
                            <?php elseif ($task['status'] === 'in_progress'): ?>
                                <form method="POST" style="display:inline;" class="complete-form">
                                    <input type="hidden" name="action" value="complete_task">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <input type="text" name="notes" placeholder="Add notes (optional)" style="padding: 6px 10px; border: 1px solid #E5E7EB; border-radius: 6px; font-size: 0.8rem; width: 200px;">
                                    <button type="submit" class="task-btn complete">✓ Complete</button>
                                </form>
                            <?php else: ?>
                                <span style="color: #22C55E; font-size: 0.85rem; font-weight: 600;">✓ Completed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Recent Attendance -->
        <div class="section">
            <div class="section-title">📊 Recent Attendance</div>
            <?php if (empty($recentAttendance)): ?>
                <div style="text-align: center; padding: 24px; color: #94A3B8;">
                    No attendance records yet
                </div>
            <?php else: ?>
                <?php foreach ($recentAttendance as $att): ?>
                    <div class="attendance-item">
                        <div>
                            <div class="attendance-date"><?= date('D, M j', strtotime($att['clock_in'])) ?></div>
                            <div style="font-size: 0.8rem; color: #64748B;">
                                <?= date('H:i', strtotime($att['clock_in'])) ?> - <?= $att['clock_out'] ? date('H:i', strtotime($att['clock_out'])) : 'Ongoing' ?>
                            </div>
                        </div>
                        <div class="attendance-hours">
                            <?php
                            $seconds = strtotime($att['clock_out'] ?: 'now') - strtotime($att['clock_in']);
                            $seconds -= ($att['break_minutes'] ?? 0) * 60;
                            echo round($seconds / 3600, 1) . 'h';
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Total Hours -->
        <div class="section" style="text-align: center;">
            <div class="section-title" style="justify-content: center;">💰 Total Hours Worked</div>
            <div style="font-size: 2.5rem; font-weight: 800; color: #22C55E;"><?= $totalHours ?>h</div>
            <div style="font-size: 0.85rem; color: #64748B;">All time</div>
        </div>
    </div>
    
    <script>
        // Update clock display
        function updateClock() {
            const now = new Date();
            document.getElementById('clockDisplay').textContent = now.toLocaleTimeString('en-US', {hour12: false});
        }
        setInterval(updateClock, 1000);
        
        // Get GPS location
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(pos) {
                    document.getElementById('lat').value = pos.coords.latitude;
                    document.getElementById('lng').value = pos.coords.longitude;
                });
            }
        }
    </script>
</body>
</html>
