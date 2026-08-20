<?php
/**
 * Add help tooltips to mixed_farming.php and dashboard.php
 */

// ══════════════════════════════════════════════════════════════
// MIXED FARMING DASHBOARD — headings with icons
// ══════════════════════════════════════════════════════════════
$filePath = __DIR__ . '/../Frontend/admin/mixed_farming.php';
$file = file_get_contents($filePath);
$count = 0;

$mixedMap = [
    ["Livestock Summary</h3>", "Livestock Summary <?= helpTip('All your animals counted by type. See how many chickens, cattle, goats, etc. you have.') ?></h3>"],
    ["Crop Overview</h3>", "Crop Overview <?= helpTip('What crops are growing now, where they are, and how much they cost so far.') ?></h3>"],
    ["Housing by Species</h3>", "Housing by Species <?= helpTip('All buildings and pens on your farm, grouped by which animal lives there.') ?></h3>"],
    ["Upcoming Tasks</h3>", "Upcoming Tasks <?= helpTip('Vaccinations due, harvests coming, and health alerts that need your attention.') ?></h3>"],
];

foreach ($mixedMap as [$old, $new]) {
    if (strpos($file, $old) !== false) {
        $file = str_replace($old, $new, $file);
        $count++;
    }
}

file_put_contents($filePath, $file);
echo "mixed_farming.php: $count tooltips added\n";

// ══════════════════════════════════════════════════════════════
// DASHBOARD
// ══════════════════════════════════════════════════════════════
$filePath = __DIR__ . '/../Frontend/admin/dashboard.php';
if (file_exists($filePath)) {
    $file = file_get_contents($filePath);
    $count = 0;
    
    $dashMap = [
        ["Dashboard</h1>", "Dashboard <?= helpTip('Your farm at a glance. See the most important numbers and what needs your attention today.') ?></h1>"],
    ];
    
    foreach ($dashMap as [$old, $new]) {
        if (strpos($file, $old) !== false) {
            $file = str_replace($old, $new, $file);
            $count++;
        }
    }
    
    file_put_contents($filePath, $file);
    echo "dashboard.php: $count tooltips added\n";
}

echo "\n=== Done ===\n";
