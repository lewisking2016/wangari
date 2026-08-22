<?php
/**
 * Backend API - Incoming Stock & Supplier Management
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();

require_once __DIR__ . '/../config/database.php';

try {
    if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager', 'stock_manager'], true)) {
        throw new Exception('Unauthorized access');
    }

    $pdo = getDatabaseConnection();
    if (!$pdo) throw new Exception('Database connection failed');

    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($action) {
        // --- SUPPLIERS ---
        case 'get_suppliers':
            $suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC")->fetchAll();
            echo json_encode(['success' => true, 'data' => $suppliers]);
            break;

        case 'save_supplier':
            if ($method !== 'POST') throw new Exception('Invalid request method');
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $contact = trim($_POST['contact_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $lead_time = (int)($_POST['lead_time_days'] ?? 5);

            if ($name === '') throw new Exception('Supplier name is required');

            if ($id > 0) {
                execute($pdo, "UPDATE suppliers SET name = ?, contact_name = ?, phone = ?, email = ?, address = ?, lead_time_days = ? WHERE id = ?",
                    [$name, $contact, $phone, $email, $address, $lead_time, $id]);
                $msg = 'Supplier updated';
            } else {
                execute($pdo, "INSERT INTO suppliers (name, contact_name, phone, email, address, lead_time_days) VALUES (?, ?, ?, ?, ?, ?)",
                    [$name, $contact, $phone, $email, $address, $lead_time]);
                $msg = 'Supplier created';
            }
            echo json_encode(['success' => true, 'message' => $msg]);
            break;

        case 'delete_supplier':
            if ($method !== 'POST') throw new Exception('Invalid request method');
            $id = (int)($_POST['id'] ?? 0);
            execute($pdo, "DELETE FROM suppliers WHERE id = ?", [$id]);
            echo json_encode(['success' => true, 'message' => 'Supplier deleted']);
            break;

        // --- INCOMING SHIPMENTS ---
        case 'get_incoming_shipments':
            $shipments = $pdo->query("
                SELECT s.*, sup.name as supplier_name, rm.name as material_name 
                FROM incoming_shipments s 
                JOIN suppliers sup ON s.supplier_id = sup.id 
                JOIN raw_materials rm ON s.raw_material_id = rm.id 
                ORDER BY s.expected_delivery_date ASC, s.created_at DESC
            ")->fetchAll();
            echo json_encode(['success' => true, 'data' => $shipments]);
            break;

        case 'save_incoming_shipment':
            if ($method !== 'POST') throw new Exception('Invalid request method');
            $id = (int)($_POST['id'] ?? 0);
            $supplier_id = (int)($_POST['supplier_id'] ?? 0);
            $raw_material_id = (int)($_POST['raw_material_id'] ?? 0);
            $qty_kg = (float)($_POST['quantity_kg'] ?? 0);
            $cost_per_kg = (float)($_POST['cost_per_kg'] ?? 0);
            $expected_date = $_POST['expected_delivery_date'] ?? null;
            $status = $_POST['status'] ?? 'ordered';

            if ($supplier_id <= 0 || $raw_material_id <= 0 || $qty_kg <= 0 || $cost_per_kg <= 0) {
                throw new Exception('Invalid shipment details');
            }

            // Check if status is transitioning to delivered
            $should_ingest = false;
            if ($id > 0) {
                $old = fetchOne($pdo, "SELECT status, quantity_kg FROM incoming_shipments WHERE id = ?", [$id]);
                if ($old && $old['status'] !== 'delivered' && $status === 'delivered') {
                    $should_ingest = true;
                }
            } else {
                if ($status === 'delivered') {
                    $should_ingest = true;
                }
            }

            if ($id > 0) {
                execute($pdo, "UPDATE incoming_shipments SET supplier_id = ?, raw_material_id = ?, quantity_kg = ?, cost_per_kg = ?, expected_delivery_date = ?, status = ? WHERE id = ?",
                    [$supplier_id, $raw_material_id, $qty_kg, $cost_per_kg, $expected_date, $status, $id]);
                $msg = 'Shipment updated';
            } else {
                execute($pdo, "INSERT INTO incoming_shipments (supplier_id, raw_material_id, quantity_kg, cost_per_kg, expected_delivery_date, status) VALUES (?, ?, ?, ?, ?, ?)",
                    [$supplier_id, $raw_material_id, $qty_kg, $cost_per_kg, $expected_date, $status]);
                $msg = 'Shipment created';
            }

            // Perform dynamic stock auto-ingest and cost re-averaging
            if ($should_ingest) {
                $rm = fetchOne($pdo, "SELECT stock_tons, current_price_per_ton FROM raw_materials WHERE id = ?", [$raw_material_id]);
                if ($rm) {
                    $current_stock = (float)$rm['stock_tons'];
                    $current_price = (float)$rm['current_price_per_ton'];

                    // Moving Average Cost Calculation
                    $new_stock = $current_stock + $qty_kg;
                    $new_price = $new_stock > 0 
                        ? (($current_stock * $current_price) + ($qty_kg * $cost_per_kg)) / $new_stock 
                        : $cost_per_kg;

                    execute($pdo, "UPDATE raw_materials SET stock_tons = ?, current_price_per_ton = ? WHERE id = ?", 
                        [$new_stock, round($new_price, 2), $raw_material_id]);
                }
            }

            echo json_encode(['success' => true, 'message' => $msg]);
            break;

        case 'delete_incoming_shipment':
            if ($method !== 'POST') throw new Exception('Invalid request method');
            $id = (int)($_POST['id'] ?? 0);
            execute($pdo, "DELETE FROM incoming_shipments WHERE id = ?", [$id]);
            echo json_encode(['success' => true, 'message' => 'Shipment deleted']);
            break;

        // --- AUTO-ORDER ASSISTANT ---
        case 'get_auto_orders':
            // Fetch raw materials running low
            $low_stock = $pdo->query("SELECT id, name, stock_tons, min_stock_level, current_price_per_ton FROM raw_materials ORDER BY name ASC")->fetchAll();
            $auto_orders = [];

            foreach ($low_stock as $rm) {
                $stock = (float)$rm['stock_tons'];
                $min = (float)$rm['min_stock_level'];
                
                if ($stock <= $min) {
                    // Find preferred supplier (cheapest or most recently ordered from)
                    $pref = fetchOne($pdo, "
                        SELECT s.id, s.name, s.lead_time_days, COALESCE(ish.cost_per_kg, ?) as last_price
                        FROM suppliers s
                        LEFT JOIN incoming_shipments ish ON ish.supplier_id = s.id AND ish.raw_material_id = ?
                        ORDER BY ish.created_at DESC LIMIT 1
                    ", [$rm['current_price_per_ton'], $rm['id']]);

                    // Fallback to any supplier if no order history
                    if (!$pref) {
                        $pref = fetchOne($pdo, "SELECT id, name, lead_time_days FROM suppliers LIMIT 1");
                        if ($pref) $pref['last_price'] = $rm['current_price_per_ton'];
                    }

                    if ($pref) {
                        // Reorder quantity: Replenish to double the minimum stock level
                        $reorder_qty = ($min * 2) - $stock;
                        if ($reorder_qty < 0.5) $reorder_qty = 0.5; // minimum bulk reorder: 0.5 Ton

                        $auto_orders[] = [
                            'raw_material_id'      => $rm['id'],
                            'material_name'        => $rm['name'],
                            'current_stock'        => $stock,
                            'min_level'            => $min,
                            'supplier_id'          => $pref['id'],
                            'supplier_name'        => $pref['name'],
                            'lead_time_days'       => $pref['lead_time_days'],
                            'recommended_qty'      => round($reorder_qty, 3),
                            'estimated_cost_per_kg'=> $pref['last_price'],
                            'total_estimated_cost' => round($reorder_qty * $pref['last_price'], 2)
                        ];
                    }
                }
            }

            echo json_encode(['success' => true, 'data' => $auto_orders]);
            break;

        default:
            throw new Exception('Action not found');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
