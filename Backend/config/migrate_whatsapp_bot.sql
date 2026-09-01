-- ═══════════════════════════════════════════════════════════════
-- WANGARI WHATSAPP BOT — Database Migration
-- Run: mysql -u root -p wangari_db < Backend/config/migrate_whatsapp_bot.sql
-- ═══════════════════════════════════════════════════════════════

-- 1. Add primary_goal column to users table (for onboarding wizard)
-- Note: Uses IF NOT EXISTS pattern compatible with MySQL 5.7+
SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'primary_goal');
SET @sql = IF(@exists = 0, 'ALTER TABLE users ADD COLUMN primary_goal VARCHAR(50) DEFAULT NULL AFTER role', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Simple production records (for WhatsApp bot — no flock_id required)
CREATE TABLE IF NOT EXISTS daily_production (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    farm_id INT DEFAULT NULL,
    record_date DATE NOT NULL,
    eggs_collected INT DEFAULT 0,
    mortality INT DEFAULT 0,
    milk_litres DECIMAL(8,2) DEFAULT 0.00,
    weight_gain_kg DECIMAL(8,2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_date (user_id, record_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Simple expenses table (for WhatsApp bot)
CREATE TABLE IF NOT EXISTS simple_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    farm_id INT DEFAULT NULL,
    expense_date DATE NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'misc',
    description TEXT,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, expense_date)
) ENGINE=InnoDB;

-- 4. Simple income table (for WhatsApp bot)
CREATE TABLE IF NOT EXISTS simple_income (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    farm_id INT DEFAULT NULL,
    income_date DATE NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'sales',
    description TEXT,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    customer_name VARCHAR(150) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, income_date)
) ENGINE=InnoDB;

-- 5. Simple inventory table (for WhatsApp bot)
CREATE TABLE IF NOT EXISTS simple_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    farm_id INT DEFAULT NULL,
    item_name VARCHAR(150) NOT NULL,
    quantity DECIMAL(10,2) DEFAULT 0.00,
    unit VARCHAR(50) DEFAULT 'bags',
    cost_per_unit DECIMAL(10,2) DEFAULT 0.00,
    reorder_point INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_item (user_id, item_name)
) ENGINE=InnoDB;

-- 6. Customer debts/credits (for WhatsApp bot)
CREATE TABLE IF NOT EXISTS customer_debts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    farm_id INT DEFAULT NULL,
    customer_name VARCHAR(150) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    sale_date DATE NOT NULL,
    due_date DATE DEFAULT NULL,
    amount_paid DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('pending', 'partial', 'paid', 'overdue') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB;

-- 7. WhatsApp bot message log (for analytics and debugging)
CREATE TABLE IF NOT EXISTS whatsapp_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    phone VARCHAR(20) NOT NULL,
    direction ENUM('inbound', 'outbound') NOT NULL,
    message TEXT NOT NULL,
    command VARCHAR(50) DEFAULT NULL,
    processed TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- 8. Onboarding tracking (which modules are active per user)
CREATE TABLE IF NOT EXISTS user_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_name VARCHAR(50) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_module (user_id, module_name),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ═══════════════════════════════════════════════════════════════
-- VIEWS FOR DASHBOARD (optional, useful for quick queries)
-- ═══════════════════════════════════════════════════════════════

-- Daily profit view
CREATE OR REPLACE VIEW v_daily_profit AS
SELECT 
    dp.user_id,
    dp.record_date,
    dp.eggs_collected,
    dp.mortality,
    dp.milk_litres,
    COALESCE(si.total_income, 0) as total_income,
    COALESCE(se.total_expenses, 0) as total_expenses,
    COALESCE(si.total_income, 0) - COALESCE(se.total_expenses, 0) as net_profit
FROM daily_production dp
LEFT JOIN (
    SELECT user_id, income_date, SUM(amount) as total_income 
    FROM simple_income GROUP BY user_id, income_date
) si ON dp.user_id = si.user_id AND dp.record_date = si.income_date
LEFT JOIN (
    SELECT user_id, expense_date, SUM(amount) as total_expenses 
    FROM simple_expenses GROUP BY user_id, expense_date
) se ON dp.user_id = se.user_id AND dp.record_date = se.expense_date;

-- Monthly profit summary view
CREATE OR REPLACE VIEW v_monthly_profit AS
SELECT 
    user_id,
    DATE_FORMAT(income_date, '%Y-%m') as month,
    SUM(amount) as total_income,
    0 as total_expenses,
    SUM(amount) as net_profit
FROM simple_income
GROUP BY user_id, DATE_FORMAT(income_date, '%Y-%m')
UNION ALL
SELECT 
    user_id,
    DATE_FORMAT(expense_date, '%Y-%m') as month,
    0 as total_income,
    SUM(amount) as total_expenses,
    -SUM(amount) as net_profit
FROM simple_expenses
GROUP BY user_id, DATE_FORMAT(expense_date, '%Y-%m');

-- ═══════════════════════════════════════════════════════════════
-- SEED DATA (optional — add default inventory items for new users)
-- ═══════════════════════════════════════════════════════════════

-- This will be handled by the application when a user completes onboarding
-- No seed data needed in migration

-- 9. Referral program
CREATE TABLE IF NOT EXISTS referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    referred_id INT NOT NULL,
    code_used VARCHAR(20) NOT NULL,
    status ENUM('pending', 'active', 'expired') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activated_at TIMESTAMP NULL,
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_referred (referred_id)
) ENGINE=InnoDB;

-- 10. Add referral columns to users
SET @exists_code = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'referral_code');
SET @sql_code = IF(@exists_code = 0, 'ALTER TABLE users ADD COLUMN referral_code VARCHAR(20) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_code; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists_ref = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'referred_by');
SET @sql_ref = IF(@exists_ref = 0, 'ALTER TABLE users ADD COLUMN referred_by INT DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_ref; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Migration complete! WhatsApp bot + referral tables created.' as status;
