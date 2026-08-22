<?php
/**
 * Auto-Migration — ensures all module tables exist on every connection.
 *
 * Safe to call repeatedly: CREATE TABLE IF NOT EXISTS makes re-runs no-ops,
 * and individual statement errors are skipped. The completeness guard below
 * re-runs a migration file whenever any of its tables is missing, so tables
 * added to the migration files later are created automatically.
 */
declare(strict_types=1);

/**
 * Split a migration .sql file into individual executable statements.
 *
 * Comment banner lines ("-- ...") are stripped BEFORE splitting. The old
 * approach split on ";" and then dropped chunks that STARTED with a comment —
 * which silently discarded every CREATE TABLE that followed a banner, leaving
 * tables like broiler_weighings / egg_losses permanently missing.
 */
function splitMigrationSql(string $sql): array
{
    // Remove full-line SQL comments (these files use "-- ..." banners)
    $lines = preg_split('/\R/', $sql);
    if ($lines !== false) {
        $lines = array_filter($lines, function (string $line): bool {
            $trimmed = ltrim($line);
            return $trimmed !== '' && !str_starts_with($trimmed, '--');
        });
        $sql = implode("\n", $lines);
    }

    // Strip any USE statement (we are already connected to the right DB)
    $sql = preg_replace('/USE\s+`?\w+`?\s*;/i', '', $sql);

    $statements = [];
    foreach (array_map('trim', explode(';', $sql)) as $stmt) {
        if ($stmt !== '') {
            $statements[] = $stmt;
        }
    }
    return $statements;
}

/**
 * Execute a migration file, skipping statements that fail (idempotent).
 */
function runMigrationFile(PDO $pdo, string $file): void
{
    $sql = @file_get_contents($file);
    if ($sql === false) {
        return;
    }
    foreach (splitMigrationSql($sql) as $stmt) {
        try {
            $translated = translateMySQLToSQLite($stmt);
            $pdo->exec($translated);
        } catch (Exception $e) {
            // Ignore — already applied, or a transient FK ordering issue.
            // The completeness guard below re-runs on the next request.
        }
    }
}

/**
 * Table names created by a migration file, derived from the file itself.
 */
function migrationTableNames(string $file): array
{
    $sql = @file_get_contents($file);
    if ($sql === false) {
        return [];
    }
    preg_match_all('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?(\w+)`?/i', $sql, $m);
    return array_values(array_unique($m[1] ?? []));
}

/**
 * Reconcile legacy column shapes with the schema the code expects.
 *
 * The raw_materials / suppliers tables in older databases use an older shape
 * (name, stock_tons, current_price_per_ton, feed_type) while the migration
 * files and every current module read (material_name, current_stock,
 * current_price_per_unit, unit, category) and (supplier_name). Adding columns
 * and back-filling from the legacy ones lets both old and new code read the
 * table, without touching or dropping any existing data. Idempotent.
 */
