<?php
declare(strict_types=1);
/**
 * Admin - Bulk Import/Export Module
 * Handles CSV/Excel import and export for all major data entities
 */

// Start session safely (never warn if already started)
if (session_status() === PHP_SESSION_NONE) {
    $temp_dir = sys_get_temp_dir();
    if (is_writable($temp_dir)) {
        session_save_path($temp_dir);
    }
    session_start();
}

$path_prefix = '../../';
$page_title = 'Bulk Import/Export - Admin';

// Load shared config (getDB, verifyCSRFToken, security helpers) BEFORE any output
require_once __DIR__ . '/../includes/config.php';

// Check admin access
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','sales_staff'], true)) {
    echo "<script>window.location.href = '/Frontend/pages/login.php';</script>";
    exit;
}

$pdo = getDB();

// Expected CSV columns per import type (also used for template downloads).
$import_formats = [
    'products'      => ['label' => 'Products',      'columns' => ['Name', 'Category', 'Type', 'Price', 'Stock', 'Description']],
    'customers'     => ['label' => 'Customers',     'columns' => ['Username', 'Email', 'First Name', 'Last Name', 'Phone']],
    'raw_materials' => ['label' => 'Raw Materials', 'columns' => ['Name', 'Stock (tons)', 'Price/ton', 'Min Stock Level']],
    'flocks'        => ['label' => 'Flocks',        'columns' => ['Flock Name', 'Breed', 'Initial Count', 'Current Count', 'Hatch Date (YYYY-MM-DD)', 'Status']],
    'expenses'      => ['label' => 'Expenses',      'columns' => ['Category', 'Description', 'Amount', 'Date (YYYY-MM-DD)', 'Payment Method']],
    'income'        => ['label' => 'Income',        'columns' => ['Category', 'Description', 'Amount', 'Date (YYYY-MM-DD)', 'Payment Method']],
    'animals'       => ['label' => 'Animals',       'columns' => ['Tag', 'Name', 'Type', 'Breed', 'Gender', 'Birth Date (YYYY-MM-DD)', 'Status', 'Notes']],
    'crops'         => ['label' => 'Crop Plantings','columns' => ['Field Name', 'Crop', 'Variety', 'Planting Date (YYYY-MM-DD)', 'Area (Acres)', 'Expected Harvest (YYYY-MM-DD)', 'Expected Yield', 'Yield Unit', 'Status', 'Notes']],
    'workers'       => ['label' => 'Workers',       'columns' => ['Name', 'Phone', 'Role', 'Wage Type', 'Wage Rate', 'Status', 'Notes']],
];

// ==================== EXPORT FUNCTIONS ====================

