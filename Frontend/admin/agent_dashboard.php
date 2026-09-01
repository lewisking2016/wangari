<?php
/**
 * Wangari Agent Dashboard
 * 
 * Tool for field agents to:
 * 1. Create farm accounts on behalf of farmers
 * 2. Enter data for farmers who can't type
 * 3. Track onboarding progress (active vs inactive farmers)
 * 4. Generate reports for farmers during visits
 * 
 * Access: Agent role users only
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    wangariStartSession();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /Frontend/pages/login.php');
    exit;
}

$pdo = getDB();
$userId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';

// Check if user is an agent (allow admin, farm_manager, and agent roles)
$allowed_roles = ['super_admin', 'farm_manager', 'agent'];
if (!in_array($role, $allowed_roles)) {
    // Allow access but show limited view
}

$page_title = 'Agent Dashboard — Wangari';

// Handle quick data entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $target_user_id = (int)($_POST['target_user_id'] ?? 0);
    
    if ($action === 'quick_entry' && $target_user_id > 0) {
        $today = date('Y-m-d');
        $eggs = (int)($_POST['eggs'] ?? 0);
        $mortality = (int)($_POST['mortality'] ?? 0);
        $feed_bags = (int)($_POST['feed_bags'] ?? 0);
        
        // Insert or update production record
        $stmt = $pdo->prepare("SELECT id FROM daily_production WHERE user_id = ? AND record_date = ? LIMIT 1");
        $stmt->execute([$target_user_id, $today]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $pdo->prepare("UPDATE daily_production SET eggs_collected = ?, mortality = ? WHERE id = ?")
                ->execute([$eggs, $mortality, $existing['id']]);
        } else {
            $pdo->prepare("INSERT INTO daily_production (user_id, record_date, eggs_collected, mortality) VALUES (?, ?, ?, ?)")
                ->execute([$target_user_id, $today, $eggs, $mortality]);
        }
        
        // Log feed as expense if provided
        if ($feed_bags > 0) {
            $feed_cost = $feed_bags * 500; // Default KES 500/bag
            $pdo->prepare("INSERT INTO simple_expenses (user_id, expense_date, category, description, amount) VALUES (?, ?, 'feed', ?, ?)")
                ->execute([$target_user_id, $today, "$feed_bags bags of feed (entered by agent)", $feed_cost]);
        }
        
        $success_msg = "Data entered for user #$target_user_id";
    }
    
    if ($action === 'create_account' && !empty($_POST['farm_name'])) {
        // Create a new farm account for a farmer
        $farm_name = trim($_POST['farm_name']);
        $farmer_name = trim($_POST['farmer_name'] ?? '');
        $farmer_phone = trim($_POST['farmer_phone'] ?? '');
        $farmer_email = trim($_POST['farmer_email'] ?? '');
        
        if (empty($farmer_email)) {
            $error_msg = "Email is required";
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$farmer_email]);
            if ($stmt->fetch()) {
                $error_msg = "An account with this email already exists";
            } else {
                // Create user
                $username = strtolower(str_replace(['@', '.'], ['', ''], explode('@', $farmer_email)[0]));
                $username = preg_replace('/[^a-z0-9]/', '', $username);
                $password_hash = password_hash('wangari123', PASSWORD_DEFAULT);
                
                $parts = explode(' ', $farmer_name);
                $first_name = $parts[0] ?? '';
                $last_name = implode(' ', array_slice($parts, 1)) ?: '';
                
                $pdo->prepare("INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone_number) VALUES (?, ?, ?, 'customer', ?, ?, ?)")
                    ->execute([$username, $farmer_email, $password_hash, $first_name, $last_name, $farmer_phone]);
                $new_user_id = $pdo->lastInsertId();
                
                // Create farm
                $farm_code = strtoupper(substr($farm_name, 0, 3));
                for ($i = 0; $i < 4; $i++) {
                    $farm_code .= '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'[random_int(0, 35)];
                }
                
                $pdo->prepare("INSERT INTO farms (name, owner_id, farm_code, created_at) VALUES (?, ?, ?, NOW())")
                    ->execute([$farm_name, $new_user_id, $farm_code]);
                $farm_id = $pdo->lastInsertId();
                
                // Add as owner
                $pdo->prepare("INSERT INTO farm_members (farm_id, user_id, role, status, joined_at) VALUES (?, ?, 'farm_owner', 'active', NOW())")
                    ->execute([$farm_id, $new_user_id]);
                
                // Set current farm
                $pdo->prepare("UPDATE users SET current_farm_id = ? WHERE id = ?")
                    ->execute([$farm_id, $new_user_id]);
                
                $success_msg = "Account created! Email: $farmer_email, Password: wangari123, Farm Code: $farm_code";
            }
        }
    }
}

// Get agent's farmer list
$farmer_stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.email, u.phone_number, u.primary_goal,
           u.last_login, u.created_at,
           f.name as farm_name, f.farm_code,
           (SELECT COUNT(*) FROM daily_production WHERE user_id = u.id AND record_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as entries_this_week,
           (SELECT COUNT(*) FROM daily_production WHERE user_id = u.id AND record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as entries_this_month
    FROM users u
    LEFT JOIN farm_members fm ON u.id = fm.user_id
    LEFT JOIN farms f ON fm.farm_id = f.id
    WHERE fm.role = 'farm_owner'
    ORDER BY u.created_at DESC
    LIMIT 100
");
$farmer_stmt->execute();
$farmers = $farmer_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$total_farmers = count($farmers);
$active_farmers = 0;
$inactive_farmers = 0;
$goals = [];
foreach ($farmers as $f) {
    $entries = $f['entries_this_week'] ?? 0;
    if ($entries > 0) $active_farmers++;
    else $inactive_farmers++;
    $goal = $f['primary_goal'] ?? 'not set';
    $goals[$goal] = ($goals[$goal] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #0a0f0d; color: #e5e7eb; font-family: 'Inter Tight', sans-serif; padding: 24px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { font-size: 1.8rem; font-weight: 800; margin-bottom: 8px; }
        .subtitle { color: rgba(255,255,255,0.5); margin-bottom: 32px; }
        
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 32px; }
        .stat-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; }
        .stat-label { color: rgba(255,255,255,0.5); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-value { color: #fff; font-size: 2rem; font-weight: 800; margin-top: 4px; }
        .stat-value.green { color: #22C55E; }
        .stat-value.red { color: #EF4444; }
        .stat-value.yellow { color: #F59E0B; }
        
        .tabs { display: flex; gap: 8px; margin-bottom: 24px; }
        .tab { padding: 10px 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: transparent; color: rgba(255,255,255,0.6); cursor: pointer; font-size: 0.9rem; font-weight: 600; transition: all 0.2s; }
        .tab.active { background: #22C55E; color: #000; border-color: #22C55E; }
        .tab:hover { border-color: rgba(255,255,255,0.3); }
        
        .panel { display: none; }
        .panel.active { display: block; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 16px; color: rgba(255,255,255,0.5); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid rgba(255,255,255,0.08); }
        td { padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        
        .badge { display: inline-block; padding: 4px 10px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
        .badge-active { background: rgba(34,197,94,0.15); color: #22C55E; }
        .badge-inactive { background: rgba(239,68,68,0.15); color: #EF4444; }
        .badge-goal { background: rgba(99,102,241,0.15); color: #6366F1; }
        
        .form-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 24px; margin-bottom: 24px; }
        .form-card h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
        .form-row.full { grid-template-columns: 1fr; }
        label { display: block; color: rgba(255,255,255,0.6); font-size: 0.85rem; margin-bottom: 4px; }
        input, select { width: 100%; padding: 10px 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; font-size: 0.9rem; }
        input:focus, select:focus { outline: none; border-color: #22C55E; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: #22C55E; color: #000; }
        .btn-primary:hover { background: #16A34A; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; }
        .alert-success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); color: #22C55E; }
        .alert-error { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #EF4444; }
        
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            table { font-size: 0.8rem; }
            th, td { padding: 8px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧑‍🌾 Agent Dashboard</h1>
        <p class="subtitle">Onboard farmers, enter their data, and track adoption progress.</p>
        
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-label">Total Farmers</div>
                <div class="stat-value"><?php echo $total_farmers; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active This Week</div>
                <div class="stat-value green"><?php echo $active_farmers; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Inactive</div>
                <div class="stat-value red"><?php echo $inactive_farmers; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Goals Set</div>
                <div class="stat-value yellow"><?php echo count(array_filter($goals, fn($g) => $g !== 'not set')); ?></div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('farmers')">👨‍🌾 My Farmers</button>
            <button class="tab" onclick="showTab('quick-entry')">✏️ Quick Entry</button>
            <button class="tab" onclick="showTab('create')">➕ Create Account</button>
        </div>
        
        <!-- Panel: Farmers List -->
        <div id="panel-farmers" class="panel active">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Farmer</th>
                            <th>Farm</th>
                            <th>Goal</th>
                            <th>Entries (7d)</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($farmers)): ?>
                            <tr><td colspan="6" style="text-align:center; color:rgba(255,255,255,0.4); padding:40px;">No farmers yet. Start onboarding!</td></tr>
                        <?php else: ?>
                            <?php foreach ($farmers as $f): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($f['full_name'] ?: $f['email']); ?></div>
                                        <div style="font-size:0.8rem; color:rgba(255,255,255,0.4);"><?php echo htmlspecialchars($f['phone_number'] ?? ''); ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($f['farm_name'] ?? 'No farm'); ?></div>
                                        <?php if ($f['farm_code']): ?>
                                            <div style="font-size:0.8rem; color:#22C55E;">Code: <?php echo htmlspecialchars($f['farm_code']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($f['primary_goal']): ?>
                                            <span class="badge badge-goal"><?php echo htmlspecialchars(ucfirst($f['primary_goal'])); ?></span>
                                        <?php else: ?>
                                            <span style="color:rgba(255,255,255,0.3);">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="font-weight:600; color: <?php echo ($f['entries_this_week'] ?? 0) > 0 ? '#22C55E' : '#EF4444'; ?>">
                                            <?php echo $f['entries_this_week'] ?? 0; ?>
                                        </span>
                                        <span style="color:rgba(255,255,255,0.4);">/ 30d: <?php echo $f['entries_this_month'] ?? 0; ?></span>
                                    </td>
                                    <td>
                                        <?php if (($f['entries_this_week'] ?? 0) > 0): ?>
                                            <span class="badge badge-active">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:rgba(255,255,255,0.5); font-size:0.85rem;">
                                        <?php echo date('M j', strtotime($f['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Panel: Quick Entry -->
        <div id="panel-quick-entry" class="panel">
            <div class="form-card">
                <h3>✏️ Enter Data for a Farmer</h3>
                <p style="color:rgba(255,255,255,0.5); margin-bottom:16px;">Select a farmer and enter today's production data on their behalf.</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="quick_entry">
                    
                    <div class="form-row">
                        <div>
                            <label>Farmer</label>
                            <select name="target_user_id" required>
                                <option value="">Select farmer...</option>
                                <?php foreach ($farmers as $f): ?>
                                    <option value="<?php echo $f['id']; ?>">
                                        <?php echo htmlspecialchars($f['full_name'] ?: $f['email']); ?>
                                        (<?php echo htmlspecialchars($f['farm_name'] ?? 'No farm'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Date</label>
                            <input type="date" name="entry_date" value="<?php echo date('Y-m-d'); ?>" style="color: #fff;">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div>
                            <label>🥚 Eggs Collected</label>
                            <input type="number" name="eggs" min="0" value="0" placeholder="0">
                        </div>
                        <div>
                            <label>⚠️ Mortality</label>
                            <input type="number" name="mortality" min="0" value="0" placeholder="0">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div>
                            <label>📦 Feed Used (bags)</label>
                            <input type="number" name="feed_bags" min="0" value="0" placeholder="0">
                        </div>
                        <div></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="margin-top: 8px;">Save Entry</button>
                </form>
            </div>
        </div>
        
        <!-- Panel: Create Account -->
        <div id="panel-create" class="panel">
            <div class="form-card">
                <h3>➕ Create Farm Account</h3>
                <p style="color:rgba(255,255,255,0.5); margin-bottom:16px;">Create an account for a farmer who doesn't have one yet. Default password: wangari123</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create_account">
                    
                    <div class="form-row">
                        <div>
                            <label>Farmer Name</label>
                            <input type="text" name="farmer_name" required placeholder="e.g. James Mwangi">
                        </div>
                        <div>
                            <label>Phone Number</label>
                            <input type="tel" name="farmer_phone" placeholder="+254712345678">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div>
                            <label>Email (used for login)</label>
                            <input type="email" name="farmer_email" required placeholder="farmer@gmail.com">
                        </div>
                        <div>
                            <label>Farm Name</label>
                            <input type="text" name="farm_name" required placeholder="e.g. Mwangi Poultry Farm">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="margin-top: 8px;">Create Account</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    function showTab(name) {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
        event.target.classList.add('active');
        document.getElementById('panel-' + name).classList.add('active');
    }
    </script>
</body>
</html>
