<?php
/**
 * Wangari AI Tool Executor
 * 
 * Executes tool calls from OpenRouter against the actual database.
 * Uses correct table names and column names.
 */

require_once dirname(__DIR__, 2) . '/Backend/config/database.php';

class WangariAIToolExecutor {
    
    private $userId;
    private $pdo;
    
    public function __construct($userId) {
        $this->userId = $userId;
        $this->pdo = getDatabaseConnection();
    }
    
    /**
     * Execute a tool call from OpenRouter
     */
    public function execute($toolName, $arguments) {
        $startTime = microtime(true);
        
        switch ($toolName) {
            case 'add_flock':
                $result = $this->addFlock($arguments);
                break;
            case 'record_poultry_production':
                $result = $this->recordPoultryProduction($arguments);
                break;
            case 'list_flocks':
                $result = $this->listFlocks();
                break;
            case 'delete_flock':
                $result = $this->deleteFlock($arguments);
                break;
            case 'add_animal':
                $result = $this->addAnimal($arguments);
                break;
            case 'record_milk':
                $result = $this->recordMilk($arguments);
                break;
            case 'list_animals':
                $result = $this->listAnimals($arguments);
                break;
            case 'delete_animal':
                $result = $this->deleteAnimal($arguments);
                break;
            case 'add_field':
                $result = $this->addField($arguments);
                break;
            case 'list_fields':
                $result = $this->listFields();
                break;
            case 'record_expense':
                $result = $this->recordExpense($arguments);
                break;
            case 'record_income':
                $result = $this->recordIncome($arguments);
                break;
            case 'get_finance_summary':
                $result = $this->getFinanceSummary($arguments);
                break;
            case 'add_customer':
                $result = $this->addCustomer($arguments);
                break;
            case 'create_order':
                $result = $this->createOrder($arguments);
                break;
            case 'list_customers':
                $result = $this->listCustomers();
                break;
            case 'get_farm_summary':
                $result = $this->getFarmSummary();
                break;
            case 'search_web':
                $result = $this->searchWeb($arguments);
                break;
            default:
                $result = ['success' => false, 'error' => "Unknown tool: $toolName"];
        }
        
        // Log execution time
        $elapsed = round((microtime(true) - $startTime) * 1000);
        $this->logToolCall($toolName, $arguments, $result, $elapsed);
        
        return $result;
    }
    
    // ═══════════════════════════════════════════════════════════════
    // POULTRY - Table: flocks
    // ═══════════════════════════════════════════════════════════════
    
