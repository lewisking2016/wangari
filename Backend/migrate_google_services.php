<?php
/**
 * Migration: Google Services (Gmail + Calendar)
 * 
 * Run: php Backend/migrate_google_services.php
 */

require_once __DIR__ . '/config/database.php';

$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "ERROR: Could not connect to database\n";
    exit(1);
}

echo "Running Google Services migration...\n\n";

// ═══════════════════════════════════════════════════════════════
// 1. Google Tokens Table
// ═══════════════════════════════════════════════════════════════
$sql = "CREATE TABLE IF NOT EXISTS user_google_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    google_id VARCHAR(100),
    email VARCHAR(255),
    access_token TEXT,
    refresh_token TEXT,
    token_type VARCHAR(50) DEFAULT 'Bearer',
    expires_at DATETIME,
    scope TEXT,
    connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id),
    INDEX idx_google_id (google_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $pdo->exec($sql);
    echo "Created user_google_tokens table\n";
} catch (Exception $e) {
    echo "user_google_tokens: " . $e->getMessage() . "\n";
}

// ═══════════════════════════════════════════════════════════════
// 2. Calendar Events Table (synced events)
// ═══════════════════════════════════════════════════════════════
$sql = "CREATE TABLE IF NOT EXISTS google_calendar_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    google_event_id VARCHAR(255),
    event_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_datetime DATETIME,
    end_datetime DATETIME,
    reminder_minutes INT DEFAULT 60,
    synced TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_start_datetime (start_datetime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $pdo->exec($sql);
    echo "Created google_calendar_events table\n";
} catch (Exception $e) {
    echo "google_calendar_events: " . $e->getMessage() . "\n";
}

// ═══════════════════════════════════════════════════════════════
// 3. Notification Preferences Table
// ═══════════════════════════════════════════════════════════════
$sql = "CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email_notifications TINYINT(1) DEFAULT 1,
    calendar_sync TINYINT(1) DEFAULT 0,
    vaccination_alerts TINYINT(1) DEFAULT 1,
    low_stock_alerts TINYINT(1) DEFAULT 1,
    daily_summary TINYINT(1) DEFAULT 0,
    feeding_reminders TINYINT(1) DEFAULT 1,
    weather_alerts TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $pdo->exec($sql);
    echo "Created notification_preferences table\n";
} catch (Exception $e) {
    echo "notification_preferences: " . $e->getMessage() . "\n";
}

// ═══════════════════════════════════════════════════════════════
// 4. Notification Log Table
// ═══════════════════════════════════════════════════════════════
$sql = "CREATE TABLE IF NOT EXISTS notification_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    title VARCHAR(255),
    message TEXT,
    channel ENUM('email', 'calendar', 'push', 'sms') DEFAULT 'email',
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    sent_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $pdo->exec($sql);
    echo "Created notification_log table\n";
} catch (Exception $e) {
    echo "notification_log: " . $e->getMessage() . "\n";
}

echo "\nMigration complete!\n";
