<?php
/**
 * Inventory Alerts System for Wangari Farm
 * 
 * Features:
 * - Low stock alerts
 * - Reorder point tracking
 * - Automated notifications
 * - Stock valuation
 * - Supplier management
 * - Purchase order generation
 */

class InventoryAlerts {
    private $db;
    
    // Alert types
    const ALERT_CRITICAL = 'critical';   // Stock below minimum
    const ALERT_WARNING = 'warning';     // Stock below reorder point
    const ALERT_INFO = 'info';           // Stock below maximum (time to order)
    const ALERT_EXPIRY = 'expiry';       // Product expiring soon
    
    // Default inventory items with reorder points
    private $defaultInventory = [
        'feeds' => [
            'broiler_starter' => [
                'name' => 'Broiler Starter Feed',
                'unit' => 'kg',
                'min_stock' => 50,      // Critical: below this = urgent reorder
                'reorder_point' => 100,  // Warning: below this = need to reorder
                'max_stock' => 500,      // Info: below this = good time to order
                'current_stock' => 150,
                'unit_cost' => 70,       // per kg
                'supplier' => 'Kenfeed Ltd',
                'lead_time_days' => 3
            ],
            'broiler_grower' => [
                'name' => 'Broiler Grower Feed',
                'unit' => 'kg',
                'min_stock' => 50,
                'reorder_point' => 100,
                'max_stock' => 500,
                'current_stock' => 200,
                'unit_cost' => 65,
                'supplier' => 'Kenfeed Ltd',
                'lead_time_days' => 3
            ],
            'broiler_finisher' => [
                'name' => 'Broiler Finisher Feed',
                'unit' => 'kg',
                'min_stock' => 50,
                'reorder_point' => 100,
                'max_stock' => 500,
                'current_stock' => 180,
                'unit_cost' => 70,
                'supplier' => 'Kenfeed Ltd',
                'lead_time_days' => 3
            ],
            'layer_mash' => [
                'name' => 'Layer Mash',
                'unit' => 'kg',
                'min_stock' => 100,
                'reorder_point' => 200,
                'max_stock' => 1000,
                'current_stock' => 300,
                'unit_cost' => 75,
                'supplier' => 'Kenfeed Ltd',
                'lead_time_days' => 3
            ],
            'dairy_meal' => [
                'name' => 'Dairy Meal',
                'unit' => 'kg',
                'min_stock' => 50,
                'reorder_point' => 100,
                'max_stock' => 500,
                'current_stock' => 120,
                'unit_cost' => 75,
                'supplier' => 'Unga Feeds',
                'lead_time_days' => 2
            ],
            'maize_bran' => [
                'name' => 'Maize Bran',
                'unit' => 'kg',
                'min_stock' => 100,
                'reorder_point' => 200,
                'max_stock' => 1000,
                'current_stock' => 250,
                'unit_cost' => 35,
                'supplier' => 'Local Mill',
                'lead_time_days' => 1
            ]
        ],
        'medicines' => [
            'vaccines_newcastle' => [
                'name' => 'Newcastle Disease Vaccine',
                'unit' => 'doses',
                'min_stock' => 100,
                'reorder_point' => 200,
                'max_stock' => 1000,
                'current_stock' => 150,
                'unit_cost' => 2,
                'supplier' => 'Kenya Vet Lab',
                'lead_time_days' => 5
            ],
            'vaccines_gumboro' => [
                'name' => 'Gumboro Disease Vaccine',
                'unit' => 'doses',
                'min_stock' => 100,
                'reorder_point' => 200,
                'max_stock' => 1000,
                'current_stock' => 180,
                'unit_cost' => 3,
                'supplier' => 'Kenya Vet Lab',
                'lead_time_days' => 5
            ],
            'dewormer' => [
                'name' => 'Albendazole Dewormer',
                'unit' => 'tablets',
                'min_stock' => 50,
                'reorder_point' => 100,
                'max_stock' => 500,
                'current_stock' => 75,
                'unit_cost' => 15,
                'supplier' => 'Veterinary Supplies',
                'lead_time_days' => 2
            ],
            'antibiotics' => [
                'name' => 'Oxytetracycline',
                'unit' => 'capsules',
                'min_stock' => 30,
                'reorder_point' => 60,
                'max_stock' => 200,
                'current_stock' => 45,
                'unit_cost' => 25,
                'supplier' => 'Veterinary Supplies',
                'lead_time_days' => 2
            ]
        ],
        'bedding' => [
            'wood_shavings' => [
                'name' => 'Wood Shavings',
                'unit' => 'bags',
                'min_stock' => 10,
                'reorder_point' => 20,
                'max_stock' => 50,
                'current_stock' => 15,
                'unit_cost' => 200,
                'supplier' => 'Sawmill',
                'lead_time_days' => 1
            ]
        ],
        'equipment' => [
            'feed_troughs' => [
                'name' => 'Feed Troughs',
                'unit' => 'pieces',
                'min_stock' => 5,
                'reorder_point' => 10,
                'max_stock' => 30,
                'current_stock' => 12,
                'unit_cost' => 500,
                'supplier' => 'Hardware Store',
                'lead_time_days' => 7
            ],
            'drinkers' => [
                'name' => 'Drinking Cups',
                'unit' => 'pieces',
                'min_stock' => 20,
                'reorder_point' => 40,
                'max_stock' => 100,
                'current_stock' => 35,
                'unit_cost' => 100,
                'supplier' => 'Hardware Store',
                'lead_time_days' => 7
            ]
        ]
    ];
    
