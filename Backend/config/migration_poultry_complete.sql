-- ══════════════════════════════════════════════════════════════
-- Wangari Farm — Complete Poultry Management System
-- Migration: Full Feature Set for Poultry & Feeds + Online Shop
-- Includes: Health, Batches/Houses, Daily Reconciliation,
--           Egg Grading, Stores Tracking, Feed Production,
--           Bulk Sales, Walk-in Customers, Online Orders
-- ══════════════════════════════════════════════════════════════

USE wangari_db;

-- ─────────────────────────────────────────────────────────────
-- 1. HOUSES — Each physical chicken house (e.g. "Long House",
--    "Short House A", "Tangakona Block 14")
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS houses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_name VARCHAR(100) NOT NULL UNIQUE,
    house_code VARCHAR(50) NOT NULL UNIQUE,
    location VARCHAR(150),
    capacity INT DEFAULT 0,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 2. BATCHES — A group of birds placed in a house (e.g. Batch 15,
--    Batch 16, "Layers Tangakona batch 14")
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_name VARCHAR(100) NOT NULL,
    batch_code VARCHAR(50) NOT NULL UNIQUE,
    house_id INT NOT NULL,
    flock_id INT,
    breed VARCHAR(100),
    batch_type ENUM('broiler', 'layer', 'kienyeji', 'dual_purpose') DEFAULT 'layer',
    initial_birds INT NOT NULL DEFAULT 0,
    current_birds INT NOT NULL DEFAULT 0,
    placement_date DATE NOT NULL,
    expected_harvest_date DATE,
    expected_sale_date DATE,
    status ENUM('active', 'sold', 'completed', 'cancelled') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE RESTRICT,
    FOREIGN KEY (flock_id) REFERENCES flocks(id) ON DELETE SET NULL,
    INDEX idx_batch_status (status),
    INDEX idx_batch_house (house_id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 3. DAILY BATCH LOG — Per-house, per-day mortality/weight/sales
--    Mirrors "Batch 15 2026" spreadsheet (one sheet per house)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS daily_batch_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    record_date DATE NOT NULL,
    week_number INT,
    opening_birds INT NOT NULL DEFAULT 0,
    mortality INT NOT NULL DEFAULT 0,
    mortality_rate DECIMAL(6,4) DEFAULT 0.0000,
    sold_birds INT NOT NULL DEFAULT 0,
    closing_birds INT NOT NULL DEFAULT 0,
    expected_weight_kg DECIMAL(8,3) DEFAULT 0.000,
    average_weight_kg DECIMAL(8,3) DEFAULT 0.000,
    trays INT NOT NULL DEFAULT 0,
    total_eggs INT NOT NULL DEFAULT 0,
    extra_large_eggs INT NOT NULL DEFAULT 0,
    damaged_eggs INT NOT NULL DEFAULT 0,
    net_for_sale INT NOT NULL DEFAULT 0,
    production_pct DECIMAL(6,4) DEFAULT 0.0000,
    notes TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_batch_day (batch_id, record_date),
    INDEX idx_record_date (record_date)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 4. HEALTH RECORDS — vaccinations, treatments, mortality
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS health_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_date DATE NOT NULL,
    flock_id INT,
    batch_id INT,
    subject VARCHAR(255) NOT NULL,
    record_type ENUM(
        'vaccination', 'treatment', 'mortality', 'checkup',
        'deworming', 'vitamins', 'antibiotic', 'observation'
    ) NOT NULL,
    vaccine_name VARCHAR(150),
    product_name VARCHAR(150),
    dosage VARCHAR(100),
    route ENUM('oral', 'injection', 'spray', 'water', 'feed', 'eye_drop', 'wing_web') DEFAULT 'oral',
    birds_treated INT DEFAULT 0,
    mortality_count INT DEFAULT 0,
    mortality_reason VARCHAR(255),
    vet_name VARCHAR(150),
    next_due_date DATE,
    cost DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('scheduled', 'completed', 'missed', 'ongoing') DEFAULT 'completed',
    notes TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (flock_id) REFERENCES flocks(id) ON DELETE SET NULL,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_health_date (record_date),
    INDEX idx_health_type (record_type)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 5. EGG GRADING — grade eggs by size (Extra Large, B14, B15)
--    Crates-by-size breakdown as seen in SALES REPORT 2026
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS egg_grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_code VARCHAR(20) NOT NULL UNIQUE,
    grade_name VARCHAR(50) NOT NULL,
    weight_min_grams INT,
    weight_max_grams INT,
    pieces_per_crate INT DEFAULT 30,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS daily_egg_grading (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_date DATE NOT NULL,
    batch_id INT,
    grade_id INT NOT NULL,
    total_eggs INT NOT NULL DEFAULT 0,
    crates_count DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    damaged INT NOT NULL DEFAULT 0,
    notes TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE SET NULL,
    FOREIGN KEY (grade_id) REFERENCES egg_grades(id) ON DELETE RESTRICT,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_grade_date (record_date)
) ENGINE=InnoDB;

-- Seed default grades (incl. standard market sizes: Small/Medium/Large)
INSERT IGNORE INTO egg_grades (id, grade_code, grade_name, weight_min_grams, weight_max_grams, pieces_per_crate) VALUES
(1, 'XL',  'Extra Large',  70, 999, 30),
(2, 'B14', 'Grade B14',     60, 69,  30),
(3, 'B15', 'Grade B15',     50, 59,  30),
(4, 'CRK', 'Cracked',       0,  49,  30),
(5, 'PW',  'Peewee',        1,  41,  30),
(6, 'S',   'Small',        42,  49,  30),
(7, 'M',   'Medium',       50,  55,  30),
(8, 'L',   'Large',        56,  64,  30),
(9, 'J',   'Jumbo',        71, 999,  30);

-- ─────────────────────────────────────────────────────────────
-- 6. DAILY SALES RECONCILIATION — mirrors BATCH 16 spreadsheet
--    Production + crates × price tier = total sales per day
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS daily_sales_reconciliation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_date DATE NOT NULL UNIQUE,
    opening_balance_crates INT NOT NULL DEFAULT 0,
    total_production_crates INT NOT NULL DEFAULT 0,
    total_sold_crates INT NOT NULL DEFAULT 0,
    closing_balance_crates INT NOT NULL DEFAULT 0,
    total_sales_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_eggs INT NOT NULL DEFAULT 0,
    notes TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sale_date (sale_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS daily_sales_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reconciliation_id INT NOT NULL,
    product_type ENUM('eggs', 'broiler', 'kienyeji', 'manure', 'other') DEFAULT 'eggs',
    unit_price DECIMAL(10,2) NOT NULL,
    quantity_crates INT NOT NULL DEFAULT 0,
    line_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notes VARCHAR(255),
    FOREIGN KEY (reconciliation_id) REFERENCES daily_sales_reconciliation(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 7. STORES / RAW MATERIALS TRACKING
--    Mirrors STORES TRACKING 2026 spreadsheet
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS raw_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_name VARCHAR(100) NOT NULL UNIQUE,
    material_code VARCHAR(50) UNIQUE,
    unit ENUM('kg', 'g', 'litre', 'piece', 'bag', 'crate') DEFAULT 'kg',
    opening_balance DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    current_stock DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    reserved_production_kg DECIMAL(12,2) DEFAULT 0.00,
    min_stock_level DECIMAL(12,3) DEFAULT 1.000,
    current_price_per_unit DECIMAL(10,2) DEFAULT 0.00,
    supplier_id INT,
    category ENUM('feed_ingredient', 'drug', 'vaccine', 'packaging', 'other') DEFAULT 'feed_ingredient',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_material_category (category)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS raw_material_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movement_date DATE NOT NULL,
    material_id INT NOT NULL,
    movement_type ENUM(
        'opening_balance', 'received', 'used_production',
        'used_treatment', 'sold', 'transfer_out', 'transfer_in',
        'adjustment_add', 'adjustment_remove', 'staff_use', 'wastage'
    ) NOT NULL,
    quantity_kg DECIMAL(12,3) NOT NULL,
    balance_after DECIMAL(12,3) NOT NULL,
    unit_cost DECIMAL(10,2) DEFAULT 0.00,
    total_cost DECIMAL(12,2) DEFAULT 0.00,
    batch_id INT,
    reference_no VARCHAR(50),
    description VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES raw_materials(id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_movement_date (movement_date),
    INDEX idx_movement_type (movement_type)
) ENGINE=InnoDB;

-- Seed initial materials matching STORES TRACKING 2026
INSERT IGNORE INTO raw_materials (id, material_name, unit, opening_balance, current_stock, current_price_per_unit, category) VALUES
(1,  'Maize',                    'kg', 18050, 18050, 35.00,  'feed_ingredient'),
(2,  'Maize Germ/Bran',          'kg', 4775,  4775,  25.00,  'feed_ingredient'),
(3,  'Wheat Bran',               'kg', 0,     0,     22.00,  'feed_ingredient'),
(4,  'Layer Premix',             'kg', 475,   475,   450.00, 'feed_ingredient'),
(5,  'Lime',                     'kg', 4300,  4300,  15.00,  'feed_ingredient'),
(6,  'Soya Cake',                'kg', 12264, 12264, 85.00,  'feed_ingredient'),
(7,  'Wheat Pollard',            'kg', 5510,  5510,  28.00,  'feed_ingredient'),
(8,  'Sunflower Cake',           'kg', 5490,  5490,  42.00,  'feed_ingredient'),
(9,  'Toxin Binder',             'kg', 49,    49,    250.00, 'feed_ingredient'),
(10, 'DCP',                      'kg', 81,    81,    120.00, 'feed_ingredient'),
(11, 'Biogar',                   'kg', 24,    24,    180.00, 'drug'),
(12, 'Egg Colour',               'kg', 37.5,  37.5,  220.00, 'feed_ingredient'),
(13, 'Broiler Premix',           'kg', 260,   260,   500.00, 'feed_ingredient'),
(14, 'Staff Maize',              'kg', 308.5, 308.5, 35.00,  'other'),
(15, 'Kienyeji Maize',           'kg', 6,     6,     35.00,  'other'),
(16, 'Amin Vit',                 'litre', 1,  1,     850.00, 'drug'),
(17, 'Tylodoxy',                 'kg', 1,     1,     1200.00,'drug'),
(18, 'Hiprotectol',              'litre', 2,  2,     650.00, 'drug'),
(19, 'Miaphos',                  'kg', 4,     4,     950.00, 'drug'),
(20, 'Poultry Dust',             'kg', 17,    17,    450.00, 'drug'),
(21, 'Advice',                   'litre', 6,  6,     550.00, 'drug'),
(22, 'Amin Total',               'litre', 3,  3,     900.00, 'drug'),
(23, 'Livervital',               'litre', 5,  5,     720.00, 'drug'),
(24, 'Agritonic',                'litre', 6,  6,     480.00, 'drug'),
(25, 'Agrivitam',                'litre', 0,  0,     520.00, 'drug'),
(26, 'Aliseryl',                 'litre', 0,  0,     680.00, 'drug');

-- ─────────────────────────────────────────────────────────────
-- 8. FEED RECIPES & PRODUCTION
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS feed_recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_name VARCHAR(100) NOT NULL UNIQUE,
    product_id INT,
    description TEXT,
    base_bag_size_kg DECIMAL(6,2) DEFAULT 70.00,
    target_species ENUM('layers', 'broilers', 'chicks', 'kienyeji', 'all') DEFAULT 'layers',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS feed_recipe_ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NOT NULL,
    raw_material_id INT NOT NULL,
    amount_per_bag_kg DECIMAL(8,3) NOT NULL,
    FOREIGN KEY (recipe_id) REFERENCES feed_recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (raw_material_id) REFERENCES raw_materials(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS feed_production_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_date DATE NOT NULL,
    recipe_id INT NOT NULL,
    bags_produced INT NOT NULL,
    bag_size_kg DECIMAL(6,2) NOT NULL,
    total_kg DECIMAL(10,2) NOT NULL,
    total_cost DECIMAL(12,2) NOT NULL,
    cost_per_kg DECIMAL(10,2) NOT NULL,
    notes TEXT,
    produced_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipe_id) REFERENCES feed_recipes(id) ON DELETE RESTRICT,
    FOREIGN KEY (produced_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_prod_date (production_date)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 9. WALK-IN CUSTOMERS / BULK SALES (selling point)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS walk_in_customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    customer_type ENUM('retail', 'wholesale', 'institution', 'agent') DEFAULT 'retail',
    address VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bulk_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_date DATE NOT NULL,
    sale_number VARCHAR(30) NOT NULL UNIQUE,
    customer_id INT,
    customer_name VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(20),
    product_type ENUM('eggs', 'broiler', 'kienyeji', 'manure', 'feed', 'chicks', 'other') NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) DEFAULT 'crate',
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('cash', 'mpesa', 'bank', 'credit') DEFAULT 'cash',
    payment_status ENUM('paid', 'partial', 'pending', 'cancelled') DEFAULT 'paid',
    notes TEXT,
    sold_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES walk_in_customers(id) ON DELETE SET NULL,
    FOREIGN KEY (sold_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sale_date (sale_date),
    INDEX idx_sale_type (product_type)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 10. SUPPLIERS (feed raw material vendors)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(150) NOT NULL,
    contact_name VARCHAR(150),
    phone VARCHAR(20),
    email VARCHAR(100),
    address VARCHAR(255),
    lead_time_days INT DEFAULT 5,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 11. EXTEND ORDERS — add selling_point, customer_type, etc.
-- ─────────────────────────────────────────────────────────────
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS selling_point ENUM('online', 'shop', 'phone', 'walk_in', 'bulk') DEFAULT 'online',
    ADD COLUMN IF NOT EXISTS delivery_method ENUM('pickup', 'delivery', 'courier') DEFAULT 'pickup',
    ADD COLUMN IF NOT EXISTS delivery_date DATE NULL,
    ADD COLUMN IF NOT EXISTS customer_notes TEXT,
    ADD COLUMN IF NOT EXISTS internal_notes TEXT;

-- ─────────────────────────────────────────────────────────────
-- 12. ACTIVITY LOGS (audit trail)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username VARCHAR(100),
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50),
    entity_type VARCHAR(50),
    entity_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_log_user (user_id),
    INDEX idx_log_date (created_at)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 13. SETTINGS (app-wide key/value)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO settings (setting_key, setting_value, setting_group, description) VALUES
('farm_name',            'Wangari Farm',  'general', 'Display name of the farm'),
('farm_phone',           '+254 727 585 599',    'general', 'Primary contact phone'),
('farm_email',           'info@wangari.farm','general','Contact email'),
('farm_address',         'Nasira AC Sub-location, Busibwabo, Wangari, Kenya', 'general', 'Physical address'),
('currency_code',        'KES',                'general', 'Currency code'),
('default_crate_price',  '380',                'sales',   'Default price per crate of eggs'),
('mortality_alert_threshold', '10',             'health',  'Alert when daily mortality exceeds this number'),
('low_stock_alert_days', '7',                  'stores',  'Days of stock remaining to trigger alert'),
('mpesa_shortcode',      '',                   'payment', 'M-Pesa paybill/till'),
('mpesa_passkey',        '',                   'payment', 'M-Pesa API passkey');
