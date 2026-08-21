<?php
$root = dirname(__DIR__);
$changes = 0;

function addTip3(string $path, string $old, string $new) {
    global $changes;
    $content = file_get_contents($path);
    if (strpos($content, $old) === false) { echo "MISS: " . basename($path) . "\n"; return; }
    $content = str_replace($old, $new, $content, $count);
    if ($count > 0) {
        file_put_contents($path, $content);
        $changes += $count;
        echo "OK: " . basename($path) . "\n";
    }
}

// ══════ hub_people.php ══════
$f = $root . '/Frontend/admin/hub_people.php';
addTip3($f, '>Farm Staff Members</h3>',
    '>Farm Staff Members <?= helpTip("All people on your farm: managers, supervisors, workers. Track roles, contacts, and pay rates.") ?></h3>');
addTip3($f, '>Assigned Tasks</h3>',
    '>Assigned Tasks <?= helpTip("Work given to each staff member. Who does what, when it is due, and whether it is done.") ?></h3>');
addTip3($f, '>Secure Team Messages</h3>',
    '>Secure Team Messages <?= helpTip("Private messages between team members. Keep communication on record so nothing is lost.") ?></h3>');

// ══════ hub_reminders.php ══════
$f = $root . '/Frontend/admin/hub_reminders.php';
addTip3($f, '>Scheduled Reminders</h3>',
    '>Scheduled Reminders <?= helpTip("Things you set to remember: vaccination dates, payment deadlines, permit renewals. Never miss an important date.") ?></h3>');
addTip3($f, '>Weather &amp; Field Alerts</h3>',
    '>Weather &amp; Field Alerts <?= helpTip("Weather updates and warnings for your fields. Know when rain, drought, or pests are coming.") ?></h3>');
addTip3($f, '>Next 7 Days</h3>',
    '>Next 7 Days <?= helpTip("What is coming up this week: tasks, reminders, weather alerts. Plan your week ahead.") ?></h3>');

// ══════ hub_settings.php ══════
$f = $root . '/Frontend/admin/hub_settings.php';
addTip3($f, '>Farm Calendar</h3>',
    '>Farm Calendar <?= helpTip("A calendar showing all farm events: plantings, harvests, vaccinations, meetings. See everything in one view.") ?></h3>');
addTip3($f, '>Upcoming Tasks</h3>',
    '>Upcoming Tasks <?= helpTip("What needs to be done soon on your farm. Stay on top of important work.") ?></h3>');
addTip3($f, '>Application Settings</h3>',
    '>Application Settings <?= helpTip("Configure how the system works: farm name, currency, date format, language, notifications.") ?></h3>');

echo "\nDone! Added $changes help tooltips.\n";
