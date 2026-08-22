<?php
/**
 * Admin — Roles & Permissions
 * Matrix of roles × modules. The admin can switch which modules each role
 * can view and edit, and create/change user accounts (users.php handles
 * account creation; this page controls what roles may open).
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
$page_title = 'Roles & Permissions - Admin';
include __DIR__ . '/includes/admin_header.php';

if (!in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager', 'sales_staff'], true)) {
    header('Location: /Frontend/admin/dashboard.php');
    exit;
}

$pdo = getDB();
$message = '';
$error_message = '';

$roles = ['super_admin', 'farm_manager', 'stock_manager', 'sales_staff', 'customer'];
$roleLabels = [
    'super_admin'  => 'Super Admin',
    'farm_manager' => 'Farm Manager',
    'stock_manager'=> 'Stock Manager',
    'sales_staff'  => 'Sales Staff',
    'customer'     => 'Customer',
];

// Module groups shown in the matrix
$groups = [
    'Dashboard' => ['dashboard' => 'Dashboard'],
    'Poultry Operations' => [
        'flocks' => 'Flocks', 'production' => 'Daily Production', 'vaccinations' => 'Vaccinations',
        'batches' => 'Batches & Houses', 'health' => 'Health & Vet', 'broiler' => 'Broiler Workflow',
        'hatchery' => 'Hatchery (DOC)', 'feeding' => 'Feeding Program', 'losses' => 'Losses & Quality',
    ],
    'Inventory & Stores' => [
        'products' => 'Products Catalog', 'stores' => 'Stores & Stock',
        'feed_production' => 'Feed Production', 'egg_grading' => 'Egg Grading',
    ],
    'Sales & Finance' => [
        'hub_finance' => 'Sales & Finance Hub', 'profit' => 'Costs & Profit', 'cashbook' => 'Cashbook',
        'credit' => 'Customer Credit', 'lpo' => 'LPO & Invoicing', 'purchase_orders' => 'Procurement (PO)',
        'daily_sales' => 'Daily Reconciliation', 'bulk_sales' => 'Bulk Sales',
    ],
    'Reports & Tools' => [
        'analytics' => 'Analytics & Charts', 'bulk_import_export' => 'Bulk Import/Export',
    ],
    'Team & Messages' => [
        'staff' => 'Staff', 'users' => 'Customers / Accounts', 'tasks' => 'Tasks', 'messages' => 'Messages',
    ],
    'Settings' => [
        'calendar' => 'Calendar', 'dropdowns' => 'Dropdowns', 'settings' => 'App Settings',
        'logs' => 'Activity Logs', 'permissions' => 'Roles & Permissions',
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $role = $_POST['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        $error_message = 'Invalid role.';
    } else {
        try {
            $upsert = $pdo->prepare('INSERT INTO role_permissions (role, module_key, can_view, can_edit) VALUES (?,?,?,?)
                                     ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), can_edit=VALUES(can_edit)');
            $moduleKeys = wangariModuleKeys();
            $changed = 0;
            foreach ($moduleKeys as $m) {
                $view = isset($_POST['view'][$role][$m]) ? 1 : 0;
                $edit = isset($_POST['edit'][$role][$m]) ? 1 : 0;
                // super_admin is always fully allowed — keep the row as view/edit for safety
                if ($role === 'super_admin') { $view = 1; $edit = 1; }
                $upsert->execute([$role, $m, $view, $edit]);
                $changed++;
            }
            $message = "Permissions updated for {$roleLabels[$role]} ({$changed} modules).";
            logActivity($pdo, 'update', 'permissions', "Permissions updated for role: {$role}", null, 'role_permissions');
        } catch (Exception $e) {
            $error_message = 'Failed to save permissions: ' . $e->getMessage();
        }
    }
}

$matrix = function_exists('wangariRolePermissions') ? wangariRolePermissions($pdo) : [];
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Roles &amp; Permissions</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Control which modules each role can open. Super Admin always has full access. Account creation &amp; role changes live in <a href="/Frontend/admin/users.php" style="color:var(--admin-primary);font-weight:600;">Users &amp; Accounts</a>.</p>
    </div>
</div>

<?php if ($message): ?>
<div style="padding:13px 18px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;color:#166534;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <i data-lucide="check-circle-2" style="width:18px;height:18px;"></i> <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>
<?php if ($error_message): ?>
<div style="padding:13px 18px;background:#fee2e2;border:1px solid #fecaca;border-radius:8px;color:#b91c1c;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <i data-lucide="alert-circle" style="width:18px;height:18px;"></i> <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<?php foreach ($roles as $role): ?>
<div class="admin-card" style="margin-bottom:22px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <span class="badge-pill <?= $role==='super_admin'?'badge-pill-danger':($role==='farm_manager'?'badge-pill-success':'badge-pill-info') ?>" style="font-size:0.8rem;padding:6px 12px;"><?= $roleLabels[$role] ?></span>
            <?php if ($role === 'super_admin'): ?>
                <small style="color:#94a3b8;">Always has full access to every module.</small>
            <?php else: ?>
                <small style="color:#94a3b8;">Check a module to let this role open it.</small>
            <?php endif; ?>
        </div>
        <button type="button" class="btn btn-outline btn-sm perm-expand" style="border-radius:6px;">
            <i data-lucide="chevron-down" style="width:14px;height:14px;"></i> Show / hide modules
        </button>
    </div>

    <form method="POST" class="perm-form" style="display:none;">
        <input type="hidden" name="role" value="<?= $role ?>">
        <?php foreach ($groups as $groupLabel => $mods): ?>
            <div style="margin-bottom:16px;">
                <div style="font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;margin-bottom:8px;border-bottom:1px solid var(--admin-border);padding-bottom:6px;"><?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') ?></div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:8px 18px;">
                    <?php foreach ($mods as $modKey => $modLabel): ?>
                        <?php
                        $canView = ($matrix[$role][$modKey]['view'] ?? 0) || $role === 'super_admin';
                        $canEdit = ($matrix[$role][$modKey]['edit'] ?? 0) || $role === 'super_admin';
                        $locked = $role === 'super_admin';
                        ?>
                        <label style="display:flex;align-items:center;gap:8px;font-size:0.88rem;color:#1e293b;padding:5px 0;cursor:pointer;">
                            <input type="checkbox" name="view[<?= $role ?>][<?= $modKey ?>]" <?= $canView ? 'checked' : '' ?> <?= $locked ? 'disabled' : '' ?> style="width:16px;height:16px;accent-color:var(--admin-primary);">
                            <span style="flex:1;font-weight:600;"><?= htmlspecialchars($modLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            <small style="color:#94a3b8;display:flex;align-items:center;gap:4px;">
                                edit <input type="checkbox" name="edit[<?= $role ?>][<?= $modKey ?>]" <?= $canEdit ? 'checked' : '' ?> <?= $locked ? 'disabled' : '' ?> style="width:14px;height:14px;accent-color:var(--admin-accent);">
                            </small>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <div style="display:flex;gap:10px;margin-top:6px;border-top:1px solid var(--admin-border);padding-top:16px;">
            <button type="submit" class="btn btn-primary"><i data-lucide="save" style="width:15px;height:15px;"></i> Save <?= $roleLabels[$role] ?> Permissions</button>
        </div>
    </form>
</div>
<?php endforeach; ?>

<script>
document.querySelectorAll('.perm-expand').forEach(btn => {
    btn.addEventListener('click', () => {
        const form = btn.closest('.admin-card').querySelector('.perm-form');
        const open = form.style.display !== 'none';
        form.style.display = open ? 'none' : 'block';
        const ch = btn.querySelector('svg');
        if (ch) ch.style.transform = open ? '' : 'rotate(180deg)';
    });
});
document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