function reconcileLegacySchema(PDO $pdo): void
{
    // ── raw_materials ──
    if (tableExists($pdo, 'raw_materials')) {
        $add = [];
        if (!columnExists($pdo, 'raw_materials', 'material_name'))   $add[] = 'ADD COLUMN material_name VARCHAR(100) NULL AFTER id';
        if (!columnExists($pdo, 'raw_materials', 'material_code'))   $add[] = 'ADD COLUMN material_code VARCHAR(50) NULL';
        if (!columnExists($pdo, 'raw_materials', 'unit'))            $add[] = "ADD COLUMN unit VARCHAR(20) NOT NULL DEFAULT 'kg'";
        if (!columnExists($pdo, 'raw_materials', 'opening_balance')) $add[] = 'ADD COLUMN opening_balance DECIMAL(12,3) NOT NULL DEFAULT 0';
        if (!columnExists($pdo, 'raw_materials', 'current_stock'))   $add[] = 'ADD COLUMN current_stock DECIMAL(12,3) NOT NULL DEFAULT 0';
        if (!columnExists($pdo, 'raw_materials', 'current_price_per_unit')) $add[] = 'ADD COLUMN current_price_per_unit DECIMAL(10,2) NOT NULL DEFAULT 0';
        if (!columnExists($pdo, 'raw_materials', 'category'))        $add[] = "ADD COLUMN category VARCHAR(50) NOT NULL DEFAULT 'feed_ingredient'";
        if (!columnExists($pdo, 'raw_materials', 'supplier_id'))     $add[] = 'ADD COLUMN supplier_id INT NULL';
        if (!columnExists($pdo, 'raw_materials', 'notes'))           $add[] = 'ADD COLUMN notes TEXT NULL';
        if ($add) {
            $pdo->exec('ALTER TABLE raw_materials ' . implode(', ', $add));
        }

        // Back-fill from the legacy columns when they exist. The legacy stock
        // is stored in TONS (old code converts kg -> tons), the current schema
        // is in KG — so convert 1:1000 and price per ton -> per kg.
        if (columnExists($pdo, 'raw_materials', 'name')) {
            $pdo->exec("UPDATE raw_materials SET material_name = name WHERE material_name IS NULL OR material_name = ''");
            if (columnExists($pdo, 'raw_materials', 'stock_tons')) {
                $pdo->exec('UPDATE raw_materials SET current_stock = stock_tons * 1000, opening_balance = stock_tons * 1000 WHERE stock_tons IS NOT NULL AND (current_stock = 0 OR current_stock IS NULL)');
            }
            if (columnExists($pdo, 'raw_materials', 'current_price_per_ton')) {
                $pdo->exec('UPDATE raw_materials SET current_price_per_unit = current_price_per_ton / 1000 WHERE current_price_per_ton IS NOT NULL AND (current_price_per_unit = 0 OR current_price_per_unit IS NULL)');
            }
        }
    }

    // ── suppliers ──
    if (tableExists($pdo, 'suppliers')
        && !columnExists($pdo, 'suppliers', 'supplier_name')
        && columnExists($pdo, 'suppliers', 'name')) {
        $pdo->exec('ALTER TABLE suppliers ADD COLUMN supplier_name VARCHAR(150) NULL AFTER id');
        $pdo->exec("UPDATE suppliers SET supplier_name = name WHERE supplier_name IS NULL OR supplier_name = ''");
    }

    // ── financial_records (expenses) ──
    if (tableExists($pdo, 'financial_records')) {
        $add = [];
        if (!columnExists($pdo, 'financial_records', 'payment_method')) {
            $add[] = "ADD COLUMN payment_method VARCHAR(50) DEFAULT 'cash'";
        }
        if (!columnExists($pdo, 'financial_records', 'payment_status')) {
            $add[] = "ADD COLUMN payment_status ENUM('Pending','Approved','Failed','Completed') DEFAULT 'Completed'";
        }
        if ($add) {
            $pdo->exec('ALTER TABLE financial_records ' . implode(', ', $add));
        }
    }

    // ── egg_losses — add a "stage" column so broken eggs can be attributed
    //    to where they were damaged: during collection or on route (transport).
    if (tableExists($pdo, 'egg_losses') && !columnExists($pdo, 'egg_losses', 'stage')) {
        $pdo->exec("ALTER TABLE egg_losses ADD COLUMN stage ENUM('collection','transport','storage','other') NOT NULL DEFAULT 'collection' AFTER loss_type");
    }

    // ── users.role — older databases have an ENUM without 'sales_staff',
    //    so assigning that role silently stores an empty string. Widen it.
    if (tableExists($pdo, 'users')) {
        $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
        if ($col && str_contains($col['Type'] ?? '', 'enum') && !str_contains($col['Type'], 'sales_staff')) {
            $pdo->exec("ALTER TABLE users MODIFY role ENUM('super_admin','farm_manager','stock_manager','sales_staff','customer') NULL DEFAULT 'customer'");
        }
        
        $add = [];
        if (!columnExists($pdo, 'users', 'google_id')) {
            $add[] = 'ADD COLUMN google_id VARCHAR(100) NULL UNIQUE';
        }
        if (!columnExists($pdo, 'users', 'profile_pic')) {
            $add[] = 'ADD COLUMN profile_pic VARCHAR(255) NULL';
        }
        if ($add) {
            $pdo->exec('ALTER TABLE users ' . implode(', ', $add));
        }
    }

    // Desktop app license records. These back the one-install activation flow.
    if (!tableExists($pdo, 'wangari_licenses')) {
        $pdo->exec("
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
            ) ENGINE=InnoDB
        ");
    } elseif (!columnExists($pdo, 'wangari_licenses', 'user_id')) {
        $pdo->exec("ALTER TABLE wangari_licenses ADD COLUMN user_id INT DEFAULT NULL AFTER license_key");
        try {
            $pdo->exec("ALTER TABLE wangari_licenses ADD INDEX idx_lic_user (user_id)");
        } catch (Exception $e) {
            // Index may already exist on an older migration path.
        }
    }

    if (tableExists($pdo, 'wangari_licenses') && tableExists($pdo, 'platform_users')) {
        $pdo->exec("
            UPDATE wangari_licenses l
            JOIN platform_users u ON u.email = l.customer_email
            SET l.user_id = u.id
            WHERE l.user_id IS NULL
              AND l.customer_email IS NOT NULL
              AND l.customer_email <> ''
        ");
    }
}

/**
 * List of every admin module key (used by role permissions and the sidebar).
 * Keep in sync with admin_sidebar.php sub-module keys.
 */
function wangariModuleKeys(): array
{
    return [
        'dashboard',
        // Poultry Operations
        'flocks', 'production', 'vaccinations', 'batches', 'health', 'broiler', 'hatchery', 'feeding', 'losses',
        // Inventory & Stores
        'products', 'stores', 'feed_production', 'egg_grading',
        // Sales & Finance
        'hub_finance', 'profit', 'cashbook', 'credit', 'purchase_orders', 'daily_sales', 'bulk_sales', 'lpo',
        // Reports & Tools
        'analytics', 'bulk_import_export',
        // Team & Messages
        'staff', 'users', 'tasks', 'messages',
        // Settings
        'calendar', 'dropdowns', 'settings', 'logs', 'permissions',
    ];
}

/**
 * Map an admin script name to its module key ('' when unknown).
 */
function wangariModuleKeyForScript(string $script): string
{
    $map = [
        'dashboard.php' => 'dashboard',
        'hub_operations.php' => 'flocks', 'flocks.php' => 'flocks', 'flocks_tab.php' => 'flocks',
        'production.php' => 'production', 'vaccinations.php' => 'vaccinations',
        'batches.php' => 'batches', 'health.php' => 'health', 'broiler.php' => 'broiler',
        'hatchery.php' => 'hatchery', 'feeding.php' => 'feeding', 'extras.php' => 'losses',
        'hub_inventory.php' => 'products', 'products.php' => 'products',
        'stores.php' => 'stores', 'feed_production.php' => 'feed_production',
        'egg_grading.php' => 'egg_grading',
        'hub_finance.php' => 'hub_finance', 'profit.php' => 'profit', 'cashbook.php' => 'cashbook',
        'credit.php' => 'credit', 'purchase_orders.php' => 'purchase_orders',
        'daily_sales.php' => 'daily_sales', 'bulk_sales.php' => 'bulk_sales', 'lpo.php' => 'lpo',
        'analytics.php' => 'analytics', 'bulk_import_export.php' => 'bulk_import_export',
        'hub_people.php' => 'staff', 'staff.php' => 'staff', 'users.php' => 'users',
        'tasks.php' => 'tasks', 'messages.php' => 'messages',
        'hub_settings.php' => 'settings', 'calendar.php' => 'calendar', 'dropdowns.php' => 'dropdowns',
        'settings.php' => 'settings', 'logs.php' => 'logs', 'permissions.php' => 'permissions',
        'orders.php' => 'hub_finance', 'sales.php' => 'hub_finance', 'payments.php' => 'hub_finance',
        'expenses.php' => 'hub_finance', 'reports.php' => 'analytics', 'operations.php' => 'flocks',
    ];
    return $map[$script] ?? '';
}

/**
 * Default permission grants per role. super_admin and farm_manager get full
 * access; limited roles get their own module sets. 'customer' gets nothing.
 */
function wangariDefaultRolePermissions(): array
{
    $all = wangariModuleKeys();
    $perms = [];
    foreach ($all as $m) {
        $perms['super_admin'][$m] = ['view' => 1, 'edit' => 1];
        $perms['farm_manager'][$m] = ['view' => 1, 'edit' => 1];
    }

    $stock = ['products', 'stores', 'feed_production', 'egg_grading', 'batches', 'losses'];
    $sales = ['hub_finance', 'profit', 'cashbook', 'credit', 'daily_sales', 'bulk_sales', 'lpo', 'purchase_orders'];
    foreach ($all as $m) {
        $perms['stock_manager'][$m] = in_array($m, $stock, true) ? ['view' => 1, 'edit' => 1] : ['view' => 0, 'edit' => 0];
        $perms['sales_staff'][$m] = in_array($m, $sales, true) ? ['view' => 1, 'edit' => 1] : ['view' => 0, 'edit' => 0];
    }
    // Everyone can always open the dashboard.
    $perms['stock_manager']['dashboard'] = ['view' => 1, 'edit' => 1];
    $perms['sales_staff']['dashboard'] = ['view' => 1, 'edit' => 1];
    foreach ($all as $m) {
        $perms['customer'][$m] = ['view' => 0, 'edit' => 0];
    }
    return $perms;
}

/**
 * Load the role_permissions matrix for every role.
 * Returns ['role' => ['module' => ['view'=>bool,'edit'=>bool]]]
 */
function wangariRolePermissions(?PDO $pdo = null): array
{
    static $cache = null;
    if ($cache !== null) return $cache;
    if ($pdo === null) $pdo = getDatabaseConnection();
    $cache = wangariDefaultRolePermissions();
    if (!$pdo) return $cache;
    try {
        if (!tableExists($pdo, 'role_permissions')) return $cache;
        $rows = $pdo->query('SELECT role, module_key, can_view, can_edit FROM role_permissions')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            if (!isset($cache[$r['role']])) continue;
            if (!isset($cache[$r['role']][$r['module_key']])) continue;
            $cache[$r['role']][$r['module_key']] = ['view' => (int)$r['can_view'], 'edit' => (int)$r['can_edit']];
        }
    } catch (Exception $e) {
        // Table missing — fall back to defaults
    }
    return $cache;
}

