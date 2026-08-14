<?php
/**
 * Register Page, Wangari
 * Growvi-style split auth layout.
 */
declare(strict_types=1);

$temp_dir = sys_get_temp_dir();
if (is_writable($temp_dir)) {
    session_save_path($temp_dir);
}
session_start();

$path_prefix = '../';
$page_title = 'Create Account - Wangari';

include '../includes/header.php';
$csrf_token = function_exists('generateCSRFToken') ? generateCSRFToken() : ($_SESSION['csrf_token'] ?? '');

// Redirect only if a customer is already logged in
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'customer') {
    echo "<script>window.location.href = '/Frontend/index.php';</script>";
    exit;
}

$errors = [];
$form_data = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'username' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please refresh and try again.';
    }

    $form_data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'username' => trim($_POST['username'] ?? ''),
    ];
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (empty($form_data['first_name'])) $errors[] = 'First name is required';
    if (empty($form_data['last_name'])) $errors[] = 'Last name is required';
    if (empty($form_data['email']) || !filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) 
        $errors[] = 'Valid email is required';
    if (empty($form_data['phone'])) $errors[] = 'Phone number is required';
    if (empty($form_data['username']) || strlen($form_data['username']) < 3) 
        $errors[] = 'Username must be at least 3 characters';
    if (empty($password) || strlen($password) < 6) 
        $errors[] = 'Password must be at least 6 characters';
    if ($password !== $password_confirm) 
        $errors[] = 'Passwords do not match';

    if (empty($errors)) {
        $pdo = getDB();
        if ($pdo) {
            try {
                // Check if username or email exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$form_data['username'], $form_data['email']]);
                if ($stmt->fetch()) {
                    $errors[] = 'Username or email already exists';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, phone_number, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?, 'customer')");
                    $stmt->execute([
                        $form_data['username'],
                        $hash,
                        $form_data['email'],
                        $form_data['phone'],
                        $form_data['first_name'],
                        $form_data['last_name']
                    ]);
                    
                    $_SESSION['registration_success'] = true;
                    echo "<script>window.location.href = '/Frontend/pages/login.php?success=1';</script>";
                    exit;
                }
            } catch (Exception $e) {
                $errors[] = 'Registration failed. Please try again.';
                error_log("Registration error: " . $e->getMessage());
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
                <h2>Start your <span class="g-serif">smart farm</span> today</h2>
                <p>Track poultry, livestock, crops, feed production, sales and finances, all in one place, free to start.</p>
            </div>
        </div>
        <p style="font-size: 0.85rem; color: rgba(255,255,255,0.45);">&copy; <?php echo date('Y'); ?> Wangari, Smart Farming for a Sustainable Future</p>
    </div>

    <div class="g-auth-form">
        <div class="g-auth-card">
            <h1>Create Account</h1>
            <p>Join the farms growing smarter with Wangari.</p>

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
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.9rem;">
                    <div class="g-field">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($form_data['first_name']); ?>">
                    </div>
                    <div class="g-field">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($form_data['last_name']); ?>">
                    </div>
                </div>
                <div class="g-field">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($form_data['email']); ?>" placeholder="you@farm.co.ke">
                </div>
                <div class="g-field">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required value="<?php echo htmlspecialchars($form_data['phone']); ?>" placeholder="e.g. 0727...">
                </div>
                <div class="g-field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($form_data['username']); ?>">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.9rem;">
                    <div class="g-field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="g-field">
                        <label for="password_confirm">Confirm</label>
                        <input type="password" id="password_confirm" name="password_confirm" required>
                    </div>
                </div>

                <button type="submit" name="register_submit" value="1" class="g-btn g-btn-dark" style="width: 100%;">Create Account</button>
            </form>

            <p style="text-align: center; margin-top: 1.8rem; padding-top: 1.5rem; border-top: 1px solid var(--g-line); color: var(--g-muted); font-size: 0.9rem;">
                Already have an account? <a href="login.php" style="color: var(--g-tan); font-weight: 600;">Login here</a>
            </p>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
?>
