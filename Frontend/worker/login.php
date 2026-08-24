<?php
/**
 * Worker Login - Connect to Farm with Code
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

// Already logged in as worker?
if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'field_worker') {
    header('Location: /Frontend/worker/dashboard.php');
    exit;
}

$page_title = 'Worker Login — Wangari';
$errors = [];
$step = 'login'; // login or connect

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Step 1: Worker Login
    if ($action === 'worker_login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $errors[] = 'Username and password are required.';
        } else {
            $pdo = getDB();
            if ($pdo) {
                try {
                    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                        $stmt = $pdo->prepare("SELECT id, username, password, role, full_name FROM users WHERE LOWER(email) = ? AND role = 'field_worker' LIMIT 1");
                        $stmt->execute([strtolower($username)]);
                    } else {
                        $stmt = $pdo->prepare("SELECT id, username, password, role, full_name FROM users WHERE username = ? AND role = 'field_worker' LIMIT 1");
                        $stmt->execute([$username]);
                    }
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user && password_verify($password, $user['password'])) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['full_name'] = $user['full_name'];
                        
                        // Check if worker is already connected to a farm
                        $linkStmt = $pdo->prepare("SELECT farm_user_id FROM worker_farm_links WHERE worker_user_id = ? AND is_active = 1 LIMIT 1");
                        $linkStmt->execute([$user['id']]);
                        $link = $linkStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($link) {
                            $_SESSION['farm_user_id'] = $link['farm_user_id'];
                            header('Location: /Frontend/worker/dashboard.php');
                            exit;
                        } else {
                            $step = 'connect';
                        }
                    } else {
                        $errors[] = 'Invalid credentials or not a worker account.';
                    }
                } catch (Exception $e) {
                    $errors[] = 'Login error: ' . $e->getMessage();
                }
            }
        }
    }
    
    // Step 2: Connect to Farm with Code
    if ($action === 'connect_farm') {
        $code = strtoupper(trim($_POST['farm_code'] ?? ''));
        
        if (empty($code)) {
            $errors[] = 'Farm code is required.';
        } else {
            $pdo = getDB();
            if ($pdo) {
                try {
                    // Find the code
                    $stmt = $pdo->prepare("SELECT * FROM worker_connection_codes WHERE code = ? AND is_active = 1");
                    $stmt->execute([$code]);
                    $codeRecord = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$codeRecord) {
                        $errors[] = 'Invalid code. Please check with your farm manager.';
                    } elseif ($codeRecord['expires_at'] && strtotime($codeRecord['expires_at']) < time()) {
                        $errors[] = 'This code has expired. Ask your farm manager for a new one.';
                    } elseif ($codeRecord['uses_count'] >= $codeRecord['max_uses']) {
                        $errors[] = 'This code has reached its maximum uses.';
                    } else {
                        // Link worker to farm
                        $linkStmt = $pdo->prepare("INSERT INTO worker_farm_links (worker_user_id, farm_user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE is_active = 1");
                        $linkStmt->execute([(int)$_SESSION['user_id'], $codeRecord['farm_user_id']]);
                        
                        // Update code usage
                        $pdo->prepare("UPDATE worker_connection_codes SET uses_count = uses_count + 1 WHERE id = ?")->execute([$codeRecord['id']]);
                        
                        $_SESSION['farm_user_id'] = $codeRecord['farm_user_id'];
                        header('Location: /Frontend/worker/dashboard.php');
                        exit;
                    }
                } catch (Exception $e) {
                    $errors[] = 'Connection error: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0B1220 0%, #14532D 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-card { background: #fff; border-radius: 20px; padding: 40px; width: 100%; max-width: 420px; box-shadow: 0 25px 50px rgba(0,0,0,0.25); }
        .login-header { text-align: center; margin-bottom: 32px; }
        .login-header img { height: 48px; margin-bottom: 16px; }
        .login-header h1 { font-size: 1.5rem; font-weight: 700; color: #0F172A; margin-bottom: 8px; }
        .login-header p { font-size: 0.9rem; color: #64748B; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-group input { width: 100%; padding: 14px 16px; border: 2px solid #E5E7EB; border-radius: 12px; font-size: 1rem; transition: all 0.2s; outline: none; }
        .form-group input:focus { border-color: #22C55E; box-shadow: 0 0 0 3px rgba(34,197,94,0.1); }
        .btn { width: 100%; padding: 14px; border: none; border-radius: 12px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: #22C55E; color: #fff; }
        .btn-primary:hover { background: #16A34A; transform: translateY(-1px); }
        .btn-outline { background: #fff; color: #374151; border: 2px solid #E5E7EB; margin-top: 12px; }
        .btn-outline:hover { border-color: #22C55E; color: #16A34A; }
        .error { background: #FEE2E2; border: 1px solid #FCA5A5; border-radius: 12px; padding: 12px 16px; margin-bottom: 20px; color: #991B1B; font-size: 0.9rem; }
        .code-input { text-align: center; font-size: 2rem; font-weight: 700; letter-spacing: 8px; text-transform: uppercase; }
        .divider { display: flex; align-items: center; margin: 24px 0; color: #9CA3AF; font-size: 0.85rem; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #E5E7EB; }
        .divider span { padding: 0 12px; }
        .worker-badge { display: inline-flex; align-items: center; gap: 6px; background: #F0FDF4; color: #166534; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; margin-bottom: 16px; }
        .worker-badge svg { width: 14px; height: 14px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <img src="/Frontend/images/wangari-logo.png" alt="Wangari">
            <div class="worker-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Worker Portal
            </div>
            <h1><?= $step === 'connect' ? 'Connect to Farm' : 'Welcome Back' ?></h1>
            <p><?= $step === 'connect' ? 'Enter the code from your farm manager' : 'Sign in to your worker account' ?></p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $e): ?>
                    <div><?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 'login'): ?>
        <form method="POST">
            <input type="hidden" name="action" value="worker_login">
            
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" required placeholder="your_username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>
        
        <div class="divider"><span>or</span></div>
        
        <a href="/Frontend/pages/register.php?role=field_worker" class="btn btn-outline">Create Worker Account</a>
        
        <?php else: ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="connect_farm">
            
            <div class="form-group">
                <label>Farm Code</label>
                <input type="text" name="farm_code" class="code-input" required placeholder="XXXX-XXXX" maxlength="9" autocomplete="off" autofocus>
            </div>
            
            <button type="submit" class="btn btn-primary">Connect to Farm</button>
        </form>
        
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 24px;">
            <a href="/Frontend/pages/login.php" style="color: #64748B; font-size: 0.85rem; text-decoration: none;">← Back to main login</a>
        </div>
    </div>
</body>
</html>
