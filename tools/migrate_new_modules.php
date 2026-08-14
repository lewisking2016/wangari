<?php
/**
 * Wangari Module Expansion Migration
 * Adds tables for the modules farmers asked for (research-backed):
 *   - Crops & Fields (planting, activities, harvest, cost per acre)
 *   - CRM (segments, follow-ups, contact history)
 *   - Labour / Workers (workers, attendance, wages)
 *   - Smart Reminders + Weather alerts
 *   - AI settings (API key storage, assistant logs)
 * Safe to run multiple times (CREATE TABLE IF NOT EXISTS).
 */
declare(strict_types=1);
$isCli = (php_sapi_name() === 'cli');
if (!$isCli) { http_response_code(403); exit('CLI only'); }

require __DIR__ . '/../Backend/config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) { fwrite(STDERR, "DB connection failed\n"); exit(1); }

$sql = [];

// ── Crops & Fields ──────────────────────────────────────────────
$sql[] = "CREATE TABLE IF NOT EXISTS fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    location VARCHAR(200) DEFAULT '',
    size_acres DECIMAL(8,2) DEFAULT 0.00,
    soil_type VARCHAR(100) DEFAULT '',
    status ENUM('active','fallow','leased_out') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$sql[] = "CREATE TABLE IF NOT EXISTS crop_plantings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    field_id INT NOT NULL,
    crop VARCHAR(120) NOT NULL,
    variety VARCHAR(120) DEFAULT '',
    planting_date DATE NOT NULL,
    area_acres DECIMAL(8,2) DEFAULT 0.00,
    expected_harvest_date DATE NULL,
    expected_yield DECIMAL(10,2) DEFAULT 0.00,
    yield_unit VARCHAR(40) DEFAULT 'kg',
    status ENUM('growing','harvested','failed') DEFAULT 'growing',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_field (field_id),
    CONSTRAINT fk_planting_field FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$sql[] = "CREATE TABLE IF NOT EXISTS crop_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    planting_id INT NOT NULL,
    activity_type VARCHAR(60) NOT NULL,
    activity_date DATE NOT NULL,
    cost DECIMAL(12,2) DEFAULT 0.00,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_planting (planting_id),
    CONSTRAINT fk_activity_planting FOREIGN KEY (planting_id) REFERENCES crop_plantings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$sql[] = "CREATE TABLE IF NOT EXISTS crop_harvests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    planting_id INT NOT NULL,
    harvest_date DATE NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    unit VARCHAR(40) DEFAULT 'kg',
    price_per_unit DECIMAL(12,2) DEFAULT 0.00,
    revenue DECIMAL(14,2) DEFAULT 0.00,
    buyer VARCHAR(150) DEFAULT '',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_planting (planting_id),
    CONSTRAINT fk_harvest_planting FOREIGN KEY (planting_id) REFERENCES crop_plantings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// ── CRM ─────────────────────────────────────────────────────────
$sql[] = "CREATE TABLE IF NOT EXISTS crm_segments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$sql[] = "CREATE TABLE IF NOT EXISTS crm_followups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    customer_type ENUM('user','walk_in') DEFAULT 'user',
    due_date DATE NOT NULL,
    note VARCHAR(500) NOT NULL,
    status ENUM('open','done','missed') DEFAULT 'open',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_due (due_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$sql[] = "CREATE TABLE IF NOT EXISTS crm_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    customer_type ENUM('user','walk_in') DEFAULT 'user',
    contact_type VARCHAR(60) DEFAULT 'note',
    note VARCHAR(500) NOT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_customer (customer_id, customer_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// ── Labour / Workers ────────────────────────────────────────────
$sql[] = "CREATE TABLE IF NOT EXISTS workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) DEFAULT '',
    role VARCHAR(120) DEFAULT '',
    wage_type ENUM('daily','piecework','monthly') DEFAULT 'daily',
    wage_rate DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('active','inactive') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$sql[] = "CREATE TABLE IF NOT EXISTS worker_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    work_date DATE NOT NULL,
    hours_worked DECIMAL(5,2) DEFAULT 0.00,
    task VARCHAR(255) DEFAULT '',
    location VARCHAR(150) DEFAULT '',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_worker_date (worker_id, work_date),
    CONSTRAINT fk_att_worker FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$sql[] = "CREATE TABLE IF NOT EXISTS worker_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    period_start DATE NULL,
    period_end DATE NULL,
    method VARCHAR(60) DEFAULT 'cash',
    notes TEXT,
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_worker (worker_id),
    CONSTRAINT fk_pay_worker FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// ── Smart Reminders + Weather ───────────────────────────────────
$sql[] = "CREATE TABLE IF NOT EXISTS reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    remind_at DATETIME NOT NULL,
    channel ENUM('email','whatsapp','sms','app') DEFAULT 'app',
    target VARCHAR(200) DEFAULT '',
    status ENUM('pending','sent','done','dismissed') DEFAULT 'pending',
    related_type VARCHAR(60) DEFAULT '',
    related_id INT DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_remind (remind_at, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$sql[] = "CREATE TABLE IF NOT EXISTS weather_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_type VARCHAR(60) DEFAULT 'weather',
    title VARCHAR(200) NOT NULL,
    description TEXT,
    alert_date DATE NOT NULL,
    status ENUM('active','resolved') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_date (alert_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// ── AI Assistant ────────────────────────────────────────────────
$sql[] = "CREATE TABLE IF NOT EXISTS ai_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$sql[] = "CREATE TABLE IF NOT EXISTS ai_chat_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    question TEXT NOT NULL,
    answer TEXT,
    mode ENUM('local','llm') DEFAULT 'local',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$ok = 0;
foreach ($sql as $s) {
    try {
        $pdo->exec($s);
        $ok++;
    } catch (Exception $e) {
        fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n  SQL: " . substr($s, 0, 90) . "...\n");
    }
}

// Seed default CRM segments
try {
    $pdo->exec("INSERT IGNORE INTO crm_segments (name, description) VALUES
        ('Wholesale', 'Bulk buyers and distributors'),
        ('Retail', 'Walk-in and small retail customers'),
        ('Credit Customer', 'Customers who buy on credit'),
        ('VIP', 'High-value repeat customers')");
} catch (Exception $e) { /* ignore */ }

echo "Migrations applied: {$ok}/" . count($sql) . "\n";
echo "Done. New module tables are ready.\n";
