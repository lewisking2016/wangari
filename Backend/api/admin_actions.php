<?php
/**
 * Centralized Admin Actions API
 * Handles report exports and other utility functions.
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();

// Admin access check
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager'], true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../Frontend/includes/config.php';
$pdo = getDB();

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'clear_cache':
            // Clear OPcache/APCu and temp cache files (best-effort)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid method');
            try {
                if (function_exists('apcu_clear_cache')) @apcu_clear_cache();
                if (function_exists('opcache_reset')) @opcache_reset();
                $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wangari_cache';
                if (is_dir($tmp)) {
                    $files = glob($tmp . DIRECTORY_SEPARATOR . '*');
                    foreach ($files as $f) { if (is_file($f)) @unlink($f); }
                }
            } catch (Exception $e) {
                // ignore
            }
            logActivity($pdo, 'clear', 'system', 'Cleared application cache');
            echo json_encode(['success' => true, 'message' => 'Cache cleared']);
            break;

        case 'prepare_delete':
            // Prepare a full-data CSV backup and return a download URL + token + confirmation word
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid method');
            $token = bin2hex(random_bytes(8));
            $word = substr(bin2hex(random_bytes(4)), 0, 8);
            $tmpDir = sys_get_temp_dir();
            $fname = "wangari_full_backup_" . date('Ymd_His') . "_" . $token . ".csv";
            $path = $tmpDir . DIRECTORY_SEPARATOR . $fname;
            $out = fopen($path, 'w');
            if (!$out) throw new Exception('Failed to create backup file');

            // Column headers: table, row_json
            fputcsv($out, ['table','row_json']);
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
            foreach ($tables as $t) {
                $table = $t[0];
                try {
                    $stmt = $pdo->query("SELECT * FROM `{$table}`");
                    if ($stmt) {
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            fputcsv($out, [$table, json_encode($row, JSON_UNESCAPED_UNICODE)]);
                        }
                    }
                } catch (Exception $e) {
                    // skip problematic tables
                }
            }
            fclose($out);

            // Save token+file in session for later confirmation
            $_SESSION['pending_delete'] = ['token' => $token, 'word' => $word, 'file' => $path, 'filename' => $fname];

            $downloadUrl = dirname($_SERVER['PHP_SELF']) . '/admin_actions.php?action=download_backup&file=' . urlencode($fname) . '&token=' . $token;
            echo json_encode(['success' => true, 'token' => $token, 'word' => $word, 'download' => $downloadUrl]);
            break;

        case 'download_backup':
            $file = $_GET['file'] ?? '';
            $token = $_GET['token'] ?? '';
            if (!$file || !$token) throw new Exception('File and token required');
            if (empty($_SESSION['pending_delete']) || $_SESSION['pending_delete']['token'] !== $token) throw new Exception('Invalid or expired token');
            $path = $_SESSION['pending_delete']['file'] ?? '';
            if (!is_file($path) || basename($path) !== $file) throw new Exception('File not found');
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . basename($path) . '"');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            exit;
            break;

        case 'confirm_delete':
        case 'delete_everything':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid method');
            $token = $_POST['token'] ?? '';
            $typed = trim($_POST['typed_word'] ?? '');
            if (empty($_SESSION['pending_delete']) || $_SESSION['pending_delete']['token'] !== $token) throw new Exception('Invalid or expired token');
            if ($typed === '' || $typed !== ($_SESSION['pending_delete']['word'] ?? '')) throw new Exception('Confirmation word mismatch');

            // Proceed to delete — keep super_admin users intact
            $pdo->beginTransaction();
            try {
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
                foreach ($tables as $t) {
                    $table = $t[0];
                    if ($table === 'users') {
                        $pdo->prepare("DELETE FROM users WHERE role != 'super_admin'")->execute();
                    } else {
                        // Use DELETE to avoid needing DROP privileges; TRUNCATE may require permissions
                        $pdo->exec("DELETE FROM `{$table}`");
                    }
                }
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

            // remove pending token and leave backup file in temp for download/restore
            unset($_SESSION['pending_delete']);
            logActivity($pdo, 'delete', 'system', 'Deleted all non-admin data (backup taken)');
            echo json_encode(['success' => true, 'message' => 'All non-admin data deleted']);
            break;
        case 'export_orders':
            // Generate CSV for orders
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="wangari_orders_report_' . date('Y-m-d') . '.csv"');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Order ID', 'Order Number', 'Customer', 'Email', 'Amount', 'Status', 'Date']);

            $stmt = $pdo->query("SELECT o.id, o.order_number, u.username, u.email, o.total_amount, o.status, o.created_at
                                 FROM orders o
                                 LEFT JOIN users u ON o.user_id = u.id
                                 ORDER BY o.created_at DESC");

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit;

        case 'list_orders':
            $status = $_GET['status'] ?? null;
            $from = $_GET['from'] ?? null;
            $to = $_GET['to'] ?? null;
            $sql = "SELECT o.*, u.username, u.email, u.first_name, u.last_name, u.phone_number
                    FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE 1=1";
            $params = [];
            if ($status) { $sql .= " AND o.status=?"; $params[] = $status; }
            if ($from) { $sql .= " AND DATE(o.created_at) >= ?"; $params[] = $from; }
            if ($to) { $sql .= " AND DATE(o.created_at) <= ?"; $params[] = $to; }
            $sql .= " ORDER BY o.created_at DESC LIMIT 500";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $r['customer_name']  = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: ($r['username'] ?? 'Guest');
                $r['customer_email'] = $r['email'] ?? '';
                $r['phone_contact']  = $r['phone_contact'] ?? $r['phone_number'] ?? '';
            }
            echo json_encode(['success' => true, 'data' => $rows]);
            break;

        case 'get_order':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) throw new Exception('Order ID required');
            $order = fetchOne($pdo, "SELECT o.*, u.username, u.email, u.first_name, u.last_name
                                      FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE o.id=?", [$id]);
            if (!$order) throw new Exception('Order not found');
            $items = fetchAll($pdo, "SELECT oi.*, p.name FROM order_items oi
                                     JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?", [$id]);
            $order['customer_name']  = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?: ($order['username'] ?? 'Guest');
            $order['customer_email'] = $order['email'] ?? '';
            $order['items'] = $items;
            echo json_encode(['success' => true, 'data' => $order]);
            break;

        case 'update_order_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid method');
            $id = (int)($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? '');
            $valid = ['pending','paid','processing','shipped','completed','cancelled'];
            if (!$id) throw new Exception('ID required');
            if (!in_array($status, $valid, true)) throw new Exception('Invalid status');
            execute($pdo, "UPDATE orders SET status=? WHERE id=?", [$status, $id]);
            logActivity($pdo, 'update', 'orders', "Order #{$id} → {$status}", $id, 'order');
            echo json_encode(['success' => true, 'message' => 'Status updated']);
            break;

        case 'get_order_details':
            $order_id = (int)($_GET['order_id'] ?? 0);
            if (!$order_id) throw new Exception("Order ID required");

            $order = fetchOne($pdo, "SELECT o.*, u.username, u.email, u.phone_number as user_phone 
                                    FROM orders o 
                                    LEFT JOIN users u ON o.user_id = u.id 
                                    WHERE o.id = ?", [$order_id]);
            
            if (!$order) throw new Exception("Order not found");

            $items = fetchAll($pdo, "SELECT oi.*, p.name as product_name, p.product_type 
                                    FROM order_items oi 
                                    JOIN products p ON oi.product_id = p.id 
                                    WHERE oi.order_id = ?", [$order_id]);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'items' => $items
                ]
            ]);
            break;

        case 'bulk_update_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Invalid method");

            $csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            if (function_exists('verifyCSRFToken') && !verifyCSRFToken($csrfToken)) {
                throw new Exception("Invalid security token");
            }

            $order_ids = json_decode($_POST['order_ids'] ?? '[]', true);
            $new_status = trim($_POST['status'] ?? '');
            $valid = ['pending','paid','picking','packing','production','dispatch','shipped','delivered','completed','cancelled'];

            if (empty($order_ids) || !is_array($order_ids)) throw new Exception("No orders selected");
            if (!in_array($new_status, $valid, true)) throw new Exception("Invalid status: $new_status");

            $pdo->beginTransaction();
            $updated = 0;
            foreach ($order_ids as $oid) {
                $oid = (int)$oid;
                if ($oid <= 0) continue;
                
                // Get current status to detect transitions
                $current = fetchOne($pdo, "SELECT status FROM orders WHERE id = ?", [$oid]);
                if (!$current) continue;
                
                $oldStatus = $current['status'];
                execute($pdo, "UPDATE orders SET status = ? WHERE id = ?", [$new_status, $oid]);
                $updated++;

                // Auto-deduct raw materials when a feed order transitions to a "fulfilled" state from non-fulfilled
                $nonFulfilled = ['pending', 'cancelled'];
                $fulfilled = ['paid','picking','packing','production','dispatch','shipped','delivered','completed'];
                
                if (in_array($oldStatus, $nonFulfilled, true) && in_array($new_status, $fulfilled, true)) {
                    // Get feed items in this order and deduct their raw materials
                    $feedItems = fetchAll($pdo, "
                        SELECT oi.product_id, oi.quantity, p.product_type
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = ? AND p.product_type = 'feed'
                    ", [$oid]);

                    foreach ($feedItems as $fi) {
                        // Find the recipe for this product
                        $recipe = fetchOne($pdo, "SELECT * FROM feed_recipes WHERE product_id = ? AND is_active = 1 LIMIT 1", [$fi['product_id']]);
                        if (!$recipe) continue;

                        $ingredients = fetchAll($pdo, "
                            SELECT ri.amount_kg as base_amount, rm.id as rm_id, rm.name, rm.stock_tons, rm.current_price_per_ton
                            FROM recipe_ingredients ri
                            JOIN raw_materials rm ON ri.raw_material_id = rm.id
                            WHERE ri.recipe_id = ?
                        ", [$recipe['id']]);

                        $totalCost = 0;
                        foreach ($ingredients as $ing) {
                            $neededKg = $ing['base_amount'] * $fi['quantity'];
                            $neededTons = $neededKg / 1000;

                            // Deduct but don't go below zero
                            execute($pdo, "UPDATE raw_materials SET stock_tons = GREATEST(stock_tons - ?, 0) WHERE id = ?", [$neededTons, $ing['rm_id']]);
                            $totalCost += ($neededKg / 1000) * $ing['current_price_per_ton'];

                            // Check and create alert if low
                            $updated_rm = fetchOne($pdo, "SELECT stock_tons, min_stock_level, name FROM raw_materials WHERE id = ?", [$ing['rm_id']]);
                            if ($updated_rm && (float)$updated_rm['stock_tons'] <= (float)$updated_rm['min_stock_level']) {
                                execute($pdo, "INSERT IGNORE INTO stock_alerts (alert_type, message, related_id) VALUES ('low_stock', ?, ?)",
                                    ["{$updated_rm['name']} is running low after sale fulfillment! Stock: {$updated_rm['stock_tons']} tons.", $ing['rm_id']]);
                            }
                        }

                        // Also deduct finished product stock
                        execute($pdo, "UPDATE products SET stock_quantity = GREATEST(stock_quantity - ?, 0) WHERE id = ?", [$fi['quantity'], $fi['product_id']]);
                    }

                    // --- Direct Raw Material Sales: Deduct kgs from raw_materials.stock_tons ---
                    $rawMaterialItems = fetchAll($pdo, "
                        SELECT oi.product_id, oi.quantity, p.raw_material_id
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = ? AND p.raw_material_id IS NOT NULL
                    ", [$oid]);

                    foreach ($rawMaterialItems as $rmi) {
                        $rm = fetchOne($pdo, "SELECT id, name, stock_tons, reserved_production_kg, min_stock_level FROM raw_materials WHERE id = ?", [$rmi['raw_material_id']]);
                        if (!$rm) continue;

                        $deductKg = (float)$rmi['quantity']; // Each unit sold = 1 kg of raw material
                        $currentStock = (float)$rm['stock_tons'];
                        $reserve = (float)$rm['reserved_production_kg'];
                        $availableForSale = max(0, $currentStock - $reserve);

                        // Deduct stock (enforce floor at zero, not the reserve — the reserve is a warning threshold)
                        execute($pdo, "UPDATE raw_materials SET stock_tons = GREATEST(stock_tons - ?, 0) WHERE id = ?", [$deductKg, $rmi['raw_material_id']]);

                        // Also deduct finished product stock
                        execute($pdo, "UPDATE products SET stock_quantity = GREATEST(stock_quantity - ?, 0) WHERE id = ?", [$rmi['quantity'], $rmi['product_id']]);

                        // If this sale breaches the safety production reserve, create a margin_protection alert
                        if ($deductKg > $availableForSale) {
                            execute($pdo, "INSERT INTO stock_alerts (alert_type, message, related_id) VALUES ('margin_protection', ?, ?)",
                                ["{$rm['name']} raw material sale has breached the safety production reserve! Remaining: " . max(0, $currentStock - $deductKg) . " kgs (Reserve floor: {$reserve} kgs).", $rmi['raw_material_id']]);
                        }

                        // Low stock alert
                        $newStock = max(0, $currentStock - $deductKg);
                        if ($newStock <= (float)$rm['min_stock_level']) {
                            execute($pdo, "INSERT INTO stock_alerts (alert_type, message, related_id) VALUES ('low_stock', ?, ?)",
                                ["{$rm['name']} is critically low after direct sale! Stock: {$newStock} kgs.", $rmi['raw_material_id']]);
                        }
                    }
                }
            }
            $pdo->commit();

            echo json_encode(['success' => true, 'message' => "Updated $updated orders to '$new_status'."]);
            break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
