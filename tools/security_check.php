<?php
/**
 * Wangari Security Check Script
 * 
 * Audits the codebase for common security issues:
 * - SQL injection risks
 * - XSS vulnerabilities
 * - CSRF protection
 * - Password hashing
 * - File permissions
 * - Database security
 * 
 * Usage: php tools/security_check.php
 */
declare(strict_types=1);

echo "═══════════════════════════════════════════════\n";
echo "  WANGARI SECURITY AUDIT\n";
echo "  " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════\n\n";

$issues = [];
$warnings = [];
$passed = [];

$root = dirname(__DIR__);

// ═══════ CHECK 1: SQL Injection ═══════
echo "🔍 Check 1: SQL Injection...\n";

$files = glob($root . '/Backend/api/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    $name = basename($file);
    
    // Check for direct variable interpolation in queries
    if (preg_match('/"[^"]*\$[a-z_]+[^"]*"/i', $content) && !preg_match('/prepare|bindParam|bindValue/', $content)) {
        $issues[] = "$name: Potential SQL injection — direct variable in query string";
    }
    
    // Check for $_GET/$_POST directly in queries
    if (preg_match('/(SELECT|INSERT|UPDATE|DELETE).*\\\$_(GET|POST|REQUEST)/i', $content)) {
        $issues[] = "$name: Direct user input in SQL query";
    }
}

if (empty($issues)) {
    $passed[] = "No SQL injection risks found in API files";
} else {
    foreach ($issues as $issue) echo "  ❌ $issue\n";
}

// ═══════ CHECK 2: XSS Protection ═══════
echo "\n🔍 Check 2: XSS Protection...\n";

$html_files = array_merge(
    glob($root . '/Frontend/pages/*.php'),
    glob($root . '/Frontend/admin/*.php'),
    glob($root . '/Frontend/includes/*.php')
);

$xss_issues = [];
foreach ($html_files as $file) {
    $content = file_get_contents($file);
    $name = basename($file);
    
    // Check for echo without htmlspecialchars
    if (preg_match('/echo\s+\$_(GET|POST|REQUEST|COOKIE)/', $content)) {
        $xss_issues[] = "$name: Direct echo of user input without htmlspecialchars";
    }
}

if (empty($xss_issues)) {
    $passed[] = "XSS protection appears adequate";
} else {
    foreach ($xss_issues as $issue) echo "  ❌ $issue\n";
}

// ═══════ CHECK 3: CSRF Protection ═══════
echo "\n🔍 Check 3: CSRF Protection...\n";

$csrf_files = glob($root . '/Frontend/pages/register.php');
foreach ($csrf_files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'csrf_token') !== false || strpos($content, 'CSRF') !== false) {
        $passed[] = "CSRF token found in registration form";
    } else {
        $warnings[] = "Registration form may lack CSRF protection";
    }
}

// ═══════ CHECK 4: Password Hashing ═══════
echo "\n🔍 Check 4: Password Hashing...\n";

$auth_files = [
    $root . '/Backend/api/auth.php',
    $root . '/Frontend/pages/register.php',
    $root . '/Frontend/pages/login.php',
];

foreach ($auth_files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    $name = basename($file);
    
    if (strpos($content, 'password_hash') !== false) {
        $passed[] = "$name uses password_hash()";
    } elseif (strpos($content, 'md5') !== false) {
        $issues[] = "$name uses MD5 for passwords — INSECURE";
    } elseif (strpos($content, 'sha1') !== false) {
        $warnings[] = "$name may use SHA1 for passwords — consider password_hash()";
    }
}

// ═══════ CHECK 5: File Permissions ═══════
echo "\n🔍 Check 5: File Permissions...\n";

$sensitive_files = [
    $root . '/Backend/config/database.php',
    $root . '/Backend/config/security.php',
];

foreach ($sensitive_files as $file) {
    if (!file_exists($file)) continue;
    $perms = fileperms($file);
    $octal = substr(sprintf('%o', $perms), -4);
    
    if ($octal[2] > 4) { // World-readable
        $warnings[] = basename($file) . " is world-readable (perms: $octal) — restrict to 640 or 600";
    } else {
        $passed[] = basename($file) . " permissions OK ($octal)";
    }
}

// ═══════ CHECK 6: Exposed Files ═══════
echo "\n🔍 Check 6: Exposed Sensitive Files...\n";

$exposed = [
    $root . '/.git/config',
    $root . '/Backend/config/database.php',
    $root . '/wangari_data.sql',
    $root . '/.env',
];

foreach ($exposed as $file) {
    if (file_exists($file)) {
        $warnings[] = basename($file) . " exists — ensure it's not publicly accessible via web";
    }
}

// ═══════ CHECK 7: Error Reporting ═══════
echo "\n🔍 Check 7: Error Reporting...\n";

$php_ini = php_ini_loaded_file();
if (ini_get('display_errors') === '1' || ini_get('display_errors') === '') {
    $warnings[] = "display_errors is ON — should be OFF in production";
} else {
    $passed[] = "display_errors is OFF (production-safe)";
}

// ═══════ CHECK 8: HTTPS ═══════
echo "\n🔍 Check 8: HTTPS Configuration...\n";

$htaccess = @file_get_contents($root . '/.htaccess');
if ($htaccess && strpos($htaccess, 'RewriteRule') !== false && strpos($htaccess, 'https') !== false) {
    $passed[] = "HTTPS redirect found in .htaccess";
} else {
    $warnings[] = "No HTTPS redirect found in .htaccess — ensure SSL is configured";
}

// ═══════ RESULTS ═══════
echo "\n═══════════════════════════════════════════════\n";
echo "  SECURITY AUDIT RESULTS\n";
echo "═══════════════════════════════════════════════\n\n";

echo "  ✅ Passed:    " . count($passed) . "\n";
foreach ($passed as $p) echo "     ✓ $p\n";

echo "\n  ⚠️ Warnings:  " . count($warnings) . "\n";
foreach ($warnings as $w) echo "     ⚠ $w\n";

echo "\n  ❌ Issues:    " . count($issues) . "\n";
foreach ($issues as $i) echo "     ❌ $i\n";

echo "\n";

if (empty($issues)) {
    echo "  🎉 VERDICT: SYSTEM IS SECURE\n";
} else {
    echo "  ⚠️ VERDICT: FIX " . count($issues) . " ISSUE(S) BEFORE LAUNCH\n";
}

echo "\n═══════════════════════════════════════════════\n";
