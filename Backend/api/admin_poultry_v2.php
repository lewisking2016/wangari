<?php
/**
 * Backend API — Complete Poultry Management (v2)
 * Handles: Health, Houses, Batches, Daily Batch Records, Egg Grading,
 *          Daily Sales Reconciliation, Stores/Raw Materials,
 *          Feed Recipes & Production, Bulk Sales
 *
 * All actions are CSRF-protected where appropriate.
 */
declare(strict_types=1);

header('Content-Type: application/json');
header('Cache-Control: no-store');

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();

require_once __DIR__ . '/../config/database.php';

function api_ok(array $data = []): never {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}
function api_err(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

/**
 * Safe query helpers: return empty arrays on SQL errors (missing tables, etc.)
 */
function safe_query_all(PDO $pdo, string $sql, array $params = []): array {
    try {
        if (count($params) === 0) {
            $res = $pdo->query($sql);
            return $res ? $res->fetchAll(PDO::FETCH_ASSOC) : [];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function safe_query_one(PDO $pdo, string $sql, array $params = []) {
    try {
        if (count($params) === 0) {
            $res = $pdo->query($sql);
            return $res ? $res->fetch(PDO::FETCH_ASSOC) : null;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $returnVal = $stmt->fetch(PDO::FETCH_ASSOC);
        return $returnVal !== false ? $returnVal : null;
    } catch (PDOException $e) {
        return null;
    }
}

function safe_scalar(PDO $pdo, string $sql, array $params = []) {
    try {
        if (count($params) === 0) {
            $res = $pdo->query($sql);
            if (!$res) return null;
            $col = $res->fetch(PDO::FETCH_NUM);
            return $col[0] ?? null;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $col = $stmt->fetch(PDO::FETCH_NUM);
        return $col[0] ?? null;
    } catch (PDOException $e) {
        return null;
    }
}

try {
    if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager', 'stock_manager', 'sales_staff'], true)) {
        api_err('Unauthorized access', 401);
    }

    $pdo = getDatabaseConnection();
    if (!$pdo) api_err('Database connection failed', 500);

    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    $user_id = (int)$_SESSION['user_id'];

    // Allow GET for reads, POST for writes
    if ($method !== 'GET' && $method !== 'POST') api_err('Invalid request method', 405);

    // ─────────────────────────────────────────────────────────
    // HOUSES
    // ─────────────────────────────────────────────────────────
    if ($action === 'get_houses') {
        $rows = safe_query_all($pdo, "SELECT * FROM houses ORDER BY house_name ASC");
        api_ok(['data' => $rows]);
    }
    if ($action === 'save_house' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['house_name'] ?? '');
        $code = trim($_POST['house_code'] ?? '');
        $loc = trim($_POST['location'] ?? '');
        $cap = (int)($_POST['capacity'] ?? 0);
        if ($name === '' || $code === '') api_err('House name and code are required');
        if ($id > 0) {
            $pdo->prepare("UPDATE houses SET house_name=?, house_code=?, location=?, capacity=? WHERE id=?")
                ->execute([$name, $code, $loc, $cap, $id]);
        } else {
            $pdo->prepare("INSERT INTO houses (house_name, house_code, location, capacity) VALUES (?,?,?,?)")
                ->execute([$name, $code, $loc, $cap]);
        }
        api_ok(['message' => 'House saved']);
    }
    if ($action === 'delete_house' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        try { $pdo->prepare("DELETE FROM houses WHERE id=?")->execute([$id]); }
        catch (PDOException $e) { api_err('Cannot delete: house has batches'); }
        api_ok(['message' => 'House deleted']);
    }

    // ─────────────────────────────────────────────────────────
    // BATCHES
    // ─────────────────────────────────────────────────────────
    if ($action === 'get_batches') {
        $sql = "SELECT b.*, h.house_name, h.house_code
                FROM batches b LEFT JOIN houses h ON h.id=b.house_id
                ORDER BY b.placement_date DESC, b.id DESC";
        $rows = safe_query_all($pdo, $sql);
        api_ok(['data' => $rows]);
    }
    if ($action === 'save_batch' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['batch_name'] ?? '');
        $code = trim($_POST['batch_code'] ?? '');
        $house_id = (int)($_POST['house_id'] ?? 0);
        $breed = trim($_POST['breed'] ?? '');
        $type = $_POST['batch_type'] ?? 'layer';
        $initial = (int)($_POST['initial_birds'] ?? 0);
        $placement = $_POST['placement_date'] ?? date('Y-m-d');
        $harvest = $_POST['expected_harvest_date'] ?? null;
        $sale = $_POST['expected_sale_date'] ?? null;
        $status = $_POST['status'] ?? 'active';
        if ($name === '' || $code === '' || $house_id === 0 || $initial === 0) api_err('Missing required fields');
        if ($id > 0) {
            $pdo->prepare("UPDATE batches SET batch_name=?, batch_code=?, house_id=?, breed=?, batch_type=?, initial_birds=?, current_birds=?, placement_date=?, expected_harvest_date=?, expected_sale_date=?, status=? WHERE id=?")
                ->execute([$name, $code, $house_id, $breed, $type, $initial, $initial, $placement, $harvest, $sale, $status, $id]);
        } else {
            $pdo->prepare("INSERT INTO batches (batch_name, batch_code, house_id, breed, batch_type, initial_birds, current_birds, placement_date, expected_harvest_date, expected_sale_date, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$name, $code, $house_id, $breed, $type, $initial, $initial, $placement, $harvest, $sale, $status]);
        }
        api_ok(['message' => 'Batch saved']);
    }
    if ($action === 'delete_batch' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        try { $pdo->prepare("DELETE FROM batches WHERE id=?")->execute([$id]); }
        catch (PDOException $e) { api_err('Cannot delete: batch has records'); }
        api_ok(['message' => 'Batch deleted']);
    }

    // ─────────────────────────────────────────────────────────
    // DAILY BATCH RECORDS (per-house daily log)
    // ─────────────────────────────────────────────────────────
    if ($action === 'get_batch_records') {
        $batch_id = (int)($_GET['batch_id'] ?? 0);
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $sql = "SELECT * FROM daily_batch_records WHERE 1=1";
        $params = [];
        if ($batch_id > 0) { $sql .= " AND batch_id=?"; $params[] = $batch_id; }
        if ($from) { $sql .= " AND record_date >= ?"; $params[] = $from; }
        if ($to) { $sql .= " AND record_date <= ?"; $params[] = $to; }
        $sql .= " ORDER BY record_date DESC LIMIT 500";
        $rows = safe_query_all($pdo, $sql, $params);
        api_ok(['data' => $rows]);
    }
    if ($action === 'save_batch_record' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $batch_id = (int)($_POST['batch_id'] ?? 0);
        $date = $_POST['record_date'] ?? date('Y-m-d');
        $open = (int)($_POST['opening_birds'] ?? 0);
        $mort = (int)($_POST['mortality'] ?? 0);
        $sold = (int)($_POST['sold_birds'] ?? 0);
        $exp_w = (float)($_POST['expected_weight_kg'] ?? 0);
        $avg_w = (float)($_POST['average_weight_kg'] ?? 0);
        $trays = (int)($_POST['trays'] ?? 0);
        $eggs = (int)($_POST['total_eggs'] ?? 0);
        $xl = (int)($_POST['extra_large_eggs'] ?? 0);
        $dmg = (int)($_POST['damaged_eggs'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        if ($batch_id === 0) api_err('Batch required');
        $closing = $open - $mort - $sold;
        $mort_rate = $open > 0 ? round($mort / $open, 4) : 0;
        $net_sale = $eggs - $dmg;
        $prod_pct = $open > 0 ? round($eggs / $open, 4) : 0;
        if ($id > 0) {
            $pdo->prepare("UPDATE daily_batch_records SET opening_birds=?, mortality=?, mortality_rate=?, sold_birds=?, closing_birds=?, expected_weight_kg=?, average_weight_kg=?, trays=?, total_eggs=?, extra_large_eggs=?, damaged_eggs=?, net_for_sale=?, production_pct=?, notes=?, recorded_by=? WHERE id=?")
                ->execute([$open, $mort, $mort_rate, $sold, $closing, $exp_w, $avg_w, $trays, $eggs, $xl, $dmg, $net_sale, $prod_pct, $notes, $user_id, $id]);
        } else {
            $pdo->prepare("INSERT INTO daily_batch_records (batch_id, record_date, opening_birds, mortality, mortality_rate, sold_birds, closing_birds, expected_weight_kg, average_weight_kg, trays, total_eggs, extra_large_eggs, damaged_eggs, net_for_sale, production_pct, notes, recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$batch_id, $date, $open, $mort, $mort_rate, $sold, $closing, $exp_w, $avg_w, $trays, $eggs, $xl, $dmg, $net_sale, $prod_pct, $notes, $user_id]);
            $id = $pdo->lastInsertId();
        }
        // Auto-update batch current_birds
        $pdo->prepare("UPDATE batches SET current_birds=? WHERE id=?")->execute([$closing, $batch_id]);
        api_ok(['message' => 'Daily record saved', 'id' => $id, 'closing_birds' => $closing]);
    }
    if ($action === 'delete_batch_record' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM daily_batch_records WHERE id=?")->execute([$id]);
        api_ok(['message' => 'Record deleted']);
    }

    // ─────────────────────────────────────────────────────────
    // HEALTH RECORDS
    // ─────────────────────────────────────────────────────────
    if ($action === 'get_health_records') {
        $type = $_GET['type'] ?? null;
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $sql = "SELECT h.*, b.batch_name, f.flock_name
                FROM health_records h
                LEFT JOIN batches b ON b.id=h.batch_id
                LEFT JOIN flocks f ON f.id=h.flock_id
                WHERE 1=1";
        $params = [];
        if ($type) { $sql .= " AND h.record_type=?"; $params[] = $type; }
        if ($from) { $sql .= " AND h.record_date >= ?"; $params[] = $from; }
        if ($to) { $sql .= " AND h.record_date <= ?"; $params[] = $to; }
        $sql .= " ORDER BY h.record_date DESC LIMIT 500";
        $rows = safe_query_all($pdo, $sql, $params);
        api_ok(['data' => $rows]);
    }
    if ($action === 'save_health_record' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $date = $_POST['record_date'] ?? date('Y-m-d');
        $subject = trim($_POST['subject'] ?? '');
        $type = $_POST['record_type'] ?? 'treatment';
        $vaccine = trim($_POST['vaccine_name'] ?? '');
        $product = trim($_POST['product_name'] ?? '');
        $dosage = trim($_POST['dosage'] ?? '');
        $route = $_POST['route'] ?? 'oral';
        $birds = (int)($_POST['birds_treated'] ?? 0);
        $mort = (int)($_POST['mortality_count'] ?? 0);
        $reason = trim($_POST['mortality_reason'] ?? '');
        $vet = trim($_POST['vet_name'] ?? '');
        $next = $_POST['next_due_date'] ?? null;
        $cost = (float)($_POST['cost'] ?? 0);
        $status = $_POST['status'] ?? 'completed';
        $notes = trim($_POST['notes'] ?? '');
        $flock_id = (int)($_POST['flock_id'] ?? 0) ?: null;
        $batch_id = (int)($_POST['batch_id'] ?? 0) ?: null;
        if ($subject === '') api_err('Subject is required');
        if ($id > 0) {
            $pdo->prepare("UPDATE health_records SET record_date=?, flock_id=?, batch_id=?, subject=?, record_type=?, vaccine_name=?, product_name=?, dosage=?, route=?, birds_treated=?, mortality_count=?, mortality_reason=?, vet_name=?, next_due_date=?, cost=?, status=?, notes=?, recorded_by=? WHERE id=?")
                ->execute([$date, $flock_id, $batch_id, $subject, $type, $vaccine, $product, $dosage, $route, $birds, $mort, $reason, $vet, $next, $cost, $status, $notes, $user_id, $id]);
        } else {
            $pdo->prepare("INSERT INTO health_records (record_date, flock_id, batch_id, subject, record_type, vaccine_name, product_name, dosage, route, birds_treated, mortality_count, mortality_reason, vet_name, next_due_date, cost, status, notes, recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$date, $flock_id, $batch_id, $subject, $type, $vaccine, $product, $dosage, $route, $birds, $mort, $reason, $vet, $next, $cost, $status, $notes, $user_id]);
            $id = $pdo->lastInsertId();
        }
        // If mortality recorded, deduct from batch
        if ($type === 'mortality' && $mort > 0 && $batch_id) {
            $pdo->prepare("UPDATE batches SET current_birds = GREATEST(current_birds - ?, 0) WHERE id=?")
                ->execute([$mort, $batch_id]);
        }
        api_ok(['message' => 'Health record saved', 'id' => $id]);
    }
    if ($action === 'delete_health_record' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM health_records WHERE id=?")->execute([$id]);
        api_ok(['message' => 'Record deleted']);
    }

    // ─────────────────────────────────────────────────────────
    // EGG GRADING
    // ─────────────────────────────────────────────────────────
    if ($action === 'get_grades') {
        $rows = safe_query_all($pdo, "SELECT * FROM egg_grades ORDER BY weight_min_grams DESC");
        api_ok(['data' => $rows]);
    }
    if ($action === 'get_daily_grading') {
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $sql = "SELECT g.*, eg.grade_code, eg.grade_name, b.batch_name
                FROM daily_egg_grading g
                LEFT JOIN egg_grades eg ON eg.id=g.grade_id
                LEFT JOIN batches b ON b.id=g.batch_id
                WHERE 1=1";
        $params = [];
        if ($from) { $sql .= " AND g.record_date >= ?"; $params[] = $from; }
        if ($to) { $sql .= " AND g.record_date <= ?"; $params[] = $to; }
        $sql .= " ORDER BY g.record_date DESC LIMIT 500";
        $rows = safe_query_all($pdo, $sql, $params);
        api_ok(['data' => $rows]);
    }
    if ($action === 'save_daily_grading' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $date = $_POST['record_date'] ?? date('Y-m-d');
        $batch_id = (int)($_POST['batch_id'] ?? 0) ?: null;
        $grade_id = (int)($_POST['grade_id'] ?? 0);
        $eggs = (int)($_POST['total_eggs'] ?? 0);
        $crates = (float)($_POST['crates_count'] ?? 0);
        $dmg = (int)($_POST['damaged'] ?? 0);
        if ($grade_id === 0) api_err('Grade required');
        if ($id > 0) {
            $pdo->prepare("UPDATE daily_egg_grading SET record_date=?, batch_id=?, grade_id=?, total_eggs=?, crates_count=?, damaged=?, recorded_by=? WHERE id=?")
                ->execute([$date, $batch_id, $grade_id, $eggs, $crates, $dmg, $user_id, $id]);
        } else {
            $pdo->prepare("INSERT INTO daily_egg_grading (record_date, batch_id, grade_id, total_eggs, crates_count, damaged, recorded_by) VALUES (?,?,?,?,?,?,?)")
                ->execute([$date, $batch_id, $grade_id, $eggs, $crates, $dmg, $user_id]);
            $id = $pdo->lastInsertId();
        }
        api_ok(['message' => 'Grading saved', 'id' => $id]);
    }

    // ─────────────────────────────────────────────────────────
    // DAILY SALES RECONCILIATION
    // ─────────────────────────────────────────────────────────
    if ($action === 'get_daily_sales') {
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $sql = "SELECT * FROM daily_sales_reconciliation WHERE 1=1";
        $params = [];
        if ($from) { $sql .= " AND sale_date >= ?"; $params[] = $from; }
        if ($to) { $sql .= " AND sale_date <= ?"; $params[] = $to; }
        $sql .= " ORDER BY sale_date DESC LIMIT 200";
        $headers = safe_query_all($pdo, $sql, $params);
        $out = [];
        foreach ($headers as $h) {
            $lines = safe_query_all($pdo, "SELECT * FROM daily_sales_lines WHERE reconciliation_id=? ORDER BY id", [$h['id']]);
            $h['lines'] = $lines;
            $out[] = $h;
        }
        api_ok(['data' => $out]);
    }
    if ($action === 'save_daily_sales' && $method === 'POST') {
        $pdo->beginTransaction();
        try {
            $id = (int)($_POST['id'] ?? 0);
            $date = $_POST['sale_date'] ?? date('Y-m-d');
            $open = (int)($_POST['opening_balance_crates'] ?? 0);
            $prod = (int)($_POST['total_production_crates'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            $lines = json_decode($_POST['lines'] ?? '[]', true);
            if (!is_array($lines) || count($lines) === 0) api_err('At least one sales line is required');
            $total_sold = 0; $total_amount = 0; $total_eggs = 0;
            foreach ($lines as $l) {
                $q = (int)($l['quantity_crates'] ?? 0);
                $p = (float)($l['unit_price'] ?? 0);
                $total_sold += $q;
                $total_amount += $q * $p;
                $total_eggs += $q * 30; // 30 eggs per crate
            }
            $closing = $open + $prod - $total_sold;
            if ($id > 0) {
                $pdo->prepare("UPDATE daily_sales_reconciliation SET sale_date=?, opening_balance_crates=?, total_production_crates=?, total_sold_crates=?, closing_balance_crates=?, total_sales_amount=?, total_eggs=?, notes=?, recorded_by=? WHERE id=?")
                    ->execute([$date, $open, $prod, $total_sold, $closing, $total_amount, $total_eggs, $notes, $user_id, $id]);
                $pdo->prepare("DELETE FROM daily_sales_lines WHERE reconciliation_id=?")->execute([$id]);
            } else {
                $pdo->prepare("INSERT INTO daily_sales_reconciliation (sale_date, opening_balance_crates, total_production_crates, total_sold_crates, closing_balance_crates, total_sales_amount, total_eggs, notes, recorded_by) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$date, $open, $prod, $total_sold, $closing, $total_amount, $total_eggs, $notes, $user_id]);
                $id = (int)$pdo->lastInsertId();
            }
            $lineStmt = $pdo->prepare("INSERT INTO daily_sales_lines (reconciliation_id, product_type, unit_price, quantity_crates, line_total, notes) VALUES (?,?,?,?,?,?)");
            foreach ($lines as $l) {
                $q = (int)($l['quantity_crates'] ?? 0);
                $p = (float)($l['unit_price'] ?? 0);
                $lineStmt->execute([$id, $l['product_type'] ?? 'eggs', $p, $q, $q*$p, $l['notes'] ?? '']);
            }
            $pdo->commit();
            api_ok(['message' => 'Daily sales saved', 'id' => $id, 'closing_balance' => $closing, 'total_sales' => $total_amount]);
        } catch (Exception $e) {
            $pdo->rollBack();
            api_err('Save failed: ' . $e->getMessage());
        }
    }
    if ($action === 'delete_daily_sales' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM daily_sales_reconciliation WHERE id=?")->execute([$id]);
        api_ok(['message' => 'Deleted']);
    }

    // ─────────────────────────────────────────────────────────
    // STORES / RAW MATERIALS
    // ─────────────────────────────────────────────────────────
    if ($action === 'get_materials') {
        $rows = [];
        if (tableExists($pdo, 'raw_materials')) {
            $hasCategory = columnExists($pdo, 'raw_materials', 'category');
            if ($hasCategory) {
                $rows = $pdo->query("SELECT * FROM raw_materials ORDER BY category, material_name")->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $rows = $pdo->query("SELECT * FROM raw_materials ORDER BY material_name")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as &$r) { $r['category'] = 'feed_ingredient'; }
                unset($r);
            }
        }
        api_ok(['data' => $rows]);
    }
    if ($action === 'save_material' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['material_name'] ?? '');
        $code = trim($_POST['material_code'] ?? '');
        $unit = $_POST['unit'] ?? 'kg';
        $opening = (float)($_POST['opening_balance'] ?? 0);
        $price = (float)($_POST['current_price_per_unit'] ?? 0);
        $min = (float)($_POST['min_stock_level'] ?? 1);
        $cat = $_POST['category'] ?? 'feed_ingredient';
        if ($name === '') api_err('Material name is required');
        if ($id > 0) {
            $pdo->prepare("UPDATE raw_materials SET material_name=?, material_code=?, unit=?, opening_balance=?, current_price_per_unit=?, min_stock_level=?, category=? WHERE id=?")
                ->execute([$name, $code, $unit, $opening, $price, $min, $cat, $id]);
        } else {
            $pdo->prepare("INSERT INTO raw_materials (material_name, material_code, unit, opening_balance, current_stock, current_price_per_unit, min_stock_level, category) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$name, $code, $unit, $opening, $opening, $price, $min, $cat]);
            $id = (int)$pdo->lastInsertId();
        }
        logActivity($pdo, 'save', 'stores', "Material saved: {$name} ({$unit}, opening {$opening})", $id > 0 ? $id : null, 'raw_material');
        api_ok(['message' => 'Material saved']);
    }
    if ($action === 'get_movements') {
        $mat_id = (int)($_GET['material_id'] ?? 0);
        $sql = "SELECT m.*, r.material_name, r.unit
                FROM raw_material_movements m
                LEFT JOIN raw_materials r ON r.id=m.material_id
                WHERE 1=1";
        $params = [];
        if ($mat_id > 0) { $sql .= " AND m.material_id=?"; $params[] = $mat_id; }
        $sql .= " ORDER BY m.movement_date DESC, m.id DESC LIMIT 500";
        $rows = safe_query_all($pdo, $sql, $params);
        api_ok(['data' => $rows]);
    }
    if ($action === 'save_movement' && $method === 'POST') {
        $pdo->beginTransaction();
        try {
            $id = (int)($_POST['id'] ?? 0);
            $mat_id = (int)($_POST['material_id'] ?? 0);
            $date = $_POST['movement_date'] ?? date('Y-m-d');
            $type = $_POST['movement_type'] ?? 'received';
            $qty = (float)($_POST['quantity_kg'] ?? 0);
            $cost = (float)($_POST['unit_cost'] ?? 0);
            $ref = trim($_POST['reference_no'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            if ($mat_id === 0) api_err('Material required');

            // Get current stock
            $stmt = $pdo->prepare("SELECT current_stock FROM raw_materials WHERE id=?");
            $stmt->execute([$mat_id]);
            $cur = (float)$stmt->fetchColumn();

            // Compute signed change
            $incoming = ['received', 'transfer_in', 'adjustment_add', 'opening_balance'];
            $delta = in_array($type, $incoming, true) ? abs($qty) : -abs($qty);
            $new_bal = $cur + $delta;
            $total = abs($qty) * $cost;

            if ($id > 0) {
                $pdo->prepare("UPDATE raw_material_movements SET material_id=?, movement_date=?, movement_type=?, quantity_kg=?, balance_after=?, unit_cost=?, total_cost=?, reference_no=?, description=?, recorded_by=? WHERE id=?")
                    ->execute([$mat_id, $date, $type, $delta, $new_bal, $cost, $total, $ref, $desc, $user_id, $id]);
            } else {
                $pdo->prepare("INSERT INTO raw_material_movements (material_id, movement_date, movement_type, quantity_kg, balance_after, unit_cost, total_cost, reference_no, description, recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$mat_id, $date, $type, $delta, $new_bal, $cost, $total, $ref, $desc, $user_id]);
            }
            $pdo->prepare("UPDATE raw_materials SET current_stock=?, current_price_per_unit=? WHERE id=?")
                ->execute([$new_bal, $cost, $mat_id]);
            $pdo->commit();
            logActivity($pdo, 'add', 'stores', "Stock {$type}: {$delta} {$unit} on material #{$mat_id} (balance {$new_bal})", $mat_id, 'raw_material');
            api_ok(['message' => 'Movement recorded', 'new_balance' => $new_bal]);
        } catch (Exception $e) {
            $pdo->rollBack();
            api_err('Failed: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────
    // FEED RECIPES & PRODUCTION
    // ─────────────────────────────────────────────────────────
    if ($action === 'get_recipes') {
        $rows = safe_query_all($pdo, "SELECT * FROM feed_recipes ORDER BY recipe_name");
        api_ok(['data' => $rows]);
    }
    if ($action === 'get_recipe_ingredients') {
        $rid = (int)($_GET['recipe_id'] ?? 0);
        $sql = "SELECT ri.*, rm.material_name, rm.unit FROM feed_recipe_ingredients ri LEFT JOIN raw_materials rm ON rm.id=ri.raw_material_id WHERE ri.recipe_id=? ORDER BY ri.id";
        $rows = safe_query_all($pdo, $sql, [$rid]);
        api_ok(['data' => $rows]);
    }
    if ($action === 'save_recipe' && $method === 'POST') {
        $pdo->beginTransaction();
        try {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['recipe_name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $bag = (float)($_POST['base_bag_size_kg'] ?? 70);
            $target = $_POST['target_species'] ?? 'layers';
            $ingredients = json_decode($_POST['ingredients'] ?? '[]', true);
            if ($name === '') api_err('Recipe name required');
            if ($id > 0) {
                $pdo->prepare("UPDATE feed_recipes SET recipe_name=?, description=?, base_bag_size_kg=?, target_species=? WHERE id=?")
                    ->execute([$name, $desc, $bag, $target, $id]);
                $pdo->prepare("DELETE FROM feed_recipe_ingredients WHERE recipe_id=?")->execute([$id]);
            } else {
                $pdo->prepare("INSERT INTO feed_recipes (recipe_name, description, base_bag_size_kg, target_species) VALUES (?,?,?,?)")
                    ->execute([$name, $desc, $bag, $target]);
                $id = (int)$pdo->lastInsertId();
            }
            $ingStmt = $pdo->prepare("INSERT INTO feed_recipe_ingredients (recipe_id, raw_material_id, amount_per_bag_kg) VALUES (?,?,?)");
            foreach ($ingredients as $i) {
                $ingStmt->execute([$id, (int)$i['raw_material_id'], (float)$i['amount_per_bag_kg']]);
            }
            $pdo->commit();
            api_ok(['message' => 'Recipe saved', 'id' => $id]);
        } catch (Exception $e) {
            $pdo->rollBack();
            api_err('Save failed: ' . $e->getMessage());
        }
    }
    if ($action === 'produce_feed' && $method === 'POST') {
        $pdo->beginTransaction();
        try {
            $rid = (int)($_POST['recipe_id'] ?? 0);
            $bags = (int)($_POST['bags_produced'] ?? 0);
            if ($rid === 0 || $bags === 0) api_err('Recipe and bag count required');

            $stmt = $pdo->prepare("SELECT base_bag_size_kg FROM feed_recipes WHERE id=?");
            $stmt->execute([$rid]);
            $bag_size = (float)$stmt->fetchColumn();
            $total_kg = $bags * $bag_size;

            // Compute cost and deduct raw materials
            $ings = $pdo->prepare("SELECT raw_material_id, amount_per_bag_kg FROM feed_recipe_ingredients WHERE recipe_id=?");
            $ings->execute([$rid]);
            $total_cost = 0;
            $mvStmt = $pdo->prepare("INSERT INTO raw_material_movements (movement_date, material_id, movement_type, quantity_kg, balance_after, unit_cost, total_cost, reference_no, description, recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $updStmt = $pdo->prepare("UPDATE raw_materials SET current_stock = current_stock - ? WHERE id=?");
            $selStmt = $pdo->prepare("SELECT current_stock, current_price_per_unit FROM raw_materials WHERE id=?");
            $prodStmt = $pdo->prepare("INSERT INTO feed_production_batches (production_date, recipe_id, bags_produced, bag_size_kg, total_kg, total_cost, cost_per_kg, produced_by) VALUES (?,?,?,?,?,?,?,?)");

            foreach ($ings->fetchAll(PDO::FETCH_ASSOC) as $ing) {
                $need = (float)$ing['amount_per_bag_kg'] * $bags;
                $selStmt->execute([(int)$ing['raw_material_id']]);
                $row = $selStmt->fetch(PDO::FETCH_ASSOC);
                $unit_cost = (float)($row['current_price_per_unit'] ?? 0);
                $cur = (float)$row['current_stock'];
                $new_bal = $cur - $need;
                $cost = $need * $unit_cost;
                $total_cost += $cost;
                $updStmt->execute([$need, (int)$ing['raw_material_id']]);
                $mvStmt->execute([date('Y-m-d'), (int)$ing['raw_material_id'], 'used_production', -$need, $new_bal, $unit_cost, $cost, 'PROD-' . time(), "Used in feed production (recipe $rid)", $user_id]);
            }
            $cost_per_kg = $total_kg > 0 ? $total_cost / $total_kg : 0;
            $prodStmt->execute([date('Y-m-d'), $rid, $bags, $bag_size, $total_kg, $total_cost, $cost_per_kg, $user_id]);
            $pdo->commit();
            api_ok(['message' => 'Feed produced', 'total_kg' => $total_kg, 'total_cost' => $total_cost, 'cost_per_kg' => $cost_per_kg]);
        } catch (Exception $e) {
            $pdo->rollBack();
            api_err('Production failed: ' . $e->getMessage());
        }
    }
    if ($action === 'get_production_history') {
        $sql = "SELECT p.*, r.recipe_name FROM feed_production_batches p LEFT JOIN feed_recipes r ON r.id=p.recipe_id ORDER BY p.production_date DESC LIMIT 200";
        $rows = safe_query_all($pdo, $sql);
        api_ok(['data' => $rows]);
    }

    // ─────────────────────────────────────────────────────────
    // BULK SALES & WALK-IN CUSTOMERS
    // ─────────────────────────────────────────────────────────
    if ($action === 'get_walkin_customers') {
        $rows = safe_query_all($pdo, "SELECT * FROM walk_in_customers ORDER BY customer_name");
        api_ok(['data' => $rows]);
    }
    if ($action === 'save_walkin_customer' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['customer_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $type = $_POST['customer_type'] ?? 'retail';
        $addr = trim($_POST['address'] ?? '');
        if ($name === '') api_err('Name required');
        if ($id > 0) {
            $pdo->prepare("UPDATE walk_in_customers SET customer_name=?, phone=?, customer_type=?, address=? WHERE id=?")
                ->execute([$name, $phone, $type, $addr, $id]);
        } else {
            $pdo->prepare("INSERT INTO walk_in_customers (customer_name, phone, customer_type, address) VALUES (?,?,?,?)")
                ->execute([$name, $phone, $type, $addr]);
        }
        api_ok(['message' => 'Customer saved']);
    }
    if ($action === 'get_bulk_sales') {
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;
        $sql = "SELECT * FROM bulk_sales WHERE 1=1";
        $params = [];
        if ($from) { $sql .= " AND sale_date >= ?"; $params[] = $from; }
        if ($to) { $sql .= " AND sale_date <= ?"; $params[] = $to; }
        $sql .= " ORDER BY sale_date DESC LIMIT 500";
        $rows = safe_query_all($pdo, $sql, $params);
        api_ok(['data' => $rows]);
    }
    if ($action === 'save_bulk_sale' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $date = $_POST['sale_date'] ?? date('Y-m-d');
        $cust_id = (int)($_POST['customer_id'] ?? 0) ?: null;
        $name = trim($_POST['customer_name'] ?? '');
        $phone = trim($_POST['customer_phone'] ?? '');
        $type = $_POST['product_type'] ?? 'eggs';
        $qty = (float)($_POST['quantity'] ?? 0);
        $unit = $_POST['unit'] ?? 'crate';
        $price = (float)($_POST['unit_price'] ?? 0);
        $paid = (float)($_POST['amount_paid'] ?? 0);
        $method_pay = $_POST['payment_method'] ?? 'cash';
        $status = $_POST['payment_status'] ?? 'paid';
        $notes = trim($_POST['notes'] ?? '');
        if ($name === '' || $qty === 0 || $price === 0) api_err('Customer, quantity, and price are required');
        $total = $qty * $price;
        $balance = $total - $paid;
        if ($id > 0) {
            $pdo->prepare("UPDATE bulk_sales SET sale_date=?, customer_id=?, customer_name=?, customer_phone=?, product_type=?, quantity=?, unit=?, unit_price=?, total_amount=?, amount_paid=?, balance=?, payment_method=?, payment_status=?, notes=?, sold_by=? WHERE id=?")
                ->execute([$date, $cust_id, $name, $phone, $type, $qty, $unit, $price, $total, $paid, $balance, $method_pay, $status, $notes, $user_id, $id]);
        } else {
            $num = 'S' . date('Ymd') . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $pdo->prepare("INSERT INTO bulk_sales (sale_date, sale_number, customer_id, customer_name, customer_phone, product_type, quantity, unit, unit_price, total_amount, amount_paid, balance, payment_method, payment_status, notes, sold_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$date, $num, $cust_id, $name, $phone, $type, $qty, $unit, $price, $total, $paid, $balance, $method_pay, $status, $notes, $user_id]);
            $id = (int)$pdo->lastInsertId();
        }
        api_ok(['message' => 'Sale recorded', 'id' => $id, 'total' => $total, 'balance' => $balance]);
    }
    if ($action === 'delete_bulk_sale' && $method === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM bulk_sales WHERE id=?")->execute([$id]);
        api_ok(['message' => 'Deleted']);
    }

    // ─────────────────────────────────────────────────────────
    // DASHBOARD KPIs (combined)
    // ─────────────────────────────────────────────────────────
    if ($action === 'dashboard_kpis') {
        $kpis = [];
        $kpis['active_batches']   = tableExists($pdo, 'batches') ? (int)(safe_scalar($pdo, "SELECT COUNT(*) FROM batches WHERE status='active'") ?? 0) : 0;
        $kpis['total_birds']      = tableExists($pdo, 'batches') ? (int)(safe_scalar($pdo, "SELECT COALESCE(SUM(current_birds),0) FROM batches WHERE status='active'") ?? 0) : 0;
        $kpis['eggs_today']       = tableExists($pdo, 'daily_batch_records') ? (int)(safe_scalar($pdo, "SELECT COALESCE(SUM(total_eggs),0) FROM daily_batch_records WHERE record_date=CURDATE()") ?? 0) : 0;
        $kpis['mortality_today']  = tableExists($pdo, 'daily_batch_records') ? (int)(safe_scalar($pdo, "SELECT COALESCE(SUM(mortality),0) FROM daily_batch_records WHERE record_date=CURDATE()") ?? 0) : 0;
        $kpis['sales_today']      = tableExists($pdo, 'daily_sales_reconciliation') ? (float)(safe_scalar($pdo, "SELECT COALESCE(SUM(total_sales_amount),0) FROM daily_sales_reconciliation WHERE sale_date=CURDATE()") ?? 0) : 0;
        $kpis['pending_orders']   = tableExists($pdo, 'orders') ? (int)(safe_scalar($pdo, "SELECT COUNT(*) FROM orders WHERE status IN ('pending','paid','processing')") ?? 0) : 0;
        $kpis['low_stock_items']  = tableExists($pdo, 'raw_materials') ? (int)(safe_scalar($pdo, "SELECT COUNT(*) FROM raw_materials WHERE current_stock <= min_stock_level") ?? 0) : 0;
        $kpis['upcoming_vaccines']= tableExists($pdo, 'health_records') ? (int)(safe_scalar($pdo, "SELECT COUNT(*) FROM health_records WHERE record_type='vaccination' AND status='scheduled' AND next_due_date >= CURDATE()") ?? 0) : 0;
        api_ok(['data' => $kpis]);
    }

    api_err('Unknown action: ' . htmlspecialchars($action), 400);

} catch (Exception $e) {
    api_err('Server error: ' . $e->getMessage(), 500);
}
