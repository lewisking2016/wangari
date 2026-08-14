<?php
/**
 * Hub: Settings, Calendar, Dropdowns, App Settings, System Logs, DB Setup
 */
declare(strict_types=1);
$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();
$page_title = 'Settings - Admin';
include __DIR__ . '/includes/admin_header.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    // farm_manager can access most settings, restrict only DB setup
}

$tab = $_GET['tab'] ?? 'calendar';
$validTabs = ['calendar','dropdowns','settings','logs'];
if (!in_array($tab, $validTabs, true)) $tab = 'calendar';

$pdo = getDB();
$message = ''; $error_message = '';

/* ── Handle POST ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $postAction = $_POST['_action'] ?? '';

    /* Save app setting */
    if ($postAction === 'save_setting') {
        $key   = trim($_POST['setting_key'] ?? '');
        $value = trim($_POST['setting_value'] ?? '');
        if ($key !== '') {
            try {
                $stmt = $pdo->prepare('SELECT id FROM settings WHERE setting_key=?');
                $stmt->execute([$key]);
                if ($stmt->fetchColumn()) {
                    $pdo->prepare('UPDATE settings SET setting_value=? WHERE setting_key=?')->execute([$value,$key]);
                } else {
                    $pdo->prepare('INSERT INTO settings (setting_key,setting_value) VALUES (?,?)')->execute([$key,$value]);
                }
                $message = 'Setting saved.';
                if (function_exists('logActivity')) {
                    logActivity($pdo, 'save', 'settings', "Setting saved: {$key}", null, 'setting');
                }
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'settings';
    }
}

/* ── Load tab data ─────────────────────────────────── */
$settingsList = $logsList = $tasksDue = [];

if ($pdo) {
    try {
        if ($tab === 'settings') {
            $settingsList = $pdo->query('SELECT * FROM settings ORDER BY setting_key ASC')->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($tab === 'logs') {
            $logsList = $pdo->query('SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($tab === 'calendar') {
            // Tasks with due dates for the calendar
            $tasksDue = $pdo->query("SELECT t.*, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS assigned_name FROM tasks t LEFT JOIN users u ON t.assigned_to=u.id WHERE t.due_date IS NOT NULL AND t.status NOT IN ('Completed','Cancelled') ORDER BY t.due_date ASC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) { /* non-fatal */ }
}

$tabs = [
    'calendar'  => ['icon' => 'calendar',         'label' => 'Calendar'],
    'dropdowns' => ['icon' => 'list-filter',       'label' => 'Dropdowns'],
    'settings'  => ['icon' => 'sliders-horizontal','label' => 'App Settings'],
    'logs'      => ['icon' => 'terminal',          'label' => 'System Logs'],
];
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Settings</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Configure your system, manage dropdown lists, and view system activity.</p>
    </div>
</div>

<?php if ($message): ?>
<div style="padding:13px 18px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;color:#166534;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <i data-lucide="check-circle-2" style="width:18px;height:18px;"></i> <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>
<?php if ($error_message): ?>
<div style="padding:13px 18px;background:#fee2e2;border:1px solid #fecaca;border-radius:8px;color:#b91c1c;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <i data-lucide="alert-circle" style="width:18px;height:18px;"></i> <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<!-- Tab Bar -->
<div style="display:flex;gap:4px;background:#f1f5f9;padding:5px;border-radius:10px;margin-bottom:24px;overflow-x:auto;scrollbar-width:none;">
<?php foreach ($tabs as $key => $info): ?>
    <a href="?tab=<?php echo $key; ?>"
       style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:7px;text-decoration:none;white-space:nowrap;font-weight:600;font-size:0.86rem;transition:all 0.2s;
              <?php echo $tab === $key ? 'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);' : 'color:#64748b;'; ?>">
        <i data-lucide="<?php echo $info['icon']; ?>" style="width:15px;height:15px;"></i>
        <?php echo $info['label']; ?>
    </a>
<?php endforeach; ?>
</div>

<!-- ══════ CALENDAR ══════ -->
<?php if ($tab === 'calendar'): ?>
<div style="display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start;">
    <!-- Calendar widget -->
    <div class="admin-card">
        <h3 style="margin:0 0 18px;font-family:'Outfit',sans-serif;font-size:1.1rem;">Farm Calendar</h3>
        <div id="cal-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <button class="btn btn-outline btn-sm" onclick="changeMonth(-1)"><i data-lucide="chevron-left" style="width:14px;height:14px;"></i></button>
            <strong id="cal-month-label" style="font-family:'Outfit',sans-serif;font-size:1rem;"></strong>
            <button class="btn btn-outline btn-sm" onclick="changeMonth(1)"><i data-lucide="chevron-right" style="width:14px;height:14px;"></i></button>
        </div>
        <div id="cal-grid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;"></div>
    </div>
    <!-- Upcoming tasks -->
    <div class="admin-card">
        <h3 style="margin:0 0 16px;font-family:'Outfit',sans-serif;font-size:1.1rem;">Upcoming Tasks</h3>
        <?php if (empty($tasksDue)): ?>
            <p style="text-align:center;color:#94a3b8;padding:20px;">No pending tasks with due dates.</p>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <?php foreach ($tasksDue as $t): ?>
            <div style="padding:12px 14px;border-radius:8px;border:1px solid var(--admin-border);background:#fafafa;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                    <strong style="font-size:0.9rem;color:var(--admin-text-heading);"><?php echo htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span class="badge-pill <?php echo ['Pending'=>'badge-pill-warning','In Progress'=>'badge-pill-warning'][$t['status']] ?? 'badge-pill-warning'; ?>" style="white-space:nowrap;font-size:0.7rem;"><?php echo htmlspecialchars($t['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div style="margin-top:6px;display:flex;gap:14px;font-size:0.8rem;color:#64748b;">
                    <span><i data-lucide="calendar" style="width:12px;height:12px;display:inline;vertical-align:middle;"></i> <?php echo htmlspecialchars($t['due_date'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php if (trim($t['assigned_name'])): ?>
                    <span><i data-lucide="user" style="width:12px;height:12px;display:inline;vertical-align:middle;"></i> <?php echo htmlspecialchars(trim($t['assigned_name']), ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--admin-border);">
            <a href="/Frontend/admin/hub_people.php?tab=tasks" class="btn btn-outline" style="width:100%;justify-content:center;">
                <i data-lucide="plus-circle" style="width:15px;height:15px;"></i> Manage All Tasks
            </a>
        </div>
    </div>
</div>

<!-- ══════ DROPDOWNS ══════ -->
<?php elseif ($tab === 'dropdowns'): ?>
<?php include __DIR__ . '/dropdowns.php'; ?>

<!-- ══════ APP SETTINGS ══════ -->
<?php elseif ($tab === 'settings'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Application Settings</h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Configure system-wide settings and parameters.</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <button class="btn btn-primary" onclick="openSettingModal()">
                <i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Add Setting
            </button>
            <button class="btn btn-outline" onclick="clearCache()" id="clear-cache-btn">
                <i data-lucide="trash" style="width:16px;height:16px;"></i> Clear Cache
            </button>
            <button class="btn btn-danger" onclick="initiateDeleteEverything()" id="delete-everything-btn">
                <i data-lucide="x-circle" style="width:16px;height:16px;"></i> Delete Everything
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Setting Key</th><th>Value</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($settingsList)): ?>
                <tr><td colspan="3" style="text-align:center;padding:28px;color:#94a3b8;">No settings configured yet.</td></tr>
            <?php else: foreach ($settingsList as $setting): ?>
                <tr>
                    <td><code style="background:#f1f5f9;padding:2px 8px;border-radius:4px;font-size:0.85rem;"><?php echo htmlspecialchars($setting['setting_key'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                    <td><?php echo htmlspecialchars($setting['setting_value'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <div class="tbl-actions">
                            <button class="btn btn-trans btn-sm" onclick='openSettingModal(<?php echo htmlspecialchars(json_encode($setting), ENT_QUOTES, "UTF-8"); ?>)'>
                                <i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- Setting Modal -->
<div id="setting-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:440px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 id="setting-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add Setting</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_setting">
            <div class="admin-form-group"><label class="admin-form-label">Setting Key</label><input class="admin-form-control" name="setting_key" id="set-key" placeholder="e.g. farm_name" required></div>
            <div class="admin-form-group"><label class="admin-form-label">Value</label><input class="admin-form-control" name="setting_value" id="set-val" placeholder="Value"></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('setting-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════ SYSTEM LOGS ══════ -->
<?php elseif ($tab === 'logs'): ?>
<div class="admin-card">
    <div style="margin-bottom:18px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">System Activity Logs</h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Recent system actions, last 200 entries.</p>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Date / Time</th><th>User</th><th>Action</th><th>Details</th></tr></thead>
            <tbody>
            <?php if (empty($logsList)): ?>
                <tr><td colspan="4" style="text-align:center;padding:28px;color:#94a3b8;">No activity logs available.</td></tr>
            <?php else: foreach ($logsList as $log): ?>
                <tr>
                    <td style="white-space:nowrap;"><?php echo htmlspecialchars(substr($log['created_at'] ?? '', 0, 16), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($log['username'] ?? $log['user_id'] ?? 'System', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><span class="badge-pill badge-pill-warning"><?php echo htmlspecialchars($log['action'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($log['details'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<script>
/* ════ Calendar Widget ════ */
const taskDates = <?php
    $dateMap = [];
    foreach ($tasksDue as $t) {
        if ($t['due_date']) $dateMap[$t['due_date']] = ($dateMap[$t['due_date']] ?? 0) + 1;
    }
    echo json_encode($dateMap);
?>;

let calYear, calMonth;
const today = new Date();
calYear = today.getFullYear();
calMonth = today.getMonth();

function changeMonth(dir) { calMonth += dir; if (calMonth > 11) { calMonth = 0; calYear++; } if (calMonth < 0) { calMonth = 11; calYear--; } renderCalendar(); }

function renderCalendar() {
    const label = document.getElementById('cal-month-label');
    const grid  = document.getElementById('cal-grid');
    if (!label || !grid) return;
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    label.textContent = months[calMonth] + ' ' + calYear;

    const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const firstDay = new Date(calYear, calMonth, 1).getDay();
    const daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
    const todayStr = today.toISOString().split('T')[0];

    let html = days.map(d => `<div style="text-align:center;font-size:0.72rem;font-weight:700;color:#94a3b8;padding:4px 0;">${d}</div>`).join('');
    for (let i = 0; i < firstDay; i++) html += '<div></div>';
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = calYear + '-' + String(calMonth+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
        const isToday = dateStr === todayStr;
        const hasTasks = taskDates[dateStr];
        html += `<div style="text-align:center;padding:7px 4px;border-radius:6px;font-size:0.82rem;position:relative;cursor:default;
            ${isToday ? 'background:var(--admin-primary);color:#fff;font-weight:700;' : 'color:#334155;'}
            ${hasTasks && !isToday ? 'background:#dcfce7;color:var(--admin-primary);font-weight:600;' : ''}
            transition:background 0.15s;">
            ${d}
            ${hasTasks ? `<span style="position:absolute;top:2px;right:3px;width:6px;height:6px;background:${isToday?'#fff':'var(--admin-primary)'};border-radius:50%;"></span>` : ''}
        </div>`;
    }
    grid.innerHTML = html;
}

/* Setting Modal */
function openSettingModal(data) {
    document.getElementById('setting-modal-title').textContent = data?.id ? 'Edit Setting' : 'Add Setting';
    document.getElementById('set-key').value = data?.setting_key || '';
    document.getElementById('set-key').readOnly = !!data?.id;
    document.getElementById('set-val').value = data?.setting_value || '';
    document.getElementById('setting-modal').style.display = 'flex';
}

document.addEventListener('DOMContentLoaded', () => {
    renderCalendar();
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
document.addEventListener('click', function(e) {
    const modal = document.getElementById('setting-modal');
    if (modal && e.target === modal) modal.style.display = 'none';
});
</script>

<!-- Delete Everything Modal -->
<div id="delete-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:3000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:28px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 14px;font-family:'Outfit',sans-serif;">Confirm Delete Everything</h3>
        <p style="color:#334155;margin:0 0 12px;">A full backup will be created and downloaded automatically. To confirm deletion, type the confirmation word shown below exactly.</p>
        <div style="margin:12px 0;padding:12px;border-radius:8px;background:#f8fafc;border:1px dashed var(--admin-border);">
            <strong>Confirmation word: </strong> <span id="confirm-word" style="font-family:monospace;padding:6px 10px;background:#fff;border-radius:6px;border:1px solid #e6eef8;margin-left:8px;"></span>
        </div>
        <div style="margin-top:8px;">
            <input id="confirm-input" class="admin-form-control" placeholder="Type the confirmation word here">
        </div>
        <div style="display:flex;gap:10px;margin-top:18px;">
            <button class="btn btn-outline" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-danger" id="confirm-delete-btn" onclick="confirmDelete()">Delete Everything</button>
        </div>
    </div>
</div>

<script>
async function clearCache() {
    const btn = document.getElementById('clear-cache-btn');
    btn.disabled = true;
    try {
        const res = await fetch('/Backend/api/admin_actions.php?action=clear_cache', { method: 'POST' });
        const j = await res.json();
        alert(j.message || 'Cache cleared');
    } catch (e) { alert('Failed to clear cache'); }
    btn.disabled = false;
}

function closeDeleteModal() {
    document.getElementById('delete-modal').style.display = 'none';
    document.getElementById('confirm-input').value = '';
}

async function initiateDeleteEverything() {
    const btn = document.getElementById('delete-everything-btn');
    btn.disabled = true;
    try {
        const res = await fetch('/Backend/api/admin_actions.php?action=prepare_delete', { method: 'POST' });
        const j = await res.json();
        if (!j.success) { alert(j.message || 'Failed to prepare backup'); btn.disabled = false; return; }

        // Show confirmation modal with provided word
        document.getElementById('confirm-word').textContent = j.word;
        document.getElementById('delete-modal').style.display = 'flex';

        // Auto-start download in background using iframe
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = j.download;
        document.body.appendChild(iframe);
        alert('Backup download started. After it finishes, type the word to confirm deletion.');

        // store token in modal dataset for later confirmation
        document.getElementById('delete-modal').dataset.token = j.token;
    } catch (e) {
        alert('Failed to prepare delete: ' + e.message);
    }
    btn.disabled = false;
}

async function confirmDelete() {
    const token = document.getElementById('delete-modal').dataset.token;
    const typed = document.getElementById('confirm-input').value.trim();
    if (!typed) { alert('Type the confirmation word'); return; }
    const ok = confirm('This will permanently delete most system data. Are you sure?');
    if (!ok) return;
    const btn = document.getElementById('confirm-delete-btn');
    btn.disabled = true;
    try {
        const form = new FormData();
        form.append('token', token);
        form.append('typed_word', typed);
        const res = await fetch('/Backend/api/admin_actions.php?action=delete_everything', { method: 'POST', body: form });
        const j = await res.json();
        if (j.success) {
            alert('Delete complete. Backup kept in temporary storage.');
            closeDeleteModal();
            location.reload();
        } else {
            alert('Delete failed: ' + (j.message || 'Unknown'));
        }
    } catch (e) { alert('Delete request failed'); }
    btn.disabled = false;
}
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
