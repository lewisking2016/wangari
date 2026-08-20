-- ══════════════════════════════════════════════════════════════
-- Wangari Farm — Crops & Fields Module Migration (v5)
-- Tables: fields, crop_plantings, crop_activities, crop_harvests,
--         crop_costs, irrigation_records, pest_disease_records,
--         growth_monitoring, seed_inventory, post_harvest_records,
--         soil_tests, soil_amendments
-- ══════════════════════════════════════════════════════════════

-- ─────────────────────────────────────────────────────────────
-- 1. FIELDS — the physical pieces of land
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS fields (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    location    VARCHAR(200),
    size_acres  DECIMAL(8,2) NOT NULL DEFAULT 0,
    soil_type   VARCHAR(100),
    status      ENUM('active','fallow','leased_out','resting') NOT NULL DEFAULT 'active',
    gps_lat     DECIMAL(10,7),
    gps_lng     DECIMAL(10,7),
    notes       TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 2. CROP PLANTINGS — a crop season / cycle on a field
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS crop_plantings (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    field_id             INT NOT NULL,
    crop                 VARCHAR(100) NOT NULL,
    variety              VARCHAR(100),
    planting_date        DATE NOT NULL,
    area_acres           DECIMAL(8,2) NOT NULL DEFAULT 0,
    expected_harvest_date DATE,
    expected_yield       DECIMAL(10,2) DEFAULT 0,
    yield_unit           VARCHAR(20) DEFAULT 'kg',
    status               ENUM('growing','ready','harvested','failed','replanted') DEFAULT 'growing',
    season               VARCHAR(50),
    input_cost           DECIMAL(10,2) DEFAULT 0,
    notes                TEXT,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_planting_date (planting_date)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 3. CROP ACTIVITIES — tillage, planting, weeding, spraying…
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS crop_activities (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    planting_id   INT NOT NULL,
    activity_type ENUM(
        'tillage','planting','fertilising','weeding','spraying',
        'irrigation','scouting','pruning','staking','harvesting',
        'storage','transport','other'
    ) NOT NULL,
    activity_date DATE NOT NULL,
    cost          DECIMAL(10,2) DEFAULT 0,
    labour_hours  DECIMAL(6,1) DEFAULT 0,
    workers_used  INT DEFAULT 0,
    description   TEXT,
    recorded_by   INT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (planting_id) REFERENCES crop_plantings(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_activity_date (activity_date),
    INDEX idx_activity_type (activity_type)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 4. CROP HARVESTS — yield, revenue, buyer per harvest event
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS crop_harvests (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    planting_id     INT NOT NULL,
    field_id        INT,
    harvest_date    DATE NOT NULL,
    quantity        DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit            VARCHAR(20) DEFAULT 'kg',
    price_per_unit  DECIMAL(10,2) DEFAULT 0,
    revenue         DECIMAL(12,2) DEFAULT 0,
    buyer           VARCHAR(150),
    quality_grade   ENUM('A','B','C','reject') DEFAULT 'A',
    storage_location VARCHAR(100),
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (planting_id) REFERENCES crop_plantings(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE SET NULL,
    INDEX idx_harvest_date (harvest_date)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 5. CROP COSTS — per-planting input cost ledger
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS crop_costs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    planting_id  INT NOT NULL,
    cost_date    DATE NOT NULL,
    cost_category ENUM(
        'seed','fertiliser','pesticide','herbicide','fungicide',
        'irrigation','labour','equipment','transport','packaging',
        'storage','land_rent','other'
    ) NOT NULL,
    description  VARCHAR(255),
    quantity     DECIMAL(10,2) DEFAULT 0,
    unit         VARCHAR(20) DEFAULT 'unit',
    unit_cost    DECIMAL(10,2) DEFAULT 0,
    amount       DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_method ENUM('cash','mpesa','bank','credit') DEFAULT 'cash',
    receipt_no   VARCHAR(50),
    recorded_by  INT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (planting_id) REFERENCES crop_plantings(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_cost_date (cost_date),
    INDEX idx_cost_category (cost_category)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 6. IRRIGATION RECORDS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS irrigation_records (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    field_id         INT,
    planting_id      INT,
    irrigation_date  DATE NOT NULL,
    method           ENUM('drip','sprinkler','furrow','flood','manual','rain_fed') DEFAULT 'manual',
    water_source     VARCHAR(100),
    duration_hours   DECIMAL(5,1) DEFAULT 0,
    water_volume_m3  DECIMAL(10,2) DEFAULT 0,
    cost             DECIMAL(10,2) DEFAULT 0,
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE SET NULL,
    FOREIGN KEY (planting_id) REFERENCES crop_plantings(id) ON DELETE SET NULL,
    INDEX idx_irrigation_date (irrigation_date)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 7. PEST & DISEASE RECORDS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pest_disease_records (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    field_id       INT,
    planting_id    INT,
    record_date    DATE NOT NULL,
    type           ENUM('pest','disease','weed','nutrient_deficiency','other') DEFAULT 'pest',
    name           VARCHAR(150) NOT NULL,
    severity       ENUM('low','medium','high','critical') DEFAULT 'medium',
    affected_area_pct DECIMAL(5,2) DEFAULT 0,
    treatment      VARCHAR(255),
    chemical_used  VARCHAR(150),
    dosage         VARCHAR(100),
    cost           DECIMAL(10,2) DEFAULT 0,
    status         ENUM('active','treated','resolved','monitoring') DEFAULT 'active',
    follow_up_date DATE,
    notes          TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE SET NULL,
    FOREIGN KEY (planting_id) REFERENCES crop_plantings(id) ON DELETE SET NULL,
    INDEX idx_record_date (record_date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 8. GROWTH MONITORING — periodic crop checkups
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS growth_monitoring (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    planting_id     INT NOT NULL,
    monitoring_date DATE NOT NULL,
    growth_stage    VARCHAR(100),
    plant_height_cm DECIMAL(6,1),
    canopy_cover_pct DECIMAL(5,1),
    leaf_color      VARCHAR(50),
    general_health  ENUM('excellent','good','fair','poor','critical') DEFAULT 'good',
    estimated_yield DECIMAL(10,2),
    yield_unit      VARCHAR(20) DEFAULT 'kg',
    observations    TEXT,
    photos          TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (planting_id) REFERENCES crop_plantings(id) ON DELETE CASCADE,
    INDEX idx_monitoring_date (monitoring_date)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 9. SEED INVENTORY — seeds in store
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS seed_inventory (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    seed_name            VARCHAR(150) NOT NULL,
    variety              VARCHAR(100),
    crop_type            VARCHAR(100),
    supplier             VARCHAR(150),
    purchase_date        DATE,
    expiry_date          DATE,
    quantity_kg          DECIMAL(10,3) DEFAULT 0,
    reorder_level_kg     DECIMAL(10,3) DEFAULT 5,
    cost_per_kg          DECIMAL(10,2) DEFAULT 0,
    germination_rate_pct DECIMAL(5,1) DEFAULT 0,
    storage_location     VARCHAR(100),
    lot_number           VARCHAR(50),
    certified            TINYINT(1) DEFAULT 0,
    notes                TEXT,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_seed_name (seed_name)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 10. POST-HARVEST RECORDS — grading, storage, loss tracking
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS post_harvest_records (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    planting_id       INT,
    field_id          INT,
    record_date       DATE NOT NULL,
    activity          ENUM('grading','sorting','drying','milling','packaging','storage','transport','sale') NOT NULL,
    quantity_in       DECIMAL(10,2) DEFAULT 0,
    quantity_out      DECIMAL(10,2) DEFAULT 0,
    loss_pct          DECIMAL(5,2) DEFAULT 0,
    unit              VARCHAR(20) DEFAULT 'kg',
    destination       VARCHAR(150),
    price_per_unit    DECIMAL(10,2) DEFAULT 0,
    revenue           DECIMAL(12,2) DEFAULT 0,
    cost              DECIMAL(10,2) DEFAULT 0,
    notes             TEXT,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (planting_id) REFERENCES crop_plantings(id) ON DELETE SET NULL,
    FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE SET NULL,
    INDEX idx_record_date (record_date)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 11. SOIL TESTS — lab or field test results
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS soil_tests (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    field_id     INT,
    test_date    DATE NOT NULL,
    lab_name     VARCHAR(150),
    sample_depth_cm INT DEFAULT 20,
    ph           DECIMAL(4,2),
    nitrogen_ppm DECIMAL(8,2),
    phosphorus_ppm DECIMAL(8,2),
    potassium_ppm  DECIMAL(8,2),
    organic_matter_pct DECIMAL(5,2),
    texture      VARCHAR(50),
    ec_ds_m      DECIMAL(6,3),
    recommendation TEXT,
    cost         DECIMAL(10,2) DEFAULT 0,
    notes        TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE SET NULL,
    INDEX idx_test_date (test_date)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 12. SOIL AMENDMENTS — liming, manuring, mulching events
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS soil_amendments (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    field_id           INT,
    amendment_date     DATE NOT NULL,
    amendment_type     ENUM('lime','dolomite','manure','compost','biochar','gypsum','mulch','green_manure','other') NOT NULL,
    product_name       VARCHAR(150),
    quantity_kg        DECIMAL(10,2) DEFAULT 0,
    application_method VARCHAR(100),
    cost               DECIMAL(10,2) DEFAULT 0,
    purpose            VARCHAR(255),
    applied_by         INT,
    notes              TEXT,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE SET NULL,
    FOREIGN KEY (applied_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_amendment_date (amendment_date)
) ENGINE=InnoDB;

-- ══════════════════════════════════════════════════════════════
-- DEMO SEED DATA — realistic Kenyan mixed farm
-- Runs only when tables are empty (INSERT IGNORE / id-checked)
-- ══════════════════════════════════════════════════════════════

-- Fields
INSERT IGNORE INTO fields (id, name, location, size_acres, soil_type, status, notes) VALUES
(1, 'Lower Shamba',  'Near the river, east block',  3.5, 'Clay loam',   'active',  'Irrigated from borehole'),
(2, 'Upper Hill',    'North hillside',               2.0, 'Red loam',    'active',  'Rain-fed only'),
(3, 'Maize Block A', 'Central farm, flat',           4.0, 'Sandy loam',  'active',  'Main maize plot'),
(4, 'Rest Plot',     'South end',                    1.5, 'Black cotton', 'fallow', 'Fallow this season');

-- Crop Plantings
INSERT IGNORE INTO crop_plantings (id, field_id, crop, variety, planting_date, area_acres, expected_harvest_date, expected_yield, yield_unit, status, season, notes) VALUES
(1, 1, 'Tomatoes',  'Money Maker', '2026-06-01', 1.5, '2026-08-30', 3000, 'kg', 'growing',   'Long rains 2026', 'First season on this plot'),
(2, 2, 'Kale',      'Sukuma Wiki', '2026-07-10', 1.0, '2026-09-10',  800, 'kg', 'growing',   'Long rains 2026', 'Continuous harvest'),
(3, 3, 'Maize',     'H614D',       '2026-03-15', 4.0, '2026-07-20', 4800, 'kg', 'harvested', 'Long rains 2026', 'Good season'),
(4, 1, 'Beans',     'Canadian Wonder','2026-04-01',1.0,'2026-06-30',  600, 'kg', 'harvested', 'Long rains 2026', 'Mixed with maize'),
(5, 2, 'Sweet Potato','Kenya Beauregard','2026-05-20',1.0,'2026-09-01',1200,'kg','growing',  'Long rains 2026', 'Planted on terraces');

-- Crop Harvests
INSERT IGNORE INTO crop_harvests (id, planting_id, field_id, harvest_date, quantity, unit, price_per_unit, revenue, buyer, quality_grade) VALUES
(1, 3, 3, '2026-07-22', 4600, 'kg', 45,  207000, 'Farmers Market Kakamega', 'A'),
(2, 4, 1, '2026-07-01',  590, 'kg', 80,   47200, 'Local traders',           'A'),
(3, 3, 3, '2026-07-28',  200, 'kg', 30,    6000, 'Own consumption',         'B');

-- Crop Costs
INSERT IGNORE INTO crop_costs (id, planting_id, cost_date, cost_category, description, amount) VALUES
(1, 1, '2026-06-01', 'seed',       'Tomato seedlings (1500 plants)',  4500),
(2, 1, '2026-06-05', 'fertiliser', 'DAP basal dressing 50kg',         4800),
(3, 1, '2026-06-20', 'labour',     'Transplanting labour (3 workers)',  900),
(4, 1, '2026-07-05', 'pesticide',  'Ridomil for early blight',         1200),
(5, 1, '2026-07-10', 'fertiliser', 'CAN top dressing 50kg',            3500),
(6, 2, '2026-07-10', 'seed',       'Kale seedlings',                    300),
(7, 2, '2026-07-12', 'fertiliser', 'Farm yard manure 200kg',            400),
(8, 3, '2026-03-15', 'seed',       'H614D certified seed 20kg',        3200),
(9, 3, '2026-03-15', 'fertiliser', 'DAP 2 bags',                       9600),
(10,3, '2026-04-10', 'labour',     'Weeding labour',                   1800),
(11,4, '2026-04-01', 'seed',       'Canadian Wonder beans 10kg',       1500),
(12,4, '2026-04-02', 'fertiliser', 'TSP fertiliser 25kg',              2400);

-- Crop Activities
INSERT IGNORE INTO crop_activities (id, planting_id, activity_type, activity_date, cost, labour_hours, description) VALUES
(1, 1, 'tillage',      '2026-05-25',  3000, 8,  'Deep ploughing with tractor'),
(2, 1, 'planting',     '2026-06-01',   900, 12, 'Transplanting tomato seedlings'),
(3, 1, 'weeding',      '2026-06-20',   600, 6,  'First hand weeding'),
(4, 1, 'spraying',     '2026-07-05',  1200, 3,  'Fungicide spray for blight'),
(5, 1, 'fertilising',  '2026-07-10',   500, 2,  'CAN top dressing'),
(6, 1, 'irrigation',   '2026-07-15',   200, 4,  'Borehole drip irrigation'),
(7, 2, 'planting',     '2026-07-10',   300, 4,  'Kale seedling transplant'),
(8, 2, 'weeding',      '2026-07-25',   400, 4,  'Weeding and thinning'),
(9, 3, 'tillage',      '2026-03-10',  4000, 10, 'Tractor ploughing and harrowing'),
(10,3, 'planting',     '2026-03-15',   800, 8,  'Maize planting'),
(11,3, 'fertilising',  '2026-04-05',   500, 4,  'DAP side dressing'),
(12,3, 'weeding',      '2026-04-20',  1200, 12, 'First weeding round');

-- Irrigation Records
INSERT IGNORE INTO irrigation_records (id, field_id, planting_id, irrigation_date, method, water_source, duration_hours, cost) VALUES
(1, 1, 1, '2026-07-01', 'drip',    'Borehole',     2.5, 150),
(2, 1, 1, '2026-07-08', 'drip',    'Borehole',     2.5, 150),
(3, 1, 1, '2026-07-15', 'drip',    'Borehole',     2.5, 150),
(4, 3, 3, '2026-04-20', 'furrow',  'River channel', 4.0,   0);

-- Pest & Disease Records
INSERT IGNORE INTO pest_disease_records (id, field_id, planting_id, record_date, type, name, severity, affected_area_pct, treatment, chemical_used, cost, status) VALUES
(1, 1, 1, '2026-07-03', 'disease', 'Early Blight (Alternaria)',  'medium', 15, 'Spray Ridomil MZ',    'Ridomil MZ 72WP', 1200, 'treated'),
(2, 3, 3, '2026-04-15', 'pest',    'Maize Stalk Borer',          'low',     8, 'Apply Furadan granules','Furadan 3G',       800, 'resolved'),
(3, 2, 2, '2026-07-28', 'pest',    'Aphids',                     'low',     5, 'Spray Actellic',      'Actellic 50EC',    600, 'active');

-- Growth Monitoring
INSERT IGNORE INTO growth_monitoring (id, planting_id, monitoring_date, growth_stage, plant_height_cm, general_health, estimated_yield, yield_unit, observations) VALUES
(1, 1, '2026-06-15', 'Seedling establishment', 25,  'good',      2800, 'kg', 'Good take rate, 95% establishment'),
(2, 1, '2026-07-01', 'Vegetative growth',       55,  'good',      3000, 'kg', 'Healthy canopy, first flowers appearing'),
(3, 1, '2026-07-20', 'Fruiting',               75,  'excellent', 3200, 'kg', 'Heavy fruit set, some blight managed'),
(4, 3, '2026-04-05', 'Germination',            12,  'excellent', 4800, 'kg', 'Very good germination, >90%'),
(5, 3, '2026-05-10', 'V6 stage',              105,  'good',      4600, 'kg', 'Good growth, slight stalk borer pressure');

-- Seed Inventory
INSERT IGNORE INTO seed_inventory (id, seed_name, variety, crop_type, supplier, purchase_date, quantity_kg, reorder_level_kg, cost_per_kg, germination_rate_pct, certified, storage_location) VALUES
(1, 'Maize Seed H614D',       'H614D',          'Maize',         'Kenya Seed Company',  '2026-02-20', 15.0, 5.0, 160, 92, 1, 'Store Room A'),
(2, 'Tomato Seed Money Maker','Money Maker',     'Tomatoes',      'Simlaw Seeds',        '2026-04-15',  0.2, 0.1, 800, 88, 1, 'Store Room A'),
(3, 'Kale - Sukuma Wiki',     'Sukuma Wiki F1',  'Kale',          'Simlaw Seeds',        '2026-05-01',  0.5, 0.2, 400, 90, 0, 'Store Room A'),
(4, 'Beans Canadian Wonder',  'Canadian Wonder', 'Beans',         'Local Agro-Dealer',   '2026-03-20',  8.0, 3.0,  80, 85, 0, 'Store Room B'),
(5, 'Sweet Potato Vines',     'Kenya Beauregard','Sweet Potato',  'KARI Kisumu',         '2026-05-15',  0.0, 2.0,  30,  0, 1, 'None - depleted'),
(6, 'Onion Seed Red Creole',  'Red Creole',      'Onions',        'Simlaw Seeds',        '2026-06-10',  0.3, 0.1, 600, 82, 1, 'Store Room A');

-- Post-Harvest Records
INSERT IGNORE INTO post_harvest_records (id, planting_id, field_id, record_date, activity, quantity_in, quantity_out, loss_pct, unit, destination, price_per_unit, revenue, cost) VALUES
(1, 3, 3, '2026-07-22', 'grading',   4800, 4600, 4.2, 'kg', 'Market Kakamega', 45, 207000,  2000),
(2, 3, 3, '2026-07-22', 'transport', 4600, 4600, 0.0, 'kg', 'Market Kakamega', 45, 207000,  4600),
(3, 4, 1, '2026-07-01', 'grading',    600,  590, 1.7, 'kg', 'Local traders',   80,  47200,   500);

-- Soil Tests
INSERT IGNORE INTO soil_tests (id, field_id, test_date, lab_name, ph, nitrogen_ppm, phosphorus_ppm, potassium_ppm, organic_matter_pct, texture, recommendation, cost) VALUES
(1, 1, '2026-03-10', 'Kenya Soil Survey Lab', 5.8, 42, 18, 210, 2.1, 'Clay loam', 'Apply 200kg lime/acre. Nitrogen adequate. Phosphorus slightly low — apply TSP.', 2500),
(2, 3, '2026-02-28', 'Kenya Soil Survey Lab', 6.2, 38, 22, 195, 1.8, 'Sandy loam', 'pH good. Apply FYM to improve organic matter. Use CAN for N top-up.',              2500);

-- Soil Amendments
INSERT IGNORE INTO soil_amendments (id, field_id, amendment_date, amendment_type, product_name, quantity_kg, application_method, cost, purpose) VALUES
(1, 1, '2026-03-20', 'lime',    'Agricultural Lime',     700,  'Broadcast and incorporated', 2800, 'Raise pH from 5.8 to 6.5'),
(2, 3, '2026-03-05', 'manure',  'Farm Yard Manure',     4000,  'Plough in',                  1600, 'Improve organic matter and soil structure'),
(3, 2, '2026-04-01', 'compost', 'Own Farm Compost',     1000,  'Top dress and mulch',         400, 'Improve moisture retention on hillside');
