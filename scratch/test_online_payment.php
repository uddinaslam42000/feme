<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 2;
$_SESSION['user_role'] = 'customer';
$_SESSION['user_name'] = 'Eleanor Vance';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/razorpay/razorpay_client.php';

// Test creating razorpay order
$orderData = create_razorpay_order(112000.00, "test_receipt_101");

echo "=== RAZORPAY TEST SIMULATOR CHECK ===\n";
echo "Order Created Success: " . ($orderData['success'] ? 'YES' : 'NO') . "\n";
echo "Razorpay Order ID: " . $orderData['id'] . "\n";
echo "Amount in Paise: " . $orderData['amount'] . "\n";

// Test verify signature
$verifyResult = verify_razorpay_signature($orderData['id'], 'pay_simulated_123456789', 'simulated_sig_123');
echo "Signature Verified Success: " . ($verifyResult ? 'YES' : 'NO') . "\n";
