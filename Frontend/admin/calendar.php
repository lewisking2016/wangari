<?php
/**
 * Admin - Calendar Module
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();

$page_title = 'Calendar - Admin';
include __DIR__ . '/includes/admin_header.php';

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager', 'stock_manager', 'sales_staff', 'customer'], true)) {
    header('Location: /Frontend/pages/login.php');
    exit;
}

$pdo = getDB();
$view = $_GET['view'] ?? 'month';
$tasks = [];
$events = [];

if ($pdo) {
    try {
        $stmt = $pdo->query('SELECT id, title, due_date, status FROM tasks ORDER BY due_date ASC, created_at DESC LIMIT 20');
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = 'Database error (tasks): ' . $e->getMessage();
    }
    try {
        $stmt = $pdo->query('SELECT id, vaccine_name AS title, scheduled_date AS date, status FROM vaccinations ORDER BY scheduled_date ASC LIMIT 20');
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = ($error_message ? $error_message . ' | ' : '') . 'Database error (vaccinations): ' . $e->getMessage();
    }
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:16px;">
    <div>
        <h2 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.5rem;color:var(--admin-text-heading);">Calendar</h2>
        <p style="margin:4px 0 0 0;font-size:0.9rem;color:#64748b;">View farm tasks and health schedules.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="?view=month" class="btn <?php echo $view === 'month' ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius:4px;">Month</a>
        <a href="?view=week" class="btn <?php echo $view === 'week' ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius:4px;">Week</a>
        <a href="?view=day" class="btn <?php echo $view === 'day' ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius:4px;">Day</a>
    </div>
</div>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;color:var(--admin-text-heading);"><?php echo ucfirst($view); ?> View</h3>
        <span style="font-size:0.85rem;color:#64748b;">Schedule overview from tasks and vaccinations</span>
    </div>
    <?php if (empty($tasks) && empty($events)): ?>
        <p style="color:#64748b;">No scheduled items or events found.</p>
    <?php else: ?>
        <div style="display:grid;gap:14px;">
            <?php foreach ($tasks as $task): ?>
                <div style="border:1px solid var(--admin-border);border-radius:8px;padding:14px;display:flex;justify-content:space-between;align-items:center;gap:12px;">
                    <div>
                        <div style="font-weight:600;color:var(--admin-text-heading);"><?php echo htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div style="color:#64748b;font-size:0.9rem;">Due: <?php echo htmlspecialchars($task['due_date'] ?: 'TBD', ENT_QUOTES, 'UTF-8'); ?> &middot; <?php echo htmlspecialchars($task['status'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <span class="badge-pill badge-pill-primary">Task</span>
                </div>
            <?php endforeach; ?>
            <?php foreach ($events as $event): ?>
                <div style="border:1px solid var(--admin-border);border-radius:8px;padding:14px;display:flex;justify-content:space-between;align-items:center;gap:12px;">
                    <div>
                        <div style="font-weight:600;color:var(--admin-text-heading);"><?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div style="color:#64748b;font-size:0.9rem;">Date: <?php echo htmlspecialchars($event['date'] ?: 'TBD', ENT_QUOTES, 'UTF-8'); ?> &middot; <?php echo htmlspecialchars($event['status'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <span class="badge-pill badge-pill-success">Vaccination</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
