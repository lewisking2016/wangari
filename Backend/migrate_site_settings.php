<?php
/**
 * Migration: Update site_settings with correct contact info
 * Run: php Backend/migrate_site_settings.php
 */
declare(strict_types=1);

require __DIR__ . '/config/database.php';
$pdo = getDatabaseConnection();

if (!$pdo) {
    echo "ERROR: Database connection failed\n";
    exit(1);
}

echo "Updating site_settings...\n";

$updates = [
    ['farm_name', 'Wangari'],
    ['farm_email', 'info@imeantech.com'],
    ['farm_phone', '+254 114 971 070'],
    ['support_email', 'support@imeantech.com'],
    ['support_phone', '+254 114 971 070'],
];

$stmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');

foreach ($updates as [$key, $value]) {
    $stmt->execute([$key, $value]);
    echo "  ✓ Updated: $key = $value\n";
}

echo "\nSite settings updated successfully!\n";
echo "Contact info:\n";
echo "  Email: info@imeantech.com\n";
echo "  Phone: +254 114 971 070\n";
