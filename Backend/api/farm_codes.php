<?php
/**
 * Farm Codes API — manages farm invite codes, membership, and team.
 * Endpoints: generate, validate, join, members, approve/reject, revoke
 */
declare(strict_types=1);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) { http_response_code(500); echo json_encode(['error' => 'Database connection failed']); exit; }

// Start session
require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// ───────────────────────────────────────────────────────
// Helper: generate a unique farm code
// ───────────────────────────────────────────────────────
function generateCode(string $prefix = 'WGRI', int $length = 12): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no I/O/0/1 to avoid confusion
    $code = $prefix . '-';
    for ($i = 0; $i < $length; $i++) {
        if ($i > 0 && $i % 4 === 0) $code .= '-';
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

// ───────────────────────────────────────────────────────
// Helper: get or create farm for an owner
// ───────────────────────────────────────────────────────
function getOrCreateFarm($pdo, int $ownerId, string $farmName = ''): array {
    $stmt = $pdo->prepare("SELECT * FROM farms WHERE owner_id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$ownerId]);
    $farm = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($farm) return $farm;

    // Create new farm
    $code = generateCode('WGRI');
    $name = $farmName ?: ('Farm of User #' . $ownerId);
    $stmt = $pdo->prepare("INSERT INTO farms (name, owner_id, farm_code, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$name, $ownerId, $code]);
    $farmId = $pdo->lastInsertId();

    // Auto-add owner as member
    $pdo->prepare("INSERT INTO farm_members (farm_id, user_id, role, status, joined_at) VALUES (?, ?, 'farm_owner', 'active', NOW())")->execute([$farmId, $ownerId]);

    $stmt = $pdo->prepare("SELECT * FROM farms WHERE id = ?");
    $stmt->execute([$farmId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ───────────────────────────────────────────────────────
// ACTION: overview — farm stats for the owner
// ───────────────────────────────────────────────────────
if ($action === 'overview') {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { http_response_code(401); echo json_encode(['error' => 'Not authenticated']); exit; }

    $farm = getOrCreateFarm($pdo, $userId);

    // Get member counts by role
    $stmt = $pdo->prepare("SELECT role, COUNT(*) as count FROM farm_members WHERE farm_id = ? AND status = 'active' GROUP BY role");
    $stmt->execute([$farm['id']]);
    $membersByRole = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Pending join requests
    $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM farm_join_requests WHERE farm_id = ? AND status = 'pending'");
    $stmt->execute([$farm['id']]);
    $pendingCount = (int)$stmt->fetchColumn();

    // Active invite codes
    $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM farm_codes WHERE farm_id = ? AND is_active = 1");
    $stmt->execute([$farm['id']]);
    $activeCodes = (int)$stmt->fetchColumn();

    $totalMembers = array_sum($membersByRole);

    echo json_encode([
        'success' => true,
        'farm' => [
            'id' => $farm['id'],
            'name' => $farm['name'],
            'farm_code' => $farm['farm_code'],
            'created_at' => $farm['created_at'],
        ],
        'stats' => [
            'total_members' => $totalMembers,
            'members_by_role' => $membersByRole,
            'pending_requests' => $pendingCount,
            'active_codes' => $activeCodes,
        ],
    ]);
    exit;
}

// ───────────────────────────────────────────────────────
// ACTION: generate_code — owner creates an invite code
// ───────────────────────────────────────────────────────
if ($action === 'generate_code') {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { http_response_code(401); echo json_encode(['error' => 'Not authenticated']); exit; }

    $farm = getOrCreateFarm($pdo, $userId);
    $role = $input['role'] ?? 'field_worker';
    $maxUses = max(1, min(100, (int)($input['max_uses'] ?? 1)));
    $expiresDays = max(0, min(90, (int)($input['expires_days'] ?? 7)));

    $validRoles = ['farm_manager', 'stock_manager', 'sales_staff', 'field_worker', 'veterinarian', 'accountant', 'auditor', 'guest'];
    if (!in_array($role, $validRoles)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid role. Valid roles: ' . implode(', ', $validRoles)]);
        exit;
    }

    $code = generateCode('WGRI');
    $expiresAt = $expiresDays > 0 ? date('Y-m-d H:i:s', time() + $expiresDays * 86400) : null;

    $stmt = $pdo->prepare("INSERT INTO farm_codes (farm_id, code, role, max_uses, expires_at, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$farm['id'], $code, $role, $maxUses, $expiresAt]);

    // Log activity
    $pdo->prepare("INSERT INTO activity_log (farm_id, user_id, action, entity_type, details, created_at) VALUES (?, ?, 'code_generated', 'farm_code', ?, NOW())")
        ->execute([$farm['id'], $userId, "Generated code for role: $role (max uses: $maxUses)"]);

    echo json_encode([
        'success' => true,
        'code' => [
            'code' => $code,
            'role' => $role,
            'max_uses' => $maxUses,
            'expires_at' => $expiresAt,
        ],
    ]);
    exit;
}

// ───────────────────────────────────────────────────────
// ACTION: validate_code — worker checks a code before joining
// ───────────────────────────────────────────────────────
if ($action === 'validate_code') {
    $code = trim($input['code'] ?? '');
    if (empty($code)) { http_response_code(400); echo json_encode(['error' => 'Code required']); exit; }

    $stmt = $pdo->prepare("
        SELECT fc.*, f.name as farm_name, f.farm_code as farm_identifier
        FROM farm_codes fc
        JOIN farms f ON fc.farm_id = f.id
        WHERE fc.code = ? AND fc.is_active = 1
    ");
    $stmt->execute([$code]);
    $farmCode = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$farmCode) {
        http_response_code(404);
        echo json_encode(['valid' => false, 'error' => 'Invalid or inactive code']);
        exit;
    }

    // Check expiry
    if ($farmCode['expires_at'] && strtotime($farmCode['expires_at']) < time()) {
        http_response_code(410);
        echo json_encode(['valid' => false, 'error' => 'This code has expired']);
        exit;
    }

    // Check max uses
    if ($farmCode['current_uses'] >= $farmCode['max_uses']) {
        http_response_code(410);
        echo json_encode(['valid' => false, 'error' => 'This code has reached its maximum uses']);
        exit;
    }

    echo json_encode([
        'valid' => true,
        'farm_name' => $farmCode['farm_name'],
        'role' => $farmCode['role'],
        'expires_at' => $farmCode['expires_at'],
    ]);
    exit;
}

// ───────────────────────────────────────────────────────
// ACTION: join — worker joins a farm using a code
// ───────────────────────────────────────────────────────
if ($action === 'join') {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { http_response_code(401); echo json_encode(['error' => 'Not authenticated']); exit; }

    $code = trim($input['code'] ?? '');
    if (empty($code)) { http_response_code(400); echo json_encode(['error' => 'Code required']); exit; }

    $stmt = $pdo->prepare("SELECT * FROM farm_codes WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $farmCode = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$farmCode) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid or inactive code']);
        exit;
    }

    // Check expiry
    if ($farmCode['expires_at'] && strtotime($farmCode['expires_at']) < time()) {
        http_response_code(410);
        echo json_encode(['error' => 'This code has expired']);
        exit;
    }

    // Check max uses
    if ($farmCode['current_uses'] >= $farmCode['max_uses']) {
        http_response_code(410);
        echo json_encode(['error' => 'This code has reached its maximum uses']);
        exit;
    }

    // Check if already a member
    $stmt = $pdo->prepare("SELECT id FROM farm_members WHERE farm_id = ? AND user_id = ?");
    $stmt->execute([$farmCode['farm_id'], $userId]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'You are already a member of this farm']);
        exit;
    }

    // Check if already has a pending request
    $stmt = $pdo->prepare("SELECT id FROM farm_join_requests WHERE farm_id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$farmCode['farm_id'], $userId]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'You already have a pending join request for this farm']);
        exit;
    }

    // Get the farm
    $stmt = $pdo->prepare("SELECT * FROM farms WHERE id = ?");
    $stmt->execute([$farmCode['farm_id']]);
    $farm = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->beginTransaction();
    try {
        // Increment code usage
        $pdo->prepare("UPDATE farm_codes SET current_uses = current_uses + 1 WHERE id = ?")->execute([$farmCode['id']]);

        // Create join request
        $pdo->prepare("INSERT INTO farm_join_requests (farm_id, user_id, code_used, requested_role, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())")
            ->execute([$farmCode['farm_id'], $userId, $code, $farmCode['role']]);

        // Auto-add as member (for now — can be changed to require approval)
        $pdo->prepare("INSERT INTO farm_members (farm_id, user_id, role, status, joined_at) VALUES (?, ?, ?, 'active', NOW())")
            ->execute([$farmCode['farm_id'], $userId, $farmCode['role']]);

        // Update user's role if they were a customer
        $pdo->prepare("UPDATE users SET role = ? WHERE id = ? AND role = 'customer'")
            ->execute([$farmCode['role'], $userId]);

        // Log activity
        $pdo->prepare("INSERT INTO activity_log (farm_id, user_id, action, entity_type, details, created_at) VALUES (?, ?, 'member_joined', 'user', ?, NOW())")
            ->execute([$farmCode['farm_id'], $userId, "User #$userId joined as {$farmCode['role']}"]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Welcome to {$farm['name']}!",
            'farm' => ['id' => $farm['id'], 'name' => $farm['name']],
            'role' => $farmCode['role'],
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to join farm: ' . $e->getMessage()]);
    }
    exit;
}

// ───────────────────────────────────────────────────────
// ACTION: members — list all members of the farm
// ───────────────────────────────────────────────────────
if ($action === 'members') {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { http_response_code(401); echo json_encode(['error' => 'Not authenticated']); exit; }

    $farm = getOrCreateFarm($pdo, $userId);

    $stmt = $pdo->prepare("
        SELECT fm.id, fm.role, fm.status, fm.joined_at,
               u.id as user_id, u.username, u.full_name, u.email, u.phone, u.profile_pic
        FROM farm_members fm
        JOIN users u ON fm.user_id = u.id
        WHERE fm.farm_id = ? AND fm.status = 'active'
        ORDER BY fm.joined_at ASC
    ");
    $stmt->execute([$farm['id']]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'members' => $members]);
    exit;
}

// ───────────────────────────────────────────────────────
// ACTION: pending_requests — list pending join requests
// ───────────────────────────────────────────────────────
if ($action === 'pending_requests') {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { http_response_code(401); echo json_encode(['error' => 'Not authenticated']); exit; }

    $farm = getOrCreateFarm($pdo, $userId);

    $stmt = $pdo->prepare("
        SELECT fj.id, fj.code_used, fj.requested_role, fj.status, fj.created_at,
               u.id as user_id, u.username, u.full_name, u.email, u.phone, u.profile_pic
        FROM farm_join_requests fj
        JOIN users u ON fj.user_id = u.id
        WHERE fj.farm_id = ? AND fj.status = 'pending'
        ORDER BY fj.created_at DESC
    ");
    $stmt->execute([$farm['id']]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'requests' => $requests]);
    exit;
}

// ───────────────────────────────────────────────────────
// ACTION: approve / reject — owner reviews join request
// ───────────────────────────────────────────────────────
if ($action === 'approve' || $action === 'reject') {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { http_response_code(401); echo json_encode(['error' => 'Not authenticated']); exit; }

    $requestId = (int)($input['request_id'] ?? 0);
    if (!$requestId) { http_response_code(400); echo json_encode(['error' => 'Request ID required']); exit; }

    $farm = getOrCreateFarm($pdo, $userId);

    $stmt = $pdo->prepare("SELECT * FROM farm_join_requests WHERE id = ? AND farm_id = ?");
    $stmt->execute([$requestId, $farm['id']]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        http_response_code(404);
        echo json_encode(['error' => 'Request not found']);
        exit;
    }

    $newStatus = $action === 'approve' ? 'approved' : 'rejected';

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE farm_join_requests SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
            ->execute([$newStatus, $userId, $requestId]);

        if ($action === 'approve') {
            // Ensure member exists
            $pdo->prepare("INSERT IGNORE INTO farm_members (farm_id, user_id, role, status, approved_by, joined_at) VALUES (?, ?, ?, 'active', ?, NOW())")
                ->execute([$farm['id'], $request['user_id'], $request['requested_role'], $userId]);
        } else {
            // Remove member if exists
            $pdo->prepare("DELETE FROM farm_members WHERE farm_id = ? AND user_id = ?")
                ->execute([$farm['id'], $request['user_id']]);
        }

        $pdo->prepare("INSERT INTO activity_log (farm_id, user_id, action, entity_type, entity_id, details, created_at) VALUES (?, ?, ?, 'farm_join_request', ?, ?, NOW())")
            ->execute([$farm['id'], $userId, $action === 'approve' ? 'request_approved' : 'request_rejected', $requestId, "User #{$request['user_id']} {$newStatus}"]);

        $pdo->commit();

        echo json_encode(['success' => true, 'status' => $newStatus]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed: ' . $e->getMessage()]);
    }
    exit;
}

// ───────────────────────────────────────────────────────
// ACTION: revoke_code — owner deactivates an invite code
// ───────────────────────────────────────────────────────
if ($action === 'revoke_code') {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { http_response_code(401); echo json_encode(['error' => 'Not authenticated']); exit; }

    $codeId = (int)($input['code_id'] ?? 0);
    if (!$codeId) { http_response_code(400); echo json_encode(['error' => 'Code ID required']); exit; }

    $farm = getOrCreateFarm($pdo, $userId);
    $pdo->prepare("UPDATE farm_codes SET is_active = 0 WHERE id = ? AND farm_id = ?")->execute([$codeId, $farm['id']]);

    echo json_encode(['success' => true]);
    exit;
}

// ───────────────────────────────────────────────────────
// ACTION: remove_member — owner removes a member
// ───────────────────────────────────────────────────────
if ($action === 'remove_member') {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { http_response_code(401); echo json_encode(['error' => 'Not authenticated']); exit; }

    $memberId = (int)($input['member_id'] ?? 0);
    if (!$memberId) { http_response_code(400); echo json_encode(['error' => 'Member ID required']); exit; }

    $farm = getOrCreateFarm($pdo, $userId);

    // Don't allow removing the owner
    $stmt = $pdo->prepare("SELECT user_id FROM farm_members WHERE id = ? AND farm_id = ?");
    $stmt->execute([$memberId, $farm['id']]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$member || $member['user_id'] == $userId) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot remove the farm owner']);
        exit;
    }

    $pdo->prepare("UPDATE farm_members SET status = 'removed' WHERE id = ? AND farm_id = ?")->execute([$memberId, $farm['id']]);

    echo json_encode(['success' => true]);
    exit;
}

// ───────────────────────────────────────────────────────
// ACTION: codes — list all active codes for the farm
// ───────────────────────────────────────────────────────
if ($action === 'codes') {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { http_response_code(401); echo json_encode(['error' => 'Not authenticated']); exit; }

    $farm = getOrCreateFarm($pdo, $userId);

    $stmt = $pdo->prepare("SELECT * FROM farm_codes WHERE farm_id = ? ORDER BY created_at DESC");
    $stmt->execute([$farm['id']]);
    $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'codes' => $codes]);
    exit;
}

// ───────────────────────────────────────────────────────
// ACTION: activity — recent activity log
// ───────────────────────────────────────────────────────
if ($action === 'activity') {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) { http_response_code(401); echo json_encode(['error' => 'Not authenticated']); exit; }

    $farm = getOrCreateFarm($pdo, $userId);

    $stmt = $pdo->prepare("
        SELECT al.*, u.username, u.full_name
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.farm_id = ?
        ORDER BY al.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$farm['id']]);
    $log = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'activity' => $log]);
    exit;
}

// Default: unknown action
http_response_code(400);
echo json_encode(['error' => 'Unknown action', 'available' => 'overview, generate_code, validate_code, join, members, pending_requests, approve, reject, revoke_code, remove_member, codes, activity']);
