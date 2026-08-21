<?php
require_once __DIR__ . '/../includes/db.php';

$users = $pdo->query("SELECT id, name, email, password_hash, role FROM users")->fetchAll();

echo "=== DEMO LOGIN ACCOUNTS ===\n\n";
foreach ($users as $u) {
    $adminCheck = password_verify('admin123', $u['password_hash']);
    $custCheck = password_verify('customer123', $u['password_hash']);
    
    $pass = 'Unknown';
    if ($adminCheck) $pass = 'admin123';
    if ($custCheck) $pass = 'customer123';

    echo "Role: " . strtoupper($u['role']) . "\n";
    echo "Name: " . $u['name'] . "\n";
    echo "Email: " . $u['email'] . "\n";
    echo "Password: " . $pass . "\n";
    echo "---------------------------\n";
}
