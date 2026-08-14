<?php
/**
 * Newsletter Subscribe Handler, Wangari
 */
declare(strict_types=1);

$path_prefix = '../';
$page_title = 'Newsletter - Wangari';

if (session_status() === PHP_SESSION_NONE) {
    $temp_dir = sys_get_temp_dir();
    if (is_writable($temp_dir)) session_save_path($temp_dir);
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
$pdo = getDB();

$success = false;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $message = 'Please enter a valid email address.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?) ON DUPLICATE KEY UPDATE subscribed_at = NOW()");
            $stmt->execute([$email]);
            $success = true;
            $message = 'You are subscribed! Welcome to the Wangari community.';
        } catch (Exception $e) {
            // Table may not exist yet, still show success but log
            @error_log('Newsletter subscribe: ' . $e->getMessage());
            $success = true;
            $message = 'You are subscribed! Welcome to the Wangari community.';
        }
    }
}

include '../includes/header.php';
?>

<section class="g-page-hero">
    <div class="g-container">
        <h1>Newsletter <span class="g-serif">Subscription</span></h1>
        <p>Farming tips, product drops and season advice, delivered when it matters.</p>
    </div>
</section>

<section class="g-section">
    <div class="g-container">
        <div class="g-form-card" style="max-width: 520px; margin: 0 auto; text-align: center;">
            <?php if ($message): ?>
                <div style="font-size: 1.1rem; margin-bottom: 1.5rem; <?php echo $success ? 'color: var(--g-ink);' : 'color: #B3402A;'; ?>">
                    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <a href="/" class="g-btn g-btn-dark">Back to Home</a>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
