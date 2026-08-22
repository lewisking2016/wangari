-- ═══════════════════════════════════════════════════════════════
-- Platform Admin Tables Migration
-- Creates tables for /wangariadmin dashboard
-- ═══════════════════════════════════════════════════════════════

-- Platform Admin Users (separate from farm users)
CREATE TABLE IF NOT EXISTS platform_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    role ENUM('super_admin','admin','support','user') DEFAULT 'user',
    
    -- Farm details
    farm_name VARCHAR(255) DEFAULT '',
    farm_type VARCHAR(100) DEFAULT '',
    county VARCHAR(100) DEFAULT '',
    
    -- Subscription
    subscription_status ENUM('free','trial','active','expired','suspended') DEFAULT 'free',
    subscription_plan VARCHAR(50) DEFAULT 'free',
    subscription_expires DATE DEFAULT NULL,
    trial_ends DATE DEFAULT NULL,
    
    -- Limits
    max_animals INT DEFAULT 50,
    max_fields INT DEFAULT 5,
    max_users INT DEFAULT 3,
    
    -- Activity
    last_login DATETIME DEFAULT NULL,
    total_login_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_role (role),
    INDEX idx_subscription (subscription_status),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Desktop Licenses (one install per activation code)
CREATE TABLE IF NOT EXISTS wangari_licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_key VARCHAR(64) NOT NULL UNIQUE,
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
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_seen DATETIME DEFAULT NULL,
    INDEX idx_lic_status (status),
    INDEX idx_lic_hardware (hardware_id),
    INDEX idx_lic_user (user_id),
    INDEX idx_lic_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Subscription Codes
CREATE TABLE IF NOT EXISTS subscription_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    type VARCHAR(50) DEFAULT 'monthly',
    duration_days INT DEFAULT 30,
    max_animals INT DEFAULT 100,
    max_fields INT DEFAULT 10,
    max_users INT DEFAULT 3,
    description TEXT DEFAULT NULL,
    is_used TINYINT(1) DEFAULT 0,
    is_revoked TINYINT(1) DEFAULT 0,
    used_by INT DEFAULT NULL,
    used_at DATETIME DEFAULT NULL,
    created_by INT DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_code (code),
    INDEX idx_used (is_used),
    FOREIGN KEY (used_by) REFERENCES platform_users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES platform_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Platform Subscriptions
CREATE TABLE IF NOT EXISTS platform_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan VARCHAR(50) DEFAULT 'monthly',
    amount DECIMAL(10,2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'KES',
    payment_method VARCHAR(50) DEFAULT 'manual',
    mpesa_receipt VARCHAR(100) DEFAULT NULL,
    mpesa_phone VARCHAR(20) DEFAULT NULL,
    code_id INT DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    status ENUM('active','expired','cancelled') DEFAULT 'active',
    notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES platform_users(id) ON DELETE CASCADE,
    FOREIGN KEY (code_id) REFERENCES subscription_codes(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES platform_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Platform Revenue
CREATE TABLE IF NOT EXISTS platform_revenue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT DEFAULT NULL,
    user_id INT DEFAULT NULL,
    amount DECIMAL(10,2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'KES',
    type ENUM('subscription','manual','refund','other') DEFAULT 'subscription',
    payment_method VARCHAR(50) DEFAULT 'mpesa',
    mpesa_receipt VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    recorded_by INT DEFAULT NULL,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_recorded (recorded_at),
    FOREIGN KEY (subscription_id) REFERENCES platform_subscriptions(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES platform_users(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES platform_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Support Tickets
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    ticket_code VARCHAR(50) UNIQUE NOT NULL,
    subject VARCHAR(255) NOT NULL,
    category VARCHAR(50) DEFAULT 'other',
    priority ENUM('low','medium','high','critical') DEFAULT 'medium',
    status ENUM('open','in_progress','waiting','resolved','closed') DEFAULT 'open',
    is_anonymous TINYINT(1) DEFAULT 0,
    reporter_name VARCHAR(255) DEFAULT '',
    reporter_email VARCHAR(255) DEFAULT '',
    reporter_phone VARCHAR(50) DEFAULT '',
    description TEXT DEFAULT NULL,
    admin_notes TEXT DEFAULT NULL,
    assigned_to INT DEFAULT NULL,
    resolved_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (ticket_code),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    FOREIGN KEY (user_id) REFERENCES platform_users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES platform_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ticket Messages
CREATE TABLE IF NOT EXISTS ticket_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    sender_id INT DEFAULT NULL,
    sender_type ENUM('user','admin','system') DEFAULT 'user',
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_ticket (ticket_id),
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES platform_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Platform Activity Log
CREATE TABLE IF NOT EXISTS platform_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) DEFAULT NULL,
    target_id INT DEFAULT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_admin (admin_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    FOREIGN KEY (admin_id) REFERENCES platform_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Platform Settings
CREATE TABLE IF NOT EXISTS platform_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT DEFAULT NULL,
    setting_type ENUM('string','number','boolean','json') DEFAULT 'string',
    description TEXT DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Emergency Contacts
CREATE TABLE IF NOT EXISTS emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(100) DEFAULT '',
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(255) DEFAULT '',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════════════════
-- INSERT DEFAULT DATA
-- ═══════════════════════════════════════════════════════════════

-- Default Platform Admin (password: admin123)
INSERT INTO platform_users (username, email, password, full_name, role, subscription_status) 
VALUES ('admin', 'admin@wangari.imeantech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Platform Admin', 'super_admin', 'active')
ON DUPLICATE KEY UPDATE id=id;

-- Default Settings
INSERT INTO platform_settings (setting_key, setting_value, setting_type, description) VALUES
('trial_days', '40', 'number', 'Number of days for free trial'),
('max_free_animals', '50', 'number', 'Maximum animals for free tier'),
('grow_price', '999', 'number', 'Grow plan monthly price in KES'),
('scale_price', '2999', 'number', 'Scale plan monthly price in KES'),
('enterprise_price', '15000', 'number', 'Enterprise plan monthly price in KES'),
('support_email', 'support@wangari.imeantech.com', 'string', 'Support email address'),
('platform_name', 'Wangari', 'string', 'Platform name')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- Default Emergency Contacts
INSERT INTO emergency_contacts (name, role, phone, email) VALUES
('Wangari Support', 'Technical Support', '+254 700 000 000', 'support@wangari.imeantech.com'),
('iMeanTech', 'Platform Owner', '+254 700 000 001', 'info@imeantech.com')
ON DUPLICATE KEY UPDATE id=id;
