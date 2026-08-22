-- ══════════════════════════════════════════════════════════════
-- Wangari Farm — Business & Operations Module Migration
-- Adds: Costs & Profit, Cashbook, Customer Credit (Owed),
--       Feeding Program, FCR, Procurement / Purchase Orders,
--       Broiler Workflow, Hatchery, Auto-reorder, Quality Tests
-- ══════════════════════════════════════════════════════════════

USE wangari_db;

-- ─────────────────────────────────────────────────────────────
-- 1. BATCH COSTS — track money spent per batch
--    (chick cost, feed, drugs, labour, misc) → profit calc
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS batch_costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    cost_date DATE NOT NULL,
    cost_type ENUM(
        'chick_purchase', 'feed', 'drugs_vaccines', 'labour',
        'utilities', 'transport', 'packaging', 'misc'
    ) NOT NULL,
    description VARCHAR(255),
    quantity DECIMAL(12,3) DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'unit',
    unit_cost DECIMAL(10,2) DEFAULT 0,
    total_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid_from ENUM('cash', 'mpesa', 'bank', 'credit') DEFAULT 'cash',
    reference_no VARCHAR(50),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_cost_date (cost_date),
    INDEX idx_cost_type (cost_type)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 2. CASHBOOK — every shilling in, every shilling out
--    Simple "money book" for a Grade 7 student
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cashbook_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_date DATE NOT NULL,
    direction ENUM('in', 'out') NOT NULL,
    money_source ENUM(
        'egg_sales', 'broiler_sales', 'chick_sales', 'feed_sales',
        'raw_material_sales', 'online_order', 'bulk_sale',
        'credit_payment', 'loan_in', 'other_in',
        'feed_purchase', 'raw_material_purchase', 'drugs_purchase',
        'chick_purchase', 'labour', 'transport', 'utilities',
        'rent', 'loan_repayment', 'other_out'
    ) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    paid_through ENUM('cash', 'mpesa', 'bank', 'cheque') DEFAULT 'cash',
    customer_name VARCHAR(150),
    supplier_name VARCHAR(150),
    reference_no VARCHAR(50),
    description VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_cash_date (entry_date),
    INDEX idx_cash_direction (direction)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 3. CUSTOMER CREDIT (OWED) — who owes us, payment log
