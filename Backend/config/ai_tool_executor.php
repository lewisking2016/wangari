<?php
/**
 * Wangari AI Tool Executor
 * 
 * Executes the tool calls that OpenRouter returns via function calling.
 * This is the bridge between the AI's decisions and the actual database operations.
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
        // Log the tool call
        $this->logToolCall($toolName, $arguments);
        
        switch ($toolName) {
            // Poultry
            case 'add_flock':
                return $this->addFlock($arguments);
            case 'record_poultry_production':
                return $this->recordPoultryProduction($arguments);
            case 'list_flocks':
                return $this->listFlocks();
            case 'delete_flock':
                return $this->deleteFlock($arguments);
                
            // Livestock
            case 'add_animal':
                return $this->addAnimal($arguments);
            case 'record_milk':
                return $this->recordMilk($arguments);
            case 'list_animals':
                return $this->listAnimals($arguments);
            case 'delete_animal':
                return $this->deleteAnimal($arguments);
                
            // Crops
            case 'add_field':
                return $this->addField($arguments);
            case 'list_fields':
                return $this->listFields();
                
            // Finance
            case 'record_expense':
                return $this->recordExpense($arguments);
            case 'record_income':
                return $this->recordIncome($arguments);
            case 'get_finance_summary':
                return $this->getFinanceSummary($arguments);
                
            // Customers & Orders
            case 'add_customer':
                return $this->addCustomer($arguments);
            case 'create_order':
                return $this->createOrder($arguments);
            case 'list_customers':
                return $this->listCustomers();
                
            // Queries
            case 'get_farm_summary':
                return $this->getFarmSummary();
            case 'search_web':
                return $this->searchWeb($arguments);
                
            default:
                return ['success' => false, 'error' => "Unknown tool: $toolName"];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // POULTRY IMPLEMENTATIONS
    // ═══════════════════════════════════════════════════════════════
    
    private function addFlock($args) {
        $type = $args['type'] ?? 'broiler';
        $quantity = (int)($args['quantity'] ?? 50);
        $breed = $args['breed'] ?? 'Kenchic';
        $notes = $args['notes'] ?? 'Created via AI assistant';
        
        $batchNo = 'BATCH-' . strtoupper(uniqid());
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO poultry_batches (user_id, batch_no, type, breed, quantity, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, 'active', ?, NOW())
            ");
            $stmt->execute([$this->userId, $batchNo, $type, $breed, $quantity, $notes]);
            
            return [
                'success' => true,
                'batch_id' => $this->pdo->lastInsertId(),
                'batch_no' => $batchNo,
                'type' => $type,
                'quantity' => $quantity,
                'message' => "Created {$quantity} {$type} birds (Batch: {$batchNo})"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function recordPoultryProduction($args) {
        $eggs = (int)($args['eggs'] ?? 0);
        $mortality = (int)($args['mortality'] ?? 0);
        $feedUsed = (float)($args['feed_used'] ?? 0);
        $notes = $args['notes'] ?? '';
        
        try {
            // Get the latest active flock
            $stmt = $this->pdo->prepare("
                SELECT id, batch_no, type FROM poultry_batches 
                WHERE user_id = ? AND status = 'active' 
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$this->userId]);
            $flock = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$flock) {
                return ['success' => false, 'error' => 'No active flock found. Create one first.'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO poultry_production (user_id, batch_id, eggs, mortality, feed_used, notes, recorded_date, created_at)
                VALUES (?, ?, ?, ?, ?, ?, CURDATE(), NOW())
            ");
            $stmt->execute([$this->userId, $flock['id'], $eggs, $mortality, $feedUsed, $notes]);
            
            $message = "Recorded for {$flock['batch_no']}: ";
            if ($eggs > 0) $message .= "{$eggs} eggs, ";
            if ($mortality > 0) $message .= "{$mortality} mortality, ";
            if ($feedUsed > 0) $message .= "{$feedUsed}kg feed, ";
            $message = rtrim($message, ', ');
            
            return [
                'success' => true,
                'message' => $message,
                'date' => date('Y-m-d')
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function listFlocks() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, batch_no, type, breed, quantity, status, created_at
                FROM poultry_batches 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$this->userId]);
            $flocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'flocks' => $flocks,
                'count' => count($flocks)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function deleteFlock($args) {
        $flockId = (int)($args['flock_id'] ?? 0);
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE poultry_batches SET status = 'deleted' 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$flockId, $this->userId]);
            
            return [
                'success' => true,
                'message' => "Flock #{$flockId} has been removed"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // LIVESTOCK IMPLEMENTATIONS
    // ═══════════════════════════════════════════════════════════════
    
    private function addAnimal($args) {
        $name = $args['name'] ?? 'Animal-' . rand(100, 999);
        $type = $args['type'] ?? 'cattle';
        $breed = $args['breed'] ?? 'Mixed';
        $gender = $args['gender'] ?? 'female';
        $notes = $args['notes'] ?? 'Added via AI assistant';
        
        $tagId = strtoupper(substr($type, 0, 2)) . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO livestock (user_id, tag_id, name, type, breed, gender, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'active', ?, NOW())
            ");
            $stmt->execute([$this->userId, $tagId, $name, $type, $breed, $gender, $notes]);
            
            return [
                'success' => true,
                'animal_id' => $this->pdo->lastInsertId(),
                'tag_id' => $tagId,
                'name' => $name,
                'type' => $type,
                'message' => "Added {$name} ({$tag_id}) - {$type}"
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
            // Find animal by ID or name
            if ($animalId <= 0 && !empty($animalName)) {
                $stmt = $this->pdo->prepare("
                    SELECT id FROM livestock 
                    WHERE user_id = ? AND name LIKE ? AND type = 'cattle' AND status = 'active'
                    LIMIT 1
                ");
                $stmt->execute([$this->userId, "%{$animalName}%"]);
                $animal = $stmt->fetch(PDO::FETCH_ASSOC);
                $animalId = $animal['id'] ?? 0;
            }
            
            if ($animalId <= 0) {
                return ['success' => false, 'error' => 'Cow not found. Add a cow first or specify the name.'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO milk_production (user_id, animal_id, morning_liters, evening_liters, recorded_date, created_at)
                VALUES (?, ?, ?, ?, CURDATE(), NOW())
            ");
            $stmt->execute([$this->userId, $animalId, $morning, $evening]);
            
            $total = $morning + $evening;
            $revenue = $total * 50; // KES 50/liter average
            
            return [
                'success' => true,
                'liters' => $total,
                'revenue_estimate' => $revenue,
                'message' => "Recorded {$total} liters (Morning: {$morning}L, Evening: {$evening}L). Estimated revenue: KES {$revenue}"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function listAnimals($args) {
        $type = $args['type'] ?? '';
        
        try {
            $sql = "SELECT id, tag_id, name, type, breed, gender, status, created_at FROM livestock WHERE user_id = ?";
            $params = [$this->userId];
            
            if (!empty($type)) {
                $sql .= " AND type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'animals' => $animals,
                'count' => count($animals)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function deleteAnimal($args) {
        $animalId = (int)($args['animal_id'] ?? 0);
        $reason = $args['reason'] ?? 'other';
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE livestock SET status = ?, removed_reason = ?
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$reason, $reason, $animalId, $this->userId]);
            
            return [
                'success' => true,
                'message' => "Animal #{$animalId} marked as {$reason}"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // CROP IMPLEMENTATIONS
    // ═══════════════════════════════════════════════════════════════
    
    private function addField($args) {
        $name = $args['name'] ?? 'Field-' . rand(100, 999);
        $crop = $args['crop'] ?? 'maize';
        $acreage = (float)($args['acreage'] ?? 1);
        $location = $args['location'] ?? '';
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO fields (user_id, name, crop, acreage, location, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([$this->userId, $name, $crop, $acreage, $location]);
            
            return [
                'success' => true,
                'field_id' => $this->pdo->lastInsertId(),
                'name' => $name,
                'crop' => $crop,
                'acreage' => $acreage,
                'message' => "Added field '{$name}' - {$acreage} acres of {$crop}"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function listFields() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, name, crop, acreage, location, status, created_at
                FROM fields 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$this->userId]);
            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'fields' => $fields,
                'count' => count($fields)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // FINANCE IMPLEMENTATIONS
    // ═══════════════════════════════════════════════════════════════
    
    private function recordExpense($args) {
        $category = $args['category'] ?? 'other';
        $amount = (float)($args['amount'] ?? 0);
        $description = $args['description'] ?? '';
        
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Amount must be greater than 0'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO expenses (user_id, category, amount, description, expense_date, created_at)
                VALUES (?, ?, ?, ?, CURDATE(), NOW())
            ");
            $stmt->execute([$this->userId, $category, $amount, $description]);
            
            return [
                'success' => true,
                'expense_id' => $this->pdo->lastInsertId(),
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
        
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Amount must be greater than 0'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO income (user_id, category, amount, description, payment_method, income_date, created_at)
                VALUES (?, ?, ?, ?, ?, CURDATE(), NOW())
            ");
            $stmt->execute([$this->userId, $category, $amount, $description, $paymentMethod]);
            
            return [
                'success' => true,
                'income_id' => $this->pdo->lastInsertId(),
                'amount' => $amount,
                'category' => $category,
                'message' => "Recorded KES " . number_format($amount) . " income from {$category}"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function getFinanceSummary($args) {
        $period = $args['period'] ?? 'month';
        
        try {
            $dateFilter = match($period) {
                'today' => 'CURDATE()',
                'week' => 'DATE_SUB(CURDATE(), INTERVAL 7 DAY)',
                'month' => 'DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
                'year' => 'DATE_SUB(CURDATE(), INTERVAL 1 YEAR)',
                default => 'DATE_SUB(CURDATE(), INTERVAL 30 DAY)'
            };
            
            // Get expenses
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as total_expenses
                FROM expenses 
                WHERE user_id = ? AND expense_date >= {$dateFilter}
            ");
            $stmt->execute([$this->userId]);
            $expenses = $stmt->fetch(PDO::FETCH_ASSOC)['total_expenses'];
            
            // Get income
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as total_income
                FROM income 
                WHERE user_id = ? AND income_date >= {$dateFilter}
            ");
            $stmt->execute([$this->userId]);
            $income = $stmt->fetch(PDO::FETCH_ASSOC)['total_income'];
            
            $profit = $income - $expenses;
            
            return [
                'success' => true,
                'period' => $period,
                'income' => $income,
                'expenses' => $expenses,
                'profit' => $profit,
                'message' => "Finance Summary ({$period}):\nIncome: KES " . number_format($income) . "\nExpenses: KES " . number_format($expenses) . "\nProfit: KES " . number_format($profit)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // CUSTOMER & ORDER IMPLEMENTATIONS
    // ═══════════════════════════════════════════════════════════════
    
    private function addCustomer($args) {
        $name = $args['name'] ?? '';
        $phone = $args['phone'] ?? '';
        $email = $args['email'] ?? '';
        $notes = $args['notes'] ?? '';
        
        if (empty($name)) {
            return ['success' => false, 'error' => 'Customer name is required'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO customers (user_id, name, phone, email, notes, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$this->userId, $name, $phone, $email, $notes]);
            
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
        $customerName = $args['customer_name'] ?? 'Walk-in';
        $items = $args['items'] ?? '';
        $totalAmount = (float)($args['total_amount'] ?? 0);
        $paymentMethod = $args['payment_method'] ?? 'cash';
        $paymentStatus = $args['payment_status'] ?? 'paid';
        
        $orderNo = 'ORD-' . strtoupper(uniqid());
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (user_id, order_no, customer_name, items, total_amount, payment_method, payment_status, order_date, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), NOW())
            ");
            $stmt->execute([$this->userId, $orderNo, $customerName, $items, $totalAmount, $paymentMethod, $paymentStatus]);
            
            return [
                'success' => true,
                'order_id' => $this->pdo->lastInsertId(),
                'order_no' => $orderNo,
                'total' => $totalAmount,
                'message' => "Created order {$orderNo} for KES " . number_format($totalAmount) . " ({$paymentMethod})"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function listCustomers() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, name, phone, email, created_at
                FROM customers 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$this->userId]);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'customers' => $customers,
                'count' => count($customers)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // QUERY IMPLEMENTATIONS
    // ═══════════════════════════════════════════════════════════════
    
    private function getFarmSummary() {
        try {
            $summary = [
                'poultry_batches' => 0,
                'total_birds' => 0,
                'total_animals' => 0,
                'total_fields' => 0,
                'total_customers' => 0,
                'orders_this_month' => 0,
                'revenue_this_month' => 0,
                'expenses_this_month' => 0,
            ];
            
            // Poultry
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as batches, COALESCE(SUM(quantity), 0) as birds
                FROM poultry_batches WHERE user_id = ? AND status = 'active'
            ");
            $stmt->execute([$this->userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $summary['poultry_batches'] = $row['batches'];
            $summary['total_birds'] = $row['birds'];
            
            // Animals
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM livestock WHERE user_id = ? AND status = 'active'
            ");
            $stmt->execute([$this->userId]);
            $summary['total_animals'] = $stmt->fetchColumn();
            
            // Fields
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM fields WHERE user_id = ? AND status = 'active'
            ");
            $stmt->execute([$this->userId]);
            $summary['total_fields'] = $stmt->fetchColumn();
            
            // Customers
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM customers WHERE user_id = ?
            ");
            $stmt->execute([$this->userId]);
            $summary['total_customers'] = $stmt->fetchColumn();
            
            // Orders this month
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as revenue
                FROM orders WHERE user_id = ? AND order_date >= DATE_FORMAT(NOW(), '%Y-%m-01')
            ");
            $stmt->execute([$this->userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $summary['orders_this_month'] = $row['count'];
            $summary['revenue_this_month'] = $row['revenue'];
            
            // Expenses this month
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM expenses WHERE user_id = ? AND expense_date >= DATE_FORMAT(NOW(), '%Y-%m-01')
            ");
            $stmt->execute([$this->userId]);
            $summary['expenses_this_month'] = $stmt->fetchColumn();
            
            $summary['profit_this_month'] = $summary['revenue_this_month'] - $summary['expenses_this_month'];
            
            return [
                'success' => true,
                'summary' => $summary,
                'message' => $this->formatFarmSummary($summary)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function formatFarmSummary($s) {
        $msg = "Farm Summary:\n\n";
        $msg .= "Poultry: {$s['poultry_batches']} batches ({$s['total_birds']} birds)\n";
        $msg .= "Livestock: {$s['total_animals']} animals\n";
        $msg .= "Fields: {$s['total_fields']}\n";
        $msg .= "Customers: {$s['total_customers']}\n\n";
        $msg .= "This Month:\n";
        $msg .= "  Orders: {$s['orders_this_month']}\n";
        $msg .= "  Revenue: KES " . number_format($s['revenue_this_month']) . "\n";
        $msg .= "  Expenses: KES " . number_format($s['expenses_this_month']) . "\n";
        $msg .= "  Profit: KES " . number_format($s['profit_this_month']);
        return $msg;
    }
    
    private function searchWeb($args) {
        $query = $args['query'] ?? '';
        
        if (empty($query)) {
            return ['success' => false, 'error' => 'Search query is required'];
        }
        
        require_once dirname(__DIR__, 2) . '/Backend/config/web_search.php';
        $results = wangari_web_search($query, 3);
        
        if (empty($results)) {
            return ['success' => true, 'results' => [], 'message' => "No results found for: {$query}"];
        }
        
        $msg = "Search results for: {$query}\n\n";
        foreach ($results as $i => $r) {
            $msg .= ($i + 1) . ". " . $r['title'] . "\n";
            $msg .= "   " . $r['snippet'] . "\n\n";
        }
        
        return [
            'success' => true,
            'results' => $results,
            'message' => $msg
        ];
    }
    
    // ═══════════════════════════════════════════════════════════════
    // LOGGING
    // ═══════════════════════════════════════════════════════════════
    
    private function logToolCall($toolName, $arguments) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO ai_tool_logs (user_id, tool_name, arguments, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$this->userId, $toolName, json_encode($arguments)]);
        } catch (Exception $e) {
            // Silently fail
        }
    }
}
