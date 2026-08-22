<?php
/**
 * Hub: Smart Reminders & Weather
 * Tabs: Reminders | Weather Alerts | This Week
 * Research-backed: 57% want weather alerts, 36% want automatic reminders, 49% deadline reminders.
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','stock_manager'], true)) {
    echo "<script>window.location.href='/Frontend/pages/login.php';</script>"; exit;
}

$page_title = 'Smart Reminders & Weather - Admin';
include __DIR__ . '/includes/admin_header.php';

$tab = $_GET['tab'] ?? 'reminders';
$validTabs = ['reminders','weather','week'];
if (!in_array($tab, $validTabs, true)) $tab = 'reminders';

$pdo = getDB();
$message = ''; $error_message = '';

/* ═══ POST handlers ═══ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $postAction = $_POST['_action'] ?? '';

    if ($postAction === 'save_reminder') {
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $when  = trim($_POST['remind_at'] ?? '');
        $chan  = trim($_POST['channel'] ?? 'app');
        $target= trim($_POST['target'] ?? '');
        $rtype = trim($_POST['related_type'] ?? '');
        $rid   = (int)($_POST['related_id'] ?? 0);
        if (!$title || !$when) { $error_message = 'Title and date/time are required.'; }
        else {
            try {
                $pdo->prepare('INSERT INTO reminders (title,description,remind_at,channel,target,related_type,related_id,created_by) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$title,$desc,$when,$chan,$target,$rtype,$rid,(int)($_SESSION['user_id'] ?? 0)]);
                $message = 'Reminder scheduled.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'reminders';
    }

    if ($postAction === 'dismiss_reminder') {
        try {
            $pdo->prepare('UPDATE reminders SET status="dismissed" WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
            $message = 'Reminder dismissed.';
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'reminders';
    }

    if ($postAction === 'save_weather_alert') {
        $type  = trim($_POST['alert_type'] ?? 'weather');
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $date  = trim($_POST['alert_date'] ?? date('Y-m-d'));
        if (!$title) { $error_message = 'Alert title is required.'; }
        else {
            try {
                $pdo->prepare('INSERT INTO weather_alerts (alert_type,title,description,alert_date) VALUES (?,?,?,?)')
                    ->execute([$type,$title,$desc,$date]);
                $message = 'Alert recorded.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'weather';
    }

    if ($postAction === 'resolve_alert') {
        try {
            $pdo->prepare('UPDATE weather_alerts SET status="resolved" WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
            $message = 'Alert resolved.';
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'weather';
    }
}

/* ═══ Load data ═══ */
$reminders = $alerts = [];
$upcoming = [];
if ($pdo) {
    try {
        $reminders = $pdo->query('SELECT * FROM reminders ORDER BY (status="pending") DESC, remind_at ASC LIMIT 100')->fetchAll();
        $alerts = $pdo->query('SELECT * FROM weather_alerts ORDER BY (status="active") DESC, alert_date DESC LIMIT 50')->fetchAll();
        // This week: reminders + follow-ups + vaccinations + tasks due in next 7 days
        $weekStart = date('Y-m-d');
        $weekEnd = date('Y-m-d', strtotime('+7 days'));
        $r = $pdo->prepare('SELECT title, remind_at, "reminder" AS src, status FROM reminders WHERE DATE(remind_at) BETWEEN ? AND ?');
        $r->execute([$weekStart, $weekEnd]);
        $upcoming = $r->fetchAll();
        try {
            $v = $pdo->prepare('SELECT CONCAT("Vaccination: ", IFNULL(vaccine_name,"")) AS title, scheduled_date AS remind_at, "vaccination" AS src, IF(administered=1,"done","pending") AS status FROM vaccinations WHERE DATE(scheduled_date) BETWEEN ? AND ?');
            $v->execute([$weekStart, $weekEnd]);
            $upcoming = array_merge($upcoming, $v->fetchAll());
        } catch (Exception $e) { /* table may not exist */ }
        try {
            $t = $pdo->prepare('SELECT title, due_date AS remind_at, "task" AS src, status FROM tasks WHERE DATE(due_date) BETWEEN ? AND ?');
            $t->execute([$weekStart, $weekEnd]);
            $upcoming = array_merge($upcoming, $t->fetchAll());
        } catch (Exception $e) { /* table may not exist */ }
        try {
            $f = $pdo->prepare('SELECT note AS title, due_date AS remind_at, "follow-up" AS src, status FROM crm_followups WHERE DATE(due_date) BETWEEN ? AND ?');
            $f->execute([$weekStart, $weekEnd]);
            $upcoming = array_merge($upcoming, $f->fetchAll());
        } catch (Exception $e) { /* table may not exist */ }
        usort($upcoming, fn($a, $b) => strcmp($a['remind_at'], $b['remind_at']));
    } catch (Exception $e) { $error_message = $e->getMessage(); }
}

