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

?>
