<?php
/**
 * Shared session bootstrap for web, OAuth, and API requests.
 */
declare(strict_types=1);

function wangariConfigureSession(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $sessionDirs = [
        '/var/lib/php/sessions',
        dirname(__DIR__, 2) . '/Backend/storage/sessions',
        sys_get_temp_dir(),
    ];
    foreach ($sessionDirs as $sessionDir) {
        if ($sessionDir !== sys_get_temp_dir() && !is_dir($sessionDir)) {
            @mkdir($sessionDir, 0700, true);
        }
        if (is_dir($sessionDir) && is_writable($sessionDir)) {
            session_save_path($sessionDir);
            break;
        }
    }

    // Keep authentication independent from an optional Redis extension/configuration.
    ini_set('session.save_handler', 'files');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '7200');
    ini_set('session.lazy_write', '0');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
}

function wangariStartSession(): void
{
    wangariConfigureSession();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
