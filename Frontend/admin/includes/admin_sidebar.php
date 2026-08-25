<?php
/**
 * Admin Sidebar V2 — grouped navigation, fresh look.
 * Groups: Overview | Farm Operations | Inventory | Sales & Customers | People | Tools | System
 */
declare(strict_types=1);

$cp   = basename($_SERVER['SCRIPT_NAME']);
$tab  = $_GET['tab'] ?? '';

function w2IsActive(string $file): bool {
    return basename($_SERVER['SCRIPT_NAME']) === $file;
}

function w2NavItem(string $href, string $icon, string $label, bool $active, string $badge = ''): string {
    $badgeHtml = $badge !== '' ? '<span class="w2-nav-badge">' . htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') . '</span>' : '';
    $cls = $active ? ' active' : '';
    return <<<HTML
    <a href="{$href}" class="w2-nav-item{$cls}">
        <i data-lucide="{$icon}" class="w2-nav-icon"></i>
        <span>{$label}</span>
        {$badgeHtml}
    </a>
HTML;
}

function w2NavGroup(string $icon, string $label, string $hub, array $items, string $currentTab): string {
    $open   = w2IsActive($hub);
    $itemHtml = '';
    foreach ($items as $tKey => $tName) {
        if (is_array($tName) && isset($tName['label'], $tName['href'])) {
            // Explicit link to an arbitrary admin page (e.g. standalone modules)
            $subHref  = $tName['href'];
            $subLabel = $tName['label'];
            $subActive = w2IsActive(basename(parse_url($subHref, PHP_URL_PATH)));
        } else {
            $subHref  = $hub . '?tab=' . $tKey;
            $subLabel = (string)$tName;
            $subActive = w2IsActive($hub) && $currentTab === $tKey;
        }
        $cls = $subActive ? ' active' : '';
        $href = htmlspecialchars($subHref, ENT_QUOTES, 'UTF-8');
        $itemHtml .= '<a href="' . $href . '" class="w2-nav-sub' . $cls . '"><span></span>' . htmlspecialchars($subLabel, ENT_QUOTES, 'UTF-8') . '</a>';
    }
    $openCls = $open ? ' open' : '';
    $display = $open ? 'block' : 'none';
    return <<<HTML
    <div class="w2-nav-group">
        <button type="button" class="w2-nav-parent{$openCls}">
            <i data-lucide="{$icon}" class="w2-nav-icon"></i>
            <span>{$label}</span>
            <i data-lucide="chevron-down" class="w2-nav-chev"></i>
        </button>
        <div class="w2-nav-subs" style="display:{$display};">
            {$itemHtml}
        </div>
    </div>
HTML;
}
?>
<nav class="w2-side" role="navigation" aria-label="Main navigation">
    <!-- Brand -->
    <div class="w2-side-brand">
        <img src="/Frontend/images/wangari-logo.png" alt="Wangari" class="w2-logo">
        <div>
            <p class="w2-brand-name">Wangari</p>
            <small class="w2-brand-sub">Farm OS</small>
        </div>
    </div>

    <!-- Navigation -->
    <div class="w2-nav-scroll">

        <p class="w2-nav-section">Overview</p>
        <?= w2NavItem('/Frontend/admin/dashboard.php', 'layout-dashboard', 'Dashboard', w2IsActive('dashboard.php')) ?>
        <?= w2NavItem('/Frontend/admin/ai_assistant.php', 'sparkles', 'Ask Wangari AI', w2IsActive('ai_assistant.php'), 'AI') ?>
        <?php if (($_SESSION['role'] ?? '') !== 'super_admin'): ?>
        <?= w2NavItem('/Frontend/admin/hub_branches.php', 'git-branch', 'Farm Branches', w2IsActive('hub_branches.php')) ?>
        <?php endif; ?>

        <p class="w2-nav-section">Farm Operations</p>
        <?= w2NavGroup('sprout', 'Farm Operations', 'hub_operations.php', [
            'overview'     => 'Overview',
            'animals'      => 'Animals',
            'groups'       => 'Groups',
            'housing'      => 'Housing',
            'health'       => 'Health',
            'vaccinations' => 'Vaccinations',
            'production'   => 'Production',
            'breeding'     => 'Breeding',
            'feeding'      => 'Feeding',
            'poultry'      => 'Poultry Tools',
        ], $tab ?: 'overview') ?>

        <?= w2NavGroup('wheat', 'Crops & Fields', 'hub_crops.php', [
            'overview'   => 'Overview',
            'fields'     => 'Fields',
            'plantings'  => 'Crop Plantings',
            'activities' => 'Field Activities',
            'harvests'   => 'Harvests',
            'costs'      => 'Crop Costs',
        ], $tab ?: 'overview') ?>

        <p class="w2-nav-section">Mixed Farming</p>
        <?= w2NavItem('/Frontend/admin/mixed_farming.php', 'layout-dashboard', 'Mixed Dashboard', w2IsActive('mixed_farming.php')) ?>

        <p class="w2-nav-section">Inventory</p>
        <?= w2NavGroup('package', 'Inventory & Store', 'hub_inventory.php', [
            'products'  => 'Products Catalog',
            'equipment' => 'Farm Equipment',
            'feedstock' => 'Feed & Stock',
            'alerts'    => 'Inventory Alerts',
        ], $tab ?: 'products') ?>

        <p class="w2-nav-section">Sales & Customers</p>
        <?= w2NavGroup('trending-up', 'Sales & Finance', 'hub_finance.php', [
            'orders'   => 'Customer Orders',
            'sales'    => 'Sales Register',
            'payments' => 'Incoming Payments',
            'expenses' => 'Outgoing Expenses',
            'reports'  => 'Reports & Charts',
            'lpo'      => ['label' => 'LPO / Invoices', 'href' => '/Frontend/admin/lpo.php'],
        ], $tab ?: 'orders') ?>

        <?= w2NavGroup('users', 'CRM & Customers', 'hub_crm.php', [
            'customers' => 'All Customers',
            'segments'  => 'Segments',
            'followups' => 'Follow-ups',
            'contacts'  => 'Contact History',
        ], $tab ?: 'customers') ?>

        <p class="w2-nav-section">People</p>
        <?= w2NavGroup('hard-hat', 'Labour & Workers', 'hub_labour.php', [
            'workers'    => 'Workers',
            'attendance' => 'Attendance',
            'payments'   => 'Wage Payments',
        ], $tab ?: 'workers') ?>

        <?= w2NavGroup('message-square', 'Team & Messages', 'hub_people.php', [
            'staff'    => 'Staff Accounts',
            'users'    => 'Customer List',
            'tasks'    => 'Assigned Tasks',
            'messages' => 'Team Messages',
        ], $tab ?: 'staff') ?>

        <p class="w2-nav-section">Tools</p>
        <?= w2NavGroup('bell', 'Reminders & Weather', 'hub_reminders.php', [
            'reminders' => 'Smart Reminders',
            'weather'   => 'Weather Alerts',
            'week'      => 'This Week',
        ], $tab ?: 'reminders') ?>

        <?= w2NavItem('/Frontend/admin/bulk_import_export.php', 'database', 'Bulk Import/Export', w2IsActive('bulk_import_export.php')) ?>
        <?= w2NavItem('/Frontend/admin/connectors.php', 'plug', 'Connectors', w2IsActive('connectors.php')) ?>

        <?php if (($_SESSION['role'] ?? '') === 'super_admin'): ?>
        <p class="w2-nav-section">Platform</p>
        <?= w2NavItem('/Frontend/admin/team.php', 'users', 'Team Management', w2IsActive('team.php')) ?>
        <?= w2NavItem('/Frontend/admin/super_admin.php', 'shield', 'Control Center', w2IsActive('super_admin.php')) ?>
        <?php endif; ?>

        <p class="w2-nav-section">System</p>
        <?= w2NavGroup('settings', 'Settings', 'hub_settings.php', [
            'calendar'    => 'Calendar View',
            'dropdowns'   => 'Dropdown Config',
            'settings'    => 'App Settings',
            'logs'        => 'System Logs',
            'setup'       => ['label' => 'DB Setup',            'href' => '/Frontend/admin/setup.php'],
            'permissions' => ['label' => 'Roles & Permissions', 'href' => '/Frontend/admin/permissions.php'],
        ], $tab ?: 'calendar') ?>

    </div>
