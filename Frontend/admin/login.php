<?php
/**
 * Admin Login Page - Professional Split Screen Design
 */
declare(strict_types=1);

// Use temp session path
$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) session_save_path($temp_dir);
session_start();

$path_prefix = '../';
$page_title = 'Admin Portal - Wangari';

// Load frontend config
require_once __DIR__ . '/../includes/config.php';

$csrf_token = function_exists('generateCSRFToken') ? generateCSRFToken() : ($_SESSION['csrf_token'] ?? '');

function isSafeAdminRedirect(string $path): bool
{
    if ($path === '') {
        return false;
    }

    if (!str_starts_with($path, '/')) {
        return false;
    }

    if (str_starts_with($path, '//') || str_contains($path, '://') || str_contains($path, '\\')) {
        return false;
    }

    return str_starts_with($path, '/Frontend/admin/') || $path === '/wangariadmin';
}

// Redirect if already logged in as admin
if (!empty($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['super_admin','farm_manager','stock_manager','sales_staff'], true)) {
    header('Location: /Frontend/admin/dashboard.php');
    exit;
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please refresh and try again.';
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Please enter both username and password';
    } else {
        $pdo = getDB();
        try {
            $stmt = $pdo->prepare("SELECT id, username, password_hash, role, first_name FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                if (!in_array($user['role'], ['super_admin','farm_manager','stock_manager','sales_staff'], true)) {
                    $errors[] = 'Access denied. You do not have permission to use the admin panel.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['first_name'] = $user['first_name'];
                    logActivity($pdo, 'login', 'auth', "{$user['username']} logged in", (int)$user['id'], 'user');

                    $next = $_GET['next'] ?? '/Frontend/admin/products.php';
                    if (!isSafeAdminRedirect($next)) {
                        $next = '/Frontend/admin/products.php';
                    }
                    header('Location: ' . $next);
                    exit;
                }
            } else {
                $errors[] = 'Invalid username or password';
            }
        } catch (Exception $e) {
            $errors[] = 'System error occurred during login';
            if (defined('APP_DEBUG') && APP_DEBUG) error_log('Admin login error: ' . $e->getMessage());
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Frontend/assets/css/style.css">
    <style>
        :root {
            --admin-primary: #1B5E20;
            --admin-primary-light: #2E7D32;
            --admin-accent: #FFC107;
            --admin-text-main: #1e293b;
            --admin-text-muted: #64748b;
            --admin-bg-light: #f8fafc;
        }

        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            overflow: hidden;
        }

        .split-container {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        /* Branding Side */
        .branding-side {
            flex: 1.2;
            position: relative;
            background:
                radial-gradient(900px 480px at 80% -10%, rgba(208, 242, 76, 0.16) 0%, transparent 60%),
                radial-gradient(700px 500px at -10% 110%, rgba(0, 0, 0, 0.45) 0%, transparent 55%),
                linear-gradient(160deg, #04101f 0%, #0B2B1D 55%, #06190F 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .branding-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.6;
            filter: saturate(1.2) brightness(0.8);
        }

        .branding-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(27, 94, 32, 0.9) 0%, rgba(0, 0, 0, 0.4) 100%);
            z-index: 1;
        }

        .branding-content {
            position: relative;
            z-index: 2;
            color: #ffffff;
            padding: 60px;
            max-width: 600px;
            animation: slideUpFade 0.8s ease-out;
        }

        .branding-logo {
            height: 80px;
            width: auto;
            margin-bottom: 40px;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.1));
        }

        .branding-content h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 24px;
            color: #ffffff;
        }

        .branding-content p {
            font-size: 1.25rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 40px;
        }

        /* Login Side */
        .login-side {
            flex: 1;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            z-index: 10;
        }

        .login-form-wrapper {
            width: 100%;
            max-width: 400px;
            animation: fadeIn 1s ease-out;
        }

        .login-header {
            margin-bottom: 40px;
        }

        .login-header h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--admin-text-heading);
            margin-bottom: 8px;
        }

        .login-header p {
            color: var(--admin-text-muted);
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--admin-text-main);
            margin-bottom: 8px;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group i {
            position: absolute;
            left: 16px;
            color: var(--admin-text-muted);
            width: 20px;
            height: 20px;
            transition: color 0.3s ease;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            background: var(--admin-bg-light);
            border: 2px solid transparent;
            border-radius: 8px;
            font-size: 1rem;
            color: var(--admin-text-main);
            transition: all 0.3s ease;
            outline: none;
        }

        .form-control:focus {
            background: #ffffff;
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 4px rgba(27, 94, 32, 0.1);
        }

        .form-control:focus + i {
            color: var(--admin-primary);
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            font-size: 0.875rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--admin-text-muted);
            cursor: pointer;
        }

        .forgot-password {
            color: var(--admin-primary);
            font-weight: 600;
            text-decoration: none;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: var(--admin-primary);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(27, 94, 32, 0.2);
        }

        .btn-login:hover {
            background: var(--admin-primary-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(27, 94, 32, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            color: #991b1b;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Animations */
        @keyframes slideUpFade {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .branding-side { display: none; }
            .login-side { flex: 1; }
        }
    </style>
</head>
<body>
    <div class="split-container">
        <!-- Left Side: Branding -->
        <div class="branding-side">
            <div class="branding-overlay"></div>
            <div class="branding-content">
                <img src="/Frontend/images/wangari-logo.png" alt="Wangari" class="branding-logo">
                <h1>Smart Farming for a Sustainable Future.</h1>
                <p>Empowering poultry farmers with real-time insights and intelligent management tools.</p>
                <div style="display: flex; gap: 24px;">
                    <div style="text-align: center;">
                        <h4 style="font-size: 1.5rem; margin-bottom: 4px;">100%</h4>
                        <small style="text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;">Organic</small>
                    </div>
                    <div style="border-left: 1px solid rgba(255,255,255,0.3);"></div>
                    <div style="text-align: center;">
                        <h4 style="font-size: 1.5rem; margin-bottom: 4px;">Verified</h4>
                        <small style="text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;">Quality</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="login-side">
            <div class="login-form-wrapper">
                <div class="login-header">
                    <h2>Admin Portal</h2>
                    <p>Enter your credentials to manage your farm.</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <i data-lucide="alert-circle" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
                        <div>
                            <?php foreach($errors as $err): ?>
                                <div><?php echo htmlspecialchars($err); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="form-group">
                        <label class="form-label">Username or Email</label>
                        <div class="input-group">
                            <i data-lucide="user"></i>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required class="form-control" placeholder="admin@wangari.com" autocomplete="username" autofocus>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <i data-lucide="lock"></i>
                            <input type="password" name="password" required class="form-control" placeholder="••••••••" autocomplete="current-password">
                        </div>
                    </div>

                    <div class="form-footer">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" style="accent-color: var(--admin-primary);">
                            Keep me signed in
                        </label>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>

                    <button type="submit" name="admin_login" class="btn-login">
                        <span>Access Dashboard</span>
                        <i data-lucide="arrow-right" style="width: 20px; height: 20px;"></i>
                    </button>
                </form>

                <div style="margin-top: 48px; text-align: center;">
                    <p style="font-size: 0.875rem; color: var(--admin-text-muted);">
                        &copy; <?php echo date('Y'); ?> Wangari. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
