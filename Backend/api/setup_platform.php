<?php
/**
 * Platform Admin Setup Script
 * Run once to create tables and admin user
 * DELETE THIS FILE AFTER RUNNING FOR SECURITY
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();

if (!$pdo) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$results = [];

try {
    // Read and execute migration SQL
    $sql = file_get_contents(__DIR__ . '/../config/migration_platform_admin.sql');
    
    // Split by semicolons and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $executed = 0;
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $pdo->exec($statement);
                $executed++;
            } catch (PDOException $e) {
                // Skip duplicate table errors
                if (strpos($e->getMessage(), 'already exists') === false) {
                    $results['errors'][] = $e->getMessage();
                }
            }
        }
    }
    
    $results['success'] = true;
    $results['statements_executed'] = $executed;
    $results['message'] = 'Platform admin tables created successfully';
    
    // Verify admin user exists
    $adminCheck = $pdo->query("SELECT id, username, role FROM platform_users WHERE username='admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($adminCheck) {
        $results['admin_user'] = $adminCheck;
    }
    
} catch (Exception $e) {
    $results['error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
