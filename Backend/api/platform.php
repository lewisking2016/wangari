<?php
/**
 * Platform Admin API
 * Handles all platform management: users, subscriptions, codes, revenue, tickets
 * Used by the /wangariadmin SPA
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$module = $_GET['module'] ?? '';
$action = $_GET['action'] ?? 'list';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

function generateCode(string $prefix = 'WGR', int $length = 12): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = $prefix . '-';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function platformLog(PDO $pdo, ?int $adminId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): void {
    $stmt = $pdo->prepare('INSERT INTO platform_activity_log (admin_id, action, target_type, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$adminId, $action, $targetType, $targetId, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
}

try {
    switch ($module) {

        // ════ AUTH ════
        case 'auth':
            if ($action === 'login') {
                $username = trim($input['username'] ?? '');
                $password = $input['password'] ?? '';
                if (!$username || !$password) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Username and password required']);
                    exit;
                }
                $stmt = $pdo->prepare('SELECT * FROM platform_users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1');
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$user || !password_verify($password, $user['password'])) {
                    http_response_code(401);
                    echo json_encode(['error' => 'Invalid credentials']);
                    exit;
                }
                if (!in_array($user['role'], ['super_admin', 'admin', 'support'], true)) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Access denied']);
                    exit;
                }
                $pdo->prepare('UPDATE platform_users SET last_login=NOW(), total_login_count=total_login_count+1 WHERE id=?')->execute([$user['id']]);
                platformLog($pdo, $user['id'], 'login', 'user', $user['id']);
                echo json_encode([
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'full_name' => $user['full_name'],
                    ]
                ]);
            }
            break;

        // ════ DASHBOARD ════
        case 'dashboard':
            $data = [];
            $data['total_users'] = (int) $pdo->query("SELECT COUNT(*) FROM platform_users WHERE role='user'")->fetchColumn();
            $data['active_users'] = (int) $pdo->query("SELECT COUNT(*) FROM platform_users WHERE role='user' AND subscription_status='active'")->fetchColumn();
            $data['trial_users'] = (int) $pdo->query("SELECT COUNT(*) FROM platform_users WHERE role='user' AND subscription_status='trial'")->fetchColumn();
            $data['expired_users'] = (int) $pdo->query("SELECT COUNT(*) FROM platform_users WHERE role='user' AND subscription_status='expired'")->fetchColumn();
            $data['free_users'] = (int) $pdo->query("SELECT COUNT(*) FROM platform_users WHERE role='user' AND subscription_status='free'")->fetchColumn();
            $data['total_revenue'] = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM platform_revenue WHERE currency='KES'")->fetchColumn();
            $data['month_revenue'] = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM platform_revenue WHERE currency='KES' AND MONTH(recorded_at)=MONTH(CURDATE()) AND YEAR(recorded_at)=YEAR(CURDATE())")->fetchColumn();
            $data['total_codes'] = (int) $pdo->query("SELECT COUNT(*) FROM subscription_codes")->fetchColumn();
            $data['unused_codes'] = (int) $pdo->query("SELECT COUNT(*) FROM subscription_codes WHERE is_used=0 AND is_revoked=0 AND (expires_at IS NULL OR expires_at > NOW())")->fetchColumn();
            $data['open_tickets'] = (int) $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status IN ('open','in_progress')")->fetchColumn();
            $data['critical_tickets'] = (int) $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE priority='critical' AND status NOT IN ('resolved','closed')")->fetchColumn();
            $data['recent_users'] = $pdo->query("SELECT id, username, email, full_name, subscription_status, created_at FROM platform_users WHERE role='user' ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            $data['recent_tickets'] = $pdo->query("SELECT id, ticket_code, subject, category, priority, status, created_at FROM support_tickets ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            $data['revenue_by_month'] = $pdo->query("SELECT DATE_FORMAT(recorded_at,'%Y-%m') AS month, SUM(amount) AS total, COUNT(*) AS count FROM platform_revenue WHERE currency='KES' GROUP BY month ORDER BY month DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($data);
            break;

        // ════ USERS ════
        case 'users':
            if ($action === 'list') {
                $status = $_GET['status'] ?? '';
                $search = $_GET['search'] ?? '';
                $sql = 'SELECT id, username, email, full_name, phone, farm_name, farm_type, county, subscription_status, subscription_expires, trial_ends, max_animals, max_fields, max_users, total_login_count, last_login, is_active, created_at FROM platform_users WHERE role = "user"';
                if ($status) $sql .= " AND subscription_status=" . $pdo->quote($status);
                if ($search) $sql .= " AND (username LIKE " . $pdo->quote("%$search%") . " OR email LIKE " . $pdo->quote("%$search%") . " OR full_name LIKE " . $pdo->quote("%$search%") . ")";
                $sql .= ' ORDER BY created_at DESC';
                echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'get') {
                $id = (int)($_GET['id'] ?? 0);
                $stmt = $pdo->prepare('SELECT * FROM platform_users WHERE id=?');
                $stmt->execute([$id]);
                echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: ['error' => 'Not found']);
            } elseif ($action === 'create') {
                $username = trim($input['username'] ?? '');
                $email = trim($input['email'] ?? '');
                $password = password_hash($input['password'] ?? 'changeme', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO platform_users (username, email, password, full_name, phone, farm_name, farm_type, county, role, subscription_status, subscription_expires, trial_ends, max_animals, max_fields, max_users) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $trialEnd = date('Y-m-d', strtotime('+30 days'));
                $stmt->execute([
                    $username, $email, $password,
                    $input['full_name'] ?? '', $input['phone'] ?? '',
                    $input['farm_name'] ?? '', $input['farm_type'] ?? '', $input['county'] ?? '',
                    'user', 'trial', $trialEnd, $trialEnd,
                    (int)($input['max_animals'] ?? 100), (int)($input['max_fields'] ?? 10), (int)($input['max_users'] ?? 3)
                ]);
                $id = (int) $pdo->lastInsertId();
                platformLog($pdo, 1, 'create_user', 'user', $id, "Created user $username");
                echo json_encode(['success' => true, 'id' => $id]);
            } elseif ($action === 'update') {
                $id = (int)($input['id'] ?? 0);
                $stmt = $pdo->prepare('UPDATE platform_users SET full_name=?, phone=?, farm_name=?, farm_type=?, county=?, subscription_status=?, subscription_expires=?, trial_ends=?, max_animals=?, max_fields=?, max_users=?, is_active=? WHERE id=?');
                $stmt->execute([
                    $input['full_name'] ?? '', $input['phone'] ?? '',
                    $input['farm_name'] ?? '', $input['farm_type'] ?? '', $input['county'] ?? '',
                    $input['subscription_status'] ?? 'active',
                    $input['subscription_expires'] ?? null, $input['trial_ends'] ?? null,
                    (int)($input['max_animals'] ?? 100), (int)($input['max_fields'] ?? 10), (int)($input['max_users'] ?? 3),
                    (int)($input['is_active'] ?? 1), $id
                ]);
                platformLog($pdo, 1, 'update_user', 'user', $id, "Updated user #$id");
                echo json_encode(['success' => true]);
            } elseif ($action === 'deactivate') {
                $id = (int)($input['id'] ?? 0);
                $pdo->prepare('UPDATE platform_users SET is_active=0, subscription_status="suspended" WHERE id=?')->execute([$id]);
                platformLog($pdo, 1, 'deactivate_user', 'user', $id);
                echo json_encode(['success' => true]);
            } elseif ($action === 'activate') {
                $id = (int)($input['id'] ?? 0);
                $pdo->prepare('UPDATE platform_users SET is_active=1, subscription_status="active" WHERE id=?')->execute([$id]);
                platformLog($pdo, 1, 'activate_user', 'user', $id);
                echo json_encode(['success' => true]);
            } elseif ($action === 'reset_password') {
                $id = (int)($input['id'] ?? 0);
                $newPass = $input['password'] ?? 'changeme';
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE platform_users SET password=? WHERE id=?')->execute([$hash, $id]);
                platformLog($pdo, 1, 'reset_password', 'user', $id);
                echo json_encode(['success' => true, 'password' => $newPass]);
            }
            break;

        // ════ SUBSCRIPTION CODES ════
        case 'codes':
            if ($action === 'list') {
                $sql = 'SELECT c.*, u.username AS used_by_name FROM subscription_codes c LEFT JOIN platform_users u ON c.used_by=u.id';
                $filter = $_GET['filter'] ?? '';
                if ($filter === 'unused') $sql .= ' WHERE c.is_used=0 AND c.is_revoked=0';
                elseif ($filter === 'used') $sql .= ' WHERE c.is_used=1';
                elseif ($filter === 'revoked') $sql .= ' WHERE c.is_revoked=1';
                $sql .= ' ORDER BY c.created_at DESC LIMIT 200';
                echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'generate') {
                $count = min((int)($input['count'] ?? 1), 50);
                $type = $input['type'] ?? 'monthly';
                $duration = (int)($input['duration_days'] ?? 30);
                $desc = $input['description'] ?? '';
                $maxAnimals = (int)($input['max_animals'] ?? 100);
                $maxFields = (int)($input['max_fields'] ?? 10);
                $maxUsers = (int)($input['max_users'] ?? 3);
                $expiresAt = $input['expires_at'] ?? date('Y-m-d H:i:s', strtotime('+1 year'));

                $codes = [];
                $stmt = $pdo->prepare('INSERT INTO subscription_codes (code, type, duration_days, max_animals, max_fields, max_users, description, created_by, expires_at) VALUES (?,?,?,?,?,?,?,?,?)');
                for ($i = 0; $i < $count; $i++) {
                    $code = generateCode();
                    $stmt->execute([$code, $type, $duration, $maxAnimals, $maxFields, $maxUsers, $desc, 1, $expiresAt]);
                    $codes[] = $code;
                }
                platformLog($pdo, 1, 'generate_codes', 'code', null, "Generated $count $type codes");
                echo json_encode(['success' => true, 'codes' => $codes, 'count' => $count]);
            } elseif ($action === 'redeem') {
                $code = strtoupper(trim($input['code'] ?? ''));
                $userId = (int)($input['user_id'] ?? 0);
                $stmt = $pdo->prepare('SELECT * FROM subscription_codes WHERE code=? AND is_used=0 AND is_revoked=0');
                $stmt->execute([$code]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$record) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid or already used code']);
                    exit;
                }
                if ($record['expires_at'] && strtotime($record['expires_at']) < time()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Code has expired']);
                    exit;
                }
                $endDate = date('Y-m-d', strtotime("+{$record['duration_days']} days"));
                $pdo->prepare('UPDATE subscription_codes SET is_used=1, used_by=?, used_at=NOW() WHERE id=?')->execute([$userId, $record['id']]);
                $pdo->prepare('UPDATE platform_users SET subscription_status="active", subscription_expires=?, max_animals=?, max_fields=?, max_users=? WHERE id=?')->execute([$endDate, $record['max_animals'], $record['max_fields'], $record['max_users'], $userId]);
                $pdo->prepare('INSERT INTO platform_subscriptions (user_id, plan, amount, payment_method, code_id, start_date, end_date, status) VALUES (?,?,"code",?,?,NOW(),?,"active")')->execute([$userId, $record['type'], $record['id'], $endDate]);
                platformLog($pdo, 1, 'redeem_code', 'code', $record['id'], "Code $code redeemed by user #$userId");
                echo json_encode(['success' => true, 'expires' => $endDate]);
            } elseif ($action === 'revoke') {
                $id = (int)($input['id'] ?? 0);
                $pdo->prepare('UPDATE subscription_codes SET is_revoked=1 WHERE id=?')->execute([$id]);
                platformLog($pdo, 1, 'revoke_code', 'code', $id);
                echo json_encode(['success' => true]);
            }
            break;

        // ════ SUBSCRIPTIONS ════
        case 'subscriptions':
            if ($action === 'list') {
                $sql = 'SELECT s.*, u.username, u.email FROM platform_subscriptions s LEFT JOIN platform_users u ON s.user_id=u.id';
                $status = $_GET['status'] ?? '';
                if ($status) $sql .= " WHERE s.status=" . $pdo->quote($status);
                $sql .= ' ORDER BY s.created_at DESC LIMIT 200';
                echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'create') {
                $userId = (int)($input['user_id'] ?? 0);
                $plan = $input['plan'] ?? 'monthly';
                $amount = (float)($input['amount'] ?? 0);
                $method = $input['payment_method'] ?? 'manual';
                $mpesaReceipt = $input['mpesa_receipt'] ?? '';
                $mpesaPhone = $input['mpesa_phone'] ?? '';
                $startDate = $input['start_date'] ?? date('Y-m-d');
                $endDate = $input['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
                $notes = $input['notes'] ?? '';

                $stmt = $pdo->prepare('INSERT INTO platform_subscriptions (user_id, plan, amount, payment_method, mpesa_receipt, mpesa_phone, start_date, end_date, status, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$userId, $plan, $amount, $method, $mpesaReceipt, $mpesaPhone, $startDate, $endDate, 'active', $notes, 1]);

                if ($amount > 0) {
                    $pdo->prepare('INSERT INTO platform_revenue (subscription_id, user_id, amount, type, payment_method, mpesa_receipt, description) VALUES (?,?,?,?,?,?,?)')->execute([$pdo->lastInsertId(), $userId, $amount, 'subscription', $method, $mpesaReceipt, "Subscription: $plan"]);
                }
                $pdo->prepare('UPDATE platform_users SET subscription_status="active", subscription_expires=? WHERE id=?')->execute([$endDate, $userId]);
                platformLog($pdo, 1, 'create_subscription', 'subscription', null, "Created $plan for user #$userId");
                echo json_encode(['success' => true]);
            } elseif ($action === 'extend') {
                $id = (int)($input['id'] ?? 0);
                $extraDays = (int)($input['extra_days'] ?? 30);
                $stmt = $pdo->prepare('SELECT * FROM platform_subscriptions WHERE id=?');
                $stmt->execute([$id]);
                $sub = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($sub) {
                    $newEnd = date('Y-m-d', strtotime($sub['end_date'] . " +{$extraDays} days"));
                    $pdo->prepare('UPDATE platform_subscriptions SET end_date=?, status="active" WHERE id=?')->execute([$newEnd, $id]);
                    $pdo->prepare('UPDATE platform_users SET subscription_status="active", subscription_expires=? WHERE id=?')->execute([$newEnd, $sub['user_id']]);
                    echo json_encode(['success' => true, 'new_end' => $newEnd]);
                }
            }
            break;

        // ════ REVENUE ════
        case 'revenue':
            if ($action === 'list') {
                $sql = 'SELECT r.*, u.username, u.email FROM platform_revenue r LEFT JOIN platform_users u ON r.user_id=u.id';
                $type = $_GET['type'] ?? '';
                if ($type) $sql .= " WHERE r.type=" . $pdo->quote($type);
                $sql .= ' ORDER BY r.recorded_at DESC LIMIT 200';
                echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'summary') {
                $data = [];
                $data['total'] = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM platform_revenue WHERE currency='KES'")->fetchColumn();
                $data['this_month'] = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM platform_revenue WHERE currency='KES' AND MONTH(recorded_at)=MONTH(CURDATE()) AND YEAR(recorded_at)=YEAR(CURDATE())")->fetchColumn();
                $data['this_year'] = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM platform_revenue WHERE currency='KES' AND YEAR(recorded_at)=YEAR(CURDATE())")->fetchColumn();
                $data['by_method'] = $pdo->query("SELECT payment_method, SUM(amount) AS total, COUNT(*) AS count FROM platform_revenue WHERE currency='KES' GROUP BY payment_method")->fetchAll(PDO::FETCH_ASSOC);
                $data['by_month'] = $pdo->query("SELECT DATE_FORMAT(recorded_at,'%Y-%m') AS month, SUM(amount) AS total, COUNT(*) AS count FROM platform_revenue WHERE currency='KES' GROUP BY month ORDER BY month DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($data);
            } elseif ($action === 'record') {
                $stmt = $pdo->prepare('INSERT INTO platform_revenue (user_id, amount, type, payment_method, mpesa_receipt, description, recorded_by) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([(int)($input['user_id'] ?? 0) ?: null, (float)($input['amount'] ?? 0), $input['type'] ?? 'subscription', $input['payment_method'] ?? 'mpesa', $input['mpesa_receipt'] ?? '', $input['description'] ?? '', 1]);
                platformLog($pdo, 1, 'record_revenue', 'revenue', null, "Recorded KES " . ($input['amount'] ?? 0));
                echo json_encode(['success' => true]);
            }
            break;

        // ════ SUPPORT TICKETS ════
        case 'tickets':
            if ($action === 'list') {
                $sql = 'SELECT t.*, u.username, u.email FROM support_tickets t LEFT JOIN platform_users u ON t.user_id=u.id';
                $where = [];
                $status = $_GET['status'] ?? '';
                $priority = $_GET['priority'] ?? '';
                if ($status) $where[] = "t.status=" . $pdo->quote($status);
                if ($priority) $where[] = "t.priority=" . $pdo->quote($priority);
                if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
                $sql .= ' ORDER BY FIELD(t.priority,"critical","high","medium","low"), t.created_at DESC LIMIT 200';
                echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'get') {
                $id = (int)($_GET['id'] ?? 0);
                $ticket = $pdo->prepare('SELECT t.*, u.username FROM support_tickets t LEFT JOIN platform_users u ON t.user_id=u.id WHERE t.id=?');
                $ticket->execute([$id]);
                $ticket = $ticket->fetch(PDO::FETCH_ASSOC);
                if (!$ticket) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
                $messages = $pdo->prepare('SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY created_at');
                $messages->execute([$id]);
                $ticket['messages'] = $messages->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($ticket);
            } elseif ($action === 'respond') {
                $ticketId = (int)($input['ticket_id'] ?? 0);
                $message = trim($input['message'] ?? '');
                $adminNotes = $input['admin_notes'] ?? null;
                $status = $input['status'] ?? null;
                $assignedTo = $input['assigned_to'] ?? null;

                $pdo->prepare('INSERT INTO ticket_messages (ticket_id, sender_id, sender_type, message) VALUES (?, 1, "admin", ?)')->execute([$ticketId, $message]);
                if ($adminNotes) $pdo->prepare('UPDATE support_tickets SET admin_notes=? WHERE id=?')->execute([$adminNotes, $ticketId]);
                if ($status) {
                    $updates = ['status' => $status];
                    if ($status === 'resolved' || $status === 'closed') $updates['resolved_at'] = date('Y-m-d H:i:s');
                    $sets = [];
                    $vals = [];
                    foreach ($updates as $k => $v) { $sets[] = "$k=?"; $vals[] = $v; }
                    $vals[] = $ticketId;
                    $pdo->prepare('UPDATE support_tickets SET ' . implode(',', $sets) . ' WHERE id=?')->execute($vals);
                }
                if ($assignedTo) $pdo->prepare('UPDATE support_tickets SET assigned_to=? WHERE id=?')->execute([$assignedTo, $ticketId]);
                platformLog($pdo, 1, 'respond_ticket', 'ticket', $ticketId, "Responded to ticket #$ticketId");
                echo json_encode(['success' => true]);
            } elseif ($action === 'create') {
                $code = 'TKT-' . strtoupper(substr(uniqid(), -8));
                $stmt = $pdo->prepare('INSERT INTO support_tickets (user_id, ticket_code, subject, category, priority, is_anonymous, reporter_name, reporter_email, reporter_phone, description) VALUES (?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([
                    (int)($input['user_id'] ?? 0) ?: null,
                    $code,
                    $input['subject'] ?? 'Support Request',
                    $input['category'] ?? 'other',
                    $input['priority'] ?? 'medium',
                    (int)($input['is_anonymous'] ?? 0),
                    $input['reporter_name'] ?? '', $input['reporter_email'] ?? '', $input['reporter_phone'] ?? '',
                    $input['description'] ?? ''
                ]);
                echo json_encode(['success' => true, 'ticket_code' => $code]);
            }
            break;

        // ════ EMERGENCY CTA ════
        case 'emergency':
            if ($action === 'contacts') {
                echo json_encode($pdo->query('SELECT * FROM emergency_contacts WHERE is_active=1 ORDER BY name')->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'report') {
                $code = 'EMG-' . strtoupper(substr(uniqid(), -8));
                $pdo->prepare('INSERT INTO support_tickets (user_id, ticket_code, subject, category, priority, is_anonymous, reporter_name, reporter_email, reporter_phone, description) VALUES (?,?,?,?,?,?,?,?,?,?)')->execute([
                    null, $code,
                    $input['subject'] ?? 'EMERGENCY',
                    'urgent', 'critical',
                    (int)($input['is_anonymous'] ?? 0),
                    $input['reporter_name'] ?? '', $input['reporter_email'] ?? '', $input['reporter_phone'] ?? '',
                    $input['description'] ?? ''
                ]);
                echo json_encode(['success' => true, 'ticket_code' => $code, 'message' => 'Emergency reported. Our team will contact you immediately.']);
            }
            break;

        // ════ ACTIVITY LOG ════
        case 'activity':
            $limit = min((int)($_GET['limit'] ?? 50), 200);
            echo json_encode($pdo->query("SELECT l.*, u.username AS admin_name FROM platform_activity_log l LEFT JOIN platform_users u ON l.admin_id=u.id ORDER BY l.created_at DESC LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC));
            break;

        // ════ SETTINGS ════
        case 'settings':
            if ($action === 'list') {
                echo json_encode($pdo->query('SELECT * FROM platform_settings ORDER BY setting_key')->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'update') {
                $key = $input['key'] ?? '';
                $value = $input['value'] ?? '';
                $pdo->prepare('UPDATE platform_settings SET setting_value=? WHERE setting_key=?')->execute([$value, $key]);
                platformLog($pdo, 1, 'update_setting', 'setting', null, "Updated $key");
                echo json_encode(['success' => true]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => "Unknown module: $module", 'available' => ['auth','dashboard','users','codes','subscriptions','revenue','tickets','emergency','activity','settings']]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
