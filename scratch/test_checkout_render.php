<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 2;
$_SESSION['user_role'] = 'customer';
$_SESSION['user_name'] = 'Eleanor Vance';

require_once __DIR__ . '/../includes/db.php';

// Add item to cart for user 2 if cart empty
$checkCart = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
$checkCart->execute([2]);
if ($checkCart->fetchColumn() == 0) {
    $ins = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (2, 1, 1)");
    $ins->execute();
}

ob_start();
include __DIR__ . '/../checkout.php';
$html = ob_get_clean();

echo "Checkout HTML Render Size: " . strlen($html) . " bytes\n";
echo "Contains Shipping Address: " . (strpos($html, 'Shipping Address') !== false ? 'YES' : 'NO') . "\n";
echo "Contains CONFIRM & PLACE ORDER: " . (strpos($html, 'CONFIRM & PLACE ORDER') !== false ? 'YES' : 'NO') . "\n";
