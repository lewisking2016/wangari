<?php
/**
 * Wangari REST API v2 — comprehensive single entry point.
 * All frontend (Vercel) calls this API on the VPS backend.
 *
 * Usage: GET /api/v2.php?module=X&action=Y
 *        POST /api/v2.php?module=X&action=Y  with JSON body
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) { http_response_code(500); echo json_encode(['error' => 'Database connection failed']); exit; }

$module = $_GET['module'] ?? '';
$action = $_GET['action'] ?? 'list';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// ── Helper: run a paginated list query ──
function listQuery(PDO $pdo, string $sql, array $params = []): array {
    $limit = min((int)($_GET['limit'] ?? 200), 500);
    $stmt = $pdo->prepare("$sql ORDER BY 1 DESC LIMIT $limit");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Helper: generic save ──
function genericSave(PDO $pdo, string $table, array $fields, int $id = 0): int {
    if ($id > 0) {
        $sets = []; $vals = [];
        foreach ($fields as $k => $v) { $sets[] = "$k=?"; $vals[] = $v; }
        $vals[] = $id;
        $pdo->prepare("UPDATE $table SET " . implode(',', $sets) . " WHERE id=?")->execute($vals);
    } else {
        $cols = implode(',', array_keys($fields));
        $ph = implode(',', array_fill(0, count($fields), '?'));
        $pdo->prepare("INSERT INTO $table ($cols) VALUES ($ph)")->execute(array_values($fields));
        $id = (int) $pdo->lastInsertId();
    }
    return $id;
}

try {
    switch ($module) {

        // ════ DASHBOARD ════
        case 'dashboard':
            $today = date('Y-m-d');
            $d = [];
            $d['total_animals']   = (int) $pdo->query("SELECT COUNT(*) FROM animals WHERE status IN ('Active','alive')")->fetchColumn();
            $d['total_groups']    = (int) $pdo->query("SELECT COUNT(*) FROM animal_groups WHERE status='active'")->fetchColumn();
            $d['total_fields']    = (int) $pdo->query("SELECT COUNT(*) FROM fields")->fetchColumn();
            $d['active_plantings']= (int) $pdo->query("SELECT COUNT(*) FROM crop_plantings WHERE status='active'")->fetchColumn();
            $d['species_counts']  = $pdo->query("SELECT type AS species, COUNT(*) AS cnt FROM animals WHERE status IN ('Active','alive') GROUP BY type ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);
            $d['today_eggs']      = (int) $pdo->query("SELECT COALESCE(SUM(eggs_collected),0) FROM production_records WHERE record_date='$today'")->fetchColumn();
            $d['today_milk']      = (float)$pdo->query("SELECT COALESCE(SUM(milk_litres),0) FROM production_records WHERE record_date='$today'")->fetchColumn();
            $d['today_mortality'] = (int) $pdo->query("SELECT COALESCE(SUM(mortality),0) FROM production_records WHERE record_date='$today'")->fetchColumn();
            $d['upcoming_vaccinations'] = (int) $pdo->query("SELECT COUNT(*) FROM vaccinations WHERE status='scheduled' AND scheduled_date BETWEEN '$today' AND DATE_ADD('$today', INTERVAL 7 DAY)")->fetchColumn();
            $d['pending_births']  = (int) $pdo->query("SELECT COUNT(*) FROM breeding_records WHERE status='Pending' AND due_date BETWEEN '$today' AND DATE_ADD('$today', INTERVAL 14 DAY)")->fetchColumn();
            $d['total_users']     = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $d['total_revenue']   = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type='income' AND MONTH(record_date)=MONTH(CURDATE()) AND YEAR(record_date)=YEAR(CURDATE())")->fetchColumn();
            $d['total_expenses']  = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type='expense' AND MONTH(record_date)=MONTH(CURDATE()) AND YEAR(record_date)=YEAR(CURDATE())")->fetchColumn();
            $d['open_orders']     = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','processing')")->fetchColumn();
            $d['low_stock']       = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 10")->fetchColumn();
            $d['recent_health']   = $pdo->query("SELECT hr.*, a.name AS aname FROM health_records hr LEFT JOIN animals a ON hr.animal_id=a.id ORDER BY hr.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($d);
            break;

        // ════ ANIMALS ════
        case 'animals':
            if ($action === 'list') {
                $sp = $_GET['species'] ?? '';
                $sql = 'SELECT a.*, ag.name AS group_name FROM animals a LEFT JOIN animal_groups ag ON a.group_id=ag.id';
                if ($sp) $sql .= " WHERE a.type=" . $pdo->quote($sp);
                echo json_encode(listQuery($pdo, $sql));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'animals', [
                    'tag'=>$input['tag']??'', 'name'=>$input['name']??'', 'type'=>$input['type']??$input['species']??'Chicken',
                    'breed'=>$input['breed']??'', 'gender'=>$input['gender']??'female',
                    'birth_date'=>$input['birth_date']??null, 'status'=>$input['status']??'Active',
                    'group_id'=>(int)($input['group_id']??0)?:null, 'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'delete') {
                $pdo->prepare('DELETE FROM animals WHERE id=?')->execute([(int)($input['id']??0)]);
                echo json_encode(['success'=>true]);
            }
            break;

        // ════ GROUPS ════
        case 'groups':
            if ($action === 'list') {
                $sql = 'SELECT ag.*, h.house_name FROM animal_groups ag LEFT JOIN houses h ON ag.housing_id=h.id';
                echo json_encode(listQuery($pdo, $sql));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'animal_groups', [
                    'name'=>$input['name']??'', 'species'=>$input['species']??'Chicken',
                    'group_type'=>$input['group_type']??'flock', 'breed'=>$input['breed']??'',
                    'head_count'=>(int)($input['head_count']??0), 'housing_id'=>(int)($input['housing_id']??0)?:null,
                    'location'=>$input['location']??'', 'status'=>$input['status']??'active', 'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'delete') {
                $pdo->prepare('DELETE FROM animal_groups WHERE id=?')->execute([(int)($input['id']??0)]);
                echo json_encode(['success'=>true]);
            }
            break;

        // ════ HOUSING ════
        case 'housing':
            if ($action === 'list') {
                $sql = 'SELECT h.* FROM houses h WHERE h.is_active=1';
                echo json_encode(listQuery($pdo, $sql));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'houses', [
                    'house_name'=>$input['house_name']??'', 'house_code'=>$input['house_code']??'',
                    'location'=>$input['location']??'', 'capacity'=>(int)($input['capacity']??0),
                    'species'=>$input['species']??'Chicken', 'house_type'=>$input['house_type']??'house',
                    'description'=>$input['description']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ HEALTH ════
        case 'health':
            if ($action === 'list') {
                $sql = 'SELECT hr.*, a.name AS aname, a.tag FROM health_records hr LEFT JOIN animals a ON hr.animal_id=a.id';
                echo json_encode(listQuery($pdo, $sql));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'health_records', [
                    'record_date'=>$input['record_date']??date('Y-m-d'), 'subject'=>$input['subject']??'',
                    'record_type'=>$input['record_type']??'treatment', 'vaccine_name'=>$input['vaccine_name']??'',
                    'product_name'=>$input['product_name']??'', 'dosage'=>$input['dosage']??'',
                    'route'=>$input['route']??'oral', 'birds_treated'=>(int)($input['birds_treated']??0),
                    'mortality_count'=>(int)($input['mortality_count']??0), 'vet_name'=>$input['vet_name']??'',
                    'next_due_date'=>$input['next_due_date']??null, 'cost'=>(float)($input['cost']??0),
                    'status'=>$input['status']??'completed', 'notes'=>$input['notes']??'',
                    'species'=>$input['species']??'Chicken',
                    'animal_id'=>(int)($input['animal_id']??0)?:null,
                    'group_id'=>(int)($input['group_id']??0)?:null
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'delete') {
                $pdo->prepare('DELETE FROM health_records WHERE id=?')->execute([(int)($input['id']??0)]);
                echo json_encode(['success'=>true]);
            }
            break;

        // ════ VACCINATIONS ════
        case 'vaccinations':
            if ($action === 'list') {
                $sql = 'SELECT v.*, a.name AS aname, ag.name AS gname FROM vaccinations v LEFT JOIN animals a ON v.animal_id=a.id LEFT JOIN animal_groups ag ON v.group_id=ag.id';
                echo json_encode(listQuery($pdo, $sql));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $vaccDate = $input['scheduled_date'] ?? date('Y-m-d');
                $wd = (int)($input['withdrawal_days'] ?? 0);
                $wEnd = ($wd > 0 && $vaccDate) ? date('Y-m-d', strtotime("$vaccDate +{$wd} days")) : null;
                $fid = genericSave($pdo, 'vaccinations', [
                    'flock_id'=>(int)($input['flock_id']??0)?:null, 'vaccine_name'=>$input['vaccine_name']??'',
                    'scheduled_date'=>$vaccDate, 'administered_date'=>$input['administered_date']??null,
                    'status'=>$input['status']??'scheduled', 'dosage'=>$input['dosage']??'',
                    'notes'=>$input['notes']??'', 'cost'=>(float)($input['cost']??0),
                    'species'=>$input['species']??'Chicken',
                    'animal_id'=>(int)($input['animal_id']??0)?:null,
                    'group_id'=>(int)($input['group_id']??0)?:null,
                    'next_due_date'=>$input['next_due_date']??null,
                    'withdrawal_days'=>$wd, 'withdrawal_end_date'=>$wEnd
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'guides') {
                $sp = $_GET['species'] ?? '';
                $sql = 'SELECT * FROM vaccine_guides WHERE is_active=1';
                if ($sp) $sql .= " WHERE species=" . $pdo->quote($sp);
                echo json_encode(listQuery($pdo, $sql));
            }
            break;

        // ════ PRODUCTION ════
        case 'production':
            if ($action === 'list') {
                $sql = 'SELECT pr.*, ag.name AS gname FROM production_records pr LEFT JOIN animal_groups ag ON pr.group_id=ag.id';
                echo json_encode(listQuery($pdo, $sql));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'production_records', [
                    'flock_id'=>(int)($input['flock_id']??0)?:null,
                    'record_date'=>$input['record_date']??date('Y-m-d'),
                    'eggs_collected'=>(int)($input['eggs_collected']??0),
                    'cracked_eggs'=>(int)($input['cracked_eggs']??0),
                    'meat_weight_kg'=>(float)($input['meat_weight_kg']??0),
                    'mortality'=>(int)($input['mortality']??0),
                    'feed_consumed_kg'=>(float)($input['feed_consumed_kg']??0),
                    'notes'=>$input['notes']??'',
                    'species'=>$input['species']??'Chicken',
                    'group_id'=>(int)($input['group_id']??0)?:null,
                    'milk_litres'=>(float)($input['milk_litres']??0),
                    'weight_kg'=>(float)($input['weight_kg']??0),
                    'sold_count'=>(int)($input['sold_count']??0)
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ BREEDING ════
        case 'breeding':
            if ($action === 'list') {
                $sql = 'SELECT br.*, ad.name AS dam_name, asir.name AS sire_name FROM breeding_records br LEFT JOIN animals ad ON br.dam_id=ad.id LEFT JOIN animals asir ON br.sire_id=asir.id';
                echo json_encode(listQuery($pdo, $sql));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'breeding_records', [
                    'species'=>$input['species']??'Chicken', 'male_parent'=>$input['sire']??$input['male_parent']??'',
                    'type'=>$input['type']??'', 'date'=>$input['breeding_date']??$input['date']??date('Y-m-d'),
                    'due_date'=>$input['expected_birth']??$input['due_date']??null,
                    'status'=>$input['status']??'Pending', 'notes'=>$input['notes']??'',
                    'offspring_count'=>(int)($input['offspring_count']??0),
                    'dam_id'=>(int)($input['dam_id']??0)?:null,
                    'sire_id'=>(int)($input['sire_id']??0)?:null
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ FEEDING ════
        case 'feeding':
            if ($action === 'list') {
                $sql = 'SELECT fl.*, ag.name AS gname, a.name AS aname FROM feed_logs fl LEFT JOIN animal_groups ag ON fl.group_id=ag.id LEFT JOIN animals a ON fl.animal_id=a.id';
                echo json_encode(listQuery($pdo, $sql));
            } elseif ($action === 'standards') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM feeding_standards'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'feed_logs', [
                    'record_date'=>$input['record_date']??date('Y-m-d'),
                    'group_id'=>(int)($input['group_id']??0)?:null,
                    'animal_id'=>(int)($input['animal_id']??0)?:null,
                    'species'=>$input['species']??'Chicken',
                    'feed_type'=>$input['feed_type']??'',
                    'quantity_kg'=>(float)($input['quantity_kg']??0),
                    'cost'=>(float)($input['cost']??0),
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ MILKING ════
        case 'milking':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT mr.*, a.name AS animal_name FROM milking_records mr LEFT JOIN animals a ON mr.animal_id=a.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'milking_records', [
                    'animal_id'=>(int)($input['animal_id']??0)?:null,
                    'record_date'=>$input['record_date']??date('Y-m-d'),
                    'litres'=>(float)($input['litres']??0),
                    'fat_percentage'=>(float)($input['fat_percentage']??0),
                    'session'=>$input['session']??'morning',
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ MORTALITY ════
        case 'mortality':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT mr.*, a.name AS animal_name, ag.name AS group_name FROM mortality_records mr LEFT JOIN animals a ON mr.animal_id=a.id LEFT JOIN animal_groups ag ON mr.group_id=ag.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'mortality_records', [
                    'record_date'=>$input['record_date']??date('Y-m-d'),
                    'species'=>$input['species']??'Chicken',
                    'animal_id'=>(int)($input['animal_id']??0)?:null,
                    'group_id'=>(int)($input['group_id']??0)?:null,
                    'count'=>(int)($input['count']??1),
                    'cause'=>$input['cause']??'',
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ QUARANTINE ════
        case 'quarantine':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT qr.*, a.name AS animal_name, h.house_name FROM quarantine_records qr LEFT JOIN animals a ON qr.animal_id=a.id LEFT JOIN houses h ON qr.housing_id=h.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'quarantine_records', [
                    'animal_id'=>(int)($input['animal_id']??0)?:null,
                    'housing_id'=>(int)($input['housing_id']??0)?:null,
                    'reason'=>$input['reason']??'',
                    'start_date'=>$input['start_date']??date('Y-m-d'),
                    'end_date'=>$input['end_date']??null,
                    'status'=>$input['status']??'active',
                    'treatment'=>$input['treatment']??'',
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ WEIGHTS ════
        case 'weights':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT aw.*, a.name AS animal_name, a.tag FROM animal_weights aw LEFT JOIN animals a ON aw.animal_id=a.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'animal_weights', [
                    'animal_id'=>(int)($input['animal_id']??0)?:null,
                    'record_date'=>$input['record_date']??date('Y-m-d'),
                    'weight_kg'=>(float)($input['weight_kg']??0),
                    'body_condition_score'=>(float)($input['body_condition_score']??0),
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ BODY CONDITION ════
        case 'body_condition':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT bcs.*, a.name AS animal_name FROM body_condition_scores bcs LEFT JOIN animals a ON bcs.animal_id=a.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'body_condition_scores', [
                    'animal_id'=>(int)($input['animal_id']??0)?:null,
                    'record_date'=>$input['record_date']??date('Y-m-d'),
                    'score'=>(float)($input['score']??3),
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ PREVENTIVE CARE ════
        case 'preventive_care':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT pc.*, a.name AS animal_name FROM preventive_care pc LEFT JOIN animals a ON pc.animal_id=a.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'preventive_care', [
                    'species'=>$input['species']??'Chicken',
                    'care_type'=>$input['care_type']??'deworming',
                    'product_name'=>$input['product_name']??'',
                    'scheduled_date'=>$input['scheduled_date']??date('Y-m-d'),
                    'completed_date'=>$input['completed_date']??null,
                    'status'=>$input['status']??'scheduled',
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ TRANSPORT ════
        case 'transport':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT at.*, a.name AS animal_name FROM animal_transports at LEFT JOIN animals a ON at.animal_id=a.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'animal_transports', [
                    'animal_id'=>(int)($input['animal_id']??0)?:null,
                    'transport_date'=>$input['transport_date']??date('Y-m-d'),
                    'from_location'=>$input['from_location']??'',
                    'to_location'=>$input['to_location']??'',
                    'vehicle'=>$input['vehicle']??'',
                    'cost'=>(float)($input['cost']??0),
                    'reason'=>$input['reason']??'',
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ CROPS ════
        case 'crops':
            if ($action === 'list_fields') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM fields'));
            } elseif ($action === 'list_plantings') {
                echo json_encode(listQuery($pdo, 'SELECT cp.*, f.name AS field_name FROM crop_plantings cp LEFT JOIN fields f ON cp.field_id=f.id'));
            } elseif ($action === 'list_activities') {
                echo json_encode(listQuery($pdo, 'SELECT ca.*, cp.crop_name FROM crop_activities ca LEFT JOIN crop_plantings cp ON ca.planting_id=cp.id'));
            } elseif ($action === 'list_harvests') {
                echo json_encode(listQuery($pdo, 'SELECT ch.*, cp.crop_name, f.name AS field_name FROM crop_harvests ch LEFT JOIN crop_plantings cp ON ch.planting_id=cp.id LEFT JOIN fields f ON cp.field_id=f.id'));
            } elseif ($action === 'list_costs') {
                echo json_encode(listQuery($pdo, 'SELECT cc.*, cp.crop_name FROM crop_costs cc LEFT JOIN crop_plantings cp ON cc.planting_id=cp.id'));
            } elseif ($action === 'list_irrigation') {
                echo json_encode(listQuery($pdo, 'SELECT ir.*, cp.crop_name FROM irrigation_records ir LEFT JOIN crop_plantings cp ON ir.planting_id=cp.id'));
            } elseif ($action === 'list_pest_control') {
                echo json_encode(listQuery($pdo, 'SELECT pd.*, cp.crop_name FROM pest_disease_records pd LEFT JOIN crop_plantings cp ON pd.planting_id=cp.id'));
            } elseif ($action === 'list_soil') {
                echo json_encode(listQuery($pdo, 'SELECT sa.*, f.name AS field_name FROM soil_amendments sa LEFT JOIN fields f ON sa.field_id=f.id'));
            } elseif ($action === 'list_seeds') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM seed_inventory'));
            } elseif ($action === 'save_field') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'fields', [
                    'name'=>$input['name']??'', 'location'=>$input['location']??'',
                    'size_acres'=>(float)($input['size_acres']??0),
                    'soil_type'=>$input['soil_type']??'', 'description'=>$input['description']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'save_planting') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'crop_plantings', [
                    'field_id'=>(int)($input['field_id']??0), 'crop_name'=>$input['crop_name']??'',
                    'variety'=>$input['variety']??'', 'planting_date'=>$input['planting_date']??date('Y-m-d'),
                    'expected_harvest'=>$input['expected_harvest']??null,
                    'seed_quantity'=>(float)($input['seed_quantity']??0),
                    'seed_unit'=>$input['seed_unit']??'kg',
                    'area_planted'=>(float)($input['area_planted']??0),
                    'status'=>$input['status']??'active', 'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'save_harvest') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'crop_harvests', [
                    'planting_id'=>(int)($input['planting_id']??0),
                    'harvest_date'=>$input['harvest_date']??date('Y-m-d'),
                    'quantity'=>(float)($input['quantity']??0),
                    'unit'=>$input['unit']??'kg',
                    'quality_score'=>(float)($input['quality_score']??0),
                    'market_price'=>(float)($input['market_price']??0),
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'save_cost') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'crop_costs', [
                    'planting_id'=>(int)($input['planting_id']??0),
                    'cost_date'=>$input['cost_date']??date('Y-m-d'),
                    'category'=>$input['category']??'',
                    'amount'=>(float)($input['amount']??0),
                    'description'=>$input['description']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'save_irrigation') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'irrigation_records', [
                    'planting_id'=>(int)($input['planting_id']??0),
                    'record_date'=>$input['record_date']??date('Y-m-d'),
                    'method'=>$input['method']??'',
                    'duration_hours'=>(float)($input['duration_hours']??0),
                    'water_volume_litres'=>(float)($input['water_volume_litres']??0),
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'save_seed') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'seed_inventory', [
                    'crop_name'=>$input['crop_name']??'', 'variety'=>$input['variety']??'',
                    'quantity'=>(float)($input['quantity']??0),
                    'unit'=>$input['unit']??'kg',
                    'supplier'=>$input['supplier']??'',
                    'cost'=>(float)($input['cost']??0),
                    'purchase_date'=>$input['purchase_date']??null,
                    'expiry_date'=>$input['expiry_date']??null
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ FINANCE ════
        case 'finance':
            if ($action === 'list_orders') {
                echo json_encode(listQuery($pdo, 'SELECT o.*, c.full_name AS customer_name FROM orders o LEFT JOIN users c ON o.user_id=c.id'));
            } elseif ($action === 'list_transactions') {
                $type = $_GET['type'] ?? '';
                $sql = 'SELECT * FROM financial_records';
                if ($type) $sql .= " WHERE type=" . $pdo->quote($type);
                echo json_encode(listQuery($pdo, $sql));
            } elseif ($action === 'summary') {
                $month = $_GET['month'] ?? date('Y-m');
                $d = [];
                $d['income']  = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type='income' AND DATE_FORMAT(record_date,'%Y-%m')='$month'")->fetchColumn();
                $d['expense'] = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type='expense' AND DATE_FORMAT(record_date,'%Y-%m')='$month'")->fetchColumn();
                $d['profit']  = $d['income'] - $d['expense'];
                echo json_encode($d);
            } elseif ($action === 'save_order') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'orders', [
                    'user_id'=>(int)($input['user_id']??0)?:null,
                    'total_amount'=>(float)($input['total_amount']??0),
                    'status'=>$input['status']??'pending',
                    'payment_method'=>$input['payment_method']??'cash',
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'save_transaction') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'financial_records', [
                    'type'=>$input['type']??'expense', 'category'=>$input['category']??'',
                    'amount'=>(float)($input['amount']??0), 'description'=>$input['description']??'',
                    'record_date'=>$input['record_date']??date('Y-m-d'),
                    'payment_method'=>$input['payment_method']??'cash',
                    'reference'=>$input['reference']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'list_credit') {
                echo json_encode(listQuery($pdo, 'SELECT cc.*, u.full_name AS customer_name FROM customer_credits cc LEFT JOIN users u ON cc.user_id=u.id'));
            } elseif ($action === 'save_credit') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'customer_credits', [
                    'user_id'=>(int)($input['user_id']??0)?:null,
                    'amount'=>(float)($input['amount']??0),
                    'type'=>$input['type']??'credit',
                    'description'=>$input['description']??'',
                    'record_date'=>$input['record_date']??date('Y-m-d')
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'list_cashbook') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM cashbook_entries'));
            } elseif ($action === 'save_cashbook') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'cashbook_entries', [
                    'entry_date'=>$input['entry_date']??date('Y-m-d'),
                    'type'=>$input['type']??'income',
                    'category'=>$input['category']??'',
                    'amount'=>(float)($input['amount']??0),
                    'description'=>$input['description']??'',
                    'payment_method'=>$input['payment_method']??'cash'
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ INVENTORY ════
        case 'inventory':
            if ($action === 'list_products') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM products'));
            } elseif ($action === 'list_suppliers') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM suppliers'));
            } elseif ($action === 'list_stock') {
                echo json_encode(listQuery($pdo, 'SELECT rm.*, s.name AS supplier_name FROM raw_materials rm LEFT JOIN suppliers s ON rm.supplier_id=s.id'));
            } elseif ($action === 'list_equipment') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM farm_equipment'));
            } elseif ($action === 'list_feed_recipes') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM feed_recipes'));
            } elseif ($action === 'save_product') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'products', [
                    'name'=>$input['name']??'', 'category'=>$input['category']??'',
                    'unit'=>$input['unit']??'', 'price'=>(float)($input['price']??0),
                    'cost'=>(float)($input['cost']??0),
                    'stock_quantity'=>(int)($input['stock_quantity']??0),
                    'description'=>$input['description']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'save_supplier') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'suppliers', [
                    'name'=>$input['name']??'', 'contact_person'=>$input['contact_person']??'',
                    'phone'=>$input['phone']??'', 'email'=>$input['email']??'',
                    'address'=>$input['address']??'', 'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'save_equipment') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'farm_equipment', [
                    'name'=>$input['name']??'', 'category'=>$input['category']??'',
                    'purchase_date'=>$input['purchase_date']??null,
                    'purchase_cost'=>(float)($input['purchase_cost']??0),
                    'status'=>$input['status']??'active',
                    'location'=>$input['location']??'',
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ STAFF ════
        case 'staff':
            if ($action === 'list_workers') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM workers'));
            } elseif ($action === 'list_attendance') {
                echo json_encode(listQuery($pdo, 'SELECT wa.*, w.full_name AS worker_name FROM worker_attendance wa LEFT JOIN workers w ON wa.worker_id=w.id'));
            } elseif ($action === 'list_payments') {
                echo json_encode(listQuery($pdo, 'SELECT wp.*, w.full_name AS worker_name FROM worker_payments wp LEFT JOIN workers w ON wp.worker_id=w.id'));
            } elseif ($action === 'save_worker') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'workers', [
                    'full_name'=>$input['full_name']??'', 'phone'=>$input['phone']??'',
                    'role'=>$input['role']??'', 'department'=>$input['department']??'',
                    'daily_rate'=>(float)($input['daily_rate']??0),
                    'start_date'=>$input['start_date']??date('Y-m-d'),
                    'status'=>$input['status']??'active',
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'save_attendance') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'worker_attendance', [
                    'worker_id'=>(int)($input['worker_id']??0),
                    'date'=>$input['date']??date('Y-m-d'),
                    'status'=>$input['status']??'present',
                    'hours_worked'=>(float)($input['hours_worked']??8),
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'save_payment') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'worker_payments', [
                    'worker_id'=>(int)($input['worker_id']??0),
                    'payment_date'=>$input['payment_date']??date('Y-m-d'),
                    'amount'=>(float)($input['amount']??0),
                    'period'=>$input['period']??'',
                    'payment_method'=>$input['payment_method']??'cash',
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ CRM ════
        case 'crm':
            if ($action === 'list_contacts') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM crm_contacts'));
            } elseif ($action === 'list_segments') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM crm_segments'));
            } elseif ($action === 'list_followups') {
                echo json_encode(listQuery($pdo, 'SELECT cf.*, cc.name AS contact_name FROM crm_followups cf LEFT JOIN crm_contacts cc ON cf.contact_id=cc.id'));
            } elseif ($action === 'save_contact') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'crm_contacts', [
                    'name'=>$input['name']??'', 'phone'=>$input['phone']??'',
                    'email'=>$input['email']??'', 'company'=>$input['company']??'',
                    'type'=>$input['type']??'customer',
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ PURCHASE ORDERS ════
        case 'purchase_orders':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT po.*, s.name AS supplier_name FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id=s.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'purchase_orders', [
                    'supplier_id'=>(int)($input['supplier_id']??0),
                    'order_date'=>$input['order_date']??date('Y-m-d'),
                    'expected_date'=>$input['expected_date']??null,
                    'total_amount'=>(float)($input['total_amount']??0),
                    'status'=>$input['status']??'pending',
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ GROWTH MONITORING ════
        case 'growth':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT gm.*, ag.name AS group_name FROM growth_monitoring gm LEFT JOIN animal_groups ag ON gm.group_id=ag.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'growth_monitoring', [
                    'group_id'=>(int)($input['group_id']??0),
                    'record_date'=>$input['record_date']??date('Y-m-d'),
                    'average_weight_kg'=>(float)($input['average_weight_kg']??0),
                    'sample_size'=>(int)($input['sample_size']??0),
                    'age_days'=>(int)($input['age_days']??0),
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ USERS ════
        case 'users':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT id, username, email, role, full_name, is_active, created_at FROM users'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fields = [
                    'username'=>$input['username']??'', 'email'=>$input['email']??'',
                    'full_name'=>$input['full_name']??'', 'phone'=>$input['phone']??'',
                    'role'=>$input['role']??'user', 'is_active'=>(int)($input['is_active']??1)
                ];
                if (!empty($input['password'])) $fields['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
                $fid = genericSave($pdo, 'users', $fields, $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ SETTINGS ════
        case 'settings':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM settings'));
            } elseif ($action === 'save') {
                $key = $input['key'] ?? '';
                $val = $input['value'] ?? '';
                $exists = $pdo->query("SELECT COUNT(*) FROM settings WHERE setting_key=" . $pdo->quote($key))->fetchColumn();
                if ($exists) {
                    $pdo->prepare('UPDATE settings SET setting_value=? WHERE setting_key=?')->execute([$val, $key]);
                } else {
                    $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)')->execute([$key, $val]);
                }
                echo json_encode(['success'=>true]);
            }
            break;

        // ════ HELPERS ════
        case 'helpers':
            if ($action === 'species') {
                echo json_encode(['Chicken','Cattle','Goat','Sheep','Pig','Rabbit','Duck','Turkey','Guinea fowl','Donkey','Bee','Fish','Other']);
            } elseif ($action === 'dropdowns') {
                $d = [];
                $d['houses']   = $pdo->query('SELECT id, house_name, species FROM houses WHERE is_active=1 ORDER BY house_name')->fetchAll(PDO::FETCH_ASSOC);
                $d['groups']   = $pdo->query('SELECT id, name, species FROM animal_groups WHERE status="active" ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
                $d['animals']  = $pdo->query('SELECT id, name, tag, type AS species FROM animals ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
                $d['fields']   = $pdo->query('SELECT id, name FROM fields ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
                $d['plantings']= $pdo->query('SELECT id, crop_name, field_id FROM crop_plantings WHERE status="active" ORDER BY crop_name')->fetchAll(PDO::FETCH_ASSOC);
                $d['workers']  = $pdo->query('SELECT id, full_name FROM workers WHERE status="active" ORDER BY full_name')->fetchAll(PDO::FETCH_ASSOC);
                $d['suppliers']= $pdo->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($d);
            }
            break;

        // ════ FEED PRODUCTION ════
        case 'feed_production':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM feed_production_batches'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'feed_production_batches', [
                    'batch_date'=>$input['batch_date']??date('Y-m-d'),
                    'recipe_name'=>$input['recipe_name']??'',
                    'quantity_kg'=>(float)($input['quantity_kg']??0),
                    'cost'=>(float)($input['cost']??0),
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ EGGS / GRADING ════
        case 'egg_grading':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM daily_egg_grading'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'daily_egg_grading', [
                    'record_date'=>$input['record_date']??date('Y-m-d'),
                    'total_collected'=>(int)($input['total_collected']??0),
                    'grade_a'=>(int)($input['grade_a']??0),
                    'grade_b'=>(int)($input['grade_b']??0),
                    'grade_c'=>(int)($input['grade_c']??0),
                    'broken'=>(int)($input['broken']??0),
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ HATCHERY ════
        case 'hatchery':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM hatchery_batches'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'hatchery_batches', [
                    'batch_date'=>$input['batch_date']??date('Y-m-d'),
                    'eggs_set'=>(int)($input['eggs_set']??0),
                    'hatched'=>(int)($input['hatched']??0),
                    'dead_in_shell'=>(int)($input['dead_in_shell']??0),
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ LPO ════
        case 'lpo':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT ld.*, s.name AS supplier_name FROM lpo_documents ld LEFT JOIN suppliers s ON ld.supplier_id=s.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'lpo_documents', [
                    'lpo_number'=>$input['lpo_number']??'',
                    'supplier_id'=>(int)($input['supplier_id']??0),
                    'order_date'=>$input['order_date']??date('Y-m-d'),
                    'total_amount'=>(float)($input['total_amount']??0),
                    'status'=>$input['status']??'draft',
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ REMINDERS ════
        case 'reminders':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM reminders'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'reminders', [
                    'title'=>$input['title']??'', 'description'=>$input['description']??'',
                    'reminder_date'=>$input['reminder_date']??date('Y-m-d'),
                    'reminder_type'=>$input['reminder_type']??'general',
                    'priority'=>$input['priority']??'medium',
                    'status'=>$input['status']??'pending',
                    'is_recurring'=>(int)($input['is_recurring']??0)
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'error' => "Unknown module: $module",
                'available' => [
                    'dashboard', 'animals', 'groups', 'housing', 'health',
                    'vaccinations', 'production', 'breeding', 'feeding',
                    'milking', 'mortality', 'quarantine', 'weights',
                    'body_condition', 'preventive_care', 'transport',
                    'crops', 'finance', 'inventory', 'staff', 'crm',
                    'purchase_orders', 'growth', 'users', 'settings',
                    'helpers', 'feed_production', 'egg_grading',
                    'hatchery', 'lpo', 'reminders'
                ]
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
