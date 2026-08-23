<?php
/**
 * Migration: Update platform pricing to 3-tier model
 * Run: php Backend/migrate_pricing.php
 */
declare(strict_types=1);

require __DIR__ . '/config/database.php';
$pdo = getDatabaseConnection();

if (!$pdo) {
    echo "ERROR: Database connection failed\n";
    exit(1);
}

echo "Migrating platform pricing to 3-tier model...\n";

// Update pricing settings
$updates = [
    ['pro_monthly_price', '1500'],
    ['pro_annual_price', '15000'],
    ['plus_monthly_price', '4500'],
    ['plus_annual_price', '45000'],
    ['custom_monthly_price', '12000'],
    ['trial_duration_days', '30'],
    ['platform_version', '1.3.0'],
];

$stmt = $pdo->prepare('INSERT INTO platform_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');

foreach ($updates as [$key, $value]) {
    $stmt->execute([$key, $value]);
    echo "  ✓ Updated: $key = $value\n";
}

echo "\nMigration complete!\n";
echo "Platform version: 1.3.0\n";
echo "Pricing tiers: Pro (KES 1,500/mo), Plus (KES 4,500/mo), Custom (KES 12,000+/mo)\n";
