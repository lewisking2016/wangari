<?php
/**
 * Wangari Farm — Complete System Setup
 * Runs the full poultry management migration on the configured database.
 *
 * Usage: php setup_complete.php
 */
declare(strict_types=1);

require_once __DIR__ . '/Backend/config/database.php';

echo "═══════════════════════════════════════════════════════════════\n";
echo "  Wangari Farm — Complete Poultry System Setup\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    $pdo = getDatabaseConnection();
    if (!$pdo) throw new Exception('Database connection failed. Check Backend/config/database.php');

    echo "[1/2] Connected to database successfully.\n\n";

    echo "[2/2] Running complete poultry migration...\n\n";
    $poultrySql = file_get_contents(__DIR__ . '/Backend/config/migration_poultry_complete.sql');
    if ($poultrySql === false) throw new Exception('Could not read migration file');

    $businessSql = file_get_contents(__DIR__ . '/Backend/config/migration_v2_business.sql');
    if ($businessSql === false) throw new Exception('Could not read v2 migration file');

    // Poultry tables must run FIRST — the business tables FK-reference them.
    // splitMigrationSql strips comment banners so statements aren't dropped.
    $statements = array_merge(
        splitMigrationSql($poultrySql),
        splitMigrationSql($businessSql)
    );

    $success = 0; $failed = 0;
    foreach ($statements as $i => $stmt) {
        if (empty($stmt)) continue;
        try {
            $pdo->exec($stmt);
            $success++;
        } catch (PDOException $e) {
            $failed++;
            echo "  ⚠ Statement " . ($i+1) . ": " . $e->getMessage() . "\n";
        }
    }

    echo "\n  ✓ Migration complete: $success statements executed, $failed warnings (non-fatal).\n\n";

    // Verify tables exist
    $expectedTables = [
        'houses', 'batches', 'daily_batch_records', 'health_records',
        'egg_grades', 'daily_egg_grading', 'daily_sales_reconciliation',
        'daily_sales_lines', 'raw_materials', 'raw_material_movements',
        'feed_recipes', 'feed_recipe_ingredients', 'feed_production_batches',
        'walk_in_customers', 'bulk_sales', 'suppliers', 'activity_logs', 'settings',
        'batch_costs', 'cashbook_entries', 'customer_credits', 'credit_payments',
        'feeding_standards', 'feed_allocations', 'supplier_prices',
        'purchase_orders', 'purchase_order_items', 'broiler_weighings',
        'hatchery_batches', 'egg_losses', 'quality_tests', 'reorder_rules',
        'system_alerts'
    ];
    $existing = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $missing = array_diff($expectedTables, $existing);

    if (empty($missing)) {
        echo "  ✓ All " . count($expectedTables) . " required tables present.\n\n";
    } else {
        echo "  ⚠ Missing tables: " . implode(', ', $missing) . "\n\n";
    }

    // Verify seed data
    $matCount = (int)$pdo->query("SELECT COUNT(*) FROM raw_materials")->fetchColumn();
    $gradeCount = (int)$pdo->query("SELECT COUNT(*) FROM egg_grades")->fetchColumn();
    $settingCount = (int)$pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();

    echo "  ✓ Seeded $matCount raw materials, $gradeCount egg grades, $settingCount settings.\n\n";

    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  Setup complete! Your system is ready.\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    echo "  Admin Login:   http://localhost:8000/Frontend/admin/login.php\n\n";

    echo "  New admin modules available:\n";
    echo "  • Health & Vet         (vaccinations, mortality, treatments)\n";
    echo "  • Batches & Houses     (per-house daily tracking)\n";
    echo "  • Daily Reconciliation (crates × price tiers)\n";
    echo "  • Stores & Stock       (maize, premix, drugs, etc.)\n";
    echo "  • Feed Production      (recipes + production)\n";
    echo "  • Bulk Sales           (walk-in customers)\n";
    echo "  • Online Orders        (with CSV export)\n\n";

} catch (Exception $e) {
    echo "\n  ✗ Setup failed: " . $e->getMessage() . "\n\n";
    exit(1);
}