$tabs = [
    'reminders' => ['icon' => 'bell',        'label' => 'Reminders'],
    'weather'   => ['icon' => 'cloud-sun',   'label' => 'Weather Alerts'],
    'week'      => ['icon' => 'calendar',    'label' => 'This Week'],
];
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
    <div class="hub-page-header" style="margin-bottom:0;">
        <div class="hub-page-icon"><i data-lucide="bell"></i></div>
        <div>
            <h1 class="hub-page-title">Smart Reminders &amp; Weather</h1>
            <p class="hub-page-sub">Never miss a deadline, treatment, or weather warning again.</p>
        </div>
    </div>
    <div style="display:flex;gap:10px;">
        <?php if ($tab === 'reminders'): ?><button class="btn btn-primary" onclick="document.getElementById('reminder-modal').style.display='flex'"><i data-lucide="plus" style="width:15px;height:15px;"></i> New Reminder</button><?php endif; ?>
        <?php if ($tab === 'weather'): ?><button class="btn btn-primary" onclick="document.getElementById('weather-modal').style.display='flex'"><i data-lucide="plus" style="width:15px;height:15px;"></i> Log Alert</button><?php endif; ?>
    </div>
</div>

<?php if ($message): ?>
<div class="hub-alert hub-alert-success"><i data-lucide="check-circle-2"></i><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($error_message): ?>
<div class="hub-alert hub-alert-error"><i data-lucide="alert-circle"></i><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Tab Bar -->
<div class="hub-tab-bar">
<?php foreach ($tabs as $key => $info): ?>
    <a href="?tab=<?= $key ?>" class="hub-tab<?= $tab===$key ? ' active' : '' ?>">
        <i data-lucide="<?= $info['icon'] ?>"></i><?= $info['label'] ?>
    </a>
<?php endforeach; ?>
</div>