/**
 * Idempotent master-data seeding: egg grades (incl. Small/Medium/Large),
 * chicken sizes, user-role dropdown entries and the role_permissions matrix.
 * Runs on every connection but is a handful of INSERT IGNOREs — cheap.
 */
function seedMasterData(PDO $pdo): void
{
    // ── Egg grades: add the standard market sizes alongside B14/B15/Cracked ──
    if (tableExists($pdo, 'egg_grades')) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO egg_grades (grade_code, grade_name, weight_min_grams, weight_max_grams, pieces_per_crate, description, is_active) VALUES (?,?,?,?,?,?,1)');
        $grades = [
            ['PW', 'Peewee', 1, 41, 30, 'Very small eggs (< 42g)'],
            ['S',  'Small', 42, 49, 30, 'Small sized eggs'],
            ['M',  'Medium', 50, 55, 30, 'Medium sized eggs'],
            ['L',  'Large', 56, 64, 30, 'Large sized eggs'],
            ['J',  'Jumbo', 71, 999, 30, 'Jumbo sized eggs (> 70g)'],
        ];
        foreach ($grades as $g) {
            $stmt->execute($g);
        }
    }

    // ── Chicken sizes dropdown group (for product / sale size options) ──
    if (tableExists($pdo, 'system_dropdowns')) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO system_dropdowns (group_key, group_label, option_value, option_label, sort_order, is_active, is_system) VALUES (?,?,?,?,?,1,1)');
        $sizes = [
            ['chicken_sizes', 'Chicken Sizes', 'peewee', 'Peewee', 1],
            ['chicken_sizes', 'Chicken Sizes', 'small', 'Small', 2],
            ['chicken_sizes', 'Chicken Sizes', 'medium', 'Medium', 3],
            ['chicken_sizes', 'Chicken Sizes', 'large', 'Large', 4],
            ['chicken_sizes', 'Chicken Sizes', 'extra_large', 'Extra Large', 5],
            ['chicken_sizes', 'Chicken Sizes', 'jumbo', 'Jumbo', 6],
            // User roles used by the code (older seed only had 'admin')
            ['user_roles', 'User Roles', 'super_admin', 'Super Admin', 0],
            ['user_roles', 'User Roles', 'farm_manager', 'Farm Manager', 1],
            ['user_roles', 'User Roles', 'sales_staff', 'Sales Staff', 4],
        ];
        foreach ($sizes as $s) {
            $stmt->execute($s);
        }
    }

    // ── Role permissions matrix (idempotent) ──
    if (tableExists($pdo, 'role_permissions')) {
        $defaults = wangariDefaultRolePermissions();
        $stmt = $pdo->prepare('INSERT IGNORE INTO role_permissions (role, module_key, can_view, can_edit) VALUES (?,?,?,?)');
        foreach ($defaults as $role => $mods) {
            foreach ($mods as $mod => $p) {
                $stmt->execute([$role, $mod, $p['view'], $p['edit']]);
            }
        }
    }
}

/**
 * Ops V2: Species-agnostic Farm Operations schema.
 *
 * Adds animal_groups (unified flocks/herds), vaccine_guides, feed_logs,
 * and species columns on existing tables so every species (chicken, cattle,
 * goat, sheep, pig, rabbit …) gets the same treatment.
 */
