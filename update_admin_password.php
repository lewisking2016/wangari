<?php
/**
 * Update Admin Password to wangari123
 * Run this once to update the admin password
 */
declare(strict_types=1);

require_once __DIR__ . '/Backend/config/database.php';

header('Content-Type: text/html; charset=UTF-8');
echo "<!DOCTYPE html><html><head><title>Update Password</title>";
echo "<style>body{font-family:system-ui;padding:40px;background:#f5f5f5;max-width:800px;margin:0 auto;}";
echo ".ok{color:#28a745;padding:8px;background:#d4edda;border-radius:4px;margin:4px 0;display:block;}";
echo ".fail{color:#dc3545;padding:8px;background:#fee;border-radius:4px;margin:4px 0;display:block;}";
echo "h1{color:#2c3e50;}</style></head><body>";
echo "<h1>🔐 Update Admin Password</h1>";

try {
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception("Cannot connect to database");
    }
    
    echo "<span class='ok'>✓ Connected to database</span>";
    
    // Hash the password wangari123
    $newPasswordHash = password_hash('wangari123', PASSWORD_DEFAULT);
    
    // Update admin user password
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
    $stmt->execute([$newPasswordHash]);
    
    if ($stmt->rowCount() > 0) {
        echo "<span class='ok'>✓ Admin password updated successfully!</span>";
        echo "<h2>Login Credentials:</h2>";
        echo "<ul>";
        echo "<li><strong>Username:</strong> admin</li>";
        echo "<li><strong>Password:</strong> wangari123</li>";
        echo "<li><strong>Email:</strong> admin@wangari.farm</li>";
        echo "</ul>";
    } else {
        echo "<span class='fail'>✗ Admin user not found in database</span>";
        
        // Create admin user if doesn't exist
        echo "<p>Creating admin user...</p>";
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@wangari.farm', $newPasswordHash, 'super_admin', 'Admin', 'User']);
        echo "<span class='ok'>✓ Admin user created with password: wangari123</span>";
    }
    
    // Also update manager and demo passwords for consistency
    $managerHash = password_hash('manager123', PASSWORD_DEFAULT);
    $demoHash = password_hash('demo123', PASSWORD_DEFAULT);
    
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'manager'")->execute([$managerHash]);
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'demo'")->execute([$demoHash]);
    
    echo "<span class='ok'>✓ All user passwords updated</span>";
    
    echo "<h2>All Login Credentials:</h2>";
    echo "<table style='width:100%;border-collapse:collapse;background:white;'>";
    echo "<tr style='background:#f8f9fa;'><th style='padding:10px;text-align:left;border:1px solid #dee2e6;'>Username</th><th style='padding:10px;text-align:left;border:1px solid #dee2e6;'>Password</th><th style='padding:10px;text-align:left;border:1px solid #dee2e6;'>Role</th></tr>";
    echo "<tr><td style='padding:10px;border:1px solid #dee2e6;'>admin</td><td style='padding:10px;border:1px solid #dee2e6;'><strong>wangari123</strong></td><td style='padding:10px;border:1px solid #dee2e6;'>Super Admin</td></tr>";
    echo "<tr><td style='padding:10px;border:1px solid #dee2e6;'>manager</td><td style='padding:10px;border:1px solid #dee2e6;'>manager123</td><td style='padding:10px;border:1px solid #dee2e6;'>Farm Manager</td></tr>";
    echo "<tr><td style='padding:10px;border:1px solid #dee2e6;'>demo</td><td style='padding:10px;border:1px solid #dee2e6;'>demo123</td><td style='padding:10px;border:1px solid #dee2e6;'>Customer</td></tr>";
    echo "</table>";
    
    echo "<p style='margin-top:30px;'><strong>⚠️ IMPORTANT: Delete this file (update_admin_password.php) now for security!</strong></p>";
    echo "<p><a href='/Frontend/admin/login.php' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:4px;display:inline-block;'>Go to Admin Login</a></p>";
    
} catch (Exception $e) {
    echo "<span class='fail'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>";
}

echo "</body></html>";
?>