    private function addFlock($args) {
        $type = $args['type'] ?? 'broiler';
        $quantity = (int)($args['quantity'] ?? 50);
        $breed = $args['breed'] ?? 'Kenchic';
        $flockName = ucfirst($type) . '-' . date('Ymd') . '-' . rand(10, 99);
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO flocks (flock_name, breed, initial_count, current_count, hatch_date, status, created_at)
                VALUES (?, ?, ?, ?, CURDATE(), 'active', NOW())
            ");
            $stmt->execute([$flockName, $breed, $quantity, $quantity]);
            
            return [
                'success' => true,
                'flock_id' => $this->pdo->lastInsertId(),
                'flock_name' => $flockName,
                'type' => $type,
                'quantity' => $quantity,
                'message' => "Created {$quantity} {$type} birds ({$flockName})"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function recordPoultryProduction($args) {
        $eggs = (int)($args['eggs'] ?? 0);
        $mortality = (int)($args['mortality'] ?? 0);
        $feedUsed = (float)($args['feed_used'] ?? 0);
        
        try {
            // Get the latest active flock
            $stmt = $this->pdo->prepare("
                SELECT id, flock_name, breed FROM flocks 
                WHERE status = 'active' ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute();
            $flock = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$flock) {
                return ['success' => false, 'error' => 'No active flock found. Create one first.'];
            }
            
            // Use production_records or egg_losses table
            if ($eggs > 0) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO production_records (flock_id, eggs_collected, mortality, feed_kg, recorded_date, created_at)
                    VALUES (?, ?, ?, ?, CURDATE(), NOW())
                ");
                $stmt->execute([$flock['id'], $eggs, $mortality, $feedUsed]);
            } elseif ($mortality > 0) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO mortality_records (flock_id, count, cause, recorded_date, created_at)
                    VALUES (?, ?, 'unknown', CURDATE(), NOW())
                ");
                $stmt->execute([$flock['id'], $mortality]);
            }
            
            $msg = "Recorded for {$flock['flock_name']}: ";
            if ($eggs > 0) $msg .= "{$eggs} eggs, ";
            if ($mortality > 0) $msg .= "{$mortality} mortality, ";
            if ($feedUsed > 0) $msg .= "{$feedUsed}kg feed";
            
            return ['success' => true, 'message' => rtrim($msg, ', '), 'date' => date('Y-m-d')];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function listFlocks() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, flock_name, breed, initial_count, current_count, status, created_at
                FROM flocks ORDER BY created_at DESC LIMIT 20
            ");
            $stmt->execute();
            $flocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'flocks' => $flocks, 'count' => count($flocks)];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function deleteFlock($args) {
        $flockId = (int)($args['flock_id'] ?? 0);
        try {
            $stmt = $this->pdo->prepare("UPDATE flocks SET status = 'archived' WHERE id = ?");
            $stmt->execute([$flockId]);
            return ['success' => true, 'message' => "Flock #{$flockId} archived"];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // LIVESTOCK - Table: animals
    // ═══════════════════════════════════════════════════════════════
    
    private function addAnimal($args) {
        $name = $args['name'] ?? 'Animal-' . rand(100, 999);
        $type = $args['type'] ?? 'cattle';
        $breed = $args['breed'] ?? 'Mixed';
        $gender = $args['gender'] ?? 'female';
        $tag = strtoupper(substr($type, 0, 2)) . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO animals (tag, name, type, breed, gender, birth_date, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, CURDATE(), 'active', 'Added via AI', NOW())
            ");
            $stmt->execute([$tag, $name, $type, $breed, $gender]);
            
            return [
                'success' => true,
                'animal_id' => $this->pdo->lastInsertId(),
                'tag' => $tag,
                'name' => $name,
                'type' => $type,
                'message' => "Added {$name} ({$tag}) - {$type}"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function recordMilk($args) {
        $animalId = (int)($args['animal_id'] ?? 0);
        $animalName = $args['animal_name'] ?? '';
        $liters = (float)($args['liters'] ?? 0);
        $morning = (float)($args['morning_liters'] ?? $liters / 2);
        $evening = (float)($args['evening_liters'] ?? $liters / 2);
        
        try {
            if ($animalId <= 0 && !empty($animalName)) {
                $stmt = $this->pdo->prepare("
                    SELECT id FROM animals WHERE name LIKE ? AND type = 'cattle' AND status = 'active' LIMIT 1
                ");
                $stmt->execute(["%{$animalName}%"]);
                $animal = $stmt->fetch(PDO::FETCH_ASSOC);
                $animalId = $animal['id'] ?? 0;
            }
            
            if ($animalId <= 0) {
                return ['success' => false, 'error' => 'Cow not found. Add a cow first or specify the name.'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO milking_records (animal_id, morning_liters, evening_liters, recorded_date, created_at)
                VALUES (?, ?, ?, CURDATE(), NOW())
            ");
            $stmt->execute([$animalId, $morning, $evening]);
            
            $total = $morning + $evening;
            $revenue = $total * 50;
            
            return [
                'success' => true,
                'liters' => $total,
                'message' => "Recorded {$total}L milk. Est. revenue: KES {$revenue}"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function listAnimals($args) {
        $type = $args['type'] ?? '';
        try {
            $sql = "SELECT id, tag, name, type, breed, gender, status FROM animals WHERE 1=1";
            $params = [];
            if (!empty($type)) {
                $sql .= " AND type = ?";
                $params[] = $type;
            }
            $sql .= " ORDER BY created_at DESC LIMIT 20";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'animals' => $animals, 'count' => count($animals)];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function deleteAnimal($args) {
        $animalId = (int)($args['animal_id'] ?? 0);
        $reason = $args['reason'] ?? 'other';
        try {
            $stmt = $this->pdo->prepare("UPDATE animals SET status = ? WHERE id = ?");
            $stmt->execute([$reason, $animalId]);
            return ['success' => true, 'message' => "Animal #{$animalId} marked as {$reason}"];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // CROPS - Table: fields
    // ═══════════════════════════════════════════════════════════════
    
    private function addField($args) {
        $name = $args['name'] ?? 'Field-' . rand(100, 999);
        $location = $args['location'] ?? '';
        $acreage = (float)($args['acreage'] ?? 1);
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO fields (name, location, size_acres, status, notes, created_at)
                VALUES (?, ?, ?, 'active', 'Added via AI', NOW())
            ");
            $stmt->execute([$name, $location, $acreage]);
            
            return [
                'success' => true,
                'field_id' => $this->pdo->lastInsertId(),
                'name' => $name,
                'acreage' => $acreage,
                'message' => "Added field '{$name}' - {$acreage} acres"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function listFields() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, name, location, size_acres, status, created_at
                FROM fields ORDER BY created_at DESC LIMIT 20
            ");
            $stmt->execute();
            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'fields' => $fields, 'count' => count($fields)];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // FINANCE - Table: financial_records
    // ═══════════════════════════════════════════════════════════════
    
    private function recordExpense($args) {
        $category = $args['category'] ?? 'other';
        $amount = (float)($args['amount'] ?? 0);
        $description = $args['description'] ?? '';
        
        if ($amount <= 0) return ['success' => false, 'error' => 'Amount must be greater than 0'];
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO financial_records (type, category, amount, description, transaction_date, created_at)
                VALUES ('expense', ?, ?, ?, CURDATE(), NOW())
            ");
            $stmt->execute([$category, $amount, $description]);
            
            return [
                'success' => true,
                'amount' => $amount,
                'category' => $category,
                'message' => "Recorded KES " . number_format($amount) . " expense for {$category}"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function recordIncome($args) {
        $category = $args['category'] ?? 'other';
        $amount = (float)($args['amount'] ?? 0);
        $description = $args['description'] ?? '';
        $paymentMethod = $args['payment_method'] ?? 'cash';
        
        if ($amount <= 0) return ['success' => false, 'error' => 'Amount must be greater than 0'];
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO financial_records (type, category, amount, description, payment_method, transaction_date, created_at)
                VALUES ('income', ?, ?, ?, ?, CURDATE(), NOW())
            ");
            $stmt->execute([$category, $amount, $description, $paymentMethod]);
            
            return [
                'success' => true,
                'amount' => $amount,
                'message' => "Recorded KES " . number_format($amount) . " income from {$category}"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function getFinanceSummary($args) {
        $period = $args['period'] ?? 'month';
        
        try {
            switch ($period) {
                case 'today': $dateFilter = 'CURDATE()'; break;
                case 'week': $dateFilter = 'DATE_SUB(CURDATE(), INTERVAL 7 DAY)'; break;
                case 'year': $dateFilter = 'DATE_SUB(CURDATE(), INTERVAL 1 YEAR)'; break;
                default: $dateFilter = 'DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
            }
            
            $stmt = $this->pdo->prepare("
                SELECT type, COALESCE(SUM(amount), 0) as total
                FROM financial_records WHERE transaction_date >= {$dateFilter} GROUP BY type
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $income = 0;
            $expenses = 0;
            foreach ($rows as $row) {
                if ($row['type'] === 'income') $income = $row['total'];
                if ($row['type'] === 'expense') $expenses = $row['total'];
            }
            
            return [
                'success' => true,
                'period' => $period,
                'income' => $income,
                'expenses' => $expenses,
                'profit' => $income - $expenses,
                'message' => "Finance ({$period}): Income KES " . number_format($income) . ", Expenses KES " . number_format($expenses) . ", Profit KES " . number_format($income - $expenses)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // CUSTOMERS & ORDERS
    // ═══════════════════════════════════════════════════════════════
    
    private function addCustomer($args) {
        $name = $args['name'] ?? '';
        $phone = $args['phone'] ?? '';
        $notes = $args['notes'] ?? '';
        
        if (empty($name)) return ['success' => false, 'error' => 'Customer name is required'];
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO walk_in_customers (customer_name, phone, customer_type, notes, created_at)
                VALUES (?, ?, 'retail', ?, NOW())
            ");
            $stmt->execute([$name, $phone, $notes]);
            
            return [
                'success' => true,
                'customer_id' => $this->pdo->lastInsertId(),
                'name' => $name,
                'message' => "Added customer: {$name}" . ($phone ? " ({$phone})" : '')
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function createOrder($args) {
        $items = $args['items'] ?? '';
        $totalAmount = (float)($args['total_amount'] ?? 0);
        $paymentMethod = $args['payment_method'] ?? 'cash';
        
        $orderNo = 'ORD-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (user_id, order_number, status, total_amount, payment_method, created_at)
                VALUES (?, ?, 'pending', ?, ?, NOW())
            ");
            $stmt->execute([$this->userId, $orderNo, $totalAmount, $paymentMethod]);
            
            return [
                'success' => true,
                'order_id' => $this->pdo->lastInsertId(),
                'order_number' => $orderNo,
                'total' => $totalAmount,
                'message' => "Created order {$orderNo} for KES " . number_format($totalAmount)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function listCustomers() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, customer_name, phone, customer_type, created_at
                FROM walk_in_customers ORDER BY created_at DESC LIMIT 20
            ");
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'customers' => $customers, 'count' => count($customers)];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // QUERIES
    // ═══════════════════════════════════════════════════════════════
    
    private function getFarmSummary() {
        try {
            $s = ['flocks' => 0, 'total_birds' => 0, 'animals' => 0, 'fields' => 0, 'customers' => 0, 'orders_month' => 0, 'revenue_month' => 0, 'expenses_month' => 0];
            
            $stmt = $this->pdo->query("SELECT COUNT(*) c, COALESCE(SUM(current_count),0) b FROM flocks WHERE status='active'");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $s['flocks'] = $row['c'];
            $s['total_birds'] = $row['b'];
            
            $s['animals'] = $this->pdo->query("SELECT COUNT(*) FROM animals WHERE status='active'")->fetchColumn();
            $s['fields'] = $this->pdo->query("SELECT COUNT(*) FROM fields WHERE status='active'")->fetchColumn();
            $s['customers'] = $this->pdo->query("SELECT COUNT(*) FROM walk_in_customers")->fetchColumn();
            
            $stmt = $this->pdo->query("
                SELECT COUNT(*) c, COALESCE(SUM(total_amount),0) r 
                FROM orders WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
            ");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $s['orders_month'] = $row['c'];
            $s['revenue_month'] = $row['r'];
            
            $s['expenses_month'] = $this->pdo->query("
                SELECT COALESCE(SUM(amount),0) FROM financial_records 
                WHERE type='expense' AND transaction_date >= DATE_FORMAT(NOW(), '%Y-%m-01')
            ")->fetchColumn();
            
            $s['profit_month'] = $s['revenue_month'] - $s['expenses_month'];
            
            return [
                'success' => true,
                'message' => "Farm: {$s['flocks']} flocks ({$s['total_birds']} birds), {$s['animals']} animals, {$s['fields']} fields, {$s['customers']} customers. This month: {$s['orders_month']} orders, KES " . number_format($s['revenue_month']) . " revenue, KES " . number_format($s['profit_month']) . " profit"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function searchWeb($args) {
        $query = $args['query'] ?? '';
        if (empty($query)) return ['success' => false, 'error' => 'Search query is required'];
        
        require_once dirname(__DIR__, 2) . '/Backend/config/web_search.php';
        $results = wangari_web_search($query, 3);
        
        if (empty($results)) return ['success' => true, 'results' => [], 'message' => "No results for: {$query}"];
        
        $msg = "Search: {$query}\n\n";
        foreach ($results as $i => $r) {
            $msg .= ($i + 1) . ". {$r['title']}\n   {$r['snippet']}\n\n";
        }
        
        return ['success' => true, 'results' => $results, 'message' => $msg];
    }
    
    // ═══════════════════════════════════════════════════════════════
    // LOGGING
    // ═══════════════════════════════════════════════════════════════
    
    private function logToolCall($toolName, $arguments, $result, $elapsedMs) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO ai_tool_logs (user_id, tool_name, arguments, result, success, execution_time_ms, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $this->userId,
                $toolName,
                json_encode($arguments),
                json_encode($result),
                $result['success'] ?? false,
                $elapsedMs
            ]);
        } catch (Exception $e) {
            // Silently fail
        }
    }
}
