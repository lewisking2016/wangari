<?php
/**
 * Sub-Module: Animals Tab Content
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_animal'])) {
    $animalId = (int)($_POST['animal_id'] ?? 0);
    $tag = trim($_POST['tag'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $breed = trim($_POST['breed'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $birthDate = trim($_POST['birth_date'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $herdId = (int)($_POST['herd_id'] ?? 0) ?: null;
    $notes = trim($_POST['notes'] ?? '');

    if ($tag === '' || $name === '') {
        $error_message = 'Animal tag and name are required.';
    } else {
        try {
            if ($animalId > 0) {
                $stmt = $pdo->prepare('UPDATE animals SET tag = ?, name = ?, type = ?, breed = ?, gender = ?, birth_date = ?, status = ?, herd_id = ?, notes = ? WHERE id = ?');
                $stmt->execute([$tag, $name, $type, $breed, $gender, $birthDate ?: null, $status, $herdId, $notes, $animalId]);
                $message = 'Animal record updated successfully.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO animals (tag, name, type, breed, gender, birth_date, status, herd_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$tag, $name, $type, $breed, $gender, $birthDate ?: null, $status, $herdId, $notes]);
                $message = 'Animal record saved successfully.';
            }
        } catch (Exception $e) {
            $error_message = 'Unable to save animal record: ' . $e->getMessage();
        }
    }
}

$herds = [];
if ($pdo) {
    try {
        $herds = $pdo->query('SELECT id, name FROM herds ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

$animals = [];
if ($pdo) {
    try {
        $stmt = $pdo->query('SELECT a.*, h.name AS herd_name FROM animals a LEFT JOIN herds h ON a.herd_id = h.id ORDER BY a.created_at DESC');
        $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}

$selectedAnimal = null;
if (in_array($action, ['view', 'edit'], true) && $pdo) {
    $animalId = (int)($_GET['id'] ?? 0);
    if ($animalId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM animals WHERE id = ?');
        $stmt->execute([$animalId]);
        $selectedAnimal = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

if (!function_exists('renderInputLocal')) {
    function renderInputLocal(string $label, string $name, string $value = '', string $type = 'text'): string {
        return '<div class="admin-form-group"><label class="admin-form-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label><input class="admin-form-control" type="' . $type . '" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"></div>';
    }
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:16px;">
    <div>
        <h2 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.5rem;color:var(--admin-text-heading);">Animals</h2>
        <p style="margin:4px 0 0 0;font-size:0.9rem;color:#64748b;">Track every animal with actual farm data.</p>
    </div>
    <a href="?tab=animals&action=add" class="btn btn-primary" style="border-radius:4px;display:inline-flex;align-items:center;gap:8px;">
        <i data-lucide="plus-circle" style="width:18px;height:18px;"></i>
        <span>Add Animal</span>
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
        <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.1rem;color:var(--admin-text-heading);">Animal Records</h3>
        <span style="font-size:0.85rem;color:#64748b;">Live database-backed records</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tag</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Breed</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($animals)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding: 20px; color: #64748b;">No animal records found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($animals as $animal): ?>
                <tr>
                    <td><?php echo htmlspecialchars($animal['tag'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($animal['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($animal['type'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($animal['breed'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($animal['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td style="text-align:right;">
                        <a class="btn btn-outline btn-sm" href="?tab=animals&action=view&id=<?php echo (int)$animal['id']; ?>">View</a>
                        <a class="btn btn-outline btn-sm" href="?tab=animals&action=edit&id=<?php echo (int)$animal['id']; ?>">Edit</a>
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
        'tag' => '',
        'name' => '',
        'type' => '',
        'breed' => '',
        'gender' => '',
        'birth_date' => '',
        'status' => 'Active',
        'herd_id' => '',
        'notes' => '',
    ];
    if ($action === 'edit' && $selectedAnimal) {
        $formValues = [
            'tag' => $selectedAnimal['tag'],
            'name' => $selectedAnimal['name'],
            'type' => $selectedAnimal['type'],
            'breed' => $selectedAnimal['breed'],
            'gender' => $selectedAnimal['gender'],
            'birth_date' => $selectedAnimal['birth_date'],
            'status' => $selectedAnimal['status'],
            'herd_id' => $selectedAnimal['herd_id'],
            'notes' => $selectedAnimal['notes'],
        ];
    }
?>
<div class="admin-card">
    <h3 style="margin:0 0 16px 0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">
        <?php echo $action === 'add' ? 'Add Animal' : 'Edit Animal'; ?>
    </h3>
    <form method="POST" action="?tab=animals&action=<?php echo $action; ?><?php echo $action === 'edit' ? '&id=' . $selectedAnimal['id'] : ''; ?>">
        <input type="hidden" name="save_animal" value="1">
        <?php if ($action === 'edit' && $selectedAnimal): ?>
            <input type="hidden" name="animal_id" value="<?php echo (int)$selectedAnimal['id']; ?>">
        <?php endif; ?>
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
            <?php echo renderInputLocal('Animal Tag', 'tag', $formValues['tag']); ?>
            <?php echo renderInputLocal('Animal Name', 'name', $formValues['name']); ?>
            <?php echo renderInputLocal('Type', 'type', $formValues['type']); ?>
            <?php echo renderInputLocal('Breed', 'breed', $formValues['breed']); ?>
            <?php echo renderInputLocal('Gender', 'gender', $formValues['gender']); ?>
            <?php echo renderInputLocal('Status', 'status', $formValues['status']); ?>
            <div class="admin-form-group">
                <label class="admin-form-label">Birth Date</label>
                <input class="admin-form-control" type="date" name="birth_date" value="<?php echo htmlspecialchars($formValues['birth_date'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Herd</label>
                <select class="admin-form-control" name="herd_id">
                    <option value="">-- Select Herd --</option>
                    <?php foreach ($herds as $herd): ?>
                        <option value="<?php echo (int)$herd['id']; ?>" <?php echo $formValues['herd_id'] == $herd['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($herd['name'], ENT_QUOTES, 'UTF-8'); ?></option>
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
            <a href="?tab=animals" class="btn btn-outline" style="border-radius:4px;">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h3 style="margin:0;font-family:'Outfit',sans-serif;font-size:1.2rem;color:var(--admin-text-heading);">Animal Details</h3>
            <p style="margin:6px 0 0 0;color:#64748b;">Review the selected animal record.</p>
        </div>
        <a href="?tab=animals" class="btn btn-outline" style="border-radius:4px;">Back</a>
    </div>
    <?php if ($selectedAnimal): ?>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;">
        <div><strong>Tag:</strong> <?php echo htmlspecialchars($selectedAnimal['tag'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Name:</strong> <?php echo htmlspecialchars($selectedAnimal['name'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Type:</strong> <?php echo htmlspecialchars($selectedAnimal['type'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Breed:</strong> <?php echo htmlspecialchars($selectedAnimal['breed'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Gender:</strong> <?php echo htmlspecialchars($selectedAnimal['gender'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Status:</strong> <?php echo htmlspecialchars($selectedAnimal['status'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Herd:</strong> <?php echo htmlspecialchars($selectedAnimal['herd_name'] ?? 'None', ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Birth Date:</strong> <?php echo htmlspecialchars($selectedAnimal['birth_date'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
        <div style="grid-column: span 2;"><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($selectedAnimal['notes'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
    </div>
    <?php else: ?>
    <p style="color:#64748b;">Animal record not found.</p>
    <?php endif; ?>
</div>
<?php endif; ?>
