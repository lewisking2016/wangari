<?php
/**
 * Farm Operations V2 — Species-agnostic module
 * Tabs: overview | animals | groups | housing | health | vaccinations | production | breeding | feeding | poultry
 *
 * Every animal type (chicken, cattle, goat, sheep, pig, rabbit…) gets equal
 * treatment: unified registry, shared health/vaccination/production/breeding
 * feeds, species-aware housing, and poultry deep-tools folded in.
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
require_once dirname(__DIR__, 2) . '/Backend/config/limits.php';
wangariStartSession();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','stock_manager','sales_staff'], true)) {
    echo "<script>window.location.href='/Frontend/pages/login.php';</script>"; exit;
}

$page_title = 'Farm Operations - Admin';
include __DIR__ . '/includes/admin_header.php';

$tab = $_GET['tab'] ?? 'overview';
$validTabs = ['overview','animals','groups','housing','health','vaccinations','production','breeding','feeding','weights','milking','mortality','quarantine','ai_records','body_condition','transport','preventive_care','poultry','grazing','farmmap'];
if (!in_array($tab, $validTabs, true)) $tab = 'overview';

$pdo = getDB();
$message = ''; $error_message = '';
$speciesFilter = $_GET['species'] ?? '';

/* ══════════════════════════════════════════════════════════════
   POST HANDLERS — all forms POST back to the same page
   ══════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $postAction = $_POST['_action'] ?? '';

    /* ── Save Animal (individual) ── */
    if ($postAction === 'save_animal') {
        $id = (int)($_POST['id'] ?? 0);
        
        // Check animal limit before inserting new animal
        if ($id === 0) {
            $limitCheck = wangariCheckAnimalLimit($pdo, (int)($_SESSION['user_id'] ?? 0));
            if (!$limitCheck['allowed']) {
                $error_message = $limitCheck['message'];
                $tab = 'animals';
            }
        }
        
        if (empty($error_message)) {
        $v = [
            trim($_POST['tag'] ?? ''), trim($_POST['name'] ?? ''),
            trim($_POST['species'] ?? 'Chicken'), trim($_POST['breed'] ?? ''),
            trim($_POST['gender'] ?? 'female'), $_POST['birth_date'] ?? null,
            trim($_POST['status'] ?? 'Active'), (int)($_POST['group_id'] ?? 0) ?: null,
            trim($_POST['notes'] ?? ''),
        ];
        try {
            if ($id > 0) {
                $pdo->prepare('UPDATE animals SET tag=?,name=?,type=?,breed=?,gender=?,birth_date=?,status=?,group_id=?,notes=? WHERE id=?')
                    ->execute(array_merge($v, [$id]));
                $message = 'Animal updated.';
            } else {
                $pdo->prepare('INSERT INTO animals (tag,name,type,breed,gender,birth_date,status,group_id,notes) VALUES (?,?,?,?,?,?,?,?,?)')
                    ->execute($v);
                $message = 'Animal added.';
            }
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        } // end if (empty($error_message))
        $tab = 'animals';
    }

    /* ── Save Group (flock/herd/pen/boma) ── */
    if ($postAction === 'save_group') {
        $id = (int)($_POST['id'] ?? 0);
        $v = [
            trim($_POST['name'] ?? ''), trim($_POST['species'] ?? 'Chicken'),
            trim($_POST['group_type'] ?? 'flock'), trim($_POST['breed'] ?? ''),
            (int)($_POST['head_count'] ?? 0), (int)($_POST['housing_id'] ?? 0) ?: null,
            trim($_POST['location'] ?? ''), trim($_POST['status'] ?? 'active'),
            trim($_POST['notes'] ?? ''),
        ];
        try {
            if ($id > 0) {
                $pdo->prepare('UPDATE animal_groups SET name=?,species=?,group_type=?,breed=?,head_count=?,housing_id=?,location=?,status=?,notes=? WHERE id=?')
                    ->execute(array_merge($v, [$id]));
                $message = 'Group updated.';
            } else {
                $pdo->prepare('INSERT INTO animal_groups (name,species,group_type,breed,head_count,housing_id,location,status,notes) VALUES (?,?,?,?,?,?,?,?,?)')
                    ->execute($v);
                $message = 'Group created.';
            }
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'groups';
    }

    /* ── Save Housing ── */
    if ($postAction === 'save_housing') {
        $id = (int)($_POST['id'] ?? 0);
        $v = [
            trim($_POST['house_name'] ?? ''), trim($_POST['house_code'] ?? ''),
            trim($_POST['location'] ?? ''), (int)($_POST['capacity'] ?? 0),
            trim($_POST['species'] ?? 'Chicken'), trim($_POST['house_type'] ?? 'house'),
            trim($_POST['description'] ?? ''),
        ];
        try {
            if ($id > 0) {
                $pdo->prepare('UPDATE houses SET house_name=?,house_code=?,location=?,capacity=?,species=?,house_type=?,description=? WHERE id=?')
                    ->execute(array_merge($v, [$id]));
                $message = 'Housing updated.';
            } else {
                $pdo->prepare('INSERT INTO houses (house_name,house_code,location,capacity,species,house_type,description) VALUES (?,?,?,?,?,?,?)')
                    ->execute($v);
                $message = 'Housing unit added.';
            }
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'housing';
    }

    /* ── Save Health Record ── */
    if ($postAction === 'save_health') {
        $id = (int)($_POST['id'] ?? 0);
        $v = [
            $_POST['record_date'] ?? date('Y-m-d'),
            trim($_POST['subject'] ?? ''),
            trim($_POST['record_type'] ?? 'treatment'),
            trim($_POST['vaccine_name'] ?? ''), trim($_POST['product_name'] ?? ''),
            trim($_POST['dosage'] ?? ''), trim($_POST['route'] ?? 'oral'),
            (int)($_POST['birds_treated'] ?? 0), (int)($_POST['mortality_count'] ?? 0),
            trim($_POST['vet_name'] ?? ''), $_POST['next_due_date'] ?: null,
            (float)($_POST['cost'] ?? 0), trim($_POST['status'] ?? 'completed'),
            trim($_POST['notes'] ?? ''), trim($_POST['species'] ?? 'Chicken'),
            (int)($_POST['animal_id'] ?? 0) ?: null,
            (int)($_POST['group_id'] ?? 0) ?: null,
        ];
        try {
            if ($id > 0) {
                $pdo->prepare('UPDATE health_records SET record_date=?,subject=?,record_type=?,vaccine_name=?,product_name=?,dosage=?,route=?,birds_treated=?,mortality_count=?,vet_name=?,next_due_date=?,cost=?,status=?,notes=?,species=?,animal_id=?,group_id=? WHERE id=?')
                    ->execute(array_merge($v, [$id]));
                $message = 'Health record updated.';
            } else {
                $pdo->prepare('INSERT INTO health_records (record_date,subject,record_type,vaccine_name,product_name,dosage,route,birds_treated,mortality_count,vet_name,next_due_date,cost,status,notes,species,animal_id,group_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute($v);
                $message = 'Health record logged.';
            }
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'health';
    }

    /* ── Save Vaccination ── */
    if ($postAction === 'save_vaccination') {
        $id = (int)($_POST['id'] ?? 0);
        $v = [
            (int)($_POST['flock_id'] ?? 0) ?: null,
            trim($_POST['vaccine_name'] ?? ''),
            $_POST['scheduled_date'] ?? date('Y-m-d'),
            $_POST['administered_date'] ?: null,
            trim($_POST['status'] ?? 'scheduled'),
            trim($_POST['dosage'] ?? ''), trim($_POST['notes'] ?? ''),
            (float)($_POST['cost'] ?? 0),
            trim($_POST['species'] ?? 'Chicken'),
            (int)($_POST['animal_id'] ?? 0) ?: null,
            (int)($_POST['group_id'] ?? 0) ?: null,
            $_POST['next_due_date'] ?: null,
        ];
        $withdrawalDays = (int)($_POST['withdrawal_days'] ?? 0);
        $withdrawalEnd = null;
        if ($withdrawalDays > 0 && $_POST['administered_date']) {
            $withdrawalEnd = date('Y-m-d', strtotime($_POST['administered_date'] . " +{$withdrawalDays} days"));
        }
        try {
            if ($id > 0) {
                $pdo->prepare('UPDATE vaccinations SET flock_id=?,vaccine_name=?,scheduled_date=?,administered_date=?,status=?,dosage=?,notes=?,cost=?,species=?,animal_id=?,group_id=?,next_due_date=?,withdrawal_days=?,withdrawal_end_date=? WHERE id=?')
                    ->execute(array_merge($v, [$withdrawalDays, $withdrawalEnd, [$id]]));
                $message = 'Vaccination updated.';
            } else {
                $pdo->prepare('INSERT INTO vaccinations (flock_id,vaccine_name,scheduled_date,administered_date,status,dosage,notes,cost,species,animal_id,group_id,next_due_date,withdrawal_days,withdrawal_end_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute(array_merge($v, [$withdrawalDays, $withdrawalEnd]));
                $message = 'Vaccination scheduled.';
            }
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'vaccinations';
    }

    /* ── Save Production Record ── */
    if ($postAction === 'save_production') {
        $id = (int)($_POST['id'] ?? 0);
        $v = [
            (int)($_POST['flock_id'] ?? 0) ?: null,
            $_POST['record_date'] ?? date('Y-m-d'),
            (int)($_POST['eggs_collected'] ?? 0), (int)($_POST['cracked_eggs'] ?? 0),
            (float)($_POST['meat_weight_kg'] ?? 0), (int)($_POST['mortality'] ?? 0),
            (float)($_POST['feed_consumed_kg'] ?? 0), trim($_POST['notes'] ?? ''),
            trim($_POST['species'] ?? 'Chicken'),
            (int)($_POST['group_id'] ?? 0) ?: null,
            (float)($_POST['milk_litres'] ?? 0),
            (float)($_POST['weight_kg'] ?? 0),
            (int)($_POST['sold_count'] ?? 0),
        ];
        try {
            if ($id > 0) {
                $pdo->prepare('UPDATE production_records SET flock_id=?,record_date=?,eggs_collected=?,cracked_eggs=?,meat_weight_kg=?,mortality=?,feed_consumed_kg=?,notes=?,species=?,group_id=?,milk_litres=?,weight_kg=?,sold_count=? WHERE id=?')
                    ->execute(array_merge($v, [$id]));
                $message = 'Production record updated.';
            } else {
                $pdo->prepare('INSERT INTO production_records (flock_id,record_date,eggs_collected,cracked_eggs,meat_weight_kg,mortality,feed_consumed_kg,notes,species,group_id,milk_litres,weight_kg,sold_count) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute($v);
                $message = 'Production logged.';
            }
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'production';
    }

    /* ── Save Breeding Record ── */
    if ($postAction === 'save_breeding') {
        $id = (int)($_POST['id'] ?? 0);
        $v = [
            trim($_POST['species'] ?? 'Chicken'),
            trim($_POST['dam'] ?? ''), trim($_POST['sire'] ?? ''),
            $_POST['breeding_date'] ?? date('Y-m-d'),
            $_POST['expected_birth'] ?: null,
            trim($_POST['status'] ?? 'Pending'),
            trim($_POST['notes'] ?? ''),
            (int)($_POST['offspring_count'] ?? 0),
            (int)($_POST['dam_id'] ?? 0) ?: null,
            (int)($_POST['sire_id'] ?? 0) ?: null,
        ];
        try {
            if ($id > 0) {
                $pdo->prepare('UPDATE breeding_records SET species=?,male_parent=?,type=?,date=?,due_date=?,status=?,notes=?,offspring_count=?,dam_id=?,sire_id=? WHERE id=?')
                    ->execute(array_merge($v, [$id]));
                $message = 'Breeding record updated.';
            } else {
                $pdo->prepare('INSERT INTO breeding_records (species,male_parent,type,date,due_date,status,notes,offspring_count,dam_id,sire_id) VALUES (?,?,?,?,?,?,?,?,?,?)')
                    ->execute($v);
                $message = 'Breeding recorded.';
            }
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'breeding';
    }

    /* ── Save Feed Log ── */
    if ($postAction === 'save_feed_log') {
        $id = (int)($_POST['id'] ?? 0);
        $v = [
            $_POST['record_date'] ?? date('Y-m-d'),
            (int)($_POST['group_id'] ?? 0) ?: null,
            (int)($_POST['animal_id'] ?? 0) ?: null,
            trim($_POST['species'] ?? 'Chicken'),
            trim($_POST['feed_type'] ?? ''),
            (float)($_POST['quantity_kg'] ?? 0),
            (float)($_POST['cost'] ?? 0),
            trim($_POST['notes'] ?? ''),
            (int)($_SESSION['user_id'] ?? 0),
        ];
        try {
            if ($id > 0) {
                $pdo->prepare('UPDATE feed_logs SET record_date=?,group_id=?,animal_id=?,species=?,feed_type=?,quantity_kg=?,cost=?,notes=?,recorded_by=? WHERE id=?')
                    ->execute(array_merge($v, [$id]));
                $message = 'Feed log updated.';
            } else {
                $pdo->prepare('INSERT INTO feed_logs (record_date,group_id,animal_id,species,feed_type,quantity_kg,cost,notes,recorded_by) VALUES (?,?,?,?,?,?,?,?,?)')
                    ->execute($v);
                $message = 'Feed logged.';
            }
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'feeding';
    }

    if ($postAction === 'save_weight') {
        try {
            $id = (int)($_POST['id'] ?? 0);
            $v = [
                (int)($_POST['animal_id'] ?? 0) ?: null,
                (int)($_POST['group_id'] ?? 0) ?: null,
                trim($_POST['species'] ?? 'Chicken'),
                (float)($_POST['weight_kg'] ?? 0),
                trim($_POST['recorded_date'] ?? date('Y-m-d')),
                trim($_POST['notes'] ?? ''),
            ];
            if ($id > 0) {
                $pdo->prepare('UPDATE animal_weights SET animal_id=?,group_id=?,species=?,weight_kg=?,recorded_date=?,notes=? WHERE id=?')
                    ->execute(array_merge($v, [$id]));
                $message = 'Weight record updated.';
            } else {
                $pdo->prepare('INSERT INTO animal_weights (animal_id,group_id,species,weight_kg,recorded_date,notes) VALUES (?,?,?,?,?,?)')
                    ->execute($v);
                $message = 'Weight recorded.';
            }
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'weights';
    }

    // Save milking record
    if ($postAction === 'save_milking') {
        try {
            $id = (int)($_POST['id'] ?? 0);
            $v = [
                (int)($_POST['animal_id'] ?? 0) ?: null,
                (int)($_POST['group_id'] ?? 0) ?: null,
                trim($_POST['species'] ?? 'Cattle'),
                trim($_POST['milking_date'] ?? date('Y-m-d')),
                trim($_POST['milking_time'] ?? 'morning'),
                (float)($_POST['litres'] ?? 0),
                (float)($_POST['fat_pct'] ?? 0) ?: null,
                trim($_POST['quality_grade'] ?? 'A'),
                trim($_POST['notes'] ?? ''),
            ];
            if ($id > 0) {
                $pdo->prepare('UPDATE milking_records SET animal_id=?,group_id=?,species=?,milking_date=?,milking_time=?,litres=?,fat_pct=?,quality_grade=?,notes=? WHERE id=?')
                    ->execute(array_merge($v, [$id]));
                $message = 'Milking record updated.';
            } else {
                $pdo->prepare('INSERT INTO milking_records (animal_id,group_id,species,milking_date,milking_time,litres,fat_pct,quality_grade,notes) VALUES (?,?,?,?,?,?,?,?,?)')
                    ->execute($v);
                $message = 'Milking recorded.';
            }
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'milking';
    }

    // Save mortality record
    if ($postAction === 'save_mortality') {
        try {
            $v = [
                (int)($_POST['animal_id'] ?? 0) ?: null,
                (int)($_POST['group_id'] ?? 0) ?: null,
                trim($_POST['species'] ?? 'Chicken'),
                trim($_POST['death_date'] ?? date('Y-m-d')),
                (int)($_POST['count'] ?? 1),
                trim($_POST['cause'] ?? ''),
                trim($_POST['cause_category'] ?? 'unknown'),
                trim($_POST['disposal_method'] ?? 'burial'),
                trim($_POST['notes'] ?? ''),
            ];
            $pdo->prepare('INSERT INTO mortality_records (animal_id,group_id,species,death_date,count,cause,cause_category,disposal_method,notes) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute($v);
            $message = 'Mortality recorded.';
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'mortality';
    }

    // Save quarantine record
    if ($postAction === 'save_quarantine') {
        try {
            $id = (int)($_POST['id'] ?? 0);
            $v = [
                (int)($_POST['animal_id'] ?? 0) ?: null,
                (int)($_POST['group_id'] ?? 0) ?: null,
                trim($_POST['species'] ?? 'Chicken'),
                trim($_POST['quarantine_start'] ?? date('Y-m-d')),
                trim($_POST['reason'] ?? ''),
                trim($_POST['location'] ?? ''),
                trim($_POST['diagnosis'] ?? ''),
                trim($_POST['treatment_given'] ?? ''),
                trim($_POST['vet_name'] ?? ''),
                (float)($_POST['cost'] ?? 0),
            ];
            if ($id > 0) {
                $pdo->prepare('UPDATE quarantine_records SET animal_id=?,group_id=?,species=?,quarantine_start=?,reason=?,location=?,diagnosis=?,treatment_given=?,vet_name=?,cost=? WHERE id=?')
                    ->execute(array_merge($v, [$id]));
                $message = 'Quarantine updated.';
            } else {
                $pdo->prepare('INSERT INTO quarantine_records (animal_id,group_id,species,quarantine_start,reason,location,diagnosis,treatment_given,vet_name,cost) VALUES (?,?,?,?,?,?,?,?,?,?)')
                    ->execute($v);
                $message = 'Animal quarantined.';
            }
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'quarantine';
    }

    // Save AI record
    if ($postAction === 'save_ai') {
        try {
            $v = [
                (int)($_POST['animal_id'] ?? 0) ?: null,
                trim($_POST['species'] ?? 'Cattle'),
                trim($_POST['insemination_date'] ?? date('Y-m-d')),
                trim($_POST['bull_semen_id'] ?? ''),
                trim($_POST['bull_name'] ?? ''),
                trim($_POST['bull_breed'] ?? ''),
                trim($_POST['insemination_type'] ?? 'ai'),
                trim($_POST['technician'] ?? ''),
                (float)($_POST['cost'] ?? 0),
            ];
            $pdo->prepare('INSERT INTO ai_records (animal_id,species,insemination_date,bull_semen_id,bull_name,bull_breed,insemination_type,technician,cost) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute($v);
            $message = 'AI record saved.';
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'ai_records';
    }

    // Save body condition score
    if ($postAction === 'save_bcs') {
        try {
            $v = [
                (int)($_POST['animal_id'] ?? 0) ?: null,
                (int)($_POST['group_id'] ?? 0) ?: null,
                trim($_POST['species'] ?? 'Cattle'),
                trim($_POST['score_date'] ?? date('Y-m-d')),
                (float)($_POST['score'] ?? 0),
                trim($_POST['scorer'] ?? ''),
                trim($_POST['notes'] ?? ''),
            ];
            $pdo->prepare('INSERT INTO body_condition_scores (animal_id,group_id,species,score_date,score,scorer,notes) VALUES (?,?,?,?,?,?,?)')
                ->execute($v);
            $message = 'Body condition score recorded.';
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'body_condition';
    }

    // Save mortality record
    if ($postAction === 'save_transport') {
        try {
            $v = [
                trim($_POST['transport_date'] ?? date('Y-m-d')),
                trim($_POST['species'] ?? 'Chicken'),
                (int)($_POST['animal_count'] ?? 0),
                trim($_POST['from_location'] ?? ''),
                trim($_POST['to_location'] ?? ''),
                trim($_POST['transporter_name'] ?? ''),
                trim($_POST['transporter_phone'] ?? ''),
                trim($_POST['vehicle_registration'] ?? ''),
                (float)($_POST['transport_cost'] ?? 0),
                trim($_POST['reason'] ?? ''),
                trim($_POST['notes'] ?? ''),
            ];
            $pdo->prepare('INSERT INTO animal_transports (transport_date,species,animal_count,from_location,to_location,transporter_name,transporter_phone,vehicle_registration,transport_cost,reason,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                ->execute($v);
            $message = 'Transport recorded.';
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        $tab = 'transport';
    }

    /* ── DELETE HANDLERS ── */
    if ($postAction === 'delete_animal') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM animals WHERE id=?')->execute([$id]);
                $message = 'Animal deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'animals';
    }

    if ($postAction === 'delete_group') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM animal_groups WHERE id=?')->execute([$id]);
                $message = 'Group deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'groups';
    }

    if ($postAction === 'delete_housing') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM houses WHERE id=?')->execute([$id]);
                $message = 'Housing deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'housing';
    }

    if ($postAction === 'delete_health') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM health_records WHERE id=?')->execute([$id]);
                $message = 'Health record deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'health';
    }

    if ($postAction === 'delete_vaccination') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM vaccinations WHERE id=?')->execute([$id]);
                $message = 'Vaccination deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'vaccinations';
    }

    if ($postAction === 'delete_production') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM production_records WHERE id=?')->execute([$id]);
                $message = 'Production record deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'production';
    }

    if ($postAction === 'delete_breeding') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM breeding_records WHERE id=?')->execute([$id]);
                $message = 'Breeding record deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'breeding';
    }

    if ($postAction === 'delete_feeding') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM feed_logs WHERE id=?')->execute([$id]);
                $message = 'Feed log deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'feeding';
    }

    if ($postAction === 'delete_mortality') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM mortality_records WHERE id=?')->execute([$id]);
                $message = 'Mortality record deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'mortality';
    }

    if ($postAction === 'delete_quarantine') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM quarantine_records WHERE id=?')->execute([$id]);
                $message = 'Quarantine record deleted.';
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'quarantine';
    }
}

/* ══════════════════════════════════════════════════════════════
   DATA LOADING — fetch data for the active tab
   ══════════════════════════════════════════════════════════════ */
$animals = $groups = $houses = $healthRecs = $vaccinations = $prodRecs = $breedingRecs = $feedLogs = $feedStandards = $vaccineGuides = $animalList = $groupList = [];

if ($pdo) {
    try {
        $animalList = $pdo->query("SELECT id, CONCAT(COALESCE(tag,''), ' - ', COALESCE(name,'?')) AS label, type AS species FROM animals ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $groupList  = $pdo->query("SELECT id, CONCAT(name, ' [', species, ']') AS label, species FROM animal_groups WHERE status='active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $houses     = $pdo->query("SELECT * FROM houses WHERE is_active=1 ORDER BY house_name ASC")->fetchAll(PDO::FETCH_ASSOC);

        if ($tab === 'overview') {
            $totalAnimals = $pdo->query("SELECT COUNT(*) FROM animals WHERE status IN ('Active','alive')")->fetchColumn();
            $totalGroups  = $pdo->query("SELECT COUNT(*) FROM animal_groups WHERE status='active'")->fetchColumn();
            $speciesCounts = $pdo->query("SELECT type AS species, COUNT(*) AS cnt FROM animals WHERE status IN ('Active','alive') GROUP BY type ORDER BY type")->fetchAll(PDO::FETCH_ASSOC);
            $groupSpeciesCounts = $pdo->query("SELECT species, SUM(head_count) AS total_head FROM animal_groups WHERE status='active' GROUP BY species ORDER BY species")->fetchAll(PDO::FETCH_ASSOC);
            $today = date('Y-m-d');
            $todayProd = $pdo->query("SELECT species, SUM(eggs_collected) AS eggs, SUM(milk_litres) AS milk, SUM(mortality) AS mort FROM production_records WHERE record_date='$today' GROUP BY species")->fetchAll(PDO::FETCH_ASSOC);
            $weekAgo = date('Y-m-d', strtotime('-7 days'));
            $upcomingVacs = $pdo->query("SELECT COUNT(*) FROM vaccinations WHERE status='scheduled' AND scheduled_date BETWEEN '$today' AND '".date('Y-m-d', strtotime('+7 days'))."'")->fetchColumn();
            $pendingBirths = $pdo->query("SELECT COUNT(*) FROM breeding_records WHERE status='Pending' AND due_date >= '$today' AND due_date <= '".date('Y-m-d', strtotime('+14 days'))."'")->fetchColumn();
            $recentHealth = $pdo->query("SELECT * FROM health_records ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tab === 'animals') {
            $sql = 'SELECT a.*, ag.name AS group_name FROM animals a LEFT JOIN animal_groups ag ON a.group_id=ag.id';
            if ($speciesFilter) $sql .= " WHERE a.type=". $pdo->quote($speciesFilter);
            $sql .= ' ORDER BY a.type ASC, a.name ASC';
            $animals = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tab === 'groups') {
            $sql = 'SELECT ag.*, h.house_name FROM animal_groups ag LEFT JOIN houses h ON ag.housing_id=h.id';
            if ($speciesFilter) $sql .= " WHERE ag.species=". $pdo->quote($speciesFilter);
            $sql .= ' ORDER BY ag.species ASC, ag.name ASC';
            $groups = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tab === 'housing') {
            $sql = 'SELECT h.*, (SELECT COUNT(*) FROM animal_groups ag WHERE ag.housing_id=h.id AND ag.status=\'active\') AS active_groups FROM houses h WHERE h.is_active=1';
            if ($speciesFilter) $sql .= " AND h.species=". $pdo->quote($speciesFilter);
            $sql .= ' ORDER BY h.species ASC, h.house_name ASC';
            $houses = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tab === 'health') {
            $sql = 'SELECT hr.*, a.name AS aname, a.tag, ag.name AS gname FROM health_records hr LEFT JOIN animals a ON hr.animal_id=a.id LEFT JOIN animal_groups ag ON hr.group_id=ag.id';
            if ($speciesFilter) $sql .= " WHERE hr.species=". $pdo->quote($speciesFilter);
            $sql .= ' ORDER BY hr.record_date DESC, hr.created_at DESC LIMIT 200';
            $healthRecs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tab === 'vaccinations') {
            $sql = 'SELECT v.*, a.name AS aname, a.tag, ag.name AS gname FROM vaccinations v LEFT JOIN animals a ON v.animal_id=a.id LEFT JOIN animal_groups ag ON v.group_id=ag.id';
            if ($speciesFilter) $sql .= " WHERE v.species=". $pdo->quote($speciesFilter);
            $sql .= ' ORDER BY v.scheduled_date DESC LIMIT 200';
            $vaccinations = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $vgSql = 'SELECT * FROM vaccine_guides WHERE is_active=1';
            if ($speciesFilter) $vgSql .= " AND species=". $pdo->quote($speciesFilter);
            $vgSql .= ' ORDER BY species ASC, sort_order ASC';
            $vaccineGuides = $pdo->query($vgSql)->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tab === 'production') {
            $sql = 'SELECT pr.*, ag.name AS gname FROM production_records pr LEFT JOIN animal_groups ag ON pr.group_id=ag.id';
            if ($speciesFilter) $sql .= " WHERE pr.species=". $pdo->quote($speciesFilter);
            $sql .= ' ORDER BY pr.record_date DESC LIMIT 200';
            $prodRecs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tab === 'breeding') {
            $sql = 'SELECT * FROM breeding_records';
            if ($speciesFilter) $sql .= " WHERE species=". $pdo->quote($speciesFilter);
            $sql .= ' ORDER BY date DESC LIMIT 200';
            $breedingRecs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tab === 'feeding') {
            $sql = 'SELECT fl.*, ag.name AS gname, a.name AS aname, a.tag FROM feed_logs fl LEFT JOIN animal_groups ag ON fl.group_id=ag.id LEFT JOIN animals a ON fl.animal_id=a.id';
            if ($speciesFilter) $sql .= " WHERE fl.species=". $pdo->quote($speciesFilter);
            $sql .= ' ORDER BY fl.record_date DESC LIMIT 200';
            $feedLogs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $fsSql = 'SELECT * FROM feeding_standards';
            if ($speciesFilter) $fsSql .= " WHERE species=". $pdo->quote($speciesFilter);
            $fsSql .= ' ORDER BY species ASC, week_number ASC';
            $feedStandards = $pdo->query($fsSql)->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tab === 'weights') {
            $sql = 'SELECT aw.*, a.name AS aname, a.tag, ag.name AS gname FROM animal_weights aw LEFT JOIN animals a ON aw.animal_id=a.id LEFT JOIN animal_groups ag ON aw.group_id=ag.id';
            if ($speciesFilter) $sql .= " WHERE aw.species=". $pdo->quote($speciesFilter);
            $sql .= ' ORDER BY aw.recorded_date DESC LIMIT 200';
            $weightRecs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tab === 'milking') {
            $sql = 'SELECT mr.*, a.name AS aname, a.tag, ag.name AS gname FROM milking_records mr LEFT JOIN animals a ON mr.animal_id=a.id LEFT JOIN animal_groups ag ON mr.group_id=ag.id';
            if ($speciesFilter) $sql .= " WHERE mr.species=". $pdo->quote($speciesFilter);
            $sql .= ' ORDER BY mr.milking_date DESC, mr.milking_time DESC LIMIT 200';
            $milkingRecs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            // Today's milking summary
            $todayMilking = $pdo->query("SELECT milking_time, SUM(litres) as total FROM milking_records WHERE milking_date='" . date('Y-m-d') . "' GROUP BY milking_time")->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tab === 'mortality') {
            $sql = 'SELECT mr.*, a.name AS aname, a.tag, ag.name AS gname FROM mortality_records mr LEFT JOIN animals a ON mr.animal_id=a.id LEFT JOIN animal_groups ag ON mr.group_id=ag.id';
            if ($speciesFilter) $sql .= " WHERE mr.species=". $pdo->quote($speciesFilter);
            $sql .= ' ORDER BY mr.death_date DESC LIMIT 200';
            $mortalityRecs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tab === 'quarantine') {
            $sql = 'SELECT qr.*, a.name AS aname, a.tag, ag.name AS gname FROM quarantine_records qr LEFT JOIN animals a ON qr.animal_id=a.id LEFT JOIN animal_groups ag ON qr.group_id=ag.id';
            if ($speciesFilter) $sql .= " WHERE qr.species=". $pdo->quote($speciesFilter);
            $sql .= ' ORDER BY qr.quarantine_start DESC LIMIT 200';
            $quarantineRecs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tab === 'ai_records') {
            $sql = 'SELECT air.*, a.name AS aname, a.tag FROM ai_records air LEFT JOIN animals a ON air.animal_id=a.id';
            if ($speciesFilter) $sql .= " WHERE air.species=". $pdo->quote($speciesFilter);
            $sql .= ' ORDER BY air.insemination_date DESC LIMIT 200';
            $aiRecs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tab === 'body_condition') {
            $sql = 'SELECT bcs.*, a.name AS aname, a.tag, ag.name AS gname FROM body_condition_scores bcs LEFT JOIN animals a ON bcs.animal_id=a.id LEFT JOIN animal_groups ag ON bcs.group_id=ag.id';
            if ($speciesFilter) $sql .= " WHERE bcs.species=". $pdo->quote($speciesFilter);
            $sql .= ' ORDER BY bcs.score_date DESC LIMIT 200';
            $bcsRecs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tab === 'transport') {
            $transportRecs = $pdo->query('SELECT * FROM animal_transports ORDER BY transport_date DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tab === 'preventive_care') {
            $preventiveCareRecs = $pdo->query('SELECT * FROM preventive_care ORDER BY next_due ASC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) { /* non-fatal */ }
}

$spList = ['Chicken','Cattle','Goat','Sheep','Pig','Rabbit','Duck','Turkey','Guinea fowl','Donkey','Bee','Fish','Other'];
$allSpecies = ['Chicken','Cattle','Goat','Sheep','Pig'];

$tabs = [
    'overview'     => ['icon'=>'layout-dashboard','label'=>'Overview'],
    'animals'      => ['icon'=>'paw-print',       'label'=>'Animals'],
    'groups'       => ['icon'=>'users',           'label'=>'Groups'],
    'housing'      => ['icon'=>'home',            'label'=>'Housing'],
    'health'       => ['icon'=>'heart-pulse',     'label'=>'Health'],
    'vaccinations' => ['icon'=>'syringe',         'label'=>'Vaccinations'],
    'production'   => ['icon'=>'egg',             'label'=>'Production'],
    'breeding'     => ['icon'=>'dna',             'label'=>'Breeding'],
    'feeding'      => ['icon'=>'wheat',           'label'=>'Feeding'],
    'weights'      => ['icon'=>'scale',           'label'=>'Weight Tracking'],
    'milking'      => ['icon'=>'milk',            'label'=>'Milking Records'],
    'mortality'    => ['icon'=>'skull',           'label'=>'Mortality'],
    'quarantine'   => ['icon'=>'shield-alert',    'label'=>'Quarantine'],
    'ai_records'   => ['icon'=>'microscope',      'label'=>'AI & Breeding'],
    'body_condition'=> ['icon'=>'heart',          'label'=>'Body Condition'],
    'transport'    => ['icon'=>'truck',           'label'=>'Transport'],
    'preventive_care'=> ['icon'=>'calendar-check','label'=>'Preventive Care'],
    'grazing'      => ['icon'=>'trees',           'label'=>'Grazing & Pasture'],
    'poultry'      => ['icon'=>'bird',            'label'=>'Poultry Tools'],
    'farmmap'      => ['icon'=>'map-pin',         'label'=>'Farm Map'],
];
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div class="hub-page-header">
    <div class="hub-page-icon"><i data-lucide="tractor" style="width:24px;height:24px;"></i></div>
    <div>
        <h1 class="hub-page-title">Farm Operations</h1>
        <p class="hub-page-sub">Manage all animals, groups, health, production and more — every species, one place.</p>
    </div>
</div>

<?php if ($message): ?>
<div style="padding:13px 18px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;color:#166534;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <i data-lucide="check-circle-2" style="width:18px;height:18px;"></i> <?= htmlspecialchars($message, ENT_QUOTES,'UTF-8') ?>
</div>
<?php endif; ?>
<?php if ($error_message): ?>
<div style="padding:13px 18px;background:#fee2e2;border:1px solid #fecaca;border-radius:8px;color:#b91c1c;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <i data-lucide="alert-circle" style="width:18px;height:18px;"></i> <?= htmlspecialchars($error_message, ENT_QUOTES,'UTF-8') ?>
</div>
<?php endif; ?>

<!-- Tab Bar -->
<div style="display:flex;gap:4px;background:#f1f5f9;padding:5px;border-radius:10px;margin-bottom:24px;overflow-x:auto;scrollbar-width:none;-ms-overflow-style:none;">
<?php foreach ($tabs as $key => $info): ?>
    <a href="?tab=<?= $key ?><?= $speciesFilter && $key !== 'overview' ? '&species='.urlencode($speciesFilter) : '' ?>" style="display:flex;align-items:center;gap:7px;padding:9px 14px;border-radius:7px;text-decoration:none;white-space:nowrap;font-weight:600;font-size:0.84rem;transition:all 0.18s;<?= $tab===$key ? 'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);' : 'color:#64748b;' ?>">
        <i data-lucide="<?= $info['icon'] ?>" style="width:15px;height:15px;"></i><?= $info['label'] ?>
    </a>
<?php endforeach; ?>
</div>

<?php /* ════════════════════════ SPECIES FILTER CHIPS ════════════════════════ */ ?>
<?php if (!in_array($tab, ['overview','poultry'])): ?>
<div style="display:flex;gap:6px;margin-bottom:18px;flex-wrap:wrap;">
    <a href="?tab=<?= $tab ?>" style="padding:6px 14px;border-radius:20px;font-size:0.82rem;font-weight:600;text-decoration:none;<?= !$speciesFilter ? 'background:var(--admin-primary);color:#fff;' : 'background:#f1f5f9;color:#475569;' ?>">All</a>
    <?php foreach ($allSpecies as $sp): ?>
        <a href="?tab=<?= $tab ?>&species=<?= urlencode($sp) ?>" style="padding:6px 14px;border-radius:20px;font-size:0.82rem;font-weight:600;text-decoration:none;<?= $speciesFilter === $sp ? 'background:var(--admin-primary);color:#fff;' : 'background:#f1f5f9;color:#475569;' ?>"><?= $sp ?></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>


<?php /* ══════════════════════════════════════════════════════════════════════════════════
   TAB: OVERVIEW
   ══════════════════════════════════════════════════════════════════════════════════ */ ?>
<?php if ($tab === 'overview'): ?>
<!-- Species KPI Cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-bottom:24px;">
    <?php foreach ($groupSpeciesCounts as $gs): ?>
    <div class="stat-card" style="cursor:pointer;" onclick="window.location='?tab=groups&species=<?= urlencode($gs['species']) ?>'">
        <div class="stat-card-info"><small><?= htmlspecialchars($gs['species']) ?> (Groups)</small><strong><?= number_format((float)$gs['total_head']) ?></strong></div>
        <div class="stat-card-icon"><i data-lucide="users" style="width:22px;height:22px;"></i></div>
    </div>
    <?php endforeach; ?>
    <?php foreach ($speciesCounts as $sc): ?>
    <div class="stat-card" style="cursor:pointer;" onclick="window.location='?tab=animals&species=<?= urlencode($sc['species']) ?>'">
        <div class="stat-card-info"><small><?= htmlspecialchars($sc['species']) ?> (Individuals)</small><strong><?= number_format((int)$sc['cnt']) ?></strong></div>
        <div class="stat-card-icon accent"><i data-lucide="paw-print" style="width:22px;height:22px;"></i></div>
    </div>
    <?php endforeach; ?>
    <?php if (!$speciesCounts && !$groupSpeciesCounts): ?>
    <div class="stat-card"><div class="stat-card-info"><small>Total Animals</small><strong>0</strong></div><div class="stat-card-icon"><i data-lucide="paw-print" style="width:22px;height:22px;"></i></div></div>
    <?php endif; ?>
</div>

<!-- Today's Production + Alerts -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;margin-bottom:24px;">
    <div class="stat-card"><div class="stat-card-info"><small>Today's Eggs</small><strong><?= number_format((float)array_sum(array_column($todayProd, 'eggs'))) ?></strong></div><div class="stat-card-icon info"><i data-lucide="egg" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Today's Milk (L)</small><strong><?= number_format((float)array_sum(array_column($todayProd, 'milk')), 1) ?></strong></div><div class="stat-card-icon"><i data-lucide="milk" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Today's Mortality</small><strong style="color:#dc2626;"><?= number_format((float)array_sum(array_column($todayProd, 'mort'))) ?></strong></div><div class="stat-card-icon" style="background:#fee2e2;color:#dc2626;"><i data-lucide="alert-triangle" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Upcoming Vaccinations (7d)</small><strong><?= $upcomingVacs ?></strong></div><div class="stat-card-icon accent"><i data-lucide="syringe" style="width:22px;height:22px;"></i></div></div>
    <div class="stat-card"><div class="stat-card-info"><small>Pending Births (14d)</small><strong><?= $pendingBirths ?></strong></div><div class="stat-card-icon info"><i data-lucide="baby" style="width:22px;height:22px;"></i></div></div>
</div>

<!-- Batch Performance Analytics -->
<?php include __DIR__ . '/includes/batch_analytics_widget.php'; ?>

<!-- Recent Health -->
<div class="admin-card" style="margin-bottom:24px;">
    <h3 style="margin:0 0 14px;font-family:'Outfit',sans-serif;font-size:1.05rem;">Recent Health Activity</h3>
    <?php if (empty($recentHealth)): ?>
    <p style="color:#94a3b8;text-align:center;padding:18px;">No health records yet.</p>
    <?php else: ?>
    <div class="table-responsive"><table class="admin-table">
        <thead><tr><th>Date</th><th>Species</th><th>Subject</th><th>Type</th><th>Vet</th><th>Cost</th></tr></thead>
        <tbody>
        <?php foreach ($recentHealth as $h): ?>
        <tr>
            <td><?= htmlspecialchars($h['record_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge-pill badge-pill-info"><?= htmlspecialchars($h['species'] ?? 'Chicken', ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars($h['subject'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($h['record_type'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($h['vet_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= isset($h['cost']) && $h['cost'] ? number_format((float)$h['cost'], 2) : '-' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>

<!-- Quick Actions -->
<div class="admin-card">
    <h3 style="margin:0 0 14px;font-family:'Outfit',sans-serif;font-size:1.05rem;">Quick Actions</h3>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="?tab=animals" class="btn btn-primary"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Add Animal</a>
        <a href="?tab=groups" class="btn btn-outline"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Add Group</a>
        <a href="?tab=production" class="btn btn-outline"><i data-lucide="egg" style="width:16px;height:16px;"></i> Log Production</a>
        <a href="?tab=health" class="btn btn-outline"><i data-lucide="heart-pulse" style="width:16px;height:16px;"></i> Health Record</a>
        <a href="?tab=vaccinations" class="btn btn-outline"><i data-lucide="syringe" style="width:16px;height:16px;"></i> Schedule Vaccine</a>
        <a href="?tab=feeding" class="btn btn-outline"><i data-lucide="wheat" style="width:16px;height:16px;"></i> Log Feeding</a>
    </div>
</div>


<?php /* ══════════════════════════════════════════════════════════════════════════════════
   TAB: ANIMALS — unified registry of individual animals
   ══════════════════════════════════════════════════════════════════════════════════ */ ?>
<?php elseif ($tab === 'animals'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Animal Registry <?= helpTip('A list of every individual animal on your farm. Add animals here with their tag, name, breed, gender, and birth date.') ?></h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">All individual animals across every species — cows, goats, sheep, pigs, rabbits, and more.</p></div>
        <button class="btn btn-primary" onclick="openAnimalModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Add Animal</button>
    </div>
    <div class="table-responsive"><table class="admin-table">
        <thead><tr><th>Tag</th><th>Name</th><th>Species</th><th>Breed</th><th>Gender</th><th>DOB</th><th>Group</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody><?php if (empty($animals)): ?>
        <tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8;"><strong>No animals yet.</strong><br>Tap <strong>+ Add Animal</strong> to register your first cow, goat, sheep or any livestock.</td></tr>
        <?php else: foreach ($animals as $a): ?>
        <tr>
            <td><strong><?= htmlspecialchars($a['tag'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong></td>
            <td><?= htmlspecialchars($a['name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge-pill badge-pill-info"><?= htmlspecialchars($a['type'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars($a['breed'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars(ucfirst($a['gender'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($a['birth_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($a['group_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge-pill <?= ($a['status'] ?? '') === 'Active' || ($a['status'] ?? '') === 'alive' ? 'badge-pill-success' : (($a['status'] ?? '') === 'sick' ? 'badge-pill-warning' : 'badge-pill-danger') ?>"><?= htmlspecialchars($a['status'] ?? 'Active', ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openAnimalModal(<?= htmlspecialchars(json_encode($a), ENT_QUOTES, "UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button><button class="btn btn-trans btn-sm" style="color:#dc2626;" onclick="if(confirm('Delete this animal?'))document.getElementById('delete-form-<?= (int)$a['id'] ?>').submit();"><i data-lucide="trash-2" style="width:13px;height:13px;"></i> Delete</button></div></td>
        <form id="delete-form-<?= (int)$a['id'] ?>" method="POST" style="display:none;"><input type="hidden" name="_action" value="delete_animal"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"></form>
        </tr>
        <?php endforeach; endif; ?></tbody>
    </table></div>
</div>

<!-- Animal Modal -->
<div id="animal-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 id="animal-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add Animal</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_animal"><input type="hidden" name="id" id="a-id">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group"><label class="admin-form-label">Tag / ID</label><input class="admin-form-control" name="tag_id" id="a-tag" placeholder="e.g. A-001"></div>
            <div class="admin-form-group"><label class="admin-form-label">Name</label><input class="admin-form-control" name="name" id="a-name" placeholder="e.g. Bessie"></div>
            <div class="admin-form-group"><label class="admin-form-label">Species</label><select class="admin-form-control" name="species" id="a-species"><?php foreach ($spList as $sp): ?><option><?= $sp ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Breed</label><input class="admin-form-control" name="breed" id="a-breed" placeholder="e.g. Friesian"></div>
            <div class="admin-form-group"><label class="admin-form-label">Gender</label><select class="admin-form-control" name="gender" id="a-gender"><option value="female">Female</option><option value="male">Male</option><option value="unknown">Unknown</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Date of Birth</label><input class="admin-form-control" type="date" name="birth_date" id="a-dob"></div>
            <div class="admin-form-group"><label class="admin-form-label">Group (optional)</label><select class="admin-form-control" name="group_id" id="a-group"><option value="">-- None --</option><?php foreach ($groupList as $gl): ?><option value="<?= (int)$gl['id'] ?>"><?= htmlspecialchars($gl['label'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Status</label><select class="admin-form-control" name="status" id="a-status"><option value="Active">Active</option><option value="alive">Alive</option><option value="sick">Sick</option><option value="sold">Sold</option><option value="deceased">Deceased</option></select></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" id="a-notes" rows="3"></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('animal-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Animal</button>
        </div></form>
    </div>
</div>
<script>
function openAnimalModal(d){
    document.getElementById('animal-modal-title').textContent=d?.id?'Edit Animal':'Add Animal';
    document.getElementById('a-id').value=d?.id||''; document.getElementById('a-tag').value=d?.tag||'';
    document.getElementById('a-name').value=d?.name||''; document.getElementById('a-species').value=d?.type||'Chicken';
    document.getElementById('a-breed').value=d?.breed||''; document.getElementById('a-gender').value=d?.gender||'female';
    document.getElementById('a-dob').value=d?.birth_date||''; document.getElementById('a-group').value=d?.group_id||d?.herd_id||'';
    document.getElementById('a-status').value=d?.status||'Active'; document.getElementById('a-notes').value=d?.notes||'';
    document.getElementById('animal-modal').style.display='flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('animal-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>


<?php /* ══════════════════════════════════════════════════════════════════════════════════
   TAB: GROUPS — unified flocks + herds
   ══════════════════════════════════════════════════════════════════════════════════ */ ?>
<?php elseif ($tab === 'groups'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Animal Groups <?= helpTip('Groups let you manage many animals at once instead of one by one. Example: all your Layer chickens in one group.') ?></h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Unified flocks, herds and pens — all species in one place.</p></div>
        <button class="btn btn-primary" onclick="openGroupModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Add Group</button>
    </div>
    <div class="table-responsive"><table class="admin-table">
        <thead><tr><th>Name</th><th>Species</th><th>Type</th><th>Breed</th><th>Head Count</th><th>Location</th><th>Housing</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody><?php if (empty($groups)): ?>
        <tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8;"><strong>No groups yet.</strong><br>Create your first flock, herd or pen with <strong>+ Add Group</strong>.</td></tr>
        <?php else: foreach ($groups as $g): ?>
        <tr>
            <td><strong><?= htmlspecialchars($g['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
            <td><span class="badge-pill badge-pill-info"><?= htmlspecialchars($g['species'], ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars(ucfirst($g['group_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($g['breed'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><strong><?= number_format((float)($g['head_count'] ?? 0)) ?></strong></td>
            <td><?= htmlspecialchars($g['location'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($g['house_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge-pill <?= ($g['status'] ?? '') === 'active' ? 'badge-pill-success' : (($g['status'] ?? '') === 'sold' ? 'badge-pill-warning' : 'badge-pill-danger') ?>"><?= ucfirst(htmlspecialchars($g['status'] ?? 'active', ENT_QUOTES, 'UTF-8')) ?></span></td>
            <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openGroupModal(<?= htmlspecialchars(json_encode($g), ENT_QUOTES, "UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button><button class="btn btn-trans btn-sm" style="color:#dc2626;" onclick="if(confirm('Delete this group?'))document.getElementById('delete-grp-<?= (int)$g['id'] ?>').submit();"><i data-lucide="trash-2" style="width:13px;height:13px;"></i> Delete</button></div></td>
        <form id="delete-grp-<?= (int)$g['id'] ?>" method="POST" style="display:none;"><input type="hidden" name="_action" value="delete_group"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>"></form>
        </tr>
        <?php endforeach; endif; ?></tbody>
    </table></div>
</div>

<!-- Group Modal -->
<div id="group-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 id="group-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add Group</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_group"><input type="hidden" name="id" id="g-id">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Group Name *</label><input class="admin-form-control" name="name" id="g-name" required placeholder="e.g. Layers Batch 15"></div>
            <div class="admin-form-group"><label class="admin-form-label">Species *</label><select class="admin-form-control" name="species" id="g-species"><?php foreach ($spList as $sp): ?><option><?= $sp ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Group Type</label><select class="admin-form-control" name="group_type" id="g-type"><option value="flock">Flock</option><option value="herd">Herd</option><option value="pen">Pen</option><option value="boma">Boma</option><option value="coop">Coop</option><option value="run">Run</option><option value="paddock">Paddock</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Breed</label><input class="admin-form-control" name="breed" id="g-breed" placeholder="e.g. Isa Brown"></div>
            <div class="admin-form-group"><label class="admin-form-label">Head Count</label><input class="admin-form-control" type="number" name="head_count" id="g-count" min="0" value="0"></div>
            <div class="admin-form-group"><label class="admin-form-label">Housing</label><select class="admin-form-control" name="housing_id" id="g-housing"><option value="">-- None --</option><?php foreach ($houses as $h): ?><option value="<?= (int)$h['id'] ?>"><?= htmlspecialchars($h['house_name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Location</label><input class="admin-form-control" name="location" id="g-location" placeholder="e.g. Block A, Pen 3"></div>
            <div class="admin-form-group"><label class="admin-form-label">Status</label><select class="admin-form-control" name="status" id="g-status"><option value="active">Active</option><option value="sold">Sold</option><option value="archived">Archived</option></select></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" id="g-notes" rows="3"></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('group-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Group</button>
        </div></form>
    </div>
</div>
<script>
function openGroupModal(d){
    document.getElementById('group-modal-title').textContent=d?.id?'Edit Group':'Add Group';
    document.getElementById('g-id').value=d?.id||''; document.getElementById('g-name').value=d?.name||'';
    document.getElementById('g-species').value=d?.species||'Chicken'; document.getElementById('g-type').value=d?.group_type||'flock';
    document.getElementById('g-breed').value=d?.breed||''; document.getElementById('g-count').value=d?.head_count||0;
    document.getElementById('g-housing').value=d?.housing_id||''; document.getElementById('g-location').value=d?.location||'';
    document.getElementById('g-status').value=d?.status||'active'; document.getElementById('g-notes').value=d?.notes||'';
    document.getElementById('group-modal').style.display='flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('group-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>


<?php /* ══════════════════════════════════════════════════════════════════════════════════
   TAB: HOUSING
   ══════════════════════════════════════════════════════════════════════════════════ */ ?>
<?php elseif ($tab === 'housing'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Housing <?= helpTip('Buildings and enclosures where your animals live. Different species need different housing: coops for chickens, barns for cattle, pens for goats.') ?></h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Houses, pens, bomas, coops — all species in one place.</p></div>
        <button class="btn btn-primary" onclick="openHousingModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Add Housing</button>
    </div>
    <div class="table-responsive"><table class="admin-table">
        <thead><tr><th>Name</th><th>Code</th><th>Species</th><th>Type</th><th>Capacity</th><th>Location</th><th>Groups</th><th>Actions</th></tr></thead>
        <tbody><?php if (empty($houses)): ?>
        <tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;"><strong>No housing yet.</strong><br>Add your first house, pen or boma with <strong>+ Add Housing</strong>.</td></tr>
        <?php else: foreach ($houses as $h): ?>
        <tr>
            <td><strong><?= htmlspecialchars($h['house_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
            <td><?= htmlspecialchars($h['house_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge-pill badge-pill-info"><?= htmlspecialchars($h['species'] ?? 'Chicken', ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars(ucfirst($h['house_type'] ?? 'house'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= number_format((float)($h['capacity'] ?? 0)) ?></td>
            <td><?= htmlspecialchars($h['location'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int)($h['active_groups'] ?? 0) ?></td>
            <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openHousingModal(<?= htmlspecialchars(json_encode($h), ENT_QUOTES, "UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button><button class="btn btn-trans btn-sm" style="color:#dc2626;" onclick="if(confirm('Delete this housing?'))document.getElementById('delete-house-<?= (int)$h['id'] ?>').submit();"><i data-lucide="trash-2" style="width:13px;height:13px;"></i> Delete</button></div></td>
        <form id="delete-house-<?= (int)$h['id'] ?>" method="POST" style="display:none;"><input type="hidden" name="_action" value="delete_housing"><input type="hidden" name="id" value="<?= (int)$h['id'] ?>"></form>
        </tr>
        <?php endforeach; endif; ?></tbody>
    </table></div>
</div>

<!-- Housing Modal -->
<div id="housing-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 id="housing-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add Housing</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_housing"><input type="hidden" name="id" id="h-id">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">House Name *</label><input class="admin-form-control" name="house_name" id="h-name" required placeholder="e.g. Long House"></div>
            <div class="admin-form-group"><label class="admin-form-label">Code *</label><input class="admin-form-control" name="house_code" id="h-code" required placeholder="e.g. LH-01"></div>
            <div class="admin-form-group"><label class="admin-form-label">Species</label><select class="admin-form-control" name="species" id="h-species"><?php foreach ($spList as $sp): ?><option><?= $sp ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Housing Type</label><select class="admin-form-control" name="house_type" id="h-type"><option value="house">House</option><option value="pen">Pen</option><option value="boma">Boma</option><option value="coop">Coop</option><option value="run">Run</option><option value="paddock">Paddock</option><option value="pond">Pond</option><option value="shed">Shed</option><option value="kraal">Kraal</option><option value="sty">Sty</option><option value="hutch">Hutch</option><option value="barn">Barn</option><option value="milking_parlor">Milking Parlor</option><option value="aviary">Aviary</option><option value="brooder">Brooder</option><option value="incubator">Incubator</option><option value="greenhouse">Greenhouse</option><option value="nursery">Nursery</option><option value="store">Store</option><option value="other">Other</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Capacity</label><input class="admin-form-control" type="number" name="capacity" id="h-cap" min="0" value="0"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Location</label><input class="admin-form-control" name="location" id="h-loc" placeholder="e.g. North Farm"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Description</label><textarea class="admin-form-control" name="description" id="h-desc" rows="2"></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('housing-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
        </div></form>
    </div>
</div>
<script>
function openHousingModal(d){
    document.getElementById('housing-modal-title').textContent=d?.id?'Edit Housing':'Add Housing';
    document.getElementById('h-id').value=d?.id||''; document.getElementById('h-name').value=d?.house_name||'';
    document.getElementById('h-code').value=d?.house_code||''; document.getElementById('h-species').value=d?.species||'Chicken';
    document.getElementById('h-type').value=d?.house_type||'house'; document.getElementById('h-cap').value=d?.capacity||0;
    document.getElementById('h-loc').value=d?.location||''; document.getElementById('h-desc').value=d?.description||'';
    document.getElementById('housing-modal').style.display='flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('housing-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>


<?php /* ══════════════════════════════════════════════════════════════════════════════════
   TAB: HEALTH
   ══════════════════════════════════════════════════════════════════════════════════ */ ?>
<?php elseif ($tab === 'health'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Health Records <?= helpTip('Track every time an animal gets sick, receives treatment, or needs medicine. This helps you spot problems early.') ?></h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Treatments, vaccinations, deworming, checkups — all species.</p></div>
        <button class="btn btn-primary" onclick="openHealthModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Log Health Record</button>
    </div>
    <div class="table-responsive"><table class="admin-table">
        <thead><tr><th>Date</th><th>Species</th><th>Subject</th><th>Type</th><th>Product</th><th>Vet</th><th>Cost</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody><?php if (empty($healthRecs)): ?>
        <tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8;"><strong>No health records yet.</strong><br>Log treatments and vet visits here.</td></tr>
        <?php else: foreach ($healthRecs as $h): ?>
        <tr>
            <td><?= htmlspecialchars($h['record_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge-pill badge-pill-info"><?= htmlspecialchars($h['species'] ?? 'Chicken', ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars($h['subject'] ?? ($h['gname'] ?? ($h['aname'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars(ucfirst($h['record_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($h['product_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($h['vet_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= isset($h['cost']) && $h['cost'] ? number_format((float)$h['cost'], 2) : '-' ?></td>
            <td><span class="badge-pill <?= ($h['status'] ?? '') === 'completed' ? 'badge-pill-success' : (($h['status'] ?? '') === 'scheduled' ? 'badge-pill-warning' : 'badge-pill-danger') ?>"><?= htmlspecialchars($h['status'] ?? 'completed', ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openHealthModal(<?= htmlspecialchars(json_encode($h), ENT_QUOTES, "UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button><button class="btn btn-trans btn-sm" style="color:#dc2626;" onclick="if(confirm('Delete this health record?'))document.getElementById('delete-health-<?= (int)$h['id'] ?>').submit();"><i data-lucide="trash-2" style="width:13px;height:13px;"></i> Delete</button></div></td>
        <form id="delete-health-<?= (int)$h['id'] ?>" method="POST" style="display:none;"><input type="hidden" name="_action" value="delete_health"><input type="hidden" name="id" value="<?= (int)$h['id'] ?>"></form>
        </tr>
        <?php endforeach; endif; ?></tbody>
    </table></div>
</div>

<!-- Health Modal -->
<div id="health-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 id="health-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Log Health Record</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_health"><input type="hidden" name="id" id="hl-id">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" name="record_date" id="hl-date" value="<?= date('Y-m-d') ?>"></div>
            <div class="admin-form-group"><label class="admin-form-label">Species *</label><select class="admin-form-control" name="species" id="hl-species"><?php foreach ($spList as $sp): ?><option><?= $sp ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Subject (Animal/Group name)</label><input class="admin-form-control" name="subject" id="hl-subject" placeholder="e.g. Bessie, Herd A, or general"></div>
            <div class="admin-form-group"><label class="admin-form-label">Record Type</label><select class="admin-form-control" name="record_type" id="hl-type"><option value="treatment">Treatment</option><option value="vaccination">Vaccination</option><option value="deworming">Deworming</option><option value="checkup">Checkup</option><option value="mortality">Mortality</option><option value="vitamins">Vitamins</option><option value="antibiotic">Antibiotic</option><option value="observation">Observation</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Product / Medicine</label><input class="admin-form-control" name="product_name" id="hl-product" placeholder="e.g. Amprolium, Ivermectin"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Vaccine Name (if vaccination)</label><input class="admin-form-control" name="vaccine_name" id="hl-vaccine" placeholder="e.g. Newcastle, FMD"></div>
            <div class="admin-form-group"><label class="admin-form-label">Dosage</label><input class="admin-form-control" name="dosage" id="hl-dosage" placeholder="e.g. 3ml IM"></div>
            <div class="admin-form-group"><label class="admin-form-label">Route</label><select class="admin-form-control" name="route" id="hl-route"><option value="oral">Oral</option><option value="injection">Injection</option><option value="spray">Spray</option><option value="water">Water</option><option value="feed">Feed</option><option value="eye_drop">Eye Drop</option><option value="wing_web">Wing Web</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Birds / Animals Treated</label><input class="admin-form-control" type="number" name="birds_treated" id="hl-treated" min="0" value="0"></div>
            <div class="admin-form-group"><label class="admin-form-label">Mortality Count</label><input class="admin-form-control" type="number" name="mortality_count" id="hl-mortality" min="0" value="0"></div>
            <div class="admin-form-group"><label class="admin-form-label">Vet Name</label><input class="admin-form-control" name="vet_name" id="hl-vet" placeholder="Dr. Kamau"></div>
            <div class="admin-form-group"><label class="admin-form-label">Cost (KES)</label><input class="admin-form-control" type="number" step="0.01" name="cost" id="hl-cost" value="0"></div>
            <div class="admin-form-group"><label class="admin-form-label">Next Due Date</label><input class="admin-form-control" type="date" name="next_due_date" id="hl-next"></div>
            <div class="admin-form-group"><label class="admin-form-label">Status</label><select class="admin-form-control" name="status" id="hl-status"><option value="completed">Completed</option><option value="scheduled">Scheduled</option><option value="ongoing">Ongoing</option><option value="missed">Missed</option></select></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" id="hl-notes" rows="2"></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('health-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Record</button>
        </div></form>
    </div>
</div>
<script>
function openHealthModal(d){
    document.getElementById('health-modal-title').textContent=d?.id?'Edit Health Record':'Log Health Record';
    document.getElementById('hl-id').value=d?.id||''; document.getElementById('hl-date').value=d?.record_date||'<?= date("Y-m-d") ?>';
    document.getElementById('hl-species').value=d?.species||'Chicken'; document.getElementById('hl-subject').value=d?.subject||'';
    document.getElementById('hl-type').value=d?.record_type||'treatment'; document.getElementById('hl-product').value=d?.product_name||'';
    document.getElementById('hl-vaccine').value=d?.vaccine_name||''; document.getElementById('hl-dosage').value=d?.dosage||'';
    document.getElementById('hl-route').value=d?.route||'oral'; document.getElementById('hl-treated').value=d?.birds_treated||0;
    document.getElementById('hl-mortality').value=d?.mortality_count||0; document.getElementById('hl-vet').value=d?.vet_name||'';
    document.getElementById('hl-cost').value=d?.cost||''; document.getElementById('hl-next').value=d?.next_due_date||'';
    document.getElementById('hl-status').value=d?.status||'completed'; document.getElementById('hl-notes').value=d?.notes||'';
    document.getElementById('health-modal').style.display='flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('health-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>


<?php /* ══════════════════════════════════════════════════════════════════════════════════
   TAB: VACCINATIONS — schedule + vaccine guides
   ══════════════════════════════════════════════════════════════════════════════════ */ ?>
<?php elseif ($tab === 'vaccinations'): ?>
<!-- Vaccine Guides -->
<div class="admin-card" style="margin-bottom:20px;">
    <h3 style="margin:0 0 14px;font-family:'Outfit',sans-serif;font-size:1.05rem;">Vaccine Guides by Species</h3>
    <?php
    $grouped = [];
    foreach ($vaccineGuides as $vg) { $grouped[$vg['species']][] = $vg; }
    if (empty($grouped)): ?>
    <p style="color:#94a3b8;text-align:center;padding:18px;">No vaccine guides yet.</p>
    <?php else: foreach ($grouped as $sp => $guides): ?>
    <details style="margin-bottom:10px;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;" <?= $speciesFilter === $sp ? 'open' : '' ?>>
        <summary style="padding:12px 16px;background:#f8fafc;cursor:pointer;font-weight:700;font-size:0.92rem;display:flex;align-items:center;gap:8px;">
            <span class="badge-pill badge-pill-info" style="font-size:0.78rem;"><?= htmlspecialchars($sp) ?></span>
            <?= count($guides) ?> vaccines
        </summary>
        <div style="padding:12px 16px;">
            <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                <tr style="border-bottom:1px solid #e2e8f0;"><th style="text-align:left;padding:6px 4px;color:#64748b;">Disease</th><th style="text-align:left;padding:6px 4px;color:#64748b;">Vaccine</th><th style="text-align:left;padding:6px 4px;color:#64748b;">When</th><th style="text-align:left;padding:6px 4px;color:#64748b;">Route</th><th style="text-align:left;padding:6px 4px;color:#64748b;">Notes</th></tr>
                <?php foreach ($guides as $g): ?>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:6px 4px;font-weight:600;"><?= htmlspecialchars($g['disease']) ?></td>
                    <td style="padding:6px 4px;"><?= htmlspecialchars($g['vaccine_name']) ?></td>
                    <td style="padding:6px 4px;"><?= htmlspecialchars($g['age_or_timing']) ?></td>
                    <td style="padding:6px 4px;"><?= htmlspecialchars($g['route']) ?></td>
                    <td style="padding:6px 4px;color:#64748b;"><?= htmlspecialchars($g['notes'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </details>
    <?php endforeach; endif; ?>
</div>

<!-- Vaccination Schedule -->
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Vaccination Schedule <?= helpTip('Planned injections that protect your animals from diseases. Schedule them here and mark them done when given.') ?></h3></div>
        <button class="btn btn-primary" onclick="openVacModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Schedule Vaccine</button>
    </div>
    <div class="table-responsive"><table class="admin-table">
        <thead><tr><th>Scheduled</th><th>Species</th><th>Subject</th><th>Vaccine</th><th>Administered</th><th>Next Due</th><th>Cost</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody><?php if (empty($vaccinations)): ?>
        <tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8;"><strong>No vaccinations scheduled.</strong></td></tr>
        <?php else:
            $today = date('Y-m-d');
            foreach ($vaccinations as $v): $overdue = $v['status']==='scheduled' && $v['scheduled_date'] < $today; ?>
        <tr style="<?= $overdue ? 'background:#fff7ed;' : '' ?>">
            <td><?= $overdue ? '<span style="color:#dc2626;font-weight:700;">⚠ </span>' : '' ?><?= htmlspecialchars($v['scheduled_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge-pill badge-pill-info"><?= htmlspecialchars($v['species'] ?? 'Chicken', ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars($v['subject'] ?? ($v['gname'] ?? ($v['aname'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($v['vaccine_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= $v['administered_date'] ?: '<span style="color:#94a3b8;">Pending</span>' ?></td>
            <td><?= $v['next_due_date'] ?? '-' ?></td>
            <td><?= isset($v['cost']) && $v['cost'] ? number_format((float)$v['cost'], 2) : '-' ?></td>
            <td><span class="badge-pill <?= ($v['status'] ?? '') === 'completed' ? 'badge-pill-success' : (($v['status'] ?? '') === 'missed' ? 'badge-pill-danger' : 'badge-pill-warning') ?>"><?= htmlspecialchars($v['status'] ?? 'scheduled', ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openVacModal(<?= htmlspecialchars(json_encode($v), ENT_QUOTES, "UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button><button class="btn btn-trans btn-sm" style="color:#dc2626;" onclick="if(confirm('Delete this vaccination?'))document.getElementById('delete-vac-<?= (int)$v['id'] ?>').submit();"><i data-lucide="trash-2" style="width:13px;height:13px;"></i> Delete</button></div></td>
        <form id="delete-vac-<?= (int)$v['id'] ?>" method="POST" style="display:none;"><input type="hidden" name="_action" value="delete_vaccination"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"></form>
        </tr>
        <?php endforeach; endif; ?></tbody>
    </table></div>
</div>

<!-- Vaccination Modal -->
<div id="vac-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:520px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 id="vac-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Schedule Vaccination</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_vaccination"><input type="hidden" name="id" id="v-id">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group"><label class="admin-form-label">Species *</label><select class="admin-form-control" name="species" id="v-species"><?php foreach ($spList as $sp): ?><option><?= $sp ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Vaccine Name *</label><input class="admin-form-control" name="vaccine_name" id="v-name" required placeholder="e.g. Newcastle, FMD"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Subject (Animal / Group)</label><input class="admin-form-control" name="subject" id="v-subject" placeholder="e.g. Bessie, Herd A, or general"></div>
            <div class="admin-form-group"><label class="admin-form-label">Scheduled Date *</label><input class="admin-form-control" type="date" name="scheduled_date" id="v-sched" required></div>
            <div class="admin-form-group"><label class="admin-form-label">Administered Date</label><input class="admin-form-control" type="date" name="administered_date" id="v-admin"></div>
            <div class="admin-form-group"><label class="admin-form-label">Next Due Date</label><input class="admin-form-control" type="date" name="next_due_date" id="v-next"></div>
            <div class="admin-form-group"><label class="admin-form-label">Status</label><select class="admin-form-control" name="status" id="v-status"><option value="scheduled">Scheduled</option><option value="completed">Completed</option><option value="missed">Missed</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Dosage</label><input class="admin-form-control" name="dosage" id="v-dosage" placeholder="e.g. 3ml"></div>
            <div class="admin-form-group"><label class="admin-form-label">Cost (KES)</label><input class="admin-form-control" type="number" step="0.01" name="cost" id="v-cost" value="0"></div>
            <div class="admin-form-group"><label class="admin-form-label">Withdrawal Period (days)</label><input class="admin-form-control" type="number" name="withdrawal_days" id="v-withdrawal" min="0" placeholder="e.g. 7 for eggs, 14 for meat"><small style="color:#64748b;font-size:0.75rem;">Days before eggs/meat/milk can be sold after treatment</small></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" id="v-notes" rows="2"></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('vac-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
        </div></form>
    </div>
</div>
<script>
function openVacModal(d){
    document.getElementById('vac-modal-title').textContent=d?.id?'Edit Vaccination':'Schedule Vaccination';
    document.getElementById('v-id').value=d?.id||''; document.getElementById('v-species').value=d?.species||'Chicken';
    document.getElementById('v-name').value=d?.vaccine_name||''; document.getElementById('v-subject').value=d?.subject||'';
    document.getElementById('v-sched').value=d?.scheduled_date||''; document.getElementById('v-admin').value=d?.administered_date||'';
    document.getElementById('v-next').value=d?.next_due_date||''; document.getElementById('v-status').value=d?.status||'scheduled';
    document.getElementById('v-dosage').value=d?.dosage||''; document.getElementById('v-cost').value=d?.cost||'';
    document.getElementById('v-withdrawal').value=d?.withdrawal_days||''; document.getElementById('v-notes').value=d?.notes||''; document.getElementById('vac-modal').style.display='flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('vac-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>


<?php /* ══════════════════════════════════════════════════════════════════════════════════
   TAB: PRODUCTION
   ══════════════════════════════════════════════════════════════════════════════════ */ ?>
<?php elseif ($tab === 'production'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Daily Production Log <?= helpTip('Record what your animals produce each day: eggs from chickens, milk from cows, weight gain, etc.') ?></h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Record eggs, milk, weight, feed and mortality — all species.</p></div>
        <button class="btn btn-primary" onclick="openProdModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Log Production</button>
    </div>
    <div class="table-responsive"><table class="admin-table">
        <thead><tr><th>Date</th><th>Species</th><th>Group</th><th>Eggs</th><th>Milk (L)</th><th>Weight (kg)</th><th>Feed (kg)</th><th>Mortality</th><th>Actions</th></tr></thead>
        <tbody><?php if (empty($prodRecs)): ?>
        <tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8;"><strong>No production logs yet.</strong></td></tr>
        <?php else: foreach ($prodRecs as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['record_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge-pill badge-pill-info"><?= htmlspecialchars($p['species'] ?? 'Chicken', ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars($p['gname'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><strong><?= number_format((float)($p['eggs_collected'] ?? 0)) ?></strong></td>
            <td><strong><?= number_format((float)($p['milk_litres'] ?? 0), 1) ?></strong></td>
            <td><strong><?= number_format((float)($p['weight_kg'] ?? 0), 1) ?></strong></td>
            <td><?= number_format((float)($p['feed_consumed_kg'] ?? 0), 1) ?></td>
            <td><strong style="color:<?= ($p['mortality'] ?? 0) > 0 ? '#dc2626' : '#1e293b' ?>"><?= $p['mortality'] ?? 0 ?></strong></td>
            <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openProdModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, "UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button><button class="btn btn-trans btn-sm" style="color:#dc2626;" onclick="if(confirm('Delete this production record?'))document.getElementById('delete-prod-<?= (int)$p['id'] ?>').submit();"><i data-lucide="trash-2" style="width:13px;height:13px;"></i> Delete</button></div></td>
        <form id="delete-prod-<?= (int)$p['id'] ?>" method="POST" style="display:none;"><input type="hidden" name="_action" value="delete_production"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"></form>
        </tr>
        <?php endforeach; endif; ?></tbody>
    </table></div>
</div>

<!-- Production Modal -->
<div id="prod-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 id="prod-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Log Production</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_production"><input type="hidden" name="id" id="p-id">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group"><label class="admin-form-label">Species *</label><select class="admin-form-control" name="species" id="p-species"><?php foreach ($spList as $sp): ?><option><?= $sp ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Group</label><select class="admin-form-control" name="group_id" id="p-group"><option value="">-- None --</option><?php foreach ($groupList as $gl): ?><option value="<?= (int)$gl['id'] ?>"><?= htmlspecialchars($gl['label'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" name="record_date" id="p-date" value="<?= date('Y-m-d') ?>"></div>
            <div class="admin-form-group"><label class="admin-form-label">Eggs Collected</label><input class="admin-form-control" type="number" name="eggs_collected" id="p-eggs" min="0" value="0"></div>
            <div class="admin-form-group"><label class="admin-form-label">Cracked Eggs</label><input class="admin-form-control" type="number" name="cracked_eggs" id="p-cracked" min="0" value="0"></div>
            <div class="admin-form-group"><label class="admin-form-label">Milk (Litres)</label><input class="admin-form-control" type="number" step="0.1" name="milk_litres" id="p-milk" min="0" value="0"></div>
            <div class="admin-form-group"><label class="admin-form-label">Meat/Weight (kg)</label><input class="admin-form-control" type="number" step="0.1" name="meat_weight_kg" id="p-meat" min="0" value="0"></div>
            <div class="admin-form-group"><label class="admin-form-label">Average Weight (kg)</label><input class="admin-form-control" type="number" step="0.1" name="weight_kg" id="p-weight" min="0" value="0"></div>
            <div class="admin-form-group"><label class="admin-form-label">Feed Consumed (kg)</label><input class="admin-form-control" type="number" step="0.1" name="feed_consumed_kg" id="p-feed" min="0" value="0"></div>
            <div class="admin-form-group"><label class="admin-form-label">Mortality</label><input class="admin-form-control" type="number" name="mortality" id="p-mort" min="0" value="0" style="border-color:#fca5a5;"></div>
            <div class="admin-form-group"><label class="admin-form-label">Sold</label><input class="admin-form-control" type="number" name="sold_count" id="p-sold" min="0" value="0"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" id="p-notes" rows="2"></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('prod-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Log</button>
        </div></form>
    </div>
</div>
<script>
function openProdModal(d){
    document.getElementById('prod-modal-title').textContent=d?.id?'Edit Production':'Log Production';
    document.getElementById('p-id').value=d?.id||''; document.getElementById('p-species').value=d?.species||'Chicken';
    document.getElementById('p-group').value=d?.group_id||''; document.getElementById('p-date').value=d?.record_date||'<?= date("Y-m-d") ?>';
    document.getElementById('p-eggs').value=d?.eggs_collected||0; document.getElementById('p-cracked').value=d?.cracked_eggs||0;
    document.getElementById('p-milk').value=d?.milk_litres||0; document.getElementById('p-meat').value=d?.meat_weight_kg||0;
    document.getElementById('p-weight').value=d?.weight_kg||0; document.getElementById('p-feed').value=d?.feed_consumed_kg||0;
    document.getElementById('p-mort').value=d?.mortality||0; document.getElementById('p-sold').value=d?.sold_count||0;
    document.getElementById('p-notes').value=d?.notes||''; document.getElementById('prod-modal').style.display='flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('prod-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>


<?php /* ══════════════════════════════════════════════════════════════════════════════════
   TAB: BREEDING
   ══════════════════════════════════════════════════════════════════════════════════ */ ?>
<?php elseif ($tab === 'breeding'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Breeding Records <?= helpTip('Track when animals mate, become pregnant, and give birth. Helps you plan for new babies on the farm.') ?></h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Mating events, expected births, and offspring for all species.</p></div>
        <button class="btn btn-primary" onclick="openBreedingModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Record Breeding</button>
    </div>
    <div class="table-responsive"><table class="admin-table">
        <thead><tr><th>Date</th><th>Species</th><th>Dam (Mother)</th><th>Sire (Father)</th><th>Expected Birth</th><th>Status</th><th>Notes</th><th>Actions</th></tr></thead>
        <tbody><?php if (empty($breedingRecs)): ?>
        <tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;"><strong>No breeding records yet.</strong></td></tr>
        <?php else: foreach ($breedingRecs as $b): ?>
        <tr>
            <td><?= htmlspecialchars($b['date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge-pill badge-pill-info"><?= htmlspecialchars($b['species'] ?? 'Chicken', ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars($b['type'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($b['male_parent'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($b['due_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge-pill <?= ($b['status'] ?? '') === 'Born' ? 'badge-pill-success' : (($b['status'] ?? '') === 'Pending' ? 'badge-pill-warning' : 'badge-pill-danger') ?>"><?= htmlspecialchars($b['status'] ?? 'Pending', ENT_QUOTES, 'UTF-8') ?></span></td>
            <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($b['notes'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openBreedingModal(<?= htmlspecialchars(json_encode($b), ENT_QUOTES, "UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button><button class="btn btn-trans btn-sm" style="color:#dc2626;" onclick="if(confirm('Delete this breeding record?'))document.getElementById('delete-breed-<?= (int)$b['id'] ?>').submit();"><i data-lucide="trash-2" style="width:13px;height:13px;"></i> Delete</button></div></td>
        <form id="delete-breed-<?= (int)$b['id'] ?>" method="POST" style="display:none;"><input type="hidden" name="_action" value="delete_breeding"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"></form>
        </tr>
        <?php endforeach; endif; ?></tbody>
    </table></div>
</div>

<!-- Breeding Modal -->
<div id="breeding-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:520px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 id="breeding-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Record Breeding</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_breeding"><input type="hidden" name="id" id="br-id">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Species *</label><select class="admin-form-control" name="species" id="br-species" onchange="calcDueDate()"><?php foreach ($spList as $sp): ?><option><?= $sp ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Dam (Mother)</label><input class="admin-form-control" name="dam" id="br-dam" placeholder="e.g. A-007"></div>
            <div class="admin-form-group"><label class="admin-form-label">Sire (Father)</label><input class="admin-form-control" name="sire" id="br-sire" placeholder="e.g. A-003"></div>
            <div class="admin-form-group"><label class="admin-form-label">Breeding Date</label><input class="admin-form-control" type="date" name="breeding_date" id="br-date" value="<?= date('Y-m-d') ?>" onchange="calcDueDate()"></div>
            <div class="admin-form-group"><label class="admin-form-label">Expected Birth</label><input class="admin-form-control" type="date" name="expected_birth" id="br-exp"></div>
            <div class="admin-form-group"><label class="admin-form-label">Status</label><select class="admin-form-control" name="status" id="br-status"><option>Pending</option><option>Born</option><option>Failed</option><option>Aborted</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Offspring Count</label><input class="admin-form-control" type="number" name="offspring_count" id="br-offspring" min="0" value="0"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" id="br-notes" rows="3"></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('breeding-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
        </div></form>
    </div>
</div>
<script>
const gestationDays={Chicken:21,Cattle:283,Goat:150,Sheep:150,Pig:114,Rabbit:31,Duck:28,Other:0};
function calcDueDate(){
    const d=document.getElementById('br-date').value;
    if(!d)return;
    const sp=document.getElementById('br-species').value;
    const days=gestationDays[sp]||0;
    if(!days)return;
    const dt=new Date(d); dt.setDate(dt.getDate()+days);
    document.getElementById('br-exp').value=dt.toISOString().split('T')[0];
}
function openBreedingModal(d){
    document.getElementById('breeding-modal-title').textContent=d?.id?'Edit Breeding':'Record Breeding';
    document.getElementById('br-id').value=d?.id||''; document.getElementById('br-species').value=d?.species||'Chicken';
    document.getElementById('br-dam').value=d?.type||''; document.getElementById('br-sire').value=d?.male_parent||'';
    document.getElementById('br-date').value=d?.date||'<?= date("Y-m-d") ?>'; document.getElementById('br-exp').value=d?.due_date||'';
    document.getElementById('br-status').value=d?.status||'Pending'; document.getElementById('br-offspring').value=d?.offspring_count||0;
    document.getElementById('br-notes').value=d?.notes||''; document.getElementById('breeding-modal').style.display='flex';
    calcDueDate();
}
document.addEventListener('click',e=>{ const m=document.getElementById('breeding-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>


<?php /* ══════════════════════════════════════════════════════════════════════════════════
   TAB: FEEDING — standards + daily log
   ══════════════════════════════════════════════════════════════════════════════════ */ ?>
<?php elseif ($tab === 'feeding'): ?>
<!-- Feeding Standards -->
<div class="admin-card" style="margin-bottom:20px;">
    <h3 style="margin:0 0 14px;font-family:'Outfit',sans-serif;font-size:1.05rem;">Feeding Standards</h3>
    <?php if (empty($feedStandards)): ?>
    <p style="color:#94a3b8;text-align:center;padding:18px;">No feeding standards configured.</p>
    <?php else: ?>
    <div class="table-responsive"><table class="admin-table">
        <thead><tr><th>Species</th><th>Type</th><th>Week / Stage</th><th>Feed per Head/Day</th><th>Feed Type</th><th>Notes</th></tr></thead>
        <tbody><?php foreach ($feedStandards as $fs): ?>
        <tr>
            <td><span class="badge-pill badge-pill-info"><?= htmlspecialchars($fs['species'] ?? 'Chicken', ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars(ucfirst($fs['bird_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= $fs['week_number'] ?? '-' ?></td>
            <td><strong><?= number_format((float)($fs['feed_per_bird_per_day_grams'] ?? 0), 1) ?> g</strong></td>
            <td><?= htmlspecialchars($fs['feed_type'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($fs['notes'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
</div>

<!-- Feed Log -->
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Daily Feed Log <?= helpTip('Record what and how much you feed your animals each day. This helps you track feed costs and make sure animals eat the right amount.') ?></h3></div>
        <button class="btn btn-primary" onclick="openFeedModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Log Feeding</button>
    </div>
    <div class="table-responsive"><table class="admin-table">
        <thead><tr><th>Date</th><th>Species</th><th>Group</th><th>Feed Type</th><th>Qty (kg)</th><th>Cost</th><th>Notes</th><th>Actions</th></tr></thead>
        <tbody><?php if (empty($feedLogs)): ?>
        <tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;"><strong>No feed logs yet.</strong></td></tr>
        <?php else: foreach ($feedLogs as $fl): ?>
        <tr>
            <td><?= htmlspecialchars($fl['record_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge-pill badge-pill-info"><?= htmlspecialchars($fl['species'] ?? 'Chicken', ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars($fl['gname'] ?? ($fl['aname'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($fl['feed_type'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><strong><?= number_format((float)($fl['quantity_kg'] ?? 0), 1) ?></strong></td>
            <td><?= isset($fl['cost']) && $fl['cost'] ? number_format((float)$fl['cost'], 2) : '-' ?></td>
            <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($fl['notes'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openFeedModal(<?= htmlspecialchars(json_encode($fl), ENT_QUOTES, "UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button><button class="btn btn-trans btn-sm" style="color:#dc2626;" onclick="if(confirm('Delete this feed log?'))document.getElementById('delete-feed-<?= (int)$fl['id'] ?>').submit();"><i data-lucide="trash-2" style="width:13px;height:13px;"></i> Delete</button></div></td>
        <form id="delete-feed-<?= (int)$fl['id'] ?>" method="POST" style="display:none;"><input type="hidden" name="_action" value="delete_feeding"><input type="hidden" name="id" value="<?= (int)$fl['id'] ?>"></form>
        </tr>
        <?php endforeach; endif; ?></tbody>
    </table></div>
</div>

<!-- Feed Log Modal -->
<div id="feed-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 id="feed-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Log Feeding</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_feed_log"><input type="hidden" name="id" id="fl-id">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" name="record_date" id="fl-date" value="<?= date('Y-m-d') ?>"></div>
            <div class="admin-form-group"><label class="admin-form-label">Species *</label><select class="admin-form-control" name="species" id="fl-species"><?php foreach ($spList as $sp): ?><option><?= $sp ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Group</label><select class="admin-form-control" name="group_id" id="fl-group"><option value="">-- None --</option><?php foreach ($groupList as $gl): ?><option value="<?= (int)$gl['id'] ?>"><?= htmlspecialchars($gl['label'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Feed Type</label><input class="admin-form-control" name="feed_type" id="fl-type" placeholder="e.g. Layers Mash, Dairy Meal"></div>
            <div class="admin-form-group"><label class="admin-form-label">Quantity (kg)</label><input class="admin-form-control" type="number" step="0.1" name="quantity_kg" id="fl-qty" min="0" value="0"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Cost (KES)</label><input class="admin-form-control" type="number" step="0.01" name="cost" id="fl-cost" value="0"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" id="fl-notes" rows="2"></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('feed-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
        </div></form>
    </div>
</div>
<script>
function openFeedModal(d){
    document.getElementById('feed-modal-title').textContent=d?.id?'Edit Feed Log':'Log Feeding';
    document.getElementById('fl-id').value=d?.id||''; document.getElementById('fl-date').value=d?.record_date||'<?= date("Y-m-d") ?>';
    document.getElementById('fl-species').value=d?.species||'Chicken'; document.getElementById('fl-group').value=d?.group_id||'';
    document.getElementById('fl-type').value=d?.feed_type||''; document.getElementById('fl-qty').value=d?.quantity_kg||0;
    document.getElementById('fl-cost').value=d?.cost||''; document.getElementById('fl-notes').value=d?.notes||'';
    document.getElementById('feed-modal').style.display='flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('feed-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>


<?php /* ══════════════════════════════════════════════════════════════════════════════════
   TAB: POULTRY — deep tools folded in
   ══════════════════════════════════════════════════════════════════════════════════ */ ?>
<?php elseif ($tab === 'poultry'): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;">

    <!-- Broiler Weighings -->
    <div class="admin-card">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div class="stat-card-icon accent" style="width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;"><i data-lucide="scale" style="width:20px;height:20px;"></i></div>
            <div><h3 style="margin:0;font-size:1rem;">Broiler Weighings</h3><p style="margin:2px 0 0;font-size:0.82rem;color:#64748b;">Track growth per batch</p></div>
        </div>
        <?php
        $broilerCount = 0;
        try { $broilerCount = $pdo->query("SELECT COUNT(*) FROM broiler_weighings")->fetchColumn(); } catch (Exception $e) {}
        ?>
        <p style="margin:10px 0 14px;font-size:0.9rem;"><strong><?= $broilerCount ?></strong> weighing records</p>
        <a href="broiler.php" class="btn btn-primary" style="width:100%;justify-content:center;"><i data-lucide="external-link" style="width:16px;height:16px;"></i> Open Broiler Tool</a>
    </div>

    <!-- Hatchery -->
    <div class="admin-card">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div class="stat-card-icon info" style="width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;"><i data-lucide="baby" style="width:20px;height:20px;"></i></div>
            <div><h3 style="margin:0;font-size:1rem;">Hatchery</h3><p style="margin:2px 0 0;font-size:0.82rem;color:#64748b;">Setting dates, hatch rates</p></div>
        </div>
        <?php
        $hatchCount = 0;
        try { $hatchCount = $pdo->query("SELECT COUNT(*) FROM hatchery_batches")->fetchColumn(); } catch (Exception $e) {}
        ?>
        <p style="margin:10px 0 14px;font-size:0.9rem;"><strong><?= $hatchCount ?></strong> hatchery batches</p>
        <a href="hatchery.php" class="btn btn-primary" style="width:100%;justify-content:center;"><i data-lucide="external-link" style="width:16px;height:16px;"></i> Open Hatchery Tool</a>
    </div>

    <!-- Egg Grading -->
    <div class="admin-card">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div class="stat-card-icon" style="width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#fef3c7;color:#d97706;"><i data-lucide="circle-dot" style="width:20px;height:20px;"></i></div>
            <div><h3 style="margin:0;font-size:1rem;">Egg Grading</h3><p style="margin:2px 0 0;font-size:0.82rem;color:#64748b;">Grade by size &amp; crate</p></div>
        </div>
        <?php
        $gradeCount = 0;
        try { $gradeCount = $pdo->query("SELECT COUNT(*) FROM daily_egg_grading")->fetchColumn(); } catch (Exception $e) {}
        ?>
        <p style="margin:10px 0 14px;font-size:0.9rem;"><strong><?= $gradeCount ?></strong> grading records</p>
        <a href="egg_grading.php" class="btn btn-primary" style="width:100%;justify-content:center;"><i data-lucide="external-link" style="width:16px;height:16px;"></i> Open Grading Tool</a>
    </div>

</div>
<!-- ══════ WEIGHT TRACKING TAB ══════ -->
<?php elseif ($tab === 'weights'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Weight Tracking <?= helpTip('Weigh your animals regularly to make sure they are growing well. If weight drops, something may be wrong.') ?></h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Record and monitor individual animal or group weights over time.</p>
        </div>
        <button class="btn btn-primary" onclick="openWeightModal()"><i data-lucide="plus" style="width:16px;height:16px;"></i> Record Weight</button>
    </div>

    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Animal</th><th>Group</th><th>Species</th><th>Weight (kg)</th><th>Notes</th></tr></thead>
            <tbody>
            <?php if (empty($weightRecs)): ?>
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">No weight records yet. Click <strong>Record Weight</strong> to start tracking.</td></tr>
            <?php else: foreach ($weightRecs as $w): ?>
                <tr>
                    <td><?= htmlspecialchars($w['recorded_date'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars(($w['tag'] ? $w['tag'].' - ' : '').($w['aname'] ?? '—'), ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($w['gname'] ?? '—', ENT_QUOTES) ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($w['species'], ENT_QUOTES) ?></span></td>
                    <td><strong><?= number_format((float)$w['weight_kg'], 2) ?></strong></td>
                    <td><?= htmlspecialchars($w['notes'] ?? '', ENT_QUOTES) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="weight-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
        <h3 id="weight-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Record Weight</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_weight"><input type="hidden" name="id" id="w-id">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group"><label class="admin-form-label">Animal</label><select class="admin-form-control" name="animal_id" id="w-animal"><option value="">-- Select Animal --</option><?php foreach ($animalList as $a): ?><option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['label'], ENT_QUOTES) ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Group</label><select class="admin-form-control" name="group_id" id="w-group"><option value="">-- Select Group --</option><?php foreach ($groupList as $g): ?><option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['label'], ENT_QUOTES) ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Species *</label><select class="admin-form-control" name="species" id="w-species" required><?php foreach ($spList as $sp): ?><option><?= $sp ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Weight (kg) *</label><input class="admin-form-control" type="number" step="0.01" min="0" name="weight_kg" id="w-weight" required placeholder="0.00"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" name="recorded_date" id="w-date" value="<?= date('Y-m-d') ?>" required></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" id="w-notes" rows="2" placeholder="Optional notes..."></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('weight-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save</button>
        </div></form>
    </div>
</div>
<script>
function openWeightModal(d){
    document.getElementById('weight-modal-title').textContent=d?.id?'Edit Weight':'Record Weight';
    document.getElementById('w-id').value=d?.id||''; document.getElementById('w-animal').value=d?.animal_id||'';
    document.getElementById('w-group').value=d?.group_id||''; document.getElementById('w-species').value=d?.species||'Chicken';
    document.getElementById('w-weight').value=d?.weight_kg||''; document.getElementById('w-date').value=d?.recorded_date||'<?= date('Y-m-d') ?>';
    document.getElementById('w-notes').value=d?.notes||'';
    document.getElementById('weight-modal').style.display='flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('weight-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>

<!-- ══════ MILKING RECORDS TAB ══════ -->
<?php elseif ($tab === 'milking'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Milking Records <?= helpTip('Track how much milk each cow (or goat) gives every milking session. Helps you see which animals produce the most.') ?></h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Track daily milk production by animal and session.</p></div>
        <button class="btn btn-primary" onclick="openMilkingModal()"><i data-lucide="plus" style="width:16px;height:16px;"></i> Record Milking</button>
    </div>
    <?php if (!empty($todayMilking)): ?>
    <div style="display:flex;gap:16px;margin-bottom:16px;">
        <?php foreach ($todayMilking as $tm): ?>
        <div style="background:#DBEAFE;border-radius:8px;padding:12px 16px;border:1px solid #93C5FD;">
            <div style="font-weight:600;color:#1E40AF;"><?= ucfirst($tm['milking_time']) ?></div>
            <div style="font-size:1.5rem;font-weight:700;color:#2563EB;"><?= number_format((float)$tm['total'], 1) ?> L</div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Time</th><th>Animal</th><th>Group</th><th>Litres</th><th>Fat %</th><th>Grade</th><th>Notes</th></tr></thead>
            <tbody>
            <?php if (empty($milkingRecs)): ?>
                <tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">No milking records yet.</td></tr>
            <?php else: foreach ($milkingRecs as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['milking_date'], ENT_QUOTES) ?></td>
                    <td><span class="badge badge-info\"><?= ucfirst($m['milking_time']) ?></span></td>
                    <td><?= htmlspecialchars(($m['tag'] ? $m['tag'].' - ' : '').($m['aname'] ?? '—'), ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($m['gname'] ?? '—', ENT_QUOTES) ?></td>
                    <td><strong><?= number_format((float)$m['litres'], 1) ?></strong></td>
                    <td><?= $m['fat_pct'] ? number_format((float)$m['fat_pct'], 1).'%' : '—' ?></td>
                    <td><?= htmlspecialchars($m['quality_grade'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($m['notes'] ?? '', ENT_QUOTES) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div id="milking-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Record Milking</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_milking">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group"><label class="admin-form-label">Animal</label><select class="admin-form-control" name="animal_id"><option value="">-- Select --</option><?php foreach ($animalList as $a): ?><option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['label'], ENT_QUOTES) ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Group</label><select class="admin-form-control" name="group_id"><option value="">-- Select --</option><?php foreach ($groupList as $g): ?><option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['label'], ENT_QUOTES) ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Species</label><select class="admin-form-control" name="species"><option>Cattle</option><option>Goat</option><option>Sheep</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" name="milking_date" value="<?= date('Y-m-d') ?>" required></div>
            <div class="admin-form-group"><label class="admin-form-label">Session *</label><select class="admin-form-control" name="milking_time"><option value="morning">Morning</option><option value="midday">Midday</option><option value="evening">Evening</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Litres *</label><input class="admin-form-control" type="number" step="0.1" name="litres" required placeholder="0.0"></div>
            <div class="admin-form-group"><label class="admin-form-label">Fat %</label><input class="admin-form-control" type="number" step="0.1" name="fat_pct" placeholder="e.g. 3.5"></div>
            <div class="admin-form-group"><label class="admin-form-label">Grade</label><select class="admin-form-control" name="quality_grade"><option value="A">A - Excellent</option><option value="B">B - Good</option><option value="C">C - Fair</option><option value="rejected">Rejected</option></select></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" rows="2"></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('milking-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;">Save</button>
        </div></form>
    </div>
</div>
<script>function openMilkingModal(){document.getElementById('milking-modal').style.display='flex';}
document.addEventListener('click',e=>{const m=document.getElementById('milking-modal');if(m&&e.target===m)m.style.display='none';});</script>

<!-- ══════ MORTALITY TAB ══════ -->
<?php elseif ($tab === 'mortality'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Mortality Records <?= helpTip('Record when animals die. Track the cause (disease, predator, accident) so you can prevent it from happening again.') ?></h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Track animal deaths, causes, and disposal.</p></div>
        <button class="btn btn-primary" onclick="openMortalityModal()"><i data-lucide="plus" style="width:16px;height:16px;"></i> Record Mortality</button>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Species</th><th>Animal</th><th>Count</th><th>Cause</th><th>Category</th><th>Disposal</th></tr></thead>
            <tbody>
            <?php if (empty($mortalityRecs)): ?>
                <tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">No mortality records.</td></tr>
            <?php else: foreach ($mortalityRecs as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['death_date'], ENT_QUOTES) ?></td>
                    <td><span class="badge badge-warning\"><?= htmlspecialchars($m['species'], ENT_QUOTES) ?></span></td>
                    <td><?= htmlspecialchars(($m['tag'] ? $m['tag'].' - ' : '').($m['aname'] ?? '—'), ENT_QUOTES) ?></td>
                    <td><strong><?= $m['count'] ?></strong></td>
                    <td><?= htmlspecialchars($m['cause'] ?? '—', ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($m['cause_category'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($m['disposal_method'], ENT_QUOTES) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div id="mortality-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Record Mortality</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_mortality">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group"><label class="admin-form-label">Animal</label><select class="admin-form-control" name="animal_id"><option value="">-- Select --</option><?php foreach ($animalList as $a): ?><option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['label'], ENT_QUOTES) ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Group</label><select class="admin-form-control" name="group_id"><option value="">-- Select --</option><?php foreach ($groupList as $g): ?><option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['label'], ENT_QUOTES) ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Species *</label><select class="admin-form-control" name="species" required><?php foreach ($spList as $sp): ?><option><?= $sp ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" name="death_date" value="<?= date('Y-m-d') ?>" required></div>
            <div class="admin-form-group"><label class="admin-form-label">Count *</label><input class="admin-form-control" type="number" name="count" value="1" min="1" required></div>
            <div class="admin-form-group"><label class="admin-form-label">Category</label><select class="admin-form-control" name="cause_category"><option value="disease">Disease</option><option value="predator">Predator</option><option value="accident">Accident</option><option value="starvation">Starvation</option><option value="poisoning">Poisoning</option><option value="unknown">Unknown</option></select></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Cause</label><input class="admin-form-control" name="cause" placeholder="e.g. Newcastle disease"></div>
            <div class="admin-form-group"><label class="admin-form-label">Disposal</label><select class="admin-form-control" name="disposal_method"><option value="burial">Burial</option><option value="burning">Burning</option><option value="Rendering">Rendering</option><option value="composting">Composting</option><option value="other">Other</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" rows="2"></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('mortality-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;">Save</button>
        </div></form>
    </div>
</div>
<script>function openMortalityModal(){document.getElementById('mortality-modal').style.display='flex';}
document.addEventListener('click',e=>{const m=document.getElementById('mortality-modal');if(m&&e.target===m)m.style.display='none';});</script>

<!-- ══════ QUARANTINE TAB ══════ -->
<?php elseif ($tab === 'quarantine'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Quarantine Management <?= helpTip('Isolate sick animals here so they do not infect the healthy ones. Track their treatment and recovery.') ?></h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Isolate and treat sick animals.</p></div>
        <button class="btn btn-primary" onclick="openQuarantineModal()"><i data-lucide="plus" style="width:16px;height:16px;"></i> Add to Quarantine</button>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead><tr><th>Start</th><th>Species</th><th>Animal</th><th>Reason</th><th>Status</th><th>Vet</th><th>Cost</th></tr></thead>
            <tbody>
            <?php if (empty($quarantineRecs)): ?>
                <tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">No quarantine records.</td></tr>
            <?php else: foreach ($quarantineRecs as $q): ?>
                <tr>
                    <td><?= htmlspecialchars($q['quarantine_start'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($q['species'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars(($q['tag'] ? $q['tag'].' - ' : '').($q['aname'] ?? '—'), ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($q['reason'], ENT_QUOTES) ?></td>
                    <td><span class="badge badge-<?= $q['status']==='active' ? 'warning' : 'success' ?>"><?= ucfirst($q['status']) ?></span></td>
                    <td><?= htmlspecialchars($q['vet_name'] ?? '—', ENT_QUOTES) ?></td>
                    <td>KES <?= number_format((float)$q['cost'], 0) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div id="quarantine-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add to Quarantine</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_quarantine">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group"><label class="admin-form-label">Animal</label><select class="admin-form-control" name="animal_id"><option value="">-- Select --</option><?php foreach ($animalList as $a): ?><option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['label'], ENT_QUOTES) ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Group</label><select class="admin-form-control" name="group_id"><option value="">-- Select --</option><?php foreach ($groupList as $g): ?><option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['label'], ENT_QUOTES) ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Species *</label><select class="admin-form-control" name="species" required><?php foreach ($spList as $sp): ?><option><?= $sp ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Start Date *</label><input class="admin-form-control" type="date" name="quarantine_start" value="<?= date('Y-m-d') ?>" required></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Reason *</label><input class="admin-form-control" name="reason" required placeholder="e.g. Suspected pneumonia"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Location</label><input class="admin-form-control" name="location" placeholder="e.g. Isolation pen 1"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Diagnosis</label><textarea class="admin-form-control" name="diagnosis" rows="2"></textarea></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Treatment Given</label><textarea class="admin-form-control" name="treatment_given" rows="2"></textarea></div>
            <div class="admin-form-group"><label class="admin-form-label">Vet Name</label><input class="admin-form-control" name="vet_name"></div>
            <div class="admin-form-group"><label class="admin-form-label">Cost (KES)</label><input class="admin-form-control" type="number" step="0.01" name="cost" value="0"></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('quarantine-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;">Save</button>
        </div></form>
    </div>
</div>
<script>function openQuarantineModal(){document.getElementById('quarantine-modal').style.display='flex';}
document.addEventListener('click',e=>{const m=document.getElementById('quarantine-modal');if(m&&e.target===m)m.style.display='none';});</script>

<!-- ══════ AI & BREEDING RECORDS TAB ══════ -->
<?php elseif ($tab === 'ai_records'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">AI & Breeding Records <?= helpTip('Track artificial insemination (AI) and natural breeding. Record which bull was used and whether the animal got pregnant.') ?></h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Track AI services, semen usage, and pregnancy results.</p></div>
        <button class="btn btn-primary" onclick="openAIModal()"><i data-lucide="plus" style="width:16px;height:16px;"></i> Record AI</button>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Animal</th><th>Species</th><th>Bull/Semen ID</th><th>Type</th><th>Technician</th><th>Cost</th><th>Result</th></tr></thead>
            <tbody>
            <?php if (empty($aiRecs)): ?>
                <tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">No AI records yet.</td></tr>
            <?php else: foreach ($aiRecs as $ai): ?>
                <tr>
                    <td><?= htmlspecialchars($ai['insemination_date'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars(($ai['tag'] ? $ai['tag'].' - ' : '').($ai['aname'] ?? '—'), ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($ai['species'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($ai['bull_semen_id'] ?? '—', ENT_QUOTES) ?></td>
                    <td><span class="badge badge-info"><?= ucfirst(str_replace('_',' ',$ai['insemination_type'])) ?></span></td>
                    <td><?= htmlspecialchars($ai['technician'] ?? '—', ENT_QUOTES) ?></td>
                    <td>KES <?= number_format((float)$ai['cost'], 0) ?></td>
                    <td><span class="badge badge-<?= $ai['result']==='pregnant' ? 'success' : ($ai['result']==='failed' ? 'danger' : 'warning') ?>"><?= ucfirst($ai['result']) ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div id="ai-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Record AI Service</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_ai">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group"><label class="admin-form-label">Animal *</label><select class="admin-form-control" name="animal_id" required><option value="">-- Select --</option><?php foreach ($animalList as $a): ?><option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['label'], ENT_QUOTES) ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Species *</label><select class="admin-form-control" name="species" required><?php foreach ($spList as $sp): ?><option><?= $sp ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" name="insemination_date" value="<?= date('Y-m-d') ?>" required></div>
            <div class="admin-form-group"><label class="admin-form-label">Type</label><select class="admin-form-control" name="insemination_type"><option value="ai">Artificial Insemination</option><option value="natural">Natural</option><option value="embryo_transfer">Embryo Transfer</option></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Bull/Semen ID</label><input class="admin-form-control" name="bull_semen_id" placeholder="e.g. SEM-2024-015"></div>
            <div class="admin-form-group"><label class="admin-form-label">Bull Name</label><input class="admin-form-control" name="bull_name"></div>
            <div class="admin-form-group"><label class="admin-form-label">Bull Breed</label><input class="admin-form-control" name="bull_breed" placeholder="e.g. Holstein Friesian"></div>
            <div class="admin-form-group"><label class="admin-form-label">Technician</label><input class="admin-form-control" name="technician"></div>
            <div class="admin-form-group"><label class="admin-form-label">Cost (KES)</label><input class="admin-form-control" type="number" step="0.01" name="cost" value="0"></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('ai-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;">Save</button>
        </div></form>
    </div>
</div>
<script>function openAIModal(){document.getElementById('ai-modal').style.display='flex';}
document.addEventListener('click',e=>{const m=document.getElementById('ai-modal');if(m&&e.target===m)m.style.display='none';});</script>

<!-- ══════ BODY CONDITION SCORE TAB ══════ -->
<?php elseif ($tab === 'body_condition'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Body Condition Scoring <?= helpTip('Score how fat or thin an animal looks (1 to 5). A score of 3 is ideal. Too thin = not enough food. Too fat = health problems.') ?></h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Monitor animal health through visual body condition assessment (1-5 scale).</p></div>
        <button class="btn btn-primary" onclick="openBCSModal()"><i data-lucide="plus" style="width:16px;height:16px;"></i> Record Score</button>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Animal</th><th>Species</th><th>Score</th><th>Condition</th><th>Scorer</th><th>Notes</th></tr></thead>
            <tbody>
            <?php if (empty($bcsRecs)): ?>
                <tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">No body condition scores recorded yet.</td></tr>
            <?php else: foreach ($bcsRecs as $b): ?>
                <?php $scoreLabel = $b['score'] < 2 ? 'Poor' : ($b['score'] < 3 ? 'Thin' : ($b['score'] < 4 ? 'Good' : 'Fat')); ?>
                <tr>
                    <td><?= htmlspecialchars($b['score_date'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars(($b['tag'] ? $b['tag'].' - ' : '').($b['aname'] ?? '—'), ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($b['species'], ENT_QUOTES) ?></td>
                    <td><strong style="font-size:1.2rem;color:<?= $b['score'] < 2.5 ? '#EF4444' : ($b['score'] > 3.5 ? '#F59E0B' : '#22C55E') ?>"><?= number_format((float)$b['score'], 1) ?></strong>/5</td>
                    <td><?= $scoreLabel ?></td>
                    <td><?= htmlspecialchars($b['scorer'] ?? '—', ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($b['notes'] ?? '', ENT_QUOTES) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div id="bcs-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Record Body Condition Score</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_bcs">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group"><label class="admin-form-label">Animal</label><select class="admin-form-control" name="animal_id"><option value="">-- Select --</option><?php foreach ($animalList as $a): ?><option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['label'], ENT_QUOTES) ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Group</label><select class="admin-form-control" name="group_id"><option value="">-- Select --</option><?php foreach ($groupList as $g): ?><option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['label'], ENT_QUOTES) ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Species *</label><select class="admin-form-control" name="species" required><?php foreach ($spList as $sp): ?><option><?= $sp ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" name="score_date" value="<?= date('Y-m-d') ?>" required></div>
            <div class="admin-form-group"><label class="admin-form-label">Score (1-5) *</label><input class="admin-form-control" type="number" step="0.1" min="1" max="5" name="score" required placeholder="e.g. 3.0"></div>
            <div class="admin-form-group"><label class="admin-form-label">Scorer</label><input class="admin-form-control" name="scorer" placeholder="Who assessed"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" rows="2"></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('bcs-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;">Save</button>
        </div></form>
    </div>
</div>
<script>function openBCSModal(){document.getElementById('bcs-modal').style.display='flex';}
document.addEventListener('click',e=>{const m=document.getElementById('bcs-modal');if(m&&e.target===m)m.style.display='none';});</script>

<!-- ══════ TRANSPORT TAB ══════ -->
<?php elseif ($tab === 'transport'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Animal Transport <?= helpTip('Record when you move animals from one place to another. Track transporter details, cost, and delivery status.') ?></h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Track animal movements between locations.</p></div>
        <button class="btn btn-primary" onclick="openTransportModal()"><i data-lucide="plus" style="width:16px;height:16px;"></i> Record Transport</button>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead><tr><th>Date</th><th>Species</th><th>Count</th><th>From</th><th>To</th><th>Transporter</th><th>Cost</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (empty($transportRecs)): ?>
                <tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">No transport records.</td></tr>
            <?php else: foreach ($transportRecs as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['transport_date'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($t['species'], ENT_QUOTES) ?></td>
                    <td><strong><?= $t['animal_count'] ?></strong></td>
                    <td><?= htmlspecialchars($t['from_location'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($t['to_location'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($t['transporter_name'], ENT_QUOTES) ?></td>
                    <td>KES <?= number_format((float)$t['transport_cost'], 0) ?></td>
                    <td><span class="badge badge-<?= $t['status']==='delivered' ? 'success' : ($t['status']==='in_transit' ? 'warning' : 'info') ?>"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div id="transport-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:540px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Record Transport</h3>
        <form method="POST"><input type="hidden" name="_action" value="save_transport">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="admin-form-group"><label class="admin-form-label">Date *</label><input class="admin-form-control" type="date" name="transport_date" value="<?= date('Y-m-d') ?>" required></div>
            <div class="admin-form-group"><label class="admin-form-label">Species *</label><select class="admin-form-control" name="species" required><?php foreach ($spList as $sp): ?><option><?= $sp ?></option><?php endforeach; ?></select></div>
            <div class="admin-form-group"><label class="admin-form-label">Animal Count *</label><input class="admin-form-control" type="number" name="animal_count" min="1" required></div>
            <div class="admin-form-group"><label class="admin-form-label">Cost (KES)</label><input class="admin-form-control" type="number" step="0.01" name="transport_cost" value="0"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">From *</label><input class="admin-form-control" name="from_location" required placeholder="e.g. Main Farm, Ruiru"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">To *</label><input class="admin-form-control" name="to_location" required placeholder="e.g. Nairobi Market"></div>
            <div class="admin-form-group"><label class="admin-form-label">Transporter</label><input class="admin-form-control" name="transporter_name"></div>
            <div class="admin-form-group"><label class="admin-form-label">Phone</label><input class="admin-form-control" name="transporter_phone"></div>
            <div class="admin-form-group"><label class="admin-form-label">Vehicle Reg</label><input class="admin-form-control" name="vehicle_registration"></div>
            <div class="admin-form-group"><label class="admin-form-label">Reason</label><input class="admin-form-control" name="reason" placeholder="e.g. Market sale"></div>
            <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes</label><textarea class="admin-form-control" name="notes" rows="2"></textarea></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('transport-modal').style.display='none'">Cancel</button>
            <button type="submit" class="btn btn-primary" style="flex:1;">Save</button>
        </div></form>
    </div>
</div>
<script>function openTransportModal(){document.getElementById('transport-modal').style.display='flex';}
document.addEventListener('click',e=>{const m=document.getElementById('transport-modal');if(m&&e.target===m)m.style.display='none';});</script>

<!-- ══════ PREVENTIVE CARE TAB ══════ -->
<?php elseif ($tab === 'preventive_care'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div><h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Preventive Care <?= helpTip('Regular treatments to keep animals healthy: deworming every 3 months, hoof trimming, shearing for sheep.') ?></h3>
        <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Deworming, hoof trimming, shearing, and other routine care.</p></div>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead><tr><th>Species</th><th>Care Type</th><th>Target</th><th>Frequency</th><th>Last Done</th><th>Next Due</th><th>Cost/Event</th></tr></thead>
            <tbody>
            <?php if (empty($preventiveCareRecs)): ?>
                <tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">No preventive care schedules.</td></tr>
            <?php else: foreach ($preventiveCareRecs as $pc): ?>
                <tr style="<?= ($pc['next_due'] && $pc['next_due'] <= date('Y-m-d')) ? 'background:#FEF3C7;' : '' ?>">
                    <td><span class="badge badge-info"><?= htmlspecialchars($pc['species'], ENT_QUOTES) ?></span></td>
                    <td><strong><?= htmlspecialchars($pc['care_type'], ENT_QUOTES) ?></strong></td>
                    <td><?= htmlspecialchars($pc['target_group'] ?? '—', ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($pc['frequency'] ?? '—', ENT_QUOTES) ?></td>
                    <td><?= $pc['last_done'] ? htmlspecialchars($pc['last_done'], ENT_QUOTES) : 'Never' ?></td>
                    <td style="<?= ($pc['next_due'] && $pc['next_due'] <= date('Y-m-d')) ? 'color:#EF4444;font-weight:700;' : '' ?>"><?= $pc['next_due'] ? htmlspecialchars($pc['next_due'], ENT_QUOTES) : '—' ?></td>
                    <td>KES <?= number_format((float)$pc['cost_per_event'], 0) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
\n<!-- ══════ GRAZING & PASTURE TAB ══════ -->
<?php elseif ($tab === 'grazing'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Grazing & Pasture <?= helpTip('Manage where your animals eat grass. Rotate them between fields so the grass can regrow.') ?></h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Track pasture rotations, grazing schedules, and pasture health for all livestock.</p>
        </div>
    </div>

    <?php
    // Load grazing data from houses (pastures/bomas/paddocks)
    $pastures = [];
    if ($pdo) {
        try {
            $pastures = $pdo->query("SELECT h.*, 
                (SELECT COUNT(*) FROM animal_groups ag WHERE ag.housing_id = h.id AND ag.status = 'active') as group_count,
                (SELECT COALESCE(SUM(ag.head_count), 0) FROM animal_groups ag WHERE ag.housing_id = h.id AND ag.status = 'active') as total_head
                FROM houses h WHERE h.house_type IN ('pasture','paddock','boma','field','pen') OR h.species IN ('Cattle','Goat','Sheep')
                ORDER BY h.name")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }
    ?>

    <!-- Grazing Overview -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:24px;">
        <div class="stat-card" style="min-width:0;"><div class="stat-card-icon"><i data-lucide="trees"></i></div><div class="stat-card-info"><strong><?= count($pastures) ?></strong><small>Pastures / Paddocks</small></div></div>
        <div class="stat-card" style="min-width:0;"><div class="stat-card-icon accent"><i data-lucide="users"></i></div><div class="stat-card-info"><strong><?= array_sum(array_map(fn($p) => (int)$p['total_head'], $pastures)) ?></strong><small>Animals on Pasture</small></div></div>
        <div class="stat-card" style="min-width:0;"><div class="stat-card-icon info"><i data-lucide="home"></i></div><div class="stat-card-info"><strong><?= count(array_filter($pastures, fn($p) => ($p['status'] ?? '') === 'active')) ?></strong><small>Active Pastures</small></div></div>
    </div>

    <!-- Pasture List -->
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Pasture / Paddock</th><th>Species</th><th>Capacity</th><th>Current Animals</th><th>Groups</th><th>Status</th><th>Location</th></tr></thead>
            <tbody>
            <?php if (empty($pastures)): ?>
                <tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;"><strong>No pastures or paddocks registered.</strong><br>Add grazing areas in the Housing tab, then track rotations here.</td></tr>
            <?php else: foreach ($pastures as $p): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><span class="badge-pill badge-pill-info"><?= htmlspecialchars($p['species'] ?? 'Cattle', ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= (int)($p['capacity'] ?? 0) ?> <?= ($p['species'] ?? '') === 'Chicken' ? 'birds' : 'heads' ?></td>
                    <td><strong><?= (int)($p['total_head'] ?? 0) ?></strong></td>
                    <td><?= (int)($p['group_count'] ?? 0) ?></td>
                    <td><span class="badge-pill <?= ($p['status'] ?? '') === 'active' ? 'badge-pill-success' : 'badge-pill-warning' ?>"><?= htmlspecialchars($p['status'] ?? 'active', ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($p['location'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Grazing Tips -->
    <div style="margin-top:20px;padding:16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;">
        <h4 style="margin:0 0 8px;font-size:0.92rem;color:#166534;"><i data-lucide="lightbulb" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:6px;"></i>Grazing Best Practices</h4>
        <ul style="margin:0;padding-left:20px;font-size:0.85rem;color:#166534;line-height:1.7;">
            <li>Rotate cattle every 3-5 days to prevent overgrazing</li>
            <li>Allow 60-90 days rest between grazing cycles per paddock</li>
            <li>Move goats after 1-2 days — they browse faster than cattle graze</li>
            <li>Monitor FAMACHA scores for sheep/goats during dry season</li>
            <li>Separate species when possible to reduce parasite cross-contamination</li>
        </ul>
    </div>
</div>

<!-- ══════ FARM MAP TAB ══════ -->
<?php elseif ($tab === 'farmmap'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Farm Map <?= helpTip('See a visual layout of your farm buildings, fields, and animal houses.') ?></h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Visual overview of all farm locations — houses, pastures, fields, and infrastructure.</p>
        </div>
    </div>

    <?php
    // Load all locations
    $allLocations = [];
    if ($pdo) {
        try {
            // Houses
            $houses = $pdo->query("SELECT name, species, house_type, capacity, current_occupants, location, status FROM houses ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
            // Fields
            $fields = $pdo->query("SELECT name, location, size_acres, soil_type, status FROM fields ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
            // Groups
            $groups = $pdo->query("SELECT ag.name, ag.species, ag.head_count, ag.location, ag.status, h.name as housing_name FROM animal_groups ag LEFT JOIN houses h ON ag.housing_id = h.id WHERE ag.status = 'active' ORDER BY ag.species, ag.name")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }
    ?>

    <!-- Infrastructure Map -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin-bottom:24px;">
        <!-- Houses & Structures -->
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;">
            <h4 style="margin:0 0 14px;font-size:0.95rem;color:#0F172A;"><i data-lucide="home" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:6px;color:#3b82f6;"></i>Houses & Structures</h4>
            <?php if (empty($houses)): ?>
                <p style="color:#94a3b8;font-size:0.85rem;margin:0;">No houses registered. Add them in the Housing tab.</p>
            <?php else: foreach ($houses as $h): ?>
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <div style="width:8px;height:8px;background:<?= ($h['status'] ?? '') === 'active' ? '#16a34a' : '#d97706' ?>;border-radius:50%;"></div>
                    <div style="flex:1;">
                        <strong style="font-size:0.88rem;"><?= htmlspecialchars($h['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span style="font-size:0.78rem;color:#64748b;margin-left:6px;"><?= htmlspecialchars($h['house_type'] ?? 'house', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <span style="font-size:0.82rem;color:#475569;"><?= (int)($h['current_occupants'] ?? 0) ?>/<?= (int)($h['capacity'] ?? 0) ?></span>
                    <span class="badge-pill badge-pill-info" style="font-size:0.7rem;"><?= htmlspecialchars($h['species'] ?? 'Chicken', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- Fields & Pastures -->
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;">
            <h4 style="margin:0 0 14px;font-size:0.95rem;color:#0F172A;"><i data-lucide="sprout" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:6px;color:#16a34a;"></i>Fields & Pastures</h4>
            <?php if (empty($fields)): ?>
                <p style="color:#94a3b8;font-size:0.85rem;margin:0;">No fields registered. Add them in the Crops tab.</p>
            <?php else: foreach ($fields as $f): ?>
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                    <div style="width:8px;height:8px;background:<?= ($f['status'] ?? '') === 'active' ? '#16a34a' : '#94a3b8' ?>;border-radius:50%;"></div>
                    <div style="flex:1;">
                        <strong style="font-size:0.88rem;"><?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span style="font-size:0.78rem;color:#64748b;margin-left:6px;"><?= htmlspecialchars($f['soil_type'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <span style="font-size:0.82rem;color:#475569;"><?= number_format((float)($f['size_acres'] ?? 0), 1) ?> acres</span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Animal Distribution -->
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;">
        <h4 style="margin:0 0 14px;font-size:0.95rem;color:#0F172A;"><i data-lucide="paw-print" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:6px;color:#d97706;"></i>Animal Distribution by Location</h4>
        <?php
        // Group by species
        $speciesGroups = [];
        foreach ($groups as $g) {
            $sp = $g['species'] ?? 'Unknown';
            $speciesGroups[$sp][] = $g;
        }
        if (empty($speciesGroups)): ?>
            <p style="color:#94a3b8;font-size:0.85rem;margin:0;">No active animal groups. Add groups in the Groups tab.</p>
        <?php else: foreach ($speciesGroups as $sp => $spGroups): ?>
            <div style="margin-bottom:16px;">
                <h5 style="margin:0 0 8px;font-size:0.88rem;color:#475569;"><?= htmlspecialchars($sp) ?></h5>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <?php foreach ($spGroups as $sg): ?>
                        <div style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;font-size:0.82rem;">
                            <strong><?= htmlspecialchars($sg['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span style="color:#64748b;">· <?= (int)$sg['head_count'] ?> head</span>
                            <?php if ($sg['housing_name']): ?><span style="color:#3b82f6;">@ <?= htmlspecialchars($sg['housing_name'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
<?php endif; ?>


<!-- Shared JS helpers -->
<script>
function setTbLoading(id,cols,msg){ const el=document.getElementById(id); if(el) el.innerHTML=`<tr><td colspan="${cols}" style="text-align:center;padding:28px;color:#64748b;"><div style="display:inline-flex;align-items:center;gap:10px;"><div style="width:20px;height:20px;border:2px solid #cbd5e1;border-top-color:var(--admin-primary);border-radius:50%;animation:spin 0.8s linear infinite;"></div>${msg}</div></td></tr>`; }
function setTbError(id,cols,msg){ const el=document.getElementById(id); if(el) el.innerHTML=`<tr><td colspan="${cols}" style="text-align:center;padding:28px;"><div style="display:inline-flex;align-items:center;gap:10px;color:#dc2626;background:#fef2f2;border:1px solid #fecaca;padding:14px 24px;border-radius:8px;font-weight:600;">${msg}</div></td></tr>`; }
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
