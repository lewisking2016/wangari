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
require_once dirname(__DIR__, 2) . '/Backend/config/email_policy.php';

$page_title = 'Create Account — Wangari';
$csrf_token = function_exists('generateCSRFToken') ? generateCSRFToken() : ($_SESSION['csrf_token'] ?? '');

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    header('Location: ' . wangariAuthRedirectPath((string)$role));
    exit;
}

$errors = [];
$step = (int)($_GET['step'] ?? 1);
$role_choice = $_GET['role'] ?? '';
$code = $_GET['code'] ?? '';
$googleRegistration = $_SESSION['google_registration_profile'] ?? [];
$googleNotice = '';
$googlePrefillName = trim((string)($googleRegistration['full_name'] ?? ''));
$googlePrefillEmail = trim((string)($googleRegistration['email'] ?? ''));
$googlePrefillId = trim((string)($googleRegistration['google_id'] ?? ''));
$googlePrefillPicture = trim((string)($googleRegistration['profile_pic'] ?? ''));

if (!empty($_SESSION['google_login_error'])) {
    $googleNotice = (string) $_SESSION['google_login_error'];
    unset($_SESSION['google_login_error']);
} elseif (($_GET['google'] ?? '') === 'required') {
    $googleNotice = 'Your Google account is not linked yet. Pick Farm Owner or Join as Worker to finish creating your account.';
} elseif ($googlePrefillEmail !== '') {
    $googleNotice = 'Google connected. Complete the form below and choose Farm Owner or Join as Worker to finish linking this account.';
}

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
    $googleId = trim($_POST['google_id'] ?? ($googlePrefillId ?? ''));
    $googlePicture = trim($_POST['google_picture'] ?? ($googlePrefillPicture ?? ''));
    $googleEmail = $googlePrefillEmail !== '' ? $googlePrefillEmail : '';

    if (empty($fullName)) $errors[] = 'Full name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($errors) && !wangariIsAllowedEmail($email)) $errors[] = 'Only Gmail and Outlook email addresses are allowed';
    if (empty($errors) && !empty($googleId) && $googleEmail !== '' && wangariNormalizeEmail($email) !== wangariNormalizeEmail($googleEmail)) {
        $errors[] = 'Please use the same Google email to complete this account';
    }
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
            $emailVariants = wangariEmailVariants($email);
            $placeholders = implode(',', array_fill(0, count($emailVariants), '?'));
            $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) IN ($placeholders)");
            $stmt->execute($emailVariants);
            if (!empty($googleId)) {
                $googleStmt = $pdo->prepare("SELECT id FROM users WHERE google_id = ? LIMIT 1");
                $googleStmt->execute([$googleId]);
                if ($googleStmt->fetch()) {
                    $errors[] = 'This Google account is already linked to an account';
                }
            }
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
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role, google_id, profile_pic, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
                $stmt->execute([
                    $username,
                    wangariNormalizeEmail($email),
                    $password_hash,
                    $fullName,
                    $phone,
                    $mappedRole,
                    $googleId !== '' ? $googleId : null,
                    $googlePicture !== '' ? $googlePicture : null,
                ]);
                $userId = $pdo->lastInsertId();

                // Rotate the session after account creation to prevent fixation.
                require_once dirname(__DIR__, 2) . '/Backend/config/session.php';
wangariStartSession();
                session_regenerate_id(true);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $mappedRole;
                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = wangariNormalizeEmail($email);
                unset($_SESSION['google_registration_profile']);

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

                    // Redirect to onboarding wizard (Goal Picker)
                    header('Location: /Frontend/pages/onboarding.php?welcome=1');
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
                        header('Location: ' . wangariAuthRedirectPath($mappedRole) . '?welcome=1');
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

