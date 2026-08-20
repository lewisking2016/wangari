<?php
/**
 * Demo data seeder for the Wangari dashboard screenshot + walkthrough.
 * Populates the LOCAL dev DB (busia_chicken_db) with realistic-looking data.
 * Lives in .freebuff/ so it is never committed or pushed.
 */
declare(strict_types=1);

$pdo = new PDO('mysql:host=localhost;dbname=busia_chicken_db;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (['order_items','orders','daily_batch_records','batch_costs','feed_allocations','broiler_weighings','batches','flocks','houses','cashbook_entries','walk_in_customers','crm_followups','crm_contacts','crm_segments','crop_activities','crop_harvests','crop_plantings','fields','herds','products'] as $t) {
    $pdo->exec("DELETE FROM `$t`");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

$now = date('Y-m-d H:i:s');

/* ---------- Houses ---------- */
$houses = [
    ['House A — Layers', 'HA-LYR', 'Main Farm', 1500],
    ['House B — Layers', 'HB-LYR', 'Main Farm', 1500],
    ['House C — Broilers', 'HC-BRO', 'Main Farm', 2000],
    ['House D — Kienyeji', 'HD-KYE', 'North Field', 800],
];
$houseIds = [];
$stmt = $pdo->prepare("INSERT INTO houses (house_name, house_code, location, capacity, is_active, created_at) VALUES (?,?,?,?,1,?)");
foreach ($houses as $i => $h) {
    $stmt->execute([$h[0], $h[1], $h[2], $h[3], $now]);
    $houseIds[] = (int)$pdo->lastInsertId();
}

/* ---------- Flocks ---------- */
$flocks = [
    ['Layers Batch 12', 'Isa Brown', 1200, 1148, '2025-09-10', 'active'],
    ['Layers Batch 13', 'Hy-Line Brown', 1400, 1382, '2026-01-20', 'active'],
    ['Broilers Batch 31', 'Cobb 500', 2000, 1956, '2026-06-15', 'active'],
    ['Kienyeji Flock 4', 'Improved Kienyeji', 800, 792, '2026-03-05', 'active'],
];
$flockIds = [];
$stmt = $pdo->prepare("INSERT INTO flocks (flock_name, breed, initial_count, current_count, hatch_date, status, created_at) VALUES (?,?,?,?,?,?,?)");
foreach ($flocks as $i => $f) {
    $stmt->execute([$f[0], $f[1], $f[2], $f[3], $f[4], $f[5], $now]);
    $flockIds[] = (int)$pdo->lastInsertId();
}

/* ---------- Batches ---------- */
$batches = [
    ['Layers Batch 12', 'LYR-12', $houseIds[0], $flockIds[0], 'Isa Brown', 'layer', 1200, 1148, '2025-09-10', '2026-09-30', '2026-09-30', 'active'],
    ['Layers Batch 13', 'LYR-13', $houseIds[1], $flockIds[1], 'Hy-Line Brown', 'layer', 1400, 1382, '2026-01-20', '2027-01-20', '2027-01-20', 'active'],
    ['Broilers Batch 31', 'BRO-31', $houseIds[2], $flockIds[2], 'Cobb 500', 'broiler', 2000, 1956, '2026-06-15', '2026-08-20', '2026-08-20', 'active'],
    ['Kienyeji Flock 4', 'KYE-04', $houseIds[3], $flockIds[3], 'Improved Kienyeji', 'kienyeji', 800, 792, '2026-03-05', '2026-12-31', '2026-12-31', 'active'],
];
$batchIds = [];
$stmt = $pdo->prepare("INSERT INTO batches (batch_name, batch_code, house_id, flock_id, breed, batch_type, initial_birds, current_birds, placement_date, expected_harvest_date, expected_sale_date, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
foreach ($batches as $i => $b) {
    $stmt->execute([$b[0], $b[1], $b[2], $b[3], $b[4], $b[5], $b[6], $b[7], $b[8], $b[9], $b[10], $b[11], $now]);
    $batchIds[] = (int)$pdo->lastInsertId();
}

/* ---------- Daily batch records: 30 days of egg production ---------- */
$stmt = $pdo->prepare("INSERT INTO daily_batch_records (batch_id, record_date, week_number, opening_birds, mortality, mortality_rate, closing_birds, expected_weight_kg, average_weight_kg, trays, total_eggs, extra_large_eggs, damaged_eggs, net_for_sale, production_pct, notes, recorded_by, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,2,?)");
$eggBase = [1050, 1290]; // layer batches 12, 13
for ($d = 29; $d >= 0; $d--) {
    $date = date('Y-m-d', strtotime("-$d days"));
    $wk = intdiv(30 - $d, 7) + 30;
    foreach ([0, 1] as $li) {
        $eggs = (int)round($eggBase[$li] * (0.82 + 0.12 * sin(($d + $li * 3) / 4)) + mt_rand(-18, 22));
        $mort = mt_rand(0, 3);
        $xl = (int)round($eggs * 0.22);
        $damaged = (int)round($eggs * 0.012) + mt_rand(0, 1);
        $net = $eggs - $xl - $damaged;
        $stmt->execute([$batchIds[$li], $date, $wk, 1200, $mort, $mort / 1200, 1200 - $mort, 1.9, 1.9, intdiv($net, 30), $eggs, $xl, $damaged, $net, $net / 1200, 'Morning + evening collection', $now]);
    }
    // broiler weights
    $stmt2 = $pdo->prepare("INSERT INTO broiler_weighings (batch_id, weigh_date, day_number, sample_size, avg_weight_kg, notes, recorded_by, created_at) VALUES (?,?,?,?,?,?,2,?)");
    $stmt2->execute([$batchIds[2], $date, $d + 20, mt_rand(20, 40), round(1.1 + $d * 0.035 + mt_rand(-8, 8) / 100, 2), 'Weekly weighing sample', $now]);
    // feed allocations
    $stmt3 = $pdo->prepare("INSERT INTO feed_allocations (batch_id, allocation_date, feed_type, kg_fed, notes, recorded_by, created_at) VALUES (?,?,?,?,?,2,?)");
    foreach ([0, 1] as $li) {
        $stmt3->execute([$batchIds[$li], $date, 'Layers Mash', round(320 + mt_rand(-15, 20), 1), 'Daily ration', $now]);
    }
}

/* ---------- Products (inventory) ---------- */
$products = [
    ['Broiler Live (2.0 kg)', 'live_chicken', 650, 24, 2.0, 'BRO-LIVE-20'],
    ['Layer Live (1.8 kg)', 'live_chicken', 550, 0, 1.8, 'LYR-LIVE-18'],
    ['Fresh Eggs (tray of 30)', 'eggs', 780, 120, 0.0, 'EGG-TRAY-30'],
    ['Jumbo Eggs (tray of 30)', 'eggs', 850, 64, 0.0, 'EGG-JUMBO-30'],
    ['Day-Old Chicks — Isa Brown', 'chicks', 180, 400, 0.0, 'DOC-ISA-01'],
    ['Day-Old Chicks — Cobb 500', 'chicks', 200, 320, 0.0, 'DOC-COBB-01'],
    ['Layers Mash 50kg', 'feed', 2950, 148, 50.0, 'FEED-LYR-50'],
    ['Broiler Starter 50kg', 'feed', 3100, 86, 50.0, 'FEED-BRS-50'],
    ['Kienyeji Grower 50kg', 'feed', 2650, 12, 50.0, 'FEED-KYE-50'],
];
$stmt = $pdo->prepare("INSERT INTO products (category_id, name, slug, description, product_type, price, stock_quantity, weight_kg, sku, manufacturer, is_active, is_featured, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,1,1,?)");
$catMap = ['live_chicken' => 1, 'eggs' => 2, 'chicks' => 3, 'feed' => 4];
$productIds = [];
foreach ($products as $i => $p) {
    $stmt->execute([$catMap[$p[1]], $p[0], strtolower(str_replace([' ', '(', ')', '.'], '-', $p[0])), $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], 'Wangari Farm', $now]);
    $productIds[] = (int)$pdo->lastInsertId();
}

/* ---------- Customers (walk-in) ---------- */
$walkins = [
    ['Mary Wanjiku', '0722123456', 'wholesale', 'Naivasha', 'Buys eggs weekly, credit OK'],
    ['John Otieno', '0733987654', 'retail', 'Kisumu', 'Prefers jumbo eggs'],
    ['Naomi Chebet', '0711888999', 'institution', 'Eldoret', 'School kitchen — 40 trays/week'],
    ['David Mutua', '0700555666', 'agent', 'Nairobi', 'Resells day-old chicks'],
    ['Grace Achieng', '0744666777', 'retail', 'Busia', 'Layers mash monthly'],
    ['Peter Kamau', '0720555444', 'wholesale', 'Thika', 'Broilers bulk buyer'],
];
$stmt = $pdo->prepare("INSERT INTO walk_in_customers (customer_name, phone, customer_type, address, notes, created_at) VALUES (?,?,?,?,?,?)");
$walkinIds = [];
foreach ($walkins as $w) {
    $stmt->execute([$w[0], $w[1], $w[2], $w[3], $w[4], $now]);
    $walkinIds[] = (int)$pdo->lastInsertId();
}

/* ---------- CRM segments + contacts + follow-ups ---------- */
$segs = ['Wholesale Buyers', 'School Accounts', 'Egg Subscribers', 'Feed Regulars'];
$stmt = $pdo->prepare("INSERT INTO crm_segments (name, description, created_at) VALUES (?,?,?)");
foreach ($segs as $s) {
    $stmt->execute([$s, 'Auto-generated demo segment', $now]);
}
$stmt = $pdo->prepare("INSERT INTO crm_contacts (customer_id, customer_type, contact_type, note, created_by, created_at) VALUES (?,?,?,?,2,?)");
$contactTypes = ['phone_call', 'whatsapp', 'visit', 'sms'];
foreach ($walkinIds as $i => $wid) {
    $stmt->execute([$wid, 'walk_in', $contactTypes[$i % 4], 'Demo contact: ' . $walkins[$i][0] . ' — order follow-up', date('Y-m-d H:i:s', strtotime("-" . ($i + 1) . " days"))]);
}
$stmt = $pdo->prepare("INSERT INTO crm_followups (customer_id, customer_type, due_date, status, note, created_by, created_at) VALUES (?,?,?,?,?,2,?)");
foreach ([0, 1, 2, 3] as $i) {
    $stmt->execute([$walkinIds[$i], 'walk_in', date('Y-m-d', strtotime("+" . ($i + 2) . " days")), 'open', 'Call to confirm weekly order', $now]);
}

/* ---------- Orders + items (last 7 days) ---------- */
$orderStatuses = ['paid', 'completed', 'completed', 'completed', 'packing', 'paid', 'completed'];
$stmtO = $pdo->prepare("INSERT INTO orders (user_id, order_number, status, total_amount, payment_method, selling_point, delivery_method, phone_contact, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
$stmtI = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?,?,?,?)");
$productCatalog = [
    [4, 780, 'Fresh Eggs (tray of 30)'],   // index 2 in products -> productIds[2]
    [3, 850, 'Jumbo Eggs (tray of 30)'],
    [6, 2950, 'Layers Mash 50kg'],
    [7, 3100, 'Broiler Starter 50kg'],
    [1, 650, 'Broiler Live (2.0 kg)'],
    [5, 180, 'Day-Old Chicks — Isa Brown'],
];
for ($d = 6; $d >= 0; $d--) {
    $nOrders = mt_rand(2, 4);
    for ($o = 0; $o < $nOrders; $o++) {
        $ts = strtotime("-$d days " . mt_rand(7, 19) . ":" . mt_rand(0, 59));
        $date = date('Y-m-d H:i:s', $ts);
        $num = 'WGR-' . date('ymd', $ts) . '-' . str_pad((string)mt_rand(1, 99), 2, '0', STR_PAD_LEFT);
        // pick 1-3 line items
        $lines = [];
        shuffle($productCatalog);
        $used = [];
        foreach ($productCatalog as $pc) {
            if (count($lines) >= mt_rand(1, 3)) break;
            $used[] = $pc[2];
            $qty = in_array($pc[2], ['Fresh Eggs (tray of 30)', 'Jumbo Eggs (tray of 30)']) ? mt_rand(2, 20) : mt_rand(1, 6);
            $lines[] = [$pc[0], $qty, $pc[1]];
        }
        $total = array_sum(array_map(fn($l) => $l[1] * $l[2], $lines));
        $cust = $walkins[array_rand($walkins)];
        $stmtO->execute([1, $num, $orderStatuses[$d % 7], $total, ['mpesa', 'cash', 'bank'][mt_rand(0, 2)], 'walk_in', 'pickup', $cust[1], $date, $date]);
        $oid = (int)$pdo->lastInsertId();
        foreach ($lines as $l) {
            $stmtI->execute([$oid, $productIds[$l[0]], $l[1], $l[2]]);
        }
        // recent orders need customer name — update via join later if needed
    }
}

/* ---------- Cashbook: 6 months of in/out ---------- */
$inSources = ['egg_sales', 'broiler_sales', 'feed_sales', 'online_order', 'bulk_sale', 'credit_payment'];
$outSources = ['feed_purchase', 'raw_material_purchase', 'drugs_purchase', 'chick_purchase', 'labour', 'transport', 'utilities'];
$stmt = $pdo->prepare("INSERT INTO cashbook_entries (entry_date, direction, money_source, amount, paid_through, customer_name, supplier_name, reference_no, description, recorded_by, created_at) VALUES (?,?,?,?,?,?,?,?,?,2,?)");
for ($m = 5; $m >= 0; $m--) {
    $baseIn = 420000 - $m * 35000 + mt_rand(-20000, 30000);
    $baseOut = 310000 - $m * 28000 + mt_rand(-15000, 25000);
    // 8-12 'in' entries
    $nIn = mt_rand(8, 12);
    for ($i = 0; $i < $nIn; $i++) {
        $day = mt_rand(1, 28);
        $date = date('Y-m-d', mktime(0, 0, 0, (int)date('n') - $m, $day, (int)date('Y')));
        if ($m === 0 && $day > (int)date('j')) continue;
        $amt = round($baseIn / $nIn * mt_rand(60, 140) / 100, 0);
        $src = $inSources[array_rand($inSources)];
        $stmt->execute([$date, 'in', $src, $amt, ['cash', 'mpesa', 'bank'][mt_rand(0, 2)], $walkins[array_rand($walkins)][0], null, 'INV-' . mt_rand(1000, 9999), 'Sales entry', $now]);
    }
    $nOut = mt_rand(7, 11);
    for ($i = 0; $i < $nOut; $i++) {
        $day = mt_rand(1, 28);
        $date = date('Y-m-d', mktime(0, 0, 0, (int)date('n') - $m, $day, (int)date('Y')));
        if ($m === 0 && $day > (int)date('j')) continue;
        $amt = round($baseOut / $nOut * mt_rand(60, 140) / 100, 0);
        $src = $outSources[array_rand($outSources)];
        $stmt->execute([$date, 'out', $src, $amt, ['cash', 'mpesa', 'bank', 'cheque'][mt_rand(0, 3)], null, ['Uzima Feeds', 'Kenya Kwanza Vet', 'AgroChem', 'KPLC', 'Farm Stores'][mt_rand(0, 4)], 'PO-' . mt_rand(100, 999), 'Purchase / expense entry', $now]);
    }
}

/* ---------- Batch costs (feed costing) ---------- */
$stmt = $pdo->prepare("INSERT INTO batch_costs (batch_id, cost_date, cost_type, description, quantity, unit, unit_cost, total_cost, paid_from, reference_no, recorded_by, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,2,?)");
foreach ([0, 1, 2, 3] as $bi) {
    for ($i = 0; $i < 6; $i++) {
        $date = date('Y-m-d', strtotime("-" . ($i * 5 + mt_rand(1, 4)) . " days"));
        $qty = mt_rand(40, 120);
        $uc = mt_rand(2900, 3300);
        $stmt->execute([$batchIds[$bi], $date, 'feed', 'Layers/Broiler feed delivery', $qty, 'kg', $uc, $qty * $uc / 1000, ['mpesa', 'bank'][mt_rand(0, 1)], 'PO-' . mt_rand(100, 999), $now]);
    }
}

/* ---------- Herds / Fields / Crops (other hubs) ---------- */
$stmt = $pdo->prepare("INSERT INTO herds (name, species, size, location, status, notes, created_at) VALUES (?,?,?,?,?,?,?)");
$herds = [
    ['Milking Herd A', 'Friesian', 14, 'North Field', 'Active', 'Milking twice daily, ~42L/day'],
    ['Goat Herd 1', 'Galla Goats', 9, 'East Paddock', 'Active', 'Breeding does'],
];
foreach ($herds as $h) {
    $stmt->execute([$h[0], $h[1], $h[2], $h[3], $h[4], $h[5], $now]);
}
$stmt = $pdo->prepare("INSERT INTO fields (name, location, size_acres, soil_type, status, notes, created_at) VALUES (?,?,?,?,?,?,?)");
$fields = [
    ['Maize Plot 1', 'North Field', 4.5, 'Loam', 'active', 'Maize — planted with demo batch', $now],
    ['Kale Garden', 'Behind houses', 1.2, 'Clay loam', 'active', 'Kale + spinach', $now],
    ['Fallow Plot', 'South', 2.0, 'Sandy loam', 'fallow', 'Resting for next season', $now],
];
$fieldIds = [];
foreach ($fields as $f) {
    $stmt->execute([$f[0], $f[1], $f[2], $f[3], $f[4], $f[5], $now]);
    $fieldIds[] = (int)$pdo->lastInsertId();
}
$stmt = $pdo->prepare("INSERT INTO crop_plantings (field_id, crop, variety, planting_date, area_acres, expected_harvest_date, expected_yield, yield_unit, status, notes, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
$crops = [
    [$fieldIds[0], 'Maize', 'DK 8031', '2026-03-18', 4.5, '2026-09-05', 18.0, '90kg bags', 'growing', 'Demo planting', $now],
    [$fieldIds[1], 'Kale', 'Collard', '2026-06-02', 1.2, '2026-08-25', 850.0, 'kg', 'growing', 'Weekly harvest for feed supplement', $now],
];
foreach ($crops as $c) {
    $stmt->execute([$c[0], $c[1], $c[2], $c[3], $c[4], $c[5], $c[6], $c[7], $c[8], $c[9], $c[10]]);
}

echo "Demo data seeded OK\n";
echo "Orders: " . $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() . "\n";
echo "Cashbook: " . $pdo->query("SELECT COUNT(*) FROM cashbook_entries")->fetchColumn() . "\n";
echo "Egg records: " . $pdo->query("SELECT COUNT(*) FROM daily_batch_records")->fetchColumn() . "\n";
echo "Products: " . $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() . "\n";