function reconcileOpsV2Schema(PDO $pdo): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    // ── 1. animal_groups — unified flocks + herds ──
    if (!tableExists($pdo, 'animal_groups')) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS animal_groups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                species VARCHAR(80) NOT NULL DEFAULT 'Chicken',
                group_type VARCHAR(50) NOT NULL DEFAULT 'flock',
                breed VARCHAR(100),
                head_count INT NOT NULL DEFAULT 0,
                housing_id INT NULL,
                location VARCHAR(200),
                status ENUM('active','sold','archived') NOT NULL DEFAULT 'active',
                source VARCHAR(50) DEFAULT 'manual',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_ag_species (species),
                INDEX idx_ag_status (status)
            ) ENGINE=InnoDB
        ");
    }

    // ── 2. vaccine_guides — per-species vaccine schedules ──
    if (!tableExists($pdo, 'vaccine_guides')) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS vaccine_guides (
                id INT AUTO_INCREMENT PRIMARY KEY,
                species VARCHAR(80) NOT NULL,
                disease VARCHAR(150) NOT NULL,
                vaccine_name VARCHAR(150) NOT NULL,
                age_or_timing VARCHAR(120) NOT NULL,
                route VARCHAR(60) DEFAULT 'injection',
                dose VARCHAR(100),
                frequency VARCHAR(120),
                notes TEXT,
                sort_order INT DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_vg_species (species)
            ) ENGINE=InnoDB
        ");
    }

    // ── 3. feed_logs — daily feeding records per group/animal ──
    if (!tableExists($pdo, 'feed_logs')) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS feed_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                record_date DATE NOT NULL,
                group_id INT NULL,
                animal_id INT NULL,
                species VARCHAR(80) NOT NULL DEFAULT 'Chicken',
                feed_type VARCHAR(100),
                quantity_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
                cost DECIMAL(10,2) DEFAULT 0,
                notes TEXT,
                recorded_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_fl_date (record_date),
                INDEX idx_fl_species (species)
            ) ENGINE=InnoDB
        ");
    }

    // ── 3b. animals — add group_id (points at animal_groups; herd_id is a
    //    legacy FK to the old herds table) ──
    if (tableExists($pdo, 'animals')) {
        if (!columnExists($pdo, 'animals', 'group_id')) {
            try { $pdo->exec('ALTER TABLE animals ADD COLUMN group_id INT NULL AFTER herd_id'); } catch (Exception $e) {}
        }
    }

    // ── 4. houses — add species + house_type + current_occupants ──
    if (tableExists($pdo, 'houses')) {
        $add = [];
        if (!columnExists($pdo, 'houses', 'species'))          $add[] = "ADD COLUMN species VARCHAR(80) NOT NULL DEFAULT 'Chicken'";
        if (!columnExists($pdo, 'houses', 'house_type'))       $add[] = "ADD COLUMN house_type VARCHAR(50) NOT NULL DEFAULT 'house'";
        if (!columnExists($pdo, 'houses', 'current_occupants'))$add[] = 'ADD COLUMN current_occupants INT NOT NULL DEFAULT 0';
        if ($add) { try { $pdo->exec('ALTER TABLE houses ' . implode(', ', $add)); } catch (Exception $e) {} }
    }

    // ── 5. vaccinations — add animal_id, group_id, species, dosage, cost, notes ──
    if (tableExists($pdo, 'vaccinations')) {
        $add = [];
        if (!columnExists($pdo, 'vaccinations', 'animal_id')) $add[] = 'ADD COLUMN animal_id INT NULL';
        if (!columnExists($pdo, 'vaccinations', 'group_id'))  $add[] = 'ADD COLUMN group_id INT NULL';
        if (!columnExists($pdo, 'vaccinations', 'species'))   $add[] = "ADD COLUMN species VARCHAR(80) NOT NULL DEFAULT 'Chicken'";
        if (!columnExists($pdo, 'vaccinations', 'dosage'))    $add[] = 'ADD COLUMN dosage VARCHAR(100) NULL';
        if (!columnExists($pdo, 'vaccinations', 'cost'))      $add[] = 'ADD COLUMN cost DECIMAL(10,2) DEFAULT 0';
        if (!columnExists($pdo, 'vaccinations', 'notes'))     $add[] = 'ADD COLUMN notes TEXT NULL';
        if (!columnExists($pdo, 'vaccinations', 'next_due_date'))$add[] = 'ADD COLUMN next_due_date DATE NULL';
        if ($add) { try { $pdo->exec('ALTER TABLE vaccinations ' . implode(', ', $add)); } catch (Exception $e) {} }
        // Make flock_id nullable for new module (existing rows keep their FK)
        try { $pdo->exec('ALTER TABLE vaccinations MODIFY flock_id INT NULL'); } catch (Exception $e) {}
    }

    // ── 6. production_records — add group_id, species, milk_litres, weight_kg, sold_count ──
    if (tableExists($pdo, 'production_records')) {
        $add = [];
        if (!columnExists($pdo, 'production_records', 'group_id'))   $add[] = 'ADD COLUMN group_id INT NULL';
        if (!columnExists($pdo, 'production_records', 'species'))    $add[] = "ADD COLUMN species VARCHAR(80) NOT NULL DEFAULT 'Chicken'";
        if (!columnExists($pdo, 'production_records', 'milk_litres'))$add[] = 'ADD COLUMN milk_litres DECIMAL(8,2) DEFAULT 0';
        if (!columnExists($pdo, 'production_records', 'weight_kg'))  $add[] = 'ADD COLUMN weight_kg DECIMAL(8,2) DEFAULT 0';
        if (!columnExists($pdo, 'production_records', 'sold_count')) $add[] = 'ADD COLUMN sold_count INT DEFAULT 0';
        if ($add) { try { $pdo->exec('ALTER TABLE production_records ' . implode(', ', $add)); } catch (Exception $e) {} }
        try { $pdo->exec('ALTER TABLE production_records MODIFY flock_id INT NULL'); } catch (Exception $e) {}
    }

    // ── 7. health_records — reconcile to rich shape (old DBs have a legacy
    //    subject/type/product/date schema; the code expects record_date /
    //    record_type / product_name / vet_name / cost / next_due_date) ──
    if (tableExists($pdo, 'health_records')) {
        $add = [];
        if (!columnExists($pdo, 'health_records', 'animal_id'))    $add[] = 'ADD COLUMN animal_id INT NULL';
        if (!columnExists($pdo, 'health_records', 'group_id'))     $add[] = 'ADD COLUMN group_id INT NULL';
        if (!columnExists($pdo, 'health_records', 'species'))      $add[] = "ADD COLUMN species VARCHAR(80) NOT NULL DEFAULT 'Chicken'";
        if (!columnExists($pdo, 'health_records', 'record_date'))  $add[] = 'ADD COLUMN record_date DATE NULL';
        if (!columnExists($pdo, 'health_records', 'record_type'))  $add[] = "ADD COLUMN record_type VARCHAR(50) NOT NULL DEFAULT 'treatment'";
        if (!columnExists($pdo, 'health_records', 'vaccine_name')) $add[] = 'ADD COLUMN vaccine_name VARCHAR(150) NULL';
        if (!columnExists($pdo, 'health_records', 'product_name')) $add[] = 'ADD COLUMN product_name VARCHAR(150) NULL';
        if (!columnExists($pdo, 'health_records', 'dosage'))       $add[] = 'ADD COLUMN dosage VARCHAR(100) NULL';
        if (!columnExists($pdo, 'health_records', 'route'))        $add[] = "ADD COLUMN route VARCHAR(50) NOT NULL DEFAULT 'oral'";
        if (!columnExists($pdo, 'health_records', 'birds_treated'))$add[] = 'ADD COLUMN birds_treated INT NOT NULL DEFAULT 0';
        if (!columnExists($pdo, 'health_records', 'mortality_count'))$add[] = 'ADD COLUMN mortality_count INT NOT NULL DEFAULT 0';
        if (!columnExists($pdo, 'health_records', 'vet_name'))     $add[] = 'ADD COLUMN vet_name VARCHAR(150) NULL';
        if (!columnExists($pdo, 'health_records', 'next_due_date'))$add[] = 'ADD COLUMN next_due_date DATE NULL';
        if (!columnExists($pdo, 'health_records', 'cost'))         $add[] = 'ADD COLUMN cost DECIMAL(10,2) NOT NULL DEFAULT 0';
        if (!columnExists($pdo, 'health_records', 'recorded_by'))  $add[] = 'ADD COLUMN recorded_by INT NULL';
        if ($add) { try { $pdo->exec('ALTER TABLE health_records ' . implode(', ', $add)); } catch (Exception $e) {} }

        // Backfill rich columns from legacy columns (date→record_date,
        // type→record_type, product→product_name, next_date→next_due_date)
        try {
            if (columnExists($pdo, 'health_records', 'date')) {
                $pdo->exec("UPDATE health_records SET record_date = date WHERE record_date IS NULL AND date IS NOT NULL");
            }
            if (columnExists($pdo, 'health_records', 'type')) {
                $pdo->exec("UPDATE health_records SET record_type = type WHERE (record_type = 'treatment' OR record_type = '') AND type IS NOT NULL AND type != ''");
            }
            if (columnExists($pdo, 'health_records', 'product')) {
                $pdo->exec("UPDATE health_records SET product_name = product WHERE (product_name IS NULL OR product_name = '') AND product IS NOT NULL AND product != ''");
            }
            if (columnExists($pdo, 'health_records', 'next_date')) {
                $pdo->exec("UPDATE health_records SET next_due_date = next_date WHERE next_due_date IS NULL AND next_date IS NOT NULL");
            }
        } catch (Exception $e) {}
    }

    // ── 8. breeding_records — add species, dam_id, sire_id, offspring_count, result ──
    if (tableExists($pdo, 'breeding_records')) {
        $add = [];
        if (!columnExists($pdo, 'breeding_records', 'species'))         $add[] = "ADD COLUMN species VARCHAR(80) NOT NULL DEFAULT 'Chicken'";
        if (!columnExists($pdo, 'breeding_records', 'dam_id'))          $add[] = 'ADD COLUMN dam_id INT NULL';
        if (!columnExists($pdo, 'breeding_records', 'sire_id'))         $add[] = 'ADD COLUMN sire_id INT NULL';
        if (!columnExists($pdo, 'breeding_records', 'offspring_count')) $add[] = 'ADD COLUMN offspring_count INT DEFAULT 0';
        if (!columnExists($pdo, 'breeding_records', 'result'))          $add[] = "ADD COLUMN result VARCHAR(50) DEFAULT 'Pending'";
        if ($add) { try { $pdo->exec('ALTER TABLE breeding_records ' . implode(', ', $add)); } catch (Exception $e) {} }
    }

    // ── 9. feeding_standards — add species column + widen bird_type ──
    if (tableExists($pdo, 'feeding_standards')) {
        if (!columnExists($pdo, 'feeding_standards', 'species')) {
            try {
                $pdo->exec("ALTER TABLE feeding_standards ADD COLUMN species VARCHAR(80) NOT NULL DEFAULT 'Chicken' AFTER bird_type");
            } catch (Exception $e) {}
        }
        // bird_type was ENUM('layer','broiler','kienyeji','dual_purpose'); widen to VARCHAR for all species
        try {
            $col = $pdo->query("SHOW COLUMNS FROM feeding_standards LIKE 'bird_type'")->fetch(PDO::FETCH_ASSOC);
            if ($col && str_contains($col['Type'] ?? '', 'enum')) {
                $pdo->exec("ALTER TABLE feeding_standards MODIFY bird_type VARCHAR(80) NOT NULL");
            }
        } catch (Exception $e) {}
    }

    // ── 10. Migrate existing flocks → animal_groups (one-time) ──
    if (tableExists($pdo, 'flocks') && tableExists($pdo, 'animal_groups')) {
        $agCount = $pdo->query('SELECT COUNT(*) FROM animal_groups')->fetchColumn();
        $fkCount = $pdo->query('SELECT COUNT(*) FROM flocks')->fetchColumn();
        if ($agCount == 0 && $fkCount > 0) {
            try {
                $pdo->exec("
                    INSERT INTO animal_groups (name, species, group_type, breed, head_count, location, status, source, created_at)
                    SELECT
                        flock_name,
                        'Chicken',
                        CASE
                            WHEN LOWER(flock_name) LIKE '%broiler%' THEN 'flock'
                            WHEN LOWER(flock_name) LIKE '%layer%' THEN 'flock'
                            WHEN LOWER(flock_name) LIKE '%kienyeji%' THEN 'flock'
                            ELSE 'flock'
                        END,
                        breed,
                        current_count,
                        '',
                        status,
                        'migrated_flocks',
                        created_at
                    FROM flocks
                ");
            } catch (Exception $e) {}
        }
    }

    // ── 11. Migrate existing herds → animal_groups (one-time) ──
    if (tableExists($pdo, 'herds') && tableExists($pdo, 'animal_groups')) {
        $agCount = $pdo->query('SELECT COUNT(*) FROM animal_groups WHERE source = "migrated_herds"')->fetchColumn();
        $hdCount = $pdo->query('SELECT COUNT(*) FROM herds')->fetchColumn();
        if ($agCount == 0 && $hdCount > 0) {
            try {
                $pdo->exec("
                    INSERT INTO animal_groups (name, species, group_type, breed, head_count, location, status, source, created_at)
                    SELECT
                        name,
                        CASE
                            WHEN LOWER(name) LIKE '%goat%' OR LOWER(species) LIKE '%goat%' THEN 'Goat'
                            WHEN LOWER(name) LIKE '%sheep%' OR LOWER(species) LIKE '%sheep%' THEN 'Sheep'
                            WHEN LOWER(name) LIKE '%pig%' OR LOWER(species) LIKE '%pig%' THEN 'Pig'
                            WHEN LOWER(name) LIKE '%chicken%' OR LOWER(species) LIKE '%chicken%' THEN 'Chicken'
                            ELSE 'Cattle'
                        END,
                        'herd',
                        species,
                        size,
                        location,
                        LOWER(status),
                        'migrated_herds',
                        created_at
                    FROM herds
                ");
            } catch (Exception $e) {}
        }
    }
}

/**
 * Ops V3: Equipment maintenance, soil health, budgeting, weather, audit trail.
 * Adds missing tables and columns to support Farmbrite-level features.
 */
function reconcileOpsV3Schema(PDO $pdo): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    // ── 1. equipment_maintenance — preventive maintenance, service records ──
    if (!tableExists($pdo, 'equipment_maintenance')) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS equipment_maintenance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                equipment_id INT NOT NULL,
                maintenance_type ENUM('preventive','corrective','inspection','calibration') NOT NULL DEFAULT 'preventive',
                title VARCHAR(200) NOT NULL,
                description TEXT,
                scheduled_date DATE NOT NULL,
                completed_date DATE NULL,
                status ENUM('scheduled','overdue','completed','skipped') NOT NULL DEFAULT 'scheduled',
                cost DECIMAL(10,2) DEFAULT 0,
                performed_by VARCHAR(150),
                next_due_date DATE NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_em_equip (equipment_id),
                INDEX idx_em_status (status),
                INDEX idx_em_date (scheduled_date)
            ) ENGINE=InnoDB
        ");
    }

    // ── 2. equipment_usage — track usage hours/days for depreciation ──
    if (!tableExists($pdo, 'equipment_usage')) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS equipment_usage (
                id INT AUTO_INCREMENT PRIMARY KEY,
                equipment_id INT NOT NULL,
                usage_date DATE NOT NULL,
                hours_used DECIMAL(6,2) DEFAULT 0,
                task VARCHAR(200),
                operator VARCHAR(150),
                fuel_cost DECIMAL(10,2) DEFAULT 0,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_eu_equip (equipment_id),
                INDEX idx_eu_date (usage_date)
            ) ENGINE=InnoDB
        ");
    }

    // ── 3. soil_tests — soil health tracking per field ──
    if (!tableExists($pdo, 'soil_tests')) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS soil_tests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                field_id INT NULL,
                test_date DATE NOT NULL,
                ph_level DECIMAL(4,2),
                nitrogen_ppm DECIMAL(8,2),
                phosphorus_ppm DECIMAL(8,2),
                potassium_ppm DECIMAL(8,2),
                organic_matter_pct DECIMAL(5,2),
                moisture_pct DECIMAL(5,2),
                electrical_conductivity DECIMAL(6,3),
                texture VARCHAR(50),
                lab_name VARCHAR(150),
                recommendations TEXT,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_st_field (field_id),
                INDEX idx_st_date (test_date)
            ) ENGINE=InnoDB
        ");
    }

    // ── 4. soil_amendments — amendment applications per field ──
    if (!tableExists($pdo, 'soil_amendments')) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS soil_amendments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                field_id INT NULL,
                amendment_date DATE NOT NULL,
                amendment_type VARCHAR(100) NOT NULL,
                product_name VARCHAR(150),
                quantity_kg DECIMAL(10,2),
                application_method VARCHAR(100),
                cost DECIMAL(10,2) DEFAULT 0,
                purpose TEXT,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sa_field (field_id),
                INDEX idx_sa_date (amendment_date)
            ) ENGINE=InnoDB
        ");
    }

    // ── 5. farm_budgets — monthly/annual budgets per category ──
    if (!tableExists($pdo, 'farm_budgets')) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS farm_budgets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                budget_year INT NOT NULL,
                budget_month INT NOT NULL,
                category VARCHAR(100) NOT NULL,
                subcategory VARCHAR(100),
                planned_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                actual_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                department VARCHAR(50) DEFAULT 'general',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_budget (budget_year, budget_month, category, subcategory),
                INDEX idx_fb_year (budget_year)
            ) ENGINE=InnoDB
        ");
    }

    // ── 6. weather_cache — local weather data cache ──
    if (!tableExists($pdo, 'weather_cache')) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS weather_cache (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cache_date DATE NOT NULL,
                location VARCHAR(200) DEFAULT 'default',
                temperature_max DECIMAL(5,2),
                temperature_min DECIMAL(5,2),
                humidity INT,
                rainfall_mm DECIMAL(6,2) DEFAULT 0,
                wind_speed_kph DECIMAL(5,2),
                weather_code VARCHAR(20),
                description VARCHAR(200),
                forecast_json TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_weather (cache_date, location)
            ) ENGINE=InnoDB
        ");
    }

    // ── 7. activity_log — audit trail of all actions ──
    if (!tableExists($pdo, 'activity_log')) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS activity_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                action VARCHAR(50) NOT NULL,
                module VARCHAR(50) NOT NULL,
                description TEXT,
                record_id INT NULL,
                record_type VARCHAR(50),
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_al_user (user_id),
                INDEX idx_al_module (module),
                INDEX idx_al_date (created_at)
            ) ENGINE=InnoDB
        ");
    }

    // ── 8. Add withdrawal fields to vaccinations ──
    if (tableExists($pdo, 'vaccinations')) {
        if (!columnExists($pdo, 'vaccinations', 'withdrawal_days')) {
            try { $pdo->exec('ALTER TABLE vaccinations ADD COLUMN withdrawal_days INT NULL'); } catch (Exception $e) {}
        }
        if (!columnExists($pdo, 'vaccinations', 'withdrawal_end_date')) {
            try { $pdo->exec('ALTER TABLE vaccinations ADD COLUMN withdrawal_end_date DATE NULL'); } catch (Exception $e) {}
        }
    }

    // ── 9. Add last_service fields to farm_equipment ──
    if (tableExists($pdo, 'farm_equipment')) {
        if (!columnExists($pdo, 'farm_equipment', 'last_service_date')) {
            try { $pdo->exec('ALTER TABLE farm_equipment ADD COLUMN last_service_date DATE NULL'); } catch (Exception $e) {}
        }
        if (!columnExists($pdo, 'farm_equipment', 'next_service_date')) {
            try { $pdo->exec('ALTER TABLE farm_equipment ADD COLUMN next_service_date DATE NULL'); } catch (Exception $e) {}
        }
        if (!columnExists($pdo, 'farm_equipment', 'total_usage_hours')) {
            try { $pdo->exec('ALTER TABLE farm_equipment ADD COLUMN total_usage_hours DECIMAL(10,2) DEFAULT 0'); } catch (Exception $e) {}
        }
    }
}