// Persist the token and any Google registration profile before rendering.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
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
        .role-card .icon { margin-bottom: 16px; display: flex; justify-content: center; }
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
        @media (max-width: 640px) {
            .role-cards { grid-template-columns: 1fr; }
            .form-row { flex-direction: column; gap: 12px; }
            .xai-auth-card { padding: 24px 20px; margin: 16px; }
            .xai-auth-brand { padding: 20px; }
            .xai-auth-brand h2 { font-size: 1.3rem; }
            .step-dots { gap: 6px; }
            .step-dot { width: 8px; height: 8px; }
        }
    </style>
    <link rel="stylesheet" href="/Frontend/assets/css/mobile-fix.css">
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

            <?php if (!empty($googleNotice)): ?>
                <div style="padding: 16px; background: #ECFDF5; border: 1px solid #86EFAC; border-radius: 12px; color: #166534; margin-bottom: 24px; font-size: 0.9rem;">
                    <?php echo htmlspecialchars($googleNotice, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

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
                        <span class="icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
                        <h3>Farm Owner</h3>
                        <p>Create your own farm, manage your team, track everything.</p>
                    </div>
                    <div class="role-card" onclick="selectRole('worker')" id="card-worker">
                        <span class="check" id="check-worker"></span>
                        <span class="icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                        <h3>Join as Worker</h3>
                        <p>Enter a farm code from your admin to join their team.</p>
                    </div>
                </div>
                <div id="step1-error" style="display:none;color:#DC2626;font-size:0.85rem;margin-top:12px;padding:10px 14px;background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;"></div>

                <a href="/Frontend/auth/google/login.php?flow=register" class="xai-btn" style="display:flex;align-items:center;justify-content:center;gap:12px;width:100%;background:#000000;border:1.5px solid rgba(255,255,255,0.25);border-radius:12px;color:#ffffff;text-decoration:none;font-size:0.95rem;font-weight:600;padding:14px;box-shadow:0 4px 14px rgba(0,0,0,0.4);transition:all 0.2s ease;margin-bottom:12px;" onmouseover="this.style.background='#18181b'; this.style.borderColor='rgba(255,255,255,0.4)';" onmouseout="this.style.background='#000000'; this.style.borderColor='rgba(255,255,255,0.25)';">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span>Continue with Google</span>
                </a>
                <p style="font-size:0.82rem;color:#64748B;margin:0 0 18px 0;">We will prefill your name and email, then you choose Farm Owner or Join as Worker.</p>

                <button class="xai-btn xai-btn-primary xai-btn-lg" style="width:100%;" onclick="goToStep2()" id="step1-btn">
                    Continue
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>

                <div class="xai-form-footer">
                    Already have an account? <a href="/Frontend/pages/login.php">Sign In</a>
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
                    <input type="hidden" name="google_id" value="<?php echo htmlspecialchars($googlePrefillId, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="google_picture" value="<?php echo htmlspecialchars($googlePrefillPicture, ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="xai-form-group">
                        <label class="xai-form-label">Farm Name</label>
                        <input type="text" name="farm_name" class="xai-form-input" placeholder="e.g. Wangari Main Farm" required>
                    </div>
                    <div class="xai-form-group">
                        <label class="xai-form-label">Full Name</label>
                        <input type="text" name="full_name" class="xai-form-input" placeholder="Your full name" required value="<?php echo htmlspecialchars($googlePrefillName, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-row">
                        <div class="xai-form-group">
                            <label class="xai-form-label">Email</label>
                            <input type="email" name="email" class="xai-form-input" placeholder="you@email.com" required value="<?php echo htmlspecialchars($googlePrefillEmail, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $googlePrefillEmail !== '' ? 'readonly' : ''; ?>>
                            <?php if ($googlePrefillEmail !== ''): ?><small style="display:block;margin-top:8px;color:#64748B;">This email came from your Google account.</small><?php endif; ?>
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
                    <input type="hidden" name="google_id" value="<?php echo htmlspecialchars($googlePrefillId, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="google_picture" value="<?php echo htmlspecialchars($googlePrefillPicture, ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="xai-form-group">
                        <label class="xai-form-label">Full Name</label>
                        <input type="text" name="full_name" class="xai-form-input" placeholder="Your full name" required value="<?php echo htmlspecialchars($googlePrefillName, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-row">
                        <div class="xai-form-group">
                            <label class="xai-form-label">Email</label>
                            <input type="email" name="email" class="xai-form-input" placeholder="you@email.com" required value="<?php echo htmlspecialchars($googlePrefillEmail, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $googlePrefillEmail !== '' ? 'readonly' : ''; ?>>
                            <?php if ($googlePrefillEmail !== ''): ?><small style="display:block;margin-top:8px;color:#64748B;">This email came from your Google account.</small><?php endif; ?>
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
    if (!selectedRole) {
        var err = document.getElementById('step1-error');
        if (err) { err.textContent = 'Please select whether you are a Farm Owner or Worker.'; err.style.display = 'block'; }
        return;
    }
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
        const res = await fetch('/Backend/api/farm_codes.php?action=validate_code', {
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