function exportProducts($pdo) {
    $stmt = $pdo->query("
        SELECT p.id, p.name, c.name as category, p.product_type, p.price, p.stock_quantity, 
               p.description, p.is_active, p.created_at
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.id
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    outputCSV('products_export', [
        'ID', 'Name', 'Category', 'Type', 'Price', 'Stock', 'Description', 'Active', 'Created'
    ], $rows);
}

function exportOrders($pdo) {
    $stmt = $pdo->query("
        SELECT o.id, o.order_number, u.username, u.email, o.total_amount, o.payment_method, 
               o.status, o.shipping_address, o.phone_contact, o.created_at
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    outputCSV('orders_export', [
        'ID', 'Order#', 'Customer', 'Email', 'Amount', 'Payment', 'Status', 'Address', 'Phone', 'Date'
    ], $rows);
}

function exportCustomers($pdo) {
    $stmt = $pdo->query("
        SELECT id, username, email, first_name, last_name, phone_number, role, 
               created_at
        FROM users
        WHERE role IN ('customer', 'demo')
        ORDER BY created_at DESC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    outputCSV('customers_export', [
        'ID', 'Username', 'Email', 'First Name', 'Last Name', 'Phone', 'Role', 'Registered'
    ], $rows);
}

function exportRawMaterials($pdo) {
    $stmt = $pdo->query("
        SELECT id, name, stock_tons, current_price_per_ton, min_stock_level, 
               created_at, updated_at
        FROM raw_materials
        ORDER BY name
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    outputCSV('raw_materials_export', [
        'ID', 'Name', 'Stock (tons)', 'Price/ton', 'Min Stock Level', 'Created', 'Updated'
    ], $rows);
}

function exportFlocks($pdo) {
    $stmt = $pdo->query("
        SELECT id, flock_name, breed, initial_count, current_count, 
               hatch_date, status
        FROM flocks
        ORDER BY hatch_date DESC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    outputCSV('flocks_export', [
        'ID', 'Flock Name', 'Breed', 'Initial Count', 'Current Count', 'Hatch Date', 'Status'
    ], $rows);
}

function exportAnimals($pdo) {
    $stmt = $pdo->query("SELECT tag, name, type, breed, gender, birth_date, status, notes FROM animals ORDER BY id");
    outputCSV('animals_export', ['Tag', 'Name', 'Type', 'Breed', 'Gender', 'Birth Date', 'Status', 'Notes'], $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function exportCrops($pdo) {
    $stmt = $pdo->query("
        SELECT f.name as field_name, cp.crop, cp.variety, cp.planting_date, cp.area_acres, 
               cp.expected_harvest_date, cp.expected_yield, cp.yield_unit, cp.status, cp.notes
        FROM crop_plantings cp
        LEFT JOIN fields f ON cp.field_id = f.id
        ORDER BY cp.id
    ");
    outputCSV('crops_export', ['Field Name', 'Crop', 'Variety', 'Planting Date', 'Area (Acres)', 'Expected Harvest', 'Expected Yield', 'Yield Unit', 'Status', 'Notes'], $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function exportWorkers($pdo) {
    $stmt = $pdo->query("SELECT name, phone, role, wage_type, wage_rate, status, notes FROM workers ORDER BY id");
    outputCSV('workers_export', ['Name', 'Phone', 'Role', 'Wage Type', 'Wage Rate', 'Status', 'Notes'], $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function exportIncome($pdo) {
    $hasPayment = function_exists('columnExists') && columnExists($pdo, 'financial_records', 'payment_method');
    $stmt = $pdo->query("
        SELECT category, description, amount, transaction_date,
               " . ($hasPayment ? 'payment_method' : "'cash' AS payment_method") . "
        FROM financial_records
        WHERE type = 'income'
        ORDER BY transaction_date DESC
    ");
    outputCSV('income_export', ['Category', 'Description', 'Amount', 'Date', 'Payment Method'], $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function exportExpenses($pdo) {
    // Older databases lack financial_records.payment_method; the auto-migration
    // adds it, but degrade gracefully regardless so the export never fails.
    $hasPayment = function_exists('columnExists') && columnExists($pdo, 'financial_records', 'payment_method');
    $stmt = $pdo->query("
        SELECT id, category, description, amount, transaction_date,
               " . ($hasPayment ? 'payment_method' : "'cash' AS payment_method") . ", created_at
        FROM financial_records
        WHERE type = 'expense'
        ORDER BY transaction_date DESC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    outputCSV('expenses_export', [
        'ID', 'Category', 'Description', 'Amount', 'Date', 'Payment Method', 'Created'
    ], $rows);
}

function outputCSV($filename, $headers, $rows) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    
    foreach ($rows as $row) {
        fputcsv($output, array_values($row));
    }
    
    fclose($output);
}

// Handle Export Actions - MUST run before any HTML is sent, otherwise the
// CSV gets embedded inside the page and the download is corrupt.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    try {
        switch ($export_type) {
            case 'products':
                exportProducts($pdo);
                break;
            case 'orders':
                exportOrders($pdo);
                break;
            case 'customers':
                exportCustomers($pdo);
                break;
            case 'raw_materials':
                exportRawMaterials($pdo);
                break;
            case 'flocks':
                exportFlocks($pdo);
                break;
            case 'expenses':
                exportExpenses($pdo);
                break;
            case 'animals':
                exportAnimals($pdo);
                break;
            case 'crops':
                exportCrops($pdo);
                break;
            case 'workers':
                exportWorkers($pdo);
                break;
            case 'income':
                exportIncome($pdo);
                break;

            default:
                die('Invalid export type');
        }
    } catch (Exception $e) {
        die('Export failed: ' . $e->getMessage());
    }
    exit;
}

// Handle Template Downloads (headers-only CSV) - also before any HTML output.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['template'])) {
    $tpl_key = $_GET['template'];
    if (!isset($import_formats[$tpl_key])) {
        die('Invalid template type');
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $tpl_key . '_template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $import_formats[$tpl_key]['columns']);
    fclose($out);
    exit;
}

$success_message = '';
$error_message = '';
$csrf_token = function_exists('generateCSRFToken') ? generateCSRFToken() : ($_SESSION['csrf_token'] ?? '');

// Live record counts for the export cards / stats row (never break the page
// if a table is missing).
$record_counts = [];
$count_queries = [
    'products'      => ['products',           "SELECT COUNT(*) FROM products"],
    'orders'        => ['orders',             "SELECT COUNT(*) FROM orders"],
    'customers'     => ['users',              "SELECT COUNT(*) FROM users WHERE role IN ('customer','demo')"],
    'raw_materials' => ['raw_materials',      "SELECT COUNT(*) FROM raw_materials"],
    'flocks'        => ['flocks',             "SELECT COUNT(*) FROM flocks"],
    'expenses'      => ['financial_records',  "SELECT COUNT(*) FROM financial_records WHERE type='expense'"],
    'income'        => ['financial_records',  "SELECT COUNT(*) FROM financial_records WHERE type='income'"],
    'animals'       => ['animals',            "SELECT COUNT(*) FROM animals"],
    'crops'         => ['crop_plantings',     "SELECT COUNT(*) FROM crop_plantings"],
    'workers'       => ['workers',            "SELECT COUNT(*) FROM workers"],
];
$existing_tables = ($pdo instanceof PDO) ? ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: []) : [];
foreach ($count_queries as $ckey => [$ctable, $csql]) {
    try {
        $record_counts[$ckey] = ($pdo instanceof PDO && in_array($ctable, $existing_tables, true)) ? (int)$pdo->query($csql)->fetchColumn() : null;
    } catch (Exception $e) {
        $record_counts[$ckey] = null;
    }
}

// Handle Import Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security token expired. Please refresh and try again.';
    } else {
        $import_type = $_POST['import_type'] ?? '';
        
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $error_message = 'Please select a valid CSV file to upload.';
        } else {
            try {
                $file_path = $_FILES['import_file']['tmp_name'];
                
                switch ($import_type) {
                    case 'products':
                        $result = importProducts($pdo, $file_path);
                        break;
                    case 'customers':
                        $result = importCustomers($pdo, $file_path);
                        break;
                    case 'raw_materials':
                        $result = importRawMaterials($pdo, $file_path);
                        break;
                    case 'flocks':
                        $result = importFlocks($pdo, $file_path);
                        break;
                    case 'expenses':
                        $result = importExpenses($pdo, $file_path);
                        break;
                    case 'income':
                        $result = importIncome($pdo, $file_path);
                        break;
                    case 'animals':
                        $result = importAnimals($pdo, $file_path);
                        break;
                    case 'crops':
                        $result = importCrops($pdo, $file_path);
                        break;
                    case 'workers':
                        $result = importWorkers($pdo, $file_path);
                        break;
                    default:
                        throw new Exception('Invalid import type');
                }
                
                $success_message = $result['success'] . ' records imported successfully. ' . ($result['errors'] > 0 ? $result['errors'] . ' errors.' : '');
                if (function_exists('logActivity')) {
                    logActivity($pdo, 'import', 'bulk_import_export', "Imported {$result['success']} {$import_type} records" . ($result['errors'] > 0 ? " ({$result['errors']} errors)" : ''));
                }
            } catch (Exception $e) {
                $error_message = 'Import failed: ' . $e->getMessage();
            }
        }
    }
}

// ==================== IMPORT FUNCTIONS ====================

function importProducts($pdo, $file_path) {
    $success = 0;
    $errors = 0;
    
    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        $headers = fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            try {
                // Expected: Name, Category, Type, Price, Stock, Description
                $name = trim($data[0] ?? '');
                $category_name = trim($data[1] ?? '');
                $type = trim($data[2] ?? 'general');
                $price = (float)($data[3] ?? 0);
                $stock = (int)($data[4] ?? 0);
                $description = trim($data[5] ?? '');
                
                if (empty($name)) {
                    $errors++;
                    continue;
                }
                
                // Get category ID
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
                $stmt->execute([$category_name]);
                $category = $stmt->fetch();
                $category_id = $category ? $category['id'] : 1;
                
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
                
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, slug, category_id, product_type, price, stock_quantity, description, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE 
                    price = VALUES(price), stock_quantity = VALUES(stock_quantity), description = VALUES(description)
                ");
                $stmt->execute([$name, $slug, $category_id, $type, $price, $stock, $description]);
                $success++;
            } catch (Exception $e) {
                $errors++;
            }
        }
        fclose($handle);
    }
    
    return ['success' => $success, 'errors' => $errors];
}

function importCustomers($pdo, $file_path) {
    $success = 0;
    $errors = 0;
    
    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        $headers = fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            try {
                // Expected: Username, Email, First Name, Last Name, Phone
                $username = trim($data[0] ?? '');
                $email = trim($data[1] ?? '');
                $first_name = trim($data[2] ?? '');
                $last_name = trim($data[3] ?? '');
                $phone = trim($data[4] ?? '');
                
                if (empty($username) || empty($email)) {
                    $errors++;
                    continue;
                }
                
                $password_hash = password_hash('password123', PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, first_name, last_name, phone_number, role)
                    VALUES (?, ?, ?, ?, ?, ?, 'customer')
                    ON DUPLICATE KEY UPDATE 
                    first_name = VALUES(first_name), last_name = VALUES(last_name), phone_number = VALUES(phone_number)
                ");
                $stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $phone]);
                $success++;
            } catch (Exception $e) {
                $errors++;
            }
        }
        fclose($handle);
    }
    
    return ['success' => $success, 'errors' => $errors];
}

function importRawMaterials($pdo, $file_path) {
    $success = 0;
    $errors = 0;
    
    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        $headers = fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            try {
                // Expected: Name, Stock (tons), Price/ton, Min Stock Level
                $name = trim($data[0] ?? '');
                $stock_tons = (float)($data[1] ?? 0);
                $price_per_ton = (float)($data[2] ?? 0);
                $min_stock_level = (float)($data[3] ?? 1.0);
                
                if (empty($name)) {
                    $errors++;
                    continue;
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO raw_materials (name, stock_tons, current_price_per_ton, min_stock_level)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    stock_tons = VALUES(stock_tons), current_price_per_ton = VALUES(current_price_per_ton), 
                    min_stock_level = VALUES(min_stock_level)
                ");
                $stmt->execute([$name, $stock_tons, $price_per_ton, $min_stock_level]);
                $success++;
            } catch (Exception $e) {
                $errors++;
            }
        }
        fclose($handle);
    }
    
    return ['success' => $success, 'errors' => $errors];
}

function importFlocks($pdo, $file_path) {
    $success = 0;
    $errors = 0;
    
    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        $headers = fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            try {
                // Expected: Flock Name, Breed, Initial Count, Current Count, Hatch Date (YYYY-MM-DD), Status
                $flock_name = trim($data[0] ?? '');
                $breed = trim($data[1] ?? '');
                $initial_count = (int)($data[2] ?? 0);
                $current_count = (int)($data[3] ?? 0);
                $hatch_date = trim($data[4] ?? date('Y-m-d'));
                $status = trim($data[5] ?? 'active');
                
                if (empty($flock_name)) {
                    $errors++;
                    continue;
                }
                
                // If current_count not provided, use initial_count
                if ($current_count === 0 && $initial_count > 0) {
                    $current_count = $initial_count;
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO flocks (flock_name, breed, initial_count, current_count, hatch_date, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$flock_name, $breed, $initial_count, $current_count, $hatch_date, $status]);
                $success++;
            } catch (Exception $e) {
                $errors++;
            }
        }
        fclose($handle);
    }
    
    return ['success' => $success, 'errors' => $errors];
}