/**
 * Seed vaccine guides for all supported species.
 * Idempotent — INSERT IGNORE prevents duplicates.
 */
function seedVaccineGuides(PDO $pdo): void
{
    if (!tableExists($pdo, 'vaccine_guides')) return;
    $count = $pdo->query('SELECT COUNT(*) FROM vaccine_guides')->fetchColumn();
    if ($count > 0) return;

    $stmt = $pdo->prepare('INSERT IGNORE INTO vaccine_guides (species, disease, vaccine_name, age_or_timing, route, dose, frequency, notes, sort_order) VALUES (?,?,?,?,?,?,?,?,?)');

    $guides = [
        // ── Chicken ──
        ['Chicken','Newcastle Disease','Lasota / LaSota','Day 1-3, then every 4 weeks','oral / drinking water','1 dose in 10ml water','Every 4 weeks','Administer in clean water, no chlorine',1],
        ['Chicken','Gumboro (IBD)','Gumboro Vaccine','Day 14','oral / drinking water','1 dose per bird','Once + booster at 4 weeks','Withdraw water 2hrs before',2],
        ['Chicken','Mareks Disease','Mareks Vaccine','Day 1 (at hatchery)','injection (subcutaneous)','0.2ml per chick','Once at day-old','Must be given at hatchery only',3],
        ['Chicken','Fowl Pox','Fowl Pox Vaccine','6-8 weeks','wing-web','1 dose','Once','Pox scab forms in 7-10 days',4],
        ['Chicken','Infectious Bronchitis (IB)','IB Vaccine (H120)','3 weeks','oral / drinking water','1 dose','Every 12 weeks','Mix with clean water',5],
        ['Chicken','Fowl Cholera','Fowl Cholera Bacterin','8-10 weeks','injection (IM)','0.5ml per bird','Once + annual booster','Autogenous or commercial bacterin',6],
        ['Chicken','Coccidiosis','Coccidiosis Vaccine','Day 1','oral / spray','1 dose','Once','Or via anticoccidial in feed',7],
        ['Chicken','Avian Influenza','AI Vaccine (where required)','Per government schedule','injection (IM)','0.5ml per bird','As required','Mandatory in some regions',8],

        // ── Cattle ──
        ['Cattle','Foot & Mouth Disease (FMD)','FMD Vaccine','3 months, then every 6 months','injection (SC)','2ml per animal','Every 6 months','Inject in neck; avoid milking day',1],
        ['Cattle','Lumpy Skin Disease','LSD Vaccine','3 months, then annually','injection (SC)','2ml per animal','Annually','Inject behind ear',2],
        ['Cattle','CBPP (Contagious Bovine Pleuropneumonia)','CBPP Vaccine','6 months','injection (SC)','0.5ml per animal','Once + annual booster','Subcutaneous injection',3],
        ['Cattle','Anthrax','Anthrax Spore Vaccine','6 months, then annually','injection (SC)','1ml per animal','Annually','Inject behind ear',4],
        ['Cattle','Blackleg','Blackleg Vaccine','3 months, then every 6 months','injection (SC)','2ml per animal','Every 6 months','Inject in neck',5],
        ['Cattle','Brucellosis','Brucella S19 (heifers only)','4-8 months (females only)','injection (SC)','5ml per animal','Once','Only for heifers; not for pregnant animals',6],
        ['Cattle','Rift Valley Fever','RVF Vaccine','Per outbreak/epidemic','injection (SC)','2ml per animal','As required','Seasonal in endemic areas',7],
        ['Cattle','Deworming','Albendazole / Ivermectin','Every 3 months','oral / injection','Per body weight','Every 3-4 months','Rotate dewormers to prevent resistance',8],

        // ── Goat ──
        ['Goat','PPR (Peste des Petits Ruminants)','PPR Vaccine','3 months, then annually','injection (SC)','1ml per animal','Annually','Inject behind ear; critical vaccine',1],
        ['Goat','CCPP (Contagious Caprine Pleuropneumonia)','CCPP Vaccine','3 months','injection (SC)','0.5ml per animal','Annually','Subcutaneous; avoid injection site issues',2],
        ['Goat','Anthrax','Anthrax Spore Vaccine','6 months, then annually','injection (SC)','1ml per animal','Annually','Inject behind ear',3],
        ['Goat','Enterotoxemia (Pulpy Kidney)','CDT Toxoid','3 months, then annually','injection (SC)','2ml per animal','Annually','Boost before breeding season',4],
        ['Goat','Pasteurellosis','Pasteurella Vaccine','4 months','injection (SC)','2ml per animal','Annually','Combine with deworming schedule',5],
        ['Goat','Foot & Mouth Disease','FMD Vaccine','3 months, then every 6 months','injection (SC)','2ml per animal','Every 6 months','Same vaccine as cattle',6],
        ['Goat','Deworming','Albendazole / Levamisole','Every 3 months','oral','Per body weight','Every 3 months','Monitor FAMACHA score',7],

        // ── Sheep ──
        ['Sheep','PPR (Peste des Petits Ruminants)','PPR Vaccine','3 months, then annually','injection (SC)','1ml per animal','Annually','Same vaccine as goats',1],
        ['Sheep','Anthrax','Anthrax Spore Vaccine','6 months, then annually','injection (SC)','1ml per animal','Annually','Inject behind ear',2],
        ['Sheep','Enterotoxemia (Pulpy Kidney)','CDT Toxoid','3 months, then annually','injection (SC)','2ml per animal','Annually','Critical for pregnant ewes',3],
        ['Sheep','Foot Rot','Foot Rot Vaccine','4 months','injection (SC)','2ml per animal','Annually','Combine with foot trimming',4],
        ['Sheep','Caseous Lymphadenitis (CL)','CL Vaccine','4 months','injection (SC)','1ml per animal','Annually','Inject in neck region',5],
        ['Sheep','Foot & Mouth Disease','FMD Vaccine','3 months, then every 6 months','injection (SC)','2ml per animal','Every 6 months','Same as cattle and goats',6],
        ['Sheep','Deworming','Albendazole / Ivermectin','Every 3 months','oral / injection','Per body weight','Every 3 months','FAMACHA-based decisions preferred',7],

        // ── Pig ──
        ['Pig','African Swine Fever (ASF)','No vaccine available','N/A','N/A','N/A','N/A','Prevention through biosecurity only; no cure',1],
        ['Pig','Classical Swine Fever (CSF)','CSF Vaccine','8 weeks, then every 6 months','injection (IM)','2ml per animal','Every 6 months','Inject behind ear',2],
        ['Pig','Erysipelas','Erysipelas Bacterin','8 weeks, then annually','injection (IM)','2ml per animal','Annually','Combine with mycoplasma vaccine',3],
        ['Pig','Mycoplasma Pneumonia','Mycoplasma Hyo Vaccine','3 weeks (nursery)','injection (IM)','2ml per animal','Once + booster','Pre-weaning vaccination preferred',4],
        ['Pig','Porcine Parvovirus','PPV Vaccine (gilts only)','Before first breeding','injection (IM)','2ml per animal','Once + annual booster','Only for breeding gilts/sows',5],
        ['Pig','Foot & Mouth Disease','FMD Vaccine','Per government schedule','injection (SC)','2ml per animal','Every 6 months','Where endemic',6],
        ['Pig','Deworming','Ivermectin / Fenbendazole','Every 3 months','oral / injection','Per body weight','Every 3 months','Treat all pigs in pen together',7],
    ];

    foreach ($guides as $g) {
        $stmt->execute($g);
    }
}

