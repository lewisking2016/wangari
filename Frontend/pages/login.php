<?php
/**
 * Login Page, Wangari
 * Growvi-style split auth layout.
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) {
    session_save_path($temp_dir);
}
session_start();

$path_prefix = '../';
$page_title = 'Login - Wangari';

include '../includes/header.php';
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
                
                echo "<script>window.location.href = '/Frontend/index.php';</script>";
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

<div class="g-auth">
    <div class="g-auth-brand">
        <div>
            <a href="/" class="g-logo">
                <img src="/Frontend/images/wangari-logo.png" alt="Wangari">
                <span>Wangari<em>.</em></span>
            </a>
            <div style="margin-top: 6rem;">
                <h2>Welcome <span class="g-serif">back</span> to your farm</h2>
                <p>Track poultry, livestock, crops, feed production, sales and finances, all in one place.</p>
            </div>
        </div>
        <p style="font-size: 0.85rem; color: rgba(255,255,255,0.45);">&copy; <?php echo date('Y'); ?> Wangari, Smart Farming for a Sustainable Future</p>
    </div>

    <div class="g-auth-form">
        <div class="g-auth-card">
            <h1>Sign In</h1>
            <p>Enter your details to access your account.</p>

            <?php if (!empty($errors)): ?>
                <div style="padding: 1rem; background: #FEF2F2; border: 1px solid #FECACA; border-radius: var(--g-radius-sm); color: #991B1B; margin-bottom: 1.5rem; font-size: 0.9rem;">
                    <ul style="margin: 0; padding-left: 1rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="g-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="g-field">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($username); ?>" placeholder="you@farm.co.ke">
                </div>
                <div class="g-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--g-muted);">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="forgot-password.php" style="color: var(--g-tan); font-weight: 600;">Forgot Password?</a>
                </div>

                <button type="submit" name="login_submit" value="1" class="g-btn g-btn-dark" style="width: 100%;">Sign In</button>
            </form>

            <p style="text-align: center; margin-top: 1.8rem; padding-top: 1.5rem; border-top: 1px solid var(--g-line); color: var(--g-muted); font-size: 0.9rem;">
                Don't have an account? <a href="register.php" style="color: var(--g-tan); font-weight: 600;">Create Account</a>
            </p>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
?>