function importExpenses($pdo, $file_path) {
    $success = 0;
    $errors = 0;
    
    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        $headers = fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            try {
                // Expected: Category, Description, Amount, Date (YYYY-MM-DD), Payment Method
                $category = trim($data[0] ?? '');
                $description = trim($data[1] ?? '');
                $amount = (float)($data[2] ?? 0);
                $transaction_date = trim($data[3] ?? date('Y-m-d'));
                $payment_method = trim($data[4] ?? 'cash');
                
                if (empty($description) || $amount <= 0) {
                    $errors++;
                    continue;
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO financial_records (type, category, description, amount, transaction_date, payment_method)
                    VALUES ('expense', ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$category, $description, $amount, $transaction_date, $payment_method]);
                $success++;
            } catch (Exception $e) {
                $errors++;
            }
        }
        fclose($handle);
    }
    
    return ['success' => $success, 'errors' => $errors];
}

function importIncome($pdo, $file_path) {
    $success = 0; $errors = 0;
    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        fgetcsv($handle); // skip header
        while (($data = fgetcsv($handle)) !== FALSE) {
            try {
                $category = trim($data[0] ?? ''); $description = trim($data[1] ?? '');
                $amount = (float)($data[2] ?? 0); $transaction_date = trim($data[3] ?? date('Y-m-d'));
                $payment_method = trim($data[4] ?? 'cash');
                if (empty($description) || $amount <= 0) { $errors++; continue; }
                $pdo->prepare("INSERT INTO financial_records (type, category, description, amount, transaction_date, payment_method) VALUES ('income', ?, ?, ?, ?, ?)")
                    ->execute([$category, $description, $amount, $transaction_date, $payment_method]);
                $success++;
            } catch (Exception $e) { $errors++; }
        }
        fclose($handle);
    }
    return ['success' => $success, 'errors' => $errors];
}

