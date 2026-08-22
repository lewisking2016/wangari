<?php
/**
 * Wangari Registration — Role-Based Onboarding
 * Step 1: Choose "Farm Owner" or "Join as Worker"
 * Step 2a (Owner): Create account + farm name
 * Step 2b (Worker): Enter farm code → Create account → Join farm
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once dirname(__DIR__, 2) . '/Backend/config/security.php';

$page_title = 'Create Account — Wangari';
$csrf_token = function_exists('generateCSRFToken') ? generateCSRFToken() : ($_SESSION['csrf_token'] ?? '');

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    if (in_array($role, ['super_admin','farm_manager','stock_manager','sales_staff'])) {
        header('Location: /Frontend/admin/dashboard.php');
    } else {
        header('Location: /Frontend/index.php');
    }
    exit;
}

$errors = [];
$step = (int)($_GET['step'] ?? 1);
$role_choice = $_GET['role'] ?? '';
$code = $_GET['code'] ?? '';

// ── Handle form submission ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please refresh and try again.';
    }

    $role_choice = trim($_POST['role_choice'] ?? '');
    $code = trim($_POST['farm_code'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $farmName = trim($_POST['farm_name'] ?? '');

    if (empty($fullName)) $errors[] = 'Full name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($phone)) $errors[] = 'Phone number is required';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    if ($password !== $passwordConfirm) $errors[] = 'Passwords do not match';
    if ($role_choice !== 'owner' && $role_choice !== 'worker') $errors[] = 'Please select a role';

    if ($role_choice === 'worker' && empty($code)) {
        $errors[] = 'Farm code is required to join as a worker';
    }

    if ($role_choice === 'owner' && empty($farmName)) {
        $errors[] = 'Farm name is required for farm owners';
    }

    if (empty($errors)) {
        $pdo = getDB();
        try {
            // Check email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email already exists';
            } else {
                // Generate username from email
                $username = strtolower(str_replace(['@', '.'], ['', ''], explode('@', $email)[0]));
                $username = preg_replace('/[^a-z0-9]/', '', $username);
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $checkStmt->execute([$username]);
                if ($checkStmt->fetch()) $username .= rand(100, 999);

                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $mappedRole = $role_choice === 'owner' ? 'farm_manager' : 'customer';

                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
                $stmt->execute([$username, $email, $password_hash, $fullName, $phone, $mappedRole]);
                $userId = $pdo->lastInsertId();

                // Start session
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $mappedRole;
                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;

                if ($role_choice === 'owner') {
                    // Create farm
                    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                    $farmCode = 'WGRI-';
                    for ($i = 0; $i < 12; $i++) {
                        if ($i > 0 && $i % 4 === 0) $farmCode .= '-';
                        $farmCode .= $chars[random_int(0, strlen($chars) - 1)];
                    }

                    $pdo->prepare("INSERT INTO farms (name, owner_id, farm_code, created_at) VALUES (?, ?, ?, NOW())")
                        ->execute([$farmName, $userId, $farmCode]);
                    $farmId = $pdo->lastInsertId();
                    $pdo->prepare("INSERT INTO farm_members (farm_id, user_id, role, status, joined_at) VALUES (?, ?, 'farm_owner', 'active', NOW())")
                        ->execute([$farmId, $userId]);

                    header('Location: /Frontend/admin/dashboard.php?welcome=1');
                    exit;
                } else {
                    // Worker: join farm via code (they go to the farm system, not admin)
                    $stmt = $pdo->prepare("SELECT fc.*, f.id as farm_id, f.name as farm_name FROM farm_codes fc JOIN farms f ON fc.farm_id = f.id WHERE fc.code = ? AND fc.is_active = 1");
                    $stmt->execute([$code]);
                    $farmCode = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($farmCode && ($farmCode['expires_at'] === null || strtotime($farmCode['expires_at']) > time()) && $farmCode['current_uses'] < $farmCode['max_uses']) {
                        $roleForFarm = $farmCode['role'];

                        $pdo->prepare("UPDATE farm_codes SET current_uses = current_uses + 1 WHERE id = ?")->execute([$farmCode['id']]);
                        $pdo->prepare("INSERT INTO farm_members (farm_id, user_id, role, status, joined_at) VALUES (?, ?, ?, 'active', NOW())")
                            ->execute([$farmCode['farm_id'], $userId, $roleForFarm]);
                        $pdo->prepare("INSERT INTO farm_join_requests (farm_id, user_id, code_used, requested_role, status, created_at) VALUES (?, ?, ?, ?, 'approved', NOW())")
                            ->execute([$farmCode['farm_id'], $userId, $code, $roleForFarm]);
                        $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$roleForFarm, $userId]);

                        $_SESSION['role'] = $roleForFarm;
                    }

                    // Workers go to the farm system, not admin dashboard
                    header('Location: /Frontend/admin/dashboard.php?welcome=1');
                    exit;
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Registration failed. Please try again.';
            error_log("Registration error: " . $e->getMessage());
        }
    }
    // If errors, fall through to show form with step 2
    $step = 2;
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
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Frontend/assets/css/xai-public.css">
    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
    <style>
        .role-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 32px 0; }
        .role-card {
            padding: 32px 24px; border: 2px solid #E2E8F0;
            border-radius: 16px; cursor: pointer; text-align: center;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            background: #FFFFFF; position: relative; overflow: hidden;
        }
        .role-card:hover { border-color: #22C55E; background: #F0FDF4; transform: translateY(-2px); }
        .role-card.selected { border-color: #22C55E; background: #DCFCE7; }
        .role-card .icon { font-size: 40px; margin-bottom: 16px; display: block; }
        .role-card h3 { font-size: 18px; font-weight: 700; margin-bottom: 8px; color: #0F172A; }
        .role-card p { font-size: 13px; color: #64748B; line-height: 1.5; }
        .role-card .check {
            position: absolute; top: 12px; right: 12px;
            width: 24px; height: 24px; border-radius: 50%;
            border: 2px solid #CBD5E1;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s ease;
        }
        .role-card.selected .check { background: #22C55E; border-color: #22C55E; }

        .step-indicator { display: flex; gap: 8px; margin-bottom: 24px; }
        .step-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #CBD5E1; transition: all 0.3s ease;
        }
        .step-dot.active { background: #22C55E; width: 32px; border-radius: 4px; }
        .step-dot.done { background: #22C55E; }

        .code-input-group {
            display: flex; gap: 0; margin: 20px 0;
        }
        .code-input-group input {
            flex: 1; padding: 16px 20px; font-size: 18px;
            font-family: 'Courier New', monospace; letter-spacing: 2px;
            background: #F8FAFC; border: 2px solid #E2E8F0;
            border-right: none; border-radius: 12px 0 0 12px; color: #0F172A;
            outline: none; text-transform: uppercase;
        }
        .code-input-group input:focus { border-color: #22C55E; }
        .code-input-group button {
            padding: 16px 24px; background: #16A34A; border: none;
            border-radius: 0 12px 12px 0; color: #fff; font-weight: 600;
            cursor: pointer; white-space: nowrap; font-family: inherit;
        }

        .code-result {
            padding: 16px; border-radius: 12px; margin: 12px 0;
            font-size: 14px; display: none;
        }
        .code-result.valid { display: block; background: #DCFCE7; border: 1px solid #86EFAC; color: #166534; }
        .code-result.invalid { display: block; background: #FEE2E2; border: 1px solid #FCA5A5; color: #991B1B; }

        .form-row { display: flex; gap: 16px; }
        .form-row .xai-form-group { flex: 1; }
        .hidden { display: none !important; }
    </style>
</head>
<body>
<div class="xai-auth">
    <div class="xai-auth-brand">
        <a href="/" class="xai-nav-brand" style="margin-bottom: 60px;">
            <img src="/Frontend/images/wangari-logo.png" alt="Wangari" style="height: 48px;">
            Wangari<span>.</span>
        </a>
        <h2>Start managing your <span style="font-family: var(--font-serif); font-style: italic;">farm</span></h2>
        <p>Create your account and start tracking poultry, livestock, inventory, sales and finances — all in one place.</p>
    </div>

    <div class="xai-auth-form">
        <div class="xai-auth-card" style="max-width: 520px;">

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step-dot <?php echo $step >= 1 ? 'active' : ''; ?>" id="dot1"></div>
                <div class="step-dot <?php echo $step >= 2 ? 'active' : ''; ?>" id="dot2"></div>
            </div>

            <?php if (!empty($errors)): ?>
                <div style="padding: 16px; background: #FEE2E2; border: 1px solid #FCA5A5; border-radius: 12px; color: #991B1B; margin-bottom: 24px; font-size: 0.9rem;">
                    <?php foreach ($errors as $e): ?>
                        <div>• <?php echo htmlspecialchars($e); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- ═══════════ STEP 1: Choose Role ═══════════ -->
            <div id="step1" class="<?php echo $step === 2 ? 'hidden' : ''; ?>">
                <h1 style="font-size: 1.6rem; margin-bottom: 4px;">Who are you?</h1>
                <p style="color: #64748B; font-size: 0.9rem;">Choose how you'll use Wangari to get started.</p>

                <div class="role-cards">
                    <div class="role-card" onclick="selectRole('owner')" id="card-owner">
                        <span class="check" id="check-owner"></span>
                        <span class="icon">👨‍🌾</span>
                        <h3>Farm Owner</h3>
                        <p>Create your own farm, manage your team, track everything.</p>
                    </div>
                    <div class="role-card" onclick="selectRole('worker')" id="card-worker">
                        <span class="check" id="check-worker"></span>
                        <span class="icon">👷</span>
                        <h3>Join as Worker</h3>
                        <p>Enter a farm code from your admin to join their team.</p>
                    </div>
                </div>

                <button class="xai-btn xai-btn-primary xai-btn-lg" style="width:100%;" onclick="goToStep2()" id="step1-btn">
                    Continue
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>

                <div class="xai-form-footer">
                    Already have an account? <a href="login.php">Sign In</a>
                </div>
            </div>

            <!-- ═══════════ STEP 2a: Owner Registration ═══════════ -->
            <div id="step2-owner" class="hidden">
                <button onclick="backToStep1()" style="background:none;border:none;color:#64748B;cursor:pointer;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:4px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    Back
                </button>
                <h1>Create Your Farm</h1>
                <p style="color: #64748B; font-size: 0.9rem; margin-bottom: 24px;">Set up your farm and start inviting your team.</p>

                <form method="POST" id="owner-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="role_choice" value="owner" id="rc-owner">
                    <input type="hidden" name="farm_code" value="" id="fc-owner">

                    <div class="xai-form-group">
                        <label class="xai-form-label">Farm Name</label>
                        <input type="text" name="farm_name" class="xai-form-input" placeholder="e.g. Wangari Main Farm" required>
                    </div>
                    <div class="xai-form-group">
                        <label class="xai-form-label">Full Name</label>
                        <input type="text" name="full_name" class="xai-form-input" placeholder="Your full name" required>
                    </div>
                    <div class="form-row">
                        <div class="xai-form-group">
                            <label class="xai-form-label">Email</label>
                            <input type="email" name="email" class="xai-form-input" placeholder="you@email.com" required>
                        </div>
                        <div class="xai-form-group">
                            <label class="xai-form-label">Phone</label>
                            <input type="tel" name="phone" class="xai-form-input" placeholder="+254 7XX XXX XXX" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="xai-form-group">
                            <label class="xai-form-label">Password</label>
                            <input type="password" name="password" class="xai-form-input" placeholder="Min 6 characters" required minlength="6">
                        </div>
                        <div class="xai-form-group">
                            <label class="xai-form-label">Confirm Password</label>
                            <input type="password" name="password_confirm" class="xai-form-input" placeholder="Re-enter password" required>
                        </div>
                    </div>

                    <button type="submit" name="register_submit" value="1" class="xai-btn xai-btn-primary xai-btn-lg" style="width:100%; margin-top: 8px;">
                        Create Farm & Account
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </button>
                </form>
            </div>

            <!-- ═══════════ STEP 2b: Worker — Enter Farm Code ═══════════ -->
            <div id="step2-worker-code" class="hidden">
                <button onclick="backToStep1()" style="background:none;border:none;color:#64748B;cursor:pointer;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:4px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    Back
                </button>
                <h1>Enter Farm Code</h1>
                <p style="color: #64748B; font-size: 0.9rem;">Ask your farm admin for the invite code, then paste it below.</p>

                <div class="code-input-group">
                    <input type="text" id="farm-code-input" placeholder="WGRI-XXXX-XXXX-XXXX" maxlength="24" autocomplete="off" spellcheck="false">
                    <button onclick="validateCode()">Verify</button>
                </div>

                <div class="code-result" id="code-result"></div>

                <button class="xai-btn xai-btn-primary xai-btn-lg" style="width:100%; margin-top: 16px;" id="join-farm-btn" onclick="goToWorkerForm()" disabled>
                    Continue to Account Setup
                </button>
            </div>

            <!-- ═══════════ STEP 2c: Worker — Account Details ═══════════ -->
            <div id="step2-worker-form" class="hidden">
                <button onclick="showOnly('step2-worker-code')" style="background:none;border:none;color:rgba(240,253,244,0.5);cursor:pointer;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:4px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    Back
                </button>
                <h1>Join <span id="farm-name-display" style="color:#16A34A"></span></h1>
                <p style="color: #64748B; font-size: 0.9rem; margin-bottom: 24px;">Create your account to start working.</p>

                <form method="POST" id="worker-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="role_choice" value="worker" id="rc-worker">
                    <input type="hidden" name="farm_code" value="" id="fc-worker">

                    <div class="xai-form-group">
                        <label class="xai-form-label">Full Name</label>
                        <input type="text" name="full_name" class="xai-form-input" placeholder="Your full name" required>
                    </div>
                    <div class="form-row">
                        <div class="xai-form-group">
                            <label class="xai-form-label">Email</label>
                            <input type="email" name="email" class="xai-form-input" placeholder="you@email.com" required>
                        </div>
                        <div class="xai-form-group">
                            <label class="xai-form-label">Phone</label>
                            <input type="tel" name="phone" class="xai-form-input" placeholder="+254 7XX XXX XXX" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="xai-form-group">
                            <label class="xai-form-label">Password</label>
                            <input type="password" name="password" class="xai-form-input" placeholder="Min 6 characters" required minlength="6">
                        </div>
                        <div class="xai-form-group">
                            <label class="xai-form-label">Confirm Password</label>
                            <input type="password" name="password_confirm" class="xai-form-input" placeholder="Re-enter password" required>
                        </div>
                    </div>

                    <button type="submit" name="register_submit" value="1" class="xai-btn xai-btn-primary xai-btn-lg" style="width:100%; margin-top: 8px;">
                        Join Farm
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
let selectedRole = '';
let validatedCode = '';

function selectRole(role) {
    selectedRole = role;
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('card-' + role).classList.add('selected');
}

function goToStep2() {
    if (!selectedRole) return;
    document.getElementById('dot1').classList.remove('active');
    document.getElementById('dot1').classList.add('done');
    document.getElementById('dot2').classList.add('active');
    document.getElementById('step1').classList.add('hidden');

    if (selectedRole === 'owner') {
        document.getElementById('step2-owner').classList.remove('hidden');
    } else {
        document.getElementById('step2-worker-code').classList.remove('hidden');
    }
}

function backToStep1() {
    document.getElementById('dot1').classList.add('active');
    document.getElementById('dot1').classList.remove('done');
    document.getElementById('dot2').classList.remove('active');
    document.getElementById('step1').classList.remove('hidden');
    document.getElementById('step2-owner').classList.add('hidden');
    document.getElementById('step2-worker-code').classList.add('hidden');
    document.getElementById('step2-worker-form').classList.add('hidden');
}

function showOnly(id) {
    ['step2-owner', 'step2-worker-code', 'step2-worker-form'].forEach(s => {
        document.getElementById(s).classList.add('hidden');
    });
    document.getElementById(id).classList.remove('hidden');
}

function goToWorkerForm() {
    if (!validatedCode) return;
    document.getElementById('fc-worker').value = validatedCode;
    document.getElementById('farm-name-display').textContent = document.getElementById('code-farm-name').textContent;
    showOnly('step2-worker-form');
}

async function validateCode() {
    const input = document.getElementById('farm-code-input');
    const result = document.getElementById('code-result');
    const btn = document.getElementById('join-farm-btn');
    const code = input.value.trim().toUpperCase();
    input.value = code;

    if (code.length < 6) {
        result.className = 'code-result invalid';
        result.style.display = 'block';
        result.textContent = 'Please enter a valid farm code';
        return;
    }

    result.className = 'code-result';
    result.style.display = 'block';
    result.style.color = '#64748B';
    result.textContent = 'Verifying code...';

    try {
        const res = await fetch('/api/farm_codes.php?action=validate_code', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: code })
        });
        const data = await res.json();

        if (data.valid) {
            result.className = 'code-result valid';
            result.innerHTML = '✅ Valid! You\'re joining <strong id="code-farm-name">' + data.farm_name + '</strong> as <strong>' + data.role.replace('_', ' ') + '</strong>';
            validatedCode = code;
            btn.disabled = false;
            btn.style.opacity = '1';
        } else {
            result.className = 'code-result invalid';
            result.textContent = '❌ ' + (data.error || 'Invalid code');
            validatedCode = '';
            btn.disabled = true;
            btn.style.opacity = '0.5';
        }
    } catch (e) {
        result.className = 'code-result invalid';
        result.textContent = '❌ Could not verify code. Check your connection.';
        btn.disabled = true;
    }
}

// Auto-format code input
document.getElementById('farm-code-input').addEventListener('input', function() {
    let v = this.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().slice(0, 16);
    const parts = [];
    for (let i = 0; i < v.length; i += 4) parts.push(v.slice(i, i + 4));
    this.value = parts.join('-');
    validatedCode = '';
    document.getElementById('join-farm-btn').disabled = true;
    document.getElementById('code-result').style.display = 'none';
});

// Enter to validate
document.getElementById('farm-code-input').addEventListener('keydown', e => {
    if (e.key === 'Enter') validateCode();
});

// Init from URL params
<?php if ($role_choice === 'worker' && $step === 2): ?>
selectedRole = 'worker';
document.getElementById('dot1').classList.remove('active');
document.getElementById('dot1').classList.add('done');
document.getElementById('dot2').classList.add('active');
document.getElementById('step1').classList.add('hidden');
document.getElementById('step2-worker-code').classList.remove('hidden');
<?php elseif ($step === 2): ?>
selectedRole = 'owner';
document.getElementById('dot1').classList.remove('active');
document.getElementById('dot1').classList.add('done');
document.getElementById('dot2').classList.add('active');
document.getElementById('step1').classList.add('hidden');
document.getElementById('step2-owner').classList.remove('hidden');
<?php endif; ?>
</script>
</body>
</html>
