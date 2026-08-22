<?php
/**
 * Wangari Role Permissions System
 * Maps each role to allowed modules, actions, and restrictions.
 * Used by the API layer and frontend to enforce access control.
 */

// All roles in the system
define('WANGARI_ROLES', [
    'farm_owner'      => 'Farm Owner',
    'farm_manager'    => 'Farm Manager',
    'stock_manager'   => 'Stock Manager',
    'sales_staff'     => 'Sales Staff',
    'field_worker'    => 'Field Worker',
    'veterinarian'    => 'Veterinarian',
    'accountant'      => 'Accountant',
    'auditor'         => 'Auditor',
    'guest'           => 'Guest/Client',
]);

// ───────────────────────────────────────────────────────────
// Permission Matrix
// ───────────────────────────────────────────────────────────
define('WANGARI_PERMISSIONS', [

    // ── Farm Owner: FULL ACCESS ──
    'farm_owner' => [
        'description' => 'Full admin — everything',
        'can_create_farm' => true,
        'can_delete_farm' => true,
        'can_manage_staff' => true,
        'can_manage_settings' => true,
        'modules' => [
            'dashboard'     => ['view', 'edit', 'delete'],
            'users'         => ['view', 'create', 'edit', 'delete', 'change_role'],
            'products'      => ['view', 'create', 'edit', 'delete'],
            'inventory'     => ['view', 'create', 'edit', 'delete', 'transfer'],
            'orders'        => ['view', 'create', 'edit', 'delete', 'approve'],
            'animals'       => ['view', 'create', 'edit', 'delete', 'health'],
            'finances'      => ['view', 'create', 'edit', 'delete', 'reports'],
            'reports'       => ['view', 'export', 'schedule'],
            'lpos'          => ['view', 'create', 'edit', 'delete', 'approve'],
            'settings'      => ['view', 'edit'],
            'team'          => ['view', 'invite', 'remove', 'change_role'],
            'activity_log'  => ['view'],
        ],
    ],

    // ── Farm Manager: Near-full admin ──
    'farm_manager' => [
        'description' => 'Manage workers, orders, products, inventory, reports',
        'can_create_farm' => false,
        'can_delete_farm' => false,
        'can_manage_staff' => true,
        'can_manage_settings' => false,
        'modules' => [
            'dashboard'     => ['view', 'edit'],
            'users'         => ['view', 'create', 'edit'], // can't delete or change roles
            'products'      => ['view', 'create', 'edit', 'delete'],
            'inventory'     => ['view', 'create', 'edit', 'transfer'],
            'orders'        => ['view', 'create', 'edit', 'approve'],
            'animals'       => ['view', 'create', 'edit', 'delete', 'health'],
            'finances'      => ['view', 'create', 'edit'],
            'reports'       => ['view', 'export'],
            'lpos'          => ['view', 'create', 'edit', 'approve'],
            'team'          => ['view', 'invite', 'remove'],
            'activity_log'  => ['view'],
        ],
    ],

    // ── Stock Manager: Inventory focus ──
    'stock_manager' => [
        'description' => 'Add/edit products, track stock, manage suppliers',
        'can_create_farm' => false,
        'can_delete_farm' => false,
        'can_manage_staff' => false,
        'can_manage_settings' => false,
        'modules' => [
            'dashboard'     => ['view'],
            'products'      => ['view', 'create', 'edit'],
            'inventory'     => ['view', 'create', 'edit', 'transfer'],
            'suppliers'     => ['view', 'create', 'edit'],
            'orders'        => ['view'], // read-only
            'animals'       => ['view'],
            'reports'       => ['view'],
            'activity_log'  => ['view'],
        ],
    ],

    // ── Sales Staff: Orders focus ──
    'sales_staff' => [
        'description' => 'Create orders, manage customers, view products (read-only)',
        'can_create_farm' => false,
        'can_delete_farm' => false,
        'can_manage_staff' => false,
        'can_manage_settings' => false,
        'modules' => [
            'dashboard'     => ['view'],
            'orders'        => ['view', 'create', 'edit'],
            'customers'     => ['view', 'create', 'edit'],
            'products'      => ['view'], // read-only
            'inventory'     => ['view'], // read-only
            'reports'       => ['view'],
        ],
    ],

    // ── Field Worker: Task focus ──
    'field_worker' => [
        'description' => 'View assigned tasks, update animal status, log daily activities',
        'can_create_farm' => false,
        'can_delete_farm' => false,
        'can_manage_staff' => false,
        'can_manage_settings' => false,
        'modules' => [
            'dashboard'     => ['view'],
            'tasks'         => ['view', 'update_status'],
            'animals'       => ['view', 'update_status'], // can update status, not create/delete
            'activity_log'  => ['view', 'create'], // can log their own activities
            'attendance'    => ['view', 'clock_in_out'],
        ],
    ],

    // ── Veterinarian: Animal health ──
    'veterinarian' => [
        'description' => 'Manage animals, vaccinations, health records, medication',
        'can_create_farm' => false,
        'can_delete_farm' => false,
        'can_manage_staff' => false,
        'can_manage_settings' => false,
        'modules' => [
            'dashboard'     => ['view'],
            'animals'       => ['view', 'edit', 'health'],
            'vaccinations'  => ['view', 'create', 'edit'],
            'health_records'=> ['view', 'create', 'edit'],
            'medication'    => ['view', 'create'],
            'quarantine'    => ['view', 'create', 'edit'],
            'reports'       => ['view'],
            'activity_log'  => ['view', 'create'],
        ],
    ],

    // ── Accountant: Finance focus ──
    'accountant' => [
        'description' => 'Full financial records, reports, LPOs, invoicing',
        'can_create_farm' => false,
        'can_delete_farm' => false,
        'can_manage_staff' => false,
        'can_manage_settings' => false,
        'modules' => [
            'dashboard'     => ['view'],
            'finances'      => ['view', 'create', 'edit', 'delete', 'reports'],
            'lpos'          => ['view', 'create', 'edit'],
            'invoices'      => ['view', 'create', 'edit'],
            'orders'        => ['view'], // read-only
            'reports'       => ['view', 'export'],
            'tax_records'   => ['view', 'create', 'edit'],
            'activity_log'  => ['view'],
        ],
    ],

    // ── Auditor: Read-only ──
    'auditor' => [
        'description' => 'View all data, generate reports, export',
        'can_create_farm' => false,
        'can_delete_farm' => false,
        'can_manage_staff' => false,
        'can_manage_settings' => false,
        'modules' => [
            'dashboard'     => ['view'],
            'users'         => ['view'],
            'products'      => ['view'],
            'inventory'     => ['view'],
            'orders'        => ['view'],
            'animals'       => ['view'],
            'finances'      => ['view'],
            'reports'       => ['view', 'export'],
            'activity_log'  => ['view'],
        ],
    ],

    // ── Guest/Client: External view ──
    'guest' => [
        'description' => 'View specific order status, invoices shared with them',
        'can_create_farm' => false,
        'can_delete_farm' => false,
        'can_manage_staff' => false,
        'can_manage_settings' => false,
        'modules' => [
            'dashboard'     => ['view'],
            'orders'        => ['view_own'], // only orders assigned to them
            'invoices'      => ['view_own'], // only invoices shared with them
        ],
    ],
]);

