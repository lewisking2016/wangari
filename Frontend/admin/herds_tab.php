<?php
/**
 * Sub-Module: Herds Tab Content
 */
declare(strict_types=1);

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'farm_manager'], true)) {
    echo "<script>window.location.href = '/wangariadmin';</script>";
    exit;
}

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_herd'])) {
    $herdId = (int)($_POST['herd_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $species = trim($_POST['species'] ?? '');
    $size = (int)($_POST['size'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');
    $notes = trim($_POST['notes'] ?? '');

    if ($name === '') {
        $error_message = 'Herd name is required.';
    } else {
        try {
            if ($herdId > 0) {
                $stmt = $pdo->prepare('UPDATE herds SET name = ?, species = ?, size = ?, location = ?, status = ?, notes = ? WHERE id = ?');
                $stmt->execute([$name, $species, $size, $location, $status, $notes, $herdId]);
                $message = 'Herd updated successfully.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO herds (name, species, size, location, status, notes) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $species, $size, $location, $status, $notes]);
                $message = 'Herd added successfully.';
            }
        } catch (Exception $e) {
            $error_message = 'Unable to save herd: ' . $e->getMessage();
        }
    }
}

$herds = [];
if ($pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM herds ORDER BY created_at DESC');
        $herds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

$selectedHerd = null;
if (in_array($action, ['view', 'edit'], true) && $pdo) {
    $herdId = (int)($_GET['id'] ?? 0);
    if ($herdId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM herds WHERE id = ?');
        $stmt->execute([$herdId]);
        $selectedHerd = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

if (!function_exists('renderInputHerdLocal')) {
    function renderInputHerdLocal(string $label, string $name, string $value = '', string $type = 'text'): string {
        return '<div class="admin-form-group"><label class="admin-form-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label><input class="admin-form-control" type="' . $type . '" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"></div>';
    }
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:16px;">
    <div>
        <h2 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.5rem;color:var(--admin-text-heading);">Herds</h2>
        <p style="margin:4px 0 0 0;font-size:0.9rem;color:#64748b;">Group your animals into herds or pens.</p>
    </div>
    <a href="?tab=herds&action=add" class="btn btn-primary" style="border-radius:4px;display:inline-flex;align-items:center;gap:8px;">
        <i data-lucide="plus-circle" style="width:18px;height:18px;"></i>
        <span>Add Herd</span>
    </a>
</div>

<?php if ($message): ?>
<div style="padding:14px 18px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:6px;color:#166534;margin-bottom:20px;">
    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>
<?php if ($error_message): ?>
<div style="padding:14px 18px;background:#fee2e2;border:1px solid #fecaca;border-radius:6px;color:#b91c1c;margin-bottom:20px;">
    <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;color:var(--admin-text-heading);">Herds</h3>
        <span style="font-size:0.85rem;color:#64748b;">Live herd management data</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Species</th>
                    <th>Size</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($herds)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding: 20px; color: #64748b;">No herds found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($herds as $herd): ?>
                <tr>
                    <td><?php echo htmlspecialchars($herd['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($herd['species'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string)$herd['size'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($herd['location'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($herd['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td style="text-align:right;">
                        <a class="btn btn-outline btn-sm" href="?tab=herds&action=view&id=<?php echo (int)$herd['id']; ?>">View</a>
                        <a class="btn btn-outline btn-sm" href="?tab=herds&action=edit&id=<?php echo (int)$herd['id']; ?>">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
<?php
    $formValues = [
        'name' => '',
        'species' => '',
        'size' => '',
        'location' => '',
        'status' => 'Active',
        'notes' => '',
    ];
    if ($action === 'edit' && $selectedHerd) {
        $formValues = [
            'name' => $selectedHerd['name'],
            'species' => $selectedHerd['species'],
            'size' => $selectedHerd['size'],
            'location' => $selectedHerd['location'],
            'status' => $selectedHerd['status'],
            'notes' => $selectedHerd['notes'],
        ];
    }
?>
<div class="admin-card">
    <h3 style="margin:0 0 16px 0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">
        <?php echo $action === 'add' ? 'Add Herd' : 'Edit Herd'; ?>
    </h3>
    <form method="POST" action="?tab=herds&action=<?php echo $action; ?><?php echo $action === 'edit' ? '&id=' . $selectedHerd['id'] : ''; ?>">
        <input type="hidden" name="save_herd" value="1">
        <?php if ($action === 'edit' && $selectedHerd): ?>
            <input type="hidden" name="herd_id" value="<?php echo (int)$selectedHerd['id']; ?>">
        <?php endif; ?>
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
            <?php echo renderInputHerdLocal('Herd Name', 'name', $formValues['name']); ?>
            <?php echo renderInputHerdLocal('Species', 'species', $formValues['species']); ?>
            <div class="admin-form-group">
                <label class="admin-form-label">Location</label>
                <input class="admin-form-control" type="text" name="location" value="<?php echo htmlspecialchars($formValues['location'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Size</label>
                <input class="admin-form-control" type="number" name="size" value="<?php echo htmlspecialchars((string)$formValues['size'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Status</label>
                <select class="admin-form-control" name="status">
                    <?php foreach (['Active', 'Sold', 'Archived'] as $statusOption): ?>
                        <option value="<?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $formValues['status'] === $statusOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-form-group" style="grid-column: span 2;">
                <label class="admin-form-label">Notes</label>
                <textarea class="admin-form-control" name="notes" rows="4"><?php echo htmlspecialchars($formValues['notes'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>
        <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary" style="border-radius:4px;">Save</button>
            <a href="?tab=herds" class="btn btn-outline" style="border-radius:4px;">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">Herd Details</h3>
            <p style="margin:6px 0 0 0;color:#64748b;">View herd summary and status.</p>
        </div>
        <a href="?tab=herds" class="btn btn-outline" style="border-radius:4px;">Back</a>
    </div>
    <?php if ($selectedHerd): ?>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
        <div><strong>Name:</strong> <?php echo htmlspecialchars($selectedHerd['name'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Species:</strong> <?php echo htmlspecialchars($selectedHerd['species'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Size:</strong> <?php echo htmlspecialchars((string)$selectedHerd['size'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Location:</strong> <?php echo htmlspecialchars($selectedHerd['location'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Status:</strong> <?php echo htmlspecialchars($selectedHerd['status'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div style="grid-column: span 2;"><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($selectedHerd['notes'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
    </div>
    <?php else: ?>
    <p style="color:#64748b;">Herd not found.</p>
    <?php endif; ?>
</div>
<?php endif; ?>
