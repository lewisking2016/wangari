<?php
/**
 * Mixed Farming Expansion Migration
 * Adds:
 *   1. Feeding standards for Cattle, Goat, Sheep, Pig, Rabbit, Duck
 *   2. Animal weight tracking (universal)
 *   3. Crop cost tracking (cost per acre)
 *   4. Sample housing for all species
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

// ═══════════════════════════════════════════════════════════════
// 1. ANIMAL WEIGHTS — universal weight tracking for all species
// ═══════════════════════════════════════════════════════════════
seed($pdo, "
    CREATE TABLE IF NOT EXISTS animal_weights (
        id INT AUTO_INCREMENT PRIMARY KEY,
        animal_id INT NULL,
        group_id INT NULL,
        species VARCHAR(80) NOT NULL DEFAULT 'Chicken',
        weight_kg DECIMAL(8,2) NOT NULL,
        recorded_date DATE NOT NULL,
        notes TEXT,
        recorded_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_aw_animal (animal_id),
        INDEX idx_aw_group (group_id),
        INDEX idx_aw_species (species),
        INDEX idx_aw_date (recorded_date)
    ) ENGINE=InnoDB
");

// ═══════════════════════════════════════════════════════════════
// 2. CROP COSTS — cost tracking per field/planting
// ═══════════════════════════════════════════════════════════════
seed($pdo, "
    CREATE TABLE IF NOT EXISTS crop_costs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        planting_id INT NOT NULL,
        cost_type VARCHAR(60) NOT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        description TEXT,
        cost_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_cc_planting (planting_id),
        FOREIGN KEY (planting_id) REFERENCES crop_plantings(id) ON DELETE CASCADE
    ) ENGINE=InnoDB
");

// ═══════════════════════════════════════════════════════════════
// 3. FEEDING STANDARDS — Cattle, Goat, Sheep, Pig, Rabbit, Duck
// ═══════════════════════════════════════════════════════════════

// Helper: insert feeding standard only if not already present
$feedData = [
    // ── Cattle (Dairy) ──
    ['Cattle', 'calf', 1, 4.0, 'Milk replacer / whole milk', 'Newborn to 1 week; 4L milk per day'],
    ['Cattle', 'calf', 2, 6.0, 'Milk + Calf starter', '1-4 weeks; introduce solid feed'],
    ['Cattle', 'calf', 4, 2.0, 'Calf starter + Hay', '1-2 months; weaning transition (dry feed kg/day)'],
    ['Cattle', 'heifer', 3, 5.0, 'Heifer ration + Hay', '2-6 months; growth phase'],
    ['Cattle', 'heifer', 6, 7.0, 'Heifer ration + Hay + Silage', '6-12 months; pre-breeding'],
    ['Cattle', 'dairy_cow', 12, 12.0, 'Dairy meal + Hay + Silage', 'Lactating cow; 12-16 kg DM/day'],
    ['Cattle', 'dairy_cow', 15, 14.0, 'Dairy meal + Hay + Silage + Concentrates', 'Peak lactation'],
    ['Cattle', 'beef', 6, 8.0, 'Beef ration + Hay', '6-12 months; finishing'],
    ['Cattle', 'bull', 12, 10.0, 'Beef ration + Hay + Minerals', 'Breeding bull maintenance'],
    ['Cattle', 'dry_cow', 12, 9.0, 'Maintenance ration + Hay + Silage', 'Dry period; 60 days before calving'],

    // ── Goat ──
    ['Goat', 'kid', 1, 0.5, 'Milk / milk replacer', 'Newborn to 1 week; 0.5L/day'],
    ['Goat', 'kid', 4, 0.3, 'Kid starter + Hay', '1-3 months; weaning (dry feed kg/day)'],
    ['Goat', 'doe', 6, 1.5, 'Goat mix + Hay + Browse', 'Growing doe; 6-12 months'],
    ['Goat', 'doe', 12, 2.0, 'Goat lactation mix + Hay', 'Lactating doe; 2-2.5 kg DM/day'],
    ['Goat', 'buck', 12, 2.0, 'Goat maintenance mix + Hay + Minerals', 'Breeding buck; 2-2.5 kg DM/day'],
    ['Goat', 'meat_goat', 6, 1.8, 'Meat goat ration + Browse', 'Boer / meat breed finishing'],

    // ── Sheep ──
    ['Sheep', 'lamb', 1, 0.4, 'Milk / colostrum', 'Newborn; 0.4L/day'],
    ['Sheep', 'lamb', 4, 0.3, 'Lamb creep feed + Hay', '1-3 months; weaning'],
    ['Sheep', 'ewe', 6, 1.2, 'Sheep mix + Hay', 'Growing ewe; 6-12 months'],
    ['Sheep', 'ewe', 12, 1.8, 'Ewe lactation mix + Hay', 'Lactating ewe; 1.8-2.0 kg DM/day'],
    ['Sheep', 'ram', 12, 1.8, 'Maintenance mix + Hay + Minerals', 'Breeding ram'],
    ['Sheep', 'lamb_fatten', 4, 1.5, 'Fattening ration + Hay', 'Fattening lambs for market'],

    // ── Pig ──
    ['Pig', 'piglet', 1, 0.2, 'Sow milk', 'Nursing piglet; milk only'],
    ['Pig', 'piglet', 4, 0.5, 'Piglet starter', '1-5 kg body weight; creep feed'],
    ['Pig', 'grower', 8, 1.5, 'Grower ration', '5-30 kg BW; 1.5 kg/day'],
    ['Pig', 'finisher', 12, 2.5, 'Finisher ration', '30-90 kg BW; 2.5 kg/day'],
    ['Pig', 'sow', 12, 3.0, 'Sow lactation feed', 'Lactating sow; 3-4 kg/day'],
    ['Pig', 'boar', 12, 2.5, 'Boar maintenance ration', 'Breeding boar; 2.5 kg/day'],

    // ── Rabbit ──
    ['Rabbit', 'kit', 1, 0.05, 'Doe milk', 'Nursing kit; milk only'],
    ['Rabbit', 'kit', 4, 0.08, 'Rabbit pellet + Hay', 'Weaning; 4-8 weeks'],
    ['Rabbit', 'grower', 8, 0.10, 'Rabbit grower pellet', '8-12 weeks; 100g/day'],
    ['Rabbit', 'doe', 12, 0.15, 'Rabbit maintenance + Hay + Greens', 'Adult doe; 150g/day'],
    ['Rabbit', 'buck', 12, 0.12, 'Rabbit maintenance + Hay', 'Adult buck; 120g/day'],

    // ── Duck ──
    ['Duck', 'duckling', 1, 0.03, 'Duckling starter', 'Day-old to 2 weeks; 30g/day'],
    ['Duck', 'duckling', 4, 0.10, 'Duckling grower', '2-6 weeks; 100g/day'],
    ['Duck', 'layer', 8, 0.17, 'Layer mash + Greens + Water', '7 weeks to lay; 170g/day'],
    ['Duck', 'meat_duck', 8, 0.20, 'Meat duck finisher', 'Fattening; 200g/day'],
];

$ins = $pdo->prepare("INSERT IGNORE INTO feeding_standards (species, bird_type, week_number, feed_per_bird_per_day_grams, feed_type, notes) VALUES (?,?,?,?,?,?)");
foreach ($feedData as $row) {
    $ins->execute($row);
}

// ═══════════════════════════════════════════════════════════════
// 4. SEED SAMPLE HOUSES for all species
// ═══════════════════════════════════════════════════════════════
$houseData = [
    // Already have 4 chicken houses, add others
    ['Main Cattle Barn', 'CB-01', 'North Farm', 50, 'Cattle', 'barn', 'Main cattle housing with milking area'],
    ['Cattle Kraal', 'CK-01', 'North Farm', 40, 'Cattle', 'kraal', 'Open kraal for beef cattle'],
    ['Dairy Parlor', 'DP-01', 'North Farm', 20, 'Cattle', 'milking_parlor', 'Milking parlor for dairy herd'],
    ['Goat House', 'GH-01', 'East Farm', 30, 'Goat', 'house', 'Goat house with raised floor'],
    ['Goat Pen', 'GP-01', 'East Farm', 50, 'Goat', 'pen', 'Open goat pen with shade'],
    ['Sheep Kraal', 'SK-01', 'East Farm', 40, 'Sheep', 'kraal', 'Sheep kraal with dipping area'],
    ['Pig Sty 1', 'PS-01', 'South Farm', 20, 'Pig', 'sty', 'Pigsty with water system'],
    ['Pig Sty 2', 'PS-02', 'South Farm', 15, 'Pig', 'sty', 'Fattening sty'],
    ['Rabbit Hutch 1', 'RH-01', 'West Farm', 50, 'Rabbit', 'hutch', 'Multi-level rabbit hutch'],
    ['Duck House', 'DH-01', 'Pond Area', 40, 'Duck', 'house', 'Duck house near pond'],
    ['Duck Pond', 'DP-02', 'Pond Area', 100, 'Duck', 'pond', 'Natural pond for ducks'],
];

$ins = $pdo->prepare("INSERT IGNORE INTO houses (house_name, house_code, location, capacity, species, house_type, description, is_active) VALUES (?,?,?,?,?,?,?,1)");
foreach ($houseData as $row) {
    $ins->execute($row);
}

// ═══════════════════════════════════════════════════════════════
// Done
// ═══════════════════════════════════════════════════════════════
echo "=== Mixed Farming Migration Complete ===\n";
echo "  New tables: animal_weights, crop_costs\n";
echo "  Feeding standards seeded: " . count($feedData) . " records (Cattle, Goat, Sheep, Pig, Rabbit, Duck)\n";
echo "  Sample houses seeded: " . count($houseData) . " records\n";
echo "  SQL executed: $ok, skipped: $skip\n";
