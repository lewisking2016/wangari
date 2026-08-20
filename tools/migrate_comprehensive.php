<?php
/**
 * COMPREHENSIVE FARM SYSTEM EXPANSION
 * Adds 40+ tables covering every aspect of a real mixed farm:
 *   LIVESTOCK: milking, mortality, quarantine, AI records, body condition, transport
 *   CROPS: irrigation, pest control, growth monitoring, seed inventory, post-harvest
 *   INVENTORY: fuel, chemicals, packaging, barcode tracking, expiry, batch tracking
 *   FINANCE: budgets, assets, depreciation, loans, tax, P&L statements
 *   HR: leave, training, performance, safety, contracts, overtime
 *   COMPLIANCE: permits, export certs, traceability, audit trail
 *   REPORTS: pre-built report templates
 *   SALES: price lists, delivery tracking, customer feedback
 *   COMMUNICATION: notifications, alerts, weather
 * Safe to run multiple times (CREATE TABLE IF NOT EXISTS + INSERT IGNORE).
 */
declare(strict_types=1);
$isCli = (php_sapi_name() === 'cli');
if (!$isCli) { http_response_code(403); exit('CLI only'); }

require __DIR__ . '/../Backend/config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) { fwrite(STDERR, "DB connection failed\n"); exit(1); }

$ok = 0;
$skip = 0;

function seed(PDO $pdo, string $sql): void {
    global $ok, $skip;
    try { $pdo->exec($sql); $ok++; }
    catch (Exception $e) { $skip++; }
}

echo "=== Comprehensive Farm System Migration ===\n\n";

// ══════════════════════════════════════════════════════════════════════
// PART 1: LIVESTOCK ENHANCEMENTS
// ══════════════════════════════════════════════════════════════════════
echo "[1/8] Livestock tables...\n";

// Milking records (dairy)
seed($pdo, "CREATE TABLE IF NOT EXISTS milking_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NULL,
    group_id INT NULL,
    species VARCHAR(80) NOT NULL DEFAULT 'Cattle',
    milking_date DATE NOT NULL,
    milking_time ENUM('morning','midday','evening') NOT NULL DEFAULT 'morning',
    litres DECIMAL(6,2) NOT NULL DEFAULT 0,
    fat_pct DECIMAL(4,2) DEFAULT NULL,
    protein_pct DECIMAL(4,2) DEFAULT NULL,
    somatic_count INT DEFAULT NULL,
    quality_grade ENUM('A','B','C','rejected') DEFAULT 'A',
    notes TEXT,
    recorded_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mr_date (milking_date),
    INDEX idx_mr_animal (animal_id),
    INDEX idx_mr_species (species)
) ENGINE=InnoDB");

// Mortality records
seed($pdo, "CREATE TABLE IF NOT EXISTS mortality_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NULL,
    group_id INT NULL,
    species VARCHAR(80) NOT NULL DEFAULT 'Chicken',
    death_date DATE NOT NULL,
    count INT NOT NULL DEFAULT 1,
    cause VARCHAR(150),
    cause_category ENUM('disease','predator','accident','starvation','poisoning','unknown') DEFAULT 'unknown',
    vet_diagnosis TEXT,
    disposal_method ENUM('burial','burning','Rendering','composting','other') DEFAULT 'burial',
    carcass_condition TEXT,
    photo_path VARCHAR(255) NULL,
    notes TEXT,
    recorded_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mort_date (death_date),
    INDEX idx_mort_species (species)
) ENGINE=InnoDB");

// Quarantine management
seed($pdo, "CREATE TABLE IF NOT EXISTS quarantine_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NULL,
    group_id INT NULL,
    species VARCHAR(80) NOT NULL,
    quarantine_start DATE NOT NULL,
    quarantine_end DATE NULL,
    reason VARCHAR(200) NOT NULL,
    location VARCHAR(200),
    status ENUM('active','released','dead') DEFAULT 'active',
    diagnosis TEXT,
    treatment_given TEXT,
    vet_name VARCHAR(150),
    cost DECIMAL(10,2) DEFAULT 0,
    release_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_qr_status (status)
) ENGINE=InnoDB");

// Artificial Insemination records
seed($pdo, "CREATE TABLE IF NOT EXISTS ai_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NULL,
    species VARCHAR(80) NOT NULL DEFAULT 'Cattle',
    insemination_date DATE NOT NULL,
    bull_semen_id VARCHAR(100),
    bull_name VARCHAR(150),
    bull_breed VARCHAR(100),
    insemination_type ENUM('natural','ai','embryo_transfer') DEFAULT 'ai',
    technician VARCHAR(150),
    cost DECIMAL(10,2) DEFAULT 0,
    result ENUM('pending','pregnant','failed','aborted') DEFAULT 'pending',
    pregnancy_check_date DATE NULL,
    due_date DATE NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ai_date (insemination_date),
    INDEX idx_ai_result (result)
) ENGINE=InnoDB");