function importAnimals($pdo, $file_path) {
    $success = 0; $errors = 0;
    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== FALSE) {
            try {
                $tag = trim($data[0] ?? ''); $name = trim($data[1] ?? '');
                $type = trim($data[2] ?? ''); $breed = trim($data[3] ?? '');
                $gender = trim($data[4] ?? ''); $birth_date = trim($data[5] ?? null);
                $status = trim($data[6] ?? 'active'); $notes = trim($data[7] ?? '');
                if (empty($tag)) { $errors++; continue; }
                $pdo->prepare("INSERT INTO animals (tag, name, type, breed, gender, birth_date, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), status=VALUES(status)")
                    ->execute([$tag, $name, $type, $breed, $gender, $birth_date ?: null, $status, $notes]);
                $success++;
            } catch (Exception $e) { $errors++; }
        }
        fclose($handle);
    }
    return ['success' => $success, 'errors' => $errors];
}

function importCrops($pdo, $file_path) {
    $success = 0; $errors = 0;
    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== FALSE) {
            try {
                $field_name = trim($data[0] ?? ''); $crop = trim($data[1] ?? '');
                $variety = trim($data[2] ?? ''); $planting_date = trim($data[3] ?? date('Y-m-d'));
                $area_acres = (float)($data[4] ?? 0); $expected_harvest = trim($data[5] ?? null);
                $expected_yield = (float)($data[6] ?? 0); $yield_unit = trim($data[7] ?? 'kg');
                $status = trim($data[8] ?? 'growing'); $notes = trim($data[9] ?? '');
                if (empty($crop)) { $errors++; continue; }
                $field_id = null;
                if (!empty($field_name)) {
                    $frow = $pdo->prepare("SELECT id FROM fields WHERE name = ? LIMIT 1");
                    $frow->execute([$field_name]); $fr = $frow->fetch();
                    if ($fr) $field_id = $fr['id'];
                }
                $pdo->prepare("INSERT INTO crop_plantings (field_id, crop, variety, planting_date, area_acres, expected_harvest_date, expected_yield, yield_unit, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$field_id, $crop, $variety, $planting_date, $area_acres, $expected_harvest ?: null, $expected_yield, $yield_unit, $status, $notes]);
                $success++;
            } catch (Exception $e) { $errors++; }
        }
        fclose($handle);
    }
    return ['success' => $success, 'errors' => $errors];
}

