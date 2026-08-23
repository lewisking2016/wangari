<?php
/**
 * Migration: Add Paystack integration tables
 * Run: php Backend/migrate_paystack.php
 */
declare(strict_types=1);

require __DIR__ . '/config/database.php';
$pdo = getDatabaseConnection();

if (!$pdo) {
    echo "ERROR: Database connection failed\n";
    exit(1);
}

echo "Setting up Paystack integration...\n";

// 1. Create pending_subscriptions table
$pdo->exec("CREATE TABLE IF NOT EXISTS pending_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan VARCHAR(50) NOT NULL,
    billing VARCHAR(20) NOT NULL DEFAULT 'monthly',
    amount DECIMAL(10,2) NOT NULL,
    reference VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_reference (reference),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "  ✓ Created pending_subscriptions table\n";

// 2. Add paystack fields to platform_users
$columns = $pdo->query("SHOW COLUMNS FROM platform_users LIKE 'paystack_subscription_code'")->fetch();
if (!$columns) {
    $pdo->exec("ALTER TABLE platform_users ADD COLUMN paystack_subscription_code VARCHAR(100) NULL AFTER subscription_expires");
    echo "  ✓ Added paystack_subscription_code column\n";
} else {
    echo "  ✓ paystack_subscription_code column already exists\n";
}

// 3. Create paystack_transactions table for webhook logging
$pdo->exec("CREATE TABLE IF NOT EXISTS paystack_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(100) NOT NULL UNIQUE,
    user_id INT,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'KES',
    status VARCHAR(50),
    event_type VARCHAR(100),
    payload JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reference (reference),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "  ✓ Created paystack_transactions table\n";

echo "\nPaystack integration setup complete!\n";
echo "\nNext steps:\n";
echo "1. Set your webhook URL in Paystack dashboard:\n";
echo "   https://wangari.imeantech.com/Backend/api/paystack_webhook.php\n";
echo "2. Select events: charge.success, subscription.create, subscription.disable\n";
echo "3. Test with a payment in Paystack test mode\n";
