<?php
/**
 * Backend API - Poultry Operations Management (Flocks, Production, Vaccinations, Expenses)
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();

require_once __DIR__ . '/../config/database.php';

try {
    if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager', 'stock_manager'], true)) {
        throw new Exception('Unauthorized access');
    }

    $pdo = getDatabaseConnection();
    if (!$pdo) throw new Exception('Database connection failed');

    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($action) {
        // --- FLOCKS ---
        case 'get_flocks':
            $flocks = $pdo->query("SELECT * FROM flocks ORDER BY hatch_date DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $flocks]);
            break;

        case 'save_flock':
            if ($method !== 'POST') throw new Exception('Invalid request method');
            $id = (int)($_POST['id'] ?? 0);
            $flock_name = trim($_POST['flock_name'] ?? '');
            $breed = trim($_POST['breed'] ?? '');
            $initial_count = (int)($_POST['initial_count'] ?? 0);
            $current_count = (int)($_POST['current_count'] ?? $initial_count);
            $hatch_date = $_POST['hatch_date'] ?? '';
            $status = $_POST['status'] ?? 'active';

            if ($flock_name === '' || $breed === '' || $initial_count <= 0 || $hatch_date === '') {
                throw new Exception('Please fill in all required fields');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE flocks SET flock_name = ?, breed = ?, initial_count = ?, current_count = ?, hatch_date = ?, status = ? WHERE id = ?");
                $stmt->execute([$flock_name, $breed, $initial_count, $current_count, $hatch_date, $status, $id]);
                $msg = 'Flock updated';
            } else {
                $stmt = $pdo->prepare("INSERT INTO flocks (flock_name, breed, initial_count, current_count, hatch_date, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$flock_name, $breed, $initial_count, $initial_count, $hatch_date, $status]);
                $msg = 'Flock created';
            }
            logActivity($pdo, 'save', 'flocks', "{$msg}: {$flock_name} ({$breed}, {$initial_count} birds)", $id > 0 ? $id : (int)$pdo->lastInsertId(), 'flock');
            echo json_encode(['success' => true, 'message' => $msg]);
            break;

        case 'delete_flock':
            if ($method !== 'POST') throw new Exception('Invalid request method');
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM flocks WHERE id = ?");
            $stmt->execute([$id]);
            logActivity($pdo, 'delete', 'flocks', "Deleted flock #{$id}", $id, 'flock');
            echo json_encode(['success' => true, 'message' => 'Flock deleted']);
            break;

        // --- PRODUCTION RECORDS ---
        case 'get_production':
            $stmt = $pdo->query("
                SELECT p.*, f.flock_name 
                FROM production_records p
                JOIN flocks f ON p.flock_id = f.id
                ORDER BY p.record_date DESC, p.id DESC
            ");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $records]);
            break;

        case 'save_production':
            if ($method !== 'POST') throw new Exception('Invalid request method');
            $id = (int)($_POST['id'] ?? 0);
            $flock_id = (int)($_POST['flock_id'] ?? 0);
            $record_date = $_POST['record_date'] ?? '';
            $eggs_collected = (int)($_POST['eggs_collected'] ?? 0);
            $cracked_eggs = (int)($_POST['cracked_eggs'] ?? 0);
            $meat_weight_kg = (float)($_POST['meat_weight_kg'] ?? 0.0);
            $mortality = (int)($_POST['mortality'] ?? 0);
            $feed_consumed_kg = (float)($_POST['feed_consumed_kg'] ?? 0.0);
            $notes = trim($_POST['notes'] ?? '');

            if ($flock_id <= 0 || $record_date === '') {
                throw new Exception('Flock and Record Date are required');
            }

            if ($id > 0) {
                // If updating mortality, let's adjust current_count inside the flock table
                $old = $pdo->prepare("SELECT flock_id, mortality FROM production_records WHERE id = ?");
                $old->execute([$id]);
                $oldRecord = $old->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $pdo->prepare("UPDATE production_records SET flock_id = ?, record_date = ?, eggs_collected = ?, cracked_eggs = ?, meat_weight_kg = ?, mortality = ?, feed_consumed_kg = ?, notes = ? WHERE id = ?");
                $stmt->execute([$flock_id, $record_date, $eggs_collected, $cracked_eggs, $meat_weight_kg, $mortality, $feed_consumed_kg, $notes, $id]);

                // Sync flock current_count if mortality changed
                if ($oldRecord) {
                    $mortalityDiff = $mortality - (int)$oldRecord['mortality'];
                    if ($mortalityDiff !== 0) {
                        $upFlock = $pdo->prepare("UPDATE flocks SET current_count = current_count - ? WHERE id = ?");
                        $upFlock->execute([$mortalityDiff, $flock_id]);
                    }
                }
                $msg = 'Production record updated';
            } else {
                $stmt = $pdo->prepare("INSERT INTO production_records (flock_id, record_date, eggs_collected, cracked_eggs, meat_weight_kg, mortality, feed_consumed_kg, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$flock_id, $record_date, $eggs_collected, $cracked_eggs, $meat_weight_kg, $mortality, $feed_consumed_kg, $notes]);

                // Auto-deduct flock current_count by daily mortality
                if ($mortality > 0) {
                    $upFlock = $pdo->prepare("UPDATE flocks SET current_count = current_count - ? WHERE id = ?");
                    $upFlock->execute([$mortality, $flock_id]);
                }
                $msg = 'Production record logged';
            }
            logActivity($pdo, 'save', 'production', "{$msg}: {$eggs_collected} eggs, {$mortality} mortality, flock #{$flock_id}", $id > 0 ? $id : (int)$pdo->lastInsertId(), 'production_record');
            echo json_encode(['success' => true, 'message' => $msg]);
            break;

        case 'delete_production':
            if ($method !== 'POST') throw new Exception('Invalid request method');
            $id = (int)($_POST['id'] ?? 0);

            // Revert mortality count back to flock before deleting
            $old = $pdo->prepare("SELECT flock_id, mortality FROM production_records WHERE id = ?");
            $old->execute([$id]);
            $oldRecord = $old->fetch(PDO::FETCH_ASSOC);
            if ($oldRecord && (int)$oldRecord['mortality'] > 0) {
                $upFlock = $pdo->prepare("UPDATE flocks SET current_count = current_count + ? WHERE id = ?");
                $upFlock->execute([(int)$oldRecord['mortality'], (int)$oldRecord['flock_id']]);
            }

            $stmt = $pdo->prepare("DELETE FROM production_records WHERE id = ?");
            $stmt->execute([$id]);
            logActivity($pdo, 'delete', 'production', "Deleted production record #{$id}", $id, 'production_record');
            echo json_encode(['success' => true, 'message' => 'Record deleted']);
            break;

        // --- VACCINATIONS ---
        case 'get_vaccinations':
            $stmt = $pdo->query("
                SELECT v.*, f.flock_name, u.username as user_name 
                FROM vaccinations v
                JOIN flocks f ON v.flock_id = f.id
                LEFT JOIN users u ON v.administered_by = u.id
                ORDER BY v.scheduled_date ASC, v.id DESC
            ");
            $vaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $vaccinations]);
            break;

        case 'save_vaccination':
            if ($method !== 'POST') throw new Exception('Invalid request method');
            $id = (int)($_POST['id'] ?? 0);
            $flock_id = (int)($_POST['flock_id'] ?? 0);
            $vaccine_name = trim($_POST['vaccine_name'] ?? '');
            $scheduled_date = $_POST['scheduled_date'] ?? '';
            $administered_date = $_POST['administered_date'] ?? null;
            if ($administered_date === '') $administered_date = null;
            $status = $_POST['status'] ?? 'scheduled';
            $administered_by = $status === 'completed' ? (int)$_SESSION['user_id'] : null;

            if ($flock_id <= 0 || $vaccine_name === '' || $scheduled_date === '') {
                throw new Exception('Flock, Vaccine Name, and Scheduled Date are required');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE vaccinations SET flock_id = ?, vaccine_name = ?, scheduled_date = ?, administered_date = ?, status = ?, administered_by = ? WHERE id = ?");
                $stmt->execute([$flock_id, $vaccine_name, $scheduled_date, $administered_date, $status, $administered_by, $id]);
                $msg = 'Vaccination schedule updated';
            } else {
                $stmt = $pdo->prepare("INSERT INTO vaccinations (flock_id, vaccine_name, scheduled_date, administered_date, status, administered_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$flock_id, $vaccine_name, $scheduled_date, $administered_date, $status, $administered_by]);
                $msg = 'Vaccination scheduled';
            }
            logActivity($pdo, 'save', 'vaccinations', "{$msg}: {$vaccine_name} for flock #{$flock_id}", $id > 0 ? $id : (int)$pdo->lastInsertId(), 'vaccination');
            echo json_encode(['success' => true, 'message' => $msg]);
            break;

        case 'delete_vaccination':
            if ($method !== 'POST') throw new Exception('Invalid request method');
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM vaccinations WHERE id = ?");
            $stmt->execute([$id]);
            logActivity($pdo, 'delete', 'vaccinations', "Deleted vaccination #{$id}", $id, 'vaccination');
            echo json_encode(['success' => true, 'message' => 'Vaccination schedule deleted']);
            break;

        // --- EXPENSES (FINANCIAL RECORDS) ---
        case 'get_expenses':
            $stmt = $pdo->query("SELECT * FROM financial_records WHERE type = 'expense' ORDER BY transaction_date DESC, id DESC");
            $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $expenses]);
            break;

        case 'save_expense':
            if ($method !== 'POST') throw new Exception('Invalid request method');
            $id = (int)($_POST['id'] ?? 0);
            $category = trim($_POST['category'] ?? '');
            $amount = (float)($_POST['amount'] ?? 0.0);
            $transaction_date = $_POST['transaction_date'] ?? '';
            $description = trim($_POST['description'] ?? '');

            if ($category === '' || $amount <= 0 || $transaction_date === '') {
                throw new Exception('Category, amount, and transaction date are required');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE financial_records SET type = 'expense', category = ?, amount = ?, transaction_date = ?, description = ? WHERE id = ?");
                $stmt->execute([$category, $amount, $transaction_date, $description, $id]);
                $msg = 'Expense updated';
            } else {
                $stmt = $pdo->prepare("INSERT INTO financial_records (type, category, amount, transaction_date, description) VALUES ('expense', ?, ?, ?, ?)");
                $stmt->execute([$category, $amount, $transaction_date, $description]);
                $msg = 'Expense logged';
            }
            logActivity($pdo, 'save', 'expenses', "{$msg}: {$category} KES {$amount} ({$transaction_date})", $id > 0 ? $id : (int)$pdo->lastInsertId(), 'financial_record');
            echo json_encode(['success' => true, 'message' => $msg]);
            break;

        case 'delete_expense':
            if ($method !== 'POST') throw new Exception('Invalid request method');
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM financial_records WHERE id = ? AND type = 'expense'");
            $stmt->execute([$id]);
            logActivity($pdo, 'delete', 'expenses', "Deleted expense record #{$id}", $id, 'financial_record');
            echo json_encode(['success' => true, 'message' => 'Expense record deleted']);
            break;

        default:
            throw new Exception('Action not found');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
