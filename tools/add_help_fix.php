<?php
/**
 * Add helpTip() to remaining section headers that weren't matched.
 */
$root = dirname(__DIR__);
$changes = 0;

function addTip(string $path, string $old, string $new) {
    global $changes;
    if (!file_exists($path)) return;
    $content = file_get_contents($path);
    if (strpos($content, $old) === false) { echo "MISS: " . basename($path) . ": $old\n"; return; }
    // Don't double-add
    $context = substr($content, max(0, strpos($content, $old) - 10), strlen($old) + 200);
    if (strpos($context, 'helpTip') !== false) { echo "HAVE: " . basename($path) . ": $old\n"; return; }
    $content = str_replace($old, $new, $content, $count);
    if ($count > 0) {
        file_put_contents($path, $content);
        $changes += $count;
        echo "OK: " . basename($path) . ": $old\n";
    }
}

// ══════ hub_inventory.php ══════
$f = $root . '/Frontend/admin/hub_inventory.php';
addTip($f, '>Farm Equipment & Tools Registry</h3>',
    '>Farm Equipment & Tools Registry <?= helpTip(\'Track every tool and machine on your farm: tractors, ploughs, generators, feeders. Know what you own and where it is.\') ?></h3>');
addTip($f, '>Equipment Maintenance & Usage</h3>',
    '>Equipment Maintenance & Usage <?= helpTip(\'Record when you service or repair equipment and track how often each tool is used.\') ?></h3>');

// ══════ hub_labour.php ══════
$f = $root . '/Frontend/admin/hub_labour.php';
addTip($f, '>Workers</h3>',
    '>Workers <?= helpTip(\'All people who work on your farm: permanent staff, casual workers, managers. Track names, roles, phone numbers, and wages.\') ?></h3>');
addTip($f, '>Attendance Log</h3>',
    '>Attendance Log <?= helpTip(\'Who came to work today? Record clock-in and clock-out times to track attendance and calculate pay.\') ?></h3>');
addTip($f, '>Wage Payments</h3>',
    '>Wage Payments <?= helpTip(\'Record every payment made to workers. Shows amount, date, and period covered so you never overpay.\') ?></h3>');

// ══════ hub_crm.php ══════
$f = $root . '/Frontend/admin/hub_crm.php';
addTip($f, '>All Customers</h3>',
    '>All Customers <?= helpTip(\'Every person and business that buys from your farm. Track their contact details, purchase history, and preferences.\') ?></h3>');
addTip($f, '>Customer Segments</h3>',
    '>Customer Segments <?= helpTip(\'Group your customers by type: wholesale buyers, retail customers, restaurants, hotels. Helps you target the right people.\') ?></h3>');
addTip($f, '>Follow-ups</h3>',
    '>Follow-ups <?= helpTip(\'Reminders to contact customers: check if they need a repeat order, resolve complaints, or share new products.\') ?></h3>');
addTip($f, '>Contact History</h3>',
    '>Contact History <?= helpTip(\'A timeline of every conversation and interaction with your customers. Never forget what was discussed.\') ?></h3>');

// ══════ hub_people.php ══════
$f = $root . '/Frontend/admin/hub_people.php';
$fc = file_get_contents($f);
$peopleTips = [
    '>People Directory</h3>' => '>People Directory <?= helpTip(\'Everyone connected to your farm: workers, suppliers, customers, vets, government officers. One place for all contacts.\') ?></h3>',
    '>Roles & Permissions</h3>' => '>Roles & Permissions <?= helpTip(\'Control who can do what on the system. Farm managers see everything; workers see only their tasks.\') ?></h3>',
];
foreach ($peopleTips as $old => $new) {
    if (strpos($fc, $old) !== false && strpos($fc, 'helpTip') === false) {
        $fc = str_replace($old, $new, $fc, $count);
        $changes += $count;
    }
}
file_put_contents($f, $fc);
echo "OK: hub_people.php\n";

echo "\nDone! Added $changes more help tooltips.\n";
