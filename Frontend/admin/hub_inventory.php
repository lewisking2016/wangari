<?php
/**
 * Hub: Inventory & Store, ALL content inline, no double-includes.
 * Tabs: Products | Farm Equipment | Feed & Stock | Alerts
 */
declare(strict_types=1);
$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','stock_manager','sales_staff'], true)) {
    echo "<script>window.location.href='/wangariadmin';</script>"; exit;
}

$page_title = 'Inventory & Store - Admin';
include __DIR__ . '/includes/admin_header.php';

$tab = $_GET['tab'] ?? 'products';
$validTabs = ['products','equipment','feedstock','alerts'];
if (!in_array($tab, $validTabs, true)) $tab = 'products';

$pdo = getDB();
$message = ''; $error_message = '';

/* ── POST Handler for Farm Equipment ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $postAction = $_POST['_action'] ?? '';
    if ($postAction === 'save_equipment') {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $qty      = (int)($_POST['quantity'] ?? 1);
        $cond     = trim($_POST['condition_status'] ?? 'Good');
        $pur_date = trim($_POST['purchase_date'] ?? '');
        $cost     = (float)($_POST['cost'] ?? 0);
        $notes    = trim($_POST['notes'] ?? '');

        if ($name === '') {
            $error_message = 'Item name is required.';
        } else {
            try {
                if ($id > 0) {
                    $pdo->prepare('UPDATE farm_items SET name=?,category=?,quantity=?,condition_status=?,purchase_date=?,cost=?,notes=? WHERE id=?')
                        ->execute([$name,$category,$qty,$cond,$pur_date?:null,$cost?:null,$notes,$id]);
                    $message = 'Equipment updated successfully.';
                } else {
                    $pdo->prepare('INSERT INTO farm_items (name,category,quantity,condition_status,purchase_date,cost,notes) VALUES (?,?,?,?,?,?,?)')
                        ->execute([$name,$category,$qty,$cond,$pur_date?:null,$cost?:null,$notes]);
                    $message = 'Equipment added successfully.';
                }
            } catch (Exception $e) { $error_message = $e->getMessage(); }
        }
        $tab = 'equipment';
    }
}

/* ── Load PHP-based tab data ── */
$farmItems = [];
if ($pdo && $tab === 'equipment') {
    try {
        $farmItems = $pdo->query('SELECT * FROM farm_items ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $error_message = $e->getMessage(); }
}

$tabs = [
    'products'  => ['icon' => 'package',       'label' => 'Products Catalog'],
    'equipment' => ['icon' => 'wrench',        'label' => 'Farm Equipment & Tools'],
    'feedstock' => ['icon' => 'layers',        'label' => 'Feed & Raw Stock'],
    'alerts'    => ['icon' => 'bell',           'label' => 'Inventory Alerts'],
];
?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.6rem;color:var(--admin-text-heading);font-weight:800;">Inventory & Store</h1>
        <p style="margin:4px 0 0;color:#64748b;font-size:0.9rem;">Manage store products, farm tools, raw feed materials, and critical reorder alerts.</p>
    </div>
</div>

<?php if ($message): ?>
<div style="padding:13px 18px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;color:#166534;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <i data-lucide="check-circle-2" style="width:18px;height:18px;"></i> <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>
<?php if ($error_message): ?>
<div style="padding:13px 18px;background:#fee2e2;border:1px solid #fecaca;border-radius:8px;color:#b91c1c;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <i data-lucide="alert-circle" style="width:18px;height:18px;"></i> <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<!-- Tab Bar -->
<div style="display:flex;gap:4px;background:#f1f5f9;padding:5px;border-radius:10px;margin-bottom:24px;overflow-x:auto;scrollbar-width:none;-ms-overflow-style:none;">
<?php foreach ($tabs as $key => $info): ?>
    <a href="?tab=<?= $key ?>" style="display:flex;align-items:center;gap:7px;padding:9px 14px;border-radius:7px;text-decoration:none;white-space:nowrap;font-weight:600;font-size:0.84rem;transition:all 0.18s;<?= $tab===$key ? 'background:#fff;color:var(--admin-primary);box-shadow:0 1px 6px rgba(15,23,42,0.08);' : 'color:#64748b;' ?>">
        <i data-lucide="<?= $info['icon'] ?>" style="width:15px;height:15px;"></i><?= $info['label'] ?>
    </a>
<?php endforeach; ?>
</div>

<!-- ══════ PRODUCTS CATALOG TAB (API / AJAX IFRAME LOGIC) ══════ -->
<?php if ($tab === 'products'): ?>
<div class="admin-card" style="padding:0; overflow:hidden;">
    <iframe src="products.php" style="width:100%; height:800px; border:none; display:block;"></iframe>
</div>

<!-- ══════ EQUIPMENT TAB ══════ -->
<?php elseif ($tab === 'equipment'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;">Farm Equipment & Tools Registry</h3>
            <p style="margin:4px 0 0;font-size:0.85rem;color:#64748b;">Track tools, machinery, structures, and their current maintenance status.</p>
        </div>
        <button class="btn btn-primary" onclick="openEquipModal()"><i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Add Tool / Equipment</button>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Name</th><th>Category</th><th>Quantity</th><th>Condition</th><th>Purchase Date</th><th>Cost</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($farmItems)): ?>
                <tr><td colspan="7" style="text-align:center;padding:28px;color:#94a3b8;">No physical equipment logged yet.</td></tr>
            <?php else: foreach ($farmItems as $fi): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($fi['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($fi['category'] ?? 'Other', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)($fi['quantity'] ?? 1) ?></td>
                    <td>
                        <?php
                        $c = $fi['condition_status'] ?? 'Good';
                        $pill = $c==='Good'||$c==='New'?'badge-pill-success':($c==='Fair'?'badge-pill-warning':'badge-pill-danger');
                        ?>
                        <span class="badge-pill <?= $pill ?>"><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td><?= htmlspecialchars($fi['purchase_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $fi['cost'] ? 'KES '.number_format((float)$fi['cost'], 2) : '-' ?></td>
                    <td><div class="tbl-actions"><button class="btn btn-trans btn-sm" onclick='openEquipModal(<?= htmlspecialchars(json_encode($fi), ENT_QUOTES, "UTF-8") ?>)'><i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit</button></div></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="equip-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:32px;border-radius:12px;width:100%;max-width:520px;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
        <h3 id="equip-modal-title" style="margin:0 0 22px;font-family:'Outfit',sans-serif;">Add Equipment / Tool</h3>
        <form method="POST">
            <input type="hidden" name="_action" value="save_equipment">
            <input type="hidden" name="id" id="eq-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Item Name *</label><input class="admin-form-control" name="name" id="eq-name" required placeholder="e.g. Feed Mixer, Brooder Heater"></div>
                <div class="admin-form-group"><label class="admin-form-label">Category</label>
                    <select class="admin-form-control" name="category" id="eq-cat">
                        <option>Machinery</option><option>Tools</option><option>Feeding Equipment</option><option>Watering Equipment</option><option>Structures</option><option>Other</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Quantity</label><input class="admin-form-control" type="number" name="quantity" id="eq-qty" min="1" value="1"></div>
                <div class="admin-form-group"><label class="admin-form-label">Condition</label>
                    <select class="admin-form-control" name="condition_status" id="eq-cond">
                        <option>New</option><option>Good</option><option>Fair</option><option>Poor</option>
                    </select>
                </div>
                <div class="admin-form-group"><label class="admin-form-label">Purchase Date</label><input class="admin-form-control" type="date" name="purchase_date" id="eq-date"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Estimated Cost (KES)</label><input class="admin-form-control" type="number" step="0.01" name="cost" id="eq-cost"></div>
                <div class="admin-form-group" style="grid-column:span 2"><label class="admin-form-label">Notes / Storage Location</label><textarea class="admin-form-control" name="notes" id="eq-notes" rows="2"></textarea></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" class="btn btn-outline" style="flex:1;" onclick="document.getElementById('equip-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><i data-lucide="save" style="width:15px;height:15px;"></i> Save Tool</button>
            </div>
        </form>
    </div>
</div>
<script>
function openEquipModal(d) {
    document.getElementById('equip-modal-title').textContent = d?.id ? 'Edit Equipment / Tool' : 'Add Equipment / Tool';
    document.getElementById('eq-id').value = d?.id || '';
    document.getElementById('eq-name').value = d?.name || '';
    document.getElementById('eq-cat').value = d?.category || 'Tools';
    document.getElementById('eq-qty').value = d?.quantity || 1;
    document.getElementById('eq-cond').value = d?.condition_status || 'Good';
    document.getElementById('eq-date').value = d?.purchase_date || '';
    document.getElementById('eq-cost').value = d?.cost || '';
    document.getElementById('eq-notes').value = d?.notes || '';
    document.getElementById('equip-modal').style.display = 'flex';
}
document.addEventListener('click',e=>{ const m=document.getElementById('equip-modal'); if(m&&e.target===m) m.style.display='none'; });
</script>

<!-- ══════ FEED & STOCK TAB ══════ -->
<?php elseif ($tab === 'feedstock'): ?>
<div class="admin-card" style="padding:0; overflow:hidden;">
    <iframe src="stock_dashboard.php" style="width:100%; height:850px; border:none; display:block;"></iframe>
</div>

<!-- ══════ INVENTORY ALERTS TAB ══════ -->
<?php elseif ($tab === 'alerts'): ?>
<div class="admin-card" style="padding:0; overflow:hidden;">
    <iframe src="stock_alerts.php" style="width:100%; height:650px; border:none; display:block;"></iframe>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