    // Supplier database
    private $suppliers = [
        [
            'id' => 1,
            'name' => 'Kenfeed Ltd',
            'contact' => '+254 722 123456',
            'email' => 'sales@kenfeed.co.ke',
            'location' => 'Nairobi',
            'products' => ['feeds'],
            'rating' => 4.5
        ],
        [
            'id' => 2,
            'name' => 'Unga Feeds',
            'contact' => '+254 733 654321',
            'email' => 'orders@unga.co.ke',
            'location' => 'Nairobi',
            'products' => ['feeds'],
            'rating' => 4.2
        ],
        [
            'id' => 3,
            'name' => 'Kenya Vet Laboratory',
            'contact' => '+254 700 111222',
            'email' => 'vaccines@kenyavet.go.ke',
            'location' => 'Kabete',
            'products' => ['medicines'],
            'rating' => 4.8
        ],
        [
            'id' => 4,
            'name' => 'Veterinary Supplies Kenya',
            'contact' => '+254 711 333444',
            'email' => 'info@vetshare.co.ke',
            'location' => 'Nairobi',
            'products' => ['medicines'],
            'rating' => 4.0
        ],
        [
            'id' => 5,
            'name' => 'Local Sawmill',
            'contact' => '+254 720 555666',
            'email' => 'sawmill@local.co.ke',
            'location' => 'Thika',
            'products' => ['bedding'],
            'rating' => 4.3
        ],
        [
            'id' => 6,
            'name' => 'Farm Hardware Supplies',
            'contact' => '+254 734 777888',
            'email' => 'sales@farmhardware.co.ke',
            'location' => 'Nakuru',
            'products' => ['equipment'],
            'rating' => 4.1
        ]
    ];
    
    public function __construct() {
        try {
            require_once __DIR__ . '/database.php';
            $this->db = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            $this->db = null;
        }
    }
    
