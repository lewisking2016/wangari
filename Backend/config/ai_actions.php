<?php
/**
 * Wangari AI Actions - CRUD Operations
 * 
 * These functions allow the AI to create, read, update, and delete
 * farm data when instructed by the user.
 */

require_once dirname(__DIR__, 2) . '/Backend/config/database.php';

class WangariAIActions {
    
    private $pdo;
    private $userId;
    
    public function __construct($userId) {
        $this->pdo = getDatabaseConnection();
        $this->userId = $userId;
    }
    
    // ═══════════════════════════════════════════════════════════════
    // POULTRY ACTIONS
    // ═══════════════════════════════════════════════════════════════
    
    /**
     * Add a new flock/batch of poultry
     */
    public function addFlock($data) {
        $batchNo = $data['batch_no'] ?? 'B-' . date('Ymd') . '-' . rand(100, 999);
        $type = $data['type'] ?? 'broiler';
        $breed = $data['breed'] ?? '';
        $quantity = (int)($data['quantity'] ?? 0);
        $startDate = $data['start_date'] ?? date('Y-m-d');
        $notes = $data['notes'] ?? '';
        
        if ($quantity <= 0) {
            return ['success' => false, 'error' => 'Quantity must be greater than 0'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO poultry_batches (user_id, batch_no, type, breed, quantity, start_date, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'active', ?, NOW())
            ");
            $stmt->execute([$this->userId, $batchNo, $type, $breed, $quantity, $startDate, $notes]);
            
            $batchId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'message' => "Created $type flock '$batchNo' with $quantity birds",
                'batch_id' => $batchId,
                'batch_no' => $batchNo,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to create flock: ' . $e->getMessage()];
        }
    }
    
    /**
     * Record daily poultry production
     */
    public function recordPoultryProduction($data) {
        $batchId = (int)($data['batch_id'] ?? 0);
        $date = $data['date'] ?? date('Y-m-d');
        $eggs = (int)($data['eggs'] ?? 0);
        $mortality = (int)($data['mortality'] ?? 0);
        $feedUsed = (float)($data['feed_used'] ?? 0);
        $weight = (float)($data['weight'] ?? 0);
        $notes = $data['notes'] ?? '';
        
        if ($batchId <= 0) {
            return ['success' => false, 'error' => 'Invalid batch ID'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO poultry_daily_records (user_id, batch_id, record_date, eggs_collected, mortality, feed_used_kg, body_weight, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    eggs_collected = VALUES(eggs_collected),
                    mortality = VALUES(mortality),
                    feed_used_kg = VALUES(feed_used_kg),
                    body_weight = VALUES(body_weight),
                    notes = VALUES(notes)
            ");
            $stmt->execute([$this->userId, $batchId, $date, $eggs, $mortality, $feedUsed, $weight, $notes]);
            
            return [
                'success' => true,
                'message' => "Recorded production for batch $batchId on $date",
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to record production: ' . $e->getMessage()];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // LIVESTOCK ACTIONS
    // ═══════════════════════════════════════════════════════════════
    
    /**
     * Add a new animal
     */
    public function addAnimal($data) {
        $tagId = $data['tag_id'] ?? 'TAG-' . rand(1000, 9999);
        $name = $data['name'] ?? '';
        $type = $data['type'] ?? 'cattle';
        $breed = $data['breed'] ?? '';
        $gender = $data['gender'] ?? 'female';
        $dob = $data['date_of_birth'] ?? null;
        $notes = $data['notes'] ?? '';
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO animals (user_id, tag_id, name, type, breed, gender, date_of_birth, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW())
            ");
            $stmt->execute([$this->userId, $tagId, $name, $type, $breed, $gender, $dob, $notes]);
            
            $animalId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'message' => "Added $type '$name' with tag $tagId",
                'animal_id' => $animalId,
                'tag_id' => $tagId,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to add animal: ' . $e->getMessage()];
        }
    }
    
    /**
     * Record milk production
     */
    public function recordMilkProduction($data) {
        $animalId = (int)($data['animal_id'] ?? 0);
        $date = $data['date'] ?? date('Y-m-d');
        $morningLiters = (float)($data['morning_liters'] ?? 0);
        $eveningLiters = (float)($data['evening_liters'] ?? 0);
        $notes = $data['notes'] ?? '';
        
        if ($animalId <= 0) {
            return ['success' => false, 'error' => 'Invalid animal ID'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO milk_records (user_id, animal_id, record_date, morning_liters, evening_liters, total_liters, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    morning_liters = VALUES(morning_liters),
                    evening_liters = VALUES(evening_liters),
                    total_liters = VALUES(total_liters),
                    notes = VALUES(notes)
            ");
            $total = $morningLiters + $eveningLiters;
            $stmt->execute([$this->userId, $animalId, $date, $morningLiters, $eveningLiters, $total, $notes]);
            
            return [
                'success' => true,
                'message' => "Recorded $total liters of milk for animal $animalId on $date",
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to record milk: ' . $e->getMessage()];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // CROP ACTIONS
    // ═══════════════════════════════════════════════════════════════
    
    /**
     * Add a new field
     */
    public function addField($data) {
        $name = $data['name'] ?? 'Field-' . rand(100, 999);
        $crop = $data['crop'] ?? '';
        $acreage = (float)($data['acreage'] ?? 0);
        $location = $data['location'] ?? '';
        $notes = $data['notes'] ?? '';
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO fields (user_id, name, crop, acreage, location, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, 'active', ?, NOW())
            ");
            $stmt->execute([$this->userId, $name, $crop, $acreage, $location, $notes]);
            
            $fieldId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'message' => "Added field '$name' for $crop on $acreage acres",
                'field_id' => $fieldId,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to add field: ' . $e->getMessage()];
        }
    }
    
    /**
     * Record planting
     */
    public function recordPlanting($data) {
        $fieldId = (int)($data['field_id'] ?? 0);
        $crop = $data['crop'] ?? '';
        $quantity = (int)($data['quantity'] ?? 0);
        $date = $data['date'] ?? date('Y-m-d');
        $notes = $data['notes'] ?? '';
        
        if ($fieldId <= 0) {
            return ['success' => false, 'error' => 'Invalid field ID'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO plantings (user_id, field_id, crop, quantity, planting_date, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, 'growing', ?, NOW())
            ");
            $stmt->execute([$this->userId, $fieldId, $crop, $quantity, $date, $notes]);
            
            return [
                'success' => true,
                'message' => "Recorded planting of $crop in field $fieldId on $date",
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to record planting: ' . $e->getMessage()];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // SALES & FINANCE ACTIONS
    // ═══════════════════════════════════════════════════════════════
    
    /**
     * Create a new customer
     */
    public function addCustomer($data) {
        $name = $data['name'] ?? '';
        $phone = $data['phone'] ?? '';
        $email = $data['email'] ?? '';
        $address = $data['address'] ?? '';
        $notes = $data['notes'] ?? '';
        
        if (empty($name)) {
            return ['success' => false, 'error' => 'Customer name is required'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO customers (user_id, name, phone, email, address, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, 'active', ?, NOW())
            ");
            $stmt->execute([$this->userId, $name, $phone, $email, $address, $notes]);
            
            $customerId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'message' => "Added customer '$name'",
                'customer_id' => $customerId,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to add customer: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create a new order/sale
     */
    public function createOrder($data) {
        $customerId = (int)($data['customer_id'] ?? 0);
        $items = $data['items'] ?? []; // [{product, quantity, unit_price}]
        $paymentMethod = $data['payment_method'] ?? 'cash';
        $notes = $data['notes'] ?? '';
        
        if (empty($items)) {
            return ['success' => false, 'error' => 'Order must have at least one item'];
        }
        
        // Calculate total
        $total = 0;
        foreach ($items as $item) {
            $total += ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
        }
        
        $orderNo = 'ORD-' . date('Ymd') . '-' . rand(100, 999);
        
        try {
            $this->pdo->beginTransaction();
            
            // Create order
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (user_id, customer_id, order_no, total_amount, payment_method, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, 'completed', ?, NOW())
            ");
            $stmt->execute([$this->userId, $customerId, $orderNo, $total, $paymentMethod, $notes]);
            
            $orderId = $this->pdo->lastInsertId();
            
            // Add order items
            $stmt = $this->pdo->prepare("
                INSERT INTO order_items (order_id, product_name, quantity, unit_price, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($items as $item) {
                $qty = $item['quantity'] ?? 0;
                $price = $item['unit_price'] ?? 0;
                $subtotal = $qty * $price;
                $stmt->execute([$orderId, $item['product'] ?? '', $qty, $price, $subtotal]);
            }
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => "Created order $orderNo for KES $total",
                'order_id' => $orderId,
                'order_no' => $orderNo,
                'total' => $total,
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => 'Failed to create order: ' . $e->getMessage()];
        }
    }
    
    /**
     * Record an expense
     */
    public function recordExpense($data) {
        $category = $data['category'] ?? 'other';
        $description = $data['description'] ?? '';
        $amount = (float)($data['amount'] ?? 0);
        $date = $data['date'] ?? date('Y-m-d');
        $paymentMethod = $data['payment_method'] ?? 'cash';
        $notes = $data['notes'] ?? '';
        
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Amount must be greater than 0'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO expenses (user_id, category, description, amount, expense_date, payment_method, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$this->userId, $category, $description, $amount, $date, $paymentMethod, $notes]);
            
            return [
                'success' => true,
                'message' => "Recorded expense of KES $amount for $category",
                'expense_id' => $this->pdo->lastInsertId(),
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to record expense: ' . $e->getMessage()];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // QUERY ACTIONS (Read)
    // ═══════════════════════════════════════════════════════════════
    
    /**
     * Get summary of farm data
     */
    public function getFarmSummary() {
        $summary = [];
        
        try {
            // Count poultry
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count, SUM(quantity) as total_birds FROM poultry_batches WHERE user_id = ? AND status = 'active'");
            $stmt->execute([$this->userId]);
            $poultry = $stmt->fetch(PDO::FETCH_ASSOC);
            $summary['poultry_batches'] = $poultry['count'] ?? 0;
            $summary['total_birds'] = $poultry['total_birds'] ?? 0;
            
            // Count animals
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM animals WHERE user_id = ? AND status = 'active'");
            $stmt->execute([$this->userId]);
            $animals = $stmt->fetch(PDO::FETCH_ASSOC);
            $summary['total_animals'] = $animals['count'] ?? 0;
            
            // Count fields
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM fields WHERE user_id = ? AND status = 'active'");
            $stmt->execute([$this->userId]);
            $fields = $stmt->fetch(PDO::FETCH_ASSOC);
            $summary['total_fields'] = $fields['count'] ?? 0;
            
            // Count customers
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM customers WHERE user_id = ? AND status = 'active'");
            $stmt->execute([$this->userId]);
            $customers = $stmt->fetch(PDO::FETCH_ASSOC);
            $summary['total_customers'] = $customers['count'] ?? 0;
            
            // Recent orders
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute([$this->userId]);
            $orders = $stmt->fetch(PDO::FETCH_ASSOC);
            $summary['orders_this_month'] = $orders['count'] ?? 0;
            $summary['revenue_this_month'] = $orders['total'] ?? 0;
            
            return $summary;
        } catch (Exception $e) {
            return ['error' => 'Failed to get summary: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get list of active flocks
     */
    public function getFlocks() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, batch_no, type, breed, quantity, start_date, status
                FROM poultry_batches 
                WHERE user_id = ? AND status = 'active'
                ORDER BY created_at DESC
            ");
            $stmt->execute([$this->userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get list of animals
     */
    public function getAnimals($type = null) {
        try {
            $sql = "SELECT id, tag_id, name, type, breed, gender, status FROM animals WHERE user_id = ? AND status = 'active'";
            $params = [$this->userId];
            
            if ($type) {
                $sql .= " AND type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get recent orders
     */
    public function getRecentOrders($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT o.id, o.order_no, o.total_amount, o.payment_method, o.status, o.created_at,
                       c.name as customer_name
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE o.user_id = ?
                ORDER BY o.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$this->userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // UPDATE ACTIONS
    // ═══════════════════════════════════════════════════════════════
    
    /**
     * Update animal status
     */
    public function updateAnimalStatus($animalId, $status, $notes = '') {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE animals SET status = ?, notes = CONCAT(notes, ' ', ?) WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$status, $notes, $animalId, $this->userId]);
            
            return [
                'success' => true,
                'message' => "Updated animal $animalId status to $status",
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to update animal: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update flock status
     */
    public function updateFlockStatus($batchId, $status, $notes = '') {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE poultry_batches SET status = ?, notes = CONCAT(notes, ' ', ?) WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$status, $notes, $batchId, $this->userId]);
            
            return [
                'success' => true,
                'message' => "Updated flock $batchId status to $status",
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to update flock: ' . $e->getMessage()];
        }
    }
}
