-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NULL,
    user_id INT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    data JSON NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_user (user_id, is_read),
    INDEX idx_notif_farm (farm_id),
    INDEX idx_notif_date (created_at)
) ENGINE=InnoDB;

-- User last active tracking
CREATE TABLE IF NOT EXISTS user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    last_active DATETIME DEFAULT CURRENT_TIMESTAMP,
    current_page VARCHAR(255),
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_activity_active (last_active)
) ENGINE=InnoDB;

-- Sales commissions tracking
CREATE TABLE IF NOT EXISTS sales_commissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NULL,
    user_id INT NOT NULL,
    order_id INT NULL,
    sale_amount DECIMAL(12,2) DEFAULT 0,
    commission_rate DECIMAL(5,2) DEFAULT 0,
    commission_amount DECIMAL(12,2) DEFAULT 0,
    period_month VARCHAR(7) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_commission_user (user_id),
    INDEX idx_commission_period (period_month),
    INDEX idx_commission_farm (farm_id)
) ENGINE=InnoDB;
