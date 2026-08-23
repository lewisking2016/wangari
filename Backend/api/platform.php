<?php
/**
 * Platform Admin API — Optimized
 * Single endpoint for all wangariadmin modules
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/email_policy.php';
$pdo = getDatabaseConnection();
if (!$pdo) { http_response_code(500); echo json_encode(['error' => 'Database connection failed']); exit; }

$module = $_GET['module'] ?? '';
$action = $_GET['action'] ?? 'list';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// Helper: run query safely, return default on failure
function safeQuery(PDO $pdo, string $sql, array $params = [], $default = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return $default; }
}
function safeScalar(PDO $pdo, string $sql, array $params = [], $default = 0) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() ?: $default;
    } catch (Exception $e) { return $default; }
}
function generateCode(string $prefix = 'WGR', int $length = 12): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = $prefix . '-';
    for ($i = 0; $i < $length; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
    return $code;
}
function platformLog(PDO $pdo, ?int $adminId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): void {
    $pdo->prepare('INSERT INTO platform_activity_log (admin_id, action, target_type, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$adminId, $action, $targetType, $targetId, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
}

try {
    switch ($module) {

        // ════ AUTH ════
        case 'auth':
            if ($action === 'login') {
                $username = trim($input['username'] ?? '');
                $password = $input['password'] ?? '';
                if (!$username || !$password) { http_response_code(400); echo json_encode(['error' => 'Username and password required']); exit; }
                $stmt = $pdo->prepare('SELECT * FROM platform_users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1');
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$user || !password_verify($password, $user['password'])) { http_response_code(401); echo json_encode(['error' => 'Invalid credentials']); exit; }
                if (!in_array($user['role'], ['super_admin', 'admin', 'support'], true)) { http_response_code(403); echo json_encode(['error' => 'Access denied']); exit; }
                $pdo->prepare('UPDATE platform_users SET last_login=NOW(), total_login_count=total_login_count+1 WHERE id=?')->execute([$user['id']]);
                platformLog($pdo, $user['id'], 'login', 'user', $user['id']);
                echo json_encode(['success' => true, 'user' => ['id'=>$user['id'],'username'=>$user['username'],'email'=>$user['email'],'role'=>$user['role'],'full_name'=>$user['full_name']]]);
            }
            break;

        // ════ DASHBOARD ════ — Single combined query for speed
        case 'dashboard':
            $data = [];

            // Combined user counts from both tables in minimal queries
            $data['total_users'] = (int)safeScalar($pdo, "SELECT COUNT(*) FROM platform_users WHERE role='user'")
                + (int)safeScalar($pdo, "SELECT COUNT(*) FROM users WHERE id NOT IN (SELECT id FROM platform_users WHERE role='user')");
            $data['active_users'] = (int)safeScalar($pdo, "SELECT COUNT(*) FROM platform_users WHERE role='user' AND subscription_status='active'");
            $data['trial_users'] = (int)safeScalar($pdo, "SELECT COUNT(*) FROM platform_users WHERE role='user' AND subscription_status='trial'");
            $data['expired_users'] = (int)safeScalar($pdo, "SELECT COUNT(*) FROM platform_users WHERE role='user' AND subscription_status='expired'");
            $data['free_users'] = (int)safeScalar($pdo, "SELECT COUNT(*) FROM platform_users WHERE role='user' AND subscription_status='free'");

            // Revenue
            $data['total_revenue'] = (float)safeScalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM platform_revenue WHERE currency='KES'");
            $data['month_revenue'] = (float)safeScalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM platform_revenue WHERE currency='KES' AND MONTH(recorded_at)=MONTH(CURDATE()) AND YEAR(recorded_at)=YEAR(CURDATE())");

            // Licenses
            $data['total_codes'] = (int)safeScalar($pdo, "SELECT COUNT(*) FROM wangari_licenses");
            $data['unused_codes'] = (int)safeScalar($pdo, "SELECT COUNT(*) FROM wangari_licenses WHERE status='active' AND hardware_id IS NULL AND (expires_at IS NULL OR expires_at > NOW())");

            // Tickets
            $data['open_tickets'] = (int)safeScalar($pdo, "SELECT COUNT(*) FROM support_tickets WHERE status IN ('open','in_progress')");
            $data['critical_tickets'] = (int)safeScalar($pdo, "SELECT COUNT(*) FROM support_tickets WHERE priority='critical' AND status NOT IN ('resolved','closed')");

            // Recent data
            $data['recent_users'] = safeQuery($pdo, "SELECT id, username, email, full_name, subscription_status, created_at FROM platform_users WHERE role='user' ORDER BY created_at DESC LIMIT 5");
            if (empty($data['recent_users'])) {
                $data['recent_users'] = safeQuery($pdo, "SELECT id, username, email, username AS full_name, 'free' AS subscription_status, created_at FROM users WHERE role <> 'super_admin' ORDER BY created_at DESC LIMIT 5");
            }
            $data['recent_tickets'] = safeQuery($pdo, "SELECT id, ticket_code, subject, category, priority, status, created_at FROM support_tickets ORDER BY created_at DESC LIMIT 5");
            $data['revenue_by_month'] = safeQuery($pdo, "SELECT DATE_FORMAT(recorded_at,'%Y-%m') AS month, SUM(amount) AS total, COUNT(*) AS count FROM platform_revenue WHERE currency='KES' GROUP BY month ORDER BY month DESC LIMIT 12");

            echo json_encode($data);
            break;

        // ════ USERS ════
        case 'users':
            if ($action === 'list') {
                $status = $_GET['status'] ?? '';
                $search = $_GET['search'] ?? '';
                $page = max(1, (int)($_GET['page'] ?? 1));
                $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 50)));
                $offset = ($page - 1) * $perPage;
                $users = [];

                // platform_users
                try {
                    $where = ["role = 'user'"];
                    $params = [];
                    if ($status) { $where[] = "subscription_status=?"; $params[] = $status; }
                    if ($search) { $where[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]); }
                    $w = implode(' AND ', $where);
                    $users = safeQuery($pdo, "SELECT id, username, email, full_name, phone, farm_name, farm_type, county, subscription_status, subscription_expires, trial_ends, max_animals, max_fields, max_users, total_login_count, last_login, is_active, created_at FROM platform_users WHERE $w ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", $params);
                } catch (Exception $e) {}

                // users table (Google/manual registrations)
                try {
                    $cols = safeQuery($pdo, 'SHOW COLUMNS FROM users', [], []);
                    $colNames = array_column($cols, 'Field');
                    $hasFullName = in_array('full_name', $colNames);
                    $hasFirstName = in_array('first_name', $colNames);
                    $nameExpr = $hasFullName ? 'full_name' : ($hasFirstName ? 'CONCAT(COALESCE(first_name,"")," ",COALESCE(last_name,""))' : 'username');
                    $phoneExpr = in_array('phone_number', $colNames) ? 'phone_number' : (in_array('phone', $colNames) ? 'phone' : "''");

                    $whereU = ["role <> 'super_admin'", "id NOT IN (SELECT id FROM platform_users WHERE role='user')"];
                    $paramsU = [];
                    if ($search) { $whereU[] = "(username LIKE ? OR email LIKE ?)"; $paramsU = ["%$search%", "%$search%"]; }
                    $wU = implode(' AND ', $whereU);
                    $extraUsers = safeQuery($pdo, "SELECT id, username, email, $nameExpr AS full_name, $phoneExpr AS phone, '' AS farm_name, '' AS farm_type, '' AS county, CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 40 DAY) THEN 'trial' ELSE 'free' END AS subscription_status, NULL AS subscription_expires, NULL AS trial_ends, 100 AS max_animals, 10 AS max_fields, 3 AS max_users, 0 AS total_login_count, NULL AS last_login, 1 AS is_active, created_at FROM users WHERE $wU ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", $paramsU);
                    $existingIds = array_column($users, 'id');
                    foreach ($extraUsers as $eu) { if (!in_array($eu['id'], $existingIds)) $users[] = $eu; }
                } catch (Exception $e) {}

                echo json_encode($users);
            } elseif ($action === 'get') {
                $id = (int)($_GET['id'] ?? 0);
                $user = $pdo->prepare('SELECT * FROM platform_users WHERE id=?');
                $user->execute([$id]);
                $user = $user->fetch(PDO::FETCH_ASSOC);
                if (!$user) {
                    $user = $pdo->prepare('SELECT * FROM users WHERE id=?');
                    $user->execute([$id]);
                    $user = $user->fetch(PDO::FETCH_ASSOC);
                }
                echo json_encode($user ?: ['error' => 'Not found']);
            } elseif ($action === 'create') {
                $username = trim($input['username'] ?? '');
                $email = trim($input['email'] ?? '');
                if ($email === '' || !wangariIsAllowedEmail($email)) {
                    http_response_code(400); echo json_encode(['error' => 'Only Gmail and Outlook email addresses are allowed']); exit;
                }
                $password = password_hash($input['password'] ?? 'changeme', PASSWORD_DEFAULT);
                $trialEnd = date('Y-m-d', strtotime('+40 days'));
                $stmt = $pdo->prepare('INSERT INTO platform_users (username, email, password, full_name, phone, farm_name, farm_type, county, role, subscription_status, subscription_expires, trial_ends, max_animals, max_fields, max_users) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
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
                $stmt = $pdo->prepare('UPDATE platform_users SET email=?, full_name=?, phone=?, farm_name=?, farm_type=?, county=?, subscription_status=?, subscription_expires=?, trial_ends=?, max_animals=?, max_fields=?, max_users=?, is_active=? WHERE id=?');
                $stmt->execute([
                    $input['email'] ?? '', $input['full_name'] ?? '', $input['phone'] ?? '',
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
                // Try platform_users first, then users
                $pdo->prepare('UPDATE platform_users SET password=? WHERE id=?')->execute([$hash, $id]);
                $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hash, $id]);
                platformLog($pdo, 1, 'reset_password', 'user', $id);
                echo json_encode(['success' => true, 'password' => $newPass]);
            }
            break;

        // ════ LICENSES ════
        case 'codes':
            if ($action === 'list') {
                $filter = $_GET['filter'] ?? '';
                $where = [];
                if ($filter === 'unused') $where[] = "l.status='active' AND l.hardware_id IS NULL";
                elseif ($filter === 'used') $where[] = "l.hardware_id IS NOT NULL";
                elseif ($filter === 'revoked') $where[] = "l.status='revoked'";
                $w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
                echo json_encode(safeQuery($pdo, "SELECT l.*, u.username AS account_username, u.full_name AS account_full_name, u.email AS account_email FROM wangari_licenses l LEFT JOIN platform_users u ON u.id = l.user_id $w ORDER BY l.created_at DESC LIMIT 200"));
            } elseif ($action === 'generate') {
                $count = min((int)($input['count'] ?? 1), 50);
                $plan = trim($input['plan'] ?? 'desktop');
                $userId = (int)($input['user_id'] ?? 0);
                $maxDevices = max(1, (int)($input['max_devices'] ?? 1));
                $expiresAt = $input['expires_at'] ?? null;
                if ($expiresAt === '' || !$expiresAt) $expiresAt = date('Y-m-d H:i:s', strtotime('+1 year'));
                if ($userId <= 0) { http_response_code(400); echo json_encode(['error' => 'Please select a registered user']); exit; }

                $user = $pdo->prepare('SELECT id, username, email, full_name, is_active, role FROM platform_users WHERE id=? LIMIT 1');
                $user->execute([$userId]);
                $user = $user->fetch(PDO::FETCH_ASSOC);
                if (!$user || ($user['role'] ?? '') !== 'user' || (int)($user['is_active'] ?? 0) !== 1) {
                    http_response_code(400); echo json_encode(['error' => 'Selected account is not available']); exit;
                }
                $customerName = trim((string)($user['full_name'] ?? $user['username'] ?? ''));
                $customerEmail = trim((string)($user['email'] ?? ''));

                $codes = [];
                $stmt = $pdo->prepare('INSERT INTO wangari_licenses (license_key, user_id, customer_name, customer_email, plan, status, hardware_id, activations, max_devices, expires_at, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                for ($i = 0; $i < $count; $i++) {
                    $code = generateCode();
                    $stmt->execute([$code, $userId, $customerName, $customerEmail, $plan, 'active', null, 0, $maxDevices, $expiresAt, 1]);
                    $codes[] = $code;
                }
                platformLog($pdo, 1, 'generate_codes', 'license', $userId, "Generated $count licenses for user #$userId");
                echo json_encode(['success' => true, 'codes' => $codes, 'count' => $count]);
            } elseif ($action === 'revoke') {
                $id = (int)($input['id'] ?? 0);
                $pdo->prepare('UPDATE wangari_licenses SET status="revoked" WHERE id=?')->execute([$id]);
                platformLog($pdo, 1, 'revoke_code', 'license', $id);
                echo json_encode(['success' => true]);
            }
            break;

        // ════ SUBSCRIPTIONS ════
        case 'subscriptions':
            if ($action === 'list') {
                $status = $_GET['status'] ?? '';
                $where = $status ? "WHERE s.status=" . $pdo->quote($status) : '';
                echo json_encode(safeQuery($pdo, "SELECT s.*, u.username, u.email, u.full_name FROM platform_subscriptions s LEFT JOIN platform_users u ON s.user_id=u.id $where ORDER BY s.created_at DESC LIMIT 200"));
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

                $pdo->prepare('INSERT INTO platform_subscriptions (user_id, plan, amount, payment_method, mpesa_receipt, mpesa_phone, start_date, end_date, status, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$userId, $plan, $amount, $method, $mpesaReceipt, $mpesaPhone, $startDate, $endDate, 'active', $notes, 1]);

                if ($amount > 0) {
                    $pdo->prepare('INSERT INTO platform_revenue (subscription_id, user_id, amount, type, payment_method, mpesa_receipt, description) VALUES (?,?,?,?,?,?,?)')
                        ->execute([$pdo->lastInsertId(), $userId, $amount, 'subscription', $method, $mpesaReceipt, "Subscription: $plan"]);
                }
                $pdo->prepare('UPDATE platform_users SET subscription_status="active", subscription_expires=? WHERE id=?')->execute([$endDate, $userId]);
                platformLog($pdo, 1, 'create_subscription', 'subscription', null, "Created $plan for user #$userId");
                echo json_encode(['success' => true]);
            } elseif ($action === 'update') {
                $id = (int)($input['id'] ?? 0);
                $pdo->prepare('UPDATE platform_subscriptions SET plan=?, amount=?, status=?, notes=? WHERE id=?')
                    ->execute([$input['plan'] ?? '', (float)($input['amount'] ?? 0), $input['status'] ?? 'active', $input['notes'] ?? '', $id]);
                platformLog($pdo, 1, 'update_subscription', 'subscription', $id);
                echo json_encode(['success' => true]);
            } elseif ($action === 'extend') {
                $id = (int)($input['id'] ?? 0);
                $extraDays = (int)($input['extra_days'] ?? 30);
                $sub = $pdo->prepare('SELECT * FROM platform_subscriptions WHERE id=?');
                $sub->execute([$id]);
                $sub = $sub->fetch(PDO::FETCH_ASSOC);
                if ($sub) {
                    $newEnd = date('Y-m-d', strtotime($sub['end_date'] . " +{$extraDays} days"));
                    $pdo->prepare('UPDATE platform_subscriptions SET end_date=?, status="active" WHERE id=?')->execute([$newEnd, $id]);
                    $pdo->prepare('UPDATE platform_users SET subscription_status="active", subscription_expires=? WHERE id=?')->execute([$newEnd, $sub['user_id']]);
                    echo json_encode(['success' => true, 'new_end' => $newEnd]);
                }
            } elseif ($action === 'cancel') {
                $id = (int)($input['id'] ?? 0);
                $pdo->prepare('UPDATE platform_subscriptions SET status="cancelled" WHERE id=?')->execute([$id]);
                platformLog($pdo, 1, 'cancel_subscription', 'subscription', $id);
                echo json_encode(['success' => true]);
            }
            break;

        // ════ REVENUE ════
        case 'revenue':
            if ($action === 'list') {
                $type = $_GET['type'] ?? '';
                $where = $type ? "WHERE r.type=" . $pdo->quote($type) : '';
                echo json_encode(safeQuery($pdo, "SELECT r.*, u.username, u.email, u.full_name FROM platform_revenue r LEFT JOIN platform_users u ON r.user_id=u.id $where ORDER BY r.recorded_at DESC LIMIT 200"));
            } elseif ($action === 'summary') {
                $data = [];
                $data['total'] = (float)safeScalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM platform_revenue WHERE currency='KES'");
                $data['this_month'] = (float)safeScalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM platform_revenue WHERE currency='KES' AND MONTH(recorded_at)=MONTH(CURDATE()) AND YEAR(recorded_at)=YEAR(CURDATE())");
                $data['this_year'] = (float)safeScalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM platform_revenue WHERE currency='KES' AND YEAR(recorded_at)=YEAR(CURDATE())");
                $data['by_method'] = safeQuery($pdo, "SELECT payment_method, SUM(amount) AS total, COUNT(*) AS count FROM platform_revenue WHERE currency='KES' GROUP BY payment_method");
                $data['by_month'] = safeQuery($pdo, "SELECT DATE_FORMAT(recorded_at,'%Y-%m') AS month, SUM(amount) AS total, COUNT(*) AS count FROM platform_revenue WHERE currency='KES' GROUP BY month ORDER BY month DESC LIMIT 12");
                echo json_encode($data);
            } elseif ($action === 'record') {
                $stmt = $pdo->prepare('INSERT INTO platform_revenue (user_id, amount, type, payment_method, mpesa_receipt, description, recorded_by) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([(int)($input['user_id'] ?? 0) ?: null, (float)($input['amount'] ?? 0), $input['type'] ?? 'subscription', $input['payment_method'] ?? 'mpesa', $input['mpesa_receipt'] ?? '', $input['description'] ?? '', 1]);
                platformLog($pdo, 1, 'record_revenue', 'revenue', null, "Recorded KES " . ($input['amount'] ?? 0));
                echo json_encode(['success' => true]);
            }
            break;

        // ════ TICKETS ════
        case 'tickets':
            if ($action === 'list') {
                $where = [];
                $params = [];
                $status = $_GET['status'] ?? '';
                $priority = $_GET['priority'] ?? '';
                if ($status) { $where[] = "t.status=?"; $params[] = $status; }
                if ($priority) { $where[] = "t.priority=?"; $params[] = $priority; }
                $w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
                echo json_encode(safeQuery($pdo, "SELECT t.*, u.username, u.email FROM support_tickets t LEFT JOIN platform_users u ON t.user_id=u.id $w ORDER BY FIELD(t.priority,'critical','high','medium','low'), t.created_at DESC LIMIT 200", $params));
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
                $status = $input['status'] ?? null;
                if ($message) {
                    $pdo->prepare('INSERT INTO ticket_messages (ticket_id, sender_id, sender_type, message) VALUES (?, 1, "admin", ?)')->execute([$ticketId, $message]);
                }
                if ($status) {
                    $updates = ['status' => $status];
                    if ($status === 'resolved' || $status === 'closed') $updates['resolved_at'] = date('Y-m-d H:i:s');
                    $sets = []; $vals = [];
                    foreach ($updates as $k => $v) { $sets[] = "$k=?"; $vals[] = $v; }
                    $vals[] = $ticketId;
                    $pdo->prepare('UPDATE support_tickets SET ' . implode(',', $sets) . ' WHERE id=?')->execute($vals);
                }
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
                platformLog($pdo, 1, 'create_ticket', 'ticket', null, "Created ticket $code");
                echo json_encode(['success' => true, 'ticket_code' => $code]);
            }
            break;

        // ════ EMERGENCY ════
        case 'emergency':
            if ($action === 'contacts') {
                echo json_encode(safeQuery($pdo, 'SELECT * FROM emergency_contacts ORDER BY name'));
            } elseif ($action === 'create_contact') {
                $stmt = $pdo->prepare('INSERT INTO emergency_contacts (name, phone, email, role, is_active) VALUES (?,?,?,?,?)');
                $stmt->execute([$input['name'] ?? '', $input['phone'] ?? '', $input['email'] ?? '', $input['role'] ?? '', (int)($input['is_active'] ?? 1)]);
                platformLog($pdo, 1, 'create_emergency_contact', 'emergency', null, "Added contact " . ($input['name'] ?? ''));
                echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
            } elseif ($action === 'update_contact') {
                $id = (int)($input['id'] ?? 0);
                $pdo->prepare('UPDATE emergency_contacts SET name=?, phone=?, email=?, role=?, is_active=? WHERE id=?')
                    ->execute([$input['name'] ?? '', $input['phone'] ?? '', $input['email'] ?? '', $input['role'] ?? '', (int)($input['is_active'] ?? 1), $id]);
                platformLog($pdo, 1, 'update_emergency_contact', 'emergency', $id);
                echo json_encode(['success' => true]);
            } elseif ($action === 'delete_contact') {
                $id = (int)($input['id'] ?? 0);
                $pdo->prepare('DELETE FROM emergency_contacts WHERE id=?')->execute([$id]);
                platformLog($pdo, 1, 'delete_emergency_contact', 'emergency', $id);
                echo json_encode(['success' => true]);
            } elseif ($action === 'recent') {
                echo json_encode(safeQuery($pdo, "SELECT * FROM support_tickets WHERE category='urgent' ORDER BY created_at DESC LIMIT 20"));
            } elseif ($action === 'report') {
                $code = 'EMG-' . strtoupper(substr(uniqid(), -8));
                $pdo->prepare('INSERT INTO support_tickets (user_id, ticket_code, subject, category, priority, is_anonymous, reporter_name, reporter_email, reporter_phone, description) VALUES (?,?,?,?,?,?,?,?,?,?)')
                    ->execute([null, $code, $input['subject'] ?? 'EMERGENCY', 'urgent', 'critical', (int)($input['is_anonymous'] ?? 0), $input['reporter_name'] ?? '', $input['reporter_email'] ?? '', $input['reporter_phone'] ?? '', $input['description'] ?? '']);
                platformLog($pdo, 1, 'report_emergency', 'emergency', null, "Emergency: " . ($input['subject'] ?? ''));
                echo json_encode(['success' => true, 'ticket_code' => $code]);
            }
            break;

        // ════ ACTIVITY LOG ════
        case 'activity':
            $limit = min((int)($_GET['limit'] ?? 50), 200);
            $actionFilter = $_GET['action_filter'] ?? '';
            $where = $actionFilter ? "WHERE l.action=" . $pdo->quote($actionFilter) : '';
            echo json_encode(safeQuery($pdo, "SELECT l.*, u.username AS admin_name FROM platform_activity_log l LEFT JOIN platform_users u ON l.admin_id=u.id $where ORDER BY l.created_at DESC LIMIT $limit"));
            break;

        // ════ SETTINGS ════
        case 'settings':
            if ($action === 'list') {
                echo json_encode(safeQuery($pdo, 'SELECT * FROM platform_settings ORDER BY setting_key'));
            } elseif ($action === 'update') {
                $key = $input['key'] ?? '';
                $value = $input['value'] ?? '';
                // Upsert: update if exists, insert if not
                $exists = safeScalar($pdo, "SELECT COUNT(*) FROM platform_settings WHERE setting_key=?", [$key]);
                if ($exists) {
                    $pdo->prepare('UPDATE platform_settings SET setting_value=? WHERE setting_key=?')->execute([$value, $key]);
                } else {
                    $pdo->prepare('INSERT INTO platform_settings (setting_key, setting_value) VALUES (?,?)')->execute([$key, $value]);
                }
                platformLog($pdo, 1, 'update_setting', 'setting', null, "Updated $key");
                echo json_encode(['success' => true]);
            }
            break;

        // ════ PLATFORM USERS LIST (for dropdowns) ════
        case 'platform_users':
            echo json_encode(safeQuery($pdo, "SELECT id, username, email, full_name, subscription_status FROM platform_users WHERE role='user' AND is_active=1 ORDER BY full_name, username"));
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => "Unknown module: $module", 'available' => ['auth','dashboard','users','codes','subscriptions','revenue','tickets','emergency','activity','settings','platform_users']]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
