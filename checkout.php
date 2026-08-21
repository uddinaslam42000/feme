<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Checkout & Payment Gateway Integration
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/razorpay/razorpay_client.php';
require_once __DIR__ . '/includes/mailer.php';

// 1. Require Login
if (!is_logged_in()) {
    redirect('login.php?redirect=checkout.php');
}

$userId = $_SESSION['user_id'];

// Fetch User Profile
try {
    $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $currentUser = $userStmt->fetch();
} catch (PDOException $e) {
    $currentUser = null;
}

// Fetch Cart Items & Recalculate Totals Server-Side
$cartItems = get_cart_items($pdo);

if (empty($cartItems)) {
    redirect('cart.php');
}

$subtotal = 0;
foreach ($cartItems as $item) {
    $itemPrice = !empty($item['discount_price']) ? (float)$item['discount_price'] : (float)$item['price'];
    $subtotal += $itemPrice * (int)$item['quantity'];
}

$shippingFee = ($subtotal > 5000) ? 0 : 250;
$grandTotal = $subtotal + $shippingFee;

$errorMsg = '';
$razorpayCheckoutData = null;

// Handle Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errorMsg = 'Invalid session token. Please refresh and try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['shipping_address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? 'cod');

        if (empty($fullName) || !$email || empty($phone) || empty($address) || empty($city) || empty($pincode)) {
            $errorMsg = 'Please complete all required shipping address fields with valid details.';
        } else {
            $fullShippingAddress = "{$fullName}\n{$address}\n{$city}, {$state} - {$pincode}\nPhone: {$phone}\nEmail: {$email}";

            try {
                $pdo->beginTransaction();

                // 1. Create Local Order Record
                $orderStmt = $pdo->prepare("
                    INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method, payment_status) 
                    VALUES (?, ?, 'pending', ?, ?, 'pending')
                ");
                $orderStmt->execute([$userId, $grandTotal, $fullShippingAddress, $paymentMethod]);
                $orderId = $pdo->lastInsertId();

                // 2. Insert Order Items
                $itemStmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES (?, ?, ?, ?)
                ");

                foreach ($cartItems as $item) {
                    $unitPrice = !empty($item['discount_price']) ? (float)$item['discount_price'] : (float)$item['price'];
                    $itemStmt->execute([$orderId, $item['id'], $item['quantity'], $unitPrice]);
                }

                // Update User Profile Address/Phone if empty
                if ($currentUser && (empty($currentUser['address']) || empty($currentUser['phone']))) {
                    $updateUserStmt = $pdo->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ?");
                    $updateUserStmt->execute([$phone, $fullShippingAddress, $userId]);
                }

                if ($paymentMethod === 'cod') {
                    // COD Flow: Instantly confirm, deduct stock, and clear cart
                    $confirmStmt = $pdo->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ?");
                    $confirmStmt->execute([$orderId]);

                    $stockStmt = $pdo->prepare("UPDATE products SET stock_qty = GREATEST(0, stock_qty - ?) WHERE id = ?");
                    foreach ($cartItems as $item) {
                        $stockStmt->execute([$item['quantity'], $item['id']]);
                    }

                    $clearCartStmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                    $clearCartStmt->execute([$userId]);

                    $pdo->commit();

                    // Send Confirmation Email (Trigger 2 & 5)
                    send_order_confirmation_emails($pdo, $orderId);

                    redirect("order-confirmation.php?order_id={$orderId}");

                } else {
                    // Online Payment — Real Razorpay Checkout.js Flow
                    $rzpOrder = create_razorpay_order($grandTotal, "order_rcptid_" . $orderId);

                    if ($rzpOrder && isset($rzpOrder['id'])) {
                        $rzpOrderId = $rzpOrder['id'];
                        $updateRzpStmt = $pdo->prepare("UPDATE orders SET razorpay_order_id = ? WHERE id = ?");
                        $updateRzpStmt->execute([$rzpOrderId, $orderId]);

                        $pdo->commit();

                        // Pass data to frontend — Razorpay popup opens after page render
                        $razorpayCheckoutData = [
                            'key_id'         => RAZORPAY_KEY_ID,
                            'amount'         => $rzpOrder['amount'],
                            'order_id'       => $rzpOrderId,
                            'local_order_id' => $orderId,
                            'name'           => STORE_NAME,
                            'description'    => 'Order #' . sprintf('%06d', $orderId),
                            'prefill'        => [
                                'name'    => $fullName,
                                'email'   => $email,
                                'contact' => $phone
                            ]
                        ];
                    } else {
                        $pdo->rollBack();
                        $errorMsg = 'Unable to initialize Razorpay payment. Please try Cash on Delivery or retry.';
                    }
                }

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errorMsg = 'Order processing error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = "Checkout | FeMe Luxury Closet";
require_once __DIR__ . '/includes/header.php';
?>

<section class="section checkout-page-section">
    <div class="container">
        <div class="section-header" style="text-align: left; margin-bottom: 2rem;">
            <p class="section-subtitle">Secure Luxury Checkout</p>
            <h1 class="section-title" style="display: block;">Complete Your Royal Order</h1>
        </div>

        <?php if (!empty($errorMsg)): ?>
            <div class="auth-alert error" style="margin-bottom: 2rem;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errorMsg) ?>
            </div>
        <?php endif; ?>

        <form action="checkout.php" method="POST" class="checkout-form" id="checkoutForm">
            <?= csrf_token_input() ?>
            <div class="checkout-layout">
                <!-- Left: Shipping & Payment Address Form -->
                <div class="checkout-shipping-panel">
                    <div class="checkout-card">
                        <h3 class="checkout-card-title"><i class="fa-solid fa-location-dot"></i> Shipping Address</h3>

                        <div class="form-row">
                            <div class="form-group flex-1">
                                <label for="fullName">Full Name *</label>
                                <input type="text" name="full_name" id="fullName" class="form-control" required value="<?= isset($_POST['full_name']) ? sanitize($_POST['full_name']) : sanitize($currentUser['name'] ?? '') ?>">
                            </div>
                            <div class="form-group flex-1">
                                <label for="emailAddr">Email Address *</label>
                                <input type="email" name="email" id="emailAddr" class="form-control" required value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : sanitize($currentUser['email'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phoneNum">Phone Number *</label>
                            <input type="tel" name="phone" id="phoneNum" class="form-control" required value="<?= isset($_POST['phone']) ? sanitize($_POST['phone']) : sanitize($currentUser['phone'] ?? '') ?>" placeholder="+91 98765 43210">
                        </div>

                        <div class="form-group">
                            <label for="shippingAddr">Street Address / House / Villa *</label>
                            <textarea name="shipping_address" id="shippingAddr" class="form-control" rows="3" required placeholder="42 Regency Villa, MG Road"><?= isset($_POST['shipping_address']) ? sanitize($_POST['shipping_address']) : sanitize($currentUser['address'] ?? '') ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group flex-1">
                                <label for="cityInput">City *</label>
                                <input type="text" name="city" id="cityInput" class="form-control" required placeholder="Mumbai" value="<?= isset($_POST['city']) ? sanitize($_POST['city']) : '' ?>">
                            </div>
                            <div class="form-group flex-1">
                                <label for="stateInput">State *</label>
                                <input type="text" name="state" id="stateInput" class="form-control" required placeholder="Maharashtra" value="<?= isset($_POST['state']) ? sanitize($_POST['state']) : '' ?>">
                            </div>
                            <div class="form-group flex-1">
                                <label for="pincodeInput">PIN Code *</label>
                                <input type="text" name="pincode" id="pincodeInput" class="form-control" required placeholder="400001" value="<?= isset($_POST['pincode']) ? sanitize($_POST['pincode']) : '' ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Radio Options -->
                    <div class="checkout-card" style="margin-top: 2rem;">
                        <h3 class="checkout-card-title"><i class="fa-regular fa-credit-card"></i> Payment Method</h3>

                        <div class="payment-options">
                            <label class="payment-radio-option">
                                <input type="radio" name="payment_method" value="cod" <?= (!isset($_POST['payment_method']) || $_POST['payment_method'] === 'cod') ? 'checked' : '' ?>>
                                <div class="radio-content">
                                    <span class="payment-title"><i class="fa-solid fa-hand-holding-dollar"></i> Cash on Delivery (COD)</span>
                                    <span class="payment-sub">Pay via cash or UPI upon delivery to your doorstep.</span>
                                </div>
                            </label>

                            <label class="payment-radio-option">
                                <input type="radio" name="payment_method" value="online" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] === 'online') ? 'checked' : '' ?>>
                                <div class="radio-content">
                                    <span class="payment-title"><i class="fa-solid fa-shield-halved"></i> Online Payment / Razorpay Gateway</span>
                                    <span class="payment-sub">Pay securely via Credit/Debit Cards, NetBanking, UPI & Wallets.</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Right: Read-Only Order Summary Panel -->
                <div class="checkout-summary-panel">
                    <div class="summary-card">
                        <h3 class="summary-title">Order Items (<?= count($cartItems) ?>)</h3>

                        <div class="checkout-items-list">
                            <?php foreach ($cartItems as $item): ?>
                                <?php 
                                    $unitPrice = !empty($item['discount_price']) ? (float)$item['discount_price'] : (float)$item['price'];
                                    $imgSrc = !empty($item['primary_img']) && file_exists($item['primary_img']) ? $item['primary_img'] : 'assets/images/cat_sarees.jpg';
                                ?>
                                <div class="checkout-item-row">
                                    <img src="<?= sanitize($imgSrc) ?>" alt="<?= sanitize($item['name']) ?>" class="checkout-item-img">
                                    <div class="checkout-item-info">
                                        <h4 class="checkout-item-title"><?= sanitize($item['name']) ?></h4>
                                        <span class="checkout-item-qty">Qty: <?= $item['quantity'] ?> &times; <?= format_price($unitPrice) ?></span>
                                    </div>
                                    <span class="checkout-item-total"><?= format_price($unitPrice * $item['quantity']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="summary-divider"></div>

                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span><?= format_price($subtotal) ?></span>
                        </div>

                        <div class="summary-row">
                            <span>Shipping</span>
                            <span><?= $shippingFee === 0 ? '<span style="color:#2e7d32; font-weight:600;">FREE</span>' : format_price($shippingFee) ?></span>
                        </div>

                        <div class="summary-divider"></div>

                        <div class="summary-row grand-total-row">
                            <span>Grand Total</span>
                            <span><?= format_price($grandTotal) ?></span>
                        </div>

                        <button type="submit" class="btn-gold" style="width: 100%; margin-top: 1.75rem;">
                            CONFIRM & PLACE ORDER
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?php if (!empty($razorpayCheckoutData)): ?>
<!-- Razorpay Checkout.js — Real Payment Gateway -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
(function() {
    function launchRazorpay() {
        if (typeof Razorpay === 'undefined') {
            console.log('Waiting for Razorpay SDK to load...');
            setTimeout(launchRazorpay, 150);
            return;
        }

        try {
            const options = {
                key:         '<?= RAZORPAY_KEY_ID ?>',
                amount:      <?= $razorpayCheckoutData['amount'] ?>,
                currency:    'INR',
                name:        '<?= addslashes(STORE_NAME) ?>',
                description: '<?= addslashes($razorpayCheckoutData['description']) ?>',
                image:       '<?= BASE_URL ?>assets/images/logo.png',
                order_id:    '<?= $razorpayCheckoutData['order_id'] ?>',
                prefill: {
                    name:    '<?= addslashes($razorpayCheckoutData['prefill']['name']) ?>',
                    email:   '<?= addslashes($razorpayCheckoutData['prefill']['email']) ?>',
                    contact: '<?= addslashes($razorpayCheckoutData['prefill']['contact']) ?>'
                },
                theme: {
                    color: '#C9A24B'
                },
                handler: function (response) {
                    // Server-side HMAC Signature Verification
                    const fd = new FormData();
                    fd.append('order_id',            '<?= $razorpayCheckoutData['local_order_id'] ?>');
                    fd.append('razorpay_order_id',   response.razorpay_order_id);
                    fd.append('razorpay_payment_id', response.razorpay_payment_id);
                    fd.append('razorpay_signature',  response.razorpay_signature);
                    fd.append('csrf_token',          '<?= generate_csrf_token() ?>');

                    fetch('<?= BASE_URL ?>includes/razorpay_verify.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            window.location.href = data.redirect;
                        } else {
                            alert('Payment verified but order update failed: ' + (data.message || 'Please contact support.'));
                        }
                    })
                    .catch(() => {
                        alert('Network error during payment verification. Please contact support with Payment ID: ' + response.razorpay_payment_id);
                    });
                },
                modal: {
                    ondismiss: function () {
                        const alertBox = document.getElementById('rzpPendingAlert');
                        if (alertBox) alertBox.style.display = 'flex';
                    }
                }
            };

            const rzp = new Razorpay(options);

            rzp.on('payment.failed', function (resp) {
                alert('Payment Failed: ' + (resp.error ? resp.error.description : 'Transaction cancelled'));
            });

            rzp.open();
        } catch (e) {
            console.error('Error initializing Razorpay:', e);
            alert('Could not initialize payment window: ' + e.message);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', launchRazorpay);
    } else {
        launchRazorpay();
    }
})();
</script>

<!-- Pending Alert (shown if user closes Razorpay modal) -->
<div id="rzpPendingAlert" style="display: none; position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%); background: #1A1A1A; color: #FFF; padding: 1rem 1.5rem; border-radius: 8px; border-left: 4px solid #C9A24B; z-index: 9999; gap: 12px; align-items: center; max-width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
    <i class="fa-solid fa-triangle-exclamation" style="color: #C9A24B; font-size: 1.2rem;"></i>
    <span>Payment window closed. Your order <strong>#<?= sprintf('%06d', $razorpayCheckoutData['local_order_id']) ?></strong> is pending payment. <a href="account.php" style="color: #C9A24B; text-decoration: underline;">Retry from My Account</a>.</span>
</div>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>