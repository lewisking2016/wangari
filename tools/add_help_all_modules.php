<?php
/**
 * Add helpTip() to all section headers across every admin module.
 */

$root = dirname(__DIR__);
$changes = 0;

// Helper: replace in file
function addTip(string $path, string $old, string $new) {
    global $changes;
    if (!file_exists($path)) { echo "SKIP: $path not found\n"; return; }
    $content = file_get_contents($path);
    if (strpos($content, $old) === false) { echo "MISS: $path: $old\n"; return; }
    $content = str_replace($old, $new, $content, $count);
    if ($count > 0) {
        file_put_contents($path, $content);
        $changes += $count;
        echo "OK: " . basename($path) . " ($count)\n";
    }
}

// ══════ hub_finance.php ══════
$f = $root . '/Frontend/admin/hub_finance.php';
addTip($f,
    '>Customer Orders</h3>',
    '>Customer Orders <?= helpTip(\'All orders from your customers. Track what they ordered, quantities, prices, and payment status.\') ?></h3>'
);
addTip($f,
    '>Sales Register</h3>',
    '>Sales Register <?= helpTip(\'A log of every sale you have made. Shows date, customer, amount, and how they paid (cash, M-Pesa, bank).\') ?></h3>'
);
addTip($f,
    '>Incoming Payments Ledger</h3>',
    '>Incoming Payments Ledger <?= helpTip(\'Every payment received from customers. Matches payments to invoices so you know who has paid and who owes you.\') ?></h3>'
);
addTip($f,
    '>Update Order Status</h3>',
    '>Update Order Status <?= helpTip(\'Change the status of an order: pending, processing, delivered, or cancelled.\') ?></h3>'
);

// Budget section - look for the specific h3
$fc = file_get_contents($f);
if (strpos($fc, 'Budget & Forecasting') !== false && strpos($fc, 'helpTip') === false) {
    $fc = str_replace('>Budget & Forecasting', '>Budget & Forecasting <?= helpTip(\'Plan how much money you expect to earn and spend. Compare your plan to what actually happened.\') ?>', $fc);
    file_put_contents($f, $fc);
    $changes++;
    echo "OK: hub_finance.php (budget)\n";
}

// ══════ hub_inventory.php ══════
$f = $root . '/Frontend/admin/hub_inventory.php';
$fc = file_get_contents($f);
$invTips = [
    '>Feed & Stock</h3>' => '>Feed & Stock <?= helpTip(\'Track all animal feed in store: type, quantity, cost, supplier, and expiry date.\') ?></h3>',
    '>Equipment & Assets</h3>' => '>Equipment & Assets <?= helpTip(\'Everything you own on the farm: tractors, generators, tools. Track purchase date, cost, and condition.\') ?></h3>',
    '>Products Catalog</h3>' => '>Products Catalog <?= helpTip(\'All products your farm produces and sells: eggs, milk, meat, crops. Shows price, stock level, and unit.\') ?></h3>',
    '>Suppliers</h3>' => '>Suppliers <?= helpTip(\'People and companies you buy from: feed shops, vets, seed suppliers. Track contact details.\') ?></h3>',
    '>Stock Movements</h3>' => '>Stock Movements <?= helpTip(\'When stock comes in or goes out. This is your stock history for tracking usage and losses.\') ?></h3>',
];
foreach ($invTips as $old => $new) {
    if (strpos($fc, $old) !== false && strpos($fc, trim(extractHelp($new))) === false) {
        $fc = str_replace($old, $new, $fc, $count);
        $changes += $count;
    }
}
file_put_contents($f, $fc);
echo "OK: hub_inventory.php processed\n";

// ══════ hub_labour.php ══════
$f = $root . '/Frontend/admin/hub_labour.php';
$fc = file_get_contents($f);
$labTips = [
    '>Employees</h3>' => '>Employees <?= helpTip(\'All people who work on your farm. Track names, roles, and wages.\') ?></h3>',
    '>Attendance</h3>' => '>Attendance <?= helpTip(\'Who came to work today? Record clock-in/clock-out times.\') ?></h3>',
    '>Tasks</h3>' => '>Tasks <?= helpTip(\'Work assignments for your staff. Who does what and when.\') ?></h3>',
    '>Payroll</h3>' => '>Payroll <?= helpTip(\'Calculate how much to pay each worker: salary, overtime, deductions, and net pay.\') ?></h3>',
];
foreach ($labTips as $old => $new) {
    if (strpos($fc, $old) !== false) {
        $fc = str_replace($old, $new, $fc, $count);
        $changes += $count;
    }
}
file_put_contents($f, $fc);
echo "OK: hub_labour.php processed\n";

// ══════ hub_crm.php ══════
$f = $root . '/Frontend/admin/hub_crm.php';
$fc = file_get_contents($f);
$crmTips = [
    '>Customers</h3>' => '>Customers <?= helpTip(\'People and businesses who buy from your farm. Track contact details and purchase history.\') ?></h3>',
    '>Communications</h3>' => '>Communications <?= helpTip(\'A log of all your conversations with customers: calls, texts, emails.\') ?></h3>',
    '>Feedback</h3>' => '>Feedback <?= helpTip(\'What customers say about your products. Good feedback builds reputation; complaints need fixing.\') ?></h3>',
];
foreach ($crmTips as $old => $new) {
    if (strpos($fc, $old) !== false) {
        $fc = str_replace($old, $new, $fc, $count);
        $changes += $count;
    }
}
file_put_contents($f, $fc);
echo "OK: hub_crm.php processed\n";

// ══════ hub_people.php ══════
$f = $root . '/Frontend/admin/hub_people.php';
$fc = file_get_contents($f);
$peopleTips = [
    '>People Directory</h3>' => '>People Directory <?= helpTip(\'Everyone connected to your farm: workers, suppliers, customers, vets. One place for all contacts.\') ?></h3>',
    '>Roles & Permissions</h3>' => '>Roles & Permissions <?= helpTip(\'Control who can do what on the system. Farm managers see everything; workers see only their tasks.\') ?></h3>',
];
foreach ($peopleTips as $old => $new) {
    if (strpos($fc, $old) !== false) {
        $fc = str_replace($old, $new, $fc, $count);
        $changes += $count;
    }
}
file_put_contents($f, $fc);
echo "OK: hub_people.php processed\n";

// ══════ hub_reminders.php ══════
$f = $root . '/Frontend/admin/hub_reminders.php';
$fc = file_get_contents($f);
if (strpos($fc, '>Reminders</h3>') !== false && strpos($fc, 'helpTip') === false) {
    $fc = str_replace('>Reminders</h3>', '>Reminders <?= helpTip(\'Never miss important events: vaccination days, payment deadlines, harvest dates, permit renewals.\') ?></h3>', $fc);
    $changes++;
}
file_put_contents($f, $fc);
echo "OK: hub_reminders.php processed\n";

// ══════ hub_settings.php ══════
$f = $root . '/Frontend/admin/hub_settings.php';
$fc = file_get_contents($f);
if (strpos($fc, '>System Settings</h3>') !== false && strpos($fc, 'helpTip') === false) {
    $fc = str_replace('>System Settings</h3>', '>System Settings <?= helpTip(\'Configure how the system works: farm name, currency, date format, notifications.\') ?></h3>', $fc);
    $changes++;
}
file_put_contents($f, $fc);
echo "OK: hub_settings.php processed\n";

echo "\nDone! Added $changes help tooltips.\n";

// Helper to extract raw text for checking
function extractHelp(string $html): string {
    preg_match('/helpTip\([\'\"](.+?)[\'\"]/', $html, $m);
    return $m[1] ?? '';
}
