<?php
/**
 * One-time migration: Fix platform_settings defaults
 * Run: php Backend/migrate_fix_settings.php
 */
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
$pdo = getDatabaseConnection();

if (!$pdo) {
    echo "ERROR: Database connection failed\n";
    exit(1);
}

$updates = [
    'trial_duration_days' => '30',
    'platform_version' => '1.2.0',
];

foreach ($updates as $key => $value) {
    $exists = $pdo->prepare("SELECT COUNT(*) FROM platform_settings WHERE setting_key = ?");
    $exists->execute([$key]);
    if ($exists->fetchColumn()) {
        $pdo->prepare("UPDATE platform_settings SET setting_value = ? WHERE setting_key = ?")->execute([$value, $key]);
        echo "Updated: $key = $value\n";
    } else {
        $pdo->prepare("INSERT INTO platform_settings (setting_key, setting_value) VALUES (?, ?)")->execute([$key, $value]);
        echo "Inserted: $key = $value\n";
    }
}

echo "Migration complete.\n";
