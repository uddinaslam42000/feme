<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Server-Side Razorpay Signature Verification & Order Finalization Handler
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/razorpay/razorpay_client.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

if (!verify_csrf_token()) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF session token.']);
    exit;
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$razorpayOrderId = trim($_POST['razorpay_order_id'] ?? '');
$razorpayPaymentId = trim($_POST['razorpay_payment_id'] ?? '');
$razorpaySignature = trim($_POST['razorpay_signature'] ?? '');

if ($orderId <= 0 || empty($razorpayOrderId) || empty($razorpayPaymentId)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing payment parameters.']);
    exit;
}

// 1. Server-side Signature Verification
$isValid = verify_razorpay_signature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature);

if (!$isValid) {
    // Mark order as failed
    try {
        $failStmt = $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ?");
        $failStmt->execute([$orderId]);
    } catch (PDOException $e) {}

    echo json_encode(['status' => 'error', 'message' => 'Razorpay payment signature verification failed. Please try again.']);
    exit;
}

// 2. Signature Verified: Mark Order Paid & Reduce Stock & Clear Cart inside PDO Transaction
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Order record not found.']);
        exit;
    }

    if ($order['payment_status'] !== 'paid') {
        // Mark as paid & confirmed
        $updateStmt = $pdo->prepare("
            UPDATE orders 
            SET payment_status = 'paid', status = 'confirmed', razorpay_payment_id = ? 
            WHERE id = ?
        ");
        $updateStmt->execute([$razorpayPaymentId, $orderId]);

        // Reduce Product Stock Qty
        $itemStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$orderId]);
        $orderItems = $itemStmt->fetchAll();

        $stockStmt = $pdo->prepare("UPDATE products SET stock_qty = GREATEST(0, stock_qty - ?) WHERE id = ?");
        foreach ($orderItems as $item) {
            $stockStmt->execute([$item['quantity'], $item['product_id']]);
        }

        // Clear Database Cart & Guest Session Cart
        if (is_logged_in()) {
            $clearCart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $clearCart->execute([$_SESSION['user_id']]);
        }
        unset($_SESSION['cart']);
    }

    $pdo->commit();

    // Send Order Confirmation Emails (Trigger 2 & 5)
    send_order_confirmation_emails($pdo, $orderId);

    echo json_encode([
        'status' => 'success',
        'message' => 'Payment verified successfully.',
        'redirect' => "order-confirmation.php?order_id={$orderId}"
    ]);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Database error finalizing order payment.']);
    exit;
}
