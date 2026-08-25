<?php
/**
 * Branch Helpers — Multi-branch farm management support.
 * 
 * Provides functions to:
 * - Get the current active branch for a user
 * - Switch branches
 * - Get all branches a user can access
 * - Check if user is a branch manager
 * - Filter queries by current branch
 */

declare(strict_types=1);

/**
 * Get the current active farm/branch ID for the logged-in user.
 * Falls back to the user's first farm if current_farm_id is not set.
 */
function getCurrentFarmId(): ?int {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) return null;
    
    // Check session cache first
    if (!empty($_SESSION['current_farm_id'])) {
        return (int)$_SESSION['current_farm_id'];
    }
    
    // Get from database
    try {
        $pdo = wangariGetPdo();
        $stmt = $pdo->prepare("SELECT current_farm_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && $row['current_farm_id']) {
            $_SESSION['current_farm_id'] = (int)$row['current_farm_id'];
            return (int)$row['current_farm_id'];
        }
        
        // Fallback: get user's first farm
        $stmt = $pdo->prepare("SELECT id FROM farms WHERE owner_id = ? ORDER BY id LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $_SESSION['current_farm_id'] = (int)$row['id'];
            // Update user's current_farm_id
            $stmt = $pdo->prepare("UPDATE users SET current_farm_id = ? WHERE id = ?");
            $stmt->execute([$row['id'], $userId]);
            return (int)$row['id'];
        }
        
        return null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Switch the user's active branch.
 */
function switchFarm(int $farmId): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) return false;
    
    // Verify user has access to this farm
    $farms = getUserFarms($userId);
    $farmIds = array_column($farms, 'id');
    
    if (!in_array($farmId, $farmIds)) {
        return false;
    }
    
    try {
        $pdo = wangariGetPdo();
        $stmt = $pdo->prepare("UPDATE users SET current_farm_id = ? WHERE id = ?");
        $stmt->execute([$farmId, $userId]);
        $_SESSION['current_farm_id'] = $farmId;
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get all farms/branches a user can access.
 * - Owners see all their farms
 * - Branch managers see only their assigned farm
 * - Workers see only their connected farm
 */
function getUserFarms(int $userId): array {
    try {
        $pdo = wangariGetPdo();
        
        // Get user role
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) return [];
        
        $role = $user['role'];
        
        if ($role === 'super_admin') {
            // Super admin sees ALL farms
            $stmt = $pdo->query("SELECT f.*, u.full_name as manager_name FROM farms f LEFT JOIN users u ON f.manager_id = u.id WHERE f.is_active = 1 ORDER BY f.name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        
        if (in_array($role, ['farm_manager', 'branch_manager'])) {
            // Farm manager/branch manager sees farms they own or manage
            $stmt = $pdo->prepare("
                SELECT f.*, u.full_name as manager_name 
                FROM farms f 
                LEFT JOIN users u ON f.manager_id = u.id 
                WHERE (f.owner_id = ? OR f.manager_id = ?) AND f.is_active = 1 
                ORDER BY f.name
            ");
            $stmt->execute([$userId, $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        
        // Workers and other roles: see farms they're connected to
        $stmt = $pdo->prepare("
            SELECT f.*, u.full_name as manager_name 
            FROM farms f 
            INNER JOIN worker_farm_links wfl ON wfl.farm_user_id = f.id 
            LEFT JOIN users u ON f.manager_id = u.id 
            WHERE wfl.worker_user_id = ? AND wfl.is_active = 1 AND f.is_active = 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Check if the current user is a branch manager (sees only one branch).
 */
function isBranchManager(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return ($_SESSION['role'] ?? '') === 'branch_manager';
}

/**
 * Check if the current user is an owner/farm manager (sees all branches).
 */
function isFarmOwner(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager']);
}

/**
 * Get the current farm name for display.
 */
function getCurrentFarmName(): string {
    $farmId = getCurrentFarmId();
    if (!$farmId) return 'No Farm';
    
    try {
        $pdo = wangariGetPdo();
        $stmt = $pdo->prepare("SELECT name FROM farms WHERE id = ?");
        $stmt->execute([$farmId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['name'] : 'Unknown Farm';
    } catch (Exception $e) {
        return 'Unknown Farm';
    }
}

/**
 * Get the current farm details.
 */
function getCurrentFarm(): ?array {
    $farmId = getCurrentFarmId();
    if (!$farmId) return null;
    
    try {
        $pdo = wangariGetPdo();
        $stmt = $pdo->prepare("SELECT * FROM farms WHERE id = ?");
        $stmt->execute([$farmId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Add farm_id WHERE clause to a query based on user role.
 * - Farm owners: filter by current farm_id
 * - Branch managers: filter by their assigned farm_id
 * - Workers: filter by their connected farm_id
 * 
 * Returns the modified SQL string.
 */
function addFarmFilter(string $sql, int $userId, string $tableAlias = ''): string {
    $farmId = getCurrentFarmId();
    if (!$farmId) return $sql;
    
    $prefix = $tableAlias ? "$tableAlias." : "";
    
    // If query already has WHERE clause
    if (stripos($sql, 'WHERE') !== false) {
        return $sql . " AND {$prefix}farm_id = " . (int)$farmId;
    }
    
    // Add WHERE clause
    return $sql . " WHERE {$prefix}farm_id = " . (int)$farmId;
}

/**
 * Get farm statistics for a specific branch.
 */
function getBranchFarmStats(int $farmId): array {
    try {
        $pdo = wangariGetPdo();
        $stats = [];
        
        // Animals count
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM animals WHERE farm_id = ? AND status = 'Active'");
        $stmt->execute([$farmId]);
        $stats['animals'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        
        // Batches count
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM batches WHERE farm_id = ? AND status = 'active'");
        $stmt->execute([$farmId]);
        $stats['batches'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        
        // Workers count
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM labour_workers WHERE farm_id = ? AND is_active = 1");
        $stmt->execute([$farmId]);
        $stats['workers'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        
        // Monthly sales
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM financial_records WHERE farm_id = ? AND type = 'income' AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())");
        $stmt->execute([$farmId]);
        $stats['monthly_sales'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Customers count
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM walk_in_customers WHERE farm_id = ?");
        $stmt->execute([$farmId]);
        $stats['customers'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        
        // Fields count
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM fields WHERE farm_id = ?");
        $stmt->execute([$farmId]);
        $stats['fields'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        
        return $stats;
    } catch (Exception $e) {
        return ['animals' => 0, 'batches' => 0, 'workers' => 0, 'monthly_sales' => 0, 'customers' => 0, 'fields' => 0];
    }
}