// ───────────────────────────────────────────────────────────
// Helper Functions
// ───────────────────────────────────────────────────────────

/**
 * Check if a role has a specific permission on a module.
 */
function hasPermission(string $role, string $module, string $action = 'view'): bool {
    $perms = WANGARI_PERMISSIONS[$role] ?? null;
    if (!$perms) return false;

    $modulePerms = $perms['modules'][$module] ?? [];
    return in_array($action, $modulePerms) || in_array('*', $modulePerms);
}

/**
 * Get all allowed modules for a role.
 */
function getAllowedModules(string $role): array {
    $perms = WANGARI_PERMISSIONS[$role] ?? null;
    if (!$perms) return [];
    return array_keys($perms['modules']);
}

/**
 * Get all allowed actions for a module.
 */
function getModuleActions(string $role, string $module): array {
    $perms = WANGARI_PERMISSIONS[$role] ?? null;
    if (!$perms) return [];
    return $perms['modules'][$module] ?? [];
}

/**
 * Check if the current session user can access a module.
 */
function checkAccess(string $module, string $action = 'view'): bool {
    $role = $_SESSION['role'] ?? '';
    return hasPermission($role, $module, $action);
}

/**
 * Require access or return 403 JSON error.
 */
function requireAccess(string $module, string $action = 'view'): void {
    if (!checkAccess($module, $action)) {
        http_response_code(403);
        echo json_encode([
            'error' => 'Access denied',
            'message' => "Your role ({$_SESSION['role'] ?? 'unknown'}) does not have '$action' permission on '$module'",
        ]);
        exit;
    }
}
