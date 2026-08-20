<?php
/**
 * Wangari Registration — x.ai-inspired design
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) {
    session_save_path($temp_dir);
}
session_start();

$page_title = 'Create Account — Wangari';
// No header.php include - this page has its own xai-nav navigation
$csrf_token = function_exists('generateCSRFToken') ? generateCSRFToken() : ($_SESSION['csrf_token'] ?? '');

$errors = [];
$success = false;
$formData = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'farm_name' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please refresh and try again.';
    }

    $formData['first_name'] = trim($_POST['first_name'] ?? '');
    $formData['last_name'] = trim($_POST['last_name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $formData['farm_name'] = trim($_POST['farm_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($formData['first_name'])) $errors[] = 'First name is required';
    if (empty($formData['last_name'])) $errors[] = 'Last name is required';
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($formData['phone'])) $errors[] = 'Phone number is required';
    if (empty($password) || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    if ($password !== $password_confirm) $errors[] = 'Passwords do not match';

    if (empty($errors)) {
        $pdo = getDB();
        
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$formData['email']]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email already exists';
            } else {
                // Create username from email
                $username = strtolower(str_replace(['@', '.'], ['', ''], explode('@', $formData['email'])[0]));
                $username = preg_replace('/[^a-z0-9]/', '', $username);
                
                // Ensure unique username
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $checkStmt->execute([$username]);
                if ($checkStmt->fetch()) {
                    $username = $username . rand(100, 999);
                }
                
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone_number, farm_name, created_at) VALUES (?, ?, ?, 'farm_manager', ?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $formData['email'], $password_hash, $formData['first_name'], $formData['last_name'], $formData['phone'], $formData['farm_name']]);
                
                $success = true;
            }
        } catch (Exception $e) {
            $errors[] = 'An error occurred during registration. Please try again.';
            if (APP_DEBUG) {
                error_log("Registration error: " . $e->getMessage());
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
        <h2>Start <span style="font-family: var(--font-serif); font-style: italic;">managing</span> your farm today</h2>
        <p>Create your free account and get instant access to all farm management tools.</p>
    </div>
    
    <!-- Form Side -->
    <div class="xai-auth-form">
        <div class="xai-auth-card" style="max-width: 480px;">
            <?php if ($success): ?>
                <div style="text-align: center; padding: 40px 0;">
                    <div style="width: 64px; height: 64px; background: rgba(34, 197, 94, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
                    </div>
                    <h2 style="margin-bottom: 12px;">Account Created!</h2>
                    <p style="color: var(--xai-text-secondary); margin-bottom: 32px;">Welcome to Wangari. You can now sign in to start managing your farm.</p>
                    <a href="login.php" class="xai-btn xai-btn-primary xai-btn-lg">
                        Sign In
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            <?php else: ?>
                <h1>Create Account</h1>
                <p>Fill in your details to get started.</p>
                
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
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="xai-form-group">
                            <label class="xai-form-label">First Name</label>
                            <input type="text" name="first_name" class="xai-form-input" required value="<?php echo htmlspecialchars($formData['first_name']); ?>" placeholder="Jane">
                        </div>
                        
                        <div class="xai-form-group">
                            <label class="xai-form-label">Last Name</label>
                            <input type="text" name="last_name" class="xai-form-input" required value="<?php echo htmlspecialchars($formData['last_name']); ?>" placeholder="Wanjiku">
                        </div>
                    </div>
                    
                    <div class="xai-form-group">
                        <label class="xai-form-label">Email Address</label>
                        <input type="email" name="email" class="xai-form-input" required value="<?php echo htmlspecialchars($formData['email']); ?>" placeholder="you@farm.co.ke">
                    </div>
                    
                    <div class="xai-form-group">
                        <label class="xai-form-label">Phone Number</label>
                        <input type="tel" name="phone" class="xai-form-input" required value="<?php echo htmlspecialchars($formData['phone']); ?>" placeholder="+254 7XX XXX XXX">
                    </div>
                    
                    <div class="xai-form-group">
                        <label class="xai-form-label">Farm Name <span style="color: var(--xai-text-muted);">(optional)</span></label>
                        <input type="text" name="farm_name" class="xai-form-input" value="<?php echo htmlspecialchars($formData['farm_name']); ?>" placeholder="My Farm">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="xai-form-group">
                            <label class="xai-form-label">Password</label>
                            <input type="password" name="password" class="xai-form-input" required placeholder="••••••••">
                        </div>
                        
                        <div class="xai-form-group">
                            <label class="xai-form-label">Confirm Password</label>
                            <input type="password" name="password_confirm" class="xai-form-input" required placeholder="••••••••">
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 24px;">
                        <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.85rem; color: var(--xai-text-secondary); cursor: pointer;">
                            <input type="checkbox" name="terms" required style="accent-color: var(--xai-lime); margin-top: 4px;">
                            <span>I agree to the <a href="terms.php" style="color: var(--xai-lime);">Terms of Service</a> and <a href="privacy.php" style="color: var(--xai-lime);">Privacy Policy</a></span>
                        </label>
                    </div>
                    
                    <button type="submit" name="register_submit" value="1" class="xai-btn xai-btn-primary xai-btn-lg" style="width: 100%;">
                        Create Account
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </button>
                </form>
                
                <div class="google-login-separator" style="display: flex; align-items: center; margin: 20px 0; color: rgba(255,255,255,0.4); font-size: 0.85rem;">
                    <span style="flex: 1; height: 1px; background: rgba(255,255,255,0.15);"></span>
                    <span style="padding: 0 12px; font-weight: 500;">or</span>
                    <span style="flex: 1; height: 1px; background: rgba(255,255,255,0.15);"></span>
                </div>
                
                <a href="/Frontend/auth/google/login.html" class="xai-btn" style="display: flex; align-items: center; justify-content: center; gap: 12px; width: 100%; background: #000000; border: 1.5px solid rgba(255,255,255,0.25); border-radius: 12px; color: #ffffff; text-decoration: none; font-size: 0.95rem; font-weight: 600; padding: 14px; box-shadow: 0 4px 14px rgba(0,0,0,0.4); transition: all 0.2s ease;" onmouseover="this.style.background='#18181b'; this.style.borderColor='rgba(255,255,255,0.4)';" onmouseout="this.style.background='#000000'; this.style.borderColor='rgba(255,255,255,0.25)';">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span>Sign up with Google</span>
                </a>
                
                <div class="xai-form-footer">
                    Already have an account? <a href="login.php">Sign In</a>
                </div>
            <?php endif; ?>
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
