<?php
/**
 * Platform Admin Migration
 * Creates all tables for the Wangari platform management system:
 * - platform_users: Platform subscribers (separate from farm users)
 * - subscription_codes: Activation codes
 * - platform_subscriptions: Subscription records
 * - platform_revenue: Revenue tracking
 * - support_tickets: Issues, emergencies, feedback
 * - platform_settings: System configuration
 */
declare(strict_types=1);
$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

require __DIR__ . '/../Backend/config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) { fwrite(STDERR, "DB connection failed\n"); exit(1); }

$ok = 0;
$skip = 0;

function createTable(PDO $pdo, string $name, string $sql): void {
    global $ok, $skip;
    try {
        $pdo->exec($sql);
        $ok++;
        echo "  ✅ $name created\n";
    } catch (Exception $e) {
        if (str_contains($e->getMessage(), 'already exists')) {
            $skip++;
            echo "  ⏭️ $name exists\n";
        } else {
            echo "  ❌ $name: " . $e->getMessage() . "\n";
        }
    }
}

echo "═══ Platform Admin Migration ═══\n\n";

// ── Platform Users ──
createTable($pdo, 'platform_users', "
CREATE TABLE IF NOT EXISTS platform_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('super_admin','admin','support','user') DEFAULT 'user',
    farm_name VARCHAR(100),
    farm_type VARCHAR(50),
    county VARCHAR(50),
    subscription_status ENUM('active','trial','expired','suspended','free') DEFAULT 'trial',
    subscription_expires DATE,
    trial_ends DATE,
    max_animals INT DEFAULT 100,
    max_fields INT DEFAULT 10,
    max_users INT DEFAULT 3,
    total_login_count INT DEFAULT 0,
    last_login DATETIME,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (subscription_status),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB");

// ── Desktop Licenses ──
createTable($pdo, 'wangari_licenses', "
CREATE TABLE IF NOT EXISTS wangari_licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_key VARCHAR(64) UNIQUE NOT NULL,
    user_id INT DEFAULT NULL,
    customer_name VARCHAR(255) DEFAULT NULL,
    customer_email VARCHAR(255) DEFAULT NULL,
    plan VARCHAR(50) DEFAULT 'desktop',
    status ENUM('active','expired','revoked') DEFAULT 'active',
    hardware_id VARCHAR(128) DEFAULT NULL,
    activations INT DEFAULT 0,
    max_devices INT DEFAULT 1,
    expires_at DATETIME DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen DATETIME DEFAULT NULL,
    INDEX idx_status (status),
    INDEX idx_hardware (hardware_id),
    INDEX idx_user (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB");

// ── Subscription Codes ──
createTable($pdo, 'subscription_codes', "
CREATE TABLE IF NOT EXISTS subscription_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    type ENUM('trial','monthly','quarterly','annual','custom','free') DEFAULT 'monthly',
    duration_days INT DEFAULT 30,
    max_animals INT DEFAULT 100,
    max_fields INT DEFAULT 10,
    max_users INT DEFAULT 3,
    description VARCHAR(255),
    created_by INT,
    used_by INT,
    used_at DATETIME,
    expires_at DATETIME,
    is_used TINYINT(1) DEFAULT 0,
    is_revoked TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_used (is_used),
    INDEX idx_type (type)
) ENGINE=InnoDB");

// ── Platform Subscriptions ──
createTable($pdo, 'platform_subscriptions', "
CREATE TABLE IF NOT EXISTS platform_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan ENUM('trial','monthly','quarterly','annual','custom','free') DEFAULT 'monthly',
    amount DECIMAL(10,2) DEFAULT 0,
    currency VARCHAR(5) DEFAULT 'KES',
    payment_method ENUM('mpesa','bank','free','code','manual') DEFAULT 'mpesa',
    mpesa_receipt VARCHAR(50),
    mpesa_phone VARCHAR(20),
    code_id INT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active','expired','cancelled','pending') DEFAULT 'active',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_plan (plan)
) ENGINE=InnoDB");

// ── Platform Revenue ──
createTable($pdo, 'platform_revenue', "
CREATE TABLE IF NOT EXISTS platform_revenue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT,
    user_id INT,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(5) DEFAULT 'KES',
    type ENUM('subscription','setup_fee','custom','refund') DEFAULT 'subscription',
    payment_method ENUM('mpesa','bank','cash','free') DEFAULT 'mpesa',
    mpesa_receipt VARCHAR(50),
    description VARCHAR(255),
    recorded_by INT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (recorded_at),
    INDEX idx_type (type),
    INDEX idx_user (user_id)
) ENGINE=InnoDB");

// ── Support Tickets ──
createTable($pdo, 'support_tickets', "
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    ticket_code VARCHAR(20) UNIQUE NOT NULL,
    subject VARCHAR(200) NOT NULL,
    category ENUM('bug','feature','urgent','billing','account','other') DEFAULT 'other',
    priority ENUM('low','medium','high','critical') DEFAULT 'medium',
    status ENUM('open','in_progress','waiting','resolved','closed') DEFAULT 'open',
    is_anonymous TINYINT(1) DEFAULT 0,
    reporter_name VARCHAR(100),
    reporter_email VARCHAR(100),
    reporter_phone VARCHAR(20),
    description TEXT,
    admin_notes TEXT,
    assigned_to INT,
    resolved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_category (category),
    INDEX idx_user (user_id)
) ENGINE=InnoDB");

// ── Ticket Messages ──
createTable($pdo, 'ticket_messages', "
CREATE TABLE IF NOT EXISTS ticket_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    sender_id INT,
    sender_type ENUM('user','admin','system') DEFAULT 'user',
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ticket (ticket_id)
) ENGINE=InnoDB");

// ── Platform Activity Log ──
createTable($pdo, 'platform_activity_log', "
CREATE TABLE IF NOT EXISTS platform_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50),
    target_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin (admin_id),
    INDEX idx_date (created_at)
) ENGINE=InnoDB");

// ── Emergency Contacts ──
createTable($pdo, 'emergency_contacts', "
CREATE TABLE IF NOT EXISTS emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(50),
    phone VARCHAR(20),
    email VARCHAR(100),
    county VARCHAR(50),
    specialty VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

// ── Platform Settings ──
createTable($pdo, 'platform_settings', "
CREATE TABLE IF NOT EXISTS platform_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text','number','boolean','json') DEFAULT 'text',
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB");

// Seed default platform admin
echo "\n═══ Seeding Data ═══\n";
try {
    $hash = password_hash('Wangari@123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO platform_users (username, email, password, full_name, role, subscription_status, is_active, subscription_expires) VALUES (?, ?, ?, ?, 'super_admin', 'active', 1, DATE_ADD(CURDATE(), INTERVAL 10 YEAR))");
    $stmt->execute(['admin', 'admin@imeantech.com', $hash, 'System Admin']);
    echo "  ✅ Platform admin seeded (admin / Wangari@123)\n";
} catch (Exception $e) {
    echo "  ⏭️ Admin already exists\n";
}

// Seed default settings
$defaults = [
    ['platform_name', 'Wangari', 'text', 'Platform display name'],
    ['platform_version', '1.0.0', 'text', 'Current version'],
    ['trial_duration_days', '30', 'number', 'Default trial period'],
    ['monthly_price', '2500', 'number', 'Monthly subscription KES'],
    ['quarterly_price', '6000', 'number', 'Quarterly subscription KES'],
    ['annual_price', '20000', 'number', 'Annual subscription KES'],
    ['max_free_animals', '100', 'number', 'Free tier animal limit'],
    ['max_free_fields', '10', 'number', 'Free tier field limit'],
    ['support_email', 'support@imeantech.com', 'text', 'Support contact email'],
    ['support_phone', '+254 114 971 070', 'text', 'Support contact phone'],
    ['mpesa_paybill', '', 'text', 'M-Pesa paybill number'],
    ['mpesa_till', '', 'text', 'M-Pesa till number'],
];

$ins = $pdo->prepare("INSERT IGNORE INTO platform_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
foreach ($defaults as $d) {
    $ins->execute($d);
}
echo "  ✅ Default settings seeded\n";

// Seed some emergency contacts
$contacts = [
    ['Dr. James Ochieng', 'Veterinary Officer', '+254 700 100 200', 'james@vet.co.ke', 'Kisumu', 'Large Animals'],
    ['Dr. Sarah Wanjiku', 'Poultry Specialist', '+254 700 300 400', 'sarah@poultry.co.ke', 'Nairobi', 'Poultry'],
    ['County Agricultural Officer', 'Government', '+254 700 500 600', 'cao@county.go.ke', 'Kiambu', 'General'],
    ['Emergency Vet Line', 'Hotline', '+254 800 100 200', '', 'National', '24/7 Emergency'],
];

$ins = $pdo->prepare("INSERT IGNORE INTO emergency_contacts (name, role, phone, email, county, specialty) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($contacts as $c) {
    $ins->execute($c);
}
echo "  ✅ Emergency contacts seeded\n";

echo "\n═══ Summary ═══\n";
echo "Tables created: $ok\n";
echo "Tables skipped: $skip\n";
echo "\nDone! ✅\n";
