<?php
/**
 * Add help tooltips to all admin modules
 * Uses simple str_replace with carefully escaped strings
 */

$filePath = __DIR__ . '/../Frontend/admin/hub_operations.php';
$file = file_get_contents($filePath);
$count = 0;

// Map of old => new for all headings that need help tooltips
$map = [
    // Mortality
    [">Mortality Records</h3>", ">Mortality Records <?= helpTip('Record when animals die. Track the cause (disease, predator, accident) so you can prevent it from happening again.') ?></h3>"],
    // Quarantine
    [">Quarantine Management</h3>", ">Quarantine Management <?= helpTip('Isolate sick animals here so they do not infect the healthy ones. Track their treatment and recovery.') ?></h3>"],
    // AI
    [">Artificial Insemination Records</h3>", ">AI & Breeding Records <?= helpTip('Track artificial insemination (AI) and natural breeding. Record which bull was used and whether the animal got pregnant.') ?></h3>"],
    // Body Condition
    [">Body Condition Scoring</h3>", ">Body Condition Scoring <?= helpTip('Score how fat or thin an animal looks (1 to 5). A score of 3 is ideal. Too thin = not enough food. Too fat = health problems.') ?></h3>"],
    // Transport
    [">Animal Transport</h3>", ">Animal Transport <?= helpTip('Record when you move animals from one place to another. Track transporter details, cost, and delivery status.') ?></h3>"],
    // Preventive Care
    [">Preventive Care Schedule</h3>", ">Preventive Care <?= helpTip('Regular treatments to keep animals healthy: deworming every 3 months, hoof trimming, shearing for sheep.') ?></h3>"],
    // Grazing
    [">Grazing & Pasture Management</h3>", ">Grazing & Pasture <?= helpTip('Manage where your animals eat grass. Rotate them between fields so the grass can regrow.') ?></h3>"],
    // Farm Map
    [">Farm Map Overview</h3>", ">Farm Map <?= helpTip('See a visual layout of your farm buildings, fields, and animal houses.') ?></h3>"],
];

foreach ($map as [$old, $new]) {
    if (strpos($file, $old) !== false) {
        $file = str_replace($old, $new, $file);
        $count++;
    }
}

file_put_contents($filePath, $file);
echo "hub_operations.php: $count tooltips added\n";

// ══════════════════════════════════════════════════════════════
// HUB CROPS
// ══════════════════════════════════════════════════════════════
$filePath = __DIR__ . '/../Frontend/admin/hub_crops.php';
$file = file_get_contents($filePath);
$count = 0;

$cropsMap = [
    [">Fields & Land</h3>", ">Fields & Land <?= helpTip('All the pieces of land you farm on. Add each field with its size, location, and soil type.') ?></h3>"],
    [">Crop Plantings</h3>", ">Crop Plantings <?= helpTip('A record of what crop you planted, where, when, and how much area. Track from planting to harvest.') ?></h3>"],
    [">Field Activities</h3>", ">Field Activities <?= helpTip('Record every action you do on a field: ploughing, planting, fertilizing, spraying, weeding. This tracks your costs.') ?></h3>"],
    [">Harvest Records</h3>", ">Harvest Records <?= helpTip('Record what you harvested, how much, and how much you sold it for. This shows your income from crops.') ?></h3>"],
    [">Crop Cost Tracking</h3>", ">Crop Cost Tracking <?= helpTip('Track every shilling spent on a crop: seeds, fertilizer, labor, transport. This tells you if the crop made money.') ?></h3>"],
    [">Irrigation Management</h3>", ">Irrigation <?= helpTip('Record when and how you water your crops. Track water source, amount, and cost to save money on water.') ?></h3>"],
    [">Pest &amp; Disease Management</h3>", ">Pest &amp; Disease <?= helpTip('Track insects, diseases, and weeds that damage your crops. Record what spray you used and the cost.') ?></h3>"],
    [">Pest & Disease Management</h3>", ">Pest & Disease <?= helpTip('Track insects, diseases, and weeds that damage your crops. Record what spray you used and the cost.') ?></h3>"],
    [">Growth Monitoring</h3>", ">Growth Monitoring <?= helpTip('Check how your crops are growing: measure plant height and health. Catches problems early.') ?></h3>"],
    [">Seed Inventory</h3>", ">Seed Inventory <?= helpTip('Track all the seeds you have in store. Know when they expire and how many are left.') ?></h3>"],
    [">Post-Harvest Handling</h3>", ">Post-Harvest <?= helpTip('What happens after you pick the crop: drying, grading by size, packaging, and storage.') ?></h3>"],
    [">Soil Health Management</h3>", ">Soil Health <?= helpTip('Test your soil to know what nutrients it needs. Good soil = healthy crops = more money.') ?></h3>"],
];

foreach ($cropsMap as [$old, $new]) {
    if (strpos($file, $old) !== false) {
        $file = str_replace($old, $new, $file);
        $count++;
    }
}

file_put_contents($filePath, $file);
echo "hub_crops.php: $count tooltips added\n";

// ══════════════════════════════════════════════════════════════
// MIXED FARMING DASHBOARD
// ══════════════════════════════════════════════════════════════
$filePath = __DIR__ . '/../Frontend/admin/mixed_farming.php';
$file = file_get_contents($filePath);
$count = 0;

$mixedMap = [
    ["Mixed Farming Dashboard</h1>", "Mixed Farming Dashboard <?= helpTip('See your whole farm in one view: livestock, crops, money, and upcoming tasks.') ?></h1>"],
    [">Livestock Summary</h3>", ">Livestock Summary <?= helpTip('All your animals counted by type. See how many chickens, cattle, goats, etc. you have.') ?></h3>"],
    [">Crop Overview</h3>", ">Crop Overview <?= helpTip('What crops are growing now, where they are, and how much they cost so far.') ?></h3>"],
    [">Housing by Species</h3>", ">Housing <?= helpTip('All buildings and pens on your farm, grouped by which animal lives there.') ?></h3>"],
    [">Upcoming Tasks</h3>", ">Upcoming Tasks <?= helpTip('Vaccinations due, harvests coming, and health alerts that need your attention.') ?></h3>"],
];

foreach ($mixedMap as [$old, $new]) {
    if (strpos($file, $old) !== false) {
        $file = str_replace($old, $new, $file);
        $count++;
    }
}

file_put_contents($filePath, $file);
echo "mixed_farming.php: $count tooltips added\n";

echo "\n=== All help tooltips added ===\n";