--    Critical for Kenyan farms — most sales are on credit
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS customer_credits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    customer_name VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(20),
    credit_date DATE NOT NULL,
    due_date DATE,
    item_description VARCHAR(255) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    amount_paid DECIMAL(12,2) DEFAULT 0,
    balance DECIMAL(12,2) NOT NULL,
    status ENUM('unpaid', 'partial', 'paid', 'overdue', 'written_off') DEFAULT 'unpaid',
    last_payment_date DATE,
    notes TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES walk_in_customers(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_credit_status (status),
    INDEX idx_credit_date (credit_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS credit_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    credit_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    paid_through ENUM('cash', 'mpesa', 'bank') DEFAULT 'cash',
    reference_no VARCHAR(50),
    received_by INT,
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (credit_id) REFERENCES customer_credits(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 4. FEEDING PROGRAM — standards per age × type
--    How much feed a bird needs at each age
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS feeding_standards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bird_type ENUM('layer', 'broiler', 'kienyeji', 'dual_purpose') NOT NULL,
    week_number INT NOT NULL,
    feed_per_bird_per_day_grams DECIMAL(8,2) NOT NULL,
    feed_type VARCHAR(100),
    notes VARCHAR(255),
    UNIQUE KEY unique_bird_week (bird_type, week_number)
) ENGINE=InnoDB;

-- Seed default feeding standards
INSERT IGNORE INTO feeding_standards (bird_type, week_number, feed_per_bird_per_day_grams, feed_type, notes) VALUES
-- Layers
('layer', 1,  15, 'Chick Mash',        'Day-old to 1 week'),
('layer', 2,  25, 'Chick Mash',        'Weeks 2'),
('layer', 3,  35, 'Chick Mash',        'Weeks 3'),
('layer', 4,  45, 'Growers Mash',      'Weeks 4-8'),
('layer', 5,  55, 'Growers Mash',      ''),
('layer', 6,  65, 'Growers Mash',      ''),
('layer', 7,  75, 'Growers Mash',      ''),
('layer', 8,  85, 'Growers Mash',      ''),
('layer', 9,  95, 'Growers Mash',      'Pullet stage'),
('layer',10, 105, 'Layer Mash',        'Approaching lay'),
('layer',11, 115, 'Layer Mash',        ''),
('layer',12, 120, 'Layer Mash',        ''),
('layer',13, 125, 'Layer Mash',        ''),
('layer',14, 130, 'Layer Mash',        ''),
('layer',15, 135, 'Layer Mash',        ''),
('layer',16, 140, 'Layer Mash',        ''),
('layer',17, 140, 'Layer Mash',        ''),
('layer',18, 140, 'Layer Mash',        'Peak production'),
('layer',19, 140, 'Layer Mash',        ''),
('layer',20, 140, 'Layer Mash',        ''),
-- Broilers
('broiler', 1,  20, 'Broiler Starter',  'Day-old to 7d'),
('broiler', 2,  45, 'Broiler Starter',  '7-14d'),
('broiler', 3,  80, 'Broiler Grower',   '14-21d'),
('broiler', 4, 120, 'Broiler Grower',   '21-28d'),
('broiler', 5, 160, 'Broiler Finisher', '28-35d'),
('broiler', 6, 180, 'Broiler Finisher', '35-42d'),
-- Kienyeji (free-range)
('kienyeji', 1,  12, 'Chick Mash',      ''),
('kienyeji', 2,  20, 'Chick Mash',      ''),
('kienyeji', 3,  30, 'Growers Mash',    ''),
('kienyeji', 4,  40, 'Growers Mash',    ''),
('kienyeji', 5,  50, 'Growers Mash',    ''),
('kienyeji', 6,  60, 'Layer Mash',      ''),
('kienyeji', 7,  70, 'Layer Mash',      ''),
('kienyeji', 8,  80, 'Layer Mash',      '');

-- ─────────────────────────────────────────────────────────────
-- 5. FEED ALLOCATIONS — record what was actually fed to each batch
--    Compare with feeding_standards to detect over/under feeding
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS feed_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    allocation_date DATE NOT NULL,
    feed_type VARCHAR(100),
    kg_fed DECIMAL(10,3) NOT NULL,
    notes VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_alloc_date (allocation_date)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 6. SUPPLIER PRICE LIST — for auto-reorder & comparison
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS supplier_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    material_id INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    min_order_qty DECIMAL(10,2) DEFAULT 1,
    lead_time_days INT DEFAULT 5,
    last_updated DATE,
    notes VARCHAR(255),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(id) ON DELETE CASCADE,
    UNIQUE KEY unique_supplier_material (supplier_id, material_id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 7. PURCHASE ORDERS — order raw materials from suppliers
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(30) NOT NULL UNIQUE,
    supplier_id INT NOT NULL,
    order_date DATE NOT NULL,
    expected_delivery DATE,
    status ENUM('draft', 'sent', 'confirmed', 'partial', 'received', 'cancelled') DEFAULT 'draft',
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount_paid DECIMAL(12,2) DEFAULT 0,
    notes TEXT,
    created_by INT,
    received_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_po_date (order_date),
    INDEX idx_po_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    material_id INT NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    unit VARCHAR(20) DEFAULT 'kg',
    unit_price DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    quantity_received DECIMAL(10,3) DEFAULT 0,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 8. BROILER WEIGH-INS — periodic weight tracking (key for harvest)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS broiler_weighings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    weigh_date DATE NOT NULL,
    day_number INT NOT NULL,
    sample_size INT NOT NULL DEFAULT 10,
    avg_weight_kg DECIMAL(8,3) NOT NULL,
    notes TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_weigh_date (weigh_date)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 9. HATCHING / DAY-OLD CHICKS (DOC) — for hatchery operations
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS hatchery_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_date DATE NOT NULL,
    expected_hatch_date DATE NOT NULL,
    actual_hatch_date DATE,
    breed VARCHAR(100) NOT NULL,
    eggs_set INT NOT NULL,
    fertile_eggs INT DEFAULT 0,
    chicks_hatched INT DEFAULT 0,
    hatchability_pct DECIMAL(5,2) DEFAULT 0.00,
    destination ENUM('own_farm', 'sold', 'disposed') DEFAULT 'own_farm',
    cost_per_doc DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hatch_date (expected_hatch_date)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 10. EGG LOSSES — broken/stolen/eaten
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS egg_losses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loss_date DATE NOT NULL,
    batch_id INT,
    loss_type ENUM('broken', 'cracked', 'stolen', 'eaten_staff', 'fed_to_animals', 'expired', 'other') NOT NULL,
    stage ENUM('collection', 'transport', 'storage', 'other') NOT NULL DEFAULT 'collection',
    quantity INT NOT NULL,
    estimated_value DECIMAL(10,2) DEFAULT 0,
    reason VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_loss_date (loss_date)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 11. QUALITY TESTS — for raw material quality tracking
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS quality_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    test_date DATE NOT NULL,
    test_type ENUM('moisture', 'aflatoxin', 'purity', 'pesticide', 'visual', 'other') NOT NULL,
    result_value VARCHAR(50),
    unit VARCHAR(20),
    pass_fail ENUM('pass', 'fail', 'borderline') NOT NULL,
    tested_by VARCHAR(150),
    notes TEXT,
    FOREIGN KEY (material_id) REFERENCES raw_materials(id) ON DELETE CASCADE,
    INDEX idx_test_date (test_date)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 12. AUTO-REORDER RULES — when to alert & how much to order
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reorder_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL UNIQUE,
    min_days_cover INT DEFAULT 7,
    reorder_quantity DECIMAL(10,3) NOT NULL,
    preferred_supplier_id INT,
    enabled TINYINT(1) DEFAULT 1,
    last_triggered DATE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(id) ON DELETE CASCADE,
    FOREIGN KEY (preferred_supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 13. ALERTS — system-wide notifications
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS system_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    category ENUM('low_stock', 'mortality', 'overdue_credit', 'pending_order', 'production', 'health', 'system') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    related_id INT,
    related_type VARCHAR(50),
    is_read TINYINT(1) DEFAULT 0,
    is_dismissed TINYINT(1) DEFAULT 0,
    dismissed_by INT,
    dismissed_at DATETIME,
    INDEX idx_alert_date (alert_date),
    INDEX idx_alert_read (is_read, is_dismissed)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 14. LPO DOCUMENTS — Local Purchase Orders, Quotations & Invoices
--     One module: create a quotation, accept an LPO, issue an invoice.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS lpo_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_number VARCHAR(30) NOT NULL UNIQUE,
    doc_type ENUM('quotation', 'lpo', 'invoice') NOT NULL DEFAULT 'quotation',
    status ENUM('draft', 'sent', 'accepted', 'invoiced', 'paid', 'cancelled') NOT NULL DEFAULT 'draft',
    customer_name VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(30),
    customer_email VARCHAR(150),
    customer_address VARCHAR(255),
    issue_date DATE NOT NULL,
    due_date DATE,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_lpo_date (issue_date),
    INDEX idx_lpo_type (doc_type),
    INDEX idx_lpo_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lpo_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit VARCHAR(20) DEFAULT 'pcs',
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    line_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (doc_id) REFERENCES lpo_documents(id) ON DELETE CASCADE,
    INDEX idx_lpo_item_doc (doc_id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 15. ROLE PERMISSIONS — per-role module access matrix
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(50) NOT NULL,
    module_key VARCHAR(50) NOT NULL,
    can_view TINYINT(1) NOT NULL DEFAULT 0,
    can_edit TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY unique_role_module (role, module_key)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 16. FARM EQUIPMENT — tools, machinery, structures registry
--     (separate from sellable farm_items catalog)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS farm_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) DEFAULT 'Tool',
    quantity INT NOT NULL DEFAULT 1,
    condition_status ENUM('New','Good','Fair','Poor','Broken') DEFAULT 'Good',
    purchase_date DATE DEFAULT NULL,
    cost DECIMAL(12,2) DEFAULT 0.00,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
