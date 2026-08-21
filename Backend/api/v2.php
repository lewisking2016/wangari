<?php
/**
 * Wangari REST API v2 — single entry point for all admin operations.
 * All frontend pages on Vercel call this API on the VPS.
 * 
 * Usage: /api/v2.php?module=dashboard&action=get
 *         /api/v2.php?module=operations&action=list_animals
 *         POST with JSON body for mutations.
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$module = $_GET['module'] ?? '';
$action = $_GET['action'] ?? 'list';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// ══════ ROUTER ══════
try {
    switch ($module) {

        // ── DASHBOARD ──
        case 'dashboard':
            $today = date('Y-m-d');
            $weekAgo = date('Y-m-d', strtotime('-7 days'));
            $monthAgo = date('Y-m-d', strtotime('-30 days'));

            $data = [];
            $data['total_animals'] = (int) $pdo->query("SELECT COUNT(*) FROM animals WHERE status IN ('Active','alive')")->fetchColumn();
            $data['total_groups'] = (int) $pdo->query("SELECT COUNT(*) FROM animal_groups WHERE status='active'")->fetchColumn();
            $data['total_fields'] = (int) $pdo->query("SELECT COUNT(*) FROM fields")->fetchColumn();
            $data['active_plantings'] = (int) $pdo->query("SELECT COUNT(*) FROM crop_plantings WHERE status='active'")->fetchColumn();
            $data['species_counts'] = $pdo->query("SELECT type AS species, COUNT(*) AS cnt FROM animals WHERE status IN ('Active','alive') GROUP BY type ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);
            $data['today_eggs'] = (int) $pdo->query("SELECT COALESCE(SUM(eggs_collected),0) FROM production_records WHERE record_date='$today'")->fetchColumn();
            $data['today_milk'] = (float) $pdo->query("SELECT COALESCE(SUM(milk_litres),0) FROM production_records WHERE record_date='$today'")->fetchColumn();
            $data['today_mortality'] = (int) $pdo->query("SELECT COALESCE(SUM(mortality),0) FROM production_records WHERE record_date='$today'")->fetchColumn();
            $data['upcoming_vaccinations'] = (int) $pdo->query("SELECT COUNT(*) FROM vaccinations WHERE status='scheduled' AND scheduled_date BETWEEN '$today' AND DATE_ADD('$today', INTERVAL 7 DAY)")->fetchColumn();
            $data['pending_births'] = (int) $pdo->query("SELECT COUNT(*) FROM breeding_records WHERE status='Pending' AND due_date BETWEEN '$today' AND DATE_ADD('$today', INTERVAL 14 DAY)")->fetchColumn();
            $data['recent_health'] = $pdo->query("SELECT hr.*, a.name AS aname FROM health_records hr LEFT JOIN animals a ON hr.animal_id=a.id ORDER BY hr.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            $data['total_users'] = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            echo json_encode($data);
            break;

        // ── ANIMALS ──
        case 'animals':
            if ($action === 'list') {
                $sp = $_GET['species'] ?? '';
                $sql = 'SELECT a.*, ag.name AS group_name FROM animals a LEFT JOIN animal_groups ag ON a.group_id=ag.id';
                if ($sp) $sql .= " WHERE a.type=" . $pdo->quote($sp);
                $sql .= ' ORDER BY a.type, a.name';
                echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $v = [$input['tag']??'', $input['name']??'', $input['species']??'Chicken', $input['breed']??'', $input['gender']??'female', $input['birth_date']??null, $input['status']??'Active', (int)($input['group_id']??0)?:null, $input['notes']??''];
                if ($id > 0) {
                    $pdo->prepare('UPDATE animals SET tag=?,name=?,type=?,breed=?,gender=?,birth_date=?,status=?,group_id=?,notes=? WHERE id=?')->execute(array_merge($v, [$id]));
                } else {
                    $pdo->prepare('INSERT INTO animals (tag,name,type,breed,gender,birth_date,status,group_id,notes) VALUES (?,?,?,?,?,?,?,?,?)')->execute($v);
                    $id = (int) $pdo->lastInsertId();
                }
                echo json_encode(['success' => true, 'id' => $id]);
            } elseif ($action === 'delete') {
                $id = (int)($input['id'] ?? 0);
                $pdo->prepare('DELETE FROM animals WHERE id=?')->execute([$id]);
                echo json_encode(['success' => true]);
            }
            break;

        // ── GROUPS ──
        case 'groups':
            if ($action === 'list') {
                $sp = $_GET['species'] ?? '';
                $sql = 'SELECT ag.*, h.house_name FROM animal_groups ag LEFT JOIN houses h ON ag.housing_id=h.id';
                if ($sp) $sql .= " WHERE ag.species=" . $pdo->quote($sp);
                $sql .= ' ORDER BY ag.species, ag.name';
                echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $v = [$input['name']??'', $input['species']??'Chicken', $input['group_type']??'flock', $input['breed']??'', (int)($input['head_count']??0), (int)($input['housing_id']??0)?:null, $input['location']??'', $input['status']??'active', $input['notes']??''];
                if ($id > 0) {
                    $pdo->prepare('UPDATE animal_groups SET name=?,species=?,group_type=?,breed=?,head_count=?,housing_id=?,location=?,status=?,notes=? WHERE id=?')->execute(array_merge($v, [$id]));
                } else {
                    $pdo->prepare('INSERT INTO animal_groups (name,species,group_type,breed,head_count,housing_id,location,status,notes) VALUES (?,?,?,?,?,?,?,?,?)')->execute($v);
                    $id = (int) $pdo->lastInsertId();
                }
                echo json_encode(['success' => true, 'id' => $id]);
            } elseif ($action === 'delete') {
                $id = (int)($input['id'] ?? 0);
                $pdo->prepare('DELETE FROM animal_groups WHERE id=?')->execute([$id]);
                echo json_encode(['success' => true]);
            }
            break;

        // ── HOUSING ──
        case 'housing':
            if ($action === 'list') {
                $sp = $_GET['species'] ?? '';
                $sql = 'SELECT h.* FROM houses h WHERE h.is_active=1';
                if ($sp) $sql .= " AND h.species=" . $pdo->quote($sp);
                $sql .= ' ORDER BY h.species, h.house_name';
                echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $v = [$input['house_name']??'', $input['house_code']??'', $input['location']??'', (int)($input['capacity']??0), $input['species']??'Chicken', $input['house_type']??'house', $input['description']??''];
                if ($id > 0) {
                    $pdo->prepare('UPDATE houses SET house_name=?,house_code=?,location=?,capacity=?,species=?,house_type=?,description=? WHERE id=?')->execute(array_merge($v, [$id]));
                } else {
                    $pdo->prepare('INSERT INTO houses (house_name,house_code,location,capacity,species,house_type,description) VALUES (?,?,?,?,?,?,?)')->execute($v);
                    $id = (int) $pdo->lastInsertId();
                }
                echo json_encode(['success' => true, 'id' => $id]);
            }
            break;

        // ── HEALTH ──
        case 'health':
            if ($action === 'list') {
                $sp = $_GET['species'] ?? '';
                $sql = 'SELECT hr.*, a.name AS aname, a.tag, ag.name AS gname FROM health_records hr LEFT JOIN animals a ON hr.animal_id=a.id LEFT JOIN animal_groups ag ON hr.group_id=ag.id';
                if ($sp) $sql .= " WHERE hr.species=" . $pdo->quote($sp);
                $sql .= ' ORDER BY hr.record_date DESC LIMIT 200';
                echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $v = [$input['record_date']??date('Y-m-d'), $input['subject']??'', $input['record_type']??'treatment', $input['vaccine_name']??'', $input['product_name']??'', $input['dosage']??'', $input['route']??'oral', (int)($input['birds_treated']??0), (int)($input['mortality_count']??0), $input['vet_name']??'', $input['next_due_date']??null, (float)($input['cost']??0), $input['status']??'completed', $input['notes']??'', $input['species']??'Chicken', (int)($input['animal_id']??0)?:null, (int)($input['group_id']??0)?:null];
                if ($id > 0) {
                    $pdo->prepare('UPDATE health_records SET record_date=?,subject=?,record_type=?,vaccine_name=?,product_name=?,dosage=?,route=?,birds_treated=?,mortality_count=?,vet_name=?,next_due_date=?,cost=?,status=?,notes=?,species=?,animal_id=?,group_id=? WHERE id=?')->execute(array_merge($v, [$id]));
                } else {
                    $pdo->prepare('INSERT INTO health_records (record_date,subject,record_type,vaccine_name,product_name,dosage,route,birds_treated,mortality_count,vet_name,next_due_date,cost,status,notes,species,animal_id,group_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($v);
                    $id = (int) $pdo->lastInsertId();
                }
                echo json_encode(['success' => true, 'id' => $id]);
            } elseif ($action === 'delete') {
                $id = (int)($input['id'] ?? 0);
                $pdo->prepare('DELETE FROM health_records WHERE id=?')->execute([$id]);
                echo json_encode(['success' => true]);
            }
            break;

        // ── VACCINATIONS ──
        case 'vaccinations':
            if ($action === 'list') {
                $sp = $_GET['species'] ?? '';
                $sql = 'SELECT v.*, a.name AS aname, ag.name AS gname FROM vaccinations v LEFT JOIN animals a ON v.animal_id=a.id LEFT JOIN animal_groups ag ON v.group_id=ag.id';
                if ($sp) $sql .= " WHERE v.species=" . $pdo->quote($sp);
                $sql .= ' ORDER BY v.scheduled_date DESC LIMIT 200';
                echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $v = [(int)($input['flock_id']??0)?:null, $input['vaccine_name']??'', $input['scheduled_date']??date('Y-m-d'), $input['administered_date']??null, $input['status']??'scheduled', $input['dosage']??'', $input['notes']??'', (float)($input['cost']??0), $input['species']??'Chicken', (int)($input['animal_id']??0)?:null, (int)($input['group_id']??0)?:null, $input['next_due_date']??null, (int)($input['withdrawal_days']??0), null];
                if ($v[12] > 0 && $v[2]) $v[13] = date('Y-m-d', strtotime($v[2] . " +{$v[12]} days"));
                if ($id > 0) {
                    $pdo->prepare('UPDATE vaccinations SET flock_id=?,vaccine_name=?,scheduled_date=?,administered_date=?,status=?,dosage=?,notes=?,cost=?,species=?,animal_id=?,group_id=?,next_due_date=?,withdrawal_days=?,withdrawal_end_date=? WHERE id=?')->execute(array_merge($v, [$id]));
                } else {
                    $pdo->prepare('INSERT INTO vaccinations (flock_id,vaccine_name,scheduled_date,administered_date,status,dosage,notes,cost,species,animal_id,group_id,next_due_date,withdrawal_days,withdrawal_end_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($v);
                    $id = (int) $pdo->lastInsertId();
                }
                echo json_encode(['success' => true, 'id' => $id]);
            } elseif ($action === 'guides') {
                $sp = $_GET['species'] ?? '';
                $sql = 'SELECT * FROM vaccine_guides WHERE is_active=1';
                if ($sp) $sql .= " AND species=" . $pdo->quote($sp);
                $sql .= ' ORDER BY species, sort_order';
                echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            }
            break;

        // ── PRODUCTION ──
        case 'production':
            if ($action === 'list') {
                $sp = $_GET['species'] ?? '';
                $sql = 'SELECT pr.*, ag.name AS gname FROM production_records pr LEFT JOIN animal_groups ag ON pr.group_id=ag.id';
                if ($sp) $sql .= " WHERE pr.species=" . $pdo->quote($sp);
                $sql .= ' ORDER BY pr.record_date DESC LIMIT 200';
                echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $v = [(int)($input['flock_id']??0)?:null, $input['record_date']??date('Y-m-d'), (int)($input['eggs_collected']??0), (int)($input['cracked_eggs']??0), (float)($input['meat_weight_kg']??0), (int)($input['mortality']??0), (float)($input['feed_consumed_kg']??0), $input['notes']??'', $input['species']??'Chicken', (int)($input['group_id']??0)?:null, (float)($input['milk_litres']??0), (float)($input['weight_kg']??0), (int)($input['sold_count']??0)];
                if ($id > 0) {
                    $pdo->prepare('UPDATE production_records SET flock_id=?,record_date=?,eggs_collected=?,cracked_eggs=?,meat_weight_kg=?,mortality=?,feed_consumed_kg=?,notes=?,species=?,group_id=?,milk_litres=?,weight_kg=?,sold_count=? WHERE id=?')->execute(array_merge($v, [$id]));
                } else {
                    $pdo->prepare('INSERT INTO production_records (flock_id,record_date,eggs_collected,cracked_eggs,meat_weight_kg,mortality,feed_consumed_kg,notes,species,group_id,milk_litres,weight_kg,sold_count) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($v);
                    $id = (int) $pdo->lastInsertId();
                }
                echo json_encode(['success' => true, 'id' => $id]);
            }
            break;

        // ── BREEDING ──
        case 'breeding':
            if ($action === 'list') {
                $sp = $_GET['species'] ?? '';
                $sql = 'SELECT * FROM breeding_records';
                if ($sp) $sql .= " WHERE species=" . $pdo->quote($sp);
                $sql .= ' ORDER BY date DESC LIMIT 200';
                echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $v = [$input['species']??'Chicken', $input['dam']??'', $input['sire']??'', $input['breeding_date']??date('Y-m-d'), $input['expected_birth']??null, $input['status']??'Pending', $input['notes']??'', (int)($input['offspring_count']??0), (int)($input['dam_id']??0)?:null, (int)($input['sire_id']??0)?:null];
                if ($id > 0) {
                    $pdo->prepare('UPDATE breeding_records SET species=?,male_parent=?,type=?,date=?,due_date=?,status=?,notes=?,offspring_count=?,dam_id=?,sire_id=? WHERE id=?')->execute(array_merge($v, [$id]));
                } else {
                    $pdo->prepare('INSERT INTO breeding_records (species,male_parent,type,date,due_date,status,notes,offspring_count,dam_id,sire_id) VALUES (?,?,?,?,?,?,?,?,?,?)')->execute($v);
                    $id = (int) $pdo->lastInsertId();
                }
                echo json_encode(['success' => true, 'id' => $id]);
            }
            break;

        // ── FEEDING ──
        case 'feeding':
            if ($action === 'list') {
                $sp = $_GET['species'] ?? '';
                $sql = 'SELECT fl.*, ag.name AS gname, a.name AS aname FROM feed_logs fl LEFT JOIN animal_groups ag ON fl.group_id=ag.id LEFT JOIN animals a ON fl.animal_id=a.id';
                if ($sp) $sql .= " WHERE fl.species=" . $pdo->quote($sp);
                $sql .= ' ORDER BY fl.record_date DESC LIMIT 200';
                echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'standards') {
                $sp = $_GET['species'] ?? '';
                $sql = 'SELECT * FROM feeding_standards';
                if ($sp) $sql .= " WHERE species=" . $pdo->quote($sp);
                $sql .= ' ORDER BY species, week_number';
                echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $v = [$input['record_date']??date('Y-m-d'), (int)($input['group_id']??0)?:null, (int)($input['animal_id']??0)?:null, $input['species']??'Chicken', $input['feed_type']??'', (float)($input['quantity_kg']??0), (float)($input['cost']??0), $input['notes']??'', (int)($_SESSION['user_id']??0)];
                if ($id > 0) {
                    $pdo->prepare('UPDATE feed_logs SET record_date=?,group_id=?,animal_id=?,species=?,feed_type=?,quantity_kg=?,cost=?,notes=?,recorded_by=? WHERE id=?')->execute(array_merge($v, [$id]));
                } else {
                    $pdo->prepare('INSERT INTO feed_logs (record_date,group_id,animal_id,species,feed_type,quantity_kg,cost,notes,recorded_by) VALUES (?,?,?,?,?,?,?,?,?)')->execute($v);
                    $id = (int) $pdo->lastInsertId();
                }
                echo json_encode(['success' => true, 'id' => $id]);
            }
            break;

        // ── CROPS ──
        case 'crops':
            if ($action === 'list_fields') {
                echo json_encode($pdo->query('SELECT * FROM fields ORDER BY name')->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'list_plantings') {
                echo json_encode($pdo->query('SELECT cp.*, f.name AS field_name FROM crop_plantings cp LEFT JOIN fields f ON cp.field_id=f.id ORDER BY cp.planting_date DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'list_activities') {
                echo json_encode($pdo->query('SELECT ca.*, cp.crop_name FROM crop_activities ca LEFT JOIN crop_plantings cp ON ca.planting_id=cp.id ORDER BY ca.activity_date DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'list_harvests') {
                echo json_encode($pdo->query('SELECT ch.*, cp.crop_name, f.name AS field_name FROM crop_harvests ch LEFT JOIN crop_plantings cp ON ch.planting_id=cp.id LEFT JOIN fields f ON cp.field_id=f.id ORDER BY ch.harvest_date DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'save_field') {
                $id = (int)($input['id'] ?? 0);
                $v = [$input['name']??'', $input['location']??'', (float)($input['size_acres']??0), $input['soil_type']??'', $input['description']??''];
                if ($id > 0) {
                    $pdo->prepare('UPDATE fields SET name=?,location=?,size_acres=?,soil_type=?,description=? WHERE id=?')->execute(array_merge($v, [$id]));
                } else {
                    $pdo->prepare('INSERT INTO fields (name,location,size_acres,soil_type,description) VALUES (?,?,?,?,?)')->execute($v);
                    $id = (int) $pdo->lastInsertId();
                }
                echo json_encode(['success' => true, 'id' => $id]);
            } elseif ($action === 'save_planting') {
                $id = (int)($input['id'] ?? 0);
                $v = [(int)($input['field_id']??0), $input['crop_name']??'', $input['variety']??'', $input['planting_date']??date('Y-m-d'), $input['expected_harvest']??null, (float)($input['seed_quantity']??0), $input['seed_unit']??'kg', (float)($input['area_planted']??0), $input['status']??'active', $input['notes']??''];
                if ($id > 0) {
                    $pdo->prepare('UPDATE crop_plantings SET field_id=?,crop_name=?,variety=?,planting_date=?,expected_harvest=?,seed_quantity=?,seed_unit=?,area_planted=?,status=?,notes=? WHERE id=?')->execute(array_merge($v, [$id]));
                } else {
                    $pdo->prepare('INSERT INTO crop_plantings (field_id,crop_name,variety,planting_date,expected_harvest,seed_quantity,seed_unit,area_planted,status,notes) VALUES (?,?,?,?,?,?,?,?,?,?)')->execute($v);
                    $id = (int) $pdo->lastInsertId();
                }
                echo json_encode(['success' => true, 'id' => $id]);
            } elseif ($action === 'save_harvest') {
                $id = (int)($input['id'] ?? 0);
                $v = [(int)($input['planting_id']??0), $input['harvest_date']??date('Y-m-d'), (float)($input['quantity']??0), $input['unit']??'kg', (float)($input['quality_score']??0), (float)($input['market_price']??0), $input['notes']??''];
                if ($id > 0) {
                    $pdo->prepare('UPDATE crop_harvests SET planting_id=?,harvest_date=?,quantity=?,unit=?,quality_score=?,market_price=?,notes=? WHERE id=?')->execute(array_merge($v, [$id]));
                } else {
                    $pdo->prepare('INSERT INTO crop_harvests (planting_id,harvest_date,quantity,unit,quality_score,market_price,notes) VALUES (?,?,?,?,?,?,?)')->execute($v);
                    $id = (int) $pdo->lastInsertId();
                }
                echo json_encode(['success' => true, 'id' => $id]);
            }
            break;

        // ── FINANCE ──
        case 'finance':
            if ($action === 'list_orders') {
                echo json_encode($pdo->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'list_transactions') {
                $type = $_GET['type'] ?? '';
                $sql = 'SELECT * FROM financial_records';
                if ($type) $sql .= " WHERE type=" . $pdo->quote($type);
                $sql .= ' ORDER BY record_date DESC LIMIT 200';
                echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'summary') {
                $month = $_GET['month'] ?? date('Y-m');
                $data = [];
                $data['income'] = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type='income' AND DATE_FORMAT(record_date,'%Y-%m')='$month'")->fetchColumn();
                $data['expense'] = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type='expense' AND DATE_FORMAT(record_date,'%Y-%m')='$month'")->fetchColumn();
                $data['profit'] = $data['income'] - $data['expense'];
                echo json_encode($data);
            } elseif ($action === 'save_transaction') {
                $id = (int)($input['id'] ?? 0);
                $v = [$input['type']??'expense', $input['category']??'', (float)($input['amount']??0), $input['description']??'', $input['record_date']??date('Y-m-d'), $input['payment_method']??'cash', $input['reference']??'', (int)($_SESSION['user_id']??0)];
                if ($id > 0) {
                    $pdo->prepare('UPDATE financial_records SET type=?,category=?,amount=?,description=?,record_date=?,payment_method=?,reference=?,recorded_by=? WHERE id=?')->execute(array_merge($v, [$id]));
                } else {
                    $pdo->prepare('INSERT INTO financial_records (type,category,amount,description,record_date,payment_method,reference,recorded_by) VALUES (?,?,?,?,?,?,?,?)')->execute($v);
                    $id = (int) $pdo->lastInsertId();
                }
                echo json_encode(['success' => true, 'id' => $id]);
            }
            break;

        // ── INVENTORY ──
        case 'inventory':
            if ($action === 'list_products') {
                echo json_encode($pdo->query('SELECT * FROM products ORDER BY name')->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'list_suppliers') {
                echo json_encode($pdo->query('SELECT * FROM suppliers ORDER BY name')->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'list_stock') {
                echo json_encode($pdo->query('SELECT rm.*, s.name AS supplier_name FROM raw_materials rm LEFT JOIN suppliers s ON rm.supplier_id=s.id ORDER BY rm.name')->fetchAll(PDO::FETCH_ASSOC));
            } elseif ($action === 'save_product') {
                $id = (int)($input['id'] ?? 0);
                $v = [$input['name']??'', $input['category']??'', $input['unit']??'', (float)($input['price']??0), (float)($input['cost']??0), (int)($input['stock_quantity']??0), $input['description']??''];
                if ($id > 0) {
                    $pdo->prepare('UPDATE products SET name=?,category=?,unit=?,price=?,cost=?,stock_quantity=?,description=? WHERE id=?')->execute(array_merge($v, [$id]));
                } else {
                    $pdo->prepare('INSERT INTO products (name,category,unit,price,cost,stock_quantity,description) VALUES (?,?,?,?,?,?,?)')->execute($v);
                    $id = (int) $pdo->lastInsertId();
                }
                echo json_encode(['success' => true, 'id' => $id]);
            }
            break;

        // ── USERS ──
        case 'users':
            if ($action === 'list') {
                echo json_encode($pdo->query('SELECT id, username, email, role, full_name, is_active, created_at FROM users ORDER BY username')->fetchAll(PDO::FETCH_ASSOC));
            }
            break;

        // ── HELPERS ──
        case 'helpers':
            if ($action === 'species') {
                echo json_encode(['Chicken','Cattle','Goat','Sheep','Pig','Rabbit','Duck','Turkey','Guinea fowl','Donkey','Bee','Fish','Other']);
            } elseif ($action === 'dropdowns') {
                $data = [];
                $data['houses'] = $pdo->query('SELECT id, house_name, species FROM houses WHERE is_active=1 ORDER BY house_name')->fetchAll(PDO::FETCH_ASSOC);
                $data['groups'] = $pdo->query('SELECT id, name, species FROM animal_groups WHERE status="active" ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
                $data['animals'] = $pdo->query('SELECT id, name, tag, type AS species FROM animals ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
                $data['fields'] = $pdo->query('SELECT id, name FROM fields ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
                $data['plantings'] = $pdo->query('SELECT id, crop_name, field_id FROM crop_plantings WHERE status="active" ORDER BY crop_name')->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($data);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => "Unknown module: $module", 'available' => ['dashboard','animals','groups','housing','health','vaccinations','production','breeding','feeding','crops','finance','inventory','users','helpers']]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