\    <!-- Branch Switcher -->
    <?php
    if (($_SESSION['role'] ?? '') !== 'super_admin') {
        require_once dirname(__DIR__, 2) . '/includes/branch_helpers.php';
        $currentFarmName = getCurrentFarmName();
        $userFarms = getUserFarms((int)($_SESSION['user_id'] ?? 0));
        if (count($userFarms) > 1) {
    ?>
    <div style="padding:0 14px 10px;">
        <form method="POST" action="/Frontend/admin/hub_branches.php">
            <input type="hidden" name="action" value="switch">
            <select name="farm_id" onchange="this.form.submit()" style="width:100%;padding:8px 10px;border-radius:8px;border:1.5px solid #E7EAF0;background:#F8FAFC;font-size:0.8rem;font-weight:600;color:#0F172A;cursor:pointer;outline:none;">
                <?php foreach ($userFarms as $farm): ?>
                <option value="<?php echo $farm['id']; ?>" <?php echo ((int)$farm['id'] === (int)getCurrentFarmId()) ? 'selected' : ''; ?>><?php echo htmlspecialchars($farm['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php
        } elseif (count($userFarms) === 1) {
    ?>
    <div style="padding:0 14px 10px;">
        <div style="display:flex;align-items:center;gap:6px;padding:8px 10px;background:#F0FDF4;border-radius:8px;font-size:0.78rem;color:#166534;font-weight:600;">
            <span style="width:6px;height:6px;border-radius:50%;background:#22C55E;"></span>
            <?php echo htmlspecialchars($currentFarmName); ?>
        </div>
    </div>
    <?php
        }
    }
    ?>
\    <!-- User footer -->
    <div class="w2-side-foot">
        <div class="w2-user">
            <div class="w2-user-avatar"><?php echo strtoupper(substr($_SESSION['first_name'] ?? $_SESSION['username'] ?? 'A', 0, 1)); ?></div>
            <div class="w2-user-meta">
                <p><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?></p>
                <span><?php echo htmlspecialchars(str_replace('_', ' ', $_SESSION['role'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </div>
        <a href="/" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;border-radius:10px;background:#F0FDF4;color:#166534;text-decoration:none;font-weight:600;font-size:0.82rem;transition:all 0.18s;border:1px solid #BBF7D0;margin-bottom:8px;"><i data-lucide="globe" style="width:15px;height:15px;"></i> Visit Website</a>
        <a href="/Frontend/pages/logout.php" class="w2-signout"><i data-lucide="log-out" style="width:15px;height:15px;"></i> Sign Out</a>
    </div>
</nav>
