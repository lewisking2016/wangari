<?php
/**
 * Admin - Product Management (Full CRUD)
 * Clean SaaS Minimalist Design
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) {
    session_save_path($temp_dir);
}
session_start();

$path_prefix = '../../';
$page_title = 'Manage Products - Admin';

include __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/../../Backend/api/dropdowns.php';

// Check admin access
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','sales_staff'], true)) {
    echo "<script>window.location.href = '/wangariadmin';</script>";
    exit;
}

$pdo = getDB();
$success_message = '';
$error_message = '';
$csrf_token = function_exists('generateCSRFToken') ? generateCSRFToken() : ($_SESSION['csrf_token'] ?? '');

// --- Handle POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security token expired. Please refresh and try again.';
    } else {
    $action = $_POST['action'] ?? '';

    // Image Upload Helper
    $handleImageUpload = function($file) {
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Image upload failed.');
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('Image must be 5MB or smaller.');
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']) ?: '';
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new RuntimeException('Only JPG, PNG, WEBP, and GIF images are allowed.');
        }

        $target_dir = __DIR__ . '/../images/products/';
        if (!is_dir($target_dir) && !mkdir($target_dir, 0755, true) && !is_dir($target_dir)) {
            throw new RuntimeException('Unable to create upload directory.');
        }

        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_ext === '') {
            throw new RuntimeException('Uploaded image must have a file extension.');
        }

        $new_filename = uniqid('prod_', true) . '.' . $file_ext;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return '/Frontend/images/products/' . $new_filename;
        }
        throw new RuntimeException('Unable to save uploaded image.');
    };

    if ($action === 'add_product') {
        try {
            $image_url = $handleImageUpload($_FILES['product_image'] ?? null);
            $raw_material_id = !empty($_POST['raw_material_id']) ? (int)$_POST['raw_material_id'] : null;
            $reserved_production_kg = (float)($_POST['reserved_production_kg'] ?? 0);

            $stmt = $pdo->prepare("INSERT INTO products (category_id, raw_material_id, name, slug, product_type, price, stock_quantity, description, image_url, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($_POST['name'])));
            $stmt->execute([
                (int)$_POST['category_id'],
                $raw_material_id,
                trim($_POST['name']),
                $slug,
                $_POST['product_type'],
                (float)$_POST['price'],
                (int)$_POST['stock_quantity'],
                trim($_POST['description'] ?? ''),
                $image_url
            ]);

            if ($raw_material_id) {
                execute($pdo, "UPDATE raw_materials SET reserved_production_kg = ? WHERE id = ?", [$reserved_production_kg, $raw_material_id]);
            }

            $success_message = 'Product added successfully.';
            logActivity($pdo, 'add', 'products', "Added product: " . trim($_POST['name'] ?? ''), (int)$pdo->lastInsertId(), 'product');
        } catch (Exception $e) {
            $error_message = 'Failed to add product: ' . $e->getMessage();
        }
    }

    if ($action === 'edit_product') {
        try {
            $image_url = $handleImageUpload($_FILES['product_image'] ?? null);
            $raw_material_id = !empty($_POST['raw_material_id']) ? (int)$_POST['raw_material_id'] : null;
            $reserved_production_kg = (float)($_POST['reserved_production_kg'] ?? 0);

            $sql = "UPDATE products SET name = ?, product_type = ?, price = ?, stock_quantity = ?, description = ?, category_id = ?, raw_material_id = ?";
            $params = [
                trim($_POST['name']),
                $_POST['product_type'],
                (float)$_POST['price'],
                (int)$_POST['stock_quantity'],
                trim($_POST['description'] ?? ''),
                (int)$_POST['category_id'],
                $raw_material_id
            ];
            
            if ($image_url) {
                $sql .= ", image_url = ?";
                $params[] = $image_url;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = (int)$_POST['product_id'];
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if ($raw_material_id) {
                execute($pdo, "UPDATE raw_materials SET reserved_production_kg = ? WHERE id = ?", [$reserved_production_kg, $raw_material_id]);
            }

            $success_message = 'Product updated successfully.';
            logActivity($pdo, 'update', 'products', "Updated product: " . trim($_POST['name'] ?? ''), (int)($_POST['product_id'] ?? 0), 'product');
        } catch (Exception $e) {
            $error_message = 'Failed to update product: ' . $e->getMessage();
        }
    }

    if ($action === 'delete_product') {
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([(int)$_POST['product_id']]);
            $success_message = 'Product deleted successfully.';
            logActivity($pdo, 'delete', 'products', "Deleted product #" . (int)$_POST['product_id'], (int)$_POST['product_id'], 'product');
        } catch (Exception $e) {
            $error_message = 'Failed to delete product: ' . $e->getMessage();
        }
    }

    if ($action === 'toggle_status') {
        try {
            $stmt = $pdo->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([(int)$_POST['product_id']]);
            $success_message = 'Product status toggled.';
            logActivity($pdo, 'update', 'products', "Toggled visibility of product #" . (int)$_POST['product_id'], (int)$_POST['product_id'], 'product');
        } catch (Exception $e) {
            $error_message = 'Failed to toggle status.';
        }
    }
    }
}

// --- Fetch products with search/filter ---
$products = [];
$categories = [];
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';

$raw_materials_list = [];
if ($pdo) {
    try {
        // Sync system_dropdowns product_categories into categories table
        $missingCats = safeQueryAll($pdo,
            "SELECT sd.option_value AS slug, sd.option_label AS name
            FROM system_dropdowns sd
            LEFT JOIN categories c ON c.slug COLLATE utf8mb4_unicode_ci = sd.option_value COLLATE utf8mb4_unicode_ci
            WHERE sd.group_key = 'product_categories' AND c.id IS NULL"
        );
        if (!empty($missingCats)) {
            $insertCat = $pdo->prepare("INSERT INTO categories (name, slug, category_type, description) VALUES (?, ?, 'chicken', '')");
            foreach ($missingCats as $mc) {
                $insertCat->execute([$mc['name'], $mc['slug']]);
            }
        }
    } catch (Exception $e) {
        error_log("Admin products category sync error: " . $e->getMessage());
    }

    try {
        // Fetch categories for dropdowns
        $categories = safeQueryAll($pdo,
            "SELECT c.id, sd.option_label AS name, sd.option_value AS slug 
            FROM system_dropdowns sd
            JOIN categories c ON c.slug COLLATE utf8mb4_unicode_ci = sd.option_value COLLATE utf8mb4_unicode_ci
            WHERE sd.group_key = 'product_categories' AND sd.is_active = 1
            ORDER BY sd.sort_order ASC, sd.option_label ASC"
        );
    } catch (Exception $e) {
        error_log('Admin products categories load error: ' . $e->getMessage());
        $categories = [];
    }

    try {
        $raw_materials_list = safeQueryAll($pdo, "SELECT id, name, reserved_production_kg, stock_tons FROM raw_materials ORDER BY name");
    } catch (Exception $e) {
        error_log('Admin products raw materials load error: ' . $e->getMessage());
        $raw_materials_list = [];
    }

    try {
        // Build product query
        $query = "SELECT p.*, c.name as category_name, fr.id as recipe_id, rm.name as linked_raw_material_name, rm.reserved_production_kg, rm.stock_tons as raw_material_stock
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  LEFT JOIN feed_recipes fr ON fr.product_id = p.id
                  LEFT JOIN raw_materials rm ON p.raw_material_id = rm.id
                  WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND p.name LIKE ?";
            $params[] = "%$search%";
        }
        if (!empty($type_filter)) {
            $query .= " AND p.product_type = ?";
            $params[] = $type_filter;
        }

        $query .= " ORDER BY p.created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Admin products query error: " . $e->getMessage());
        $error_message = "Database query error: " . $e->getMessage();
        $products = [];
    }
}
?>

<!-- Alerts -->
<?php if ($success_message): ?>
<div style="padding: 12px 20px; background: #dcfce7; border: 1px solid #bbf7d0; border-radius: 4px; color: #15803d; font-size: 0.9rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
    <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
    <?php echo htmlspecialchars($success_message); ?>
</div>
<?php endif; ?>
<?php if ($error_message): ?>
<div style="padding: 12px 20px; background: #fee2e2; border: 1px solid #fecaca; border-radius: 4px; color: #b91c1c; font-size: 0.9rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
    <i data-lucide="alert-circle" style="width: 16px; height: 16px;"></i>
    <?php echo htmlspecialchars($error_message); ?>
</div>
<?php endif; ?>

<!-- Page Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.5rem; color: var(--admin-text-heading);">Product Catalog</h2>
        <p style="margin: 4px 0 0 0; font-size: 0.875rem; color: #475569;">Add, edit, and monitor your farm inventory levels.</p>
    </div>
    <button onclick="document.getElementById('add-modal').style.display='flex'" class="btn btn-primary" style="border-radius: 4px; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
        <span>Add Product</span>
    </button>
</div>

<div class="admin-card" style="padding: 0; overflow: hidden; border-radius: 4px;">
    <!-- Search & Filter Bar -->
    <form method="GET" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; background: #fafafa; border-bottom: 1px solid var(--admin-border); flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 200px;">
            <i data-lucide="search" style="width: 18px; height: 18px; color: #94a3b8;"></i>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products..." style="background: transparent; border: none; outline: none; font-size: 0.9rem; width: 100%;">
        </div>
        <div style="display: flex; gap: 8px;">
            <select name="type" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem; outline: none; background: #ffffff;">
                <?php echo renderDropdownOptions('product_types', $type_filter, 'All Types'); ?>
            </select>
            <button type="submit" class="btn btn-outline" style="border-radius: 4px; padding: 6px 16px; font-size: 0.85rem;">Filter</button>
            <?php if ($search || $type_filter): ?>
                <a href="products.php" style="padding: 6px 12px; font-size: 0.85rem; color: #64748b; text-decoration: none;">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Product Table -->
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Price</th>
                    <th>Stock Level</th>
                    <th>Stock Brain</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 48px; height: 48px; background: #f1f5f9; border-radius: 4px; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1px solid var(--admin-border);">
                                <?php if ($product['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <i data-lucide="package" style="width: 20px; height: 20px; color: #94a3b8;"></i>
                                <?php endif; ?>
                            </div>
                            <div style="font-weight: 600; color: var(--admin-text-heading);"><?php echo htmlspecialchars($product['name']); ?></div>
                        </div>
                    </td>
                    <td style="color: #475569; font-size: 0.9rem;">
                        <?php echo ucfirst(str_replace('_', ' ', $product['product_type'] ?? 'General')); ?>
                    </td>
                    <td style="font-weight: 600; color: var(--admin-text-heading);">
                        KES <?php echo number_format((float)$product['price']); ?>
                    </td>
                    <td>
                        <span style="font-weight: 500; <?php echo ($product['stock_quantity'] < 10) ? 'color: #dc2626; font-weight: 600;' : 'color: #475569;'; ?>">
                            <?php echo $product['stock_quantity']; ?> units
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($product['raw_material_id'])): ?>
                            <?php 
                                $total_stock = (float)($product['raw_material_stock'] ?? 0);
                                $reserved = (float)($product['reserved_production_kg'] ?? 0);
                                $avail_sale = max(0.0, $total_stock - $reserved);
                                $pct = $total_stock > 0 ? min(100.0, ($reserved / $total_stock) * 100) : 0;
                            ?>
                            <div style="font-size: 0.8rem; display: flex; flex-direction: column; gap: 4px;">
                                <div style="display: flex; justify-content: space-between; font-weight: 700; color: #475569;">
                                    <span><?php echo htmlspecialchars($product['linked_raw_material_name']); ?></span>
                                    <span style="color: var(--admin-primary);"><?php echo number_format($avail_sale); ?> kgs sellable</span>
                                </div>
                                <div style="width: 100%; height: 6px; background: #e2e8f0; border-radius: 9999px; overflow: hidden; display: flex;">
                                    <div style="width: <?php echo $pct; ?>%; height: 100%; background: #f59e0b;" title="Reserved for Production"></div>
                                    <div style="width: <?php echo 100 - $pct; ?>%; height: 100%; background: var(--admin-primary);" title="Available for Direct Sale"></div>
                                </div>
                                <span style="font-size: 0.7rem; color: #64748b;">Reserve Floor: <?php echo number_format($reserved); ?> kgs</span>
                            </div>
                        <?php elseif ($product['product_type'] === 'feed'): ?>
                            <span class="badge-pill badge-pill-success" style="display: inline-flex; align-items: center; gap: 4px;">
                                <i data-lucide="package" style="width: 12px; height: 12px;"></i> Feed
                            </span>
                        <?php else: ?>
                            <span style="color: #94a3b8; font-size: 0.8rem;">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0;">
                                <?php if ($product['is_active']): ?>
                                    <span class="badge-pill badge-pill-success">Active</span>
                                <?php else: ?>
                                    <span class="badge-pill badge-pill-danger">Inactive</span>
                                <?php endif; ?>
                            </button>
                        </form>
                    </td>
                    <td style="text-align: right;">
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button title="Edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px; transition: color 0.2s;" onmouseover="this.style.color='var(--admin-primary)'" onmouseout="this.style.color='#94a3b8'">
                                <i data-lucide="edit-3" style="width: 16px; height: 16px;"></i>
                            </button>
                            <form method="POST" onsubmit="return confirm('Delete this product permanently?');" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" title="Delete" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px; transition: color 0.2s;" onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#94a3b8'">
                                    <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #64748b;">No products found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ========== RAW MATERIAL SALES SUB-MODULE ========== -->
    <div class="admin-card" style="margin-top: 32px; padding: 24px; border: 1px solid var(--admin-border); border-radius: 8px; background: #ffffff;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
            <div style="background: rgba(27, 94, 32, 0.1); color: var(--admin-primary); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="percent" style="width: 20px; height: 20px;"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.2rem; color: var(--admin-text-heading);">Raw Material Sales & Protection Control</h3>
                <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: #64748b;">Direct retail of ingredients while automatically safeguarding safety production reserves.</p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px;">
            <?php 
            $linked_found = false;
            foreach ($products as $p): 
                if (!empty($p['raw_material_id'])): 
                    $linked_found = true;
                    $total = (float)$p['raw_material_stock'];
                    $reserve = (float)$p['reserved_production_kg'];
                    $sellable = max(0.0, $total - $reserve);
                    $fill_pct = $total > 0 ? min(100.0, ($reserve / $total) * 100) : 0;
            ?>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px; position: relative; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                            <span style="font-weight: 700; font-size: 0.95rem; color: var(--admin-text-heading);"><?php echo htmlspecialchars($p['name']); ?></span>
                            <span class="badge-pill badge-pill-success" style="font-size: 0.7rem;">Linked to <?php echo htmlspecialchars($p['linked_raw_material_name']); ?></span>
                        </div>
                        <p style="margin: 0 0 12px; font-size: 0.8rem; color: #64748b;">Selling Price: <strong>KES <?php echo number_format((float)$p['price'], 2); ?> / kg</strong></p>
                        
                        <div style="margin-bottom: 12px;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #475569; margin-bottom: 4px;">
                                <span>Production Reserve: <strong><?php echo number_format($reserve); ?> kgs</strong></span>
                                <span>Sellable Stock: <strong><?php echo number_format($sellable); ?> kgs</strong></span>
                            </div>
                            <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 9999px; overflow: hidden; display: flex;">
                                <div style="width: <?php echo $fill_pct; ?>%; height: 100%; background: #f59e0b;" title="Production Reserve Floor"></div>
                                <div style="width: <?php echo 100 - $fill_pct; ?>%; height: 100%; background: var(--admin-primary);" title="Available for Retail Sale"></div>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 12px; margin-top: 12px; font-size: 0.75rem; color: #64748b;">
                        <span>Total physical stock: <strong><?php echo number_format($total); ?> kgs</strong></span>
                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($p)); ?>)" class="btn btn-trans btn-sm" style="font-size:0.7rem; padding: 4px 8px;">Adjust Reserve</button>
                    </div>
                </div>
            <?php 
                endif;
            endforeach; 
            if (!$linked_found):
            ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 32px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; color: #64748b; font-size: 0.9rem;">
                    <i data-lucide="alert-circle" style="width: 24px; height: 24px; color: #94a3b8; margin-bottom: 8px;"></i>
                    <p style="margin: 0;">No products are currently configured for direct Raw Material Sales.</p>
                    <p style="margin: 4px 0 0; font-size: 0.8rem;">Edit an existing product or add a new one, then configure the "Link Raw Material" settings.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========== ADD PRODUCT MODAL ========== -->
<div id="add-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 4px; width: 100%; max-width: 560px; padding: 32px; box-shadow: 0 24px 48px rgba(0,0,0,0.15); position: relative;">
        <button onclick="document.getElementById('add-modal').style.display='none'" style="position: absolute; top: 16px; right: 16px; background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 1.2rem;">✕</button>
        <h3 style="margin: 0 0 24px 0; font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: var(--admin-text-heading);">Add New Product</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="add_product">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="admin-form-group" style="grid-column: span 2;">
                    <label class="admin-form-label">Product Image</label>
                    <div style="display: flex; align-items: center; gap: 16px; border: 1px dashed #cbd5e1; padding: 16px; border-radius: 4px;">
                        <div id="add-preview" style="width: 60px; height: 60px; background: #f8fafc; border-radius: 4px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid #e2e8f0;">
                            <i data-lucide="image" style="width: 24px; height: 24px; color: #94a3b8;"></i>
                        </div>
                        <div style="flex: 1;">
                            <input type="file" name="product_image" accept="image/*" onchange="previewImage(this, 'add-preview')" style="font-size: 0.8rem;">
                            <p style="margin: 4px 0 0 0; font-size: 0.7rem; color: #64748b;">Recommended: Square image, max 2MB.</p>
                        </div>
                    </div>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Product Name *</label>
                    <input type="text" name="name" required class="admin-form-control">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Category *</label>
                    <select name="category_id" required class="admin-form-control">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Product Type *</label>
                    <select name="product_type" required class="admin-form-control">
                        <?php echo renderDropdownOptions('product_types', null, '-- Select Type --'); ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Price (KES) *</label>
                    <input type="number" name="price" required min="0" step="0.01" class="admin-form-control">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Stock Quantity *</label>
                    <input type="number" name="stock_quantity" required min="0" class="admin-form-control">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Link Raw Material (For Direct Sale)</label>
                    <select name="raw_material_id" class="admin-form-control">
                        <option value="">None (Not a raw material)</option>
                        <?php foreach ($raw_materials_list as $rm): ?>
                            <option value="<?php echo $rm['id']; ?>"><?php echo htmlspecialchars($rm['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Safety Production Floor (kgs)</label>
                    <input type="number" name="reserved_production_kg" class="admin-form-control" min="0" value="0" step="0.01">
                </div>
            </div>
            <div class="admin-form-group" style="margin-top: 8px;">
                <label class="admin-form-label">Description</label>
                <textarea name="description" rows="3" class="admin-form-control" style="resize: vertical;"></textarea>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="document.getElementById('add-modal').style.display='none'" class="btn btn-outline" style="border-radius: 4px;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="border-radius: 4px;">Add Product</button>
            </div>
        </form>
    </div>
</div>

<!-- ========== EDIT PRODUCT MODAL ========== -->
<div id="edit-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 4px; width: 100%; max-width: 560px; padding: 32px; box-shadow: 0 24px 48px rgba(0,0,0,0.15); position: relative;">
        <button onclick="document.getElementById('edit-modal').style.display='none'" style="position: absolute; top: 16px; right: 16px; background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 1.2rem;">✕</button>
        <h3 style="margin: 0 0 24px 0; font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: var(--admin-text-heading);">Edit Product</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="product_id" id="edit-id">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="admin-form-group" style="grid-column: span 2;">
                    <label class="admin-form-label">Update Product Image</label>
                    <div style="display: flex; align-items: center; gap: 16px; border: 1px dashed #cbd5e1; padding: 16px; border-radius: 4px;">
                        <div id="edit-preview" style="width: 60px; height: 60px; background: #f8fafc; border-radius: 4px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid #e2e8f0;">
                            <i data-lucide="image" style="width: 24px; height: 24px; color: #94a3b8;"></i>
                        </div>
                        <div style="flex: 1;">
                            <input type="file" name="product_image" accept="image/*" onchange="previewImage(this, 'edit-preview')" style="font-size: 0.8rem;">
                            <p style="margin: 4px 0 0 0; font-size: 0.7rem; color: #64748b;">Leave empty to keep current image.</p>
                        </div>
                    </div>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Product Name *</label>
                    <input type="text" name="name" id="edit-name" required class="admin-form-control">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Category *</label>
                    <select name="category_id" id="edit-category" required class="admin-form-control">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Product Type *</label>
                    <select name="product_type" id="edit-type" required class="admin-form-control">
                        <?php echo renderDropdownOptions('product_types', null, '-- Select Type --'); ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Price (KES) *</label>
                    <input type="number" name="price" id="edit-price" required min="0" step="0.01" class="admin-form-control">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Stock Quantity *</label>
                    <input type="number" name="stock_quantity" id="edit-stock" required min="0" class="admin-form-control">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Link Raw Material (For Direct Sale)</label>
                    <select name="raw_material_id" id="edit-raw-material-id" class="form-control" style="width:100%; height:42px;">
                        <option value="">None (Not a raw material)</option>
                        <?php foreach ($raw_materials_list as $rm): ?>
                            <option value="<?php echo $rm['id']; ?>"><?php echo htmlspecialchars($rm['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Safety Production Floor (kgs)</label>
                    <input type="number" name="reserved_production_kg" id="edit-reserved-production-kg" class="admin-form-control" min="0" step="0.01">
                </div>
            </div>
            <div class="admin-form-group" style="margin-top: 8px;">
                <label class="admin-form-label">Description</label>
                <textarea name="description" id="edit-desc" rows="3" class="admin-form-control" style="resize: vertical;"></textarea>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="document.getElementById('edit-modal').style.display='none'" class="btn btn-outline" style="border-radius: 4px;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="border-radius: 4px;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function openEditModal(product) {
    document.getElementById('edit-id').value = product.id;
    document.getElementById('edit-name').value = product.name;
    document.getElementById('edit-category').value = product.category_id;
    document.getElementById('edit-type').value = product.product_type;
    document.getElementById('edit-price').value = product.price;
    document.getElementById('edit-stock').value = product.stock_quantity;
    document.getElementById('edit-desc').value = product.description || '';
    document.getElementById('edit-raw-material-id').value = product.raw_material_id || '';
    document.getElementById('edit-reserved-production-kg').value = product.reserved_production_kg || 0;
    
    // Set preview
    const preview = document.getElementById('edit-preview');
    if (product.image_url) {
        preview.innerHTML = `<img src="${product.image_url}" style="width: 100%; height: 100%; object-fit: cover;">`;
    } else {
        preview.innerHTML = `<i data-lucide="image" style="width: 24px; height: 24px; color: #94a3b8;"></i>`;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
    
    document.getElementById('edit-modal').style.display = 'flex';
}
</script>

<?php
include __DIR__ . '/includes/admin_footer.php';
?>