function importWorkers($pdo, $file_path) {
    $success = 0; $errors = 0;
    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== FALSE) {
            try {
                $name = trim($data[0] ?? ''); $phone = trim($data[1] ?? '');
                $role = trim($data[2] ?? ''); $wage_type = trim($data[3] ?? 'daily');
                $wage_rate = (float)($data[4] ?? 0); $status = trim($data[5] ?? 'active');
                $notes = trim($data[6] ?? '');
                if (empty($name)) { $errors++; continue; }
                $pdo->prepare("INSERT INTO workers (name, phone, role, wage_type, wage_rate, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE phone=VALUES(phone), status=VALUES(status)")
                    ->execute([$name, $phone, $role, $wage_type, $wage_rate, $status, $notes]);
                $success++;
            } catch (Exception $e) { $errors++; }
        }
        fclose($handle);
    }
    return ['success' => $success, 'errors' => $errors];
}
?>

<?php include __DIR__ . '/includes/admin_header.php'; ?>

<?php if ($success_message): ?>
<div style="display:flex;align-items:center;gap:10px;padding:14px 18px;background:#f0fdf4;border:1px solid #bbf7d0;border-left:4px solid #16a34a;border-radius:10px;color:#166534;font-size:.9rem;font-weight:500;margin-bottom:24px;">
    <i data-lucide="check-circle-2" style="width:18px;height:18px;color:#16a34a;flex-shrink:0;"></i>
    <?php echo htmlspecialchars($success_message); ?>