    /**
     * Get all inventory alerts
     */
    public function getAlerts() {
        $alerts = [];
        
        foreach ($this->defaultInventory as $category => $items) {
            foreach ($items as $key => $item) {
                $stock = $item['current_stock'];
                $min = $item['min_stock'];
                $reorder = $item['reorder_point'];
                $max = $item['max_stock'];
                
                if ($stock <= $min) {
                    $alerts[] = [
                        'type' => self::ALERT_CRITICAL,
                        'category' => $category,
                        'item_key' => $key,
                        'name' => $item['name'],
                        'current_stock' => $stock,
                        'min_stock' => $min,
                        'unit' => $item['unit'],
                        'message' => "CRITICAL: {$item['name']} is critically low! Only {$stock} {$item['unit']} remaining.",
                        'action' => "Order immediately from {$item['supplier']}",
                        'urgency' => 'high'
                    ];
                } elseif ($stock <= $reorder) {
                    $alerts[] = [
                        'type' => self::ALERT_WARNING,
                        'category' => $category,
                        'item_key' => $key,
                        'name' => $item['name'],
                        'current_stock' => $stock,
                        'reorder_point' => $reorder,
                        'unit' => $item['unit'],
                        'message' => "WARNING: {$item['name']} needs reordering. {$stock} {$item['unit']} remaining.",
                        'action' => "Plan reorder from {$item['supplier']} ({$item['lead_time_days']} days lead time)",
                        'urgency' => 'medium'
                    ];
                } elseif ($stock <= $max * 0.5) {
                    $alerts[] = [
                        'type' => self::ALERT_INFO,
                        'category' => $category,
                        'item_key' => $key,
                        'name' => $item['name'],
                        'current_stock' => $stock,
                        'unit' => $item['unit'],
                        'message' => "INFO: {$item['name']} is at 50% capacity. Consider ordering soon.",
                        'action' => "Monitor stock levels",
                        'urgency' => 'low'
                    ];
                }
            }
        }
        
        // Sort by urgency
        $urgencyOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
        usort($alerts, function($a, $b) use ($urgencyOrder) {
            return $urgencyOrder[$a['urgency']] - $urgencyOrder[$b['urgency']];
        });
        
        return $alerts;
    }
    
    /**
     * Get inventory summary
     */
    public function getInventorySummary() {
        $summary = [
            'total_items' => 0,
            'total_value' => 0,
            'critical' => 0,
            'warning' => 0,
            'good' => 0,
            'categories' => []
        ];
        
        foreach ($this->defaultInventory as $category => $items) {
            $catSummary = [
                'count' => 0,
                'value' => 0,
                'critical' => 0,
                'warning' => 0
            ];
            
            foreach ($items as $item) {
                $summary['total_items']++;
                $itemValue = $item['current_stock'] * $item['unit_cost'];
                $summary['total_value'] += $itemValue;
                $catSummary['value'] += $itemValue;
                $catSummary['count']++;
                
                if ($item['current_stock'] <= $item['min_stock']) {
                    $summary['critical']++;
                    $catSummary['critical']++;
                } elseif ($item['current_stock'] <= $item['reorder_point']) {
                    $summary['warning']++;
                    $catSummary['warning']++;
                } else {
                    $summary['good']++;
                }
            }
            
            $summary['categories'][$category] = $catSummary;
        }
        
        return $summary;
    }
    