<?php if ($tab === 'reminders'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Scheduled Reminders <?= helpTip("Things you set to remember: vaccination dates, payment deadlines, permit renewals. Never miss an important date.") ?></h3>
        <span style="color:#64748b;font-size:0.85rem;"><?= count(array_filter($reminders, fn($r) => $r['status']==='pending' && strtotime($r['remind_at']) >= time())) ?> upcoming</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>When</th><th>Reminder</th><th>Description</th><th>Channel</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php if (empty($reminders)): ?>
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">No reminders yet. Schedule treatments, harvests, payments, anything.</td></tr>
            <?php else: foreach ($reminders as $r): ?>
                <?php $isPast = ($r['status'] === 'pending' && strtotime($r['remind_at']) < time()); ?>
                <tr>
                    <td><strong><?= htmlspecialchars(date('M j, Y H:i', strtotime($r['remind_at'])), ENT_QUOTES, 'UTF-8') ?></strong><?= $isPast ? ' <span class="badge-pill badge-pill-danger">Now</span>' : '' ?></td>
                    <td><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($r['description'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge-pill badge-pill-warning" style="text-transform:capitalize;"><?= htmlspecialchars($r['channel'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><span class="badge-pill <?= $r['status']==='done' ? 'badge-pill-success' : ($r['status']==='dismissed' ? 'badge-pill-danger' : 'badge-pill-warning') ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td>
                        <?php if ($r['status'] === 'pending'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="_action" value="dismiss_reminder">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button class="btn btn-outline btn-sm"><i data-lucide="check" style="width:13px;height:13px;"></i> Dismiss</button>
                        </form>
                        <?php else: ?><span style="color:#94a3b8;">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="reminder-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:460px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 20px;font-family:'Outfit',sans-serif;">New Reminder</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_reminder">
            <div class="admin-form-group"><label class="admin-form-label">Title *</label>
                <input class="admin-form-control" type="text" name="title" required placeholder="e.g. Vaccinate broilers House 3"></div>
            <div class="admin-form-group"><label class="admin-form-label">Description</label>
                <textarea class="admin-form-control" name="description" rows="2" placeholder="Optional details"></textarea></div>
            <div class="admin-form-group"><label class="admin-form-label">Date &amp; Time *</label>
                <input class="admin-form-control" type="datetime-local" name="remind_at" required value="<?= date('Y-m-d\TH:i') ?>"></div>
            <div class="admin-form-group"><label class="admin-form-label">Channel</label>
                <select class="admin-form-control" name="channel">
                    <option value="app">In-app</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="sms">SMS</option>
                    <option value="email">Email</option>
                </select></div>
            <div class="admin-form-group"><label class="admin-form-label">Target (phone/email)</label>
                <input class="admin-form-control" type="text" name="target" placeholder="+254 7XX XXX XXX"></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('reminder-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Schedule</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($tab === 'weather'): ?>
<div class="admin-card">
    <h3 style="margin:0 0 16px;font-family:'Outfit',sans-serif;font-size:1.1rem;">Weather &amp; Field Alerts <?= helpTip("Weather updates and warnings for your fields. Know when rain, drought, or pests are coming.") ?></h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Type</th><th>Alert</th><th>Details</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php if (empty($alerts)): ?>
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">No weather alerts yet. Log heavy rain, frost, heat, or any risk that affects your farm.</td></tr>
            <?php else: foreach ($alerts as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['alert_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge-pill badge-pill-warning" style="text-transform:capitalize;"><?= htmlspecialchars($a['alert_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><strong><?= htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($a['description'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge-pill <?= $a['status']==='active' ? 'badge-pill-danger' : 'badge-pill-success' ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td>
                        <?php if ($a['status'] === 'active'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="_action" value="resolve_alert">
                            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                            <button class="btn btn-success btn-sm"><i data-lucide="check" style="width:13px;height:13px;"></i> Resolve</button>
                        </form>
                        <?php else: ?><span style="color:#94a3b8;">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="weather-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:440px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 20px;font-family:'Outfit',sans-serif;">Log Weather Alert</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_weather_alert">
            <div class="admin-form-group"><label class="admin-form-label">Type</label>
                <select class="admin-form-control" name="alert_type">
                    <option>weather</option><option>rain</option><option>frost</option><option>heat</option><option>wind</option><option>drought</option><option>flood</option>
                </select></div>
            <div class="admin-form-group"><label class="admin-form-label">Title *</label>
                <input class="admin-form-control" type="text" name="title" required placeholder="e.g. Heavy rain expected Thursday"></div>
            <div class="admin-form-group"><label class="admin-form-label">Details</label>
                <textarea class="admin-form-control" name="description" rows="2"></textarea></div>
            <div class="admin-form-group"><label class="admin-form-label">Date</label>
                <input class="admin-form-control" type="date" name="alert_date" value="<?= date('Y-m-d') ?>"></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('weather-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Save Alert</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($tab === 'week'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Next 7 Days <?= helpTip("What is coming up this week: tasks, reminders, weather alerts. Plan your week ahead.") ?></h3>
        <span style="color:#64748b;font-size:0.85rem;">Everything that needs your attention this week, from every module, in one list.</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Item</th><th>Source</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (empty($upcoming)): ?>
                <tr><td colspan="4" style="text-align:center;padding:28px;color:#94a3b8;">Nothing scheduled in the next 7 days. Enjoy the quiet week.</td></tr>
            <?php else: foreach ($upcoming as $u): ?>
                <tr>
                    <td><strong><?= htmlspecialchars(date('D, M j', strtotime($u['remind_at'])), ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($u['title'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge-pill badge-pill-success" style="text-transform:capitalize;"><?= htmlspecialchars($u['src'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><span class="badge-pill <?= ($u['status'] ?? '')==='done' ? 'badge-pill-success' : 'badge-pill-warning' ?>"><?= ucfirst($u['status'] ?? 'pending') ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
