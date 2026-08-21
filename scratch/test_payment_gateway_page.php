<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 2;
$_SESSION['user_role'] = 'customer';
$_SESSION['user_name'] = 'Eleanor Vance';

$_GET['order_id'] = 100001;

ob_start();
include __DIR__ . '/../payment-gateway.php';
$html = ob_get_clean();

echo "Payment Gateway HTML Size: " . strlen($html) . " bytes\n";
echo "Contains Razorpay Payment Gateway: " . (strpos($html, 'Razorpay Payment Gateway') !== false ? 'YES' : 'NO') . "\n";
echo "Contains Bank 3D-Secure: " . (strpos($html, 'Bank 3D-Secure') !== false ? 'YES' : 'NO') . "\n";
echo "Contains Eleanor Vance: " . (strpos($html, 'Eleanor Vance') !== false ? 'YES' : 'NO') . "\n";