/**
 * Translate MySQL-specific SQL statements to be SQLite compatible.
 */
function translateMySQLToSQLite(string $stmt): string
{
    if (!defined('DB_DRIVER') || DB_DRIVER !== 'sqlite') {
        return $stmt;
    }

    // Remove ENGINE=InnoDB, character set settings, collations at the end of statements
    $stmt = preg_replace('/\) ENGINE\s*=\s*\w+.*?/i', ')', $stmt);
    
    // Remove ON UPDATE CURRENT_TIMESTAMP
    $stmt = preg_replace('/ON UPDATE CURRENT_TIMESTAMP/i', '', $stmt);
    
    // Convert ENUM(...) to TEXT
    $stmt = preg_replace('/ENUM\([^)]+\)/i', 'TEXT', $stmt);
    
    // Convert INT AUTO_INCREMENT or BIGINT AUTO_INCREMENT to INTEGER PRIMARY KEY AUTOINCREMENT
    if (preg_match('/(\w+)\s+(INT|INTEGER|BIGINT)\s+AUTO_INCREMENT\s+PRIMARY\s+KEY/i', $stmt, $m)) {
        $stmt = preg_replace('/' . preg_quote($m[0]) . '/i', $m[1] . ' INTEGER PRIMARY KEY AUTOINCREMENT', $stmt);
    } else if (preg_match('/(\w+)\s+(INT|INTEGER|BIGINT)\s+PRIMARY\s+KEY\s+AUTO_INCREMENT/i', $stmt, $m)) {
        $stmt = preg_replace('/' . preg_quote($m[0]) . '/i', $m[1] . ' INTEGER PRIMARY KEY AUTOINCREMENT', $stmt);
    }
    
    // Convert generic AUTO_INCREMENT to AUTOINCREMENT (on integer columns)
    $stmt = preg_replace('/\bAUTO_INCREMENT\b/i', 'AUTOINCREMENT', $stmt);
    
    // Convert INT to INTEGER for types
    $stmt = preg_replace('/\bINT\b/i', 'INTEGER', $stmt);

    // Remove INDEX/KEY/CONSTRAINT statements inside CREATE TABLE.
    // SQLite does not support inline indices inside CREATE TABLE.
    $lines = explode("\n", $stmt);
    $cleanedLines = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (preg_match('/^(INDEX|KEY|UNIQUE KEY|CONSTRAINT|FOREIGN KEY)\s+/i', $trimmed)) {
            continue;
        }
        $cleanedLines[] = $line;
    }
    $stmt = implode("\n", $cleanedLines);
    
    // Clean trailing commas left over by deleted INDEX/KEY definitions
    $stmt = preg_replace('/,\s*(\)\s*;?\s*)$/s', "\n$1", $stmt);
    
    return $stmt;
}

