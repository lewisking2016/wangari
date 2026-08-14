<?php
/**
 * Security & Utility Functions
 * PSR-12 compliant security helpers for Wangari
 */
declare(strict_types=1);

/**
 * Start secure session with best practices
 */
function initializeSecureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 3600,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
        session_regenerate_id(true);
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken(string $token): bool
{
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Sanitize string input - prevents XSS
 */
function sanitizeInput(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Validate email format
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Kenya format)
 */
function isValidPhone(string $phone): bool
{
    $phone = preg_replace('/\D/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 13;
}

/**
 * Hash password with bcrypt
 */
function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 */
function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/**
 * Generate secure random token
 */
function generateToken(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

/**
 * Sanitize and validate integer
 */
function sanitizeInt(mixed $value): int
{
    return filter_var($value, FILTER_VALIDATE_INT) ?: 0;
}

/**
 * Sanitize and validate float
 */
function sanitizeFloat(mixed $value): float
{
    return filter_var($value, FILTER_VALIDATE_FLOAT) ?: 0.0;
}

/**
 * Escape SQL for display (complementary to prepared statements)
 */
function escapeSQL(string $string): string
{
    return addslashes($string);
}

/**
 * Validate file upload
 */
function isValidFileUpload(array $file, array $allowedTypes, int $maxSize = 5242880): bool
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    return isset($file['error']) &&
           $file['error'] === UPLOAD_ERR_OK &&
           in_array($mimeType, $allowedTypes, true) &&
           $file['size'] <= $maxSize;
}

/**
 * Generate safe filename
 */
function generateSafeFilename(string $originalName): string
{
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $name = pathinfo($originalName, PATHINFO_FILENAME);
    $safe = preg_replace('/[^a-z0-9_\-]/i', '', $name);
    return $safe . '_' . uniqid() . '.' . $extension;
}

/**
 * Set security headers
 */
function setSecurityHeaders(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

/**
 * Log security event
 */
function logSecurityEvent(string $event, string $level = 'INFO'): void
{
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user = $_SESSION['user_id'] ?? 'guest';
    $message = "[{$timestamp}] [{$level}] User: {$user} | IP: {$ip} | Event: {$event}\n";
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    error_log($message, 3, $logDir . '/security.log');
}

/**
 * Rate limiting helper
 */
function isRateLimited(string $identifier, int $maxAttempts = 5, int $windowSeconds = 300): bool
{
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
    }

    $elapsed = time() - $_SESSION[$key]['first_attempt'];
    
    if ($elapsed > $windowSeconds) {
        $_SESSION[$key] = ['attempts' => 1, 'first_attempt' => time()];
        return false;
    }

    $_SESSION[$key]['attempts']++;
    return $_SESSION[$key]['attempts'] > $maxAttempts;
}

/**
 * Validate form input with rules
 */
function validateForm(array $data, array $rules): array
{
    $errors = [];

    foreach ($rules as $field => $fieldRules) {
        $value = $data[$field] ?? null;
        $rulesList = explode('|', $fieldRules);

        foreach ($rulesList as $rule) {
            $ruleParams = explode(':', $rule);
            $ruleName = $ruleParams[0];

            switch ($ruleName) {
                case 'required':
                    if (empty($value)) {
                        $errors[$field] = ucfirst($field) . ' is required.';
                    }
                    break;

                case 'email':
                    if ($value && !isValidEmail($value)) {
                        $errors[$field] = 'Invalid email format.';
                    }
                    break;

                case 'min':
                    $min = intval($ruleParams[1] ?? 0);
                    if ($value && strlen($value) < $min) {
                        $errors[$field] = "Minimum {$min} characters required.";
                    }
                    break;

                case 'max':
                    $max = intval($ruleParams[1] ?? 255);
                    if ($value && strlen($value) > $max) {
                        $errors[$field] = "Maximum {$max} characters allowed.";
                    }
                    break;

                case 'numeric':
                    if ($value && !is_numeric($value)) {
                        $errors[$field] = 'Must be a number.';
                    }
                    break;

                case 'phone':
                    if ($value && !isValidPhone($value)) {
                        $errors[$field] = 'Invalid phone number.';
                    }
                    break;
            }
        }
    }

    return $errors;
}

/**
 * Get user IP address safely
 */
function getUserIP(): string
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    return filter_var($ip, FILTER_VALIDATE_IP) ?: 'unknown';
}

/**
 * Redirect with message
 */
function redirect(string $path, string $message = '', string $type = 'info'): void
{
    if (!empty($message)) {
        $_SESSION['message'] = ['text' => $message, 'type' => $type];
    }
    header("Location: {$path}");
    exit;
}

/**
 * Get and clear flash message
 */
function getFlashMessage(): ?array
{
    $message = $_SESSION['message'] ?? null;
    unset($_SESSION['message']);
    return $message;
}

/**
 * Send a plain-text email through PHP mail() using configured SMTP-ish
 * settings (from the app settings table). Returns true on success.
 *
 * Uses the `mail_from` / `mail_from_name` settings if present, else falls
 * back to the app's farm_email / farm_name settings.
 */
function sendAppMail(PDO $pdo, string $to, string $subject, string $body): bool
{
    $from = 'info@wangari.farm';
    $fromName = 'Wangari';
    try {
        $rows = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('mail_from','mail_from_name','farm_email','farm_name')")->fetchAll(PDO::FETCH_KEY_PAIR);
        $from = $rows['mail_from'] ?? ($rows['farm_email'] ?? $from);
        $fromName = $rows['mail_from_name'] ?? ($rows['farm_name'] ?? $fromName);
    } catch (Exception $e) { /* settings table may be missing */ }
    $headers = "From: " . $fromName . " <" . $from . ">\r\n" . "MIME-Version: 1.0\r\n" . "Content-Type: text/plain; charset=UTF-8\r\n";
    $body = wordwrap($body, 72, "\n");
    return @mail($to, $subject, $body, $headers);
}

/**
 * Create a password-reset token for a user and return it (or null).
 * Tokens are single-use, 32 random bytes hex, valid for 60 minutes.
 */
function createPasswordResetToken(PDO $pdo, int $userId): ?string
{
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    try {
        $pdo->prepare('DELETE FROM password_resets WHERE user_id=?')->execute([$userId]);
        $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?,?,DATE_ADD(NOW(), INTERVAL 60 MINUTE))')->execute([$userId, $hash]);
        return $token;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Validate a reset token: returns the user row + reset id, or null.
 */
function validatePasswordResetToken(PDO $pdo, string $token): ?array
{
    $hash = hash('sha256', $token);
    try {
        $stmt = $pdo->prepare('SELECT r.id AS reset_id, r.user_id, r.expires_at, u.email FROM password_resets r JOIN users u ON u.id=r.user_id WHERE r.token=? AND r.used=0 AND r.expires_at > NOW()');
        $stmt->execute([$hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Mark a reset token as used (after a successful password change).
 */
function consumePasswordResetToken(PDO $pdo, int $resetId): void
{
    try {
        $pdo->prepare('UPDATE password_resets SET used=1 WHERE id=?')->execute([$resetId]);
    } catch (Exception $e) { /* ignore */ }
}

/**
 * "Today at a glance" digest — used by the dashboard AI card.
 * Returns a short plain-text summary of today's key numbers.
 */
function getTodayDigest(PDO $pdo): string
{
    $out = [];
    try {
        $s = (float)$pdo->query('SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at)=CURDATE() AND status IN ("paid","completed")')->fetchColumn();
        $out[] = 'Sales: KES ' . number_format($s, 0);
    } catch (Exception $e) {}
    try {
        $e = (float)$pdo->query('SELECT COALESCE(SUM(eggs_collected),0) FROM production_records WHERE DATE(record_date)=CURDATE()')->fetchColumn();
        $out[] = 'Eggs: ' . number_format($e, 0);
    } catch (Exception $e) {}
    try {
        $c = (int)$pdo->query('SELECT COUNT(*) FROM customer_credits WHERE balance > 0')->fetchColumn();
        if ($c > 0) $out[] = 'Credit owed: ' . $c . ' customer(s)';
    } catch (Exception $e) {}
    try {
        $r = (int)$pdo->query('SELECT COUNT(*) FROM reminders WHERE DATE(remind_at)=CURDATE() AND status="pending"')->fetchColumn();
        if ($r > 0) $out[] = $r . ' reminder(s) today';
    } catch (Exception $e) {}
    try {
        $a = (int)$pdo->query('SELECT COUNT(*) FROM system_alerts WHERE alert_type="low_stock" AND status="active"')->fetchColumn();
        if ($a > 0) $out[] = $a . ' low-stock alert(s)';
    } catch (Exception $e) {}
    return $out ? implode('  •  ', $out) : 'No activity recorded yet today.';
}

?>
