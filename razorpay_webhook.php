<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Razorpay Backup Webhook Listener
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/razorpay/razorpay_client.php';

header('Content-Type: application/json');

$rawPayload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

if (empty($rawPayload) || empty($signature)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing payload or signature']);
    exit;
}

// 1. Verify Webhook Signature
if (!verify_razorpay_webhook_signature($rawPayload, $signature)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid webhook signature']);
    exit;
}

$event = json_decode($rawPayload, true);
$eventType = $event['event'] ?? '';

if ($eventType === 'payment.captured') {
    $paymentEntity = $event['payload']['payment']['entity'] ?? [];
    $razorpayOrderId = $paymentEntity['order_id'] ?? '';
    $razorpayPaymentId = $paymentEntity['id'] ?? '';

    if (!empty($razorpayOrderId)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT * FROM orders WHERE razorpay_order_id = ?");
            $stmt->execute([$razorpayOrderId]);
            $order = $stmt->fetch();

            if ($order && $order['payment_status'] !== 'paid') {
                // Update Order Status to Paid & Confirmed
                $updateStmt = $pdo->prepare("
                    UPDATE orders 
                    SET payment_status = 'paid', status = 'confirmed', razorpay_payment_id = ? 
                    WHERE id = ?
                ");
                $updateStmt->execute([$razorpayPaymentId, $order['id']]);

                // Reduce Stock Qty
                $itemStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $itemStmt->execute([$order['id']]);
                $items = $itemStmt->fetchAll();

                $stockStmt = $pdo->prepare("UPDATE products SET stock_qty = GREATEST(0, stock_qty - ?) WHERE id = ?");
                foreach ($items as $item) {
                    $stockStmt->execute([$item['quantity'], $item['product_id']]);
                }

                // Clear User Database Cart
                if (!empty($order['user_id'])) {
                    $clearCart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                    $clearCart->execute([$order['user_id']]);
                }
            }

            $pdo->commit();

            if (isset($order['id'])) {
                send_order_confirmation_emails($pdo, $order['id']);
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }
} elseif ($eventType === 'payment.failed') {
    $paymentEntity = $event['payload']['payment']['entity'] ?? [];
    $razorpayOrderId = $paymentEntity['order_id'] ?? '';

    if (!empty($razorpayOrderId)) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE razorpay_order_id = ? AND payment_status = 'pending'");
            $stmt->execute([$razorpayOrderId]);
        } catch (Exception $e) {
            // Ignore
        }
    }
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
exit;