</div>
<?php endif; ?>
<?php if ($error_message): ?>
<div style="display:flex;align-items:center;gap:10px;padding:14px 18px;background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #dc2626;border-radius:10px;color:#991b1b;font-size:.9rem;font-weight:500;margin-bottom:24px;">
    <i data-lucide="alert-octagon" style="width:18px;height:18px;color:#dc2626;flex-shrink:0;"></i>
    <?php echo htmlspecialchars($error_message); ?>
</div>
<?php endif; ?>

<style>
.bie-hdr{width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#14532D,#1B7A3D);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px rgba(27,94,32,.25);flex-shrink:0;}
.bie-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;}
.bie-tile{display:flex;flex-direction:column;gap:12px;padding:20px;background:#fff;border:1px solid #E7EAF0;border-radius:14px;box-shadow:0 4px 14px rgba(15,23,42,.04);transition:transform .2s,box-shadow .2s,border-color .2s;}
.bie-tile:hover{transform:translateY(-3px);box-shadow:0 12px 28px rgba(15,23,42,.09);border-color:rgba(22,101,52,.3);}
.bie-chip{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.bie-tile h4{margin:0;font-size:.95rem;font-weight:700;color:#0F172A;}
.bie-tile p{margin:0;font-size:.79rem;color:#64748b;line-height:1.5;flex-grow:1;}
.w2-sh{display:flex;align-items:center;gap:10px;margin-bottom:6px;}
.w2-sh h3{margin:0;font-size:1.1rem;font-weight:700;color:#0F172A;}
.w2-ss{margin:2px 0 0;font-size:.84rem;color:#64748b;}
.w2-sd{border:none;border-top:1px solid #E7EAF0;margin:14px 0 22px;}
.bie-drop{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:26px 16px;border:2px dashed #cbd5e1;border-radius:10px;background:#F8FAFC;cursor:pointer;transition:border-color .2s,background .2s;text-align:center;box-sizing:border-box;}
.bie-drop:hover,.bie-drop.dragover{border-color:#166534;background:rgba(22,101,52,.04);}
.bie-drop i{width:28px;height:28px;color:#166534;}
.bie-drop span{font-size:.85rem;color:#475569;font-weight:500;}
.bie-drop small{font-size:.75rem;color:#94a3b8;}
.fmt-chip{display:inline-block;padding:4px 10px;margin:0 6px 6px 0;background:#fff;border:1px solid #E7EAF0;border-radius:6px;font-family:monospace;font-size:.77rem;color:#334155;}
</style>

<div style="margin-bottom:28px;display:flex;align-items:center;gap:16px;">
    <div class="bie-hdr"><i data-lucide="arrow-left-right" style="width:24px;height:24px;"></i></div>
    <div>
        <h1 style="margin:0;font-size:1.7rem;font-weight:800;color:#0F172A;letter-spacing:-.5px;">Bulk Import &amp; Export</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:.9rem;">Move your farm data in and out as CSV - backups, bulk edits, and recovery.</p>
    </div>
</div>

<?php $stat_defs=[
    ['products','package','Products',''],['orders','shopping-cart','Orders','accent'],
    ['customers','users','Customers','info'],['animals','paw-print','Animals',''],
    ['crops','sprout','Crops',''],['workers','hard-hat','Workers',''],
    ['expenses','trending-down','Expenses',''],['income','trending-up','Income',''],
]; ?>
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:28px;">
    <?php foreach($stat_defs as[$sk,$si,$sl,$sv]):?>
    <div class="stat-card">
        <div class="stat-card-info"><small><?=$sl?></small><strong><?=isset($record_counts[$sk])?number_format((int)$record_counts[$sk]):'&mdash;'?></strong></div>
        <div class="stat-card-icon <?=$sv?>"><i data-lucide="<?=$si?>" style="width:22px;height:22px;"></i></div>
    </div>
    <?php endforeach;?>
</div>

<div class="admin-card" style="margin-bottom:28px;">
    <div class="w2-sh"><i data-lucide="download" style="width:20px;height:20px;color:#166534;"></i><h3>Export Data to CSV</h3></div>
    <p class="w2-ss">Download current data for backup, analysis in Excel, or as an import template reference.</p>
    <hr class="w2-sd">
    <?php $tiles=[
        ['products','package','Products','All products with categories, pricing, and stock levels.','rgba(22,101,52,.08)','#166534'],
        ['orders','shopping-cart','Orders','Complete order history with customer and payment details.','rgba(59,130,246,.1)','#2563eb'],
        ['customers','users','Customers','Customer database with contact information and accounts.','rgba(217,119,6,.12)','#b45309'],
        ['raw_materials','layers','Raw Materials','Ingredient stock levels and current pricing data.','rgba(139,92,246,.1)','#7c3aed'],
        ['flocks','bird','Flocks','Poultry flock records with breeds, counts, and status.','rgba(22,163,74,.1)','#15803d'],
        ['expenses','trending-down','Expenses','Farm expense records with categories and payment details.','rgba(220,38,38,.08)','#dc2626'],
        ['income','trending-up','Income','All income transactions with categories and methods.','rgba(16,185,129,.1)','#059669'],
        ['animals','paw-print','Animals','Livestock records with tags, breeds, and health status.','rgba(245,158,11,.1)','#d97706'],
        ['crops','sprout','Crop Plantings','Crop records with fields, expected yields, and harvest dates.','rgba(132,204,22,.12)','#4d7c0f'],
        ['workers','hard-hat','Workers','Worker profiles with roles, wage rates, and status.','rgba(99,102,241,.1)','#4f46e5'],
    ];?>
    <div class="bie-grid">
        <?php foreach($tiles as[$ek,$ei,$et,$ed,$bg,$col]):?>
        <div class="bie-tile">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                <div class="bie-chip" style="background:<?=$bg?>;color:<?=$col?>;"><i data-lucide="<?=$ei?>" style="width:20px;height:20px;"></i></div>
                <?php if(isset($record_counts[$ek])&&$record_counts[$ek]!==null):?><span class="badge-pill badge-pill-success"><?=number_format((int)$record_counts[$ek])?> records</span><?php endif;?>
            </div>
            <h4><?=$et?></h4><p><?=$ed?></p>
            <a href="?export=<?=$ek?>" class="btn btn-primary btn-sm" style="text-decoration:none;justify-content:center;">
                <i data-lucide="download" style="width:14px;height:14px;"></i> Export <?=$et?>
            </a>
        </div>
        <?php endforeach;?>
    </div>
</div>

<div class="admin-card" style="margin-bottom:28px;">
    <div class="w2-sh"><i data-lucide="upload" style="width:20px;height:20px;color:#166534;"></i><h3>Import Data from CSV</h3></div>
    <p class="w2-ss">Pick a data type, choose your CSV file, and import. Duplicate handling is explained below.</p>
    <hr class="w2-sd">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf_token,ENT_QUOTES,'UTF-8')?>">
        <input type="hidden" name="action" value="import">
        <div style="display:grid;grid-template-columns:300px 1fr;gap:20px;margin-bottom:22px;">
            <div class="admin-form-group" style="margin-bottom:0;">
                <label class="admin-form-label" for="import_type">Import Type</label>
                <select name="import_type" id="import_type" required class="admin-form-control">
                    <option value="">Select Data Type...</option>
                    <?php foreach($import_formats as$fk=>$fm):?><option value="<?=$fk?>"><?=$fm['label']?></option><?php endforeach;?>
                </select>
            </div>
            <div class="admin-form-group" style="margin-bottom:0;">
                <label class="admin-form-label" for="import_file">CSV File</label>
                <label class="bie-drop" for="import_file" id="file-drop">
                    <i data-lucide="file-up"></i>
                    <span id="file-name">Click to choose a CSV file, or drag &amp; drop</span>
                    <small>.csv only - up to <?=htmlspecialchars((string)ini_get('upload_max_filesize'))?></small>
                    <input type="file" name="import_file" id="import_file" accept=".csv" required hidden>
                </label>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary"><i data-lucide="upload" style="width:18px;height:18px;"></i> Upload &amp; Import Data</button>
            <span style="font-size:.8rem;color:#94a3b8;">Not sure of the format? Download the <a href="?template=products" id="format-template" style="color:#166534;font-weight:600;">CSV template</a> for the selected type.</span>
        </div>
    </form>
    <div id="format-panel" style="display:none;margin-top:22px;padding:16px 20px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <i data-lucide="info" style="width:16px;height:16px;color:#d97706;"></i>
            <h4 id="format-title" style="margin:0;font-size:.88rem;font-weight:700;color:#92400e;">CSV columns</h4>
        </div>
        <div id="format-columns" style="line-height:1;"></div>
        <p style="margin:8px 0 0;font-size:.76rem;color:#92400e;">TIP: The first row is the header - it is skipped automatically during import.</p>
    </div>
</div>

<div class="admin-card">
    <div class="w2-sh"><i data-lucide="settings" style="width:20px;height:20px;color:#166534;"></i><h3>Import Behavior</h3></div>
    <p class="w2-ss">How the system handles duplicate records during import.</p>
    <hr class="w2-sd">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div style="background:#f0fdf4;border:1px solid #dcfce7;padding:20px;border-radius:10px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;"><i data-lucide="refresh-cw" style="width:18px;height:18px;color:#16a34a;"></i><h4 style="margin:0;font-weight:700;font-size:.9rem;color:#166534;">Products, Raw Materials, Customers, Animals, Workers</h4></div>
            <p style="margin:0;font-size:.83rem;color:#15803d;line-height:1.6;"><strong>UPDATE ON DUPLICATE:</strong> if a record with the same identifier already exists, its data is updated instead of creating a duplicate row.</p>
        </div>
        <div style="background:#fffbeb;border:1px solid #fde68a;padding:20px;border-radius:10px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;"><i data-lucide="plus-circle" style="width:18px;height:18px;color:#d97706;"></i><h4 style="margin:0;font-weight:700;font-size:.9rem;color:#92400e;">Flocks, Expenses, Income, Crop Plantings</h4></div>
            <p style="margin:0;font-size:.83rem;color:#b45309;line-height:1.6;"><strong>ALWAYS INSERT:</strong> every row creates a new record - ideal for historical data that does not need deduplication.</p>
        </div>
    </div>
</div>

<script>
(function(){
    var F=<?php echo json_encode($import_formats,JSON_UNESCAPED_UNICODE);?>;
    var sel=document.getElementById('import_type'),panel=document.getElementById('format-panel'),
        ttl=document.getElementById('format-title'),cols=document.getElementById('format-columns'),
        tpl=document.getElementById('format-template'),fi=document.getElementById('import_file'),
        fn=document.getElementById('file-name'),drop=document.getElementById('file-drop');
    function rf(){var f=F[sel.value];if(!f){panel.style.display='none';return;}
        ttl.textContent=f.label+' - required CSV columns';
        cols.innerHTML=f.columns.map(function(c){return'<span class="fmt-chip">'+c+'</span>';}).join('');
        tpl.href='?template='+encodeURIComponent(sel.value);panel.style.display='block';}
    sel.addEventListener('change',rf);
    fi.addEventListener('change',function(){if(fi.files.length){fn.textContent=fi.files[0].name;drop.style.borderColor='#166534';}});
    ['dragover','dragenter'].forEach(function(e){drop.addEventListener(e,function(ev){ev.preventDefault();drop.classList.add('dragover');});});
    ['dragleave','drop'].forEach(function(e){drop.addEventListener(e,function(ev){ev.preventDefault();drop.classList.remove('dragover');});});
    drop.addEventListener('drop',function(e){if(e.dataTransfer.files.length){fi.files=e.dataTransfer.files;fn.textContent=fi.files[0].name;}});
})();
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
