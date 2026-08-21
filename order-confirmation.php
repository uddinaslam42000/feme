<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Order Confirmation & Payment Status Receipt
 */
require_once __DIR__ . '/includes/header.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
    redirect('index.php');
}

// Fetch Order & User Details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name AS user_name, u.email AS user_email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        redirect('index.php');
    }

    // Fetch Order Items
    $itemStmt = $pdo->prepare("
        SELECT oi.*, p.name AS product_name, cat.name AS category_name,
               (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) AS primary_img
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN categories cat ON p.category_id = cat.id
        WHERE oi.order_id = ?
    ");
    $itemStmt->execute([$orderId]);
    $orderItems = $itemStmt->fetchAll();

} catch (PDOException $e) {
    redirect('index.php');
}

$paymentStatusBadgeClass = 'badge-pending';
if ($order['payment_status'] === 'paid') {
    $paymentStatusBadgeClass = 'badge-delivered';
} elseif ($order['payment_status'] === 'failed') {
    $paymentStatusBadgeClass = 'badge-cancelled';
}
?>

<section class="section confirmation-page-section">
    <div class="container">
        <div class="confirmation-card">
            <!-- Header Icon & Title -->
            <div class="confirmation-header">
                <?php if ($order['payment_status'] === 'paid' || $order['payment_method'] === 'cod'): ?>
                    <div class="confirmation-icon"><i class="fa-solid fa-check"></i></div>
                    <span class="category-eyebrow">ORDER CONFIRMED</span>
                    <h1 class="confirmation-title">Thank You For Your Order</h1>
                    <p class="confirmation-subtitle">Order #<?= sprintf('%06d', $order['id']) ?> has been placed successfully.</p>
                <?php else: ?>
                    <div class="confirmation-icon" style="background: rgba(255, 77, 79, 0.15); color: #ff4d4f; border-color: #ff4d4f;"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <span class="category-eyebrow" style="color: #ff4d4f;">PAYMENT PENDING</span>
                    <h1 class="confirmation-title">Order Created – Payment Awaiting</h1>
                    <p class="confirmation-subtitle">Order #<?= sprintf('%06d', $order['id']) ?> is awaiting online payment authorization.</p>
                <?php endif; ?>
            </div>

            <!-- Details Grid -->
            <div class="confirmation-details-grid">
                <div class="details-box">
                    <h4><i class="fa-solid fa-location-dot"></i> Delivery Address</h4>
                    <p style="white-space: pre-line; margin-top: 0.5rem;"><?= sanitize($order['shipping_address']) ?></p>
                </div>

                <div class="details-box">
                    <h4><i class="fa-solid fa-receipt"></i> Payment Information</h4>
                    <p>
                        <strong>Payment Method:</strong> <?= strtoupper(sanitize($order['payment_method'])) ?> <?= $order['payment_method'] === 'online' ? '(Razorpay)' : '' ?><br>
                        <strong>Payment Status:</strong> 
                        <span class="badge-status <?= $paymentStatusBadgeClass ?>">
                            <?= strtoupper(sanitize($order['payment_status'])) ?>
                        </span><br>
                        <?php if (!empty($order['razorpay_payment_id'])): ?>
                            <strong style="font-size: 0.78rem; color: var(--text-muted);">Payment ID: <?= sanitize($order['razorpay_payment_id']) ?></strong><br>
                        <?php endif; ?>
                        <strong>Placed Date:</strong> <?= date('F d, Y – h:i A', strtotime($order['created_at'])) ?>
                    </p>
                </div>
            </div>

            <!-- Order Items Breakdown -->
            <div class="confirmation-items-box">
                <h3>Items Summary</h3>
                <div class="checkout-items-list">
                    <?php foreach ($orderItems as $item): ?>
                        <?php 
                            $imgSrc = !empty($item['primary_img']) && file_exists($item['primary_img']) ? $item['primary_img'] : 'assets/images/cat_sarees.jpg';
                        ?>
                        <div class="checkout-item-row" style="border-bottom: 1px solid var(--border-color); padding: 0.75rem 0;">
                            <img src="<?= sanitize($imgSrc) ?>" alt="" class="checkout-item-img">
                            <div class="checkout-item-info">
                                <h4 class="checkout-item-title"><?= sanitize($item['product_name']) ?></h4>
                                <span class="checkout-item-qty">Qty: <?= $item['quantity'] ?> &times; <?= format_price($item['price']) ?></span>
                            </div>
                            <span class="checkout-item-total"><?= format_price($item['price'] * $item['quantity']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-row grand-total-row" style="margin-top: 1.5rem; padding-top: 1rem; border-top: 2px solid var(--gold-primary);">
                    <span>Total Amount Paid / Due:</span>
                    <span style="color: var(--gold-primary); font-size: 1.4rem; font-weight: 700;"><?= format_price($order['total_amount']) ?></span>
                </div>
            </div>

            <div class="confirmation-actions">
                <a href="category.php" class="btn-gold">CONTINUE SHOPPING</a>
                <a href="index.php" class="btn-gold" style="background: var(--bg-dark); color: #FFF; border: 1px solid var(--gold-primary);">RETURN HOME</a>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
