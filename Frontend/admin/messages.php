<?php
/**
 * Admin - Messages Module
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

$page_title = 'Messages - Admin';
include __DIR__ . '/includes/admin_header.php';

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager', 'stock_manager', 'sales_staff'], true)) {
    header('Location: /wangariadmin');
    exit;
}

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$message = '';
$error_message = '';
$currentAdminId = (int)($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipientId = (int)($_POST['recipient_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');

    if ($recipientId <= 0 || $subject === '' || $body === '') {
        $error_message = 'Recipient, subject, and message body are required.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO messages (sender_id, recipient_id, subject, body, status) VALUES (?, ?, ?, ?, "pending")');
            $stmt->execute([$currentAdminId, $recipientId, $subject, $body]);
            $message = 'Message sent successfully.';
        } catch (Exception $e) {
            $error_message = 'Unable to send message: ' . $e->getMessage();
        }
    }
}

$users = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT id, username, first_name, last_name FROM users WHERE id != ? ORDER BY first_name ASC, last_name ASC');
        $stmt->execute([$currentAdminId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

$messages = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT m.*, su.username AS sender_username, ru.username AS recipient_username FROM messages m LEFT JOIN users su ON m.sender_id = su.id LEFT JOIN users ru ON m.recipient_id = ru.id WHERE m.sender_id = ? OR m.recipient_id = ? ORDER BY m.created_at DESC');
        $stmt->execute([$currentAdminId, $currentAdminId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

$selectedMessage = null;
if ($action === 'view' && $pdo) {
    $messageId = (int)($_GET['id'] ?? 0);
    if ($messageId > 0) {
        $stmt = $pdo->prepare('SELECT m.*, su.username AS sender_username, ru.username AS recipient_username FROM messages m LEFT JOIN users su ON m.sender_id = su.id LEFT JOIN users ru ON m.recipient_id = ru.id WHERE m.id = ?');
        $stmt->execute([$messageId]);
        $selectedMessage = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:16px;">
    <div>
        <h2 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.5rem;color:var(--admin-text-heading);">Messages</h2>
        <p style="margin:4px 0 0 0;font-size:0.9rem;color:#64748b;">Send quick notes to staff and check message status.</p>
    </div>
    <a href="?action=compose" class="btn btn-primary" style="border-radius:4px;display:inline-flex;align-items:center;gap:8px;">
        <i data-lucide="message-circle" style="width:18px;height:18px;"></i>
        <span>Compose</span>
    </a>
</div>

<?php if ($message): ?>
<div style="padding:14px 18px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:6px;color:#166534;margin-bottom:20px;">
    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>
<?php if ($error_message): ?>
<div style="padding:14px 18px;background:#fee2e2;border:1px solid #fecaca;border-radius:6px;color:#b91c1c;margin-bottom:20px;">
    <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;color:var(--admin-text-heading);">Message Inbox</h3>
        <span style="font-size:0.85rem;color:#64748b;">See messages you sent or received</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>To</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($messages)): ?>
                <tr><td colspan="5" style="text-align:center; padding:20px; color:#64748b;">No messages found.</td></tr>
                <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                <tr>
                    <td><?php echo htmlspecialchars($msg['recipient_username'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($msg['subject'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($msg['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($msg['status']), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td style="text-align:right;"><a class="btn btn-outline btn-sm" href="?action=view&id=<?php echo (int)$msg['id']; ?>">View</a></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($action === 'compose'): ?>
<div class="admin-card">
    <h3 style="margin:0 0 16px 0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">Compose Message</h3>
    <form method="POST" action="">
        <input type="hidden" name="send_message" value="1">
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
            <div class="admin-form-group">
                <label class="admin-form-label">Send to</label>
                <select class="admin-form-control" name="recipient_id" required>
                    <option value="">Select staff member...</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?php echo (int)$user['id']; ?>"><?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['username'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php echo renderInput('Subject', 'subject'); ?>
            <div class="admin-form-group" style="grid-column: span 2;">
                <label class="admin-form-label">Message</label>
                <textarea class="admin-form-control" name="body" rows="6"></textarea>
            </div>
        </div>
        <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary" style="border-radius:4px;">Send</button>
            <a href="/Frontend/admin/messages.php" class="btn btn-outline" style="border-radius:4px;">Cancel</a>
        </div>
    </form>
</div>

<?php elseif ($action === 'view'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">Message Details</h3>
            <p style="margin:6px 0 0 0;color:#64748b;">Review message content and status.</p>
        </div>
        <a href="/Frontend/admin/messages.php" class="btn btn-outline" style="border-radius:4px;">Back</a>
    </div>
    <?php if ($selectedMessage): ?>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px; margin-bottom:18px;">
        <div><strong>From:</strong> <?php echo htmlspecialchars($selectedMessage['sender_username'] ?? 'System', ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>To:</strong> <?php echo htmlspecialchars($selectedMessage['recipient_username'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Subject:</strong> <?php echo htmlspecialchars($selectedMessage['subject'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($selectedMessage['status']), ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Date:</strong> <?php echo htmlspecialchars($selectedMessage['created_at'], ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
    <div style="padding:18px;background:#f8fafc;border:1px solid var(--admin-border);border-radius:8px;color:#334155;">
        <?php echo nl2br(htmlspecialchars($selectedMessage['body'], ENT_QUOTES, 'UTF-8')); ?>
    </div>
    <?php else: ?>
    <p style="color:#64748b;">Message not found.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
