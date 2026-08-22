<?php
/**
 * Team Management API — Pending requests, team dashboard, sub-farms,
 * notifications, sales performance, activity trail.
 */
declare(strict_types=1);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/role_permissions.php';
$pdo = getDatabaseConnection();
if (!$pdo) { http_response_code(500); echo json_encode(['error' => 'Database connection failed']); exit; }

if (session_status() === PHP_SESSION_NONE) session_start();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$userId = (int)($_SESSION['user_id'] ?? 0);

if (!$userId) { http_response_code(401); echo json_encode(['error' => 'Not authenticated']); exit; }

// ── Helper: get farm for user ──
function getUserFarm($pdo, int $userId): ?array {
    $stmt = $pdo->prepare("SELECT f.* FROM farms f JOIN farm_members fm ON f.id = fm.farm_id WHERE fm.user_id = ? AND fm.status = 'active' AND f.is_active = 1 LIMIT 1");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ── Helper: get farm owner ──
function getFarmOwner($pdo, int $farmId): ?array {
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.full_name, u.email FROM users u JOIN farm_members fm ON u.id = fm.user_id WHERE fm.farm_id = ? AND fm.role = 'farm_owner' LIMIT 1");
    $stmt->execute([$farmId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ── Helper: create notification ──
function createNotification($pdo, ?int $farmId, ?int $targetUserId, string $type, string $title, string $message, array $data = []): void {
    $pdo->prepare("INSERT INTO notifications (farm_id, user_id, type, title, message, data, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())")
        ->execute([$farmId, $targetUserId, $type, $title, $message, !empty($data) ? json_encode($data) : null]);
}

// ═══════════════════════════════════════════════════════════
// ACTION: pending_requests — Owner sees join requests
// ═══════════════════════════════════════════════════════════
if ($action === 'pending_requests') {
    $farm = getUserFarm($pdo, $userId);
    if (!$farm) { echo json_encode(['success' => true, 'requests' => []]); exit; }

    $stmt = $pdo->prepare("
        SELECT fj.id, fj.code_used, fj.requested_role, fj.status, fj.created_at,
               u.id as user_id, u.username, u.full_name, u.email, u.phone, u.profile_pic,
               fc.code as invite_code
        FROM farm_join_requests fj
        JOIN users u ON fj.user_id = u.id
        LEFT JOIN farm_codes fc ON fj.code_used = fc.code
        WHERE fj.farm_id = ? AND fj.status = 'pending'
        ORDER BY fj.created_at DESC
    ");
    $stmt->execute([$farm['id']]);
    echo json_encode(['success' => true, 'requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: approve_request / reject_request — Owner reviews request
// ═══════════════════════════════════════════════════════════
if ($action === 'approve_request' || $action === 'reject_request') {
    $requestId = (int)($input['request_id'] ?? $_GET['request_id'] ?? 0);
    if (!$requestId) { http_response_code(400); echo json_encode(['error' => 'Request ID required']); exit; }

    $farm = getUserFarm($pdo, $userId);
    if (!$farm) { http_response_code(403); echo json_encode(['error' => 'No farm']); exit; }

    $stmt = $pdo->prepare("SELECT fj.*, u.full_name, u.username FROM farm_join_requests fj JOIN users u ON fj.user_id = u.id WHERE fj.id = ? AND fj.farm_id = ?");
    $stmt->execute([$requestId, $farm['id']]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$request) { http_response_code(404); echo json_encode(['error' => 'Request not found']); exit; }

    $isApprove = $action === 'approve_request';
    $newStatus = $isApprove ? 'approved' : 'rejected';

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE farm_join_requests SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
            ->execute([$newStatus, $userId, $requestId]);

        if ($isApprove) {
            $pdo->prepare("INSERT IGNORE INTO farm_members (farm_id, user_id, role, status, approved_by, joined_at) VALUES (?, ?, ?, 'active', ?, NOW())")
                ->execute([$farm['id'], $request['user_id'], $request['requested_role'], $userId]);
            // Notify the worker
            createNotification($pdo, $farm['id'], $request['user_id'], 'join_approved',
                'Welcome to ' . $farm['name'] . '!',
                "Your request to join as {$request['requested_role']} has been approved.",
                ['farm_name' => $farm['name'], 'role' => $request['requested_role']]
            );
        } else {
            $pdo->prepare("DELETE FROM farm_members WHERE farm_id = ? AND user_id = ?")->execute([$farm['id'], $request['user_id']]);
            createNotification($pdo, $farm['id'], $request['user_id'], 'join_rejected',
                'Request not approved',
                "Your request to join {$farm['name']} was not approved.",
                ['farm_name' => $farm['name']]
            );
        }

        // Notify all farm owners about the decision
        $owner = getFarmOwner($pdo, $farm['id']);
        if ($owner && $owner['id'] !== $userId) {
            createNotification($pdo, $farm['id'], $owner['id'], 'member_' . $newStatus,
                "Request {$newStatus}",
                "{$request['full_name']} was {$newStatus} as {$request['requested_role']}",
                ['user' => $request['full_name'], 'role' => $request['requested_role'], 'status' => $newStatus]
            );
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'status' => $newStatus, 'user' => $request['full_name']]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: team_dashboard — Full team overview
// ═══════════════════════════════════════════════════════════
if ($action === 'team_dashboard') {
    $farm = getUserFarm($pdo, $userId);
    if (!$farm) { echo json_encode(['success' => true, 'team' => [], 'stats' => []]); exit; }

    // Get all members with activity status
    $stmt = $pdo->prepare("
        SELECT fm.id as membership_id, fm.role, fm.status, fm.joined_at,
               u.id as user_id, u.username, u.full_name, u.email, u.phone, u.profile_pic,
               ua.last_active, ua.current_page,
               CASE
                   WHEN ua.last_active IS NULL THEN 'never'
                   WHEN TIMESTAMPDIFF(MINUTE, ua.last_active, NOW()) < 5 THEN 'online'
                   WHEN TIMESTAMPDIFF(MINUTE, ua.last_active, NOW()) < 30 THEN 'idle'
                   ELSE 'offline'
               END as online_status
        FROM farm_members fm
        JOIN users u ON fm.user_id = u.id
        LEFT JOIN user_activity ua ON u.id = ua.user_id
        WHERE fm.farm_id = ? AND fm.status = 'active'
        ORDER BY fm.role = 'farm_owner' DESC, fm.joined_at ASC
    ");
    $stmt->execute([$farm['id']]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Role breakdown
    $roleCounts = [];
    foreach ($members as $m) {
        $role = $m['role'];
        $roleCounts[$role] = ($roleCounts[$role] ?? 0) + 1;
    }

    // Online stats
    $onlineCount = count(array_filter($members, fn($m) => $m['online_status'] === 'online'));
    $totalMembers = count($members);

    echo json_encode([
        'success' => true,
        'farm' => ['id' => $farm['id'], 'name' => $farm['name'], 'farm_code' => $farm['farm_code']],
        'members' => $members,
        'stats' => [
            'total' => $totalMembers,
            'online' => $onlineCount,
            'by_role' => $roleCounts,
        ],
    ]);
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: change_role — Owner promotes/demotes a member
// ═══════════════════════════════════════════════════════════
if ($action === 'change_role') {
    $memberId = (int)($input['member_id'] ?? 0);
    $newRole = $input['new_role'] ?? '';
    if (!$memberId || !$newRole) { http_response_code(400); echo json_encode(['error' => 'member_id and new_role required']); exit; }

    $validRoles = array_keys(WANGARI_ROLES);
    if (!in_array($newRole, $validRoles)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid role. Valid: ' . implode(', ', $validRoles)]);
        exit;
    }

    $farm = getUserFarm($pdo, $userId);
    if (!$farm) { http_response_code(403); echo json_encode(['error' => 'No farm']); exit; }

    // Can't change own role
    $stmt = $pdo->prepare("SELECT user_id FROM farm_members WHERE id = ? AND farm_id = ?");
    $stmt->execute([$memberId, $farm['id']]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$member) { http_response_code(404); echo json_encode(['error' => 'Member not found']); exit; }
    if ($member['user_id'] == $userId) { http_response_code(400); echo json_encode(['error' => 'Cannot change your own role']); exit; }

    // Get old role for notification
    $stmt = $pdo->prepare("SELECT role FROM farm_members WHERE id = ?");
    $stmt->execute([$memberId]);
    $oldRole = $stmt->fetchColumn();

    $pdo->prepare("UPDATE farm_members SET role = ? WHERE id = ? AND farm_id = ?")->execute([$newRole, $memberId, $farm['id']]);
    $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $member['user_id']]);

    // Notify the affected user
    createNotification($pdo, $farm['id'], $member['user_id'], 'role_changed',
        'Your role has been changed',
        "Your role in {$farm['name']} changed from {$oldRole} to {$newRole}",
        ['old_role' => $oldRole, 'new_role' => $newRole]
    );

    echo json_encode(['success' => true, 'old_role' => $oldRole, 'new_role' => $newRole]);
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: remove_member — Owner removes a team member
// ═══════════════════════════════════════════════════════════
if ($action === 'remove_member') {
    $memberId = (int)($input['member_id'] ?? 0);
    if (!$memberId) { http_response_code(400); echo json_encode(['error' => 'member_id required']); exit; }

    $farm = getUserFarm($pdo, $userId);
    if (!$farm) { http_response_code(403); echo json_encode(['error' => 'No farm']); exit; }

    $stmt = $pdo->prepare("SELECT fm.user_id, u.full_name FROM farm_members fm JOIN users u ON fm.user_id = u.id WHERE fm.id = ? AND fm.farm_id = ?");
    $stmt->execute([$memberId, $farm['id']]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$member) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
    if ($member['user_id'] == $userId) { http_response_code(400); echo json_encode(['error' => 'Cannot remove yourself']); exit; }

    $pdo->prepare("UPDATE farm_members SET status = 'removed' WHERE id = ?")->execute([$memberId]);
    createNotification($pdo, $farm['id'], $member['user_id'], 'removed_from_farm',
        'Removed from team',
        "You have been removed from {$farm['name']}",
        ['farm_name' => $farm['name']]
    );

    echo json_encode(['success' => true, 'removed' => $member['full_name']]);
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: activity_trail — Recent activity for the farm
// ═══════════════════════════════════════════════════════════
if ($action === 'activity_trail') {
    $farm = getUserFarm($pdo, $userId);
    if (!$farm) { echo json_encode(['success' => true, 'activity' => []]); exit; }

    $limit = min(100, max(10, (int)($input['limit'] ?? 50)));
    $stmt = $pdo->prepare("
        SELECT al.*, u.username, u.full_name, u.profile_pic
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.farm_id = ?
        ORDER BY al.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$farm['id'], $limit]);

    echo json_encode(['success' => true, 'activity' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: notifications — User's notifications
// ═══════════════════════════════════════════════════════════
if ($action === 'notifications') {
    $showAll = isset($_GET['all']);
    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    $params = [$userId];
    if (!$showAll) { $sql .= " AND is_read = 0"; }
    $sql .= " ORDER BY created_at DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $unread = (int)$stmt->fetchColumn();

    echo json_encode(['success' => true, 'notifications' => $notifications, 'unread_count' => $unread]);
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: mark_read — Mark notification as read
// ═══════════════════════════════════════════════════════════
if ($action === 'mark_read') {
    $notifId = (int)($input['notification_id'] ?? 0);
    if ($notifId) {
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([$notifId, $userId]);
    } else {
        // Mark all as read
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$userId]);
    }
    echo json_encode(['success' => true]);
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: ping — Update user's last active timestamp
// ═══════════════════════════════════════════════════════════
if ($action === 'ping') {
    $page = $input['page'] ?? $_GET['page'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $pdo->prepare("INSERT INTO user_activity (user_id, last_active, current_page, ip_address) VALUES (?, NOW(), ?, ?) ON DUPLICATE KEY UPDATE last_active = NOW(), current_page = ?, ip_address = ?")
        ->execute([$userId, $page, $ip, $page, $ip]);
    echo json_encode(['success' => true]);
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: sub_farms — List all farms for the user (owner)
// ═══════════════════════════════════════════════════════════
if ($action === 'sub_farms') {
    $stmt = $pdo->prepare("
        SELECT f.id, f.name, f.farm_code, f.location, f.created_at,
               (SELECT COUNT(*) FROM farm_members WHERE farm_id = f.id AND status = 'active') as member_count,
               (SELECT COUNT(*) FROM animals WHERE id IN (SELECT entity_id FROM activity_log WHERE farm_id = f.id AND entity_type = 'animal')) as animal_count
        FROM farms f
        JOIN farm_members fm ON f.id = fm.farm_id
        WHERE fm.user_id = ? AND fm.status = 'active' AND f.is_active = 1
        ORDER BY f.created_at ASC
    ");
    $stmt->execute([$userId]);
    $farms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'farms' => $farms]);
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: create_farm — Owner creates a new sub-farm/branch
// ═══════════════════════════════════════════════════════════
if ($action === 'create_farm') {
    $name = trim($input['name'] ?? '');
    $location = trim($input['location'] ?? '');
    if (empty($name)) { http_response_code(400); echo json_encode(['error' => 'Farm name required']); exit; }

    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = 'WGRI-';
    for ($i = 0; $i < 12; $i++) {
        if ($i > 0 && $i % 4 === 0) $code .= '-';
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }

    $pdo->prepare("INSERT INTO farms (name, owner_id, farm_code, location, created_at) VALUES (?, ?, ?, ?, NOW())")
        ->execute([$name, $userId, $code, $location]);
    $farmId = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO farm_members (farm_id, user_id, role, status, joined_at) VALUES (?, ?, 'farm_owner', 'active', NOW())")
        ->execute([$farmId, $userId]);

    echo json_encode(['success' => true, 'farm' => ['id' => $farmId, 'name' => $name, 'farm_code' => $code, 'location' => $location]]);
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: sales_leaderboard — Performance tracking
// ═══════════════════════════════════════════════════════════
if ($action === 'sales_leaderboard') {
    $month = $input['month'] ?? date('Y-m');
    $farm = getUserFarm($pdo, $userId);

    // Get sales staff performance
    $farmFilter = '';
    $params = [$month];
    if ($farm) {
        $farmFilter = "AND o.id IN (SELECT id FROM orders WHERE id = o.id)";
    }

    $stmt = $pdo->prepare("
        SELECT
            u.id as user_id,
            u.full_name,
            u.username,
            u.profile_pic,
            fm.role,
            COUNT(DISTINCT o.id) as total_orders,
            COALESCE(SUM(o.total_amount), 0) as total_revenue,
            COUNT(DISTINCT CASE WHEN o.status = 'completed' THEN o.id END) as completed_orders,
            COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END), 0) as completed_revenue
        FROM users u
        JOIN farm_members fm ON u.id = fm.user_id
        LEFT JOIN orders o ON o.user_id = u.id AND DATE_FORMAT(o.created_at, '%Y-%m') = ?
        WHERE fm.status = 'active'
        " . ($farm ? "AND fm.farm_id = ?" : "") . "
        AND fm.role IN ('sales_staff', 'farm_manager', 'farm_owner')
        GROUP BY u.id, u.full_name, u.username, u.profile_pic, fm.role
        ORDER BY total_revenue DESC
    ");
    if ($farm) {
        $stmt->execute([$month, $farm['id']]);
    } else {
        $stmt->execute([$month]);
    }
    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add rank and performance tier
    foreach ($leaderboard as &$entry) {
        $rank = array_search($entry, $leaderboard) + 1;
        $entry['rank'] = $rank;
        $entry['tier'] = $rank === 1 ? 'gold' : ($rank === 2 ? 'silver' : ($rank === 3 ? 'bronze' : ''));
    }

    echo json_encode(['success' => true, 'month' => $month, 'leaderboard' => $leaderboard]);
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: create_notification — Send a notification (owner/admin)
// ═══════════════════════════════════════════════════════════
if ($action === 'send_notification') {
    $targetUserId = (int)($input['user_id'] ?? 0);
    $type = $input['type'] ?? 'info';
    $title = trim($input['title'] ?? '');
    $message = trim($input['message'] ?? '');

    if (!$title) { http_response_code(400); echo json_encode(['error' => 'Title required']); exit; }

    $farm = getUserFarm($pdo, $userId);
    createNotification($pdo, $farm ? $farm['id'] : null, $targetUserId ?: null, $type, $title, $message);

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action', 'available' => 'pending_requests, approve_request, reject_request, team_dashboard, change_role, remove_member, activity_trail, notifications, mark_read, ping, sub_farms, create_farm, sales_leaderboard, send_notification']);
