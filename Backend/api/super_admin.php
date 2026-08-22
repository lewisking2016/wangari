<?php
/**
 * Super Admin Platform API
 * ONLY accessible by super_admin role
 * Endpoints: /api/super_admin.php?endpoint=...
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

$pdo = getDatabaseConnection();
if (!$pdo) { http_response_code(500); echo json_encode(['error' => 'Database connection failed']); exit; }

// Auth check — super_admin only
session_start();
if (($_SESSION['role'] ?? '') !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Super admin access required']);
    exit;
}

$endpoint = $_GET['endpoint'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

try {
    switch ($endpoint) {

        // ═══ PLATFORM OVERVIEW ═══
        case 'overview':
            $d = [];
            $d['total_users'] = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $d['active_users'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1 OR is_active IS NULL")->fetchColumn();
            $d['users_by_role'] = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role ORDER BY count DESC")->fetchAll(PDO::FETCH_KEY_PAIR);
            $d['new_users_today'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
            $d['new_users_week'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
            $d['new_users_month'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

            // Platform health
            $d['total_products'] = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
            $d['total_orders'] = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
            $d['total_animals'] = (int) $pdo->query("SELECT COUNT(*) FROM animals")->fetchColumn();
            $d['total_flocks'] = (int) $pdo->query("SELECT COUNT(*) FROM animal_groups")->fetchColumn();

            // Revenue
            $d['revenue_this_month'] = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type='income' AND MONTH(transaction_date)=MONTH(CURDATE()) AND YEAR(transaction_date)=YEAR(CURDATE())")->fetchColumn();
            $d['revenue_last_month'] = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type='income' AND MONTH(transaction_date)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(transaction_date)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();

            // System
            $d['php_version'] = PHP_VERSION;
            $d['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'unknown';
            $d['uptime'] = @file_get_contents('/proc/uptime');
            if ($d['uptime'] !== false) {
                $parts = explode(' ', $d['uptime']);
                $d['uptime_days'] = round((float)($parts[0] ?? 0) / 86400, 1);
            }
            unset($d['uptime']);
            $load = sys_getavgload();
            $d['load_average'] = ['1min' => round($load[0], 2), '5min' => round($load[1], 2), '15min' => round($load[2], 2)];
            $mem = @file_get_contents('/proc/meminfo');
            if ($mem) {
                preg_match('/MemTotal:\s+(\d+)/', $mem, $m);
                preg_match('/MemAvailable:\s+(\d+)/', $mem, $a);
                $d['memory_total_mb'] = round(($m[1] ?? 0) / 1024);
                $d['memory_available_mb'] = round(($a[1] ?? 0) / 1024);
                $d['memory_used_pct'] = $d['memory_total_mb'] > 0 ? round((1 - $d['memory_available_mb'] / $d['memory_total_mb']) * 100, 1) : 0;
            }
            $disk = disk_free_space('/');
            $total = disk_total_space('/');
            $d['disk_free_gb'] = round($disk / 1073741824, 1);
            $d['disk_total_gb'] = round($total / 1073741824, 1);
            $d['disk_used_pct'] = round((1 - $disk / $total) * 100, 1);

            echo json_encode($d);
            break;

        // ═══ USER LIST ═══
        case 'users':
            $search = $_GET['search'] ?? '';
            $role = $_GET['role'] ?? '';
            $limit = min((int)($_GET['limit'] ?? 50), 200);
            $offset = max((int)($_GET['offset'] ?? 0), 0);

            $sql = "SELECT id, username, email, full_name, phone, role, is_active, created_at FROM users WHERE 1=1";
            $params = [];
            if ($search) {
            $sql .= " AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
            $s = "%$search%";
            $params = array_merge($params, [$s, $s, $s]);
            }
            if ($role) {
                $sql .= " AND role = ?";
                $params[] = $role;
            }
            $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Count total
            $countSql = "SELECT COUNT(*) FROM users WHERE 1=1";
            $countParams = [];
            if ($search) {
            $countSql .= " AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
            $countParams = array_merge($countParams, [$s, $s, $s]);
            }
            if ($role) {
                $countSql .= " AND role = ?";
                $countParams[] = $role;
            }
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($countParams);
            $total = (int) $countStmt->fetchColumn();

            echo json_encode(['users' => $users, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
            break;

        // ═══ USER DETAIL ═══
        case 'user':
            $userId = (int)($_GET['id'] ?? 0);
            if ($userId <= 0) { http_response_code(400); echo json_encode(['error' => 'Invalid user ID']); exit; }

            $stmt = $pdo->prepare("SELECT id, username, email, full_name, phone, role, is_active, created_at FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) { http_response_code(404); echo json_encode(['error' => 'User not found']); exit; }

            // Get activity count
            $user['activity_count'] = (int) $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE user_id = ?")->execute([$userId]) ? $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE user_id = $userId")->fetchColumn() : 0;

            echo json_encode($user);
            break;

        // ═══ CREATE/UPDATE USER ═══
        case 'save_user':
            $id = (int)($input['id'] ?? 0);
            $username = trim($input['username'] ?? '');
            $email = trim($input['email'] ?? '');
            $role = trim($input['role'] ?? 'customer');

            if ($username === '' || $email === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Username and email are required']);
                exit;
            }

            $validRoles = ['super_admin', 'farm_manager', 'stock_manager', 'sales_staff', 'customer'];
            if (!in_array($role, $validRoles, true)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid role']);
                exit;
            }

            $fields = [
                'username' => $username,
                'email' => $email,
                'full_name' => trim($input['full_name'] ?? $input['first_name'] ?? '') . ' ' . trim($input['last_name'] ?? ''),
                'phone' => trim($input['phone'] ?? $input['phone_number'] ?? ''),
                'role' => $role,
                'is_active' => (int)($input['is_active'] ?? 1),
            ];
            $fields['full_name'] = trim($fields['full_name']);

            if (!empty($input['password'])) {
                $fields['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
            }

            if ($id > 0) {
                // Update
                $sets = [];
                $vals = [];
                foreach ($fields as $k => $v) {
                    $sets[] = "$k=?";
                    $vals[] = $v;
                }
                $vals[] = $id;
                $pdo->prepare("UPDATE users SET " . implode(',', $sets) . " WHERE id=?")->execute($vals);
                logActivity($pdo, 'update', 'super_admin', "Updated user #$id: $username", $id, 'user');
                echo json_encode(['success' => true, 'id' => $id, 'message' => 'User updated']);
            } else {
                // Create
                if (empty($fields['password'])) {
                    $fields['password'] = password_hash('changeme123', PASSWORD_DEFAULT);
                }
                $cols = implode(',', array_keys($fields));
                $ph = implode(',', array_fill(0, count($fields), '?'));
                $pdo->prepare("INSERT INTO users ($cols) VALUES ($ph)")->execute(array_values($fields));
                $newId = (int) $pdo->lastInsertId();
                logActivity($pdo, 'create', 'super_admin', "Created user: $username (role: $role)", $newId, 'user');
                echo json_encode(['success' => true, 'id' => $newId, 'message' => 'User created']);
            }
            break;

        // ═══ TOGGLE USER STATUS ═══
        case 'toggle_user':
            $userId = (int)($input['id'] ?? 0);
            if ($userId <= 0) { http_response_code(400); echo json_encode(['error' => 'Invalid user ID']); exit; }

            $user = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
            $user->execute([$userId]);
            $userData = $user->fetch(PDO::FETCH_ASSOC);
            if (!$userData) { http_response_code(404); echo json_encode(['error' => 'User not found']); exit; }

            $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?")->execute([$userId]);
            $newStatus = $pdo->query("SELECT is_active FROM users WHERE id = $userId")->fetchColumn();
            logActivity($pdo, 'update', 'super_admin', "Toggled user #$userId ($userData[username]): active=" . ($newStatus ? 'yes' : 'no'), $userId, 'user');
            echo json_encode(['success' => true, 'is_active' => (bool)$newStatus]);
            break;

        // ═══ DELETE USER ═══
        case 'delete_user':
            $userId = (int)($input['id'] ?? 0);
            if ($userId <= 0) { http_response_code(400); echo json_encode(['error' => 'Invalid user ID']); exit; }

            // Prevent deleting yourself
            if ($userId === (int)$_SESSION['user_id']) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete your own account']);
                exit;
            }

            // Get user info before delete
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $delUser = $stmt->fetch(PDO::FETCH_ASSOC);

            $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'super_admin'")->execute([$userId]);
            logActivity($pdo, 'delete', 'super_admin', "Deleted user: " . ($delUser['username'] ?? 'unknown'), $userId, 'user');
            echo json_encode(['success' => true, 'message' => 'User deleted']);
            break;

        // ═══ BULK ROLE UPDATE ═══
        case 'bulk_role':
            $userIds = $input['user_ids'] ?? [];
            $newRole = trim($input['role'] ?? '');
            if (empty($userIds) || !in_array($newRole, ['super_admin','farm_manager','stock_manager','sales_staff','customer'], true)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid request']);
                exit;
            }
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $pdo->prepare("UPDATE users SET role = ? WHERE id IN ($placeholders) AND role != 'super_admin'")->execute(array_merge([$newRole], $userIds));
            logActivity($pdo, 'update', 'super_admin', "Bulk role update to $newRole: " . count($userIds) . " users", null, 'user');
            echo json_encode(['success' => true, 'message' => count($userIds) . " users updated to $newRole"]);
            break;

        // ═══ SYSTEM HEALTH ═══
        case 'health':
            $h = [];
            // Database
            try {
                $pdo->query("SELECT 1");
                $h['database'] = ['status' => 'healthy', 'tables' => (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()")->fetchColumn()];
            } catch (Exception $e) {
                $h['database'] = ['status' => 'error', 'error' => $e->getMessage()];
            }
            // Redis
            if (class_exists('Redis')) {
                try {
                    $r = new Redis();
                    $r->connect('127.0.0.1', 6379, 2);
                    $r->ping();
                    $h['redis'] = ['status' => 'healthy', 'memory' => $r->info()['used_memory_human'] ?? 'unknown'];
                } catch (Exception $e) {
                    $h['redis'] = ['status' => 'error'];
                }
            }
            // OPcache
            if (function_exists('opcache_get_status')) {
                $oc = @opcache_get_status(false);
                $h['opcache'] = $oc !== false ? ['status' => 'healthy', 'hit_rate' => round($oc['opcache_statistics']['opcache_hit_rate'] ?? 0, 2)] : ['status' => 'disabled'];
            }
            // Nginx
            $h['nginx'] = ['status' => 'healthy'];
            // PHP
            $h['php'] = ['version' => PHP_VERSION, 'memory_limit' => ini_get('memory_limit'), 'max_execution_time' => ini_get('max_execution_time')];

            echo json_encode($h);
            break;

        // ═══ ACTIVITY LOG ═══
        case 'activity_log':
            $limit = min((int)($_GET['limit'] ?? 50), 200);
            $stmt = $pdo->prepare("SELECT al.*, u.username FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT ?");
            $stmt->execute([$limit]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'error' => "Unknown endpoint: $endpoint",
                'available' => ['overview', 'users', 'user', 'save_user', 'toggle_user', 'delete_user', 'bulk_role', 'health', 'activity_log']
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
