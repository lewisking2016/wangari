-- Migration: Create all missing tables
-- Run: mysql -u wangari -p'Wangari2026!' wangari_db < Backend/config/migration_missing_tables.sql

-- CRM Segments
CREATE TABLE IF NOT EXISTS crm_segments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- CRM Contacts (communication logs)
CREATE TABLE IF NOT EXISTS crm_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    customer_type ENUM('walkin','registered','lead') DEFAULT 'walkin',
    contact_type ENUM('call','email','sms','whatsapp','visit','other') DEFAULT 'call',
    note TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- CRM Follow-ups
CREATE TABLE IF NOT EXISTS crm_followups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    customer_type ENUM('walkin','registered','lead') DEFAULT 'walkin',
    due_date DATE,
    note TEXT,
    status ENUM('open','done','overdue') DEFAULT 'open',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Reminders
CREATE TABLE IF NOT EXISTS reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    due_date DATE,
    due_time TIME,
    channel ENUM('in_app','email','sms','whatsapp') DEFAULT 'in_app',
    status ENUM('pending','done','overdue') DEFAULT 'pending',
    priority ENUM('low','medium','high') DEFAULT 'medium',
    assigned_to INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Raw Materials (feed ingredients, drugs, etc.)
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

-- Raw Material Movements
CREATE TABLE IF NOT EXISTS raw_material_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movement_date DATE NOT NULL,
    material_id INT NOT NULL,
    movement_type ENUM('purchase','production_use','adjustment','waste','transfer') DEFAULT 'purchase',
    quantity DECIMAL(12,3) NOT NULL,
    unit_cost DECIMAL(10,2) DEFAULT 0.00,
    reference VARCHAR(100),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES raw_materials(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Recipe Ingredients
CREATE TABLE IF NOT EXISTS recipe_ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NOT NULL,
    raw_material_id INT NOT NULL,
    amount_kg DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (raw_material_id) REFERENCES raw_materials(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Farm Equipment (already in DB but ensuring it exists)
CREATE TABLE IF NOT EXISTS farm_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category VARCHAR(100),
    quantity INT DEFAULT 1,
    condition_status ENUM('excellent','good','fair','poor','broken') DEFAULT 'good',
    purchase_date DATE,
    cost DECIMAL(12,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Equipment Maintenance
CREATE TABLE IF NOT EXISTS equipment_maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    maintenance_date DATE NOT NULL,
    maintenance_type ENUM('preventive','corrective','emergency') DEFAULT 'preventive',
    description TEXT,
    cost DECIMAL(10,2) DEFAULT 0.00,
    next_due DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Labour Workers
CREATE TABLE IF NOT EXISTS labour_workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(100),
    id_number VARCHAR(50),
    daily_rate DECIMAL(10,2) DEFAULT 0.00,
    employment_type ENUM('permanent','casual','contract') DEFAULT 'casual',
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Labour Attendance
CREATE TABLE IF NOT EXISTS labour_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    work_date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    hours_worked DECIMAL(5,2),
    status ENUM('present','absent','late','half_day') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Labour Payments
CREATE TABLE IF NOT EXISTS labour_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','mpesa','bank','other') DEFAULT 'cash',
    reference VARCHAR(100),
    period_start DATE,
    period_end DATE,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Products (if not exists)
CREATE TABLE IF NOT EXISTS store_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category VARCHAR(100),
    unit VARCHAR(50),
    buying_price DECIMAL(10,2) DEFAULT 0.00,
    selling_price DECIMAL(10,2) DEFAULT 0.00,
    current_stock DECIMAL(12,2) DEFAULT 0,
    min_stock_level DECIMAL(12,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SELECT '✅ All missing tables created' AS status;
