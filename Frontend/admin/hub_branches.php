<?php
/**
 * Farm Branches — Manage multiple farm locations.
 * Owners can create/edit/delete branches, assign managers, and switch between them.
 */
declare(strict_types=1);

$page_title = 'Farm Branches';
require_once __DIR__ . '/includes/admin_header.php';
require_once dirname(__DIR__) . '/includes/branch_helpers.php';

$pdo = wangariGetPdo();
$userId = $_SESSION['user_id'];
$currentFarmId = getCurrentFarmId();
$action = $_GET['action'] ?? 'list';
$branchId = (int)($_GET['id'] ?? 0);

// ── Handle POST actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    // Switch branch
    if ($postAction === 'switch') {
        $newFarmId = (int)($_POST['farm_id'] ?? 0);
        if (switchFarm($newFarmId)) {
            header('Location: /Frontend/admin/dashboard.php');
            exit;
        }
    }
    
    // Create branch
    if ($postAction === 'create' && isFarmOwner()) {
        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $managerId = (int)($_POST['manager_id'] ?? 0) ?: null;
        $branchColor = $_POST['branch_color'] ?? '#22C55E';
        
        if ($name) {
            // Generate unique farm code
            $code = 'WGRI-' . strtoupper(substr(md5(uniqid()), 0, 4)) . '-' . strtoupper(substr(md5(uniqid()), 0, 4)) . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
            
            $stmt = $pdo->prepare("INSERT INTO farms (name, owner_id, farm_code, location, description, manager_id, branch_color) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $userId, $code, $location, $description, $managerId, $branchColor]);
            
            header('Location: /Frontend/admin/hub_branches.php?created=1');
            exit;
        }
    }
    
    // Update branch
    if ($postAction === 'update' && isFarmOwner()) {
        $farmId = (int)($_POST['farm_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $managerId = (int)($_POST['manager_id'] ?? 0) ?: null;
        $branchColor = $_POST['branch_color'] ?? '#22C55E';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if ($name && $farmId) {
            $stmt = $pdo->prepare("UPDATE farms SET name = ?, location = ?, description = ?, manager_id = ?, branch_color = ?, is_active = ? WHERE id = ? AND owner_id = ?");
            $stmt->execute([$name, $location, $description, $managerId, $branchColor, $isActive, $farmId, $userId]);
            
            // If this was the current farm, keep it
            header('Location: /Frontend/admin/hub_branches.php?updated=1');
            exit;
        }
    }
    
    // Delete branch
    if ($postAction === 'delete' && isFarmOwner()) {
        $farmId = (int)($_POST['farm_id'] ?? 0);
        
        // Don't delete if it's the user's only farm
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM farms WHERE owner_id = ?");
        $stmt->execute([$userId]);
        $count = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        
        if ($count > 1 && $farmId) {
            $stmt = $pdo->prepare("UPDATE farms SET is_active = 0 WHERE id = ? AND owner_id = ?");
            $stmt->execute([$farmId, $userId]);
            
            // If deleted farm was current, switch to first active farm
            if ($farmId === $currentFarmId) {
                $stmt = $pdo->prepare("SELECT id FROM farms WHERE owner_id = ? AND is_active = 1 ORDER BY id LIMIT 1");
                $stmt->execute([$userId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    switchFarm((int)$row['id']);
                }
            }
            
            header('Location: /Frontend/admin/hub_branches.php?deleted=1');
            exit;
        }
    }
}

// ── Get data for views ──
$farms = getUserFarms($userId);
$allFarms = isFarmOwner() ? $farms : [];

// Get farm managers that can be assigned as branch managers
$mgrStmt = $pdo->prepare("SELECT id, full_name, username FROM users WHERE role IN ('farm_manager', 'branch_manager') AND is_active = 1 ORDER BY full_name");
$mgrStmt->execute();
$managers = $mgrStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Get branch being edited
$editFarm = null;
if ($action === 'edit' && $branchId) {
    $stmt = $pdo->prepare("SELECT * FROM farms WHERE id = ? AND owner_id = ?");
    $stmt->execute([$branchId, $userId]);
    $editFarm = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ── Include sidebar ──
include __DIR__ . '/includes/admin_sidebar.php';
?>

<div class="admin-content" style="margin-left:268px; width:calc(100% - 268px);">

<?php if ($action === 'list'): ?>
<!-- Branch List View -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-size:1.4rem;font-weight:700;color:#0F172A;">Farm Branches</h1>
        <p style="margin:4px 0 0;color:#64748B;font-size:0.85rem;">Manage your farm locations and assign branch managers</p>
    </div>
    <?php if (isFarmOwner()): ?>
    <a href="/Frontend/admin/hub_branches.php?action=create" class="btn btn-primary">
        <i data-lucide="plus" style="width:16px;height:16px;"></i> Add Branch
    </a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['created'])): ?>
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#166534;font-weight:600;font-size:0.9rem;">
    ✓ Branch created successfully!
</div>
<?php endif; ?>
<?php if (isset($_GET['updated'])): ?>
<div style="background:#dbeafe;border:1px solid #93c5fd;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#1e40af;font-weight:600;font-size:0.9rem;">
    ✓ Branch updated successfully!
</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#991b1b;font-weight:600;font-size:0.9rem;">
    ✓ Branch deactivated successfully!
</div>
<?php endif; ?>

<!-- Branch Cards Grid -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px;">
<?php foreach ($farms as $farm): 
    $stats = getBranchFarmStats((int)$farm['id']);
    $isActive = (int)$farm['is_active'];
    $isCurrent = (int)$farm['id'] === $currentFarmId;
    $borderColor = $isCurrent ? $farm['branch_color'] : '#E7EAF0';
    $bgColor = $isCurrent ? 'rgba(' . hexdec(substr($farm['branch_color'],1,2)) . ',' . hexdec(substr($farm['branch_color'],3,2)) . ',' . hexdec(substr($farm['branch_color'],5,2)) . ',0.04)' : '#fff';
?>
<div class="admin-card" style="border-left:4px solid <?php echo $borderColor; ?>;background:<?php echo $bgColor; ?>;<?php echo !$isActive ? 'opacity:0.5;' : ''; ?>">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;">
        <div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                <h3 style="margin:0;font-size:1.05rem;font-weight:700;color:#0F172A;"><?php echo htmlspecialchars($farm['name']); ?></h3>
                <?php if ($isCurrent): ?>
                <span class="badge-pill badge-pill-success" style="font-size:0.65rem;">Current</span>
                <?php endif; ?>
                <?php if (!$isActive): ?>
                <span class="badge-pill badge-pill-danger" style="font-size:0.65rem;">Inactive</span>
                <?php endif; ?>
            </div>
            <?php if ($farm['location']): ?>
            <p style="margin:0;color:#64748B;font-size:0.8rem;display:flex;align-items:center;gap:4px;">
                <i data-lucide="map-pin" style="width:12px;height:12px;"></i>
                <?php echo htmlspecialchars($farm['location']); ?>
            </p>
            <?php endif; ?>
            <p style="margin:3px 0 0;color:#94A3B8;font-size:0.72rem;font-family:monospace;"><?php echo htmlspecialchars($farm['farm_code']); ?></p>
        </div>
        <div style="display:flex;gap:6px;">
            <?php if (!$isCurrent && $isActive): ?>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="switch">
                <input type="hidden" name="farm_id" value="<?php echo $farm['id']; ?>">
                <button type="submit" class="btn btn-sm btn-outline" title="Switch to this branch">
                    <i data-lucide="repeat" style="width:13px;height:13px;"></i> Switch
                </button>
            </form>
            <?php endif; ?>
            <?php if (isFarmOwner()): ?>
            <a href="/Frontend/admin/hub_branches.php?action=edit&id=<?php echo $farm['id']; ?>" class="btn btn-sm btn-trans" title="Edit branch">
                <i data-lucide="pencil" style="width:13px;height:13px;"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($farm['manager_name']): ?>
    <div style="display:flex;align-items:center;gap:6px;margin-bottom:12px;padding:6px 10px;background:#F0FDF4;border-radius:8px;font-size:0.8rem;color:#166534;">
        <i data-lucide="user-check" style="width:14px;height:14px;"></i>
        <span>Manager: <strong><?php echo htmlspecialchars($farm['manager_name']); ?></strong></span>
    </div>
    <?php endif; ?>
    
    <!-- Branch Stats -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
        <div style="text-align:center;padding:8px;background:#F8FAFC;border-radius:8px;">
            <div style="font-size:1.1rem;font-weight:700;color:#0F172A;"><?php echo $stats['animals']; ?></div>
            <div style="font-size:0.65rem;color:#64748B;text-transform:uppercase;letter-spacing:0.05em;">Animals</div>
        </div>
        <div style="text-align:center;padding:8px;background:#F8FAFC;border-radius:8px;">
            <div style="font-size:1.1rem;font-weight:700;color:#0F172A;"><?php echo $stats['batches']; ?></div>
            <div style="font-size:0.65rem;color:#64748B;text-transform:uppercase;letter-spacing:0.05em;">Batches</div>
        </div>
        <div style="text-align:center;padding:8px;background:#F8FAFC;border-radius:8px;">
            <div style="font-size:1.1rem;font-weight:700;color:#0F172A;"><?php echo $stats['workers']; ?></div>
            <div style="font-size:0.65rem;color:#64748B;text-transform:uppercase;letter-spacing:0.05em;">Workers</div>
        </div>
    </div>
    
    <?php if ($stats['monthly_sales'] > 0): ?>
    <div style="margin-top:10px;text-align:center;padding:8px;background:#F0FDF4;border-radius:8px;font-size:0.85rem;color:#166534;font-weight:600;">
        KES <?php echo number_format($stats['monthly_sales']); ?> this month
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php if (empty($farms)): ?>
<div class="admin-card" style="text-align:center;padding:40px;">
    <i data-lucide="map" style="width:48px;height:48px;color:#94A3B8;margin-bottom:12px;"></i>
    <h3 style="margin:0 0 8px;color:#0F172A;">No branches yet</h3>
    <p style="margin:0 0 16px;color:#64748B;font-size:0.9rem;">Create your first farm branch to start organizing your operations by location.</p>
    <?php if (isFarmOwner()): ?>
    <a href="/Frontend/admin/hub_branches.php?action=create" class="btn btn-primary">Create First Branch</a>
    <?php endif; ?>
</div>
<?php endif; ?>
</div>

<!-- Quick Switch Panel (if owner has multiple branches) -->
<?php if (count($farms) > 1 && isFarmOwner()): ?>
<div class="admin-card" style="margin-top:20px;">
    <h3 style="margin:0 0 14px;font-size:1rem;font-weight:700;color:#0F172A;">Quick Switch Branch</h3>
    <p style="margin:0 0 12px;color:#64748B;font-size:0.82rem;">Switch your active branch. All modules will filter to show data from the selected branch.</p>
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
    <?php foreach ($farms as $farm): ?>
        <?php if ((int)$farm['id'] !== $currentFarmId && (int)$farm['is_active']): ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="switch">
            <input type="hidden" name="farm_id" value="<?php echo $farm['id']; ?>">
            <button type="submit" style="display:flex;align-items:center;gap:6px;padding:8px 14px;border-radius:999px;border:1.5px solid #E7EAF0;background:#fff;cursor:pointer;font-weight:600;font-size:0.82rem;color:#334155;transition:all 0.2s;" onmouseover="this.style.borderColor='<?php echo $farm['branch_color']; ?>';this.style.background='#F0FDF4'" onmouseout="this.style.borderColor='#E7EAF0';this.style.background='#fff'">
                <span style="width:8px;height:8px;border-radius:50%;background:<?php echo $farm['branch_color']; ?>;"></span>
                <?php echo htmlspecialchars($farm['name']); ?>
            </button>
        </form>
        <?php endif; ?>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
<!-- Create/Edit Branch Form -->
<div style="max-width:600px;">
    <div style="margin-bottom:20px;">
        <a href="/Frontend/admin/hub_branches.php" style="color:#64748B;font-size:0.85rem;text-decoration:none;display:flex;align-items:center;gap:4px;margin-bottom:8px;">
            <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Back to Branches
        </a>
        <h1 style="margin:0;font-size:1.4rem;font-weight:700;color:#0F172A;">
            <?php echo $action === 'create' ? 'Create New Branch' : 'Edit Branch'; ?>
        </h1>
    </div>
    
    <div class="admin-card">
        <form method="POST">
            <input type="hidden" name="action" value="<?php echo $action === 'create' ? 'create' : 'update'; ?>">
            <?php if ($action === 'edit' && $editFarm): ?>
            <input type="hidden" name="farm_id" value="<?php echo $editFarm['id']; ?>">
            <?php endif; ?>
            
            <div class="admin-form-group">
                <label class="admin-form-label">Branch Name *</label>
                <input type="text" name="name" class="admin-form-control" required 
                    value="<?php echo htmlspecialchars($editFarm['name'] ?? ''); ?>"
                    placeholder="e.g. Kiambu Farm, Nakuru Branch">
            </div>
            
            <div class="admin-form-group">
                <label class="admin-form-label">Location</label>
                <input type="text" name="location" class="admin-form-control" 
                    value="<?php echo htmlspecialchars($editFarm['location'] ?? ''); ?>"
                    placeholder="e.g. Kiambu County, Kenya">
            </div>
            
            <div class="admin-form-group">
                <label class="admin-form-label">Description</label>
                <textarea name="description" class="admin-form-control" rows="3" 
                    placeholder="What is this branch used for?"><?php echo htmlspecialchars($editFarm['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="admin-form-group">
                <label class="admin-form-label">Branch Manager</label>
                <select name="manager_id" class="admin-form-control">
                    <option value="0">— No manager assigned —</option>
                    <?php foreach ($managers as $mgr): ?>
                    <option value="<?php echo $mgr['id']; ?>" 
                        <?php echo ($editFarm['manager_id'] ?? 0) == $mgr['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($mgr['full_name'] ?: $mgr['username']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p style="margin:4px 0 0;color:#94A3B8;font-size:0.75rem;">Branch managers can only see this branch's data</p>
            </div>
            
            <div class="admin-form-group">
                <label class="admin-form-label">Branch Color</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input type="color" name="branch_color" value="<?php echo htmlspecialchars($editFarm['branch_color'] ?? '#22C55E'); ?>" 
                        style="width:40px;height:36px;border:none;border-radius:8px;cursor:pointer;">
                    <span style="color:#64748B;font-size:0.82rem;">Pick a color to identify this branch</span>
                </div>
            </div>
            
            <?php if ($action === 'edit'): ?>
            <div class="admin-form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" 
                        <?php echo ($editFarm['is_active'] ?? 1) ? 'checked' : ''; ?>
                        style="width:16px;height:16px;">
                    <span class="admin-form-label" style="margin:0;">Active</span>
                </label>
            </div>
            <?php endif; ?>
            
            <div style="display:flex;gap:10px;margin-top:20px;">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="<?php echo $action === 'create' ? 'plus' : 'save'; ?>" style="width:16px;height:16px;"></i>
                    <?php echo $action === 'create' ? 'Create Branch' : 'Save Changes'; ?>
                </button>
                <a href="/Frontend/admin/hub_branches.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
        
        <?php if ($action === 'edit' && $editFarm): ?>
        <div style="margin-top:24px;padding-top:16px;border-top:1px solid #E7EAF0;">
            <p style="margin:0 0 8px;color:#64748B;font-size:0.82rem;">Branch Code (share with workers to connect)</p>
            <div style="display:flex;align-items:center;gap:10px;">
                <code style="padding:8px 14px;background:#F1F5F9;border-radius:8px;font-size:0.9rem;font-weight:600;color:#0F172A;letter-spacing:0.05em;">
                    <?php echo htmlspecialchars($editFarm['farm_code']); ?>
                </code>
                <button onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($editFarm['farm_code']); ?>');this.textContent='Copied!'" class="btn btn-sm btn-outline">
                    <i data-lucide="copy" style="width:13px;height:13px;"></i> Copy
                </button>
            </div>
            
            <?php if (count($farms) > 1): ?>
            <form method="POST" style="margin-top:16px;" onsubmit="return confirm('Are you sure you want to deactivate this branch? Data will be preserved but hidden.')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="farm_id" value="<?php echo $editFarm['id']; ?>">
                <button type="submit" class="btn btn-danger btn-sm">
                    <i data-lucide="trash-2" style="width:13px;height:13px;"></i> Deactivate Branch
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

</div><!-- .admin-content -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
