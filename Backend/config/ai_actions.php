<?php
/**
 * Wangari AI Actions - CRUD Operations
 * 
 * These functions allow the AI to create, read, update, and delete
 * farm data when instructed by the user.
 * 
 * Updated to match actual database schema.
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
        $flockName = $data['name'] ?? 'Flock-' . date('Ymd') . '-' . rand(100, 999);
        $breed = $data['breed'] ?? 'Broiler';
        $quantity = (int)($data['quantity'] ?? 0);
        $hatchDate = $data['hatch_date'] ?? date('Y-m-d');
        
        if ($quantity <= 0) {
            return ['success' => false, 'error' => 'Quantity must be greater than 0'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO flocks (flock_name, breed, initial_count, current_count, hatch_date, status)
                VALUES (?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([$flockName, $breed, $quantity, $quantity, $hatchDate]);
            
            $flockId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'message' => "Created flock '$flockName' with $quantity birds",
                'flock_id' => $flockId,
                'flock_name' => $flockName,
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
        $notes = $data['notes'] ?? '';
        
        if ($batchId <= 0) {
            // Try to find the first active flock
            $stmt = $this->pdo->prepare("SELECT id FROM flocks WHERE status = 'active' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute();
            $flock = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($flock) {
                $batchId = $flock['id'];
            } else {
                return ['success' => false, 'error' => 'No active flocks found. Create a flock first.'];
            }
        }
        
        try {
            // Get opening birds count
            $stmt = $this->pdo->prepare("SELECT current_count FROM flocks WHERE id = ?");
            $stmt->execute([$batchId]);
            $flock = $stmt->fetch(PDO::FETCH_ASSOC);
            $openingBirds = $flock['current_count'] ?? 0;
            $closingBirds = $openingBirds - $mortality;
            
            // Calculate production percentage (for layers)
            $productionPct = 0;
            if ($openingBirds > 0) {
                $productionPct = round($eggs / $openingBirds * 100, 4);
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO daily_batch_records (batch_id, record_date, opening_birds, mortality, closing_birds, total_eggs, production_pct, notes, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    mortality = mortality + VALUES(mortality),
                    closing_birds = VALUES(closing_birds),
                    total_eggs = VALUES(total_eggs),
                    production_pct = VALUES(production_pct),
                    notes = VALUES(notes)
            ");
            $stmt->execute([$batchId, $date, $openingBirds, $mortality, $closingBirds, $eggs, $productionPct, $notes, $this->userId]);
            
            // Update flock current count
            if ($mortality > 0) {
                $stmt = $this->pdo->prepare("UPDATE flocks SET current_count = current_count - ? WHERE id = ?");
                $stmt->execute([$mortality, $batchId]);
            }
            
            return [
                'success' => true,
                'message' => "Recorded production for flock $batchId on $date",
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
        $tag = $data['tag_id'] ?? 'TAG-' . rand(1000, 9999);
        $name = $data['name'] ?? '';
        $type = $data['type'] ?? 'Cattle';
        $breed = $data['breed'] ?? '';
        $gender = $data['gender'] ?? 'female';
        $dob = $data['date_of_birth'] ?? null;
        $notes = $data['notes'] ?? '';
        
        // Capitalize type
        $type = ucfirst(strtolower($type));
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO animals (tag, name, type, breed, gender, birth_date, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, 'Active', ?)
            ");
            $stmt->execute([$tag, $name, $type, $breed, $gender, $dob, $notes]);
            
            $animalId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'message' => "Added $type '$name' with tag $tag",
                'animal_id' => $animalId,
                'tag' => $tag,
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
        $liters = (float)($data['liters'] ?? 0);
        $time = $data['time'] ?? 'morning';
        $notes = $data['notes'] ?? '';
        
        if ($animalId <= 0) {
            // Try to find first active cow
            $stmt = $this->pdo->prepare("SELECT id FROM animals WHERE type = 'Cattle' AND status = 'Active' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute();
            $animal = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($animal) {
                $animalId = $animal['id'];
            } else {
                return ['success' => false, 'error' => 'No cattle found. Add a cow first.'];
            }
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO milking_records (animal_id, species, milking_date, milking_time, litres, notes, recorded_by)
                VALUES (?, 'Cattle', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$animalId, $date, $time, $liters, $notes, $this->userId]);
            
            return [
                'success' => true,
                'message' => "Recorded $liters liters of milk on $date",
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
        $location = $data['location'] ?? '';
        $sizeAcres = (float)($data['size_acres'] ?? 0);
        $soilType = $data['soil_type'] ?? '';
        $notes = $data['notes'] ?? '';
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO fields (name, location, size_acres, soil_type, status, notes)
                VALUES (?, ?, ?, ?, 'active', ?)
            ");
            $stmt->execute([$name, $location, $sizeAcres, $soilType, $notes]);
            
            $fieldId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'message' => "Added field '$name' ($sizeAcres acres)",
                'field_id' => $fieldId,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to add field: ' . $e->getMessage()];
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // SALES & FINANCE ACTIONS
    // ═══════════════════════════════════════════════════════════════
    
    /**
     * Create a new order/sale
     */
    public function createOrder($data) {
        $amount = (float)($data['amount'] ?? 0);
        $paymentMethod = $data['payment_method'] ?? 'mpesa';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? 'Walk-in';
        $notes = $data['notes'] ?? '';
        
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Amount must be greater than 0'];
        }
        
        $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(100, 999);
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (user_id, order_number, status, total_amount, payment_method, phone_contact, shipping_address, customer_notes, selling_point)
                VALUES (?, ?, 'completed', ?, ?, ?, ?, ?, 'walk_in')
            ");
            $stmt->execute([$this->userId, $orderNumber, $amount, $paymentMethod, $phone, $address, $notes]);
            
            $orderId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'message' => "Created order $orderNumber for KES $amount",
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total' => $amount,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to create order: ' . $e->getMessage()];
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
            // Count flocks
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count, SUM(current_count) as total_birds FROM flocks WHERE status = 'active'");
            $stmt->execute();
            $flocks = $stmt->fetch(PDO::FETCH_ASSOC);
            $summary['flock_count'] = $flocks['count'] ?? 0;
            $summary['total_birds'] = $flocks['total_birds'] ?? 0;
            
            // Count animals
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM animals WHERE status = 'Active'");
            $stmt->execute();
            $animals = $stmt->fetch(PDO::FETCH_ASSOC);
            $summary['total_animals'] = $animals['count'] ?? 0;
            
            // Count fields
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM fields WHERE status = 'active'");
            $stmt->execute();
            $fields = $stmt->fetch(PDO::FETCH_ASSOC);
            $summary['total_fields'] = $fields['count'] ?? 0;
            
            // Recent orders
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute();
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
                SELECT id, flock_name, breed, initial_count, current_count, hatch_date, status
                FROM flocks 
                WHERE status = 'active'
                ORDER BY created_at DESC
            ");
            $stmt->execute();
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
            $sql = "SELECT id, tag, name, type, breed, gender, status FROM animals WHERE status = 'Active'";
            $params = [];
            
            if ($type) {
                $sql .= " AND type = ?";
                $params[] = ucfirst(strtolower($type));
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
                SELECT id, order_number, total_amount, payment_method, status, created_at
                FROM orders
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
