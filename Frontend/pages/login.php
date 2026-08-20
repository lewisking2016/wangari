<?php
/**
 * Wangari Login — x.ai-inspired design
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) {
    session_save_path($temp_dir);
}
session_start();

$page_title = 'Sign In — Wangari';
// No header.php include - this page has its own xai-nav navigation
$csrf_token = function_exists('generateCSRFToken') ? generateCSRFToken() : ($_SESSION['csrf_token'] ?? '');

// Redirect only if a customer is already logged in
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'customer') {
    echo "<script>window.location.href = '/Frontend/index.php';</script>";
    exit;
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please refresh and try again.';
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username)) $errors[] = 'Username or email is required';
    if (empty($password)) $errors[] = 'Password is required';

    if (empty($errors)) {
        $pdo = getDB();
        
        try {
            $stmt = $pdo->prepare("SELECT id, username, password_hash, role, first_name, last_name FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['first_name'] = $user['first_name'];
                
                echo "<script>window.location.href = '/Frontend/admin/dashboard.php';</script>";
                exit;
            } else {
                $errors[] = 'Invalid username/email or password';
            }
        } catch (Exception $e) {
            $errors[] = 'An error occurred during login. Please try again.';
            if (APP_DEBUG) {
                error_log("Login error: " . $e->getMessage());
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
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Frontend/assets/css/xai-public.css">
    <link rel="icon" type="image/png" href="/Frontend/images/wangari-logo.png">
</head>
<body>

<div class="xai-auth">
    <!-- Brand Side -->
    <div class="xai-auth-brand">
        <a href="/" class="xai-nav-brand" style="margin-bottom: 60px;">
            <img src="/Frontend/images/wangari-logo.png" alt="Wangari" style="height: 48px;">
            Wangari<span>.</span>
        </a>
        <h2>Welcome <span style="font-family: var(--font-serif); font-style: italic;">back</span> to your farm</h2>
        <p>Track poultry, livestock, crops, feed production, sales and finances — all in one place.</p>
    </div>
    
    <!-- Form Side -->
    <div class="xai-auth-form">
        <div class="xai-auth-card">
            <h1>Sign In</h1>
            <p>Enter your credentials to access your account.</p>
            
            <?php if (!empty($errors)): ?>
                <div style="padding: 16px; background: rgba(220, 38, 38, 0.1); border: 1px solid rgba(220, 38, 38, 0.2); border-radius: 12px; color: #FCA5A5; margin-bottom: 24px; font-size: 0.9rem;">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="xai-form-group">
                    <label class="xai-form-label">Username or Email</label>
                    <input type="text" name="username" class="xai-form-input" required value="<?php echo htmlspecialchars($username); ?>" placeholder="you@farm.co.ke">
                </div>
                
                <div class="xai-form-group">
                    <label class="xai-form-label">Password</label>
                    <input type="password" name="password" class="xai-form-input" required placeholder="••••••••">
                </div>
                
                <div class="xai-form-row">
                    <label>
                        <input type="checkbox" name="remember" style="accent-color: var(--xai-lime);">
                        Remember me
                    </label>
                    <a href="forgot-password.php">Forgot Password?</a>
                </div>
                
                <button type="submit" name="login_submit" value="1" class="xai-btn xai-btn-primary xai-btn-lg" style="width: 100%;">
                    Sign In
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </form>
            
            <div class="xai-form-footer">
                Don't have an account? <a href="register.php">Create Account</a>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<!-- Footer -->
<footer class="xai-footer">
    <div class="xai-container">
        <div class="xai-footer-inner">
            <div>
                <div class="xai-footer-brand">
                    <img src="/Frontend/images/wangari-logo.png" alt="Wangari">
                    Wangari<span>.</span>
                </div>
                <p class="xai-footer-desc">Smart Farming for a Sustainable Future.</p>
                <div class="xai-footer-contact">
                    <a href="mailto:info@imeantech.com" class="xai-footer-contact-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
                        info@imeantech.com
                    </a>
                </div>
            </div>
            <div>
                <h4>Product</h4>
                <ul class="xai-footer-links">
                    <li><a href="/Frontend/index.php#features">Features</a></li>
                    <li><a href="/Frontend/pages/pricing.php">Pricing</a></li>
                </ul>
            </div>
            <div>
                <h4>Legal</h4>
                <ul class="xai-footer-links">
                    <li><a href="/Frontend/pages/privacy.php">Privacy</a></li>
                    <li><a href="/Frontend/pages/terms.php">Terms</a></li>
                </ul>
            </div>
        </div>
        <div class="xai-footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> Wangari. All rights reserved.</span>
            <div class="xai-footer-credits">
                Built by <a href="https://imeantech.com" target="_blank">iMeanTech</a>
            </div>
        </div>
    </div>
</footer>

</body>
</html>
