<?php
/**
 * Backend API for Stock Management Module
 * Handles full CRUD operations and intelligent production logic.
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();

// Admin access check
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager', 'stock_manager'], true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../Frontend/includes/config.php';

$action = $_GET['action'] ?? '';
$action = $_POST['action'] ?? $action;
$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();
$mutatingActions = ['save_raw_material', 'record_production', 'resolve_alert', 'save_recipe', 'delete_recipe', 'sync_prices', 'save_recurring_schedule', 'delete_recurring_schedule'];

if (in_array($action, $mutatingActions, true)) {
    if ($method !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid method']);
        exit;
    }

    $csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!verifyCSRFToken($csrfToken)) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }
}

try {
    switch ($action) {
        case 'get_dashboard':
            // Fetch everything needed for the UI
            $raw_materials = $pdo->query("SELECT *, (stock_tons * current_price_per_ton) as total_value FROM raw_materials ORDER BY name ASC")->fetchAll();
            $finished_products = $pdo->query("SELECT id, name, price, stock_quantity FROM products WHERE product_type = 'feed' AND is_active = 1 ORDER BY name ASC")->fetchAll();
            $recipes = $pdo->query("SELECT r.*, p.price as selling_price, p.stock_quantity as current_product_stock, (SELECT COUNT(*) FROM recipe_ingredients WHERE recipe_id = r.id) as ingredient_count FROM feed_recipes r JOIN products p ON r.product_id = p.id WHERE r.is_active = 1")->fetchAll();

            // Calculate average daily consumption per raw material over last 30 days from production_history
            $dailyConsumption = [];
            $consumptionStmt = $pdo->query("
                SELECT rm.id as rm_id, rm.name,
                    COALESCE(SUM( (ri.amount_kg / fr.base_bag_size_kg) * ph.bag_size_kg * ph.quantity_bags ), 0) as total_kg_used
                FROM raw_materials rm
                LEFT JOIN recipe_ingredients ri ON ri.raw_material_id = rm.id
                LEFT JOIN feed_recipes fr ON ri.recipe_id = fr.id
                LEFT JOIN production_history ph ON ph.recipe_id = fr.id AND ph.produced_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY rm.id, rm.name
            ");
            foreach ($consumptionStmt->fetchAll() as $row) {
                $dailyConsumption[$row['rm_id']] = [
                    'total_kg_30d' => (float)$row['total_kg_used'],
                    'avg_kg_per_day' => round((float)$row['total_kg_used'] / 30, 3)
                ];
            }

            // Enhance raw_materials with days_covered
            foreach ($raw_materials as &$rm) {
                $avgDaily = $dailyConsumption[$rm['id']]['avg_kg_per_day'] ?? 0;
                $stockKg = (float)$rm['stock_tons'];
                $rm['avg_daily_consumption_kg'] = $avgDaily;
                $rm['days_covered'] = $avgDaily > 0 ? (int)floor($stockKg / $avgDaily) : 999;
                $rm['is_below_reorder'] = (float)$rm['stock_tons'] <= (float)$rm['min_stock_level'];
            }
            unset($rm);

            foreach ($recipes as &$r) {
                $stmt = $pdo->prepare("SELECT ri.amount_kg, rm.name, rm.stock_tons, rm.current_price_per_ton FROM recipe_ingredients ri JOIN raw_materials rm ON ri.raw_material_id = rm.id WHERE ri.recipe_id = ?");
                $stmt->execute([$r['id']]);
                $ingredients = $stmt->fetchAll();
                
                $cogs = 0;
                $material_capacities = [];
                
                foreach ($ingredients as $ing) {
                    $cogs += $ing['amount_kg'] * $ing['current_price_per_ton'];
                    
                    // Calculate individual material capacity
                    $can_make = (int)floor($ing['stock_tons'] / $ing['amount_kg']);
                    $material_capacities[] = [
                        'name' => $ing['name'],
                        'capacity' => $can_make,
                        'needed_per_bag' => $ing['amount_kg'],
                        'stock_tons' => $ing['stock_tons']
                    ];
                }
                
                // Sort capacities to find primary and secondary bottlenecks
                usort($material_capacities, function($a, $b) { return $a['capacity'] <=> $b['capacity']; });
                
                $primary = $material_capacities[0] ?? null;
                $secondary = $material_capacities[1] ?? null;
                
                $max_bags = $primary ? $primary['capacity'] : 0;
                $bottleneck = $primary ? $primary['name'] : 'N/A';
                
                $r['estimated_cogs'] = round($cogs, 2);
                $r['auto_capacity_bags'] = $max_bags;
                $r['bottleneck_material'] = $bottleneck;
                
                // --- SUPER INTELLIGENT TIPS LOGIC ---
                if ($max_bags == 0) {
                    if ($secondary && $secondary['capacity'] > 0) {
                        $needed_kg = round($secondary['capacity'] * $primary['needed_per_bag'], 2);
                        $r['tip'] = "Production halted. Buy <strong>{$needed_kg} kgs</strong> of <strong>{$bottleneck}</strong> to unlock production of <strong>{$secondary['capacity']} bags</strong> (limited next by {$secondary['name']}).";
                    } else {
                        $r['tip'] = "Production stalled. Total stock depletion of <strong>{$bottleneck}</strong> detected. Urgent restock required.";
                    }
                } elseif ($max_bags < 10) {
                    if ($secondary && $secondary['capacity'] > $max_bags) {
                        $gap = $secondary['capacity'] - $max_bags;
                        $needed_kg = round($gap * $primary['needed_per_bag'], 2);
                        $r['tip'] = "Critical: <strong>{$bottleneck}</strong> is a severe bottleneck. Adding <strong>{$needed_kg} kgs</strong> would boost production to <strong>{$secondary['capacity']} bags</strong>.";
                    } else {
                        $r['tip'] = "Critical: Extremely low stock of <strong>{$bottleneck}</strong> limiting you to just {$max_bags} bags.";
                    }
                } elseif ($secondary && ($secondary['capacity'] - $max_bags) > 50) {
                    $gap = $secondary['capacity'] - $max_bags;
                    $potential = $secondary['capacity'];
                    $r['tip'] = "Optimization Opportunity: You have a large surplus of other materials. Adding more <strong>{$bottleneck}</strong> could increase yield from {$max_bags} to <strong>{$potential} bags</strong>.";
                } else {
                    $r['tip'] = "Balanced Production: <strong>{$bottleneck}</strong> is your current limiting factor, but your overall material levels are well-synchronized.";
                }
            }
            unset($r);

            $raw_value = array_sum(array_column($raw_materials, 'total_value'));
            $finished_value = array_sum(array_map(function($p) { return $p['price'] * $p['stock_quantity']; }, $finished_products));
            $alerts = $pdo->query("SELECT * FROM stock_alerts WHERE is_resolved = 0 ORDER BY created_at DESC")->fetchAll();

            echo json_encode([
                'success' => true,
                'data' => [
                    'raw_materials' => $raw_materials,
                    'finished_products' => $finished_products,
                    'recipes' => $recipes,
                    'summary' => [
                        'raw_value' => round($raw_value, 2),
                        'finished_value' => round($finished_value, 2),
                        'total_value' => round($raw_value + $finished_value, 2)
                    ],
                    'alerts' => $alerts
                ]
            ]);
            break;

        case 'save_raw_material':
            if ($method !== 'POST') throw new Exception('Invalid method');
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $price = (float)($_POST['current_price_per_ton'] ?? 0);
            $stock = (float)($_POST['stock_tons'] ?? 0);
            $min_level = (float)($_POST['min_stock_level'] ?? 1.0);

            if ($id > 0) {
                // Check for price fluctuation alert
                $old = fetchOne($pdo, "SELECT current_price_per_ton FROM raw_materials WHERE id = ?", [$id]);
                if ($old && abs($old['current_price_per_ton'] - $price) > 0.01) {
                    $diff = $price - $old['current_price_per_ton'];
                    $type = $diff > 0 ? 'increased' : 'decreased';
                    execute($pdo, "INSERT INTO stock_alerts (alert_type, message, related_id) VALUES ('price_fluctuation', ?, ?)", 
                        ["Price of $name has $type by KES " . abs($diff) . " per kg. Review product margins.", $id]);
                }
                execute($pdo, "UPDATE raw_materials SET name = ?, current_price_per_ton = ?, stock_tons = ?, min_stock_level = ? WHERE id = ?", [$name, $price, $stock, $min_level, $id]);
            } else {
                execute($pdo, "INSERT INTO raw_materials (name, current_price_per_ton, stock_tons, min_stock_level) VALUES (?, ?, ?, ?)", [$name, $price, $stock, $min_level]);
            }
            echo json_encode(['success' => true, 'message' => 'Raw material saved']);
            break;

        case 'calculate':
            $recipe_id = (int)($_GET['recipe_id'] ?? 0);
            $bag_size = (float)($_GET['bag_size'] ?? 70.0);
            if (!$recipe_id) throw new Exception('Recipe ID required');

            $recipe = fetchOne($pdo, "SELECT * FROM feed_recipes WHERE id = ?", [$recipe_id]);
            $ingredients = fetchAll($pdo, "SELECT ri.amount_kg as base_amount, rm.name, rm.stock_tons, rm.current_price_per_ton FROM recipe_ingredients ri JOIN raw_materials rm ON ri.raw_material_id = rm.id WHERE ri.recipe_id = ?", [$recipe_id]);

            $calc_results = [];
            $max_bags = PHP_INT_MAX;
            $bottleneck = '';
            $total_cogs = 0;

            foreach ($ingredients as $ing) {
                $needed_per_bag = ($ing['base_amount'] / $recipe['base_bag_size_kg']) * $bag_size;
                $can_make = (int)floor(($ing['stock_tons'] * 1000) / $needed_per_bag);
                if ($can_make < $max_bags) { $max_bags = $can_make; $bottleneck = $ing['name']; }
                $total_cogs += ($needed_per_bag / 1000) * $ing['current_price_per_ton'];
                $calc_results[] = ['name' => $ing['name'], 'needed_kg_per_bag' => round($needed_per_bag, 3), 'can_make_bags' => $can_make, 'is_bottleneck' => false];
            }

            foreach ($calc_results as &$res) if ($res['name'] === $bottleneck) $res['is_bottleneck'] = true;

            echo json_encode(['success' => true, 'data' => [
                'max_bags' => $max_bags, 'bottleneck_material' => $bottleneck, 'ingredients' => $calc_results,
                'total_cogs_per_bag' => round($total_cogs, 2), 'total_batch_cost' => round($total_cogs * $max_bags, 2),
                'optimizer' => ['smaller_bag_size' => 15.0, 'suggested_bags' => $max_bags < 1000000 ? 0 : 0] // logic simplified for now
            ]]);
            break;

        case 'record_production':
            if ($method !== 'POST') throw new Exception('Invalid method');
            $recipe_id = (int)($_POST['recipe_id'] ?? 0);
            $bag_size = (float)($_POST['bag_size'] ?? 70.0);
            $quantity = (int)($_POST['quantity'] ?? 0);

            if (!$recipe_id || $quantity <= 0) throw new Exception('Valid recipe and quantity required');

            $pdo->beginTransaction();
            $recipe = fetchOne($pdo, "SELECT * FROM feed_recipes WHERE id = ?", [$recipe_id]);
            $ingredients = fetchAll($pdo, "SELECT ri.amount_kg as base_amount, rm.id as rm_id, rm.name, rm.stock_tons, rm.current_price_per_ton FROM recipe_ingredients ri JOIN raw_materials rm ON ri.raw_material_id = rm.id WHERE ri.recipe_id = ?", [$recipe_id]);

            $total_cost = 0;
            foreach ($ingredients as $ing) {
                $needed_total_kg = (($ing['base_amount'] / $recipe['base_bag_size_kg']) * $bag_size) * $quantity;
                if ($ing['stock_tons'] < $needed_total_kg) throw new Exception("Insufficient {$ing['name']} stock");
                
                // Calculate actual cost for this ingredient
                $total_cost += $needed_total_kg * $ing['current_price_per_ton'];
                
                execute($pdo, "UPDATE raw_materials SET stock_tons = stock_tons - ? WHERE id = ?", [$needed_total_kg, $ing['rm_id']]);
                
                // Check if now low stock
                $updated = fetchOne($pdo, "SELECT stock_tons, min_stock_level FROM raw_materials WHERE id = ?", [$ing['rm_id']]);
                if ($updated['stock_tons'] <= $updated['min_stock_level']) {
                    execute($pdo, "INSERT IGNORE INTO stock_alerts (alert_type, message, related_id) VALUES ('low_stock', ?, ?)", 
                        ["{$ing['name']} is running low! Current stock: {$updated['stock_tons']} kgs.", $ing['rm_id']]);
                }
            }

            // Generate batch number
            $batchNumber = 'BATCH-' . date('Ymd') . '-' . str_pad((string)rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Update finished product stock
            execute($pdo, "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?", [$quantity, $recipe['product_id']]);
            
            // Log history with actual cost and batch number
            $costPerBag = round($total_cost / $quantity, 2);
            execute($pdo, "INSERT INTO production_history (batch_number, recipe_id, bag_size_kg, quantity_bags, total_cost, notes) VALUES (?, ?, ?, ?, ?, ?)",
                [$batchNumber, $recipe_id, $bag_size, $quantity, round($total_cost, 2), "Cost/bag: KES " . number_format($costPerBag, 2)]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => "Batch $batchNumber: Produced $quantity bags. Total cost: KES " . number_format($total_cost, 2) . " (KES " . number_format($costPerBag, 2) . "/bag)"]);
            break;

        case 'resolve_alert':
            $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
            if ($id > 0) {
                execute($pdo, "UPDATE stock_alerts SET is_resolved = 1 WHERE id = ?", [$id]);
            } else {
                execute($pdo, "UPDATE stock_alerts SET is_resolved = 1 WHERE is_resolved = 0");
            }
            echo json_encode(['success' => true]);
            break;

        case 'save_recipe':
            if ($method !== 'POST') throw new Exception('Invalid method');
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $product_id = (int)($_POST['product_id'] ?? 0);
            $bag_size = (float)($_POST['base_bag_size_kg'] ?? 70.0);
            $ingredients = json_decode($_POST['ingredients'] ?? '[]', true);

            if (empty($name) || !$product_id) throw new Exception('Recipe name and product required');

            $pdo->beginTransaction();
            if ($id > 0) {
                execute($pdo, "UPDATE feed_recipes SET name = ?, product_id = ?, base_bag_size_kg = ? WHERE id = ?", [$name, $product_id, $bag_size, $id]);
                execute($pdo, "DELETE FROM recipe_ingredients WHERE recipe_id = ?", [$id]);
                $recipe_id = $id;
            } else {
                execute($pdo, "INSERT INTO feed_recipes (name, product_id, base_bag_size_kg) VALUES (?, ?, ?)", [$name, $product_id, $bag_size]);
                $recipe_id = (int)$pdo->lastInsertId();
            }

            foreach ($ingredients as $ing) {
                execute($pdo, "INSERT INTO recipe_ingredients (recipe_id, raw_material_id, amount_kg) VALUES (?, ?, ?)", 
                    [$recipe_id, $ing['raw_material_id'], $ing['amount_kg']]);
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Recipe saved successfully']);
            break;

        case 'delete_recipe':
            $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
            execute($pdo, "DELETE FROM feed_recipes WHERE id = ?", [$id]);
            echo json_encode(['success' => true]);
            break;

        case 'get_recipe_details':
            $id = (int)($_GET['id'] ?? 0);
            $recipe = fetchOne($pdo, "SELECT * FROM feed_recipes WHERE id = ?", [$id]);
            $ingredients = fetchAll($pdo, "SELECT * FROM recipe_ingredients WHERE recipe_id = ?", [$id]);
            echo json_encode(['success' => true, 'data' => ['recipe' => $recipe, 'ingredients' => $ingredients]]);
            break;

        case 'sync_prices':
            if ($method !== 'POST') throw new Exception('Invalid method');
            $recipes = fetchAll($pdo, "SELECT r.id, r.product_id FROM feed_recipes r");
            $updated_count = 0;
            
            foreach ($recipes as $r) {
                $stmt = $pdo->prepare("SELECT ri.amount_kg, rm.current_price_per_ton FROM recipe_ingredients ri JOIN raw_materials rm ON ri.raw_material_id = rm.id WHERE ri.recipe_id = ?");
                $stmt->execute([$r['id']]);
                $ingredients = $stmt->fetchAll();
                
                $cogs = 0;
                foreach ($ingredients as $ing) {
                    $cogs += $ing['amount_kg'] * $ing['current_price_per_ton'];
                }
                
                if ($cogs > 0) {
                    $suggested_price = ceil($cogs * 1.25 / 10) * 10;
                    execute($pdo, "UPDATE products SET price = ? WHERE id = ?", [$suggested_price, $r['product_id']]);
                    $updated_count++;
                }
            }
            echo json_encode(['success' => true, 'message' => "Successfully synced prices for $updated_count products based on current COGS + 25% margin."]);
            break;

        // ==================== PRODUCTION BATCH HISTORY ====================
        case 'get_production_history':
            $limit = (int)($_GET['limit'] ?? 50);
            $history = fetchAll($pdo, "
                SELECT ph.*, fr.name as recipe_name, fr.base_bag_size_kg,
                    p.name as product_name, p.price as current_selling_price
                FROM production_history ph
                JOIN feed_recipes fr ON ph.recipe_id = fr.id
                JOIN products p ON fr.product_id = p.id
                ORDER BY ph.produced_at DESC
                LIMIT ?
            ", [$limit]);

            // Compute cost_per_bag for each row
            foreach ($history as &$h) {
                $h['cost_per_bag'] = $h['quantity_bags'] > 0 ? round((float)$h['total_cost'] / (int)$h['quantity_bags'], 2) : 0;
                $h['profit_per_bag'] = round((float)$h['current_selling_price'] - $h['cost_per_bag'], 2);
            }
            unset($h);

            echo json_encode(['success' => true, 'data' => $history]);
            break;

        // ==================== CUSTOMER GROUPS & RECURRING SCHEDULES ====================
        case 'get_customer_groups':
            $customers = fetchAll($pdo, "
                SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.phone_number,
                    COUNT(o.id) as total_orders,
                    COALESCE(SUM(o.total_amount), 0) as lifetime_value,
                    MAX(o.created_at) as last_order_date
                FROM users u
                LEFT JOIN orders o ON o.user_id = u.id AND o.status != 'cancelled'
                WHERE u.role = 'customer'
                GROUP BY u.id
                HAVING total_orders > 0
                ORDER BY lifetime_value DESC
            ");

            // Get recurring schedules
            $schedules = fetchAll($pdo, "
                SELECT rfs.*, u.username, u.first_name, u.last_name, p.name as product_name
                FROM recurring_feed_schedules rfs
                JOIN users u ON rfs.user_id = u.id
                JOIN products p ON rfs.product_id = p.id
                ORDER BY rfs.next_delivery_date ASC
            ");

            echo json_encode(['success' => true, 'data' => ['customers' => $customers, 'schedules' => $schedules]]);
            break;

        case 'save_recurring_schedule':
            if ($method !== 'POST') throw new Exception('Invalid method');
            $sched_id = (int)($_POST['id'] ?? 0);
            $sched_user = (int)($_POST['user_id'] ?? 0);
            $sched_product = (int)($_POST['product_id'] ?? 0);
            $sched_qty = (int)($_POST['quantity'] ?? 1);
            $sched_freq = (int)($_POST['frequency_days'] ?? 7);
            $sched_next = trim($_POST['next_delivery_date'] ?? '');
            $sched_notes = trim($_POST['notes'] ?? '');

            if (!$sched_user || !$sched_product || !$sched_next) throw new Exception('User, product, and next delivery date are required');

            if ($sched_id > 0) {
                execute($pdo, "UPDATE recurring_feed_schedules SET user_id=?, product_id=?, quantity=?, frequency_days=?, next_delivery_date=?, notes=? WHERE id=?",
                    [$sched_user, $sched_product, $sched_qty, $sched_freq, $sched_next, $sched_notes, $sched_id]);
            } else {
                execute($pdo, "INSERT INTO recurring_feed_schedules (user_id, product_id, quantity, frequency_days, next_delivery_date, notes) VALUES (?,?,?,?,?,?)",
                    [$sched_user, $sched_product, $sched_qty, $sched_freq, $sched_next, $sched_notes]);
            }
            echo json_encode(['success' => true, 'message' => 'Schedule saved']);
            break;

        case 'delete_recurring_schedule':
            $sched_id = (int)($_POST['id'] ?? 0);
            execute($pdo, "DELETE FROM recurring_feed_schedules WHERE id = ?", [$sched_id]);
            echo json_encode(['success' => true]);
            break;

        // ==================== FORMULA PERFORMANCE ====================
        case 'get_formula_performance':
            $performance = fetchAll($pdo, "
                SELECT fr.id, fr.name as formula_name, p.name as product_name, p.price as selling_price,
                    COALESCE(SUM(ph.quantity_bags), 0) as total_produced,
                    COALESCE(SUM(ph.total_cost), 0) as total_production_cost,
                    COALESCE(sold.total_sold, 0) as total_sold,
                    COALESCE(sold.total_revenue, 0) as total_revenue
                FROM feed_recipes fr
                JOIN products p ON fr.product_id = p.id
                LEFT JOIN production_history ph ON ph.recipe_id = fr.id
                LEFT JOIN (
                    SELECT oi.product_id, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price_at_purchase) as total_revenue
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id AND o.status NOT IN ('cancelled', 'pending')
                    GROUP BY oi.product_id
                ) sold ON sold.product_id = p.id
                GROUP BY fr.id, fr.name, p.name, p.price, sold.total_sold, sold.total_revenue
            ");

            foreach ($performance as &$perf) {
                $perf['avg_cost_per_bag'] = $perf['total_produced'] > 0 ? round((float)$perf['total_production_cost'] / (int)$perf['total_produced'], 2) : 0;
                $perf['profit_margin_pct'] = (float)$perf['selling_price'] > 0 && $perf['avg_cost_per_bag'] > 0
                    ? round((((float)$perf['selling_price'] - $perf['avg_cost_per_bag']) / (float)$perf['selling_price']) * 100, 1)
                    : 0;
                $perf['gross_profit'] = round((float)$perf['total_revenue'] - (float)$perf['total_production_cost'], 2);
            }
            unset($perf);

            echo json_encode(['success' => true, 'data' => $performance]);
            break;

        default: throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