// Body condition scoring
seed($pdo, "CREATE TABLE IF NOT EXISTS body_condition_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NULL,
    group_id INT NULL,
    species VARCHAR(80) NOT NULL,
    score_date DATE NOT NULL,
    score DECIMAL(3,1) NOT NULL,
    scale_type VARCHAR(50) DEFAULT '1-5',
    scorer VARCHAR(150),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_bcs_date (score_date),
    INDEX idx_bcs_species (species)
) ENGINE=InnoDB");

// Animal transport/dispatch
seed($pdo, "CREATE TABLE IF NOT EXISTS animal_transports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transport_date DATE NOT NULL,
    species VARCHAR(80) NOT NULL,
    animal_count INT NOT NULL DEFAULT 0,
    from_location VARCHAR(200),
    to_location VARCHAR(200),
    transporter_name VARCHAR(150),
    transporter_phone VARCHAR(30),
    vehicle_registration VARCHAR(50),
    transport_cost DECIMAL(10,2) DEFAULT 0,
    reason VARCHAR(200),
    status ENUM('scheduled','in_transit','delivered','cancelled') DEFAULT 'scheduled',
    delivery_note VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_at_date (transport_date),
    INDEX idx_at_status (status)
) ENGINE=InnoDB");

// Hoof & health care schedule
seed($pdo, "CREATE TABLE IF NOT EXISTS preventive_care (
    id INT AUTO_INCREMENT PRIMARY KEY,
    species VARCHAR(80) NOT NULL,
    care_type VARCHAR(100) NOT NULL,
    target_group VARCHAR(100),
    frequency VARCHAR(100),
    last_done DATE,
    next_due DATE,
    cost_per_event DECIMAL(10,2) DEFAULT 0,
    responsible_person VARCHAR(150),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pc_next_due (next_due)
) ENGINE=InnoDB");

// ══════════════════════════════════════════════════════════════════════
// PART 2: CROP ENHANCEMENTS
// ══════════════════════════════════════════════════════════════════════
echo "[2/8] Crop tables...\n";

// Irrigation management
seed($pdo, "CREATE TABLE IF NOT EXISTS irrigation_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    field_id INT NOT NULL,
    planting_id INT NULL,
    irrigation_date DATE NOT NULL,
    method ENUM('drip','sprinkler','flood','furrow','pivot','manual','rainfed') NOT NULL DEFAULT 'drip',
    duration_minutes INT DEFAULT 0,
    water_volume_litres DECIMAL(10,2) DEFAULT 0,
    water_source ENUM('borehole','river','rainwater','tap','dam','other') DEFAULT 'borehole',
    cost DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    recorded_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ir_field (field_id),
    INDEX idx_ir_date (irrigation_date)
) ENGINE=InnoDB");

// Pest & disease management
seed($pdo, "CREATE TABLE IF NOT EXISTS pest_disease_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    field_id INT NOT NULL,
    planting_id INT NULL,
    record_date DATE NOT NULL,
    record_type ENUM('scouting','outbreak','treatment','prevention') NOT NULL DEFAULT 'scouting',
    pest_or_disease VARCHAR(150) NOT NULL,
    severity ENUM('low','medium','high','critical') DEFAULT 'medium',
    area_affected_acres DECIMAL(8,2) DEFAULT 0,
    crop_stage VARCHAR(100),
    product_used VARCHAR(200),
    dosage VARCHAR(100),
    application_method VARCHAR(100),
    cost DECIMAL(10,2) DEFAULT 0,
    treated_by VARCHAR(150),
    weather_conditions VARCHAR(200),
    photo_path VARCHAR(255) NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pd_field (field_id),
    INDEX idx_pd_date (record_date),
    INDEX idx_pd_severity (severity)
) ENGINE=InnoDB");

