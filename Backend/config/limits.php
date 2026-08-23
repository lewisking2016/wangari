<?php
/**
 * Subscription Limit Enforcement
 * Checks if user has exceeded their plan limits before allowing operations.
 */
declare(strict_types=1);

/**
 * Get the current user's subscription limits from the database.
 */
function wangariGetUserLimits(PDO $pdo, int $userId): array
{
    // Try platform_users first
    $stmt = $pdo->prepare('SELECT max_animals, max_fields, max_users, subscription_status FROM platform_users WHERE id = ? AND role = "user" LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        return [
            'max_animals' => (int)($user['max_animals'] ?? 5),
            'max_fields' => (int)($user['max_fields'] ?? 5),
            'max_users' => (int)($user['max_users'] ?? 5),
            'status' => $user['subscription_status'] ?? 'trial',
        ];
    }
    
    // Default limits for users table (trial/free)
    return [
        'max_animals' => 5,
        'max_fields' => 5,
        'max_users' => 5,
        'status' => 'trial',
    ];
}

/**
 * Check if user can add more animals.
 * Returns ['allowed' => bool, 'message' => string, 'current' => int, 'max' => int]
 */
function wangariCheckAnimalLimit(PDO $pdo, int $userId): array
{
    $limits = wangariGetUserLimits($pdo, $userId);
    $max = $limits['max_animals'];
    
    // Count current animals (from animals table)
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM animals');
    $stmt->execute();
    $current = (int)$stmt->fetchColumn();
    
    if ($max > 0 && $current >= $max) {
        return [
            'allowed' => false,
            'message' => "Animal limit reached ($current/$max). Upgrade your plan to add more animals.",
            'current' => $current,
            'max' => $max,
        ];
    }
    
    return [
        'allowed' => true,
        'message' => '',
        'current' => $current,
        'max' => $max,
    ];
}

/**
 * Check if user can add more fields.
 * Returns ['allowed' => bool, 'message' => string, 'current' => int, 'max' => int]
 */
function wangariCheckFieldLimit(PDO $pdo, int $userId): array
{
    $limits = wangariGetUserLimits($pdo, $userId);
    $max = $limits['max_fields'];
    
    // Count current fields
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM fields');
    $stmt->execute();
    $current = (int)$stmt->fetchColumn();
    
    if ($max > 0 && $current >= $max) {
        return [
            'allowed' => false,
            'message' => "Field limit reached ($current/$max). Upgrade your plan to add more fields.",
            'current' => $current,
            'max' => $max,
        ];
    }
    
    return [
        'allowed' => true,
        'message' => '',
        'current' => $current,
        'max' => $max,
    ];
}

/**
 * Check if user can add more team members.
 * Returns ['allowed' => bool, 'message' => string, 'current' => int, 'max' => int]
 */
function wangariCheckUserLimit(PDO $pdo, int $userId): array
{
    $limits = wangariGetUserLimits($pdo, $userId);
    $max = $limits['max_users'];
    
    // Count current team members
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role IN ("farm_manager", "stock_manager", "sales_staff")');
    $stmt->execute();
    $current = (int)$stmt->fetchColumn();
    
    if ($max > 0 && $current >= $max) {
        return [
            'allowed' => false,
            'message' => "Team member limit reached ($current/$max). Upgrade your plan to add more team members.",
            'current' => $current,
            'max' => $max,
        ];
    }
    
    return [
        'allowed' => true,
        'message' => '',
        'current' => $current,
        'max' => $max,
    ];
}

/**
 * Get usage summary for display.
 */
function wangariGetUsageSummary(PDO $pdo, int $userId): array
{
    $limits = wangariGetUserLimits($pdo, $userId);
    
    $animalCount = (int)$pdo->query('SELECT COUNT(*) FROM animals')->fetchColumn();
    $fieldCount = (int)$pdo->query('SELECT COUNT(*) FROM fields')->fetchColumn();
    $userCount = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role IN ("farm_manager", "stock_manager", "sales_staff")')->fetchColumn();
    
    return [
        'animals' => ['current' => $animalCount, 'max' => $limits['max_animals']],
        'fields' => ['current' => $fieldCount, 'max' => $limits['max_fields']],
        'users' => ['current' => $userCount, 'max' => $limits['max_users']],
        'status' => $limits['status'],
    ];
}
