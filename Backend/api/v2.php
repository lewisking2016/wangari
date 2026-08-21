<?php
/**
 * Wangari REST API v2 — single entry point.
 * All frontend on Vercel calls this API on the VPS.
 * Columns verified against actual DB schema.
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

function listQuery(PDO $pdo, string $sql, array $params = []): array {
    $limit = min((int)($_GET['limit'] ?? 200), 500);
    $stmt = $pdo->prepare("$sql ORDER BY 1 DESC LIMIT $limit");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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
            $d['total_revenue']   = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type='income' AND MONTH(transaction_date)=MONTH(CURDATE()) AND YEAR(transaction_date)=YEAR(CURDATE())")->fetchColumn();
            $d['total_expenses']  = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type='expense' AND MONTH(transaction_date)=MONTH(CURDATE()) AND YEAR(transaction_date)=YEAR(CURDATE())")->fetchColumn();
            $d['open_orders']     = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','paid')")->fetchColumn();
            $d['low_stock']       = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 10")->fetchColumn();
            $d['recent_health']   = $pdo->query("SELECT hr.*, a.name AS aname FROM health_records hr LEFT JOIN animals a ON hr.animal_id=a.id ORDER BY hr.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($d);
            break;

        // ════ ANIMALS ════
        case 'animals':
            if ($action === 'list') {
                $sql = 'SELECT a.*, ag.name AS group_name FROM animals a LEFT JOIN animal_groups ag ON a.group_id=ag.id';
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
                echo json_encode(listQuery($pdo, 'SELECT h.* FROM houses h WHERE h.is_active=1'));
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
                echo json_encode(listQuery($pdo, 'SELECT * FROM vaccine_guides WHERE is_active=1'));
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
                echo json_encode(listQuery($pdo, 'SELECT br.*, ad.name AS dam_name, asir.name AS sire_name FROM breeding_records br LEFT JOIN animals ad ON br.dam_id=ad.id LEFT JOIN animals asir ON br.sire_id=asir.id'));
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

        // ════ MILKING ════ (uses milking_date, fat_pct, milking_time)
        case 'milking':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT mr.*, a.name AS animal_name FROM milking_records mr LEFT JOIN animals a ON mr.animal_id=a.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'milking_records', [
                    'animal_id'=>(int)($input['animal_id']??0)?:null,
                    'species'=>$input['species']??'Cattle',
                    'milking_date'=>$input['record_date']??$input['milking_date']??date('Y-m-d'),
                    'milking_time'=>$input['session']??$input['milking_time']??'morning',
                    'litres'=>(float)($input['litres']??0),
                    'fat_pct'=>(float)($input['fat_percentage']??$input['fat_pct']??0),
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ MORTALITY ════ (uses death_date)
        case 'mortality':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT mr.*, a.name AS animal_name, ag.name AS group_name FROM mortality_records mr LEFT JOIN animals a ON mr.animal_id=a.id LEFT JOIN animal_groups ag ON mr.group_id=ag.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'mortality_records', [
                    'death_date'=>$input['record_date']??$input['death_date']??date('Y-m-d'),
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

        // ════ QUARANTINE ════ (uses quarantine_start, quarantine_end, treatment_given, diagnosis)
        case 'quarantine':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT qr.*, a.name AS animal_name FROM quarantine_records qr LEFT JOIN animals a ON qr.animal_id=a.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'quarantine_records', [
                    'animal_id'=>(int)($input['animal_id']??0)?:null,
                    'species'=>$input['species']??'',
                    'quarantine_start'=>$input['start_date']??$input['quarantine_start']??date('Y-m-d'),
                    'quarantine_end'=>$input['end_date']??$input['quarantine_end']??null,
                    'reason'=>$input['reason']??'',
                    'status'=>$input['status']??'active',
                    'treatment_given'=>$input['treatment']??$input['treatment_given']??'',
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ WEIGHTS ════ (uses recorded_date)
        case 'weights':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT aw.*, a.name AS animal_name, a.tag FROM animal_weights aw LEFT JOIN animals a ON aw.animal_id=a.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'animal_weights', [
                    'animal_id'=>(int)($input['animal_id']??0)?:null,
                    'species'=>$input['species']??'',
                    'recorded_date'=>$input['record_date']??$input['recorded_date']??date('Y-m-d'),
                    'weight_kg'=>(float)($input['weight_kg']??0),
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ BODY CONDITION ════ (uses score_date)
        case 'body_condition':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT bcs.*, a.name AS animal_name FROM body_condition_scores bcs LEFT JOIN animals a ON bcs.animal_id=a.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'body_condition_scores', [
                    'animal_id'=>(int)($input['animal_id']??0)?:null,
                    'species'=>$input['species']??'',
                    'score_date'=>$input['record_date']??$input['score_date']??date('Y-m-d'),
                    'score'=>(float)($input['score']??3),
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ PREVENTIVE CARE ════ (uses next_due, cost_per_event, last_done)
        case 'preventive_care':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM preventive_care'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'preventive_care', [
                    'species'=>$input['species']??'Chicken',
                    'care_type'=>$input['care_type']??'deworming',
                    'target_group'=>$input['target_group']??'',
                    'frequency'=>$input['frequency']??'',
                    'next_due'=>$input['scheduled_date']??$input['next_due']??date('Y-m-d'),
                    'cost_per_event'=>(float)($input['cost']??$input['cost_per_event']??0),
                    'responsible_person'=>$input['vet_name']??$input['responsible_person']??'',
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ TRANSPORT ════ (uses transport_cost, transporter_name)
        case 'transport':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM animal_transports'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'animal_transports', [
                    'transport_date'=>$input['record_date']??$input['transport_date']??date('Y-m-d'),
                    'species'=>$input['species']??'',
                    'animal_count'=>(int)($input['animal_count']??1),
                    'from_location'=>$input['from_location']??'',
                    'to_location'=>$input['to_location']??'',
                    'transporter_name'=>$input['vehicle']??$input['transporter_name']??'',
                    'transport_cost'=>(float)($input['cost']??$input['transport_cost']??0),
                    'reason'=>$input['reason']??'',
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ GROWTH MONITORING ════ (uses monitoring_date, planting_id)
        case 'growth':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT gm.*, cp.crop_name FROM growth_monitoring gm LEFT JOIN crop_plantings cp ON gm.planting_id=cp.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'growth_monitoring', [
                    'planting_id'=>(int)($input['planting_id']??0),
                    'monitoring_date'=>$input['record_date']??$input['monitoring_date']??date('Y-m-d'),
                    'growth_stage'=>$input['growth_stage']??'',
                    'plant_height_cm'=>(float)($input['plant_height_cm']??0),
                    'general_health'=>$input['general_health']??'good',
                    'observations'=>$input['notes']??$input['observations']??''
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
                    'cost_type'=>$input['category']??$input['cost_type']??'',
                    'amount'=>(float)($input['amount']??0),
                    'description'=>$input['description']??'',
                    'cost_date'=>$input['cost_date']??date('Y-m-d')
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'save_irrigation') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'irrigation_records', [
                    'planting_id'=>(int)($input['planting_id']??0)?:null,
                    'field_id'=>(int)($input['field_id']??0)?:null,
                    'irrigation_date'=>$input['record_date']??$input['irrigation_date']??date('Y-m-d'),
                    'method'=>$input['method']??'manual',
                    'duration_hours'=>(float)($input['duration_hours']??0),
                    'water_volume_m3'=>(float)($input['water_volume_litres']??$input['water_volume_m3']??0),
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'save_seed') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'seed_inventory', [
                    'seed_name'=>$input['crop_name']??$input['seed_name']??'',
                    'variety'=>$input['variety']??'',
                    'quantity_kg'=>(float)($input['quantity']??$input['quantity_kg']??0),
                    'supplier'=>$input['supplier']??'',
                    'cost_per_kg'=>(float)($input['cost']??$input['cost_per_kg']??0),
                    'purchase_date'=>$input['purchase_date']??null,
                    'expiry_date'=>$input['expiry_date']??null
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ FINANCE ════ (financial_records uses transaction_date)
        case 'finance':
            if ($action === 'list_orders') {
                echo json_encode(listQuery($pdo, 'SELECT o.*, u.full_name AS customer_name FROM orders o LEFT JOIN users c ON o.user_id=c.id'));
            } elseif ($action === 'list_transactions') {
                $type = $_GET['type'] ?? '';
                $sql = 'SELECT * FROM financial_records';
                if ($type) $sql .= " WHERE type=" . $pdo->quote($type);
                echo json_encode(listQuery($pdo, $sql));
            } elseif ($action === 'summary') {
                $month = $_GET['month'] ?? date('Y-m');
                $d = [];
                $d['income']  = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type='income' AND DATE_FORMAT(transaction_date,'%Y-%m')='$month'")->fetchColumn();
                $d['expense'] = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM financial_records WHERE type='expense' AND DATE_FORMAT(transaction_date,'%Y-%m')='$month'")->fetchColumn();
                $d['profit']  = $d['income'] - $d['expense'];
                echo json_encode($d);
            } elseif ($action === 'save_transaction') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'financial_records', [
                    'type'=>$input['type']??'expense', 'category'=>$input['category']??'',
                    'amount'=>(float)($input['amount']??0), 'description'=>$input['description']??'',
                    'transaction_date'=>$input['record_date']??$input['transaction_date']??date('Y-m-d'),
                    'payment_method'=>$input['payment_method']??'cash'
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'list_credit') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM customer_credits'));
            } elseif ($action === 'save_credit') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'customer_credits', [
                    'customer_name'=>$input['customer_name']??'',
                    'customer_phone'=>$input['phone']??'',
                    'credit_date'=>$input['record_date']??$input['credit_date']??date('Y-m-d'),
                    'item_description'=>$input['description']??$input['item_description']??'',
                    'total_amount'=>(float)($input['amount']??$input['total_amount']??0),
                    'balance'=>(float)($input['amount']??$input['total_amount']??0),
                    'status'=>$input['status']??'unpaid'
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            } elseif ($action === 'list_cashbook') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM cashbook_entries'));
            } elseif ($action === 'save_cashbook') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'cashbook_entries', [
                    'entry_date'=>$input['record_date']??$input['entry_date']??date('Y-m-d'),
                    'direction'=>$input['type']??$input['direction']??'out',
                    'money_source'=>$input['category']??$input['money_source']??'other_out',
                    'amount'=>(float)($input['amount']??0),
                    'description'=>$input['description']??''
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

        // ════ LPO ════ (uses doc_number, doc_type)
        case 'lpo':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM lpo_documents'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'lpo_documents', [
                    'doc_number'=>$input['lpo_number']??$input['doc_number']??'',
                    'doc_type'=>$input['doc_type']??'lpo',
                    'customer_name'=>$input['customer_name']??'',
                    'issue_date'=>$input['order_date']??$input['issue_date']??date('Y-m-d'),
                    'total_amount'=>(float)($input['total_amount']??0),
                    'status'=>$input['status']??'draft',
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
                    'total_amount'=>(float)($input['total_amount']??0),
                    'status'=>$input['status']??'pending',
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ EGG GRADING ════ (uses grade_id, batch_id, total_eggs, damaged)
        case 'egg_grading':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM daily_egg_grading'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'daily_egg_grading', [
                    'record_date'=>$input['record_date']??date('Y-m-d'),
                    'total_eggs'=>$input['total_collected']??$input['total_eggs']??0,
                    'damaged'=>$input['broken']??$input['damaged']??0,
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ HATCHERY ════ (uses setting_date, chicks_hatched, fertile_eggs)
        case 'hatchery':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT * FROM hatchery_batches'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'hatchery_batches', [
                    'setting_date'=>$input['batch_date']??$input['setting_date']??date('Y-m-d'),
                    'expected_hatch_date'=>$input['expected_hatch']??date('Y-m-d', strtotime('+21 days')),
                    'breed'=>$input['breed']??'',
                    'eggs_set'=>(int)($input['eggs_set']??0),
                    'chicks_hatched'=>(int)($input['hatched']??$input['chicks_hatched']??0),
                    'notes'=>$input['notes']??''
                ], $id);
                echo json_encode(['success'=>true, 'id'=>$fid]);
            }
            break;

        // ════ FEED PRODUCTION ════ (uses production_date, recipe_id, total_kg)
        case 'feed_production':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT fpb.*, fr.name AS recipe_name FROM feed_production_batches fpb LEFT JOIN feed_recipes fr ON fpb.recipe_id=fr.id'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fid = genericSave($pdo, 'feed_production_batches', [
                    'production_date'=>$input['batch_date']??$input['production_date']??date('Y-m-d'),
                    'recipe_id'=>(int)($input['recipe_id']??0),
                    'bags_produced'=>(int)($input['bags_produced']??0),
                    'bag_size_kg'=>(float)($input['bag_size_kg']??25),
                    'total_kg'=>(float)($input['quantity_kg']??$input['total_kg']??0),
                    'total_cost'=>(float)($input['cost']??$input['total_cost']??0),
                    'cost_per_kg'=>(float)($input['cost_per_kg']??0),
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

        // ════ USERS ════
        case 'users':
            if ($action === 'list') {
                echo json_encode(listQuery($pdo, 'SELECT id, username, email, role, full_name, is_active, created_at FROM users'));
            } elseif ($action === 'save') {
                $id = (int)($input['id'] ?? 0);
                $fields = [
                    'username'=>$input['username']??'', 'email'=>$input['email']??'',
                    'full_name'=>$input['full_name']??'', 'phone'=>$input['phone']??'',
                    'role'=>$input['role']??'super_admin', 'is_active'=>(int)($input['is_active']??1)
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

        default:
            http_response_code(400);
            echo json_encode([
                'error' => "Unknown module: $module",
                'available' => [
                    'dashboard','animals','groups','housing','health','vaccinations',
                    'production','breeding','feeding','milking','mortality','quarantine',
                    'weights','body_condition','preventive_care','transport','growth',
                    'crops','finance','inventory','staff','crm','purchase_orders',
                    'egg_grading','hatchery','feed_production','lpo','reminders',
                    'users','settings','helpers'
                ]
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