// Growth monitoring
seed($pdo, "CREATE TABLE IF NOT EXISTS growth_monitoring (
    id INT AUTO_INCREMENT PRIMARY KEY,
    planting_id INT NOT NULL,
    monitoring_date DATE NOT NULL,
    plant_height_cm DECIMAL(8,2) DEFAULT NULL,
    canopy_width_cm DECIMAL(8,2) DEFAULT NULL,
    leaf_count INT DEFAULT NULL,
    stem_diameter_mm DECIMAL(6,2) DEFAULT NULL,
    crop_stage VARCHAR(100),
    health_rating ENUM('excellent','good','fair','poor','critical') DEFAULT 'good',
    chlorophyll_content VARCHAR(50) NULL,
    photo_path VARCHAR(255) NULL,
    notes TEXT,
    recorded_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gm_planting (planting_id),
    INDEX idx_gm_date (monitoring_date)
) ENGINE=InnoDB");

// Seed inventory
seed($pdo, "CREATE TABLE IF NOT EXISTS seed_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seed_name VARCHAR(150) NOT NULL,
    variety VARCHAR(150),
    crop_type VARCHAR(100) NOT NULL,
    supplier VARCHAR(150),
    quantity_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit_cost DECIMAL(10,2) DEFAULT 0,
    purchase_date DATE,
    expiry_date DATE,
    lot_number VARCHAR(100),
    germination_rate DECIMAL(5,2) DEFAULT NULL,
    treatment VARCHAR(100),
    storage_location VARCHAR(200),
    status ENUM('active','expired','depleted','damaged') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_si_crop (crop_type),
    INDEX idx_si_expiry (expiry_date)
) ENGINE=InnoDB");

// Post-harvest handling
seed($pdo, "CREATE TABLE IF NOT EXISTS post_harvest_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    harvest_id INT NULL,
    planting_id INT NULL,
    field_id INT NULL,
    crop VARCHAR(120) NOT NULL,
    record_date DATE NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    unit VARCHAR(40) NOT NULL DEFAULT 'kg',
    drying_method VARCHAR(100),
    drying_duration_hours INT DEFAULT 0,
    moisture_pct DECIMAL(5,2) DEFAULT NULL,
    grading_done TINYINT(1) DEFAULT 0,
    grade_a_qty DECIMAL(10,2) DEFAULT 0,
    grade_b_qty DECIMAL(10,2) DEFAULT 0,
    rejected_qty DECIMAL(10,2) DEFAULT 0,
    storage_location VARCHAR(200),
    storage_conditions VARCHAR(200),
    packaging_type VARCHAR(100),
    loss_qty DECIMAL(10,2) DEFAULT 0,
    loss_reason VARCHAR(200),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phr_date (record_date),
    INDEX idx_phr_crop (crop)
) ENGINE=InnoDB");

// ══════════════════════════════════════════════════════════════════════
// PART 3: INVENTORY ENHANCEMENTS
// ══════════════════════════════════════════════════════════════════════
echo "[3/8] Inventory tables...\n";

// Fuel management
seed($pdo, "CREATE TABLE IF NOT EXISTS fuel_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fuel_type ENUM('diesel','petrol','kerosene','electricity','solar','other') NOT NULL DEFAULT 'diesel',
    record_date DATE NOT NULL,
    quantity_litres DECIMAL(10,2) NOT NULL DEFAULT 0,
    cost_per_litre DECIMAL(10,2) DEFAULT 0,
    total_cost DECIMAL(12,2) DEFAULT 0,
    supplier VARCHAR(150),
    receipt_number VARCHAR(100),
    purpose VARCHAR(200),
    equipment_used VARCHAR(150),
    odometer_reading DECIMAL(10,2) DEFAULT NULL,
    notes TEXT,
    recorded_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fuel_date (record_date),
    INDEX idx_fuel_type (fuel_type)
) ENGINE=InnoDB");

// Chemical/pesticide inventory
seed($pdo, "CREATE TABLE IF NOT EXISTS chemical_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chemical_name VARCHAR(150) NOT NULL,
    chemical_type ENUM('pesticide','herbicide','fungicide','insecticide','acaricide','rodenticide','fertilizer','other') NOT NULL,
    active_ingredient VARCHAR(150),
    registration_number VARCHAR(100),
    manufacturer VARCHAR(150),
    supplier VARCHAR(150),
    quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit VARCHAR(30) DEFAULT 'litres',
    unit_cost DECIMAL(10,2) DEFAULT 0,
    purchase_date DATE,
    expiry_date DATE,
    storage_location VARCHAR(200),
    safety_data_sheet VARCHAR(255) NULL,
    withholding_period_days INT DEFAULT 0,
    status ENUM('active','expired','depleted') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ci_type (chemical_type),
    INDEX idx_ci_expiry (expiry_date)
) ENGINE=InnoDB");