/**
 * Ensure sync_queue table exists to track changes for cloud synchronization.
 */
function ensureSyncSchema(PDO $pdo): void
{
    if (!tableExists($pdo, 'sync_queue')) {
        $isSqlite = (defined('DB_DRIVER') && DB_DRIVER === 'sqlite');
        if ($isSqlite) {
            $sql = "CREATE TABLE sync_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                table_name VARCHAR(100) NOT NULL,
                record_id INTEGER NOT NULL,
                action_type VARCHAR(20) NOT NULL,
                payload TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
        } else {
            $sql = "CREATE TABLE sync_queue (
                id INT AUTO_INCREMENT PRIMARY KEY,
                table_name VARCHAR(100) NOT NULL,
                record_id INT NOT NULL,
                action_type VARCHAR(20) NOT NULL,
                payload LONGTEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;";
        }
        try {
            $pdo->exec($sql);
        } catch (Exception $e) {
            @error_log("Failed to create sync_queue table: " . $e->getMessage());
        }
    }
}

/**
 * Ensure all module tables exist. No-op when everything is present.
 */
function ensureWangariSchema(PDO $pdo): void
{
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        ensureSyncSchema($pdo);
        reconcileLegacySchema($pdo);
        reconcileOpsV2Schema($pdo);
        reconcileOpsV3Schema($pdo);
        seedMasterData($pdo);

        $configDir = __DIR__;
        $poultryFile  = $configDir . '/migration_poultry_complete.sql';
        $businessFile = $configDir . '/migration_v2_business.sql';
        $cropsFile    = $configDir . '/migration_v5_crops.sql';

        $isSqlite = (defined('DB_DRIVER') && DB_DRIVER === 'sqlite');

        // Loop until stable: a statement can fail mid-run when its foreign
        // key target is created later in the same pass.
        for ($pass = 0; $pass < 6; $pass++) {
            if ($isSqlite) {
                $existing = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN) ?: [];
            } else {
                $existing = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: [];
            }

            $missingPoultry  = array_diff(migrationTableNames($poultryFile),  $existing);
            $missingBusiness = array_diff(migrationTableNames($businessFile), $existing);
            $missingCrops    = array_diff(migrationTableNames($cropsFile),    $existing);

            if (!$missingPoultry && !$missingBusiness && !$missingCrops) {
                return; // everything present
            }

            $tableCountBefore = count($existing);

            // Order matters: the business tables FK-reference poultry tables
            if ($missingPoultry) {
                runMigrationFile($pdo, $poultryFile);
            }
            if ($missingBusiness) {
                runMigrationFile($pdo, $businessFile);
            }
            if ($missingCrops) {
                runMigrationFile($pdo, $cropsFile);
            }

            if ($isSqlite) {
                $after = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $after = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            }

            if (count($after) <= $tableCountBefore) {
                return; // no progress — give up quietly, retried next request
            }
        }

        // Seed vaccine guides after all tables exist
        seedVaccineGuides($pdo);
    } catch (Exception $e) {
        // Silent — never break the page
    }
}