    /**
     * Get reorder suggestions
     */
    public function getReorderSuggestions() {
        $suggestions = [];
        
        foreach ($this->defaultInventory as $category => $items) {
            foreach ($items as $key => $item) {
                if ($item['current_stock'] <= $item['reorder_point']) {
                    $orderQty = $item['max_stock'] - $item['current_stock'];
                    $orderCost = $orderQty * $item['unit_cost'];
                    
                    $suggestions[] = [
                        'item_key' => $key,
                        'name' => $item['name'],
                        'current_stock' => $item['current_stock'],
                        'reorder_point' => $item['reorder_point'],
                        'suggested_order' => $orderQty,
                        'unit' => $item['unit'],
                        'unit_cost' => $item['unit_cost'],
                        'total_cost' => $orderCost,
                        'supplier' => $item['supplier'],
                        'lead_time_days' => $item['lead_time_days'],
                        'urgency' => $item['current_stock'] <= $item['min_stock'] ? 'critical' : 'warning'
                    ];
                }
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Generate purchase order
     */
    public function generatePurchaseOrder($items) {
        $po = [
            'po_number' => 'PO-' . date('Ymd') . '-' . rand(1000, 9999),
            'date' => date('Y-m-d'),
            'items' => [],
            'total' => 0,
            'status' => 'draft'
        ];
        
        foreach ($items as $item) {
            $inventoryItem = $this->findItem($item['item_key']);
            if ($inventoryItem) {
                $lineTotal = $item['quantity'] * $inventoryItem['unit_cost'];
                $po['items'][] = [
                    'name' => $inventoryItem['name'],
                    'quantity' => $item['quantity'],
                    'unit' => $inventoryItem['unit'],
                    'unit_cost' => $inventoryItem['unit_cost'],
                    'total' => $lineTotal
                ];
                $po['total'] += $lineTotal;
            }
        }
        
        return $po;
    }
    
    /**
     * Get suppliers
     */
    public function getSuppliers($category = null) {
        if ($category) {
            return array_filter($this->suppliers, function($s) use ($category) {
                return in_array($category, $s['products']);
            });
        }
        return $this->suppliers;
    }
    
    /**
     * Get stock history (simulated)
     */
    public function getStockHistory($itemKey, $days = 30) {
        $history = [];
        $item = $this->findItem($itemKey);
        
        if (!$item) return [];
        
        $currentStock = $item['current_stock'];
        
        for ($i = $days; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            // Simulate stock changes
            $usage = rand(2, 8);
            $stock = max(0, $currentStock + ($usage * ($i - $days/2)));
            
            $history[] = [
                'date' => $date,
                'stock' => round($stock),
                'usage' => $usage
            ];
        }
        
        return $history;
    }
    
    /**
     * Calculate days until stockout
     */
    public function calculateDaysUntilStockout($itemKey, $dailyUsage = null) {
        $item = $this->findItem($itemKey);
        
        if (!$item) return null;
        
        // Estimate daily usage based on category
        if (!$dailyUsage) {
            switch($item['unit']) {
                case 'kg': $dailyUsage = 10; break;
                case 'doses': $dailyUsage = 5; break;
                case 'tablets': $dailyUsage = 2; break;
                case 'capsules': $dailyUsage = 1; break;
                case 'bags': $dailyUsage = 0.5; break;
                default: $dailyUsage = 1; break;
            }
        }
        
        $daysUntilStockout = floor($item['current_stock'] / $dailyUsage);
        
        return [
            'days' => $daysUntilStockout,
            'current_stock' => $item['current_stock'],
            'daily_usage' => $dailyUsage,
            'stockout_date' => date('Y-m-d', strtotime("+{$daysUntilStockout} days")),
            'urgency' => $daysUntilStockout <= 3 ? 'critical' : ($daysUntilStockout <= 7 ? 'warning' : 'ok')
        ];
    }
    
    /**
     * Update stock level
     */
    public function updateStock($itemKey, $newStock, $reason = 'manual') {
        $item = $this->findItem($itemKey);
        
        if (!$item) {
            return ['error' => 'Item not found'];
        }
        
        $oldStock = $item['current_stock'];
        $item['current_stock'] = $newStock;
        
        // Log the change
        $log = [
            'item_key' => $itemKey,
            'old_stock' => $oldStock,
            'new_stock' => $newStock,
            'change' => $newStock - $oldStock,
            'reason' => $reason,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Check if alert needed
        $alert = null;
        if ($newStock <= $item['min_stock']) {
            $alert = self::ALERT_CRITICAL;
        } elseif ($newStock <= $item['reorder_point']) {
            $alert = self::ALERT_WARNING;
        }
        
        return [
            'success' => true,
            'log' => $log,
            'alert' => $alert
        ];
    }
    
    private function findItem($itemKey) {
        foreach ($this->defaultInventory as $category => $items) {
            if (isset($items[$itemKey])) {
                return $items[$itemKey];
            }
        }
        return null;
    }
}

// API endpoint handler
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'alerts';
    
    $inventory = new InventoryAlerts();
    
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'alerts':
            echo json_encode($inventory->getAlerts());
            break;
        case 'summary':
            echo json_encode($inventory->getInventorySummary());
            break;
        case 'reorder':
            echo json_encode($inventory->getReorderSuggestions());
            break;
        case 'suppliers':
            $category = $_GET['category'] ?? null;
            echo json_encode($inventory->getSuppliers($category));
            break;
        case 'history':
            $itemKey = $_GET['item_key'] ?? null;
            $days = $_GET['days'] ?? 30;
            echo json_encode($inventory->getStockHistory($itemKey, $days));
            break;
        case 'stockout':
            $itemKey = $_GET['item_key'] ?? null;
            echo json_encode($inventory->calculateDaysUntilStockout($itemKey));
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}