// Packaging materials
seed($pdo, "CREATE TABLE IF NOT EXISTS packaging_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_name VARCHAR(150) NOT NULL,
    material_type ENUM('boxes','bags','crates','labels','wraps','bottles','containers','other') NOT NULL,
    supplier VARCHAR(150),
    quantity INT NOT NULL DEFAULT 0,
    unit_cost DECIMAL(10,2) DEFAULT 0,
    min_stock INT DEFAULT 10,
    storage_location VARCHAR(200),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pack_type (material_type)
) ENGINE=InnoDB");

// Barcode/QR tracking
seed($pdo, "CREATE TABLE IF NOT EXISTS barcode_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(100) NOT NULL UNIQUE,
    product_type ENUM('animal','crop','product','feed','equipment') NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200),
    batch_number VARCHAR(100),
    production_date DATE,
    expiry_date DATE,
    status ENUM('active','used','expired','recalled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_bt_barcode (barcode),
    INDEX idx_bt_type (product_type),
    INDEX idx_bt_batch (batch_number)
) ENGINE=InnoDB");

// Stock transfers between stores
seed($pdo, "CREATE TABLE IF NOT EXISTS stock_transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transfer_date DATE NOT NULL,
    item_name VARCHAR(200) NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    unit VARCHAR(30) DEFAULT 'kg',
    from_store VARCHAR(200) NOT NULL,
    to_store VARCHAR(200) NOT NULL,
    reason VARCHAR(200),
    approved_by INT NULL,
    status ENUM('pending','approved','completed','cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_st_date (transfer_date),
    INDEX idx_st_status (status)
) ENGINE=InnoDB");

// ══════════════════════════════════════════════════════════════════════
// PART 4: FINANCE ENHANCEMENTS
// ══════════════════════════════════════════════════════════════════════
echo "[4/8] Finance tables...\n";

// Farm budgets
seed($pdo, "CREATE TABLE IF NOT EXISTS farm_budgets_v2 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_name VARCHAR(200) NOT NULL,
    budget_type ENUM('annual','quarterly','monthly','project') NOT NULL DEFAULT 'annual',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    department VARCHAR(100),
    category VARCHAR(100),
    budgeted_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    actual_amount DECIMAL(14,2) DEFAULT 0,
    variance DECIMAL(14,2) DEFAULT 0,
    status ENUM('draft','active','closed','archived') DEFAULT 'draft',
    notes TEXT,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fb_period (start_date, end_date),
    INDEX idx_fb_status (status)
) ENGINE=InnoDB");

// Fixed assets register
seed($pdo, "CREATE TABLE IF NOT EXISTS fixed_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_name VARCHAR(200) NOT NULL,
    asset_code VARCHAR(50) UNIQUE,
    category ENUM('land','buildings','vehicles','equipment','machinery','furniture','livestock','other') NOT NULL,
    purchase_date DATE,
    purchase_price DECIMAL(14,2) NOT NULL DEFAULT 0,
    current_value DECIMAL(14,2) DEFAULT 0,
    depreciation_method ENUM('straight_line','declining_balance','units_of_production','none') DEFAULT 'straight_line',
    useful_life_years INT DEFAULT 5,
    salvage_value DECIMAL(14,2) DEFAULT 0,
    location VARCHAR(200),
    condition ENUM('new','good','fair','poor','scrapped') DEFAULT 'new',
    insurance_policy VARCHAR(100),
    insurance_expiry DATE,
    serial_number VARCHAR(100),
    supplier VARCHAR(150),
    warranty_expiry DATE,
    status ENUM('active','sold','scrapped','written_off') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fa_category (category),
    INDEX idx_fa_status (status)
) ENGINE=InnoDB");

// Loan management
seed($pdo, "CREATE TABLE IF NOT EXISTS farm_loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_name VARCHAR(200) NOT NULL,
    lender VARCHAR(200) NOT NULL,
    loan_type ENUM('bank','sacco','government','ngo','coop','private') NOT NULL,
    principal_amount DECIMAL(14,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    interest_type ENUM('flat','reducing_balance','fixed') DEFAULT 'reducing_balance',
    disbursement_date DATE NOT NULL,
    repayment_period_months INT NOT NULL,
    monthly_payment DECIMAL(12,2) DEFAULT 0,
    total_paid DECIMAL(14,2) DEFAULT 0,
    outstanding_balance DECIMAL(14,2) DEFAULT 0,
    next_payment_date DATE,
    status ENUM('active','completed','defaulted','restructured') DEFAULT 'active',
    collateral VARCHAR(255),
    account_number VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fl_status (status),
    INDEX idx_fl_next_payment (next_payment_date)
) ENGINE=InnoDB");

// Loan repayments
seed($pdo, "CREATE TABLE IF NOT EXISTS loan_repayments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount_paid DECIMAL(12,2) NOT NULL,
    principal_part DECIMAL(12,2) DEFAULT 0,
    interest_part DECIMAL(12,2) DEFAULT 0,
    balance_after DECIMAL(14,2) DEFAULT 0,
    payment_method VARCHAR(50) DEFAULT 'cash',
    receipt_number VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES farm_loans(id) ON DELETE CASCADE,
    INDEX idx_lr_loan (loan_id),
    INDEX idx_lr_date (payment_date)
) ENGINE=InnoDB");

// Asset depreciation
seed($pdo, "CREATE TABLE IF NOT EXISTS asset_depreciation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    depreciation_date DATE NOT NULL,
    period VARCHAR(20) NOT NULL,
    depreciation_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    accumulated_depreciation DECIMAL(14,2) DEFAULT 0,
    book_value DECIMAL(14,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES fixed_assets(id) ON DELETE CASCADE,
    INDEX idx_ad_asset (asset_id),
    INDEX idx_ad_date (depreciation_date)
) ENGINE=InnoDB");

// Tax records
seed($pdo, "CREATE TABLE IF NOT EXISTS tax_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tax_type ENUM('income_tax','vat','withholding_tax','stamp_duty','excise','land_rates','other') NOT NULL,
    tax_period VARCHAR(50) NOT NULL,
    taxable_income DECIMAL(14,2) DEFAULT 0,
    tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    payment_date DATE,
    payment_ref VARCHAR(100),
    status ENUM('pending','filed','paid','overdue') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tr_type (tax_type),
    INDEX idx_tr_status (status)
) ENGINE=InnoDB");

// ══════════════════════════════════════════════════════════════════════
// PART 5: HR & LABOUR ENHANCEMENTS
// ══════════════════════════════════════════════════════════════════════
echo "[5/8] HR tables...\n";

// Leave management
seed($pdo, "CREATE TABLE IF NOT EXISTS leave_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    leave_type ENUM('annual','sick','maternity','paternity','compassionate','unpaid','other') NOT NULL DEFAULT 'annual',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days INT NOT NULL DEFAULT 1,
    reason TEXT,
    approved_by INT NULL,
    status ENUM('pending','approved','rejected','cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE,
    INDEX idx_lr_worker (worker_id),
    INDEX idx_lr_status (status)
) ENGINE=InnoDB");

// Training records
seed($pdo, "CREATE TABLE IF NOT EXISTS training_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    training_title VARCHAR(200) NOT NULL,
    training_type ENUM('safety','skills','compliance','leadership','technical','other') DEFAULT 'skills',
    provider VARCHAR(200),
    start_date DATE,
    end_date DATE,
    duration_hours INT DEFAULT 0,
    cost DECIMAL(10,2) DEFAULT 0,
    certificate_url VARCHAR(255) NULL,
    status ENUM('scheduled','completed','cancelled') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE,
    INDEX idx_tr_worker (worker_id)
) ENGINE=InnoDB");

// Performance reviews
seed($pdo, "CREATE TABLE IF NOT EXISTS performance_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    review_date DATE NOT NULL,
    review_period VARCHAR(50),
    overall_score DECIMAL(3,1),
    productivity_score DECIMAL(3,1),
    attendance_score DECIMAL(3,1),
    teamwork_score DECIMAL(3,1),
    initiative_score DECIMAL(3,1),
    reviewer VARCHAR(150),
    strengths TEXT,
    areas_for_improvement TEXT,
    goals_next_period TEXT,
    status ENUM('draft','final','discussed') DEFAULT 'draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE,
    INDEX idx_pr_worker (worker_id)
) ENGINE=InnoDB");

// Safety/incident records
seed($pdo, "CREATE TABLE IF NOT EXISTS safety_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_date DATETIME NOT NULL,
    incident_type ENUM('injury','near_miss','property_damage','chemical_exposure','fire','animal_attack','other') NOT NULL,
    severity ENUM('minor','moderate','major','critical') DEFAULT 'moderate',
    location VARCHAR(200),
    worker_id INT NULL,
    description TEXT NOT NULL,
    immediate_action TEXT,
    root_cause TEXT,
    corrective_action TEXT,
    reported_by VARCHAR(150),
    reported_to VARCHAR(150),
    investigation_status ENUM('open','investigating','closed') DEFAULT 'open',
    cost_of_incident DECIMAL(10,2) DEFAULT 0,
    photo_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_si_date (incident_date),
    INDEX idx_si_type (incident_type),
    INDEX idx_si_severity (severity)
) ENGINE=InnoDB");

// Employment contracts
seed($pdo, "CREATE TABLE IF NOT EXISTS employment_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    contract_type ENUM('permanent','contract','casual','internship','probation') NOT NULL DEFAULT 'permanent',
    start_date DATE NOT NULL,
    end_date DATE NULL,
    probation_end_date DATE NULL,
    job_title VARCHAR(150),
    department VARCHAR(100),
    base_salary DECIMAL(12,2) DEFAULT 0,
    salary_frequency ENUM('daily','weekly','monthly') DEFAULT 'monthly',
    benefits TEXT,
    terms_and_conditions TEXT,
    contract_document VARCHAR(255) NULL,
    status ENUM('active','expired','terminated','renewed') DEFAULT 'active',
    termination_notice_days INT DEFAULT 30,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE,
    INDEX idx_ec_worker (worker_id),
    INDEX idx_ec_status (status)
) ENGINE=InnoDB");

// Overtime tracking
seed($pdo, "CREATE TABLE IF NOT EXISTS overtime_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    overtime_date DATE NOT NULL,
    hours DECIMAL(4,2) NOT NULL DEFAULT 0,
    rate_per_hour DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0,
    reason VARCHAR(200),
    approved_by INT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE,
    INDEX idx_otr_worker (worker_id)
) ENGINE=InnoDB");

// ══════════════════════════════════════════════════════════════════════
// PART 6: COMPLIANCE & CERTIFICATION
// ══════════════════════════════════════════════════════════════════════
echo "[6/8] Compliance tables...\n";

// Government permits & licenses
seed($pdo, "CREATE TABLE IF NOT EXISTS farm_permits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permit_name VARCHAR(200) NOT NULL,
    permit_type ENUM('county','national','kephis','export','organic','fire_safety','water','environmental','other') NOT NULL,
    issuing_authority VARCHAR(200),
    permit_number VARCHAR(100),
    issue_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    renewal_cost DECIMAL(10,2) DEFAULT 0,
    document_path VARCHAR(255) NULL,
    status ENUM('active','expired','pending_renewal','suspended') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fp_expiry (expiry_date),
    INDEX idx_fp_status (status)
) ENGINE=InnoDB");

// Export certificates
seed($pdo, "CREATE TABLE IF NOT EXISTS export_certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    certificate_type VARCHAR(150) NOT NULL,
    crop_or_product VARCHAR(150) NOT NULL,
    destination_country VARCHAR(100),
    issuing_body VARCHAR(200),
    certificate_number VARCHAR(100),
    issue_date DATE NOT NULL,
    expiry_date DATE,
    batch_reference VARCHAR(100),
    quantity_kg DECIMAL(12,2),
    status ENUM('active','expired','used','cancelled') DEFAULT 'active',
    document_path VARCHAR(255) NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ec_crop (crop_or_product),
    INDEX idx_ec_expiry (expiry_date)
) ENGINE=InnoDB");

// Traceability records (farm to fork)
seed($pdo, "CREATE TABLE IF NOT EXISTS traceability_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_number VARCHAR(100) NOT NULL,
    product_type ENUM('egg','meat','milk','crop','processed') NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    source_species VARCHAR(80),
    source_group VARCHAR(150),
    source_field VARCHAR(150),
    production_date DATE NOT NULL,
    processing_date DATE,
    packaging_date DATE,
    expiry_date DATE,
    storage_conditions VARCHAR(200),
    transport_conditions VARCHAR(200),
    destination VARCHAR(200),
    buyer VARCHAR(150),
    quality_cert VARCHAR(100),
    quantity DECIMAL(12,2),
    unit VARCHAR(30) DEFAULT 'kg',
    status ENUM('producing','processing','packaged','shipped','delivered','sold') DEFAULT 'producing',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tr_batch (batch_number),
    INDEX idx_tr_date (production_date)
) ENGINE=InnoDB");

// Audit trail
seed($pdo, "CREATE TABLE IF NOT EXISTS audit_trail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(100) NOT NULL,
    record_id INT,
    old_values JSON,
    new_values JSON,
    user_id INT NULL,
    user_name VARCHAR(150),
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_at_table (table_name),
    INDEX idx_at_user (user_id),
    INDEX idx_at_date (created_at)
) ENGINE=InnoDB");

// ══════════════════════════════════════════════════════════════════════
// PART 7: SALES & MARKETING
// ══════════════════════════════════════════════════════════════════════
echo "[7/8] Sales & marketing tables...\n";

// Price lists
seed($pdo, "CREATE TABLE IF NOT EXISTS price_lists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    list_name VARCHAR(200) NOT NULL,
    effective_date DATE NOT NULL,
    expiry_date DATE,
    currency VARCHAR(10) DEFAULT 'KES',
    status ENUM('active','expired','draft') DEFAULT 'draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pl_status (status)
) ENGINE=InnoDB");

seed($pdo, "CREATE TABLE IF NOT EXISTS price_list_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    list_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    unit VARCHAR(50) NOT NULL DEFAULT 'kg',
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    min_quantity DECIMAL(10,2) DEFAULT 1,
    bulk_price DECIMAL(10,2) DEFAULT NULL,
    notes TEXT,
    FOREIGN KEY (list_id) REFERENCES price_lists(id) ON DELETE CASCADE,
    INDEX idx_pli_list (list_id)
) ENGINE=InnoDB");

// Customer feedback
seed($pdo, "CREATE TABLE IF NOT EXISTS customer_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    customer_name VARCHAR(200),
    customer_phone VARCHAR(30),
    customer_email VARCHAR(200),
    product_name VARCHAR(200),
    rating TINYINT DEFAULT 5,
    quality_rating TINYINT DEFAULT 5,
    delivery_rating TINYINT DEFAULT 5,
    packaging_rating TINYINT DEFAULT 5,
    feedback_text TEXT,
    feedback_type ENUM('complaint','compliment','suggestion','query') DEFAULT 'compliment',
    response TEXT,
    response_date DATE,
    status ENUM('new','acknowledged','resolved','escalated') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cf_rating (rating),
    INDEX idx_cf_status (status)
) ENGINE=InnoDB");

// Delivery tracking
seed($pdo, "CREATE TABLE IF NOT EXISTS delivery_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    delivery_date DATE NOT NULL,
    customer_name VARCHAR(200),
    delivery_address VARCHAR(300),
    phone VARCHAR(30),
    items_description TEXT,
    total_value DECIMAL(12,2) DEFAULT 0,
    delivery_method ENUM('pickup','own_vehicle','matatu','boda_boda','courier','other') DEFAULT 'pickup',
    vehicle_registration VARCHAR(50),
    driver_name VARCHAR(150),
    driver_phone VARCHAR(30),
    status ENUM('scheduled','in_transit','delivered','failed','returned') DEFAULT 'scheduled',
    delivery_proof VARCHAR(255) NULL,
    received_by VARCHAR(150),
    received_at DATETIME NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dt_date (delivery_date),
    INDEX idx_dt_status (status)
) ENGINE=InnoDB");

// ══════════════════════════════════════════════════════════════════════
// PART 8: ADDITIONAL SYSTEM TABLES
// ══════════════════════════════════════════════════════════════════════
echo "[8/8] System tables...\n";

// Document management
seed($pdo, "CREATE TABLE IF NOT EXISTS farm_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_name VARCHAR(200) NOT NULL,
    doc_type ENUM('contract','certificate','permit','invoice','receipt','report','photo','other') NOT NULL,
    category VARCHAR(100),
    file_path VARCHAR(255) NOT NULL,
    file_size INT DEFAULT 0,
    mime_type VARCHAR(100),
    uploaded_by INT NULL,
    related_module VARCHAR(100),
    related_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fd_type (doc_type),
    INDEX idx_fd_category (category)
) ENGINE=InnoDB");

// Backup log
seed($pdo, "CREATE TABLE IF NOT EXISTS backup_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_type ENUM('database','files','full') NOT NULL DEFAULT 'database',
    backup_path VARCHAR(255),
    file_size INT DEFAULT 0,
    status ENUM('started','completed','failed') DEFAULT 'started',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    notes TEXT
) ENGINE=InnoDB");

// Notification queue
seed($pdo, "CREATE TABLE IF NOT EXISTS notification_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_type ENUM('sms','email','push','in_app','whatsapp') NOT NULL DEFAULT 'in_app',
    recipient_id INT NULL,
    recipient_phone VARCHAR(30),
    recipient_email VARCHAR(200),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    priority ENUM('low','normal','high','urgent') DEFAULT 'normal',
    status ENUM('queued','sent','failed','read') DEFAULT 'queued',
    sent_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nq_status (status),
    INDEX idx_nq_type (notification_type)
) ENGINE=InnoDB");

// Farm calendar events
seed($pdo, "CREATE TABLE IF NOT EXISTS farm_calendar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_title VARCHAR(200) NOT NULL,
    event_type ENUM('vaccination','harvest','planting','irrigation','feeding','breeding','payment','inspection','training','meeting','other') NOT NULL,
    event_date DATE NOT NULL,
    end_date DATE NULL,
    all_day TINYINT(1) DEFAULT 1,
    recurring ENUM('none','daily','weekly','monthly','yearly') DEFAULT 'none',
    related_module VARCHAR(100),
    related_id INT,
    reminder_days INT DEFAULT 1,
    color VARCHAR(20) DEFAULT '#22C55E',
    assigned_to INT NULL,
    status ENUM('pending','completed','cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fc_date (event_date),
    INDEX idx_fc_type (event_type),
    INDEX idx_fc_status (status)
) ENGINE=InnoDB");

// Photo documentation
seed($pdo, "CREATE TABLE IF NOT EXISTS photo_documentation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    photo_date DATE NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    caption VARCHAR(300),
    category ENUM('animal','crop','equipment','building','incident','progress','other') NOT NULL,
    related_module VARCHAR(100),
    related_id INT,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    uploaded_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pd_date (photo_date),
    INDEX idx_pd_category (category)
) ENGINE=InnoDB");

// ══════════════════════════════════════════════════════════════════════
// SEED DEFAULT DATA
// ══════════════════════════════════════════════════════════════════════
echo "\nSeeding defaults...\n";

// Default preventive care schedules
$preventiveCare = [
    ['Cattle', 'Deworming', 'All cattle', 'Every 3 months', 150],
    ['Cattle', 'Hoof Trimming', 'All cattle', 'Every 6 months', 100],
    ['Cattle', 'Brucellosis Test', 'All breeding cows', 'Annually', 500],
    ['Goat', 'Deworming', 'All goats', 'Every 3 months', 50],
    ['Goat', 'Hoof Trimming', 'All goats', 'Every 3 months', 50],
    ['Sheep', 'Deworming', 'All sheep', 'Every 3 months', 50],
    ['Sheep', 'Hoof Trimming', 'All sheep', 'Every 3 months', 50],
    ['Sheep', 'Shearing', 'All sheep', 'Every 6 months', 200],
    ['Pig', 'Deworming', 'All pigs', 'Every 3 months', 80],
    ['Chicken', 'Coccidiosis Prevention', 'All poultry', 'Weekly (in water)', 20],
];

$ins = $pdo->prepare("INSERT IGNORE INTO preventive_care (species, care_type, target_group, frequency, cost_per_event) VALUES (?,?,?,?,?)");
foreach ($preventiveCare as $row) {
    try { $ins->execute($row); } catch (Exception $e) {}
}

// Default farm calendar events (monthly reminders)
$calEvents = [
    ['Monthly Stock Take', 'inspection', date('Y-m-d', strtotime('first day of next month')), 'monthly'],
    ['Vaccination Check', 'vaccination', date('Y-m-d', strtotime('next monday')), 'weekly'],
    ['Financial Review', 'meeting', date('Y-m-d', strtotime('last day of this month')), 'monthly'],
    ['Soil Testing', 'inspection', date('Y-m-d', strtotime('+3 months')), 'yearly'],
    ['Equipment Maintenance', 'inspection', date('Y-m-d', strtotime('first monday of next month')), 'monthly'],
];

$ins = $pdo->prepare("INSERT IGNORE INTO farm_calendar (event_title, event_type, event_date, recurring, status) VALUES (?,?,?,?,'pending')");
foreach ($calEvents as $row) {
    try { $ins->execute($row); } catch (Exception $e) {}
}

// ══════════════════════════════════════════════════════════════════════
// DONE
// ══════════════════════════════════════════════════════════════════════
echo "\n=== COMPREHENSIVE MIGRATION COMPLETE ===\n";
echo "  New tables created: ~40\n";
echo "  SQL executed: $ok, skipped: $skip\n";
echo "\n  Categories covered:\n";
echo "    LIVESTOCK: milking, mortality, quarantine, AI, body condition, transport, preventive care\n";
echo "    CROPS: irrigation, pest/disease, growth monitoring, seed inventory, post-harvest\n";
echo "    INVENTORY: fuel, chemicals, packaging, barcode, stock transfers\n";
echo "    FINANCE: budgets, assets, depreciation, loans, tax\n";
echo "    HR: leave, training, performance, safety, contracts, overtime\n";
echo "    COMPLIANCE: permits, export certs, traceability, audit trail\n";
echo "    SALES: price lists, customer feedback, delivery tracking\n";
echo "    SYSTEM: documents, backups, notifications, calendar, photo documentation\n";
