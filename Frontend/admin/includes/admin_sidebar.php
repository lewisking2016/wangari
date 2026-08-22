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
<nav class="w2-side">
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

    <!-- User footer -->
    <div class="w2-side-foot">
        <div class="w2-user">
            <div class="w2-user-avatar"><?php echo strtoupper(substr($_SESSION['first_name'] ?? $_SESSION['username'] ?? 'A', 0, 1)); ?></div>
            <div class="w2-user-meta">
                <p><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?></p>
                <span><?php echo htmlspecialchars(str_replace('_', ' ', $_SESSION['role'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </div>
        <a href="/Frontend/pages/logout.php" class="w2-signout"><i data-lucide="log-out" style="width:15px;height:15px;"></i> Sign Out</a>
    </div>
</nav>
