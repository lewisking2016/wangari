<?php
require_once __DIR__ . '/../Backend/config/database.php';
require_once __DIR__ . '/../Backend/config/security.php';

$pdo = getDatabaseConnection();

$username = 'admin';
$email = 'admin@wangari.farm';
$password = 'wangari123';
$role = 'super_admin';

// Check if user exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email');
$stmt->execute([':username' => $username, ':email' => $email]);
if ($stmt->fetch()) {
    echo "Admin user already exists.\n";
    exit(0);
}

$hash = hashPassword($password);
$insert = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, first_name, last_name) VALUES (:username, :email, :hash, :role, :first, :last)');
$insert->execute([
    ':username' => $username,
    ':email' => $email,
    ':hash' => $hash,
    ':role' => $role,
    ':first' => 'Wangari',
    ':last' => 'Admin'
]);

if ($pdo->lastInsertId()) {
    echo "Admin user created: {$username} / {$email}\n";
} else {
    echo "Failed to create admin user.\n";
}
