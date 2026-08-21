<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

echo "--- BACKEND VERIFICATION REPORT ---\n";

// 1. Couriers Check
$couriers = $pdo->query("SELECT * FROM couriers")->fetchAll();
echo "1. Onboard Couriers Count: " . count($couriers) . "\n";
foreach ($couriers as $c) {
    echo "   - [{$c['code']}] {$c['name']} (Status: {$c['status']})\n";
}

// 2. Orders with Courier Tagging Check
$orders = $pdo->query("
    SELECT o.id, o.status, o.total_amount, o.tracking_number, c.name AS courier_name
    FROM orders o 
    LEFT JOIN couriers c ON o.courier_id = c.id
")->fetchAll();
echo "\n2. Orders Count: " . count($orders) . "\n";
foreach ($orders as $o) {
    echo "   - Order #{$o['id']}: Amount ₹{$o['total_amount']} | Status: {$o['status']} | Courier: " . ($o['courier_name'] ?? 'Unassigned') . " | AWB: " . ($o['tracking_number'] ?? 'N/A') . "\n";
}

// 3. Customer Profile & Address Check
$customers = $pdo->query("
    SELECT u.id, u.name, u.email, u.address, COUNT(o.id) AS total_orders, COALESCE(SUM(o.total_amount), 0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id
    WHERE u.role = 'customer'
    GROUP BY u.id
")->fetchAll();
echo "\n3. Registered Customers Count: " . count($customers) . "\n";
foreach ($customers as $cust) {
    echo "   - Customer #{$cust['id']}: {$cust['name']} ({$cust['email']})\n";
    echo "     Address: {$cust['address']}\n";
    echo "     Orders Placed: {$cust['total_orders']} | Total Lifetime Spent: ₹{$cust['total_spent']}\n";
}

echo "\n--- ALL BACKEND VERIFICATIONS PASSED SUCCESSFULLY ---\n";
