<?php
$root = dirname(__DIR__);
$changes = 0;

function addTip2(string $path, string $old, string $new) {
    global $changes;
    if (!file_exists($path)) return;
    $content = file_get_contents($path);
    if (strpos($content, $old) === false) { echo "MISS: " . basename($path) . "\n"; return; }
    $content = str_replace($old, $new, $content, $count);
    if ($count > 0) {
        file_put_contents($path, $content);
        $changes += $count;
        echo "OK: " . basename($path) . "\n";
    }
}

// ══════ dashboard.php ══════
$f = $root . '/Frontend/admin/dashboard.php';
addTip2($f, '>Revenue Trend</h3>',
    '>Revenue Trend <?= helpTip("Shows how much money your farm earned over time. Rising line = more income.") ?></h3>');
addTip2($f, '>Order Volumes</h3>',
    '>Order Volumes <?= helpTip("How many orders you get each day or week. More orders = more customers buying.") ?></h3>');
addTip2($f, '>System Overview</h3>',
    '>System Overview <?= helpTip("Summary of your farm: total animals, crops, orders, and stock levels at a glance.") ?></h3>');
addTip2($f, '>Recent Activity</h3>',
    '>Recent Activity <?= helpTip("Latest things done in the system: new orders, animal records, payments.") ?></h3>');

// ══════ mixed_farming.php ══════
$f = $root . '/Frontend/admin/mixed_farming.php';
if (file_exists($f)) {
    addTip2($f, 'Livestock Overview</h3>',
        'Livestock Overview <?= helpTip("Summary of all your animals: how many of each species and their status.") ?></h3>');
    addTip2($f, 'Active Crop Plantings</h3>',
        'Active Crop Plantings <?= helpTip("All crops currently growing. Shows what is planted, where, and when it should be ready.") ?></h3>');
    addTip2($f, 'Housing Utilization</h3>',
        'Housing Utilization <?= helpTip("How full your animal houses are. Full houses mean you may need to expand.") ?></h3>');
    addTip2($f, 'Upcoming Tasks</h3>',
        'Upcoming Tasks <?= helpTip("What needs doing soon: vaccinations due, harvest dates, permit renewals.") ?></h3>');
}

echo "\nDone! Added $changes help tooltips.\n";
