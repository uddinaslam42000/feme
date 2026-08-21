<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Dedicated Online Payment Gateway (Razorpay Real Simulation) Page
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
    redirect('cart.php');
}

// Fetch Order Details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        set_flash_message('error', 'Order reference not found.');
        redirect('cart.php');
    }

    // Fetch Order Items
    $iStmt = $pdo->prepare("
        SELECT oi.*, p.name AS product_name, cat.name AS category_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN categories cat ON p.category_id = cat.id
        WHERE oi.order_id = ?
    ");
    $iStmt->execute([$orderId]);
    $orderItems = $iStmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error.");
}

$grandTotal = (float)$order['total_amount'];
$razorpayOrderId = !empty($order['razorpay_order_id']) ? $order['razorpay_order_id'] : ('order_' . bin2hex(random_bytes(8)));

// Extract Customer Info from Shipping Address or User
$shippingLines = explode("\n", $order['shipping_address']);
$customerName = !empty($order['user_name']) ? $order['user_name'] : ($shippingLines[0] ?? 'Valued Client');
$customerEmail = !empty($order['user_email']) ? $order['user_email'] : 'client@feme.com';
$customerPhone = !empty($order['user_phone']) ? $order['user_phone'] : '+91 9134366366';

$pageTitle = "Razorpay Secure Gateway | FeMe Luxury Closet";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --rzp-blue: #0C2340;
            --rzp-accent: #0284C7;
            --gold-primary: #C9A24B;
            --bg-dark: #111827;
            --font-serif: 'Playfair Display', Georgia, serif;
            --font-sans: 'Poppins', sans-serif;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-sans);
            background-color: #0F172A;
            color: #E2E8F0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Header */
        .rzp-header {
            background-color: #1E293B;
            border-bottom: 2px solid var(--gold-primary);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .rzp-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .rzp-brand img {
            height: 42px;
            width: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 1.5px solid var(--gold-primary);
        }

        .rzp-badge {
            background: rgba(201, 162, 75, 0.15);
            border: 1px solid var(--gold-primary);
            color: var(--gold-primary);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.4rem 0.85rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Main Container */
        .gateway-container {
            max-width: 1000px;
            width: 90%;
            margin: 2.5rem auto;
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 2rem;
            flex: 1;
        }

        @media (max-width: 850px) {
            .gateway-container {
                grid-template-columns: 1fr;
            }
        }

        /* Summary Card */
        .summary-card {
            background-color: #1E293B;
            border-radius: 12px;
            padding: 1.75rem;
            border: 1px solid #334155;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            height: fit-content;
        }

        .merchant-name {
            font-family: var(--font-serif);
            font-size: 1.35rem;
            font-weight: 700;
            color: #FFF;
            margin-bottom: 0.25rem;
        }

        .amount-display {
            font-family: var(--font-serif);
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--gold-primary);
            margin: 1rem 0;
            padding-bottom: 1rem;
            border-bottom: 1px solid #334155;
        }

        .order-meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            margin-bottom: 0.75rem;
            color: #94A3B8;
        }

        .order-meta-row strong {
            color: #F8FAFC;
        }

        .item-mini-list {
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px dashed #334155;
        }

        .item-mini-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.82rem;
            margin-bottom: 0.5rem;
            color: #CBD5E1;
        }

        /* Payment Card */
        .payment-card {
            background-color: #1E293B;
            border-radius: 12px;
            border: 1px solid #334155;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .payment-tabs {
            display: flex;
            background: #0F172A;
            border-bottom: 1px solid #334155;
        }

        .tab-btn {
            flex: 1;
            padding: 1rem 0.75rem;
            background: none;
            border: none;
            color: #94A3B8;
            font-family: var(--font-sans);
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .tab-btn:hover, .tab-btn.active {
            color: var(--gold-primary);
            border-bottom-color: var(--gold-primary);
            background: rgba(201, 162, 75, 0.05);
        }

        .tab-pane {
            padding: 2rem;
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        /* Form Inputs */
        .rzp-input-group {
            margin-bottom: 1.25rem;
        }

        .rzp-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #94A3B8;
            margin-bottom: 6px;
        }

        .rzp-input {
            width: 100%;
            padding: 0.85rem 1rem;
            background-color: #0F172A;
            border: 1px solid #334155;
            border-radius: 6px;
            color: #FFF;
            font-family: inherit;
            font-size: 0.95rem;
            transition: border-color 0.2s ease;
        }

        .rzp-input:focus {
            outline: none;
            border-color: var(--gold-primary);
        }

        .pay-btn-gold {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #C9A24B 0%, #B08A35 100%);
            color: #1A1A1A;
            border: none;
            border-radius: 6px;
            font-family: var(--font-sans);
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(201, 162, 75, 0.3);
        }

        .pay-btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(201, 162, 75, 0.45);
        }

        /* Modal Overlay for Bank 3D OTP Verification */
        .bank-modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100000;
        }

        .bank-modal-overlay.active {
            display: flex;
        }

        .bank-modal {
            background: #FFF;
            color: #1E293B;
            width: 90%;
            max-width: 480px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
            border: 2px solid var(--gold-primary);
            animation: modalPop 0.3s ease;
        }

        @keyframes modalPop {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .bank-header {
            background: #002D62;
            color: #FFF;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="rzp-header">
        <a href="index.php" class="rzp-brand">
            <img src="assets/images/logo.png" alt="FeMe Logo">
            <div>
                <div style="font-family: var(--font-serif); font-size: 1.3rem; font-weight: 700; color: #FFF;">Fe<span style="color: var(--gold-primary);">Me</span></div>
                <div style="font-size: 0.62rem; text-transform: uppercase; letter-spacing: 2px; color: var(--gold-primary);">Razorpay Payment Gateway</div>
            </div>
        </a>

        <div class="rzp-badge">
            <i class="fa-solid fa-shield-halved"></i> 256-BIT SSL ENCRYPTED
        </div>
    </header>

    <!-- Main Gateway Layout -->
    <div class="gateway-container">
        
        <!-- Left: Summary -->
        <div class="summary-card">
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--gold-primary); font-weight: 600;">Merchant Order</div>
            <div class="merchant-name"><?= STORE_NAME ?></div>
            
            <div class="amount-display">
                <?= format_price($grandTotal) ?>
            </div>

            <div class="order-meta-row">
                <span>Order Reference:</span>
                <strong>#<?= sprintf('%06d', $order['id']) ?></strong>
            </div>
            <div class="order-meta-row">
                <span>Customer Name:</span>
                <strong><?= sanitize($customerName) ?></strong>
            </div>
            <div class="order-meta-row">
                <span>Mobile Phone:</span>
                <strong><?= sanitize($customerPhone) ?></strong>
            </div>
            <div class="order-meta-row">
                <span>Email Address:</span>
                <strong style="word-break: break-all;"><?= sanitize($customerEmail) ?></strong>
            </div>

            <div class="item-mini-list">
                <div style="font-size: 0.78rem; text-transform: uppercase; color: #94A3B8; margin-bottom: 0.5rem; font-weight: 600;">Purchased Ensembles</div>
                <?php foreach ($orderItems as $item): ?>
                    <div class="item-mini-row">
                        <span><?= sanitize($item['product_name']) ?> (&times;<?= $item['quantity'] ?>)</span>
                        <strong><?= format_price($item['price'] * $item['quantity']) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 1.5rem; text-align: center; font-size: 0.78rem; color: #64748B;">
                <i class="fa-solid fa-clock"></i> Session Expires in: <span id="paymentTimer" style="color: var(--gold-primary); font-weight: 700;">14:59</span>
            </div>
        </div>

        <!-- Right: Payment Portal -->
        <div class="payment-card">
            <div class="payment-tabs">
                <button type="button" class="tab-btn active" onclick="switchPane('upi', this)">
                    <i class="fa-solid fa-mobile-screen-button"></i> UPI / QR
                </button>
                <button type="button" class="tab-btn" onclick="switchPane('card', this)">
                    <i class="fa-solid fa-credit-card"></i> Card
                </button>
                <button type="button" class="tab-btn" onclick="switchPane('netbank', this)">
                    <i class="fa-solid fa-building-columns"></i> NetBanking
                </button>
                <button type="button" class="tab-btn" onclick="switchPane('wallet', this)">
                    <i class="fa-solid fa-wallet"></i> Wallets
                </button>
            </div>

            <!-- Tab 1: UPI / QR Code -->
            <div id="pane-upi" class="tab-pane active">
                <div style="text-align: center; background: #0F172A; padding: 1.5rem; border-radius: 8px; border: 1px dashed #334155; margin-bottom: 1.5rem;">
                    <div style="font-size: 0.9rem; font-weight: 600; color: #FFF; margin-bottom: 10px;">Scan QR Code using GPay, PhonePe, Paytm, BHIM</div>
                    <div style="width: 150px; height: 150px; background: #FFF; border: 3px solid var(--gold-primary); margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                        <i class="fa-solid fa-qrcode" style="font-size: 5.5rem; color: #0F172A;"></i>
                    </div>
                    <div style="font-size: 0.84rem; color: #94A3B8;">UPI VPA ID: <strong style="color: var(--gold-primary);">feme.luxury@okaxis</strong></div>
                </div>

                <div class="rzp-input-group">
                    <label class="rzp-label">Or Enter Virtual Payment Address (VPA)</label>
                    <input type="text" id="upiIdInput" class="rzp-input" value="<?= strtolower(str_replace(' ', '', $customerName)) ?>@upi" placeholder="username@upi">
                </div>

                <button type="button" onclick="triggerBankModal('UPI / PhonePe App Approval')" class="pay-btn-gold">
                    <i class="fa-solid fa-bolt"></i> PAY <?= format_price($grandTotal) ?> VIA UPI
                </button>
            </div>

            <!-- Tab 2: Credit / Debit Cards -->
            <div id="pane-card" class="tab-pane">
                <div style="background: rgba(201, 162, 75, 0.1); border: 1px solid var(--gold-primary); padding: 0.85rem 1rem; border-radius: 6px; margin-bottom: 1.25rem; font-size: 0.82rem; color: var(--gold-primary);">
                    <i class="fa-solid fa-circle-info"></i> Pre-filled with Razorpay Test Card credentials.
                </div>

                <div class="rzp-input-group">
                    <label class="rzp-label">Card Number</label>
                    <input type="text" class="rzp-input" value="4111 2222 3333 4444" style="letter-spacing: 2px; font-family: monospace;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="rzp-input-group">
                        <label class="rzp-label">Expiry (MM/YY)</label>
                        <input type="text" class="rzp-input" value="12/28">
                    </div>
                    <div class="rzp-input-group">
                        <label class="rzp-label">CVV Code</label>
                        <input type="password" class="rzp-input" value="123" maxlength="4">
                    </div>
                </div>

                <div class="rzp-input-group">
                    <label class="rzp-label">Card Holder Name</label>
                    <input type="text" class="rzp-input" value="<?= sanitize($customerName) ?>">
                </div>

                <button type="button" onclick="triggerBankModal('Visa / MasterCard 3D Secure')" class="pay-btn-gold">
                    <i class="fa-solid fa-lock"></i> PAY <?= format_price($grandTotal) ?> VIA CARD
                </button>
            </div>

            <!-- Tab 3: NetBanking -->
            <div id="pane-netbank" class="tab-pane">
                <div style="font-size: 0.85rem; color: #94A3B8; margin-bottom: 1rem;">Select your NetBanking Institution:</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; margin-bottom: 1.5rem;">
                    <button type="button" onclick="triggerBankModal('HDFC NetBanking')" style="padding: 1rem; background: #0F172A; border: 1px solid #334155; color: #FFF; border-radius: 6px; cursor: pointer; text-align: left; font-weight: 600; font-size: 0.88rem;">
                        <i class="fa-solid fa-building-columns" style="color: var(--gold-primary); margin-right: 8px;"></i> HDFC Bank
                    </button>
                    <button type="button" onclick="triggerBankModal('ICICI NetBanking')" style="padding: 1rem; background: #0F172A; border: 1px solid #334155; color: #FFF; border-radius: 6px; cursor: pointer; text-align: left; font-weight: 600; font-size: 0.88rem;">
                        <i class="fa-solid fa-building-columns" style="color: var(--gold-primary); margin-right: 8px;"></i> ICICI Bank
                    </button>
                    <button type="button" onclick="triggerBankModal('SBI NetBanking')" style="padding: 1rem; background: #0F172A; border: 1px solid #334155; color: #FFF; border-radius: 6px; cursor: pointer; text-align: left; font-weight: 600; font-size: 0.88rem;">
                        <i class="fa-solid fa-building-columns" style="color: var(--gold-primary); margin-right: 8px;"></i> State Bank of India
                    </button>
                    <button type="button" onclick="triggerBankModal('Axis NetBanking')" style="padding: 1rem; background: #0F172A; border: 1px solid #334155; color: #FFF; border-radius: 6px; cursor: pointer; text-align: left; font-weight: 600; font-size: 0.88rem;">
                        <i class="fa-solid fa-building-columns" style="color: var(--gold-primary); margin-right: 8px;"></i> Axis Bank
                    </button>
                </div>
            </div>

            <!-- Tab 4: Wallets -->
            <div id="pane-wallet" class="tab-pane">
                <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-bottom: 1.5rem;">
                    <button type="button" onclick="triggerBankModal('Paytm Wallet')" style="padding: 1rem; background: #0F172A; border: 1px solid #334155; color: #FFF; border-radius: 6px; cursor: pointer; text-align: left; font-weight: 600; font-size: 0.9rem;">
                        <i class="fa-solid fa-wallet" style="color: var(--gold-primary); margin-right: 10px;"></i> Paytm Wallet
                    </button>
                    <button type="button" onclick="triggerBankModal('Amazon Pay')" style="padding: 1rem; background: #0F172A; border: 1px solid #334155; color: #FFF; border-radius: 6px; cursor: pointer; text-align: left; font-weight: 600; font-size: 0.9rem;">
                        <i class="fa-solid fa-wallet" style="color: var(--gold-primary); margin-right: 10px;"></i> Amazon Pay Balance
                    </button>
                </div>
            </div>

            <!-- Cancel Link -->
            <div style="padding: 0 2rem 1.5rem 2rem; text-align: center;">
                <a href="account.php" style="color: #EF4444; font-size: 0.82rem; text-decoration: none;" onclick="return confirm('Are you sure you want to cancel payment? Your order will remain pending.');">
                    <i class="fa-solid fa-circle-xmark"></i> Cancel Payment & Return to Store
                </a>
            </div>

        </div>

    </div>

    <!-- Bank 3D Secure OTP Modal -->
    <div class="bank-modal-overlay" id="bankModal">
        <div class="bank-modal">
            <div class="bank-header">
                <div>
                    <h4 id="bankModalTitle" style="font-size: 1.1rem; font-weight: 700; color: #FFF;">Bank 3D-Secure Verification</h4>
                    <p style="font-size: 0.72rem; color: #94A3B8;">Verified by VISA / MasterCard SecureCode</p>
                </div>
                <i class="fa-solid fa-shield-halved" style="font-size: 1.5rem; color: var(--gold-primary);"></i>
            </div>

            <div style="padding: 1.75rem;">
                <p style="font-size: 0.88rem; color: #475569; margin-bottom: 1rem; line-height: 1.5;">
                    An OTP (One Time Password) has been sent to your registered mobile number ending in <strong style="color: #002D62;"><?= substr($customerPhone, -4) ?></strong>.
                </p>

                <div style="background: #F1F5F9; border: 1px solid #CBD5E1; padding: 1rem; border-radius: 8px; margin-bottom: 1.25rem;">
                    <div style="font-size: 0.78rem; font-weight: 600; color: #64748B; text-transform: uppercase; margin-bottom: 4px;">Transaction Details</div>
                    <div style="font-weight: 700; color: #0F172A; font-size: 0.95rem;">Amount: <?= format_price($grandTotal) ?> &bull; Merchant: FeMe Luxury</div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="font-size: 0.82rem; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">Enter 6-Digit OTP *</label>
                    <input type="text" id="otpInput" value="123456" maxlength="6" style="width: 100%; padding: 0.85rem; font-family: monospace; font-size: 1.4rem; letter-spacing: 6px; text-align: center; border: 2px solid #002D62; border-radius: 6px; font-weight: 700; color: #002D62;">
                    <div style="font-size: 0.75rem; color: #64748B; margin-top: 4px; text-align: center;">Demo OTP pre-filled as <strong>123456</strong></div>
                </div>

                <button type="button" id="submitOtpBtn" onclick="submitBankOtp()" style="width: 100%; padding: 0.85rem; background: #002D62; color: #FFF; border: none; border-radius: 6px; font-family: var(--font-sans); font-size: 0.95rem; font-weight: 700; cursor: pointer;">
                    <i class="fa-solid fa-lock"></i> SUBMIT OTP & AUTHORIZE PAYMENT
                </button>

                <div id="otpStatus" style="margin-top: 1rem; font-size: 0.82rem; text-align: center;"></div>
            </div>
        </div>
    </div>

    <script>
        const localOrderId = <?= $order['id'] ?>;
        const rzpOrderId = '<?= $razorpayOrderId ?>';
        const csrfToken = '<?= generate_csrf_token() ?>';

        function switchPane(paneName, btn) {
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('pane-' + paneName).classList.add('active');
            btn.classList.add('active');
        }

        function triggerBankModal(title) {
            document.getElementById('bankModalTitle').textContent = title + ' - 3D Secure Verification';
            document.getElementById('bankModal').classList.add('active');
        }

        function submitBankOtp() {
            const btn = document.getElementById('submitOtpBtn');
            const status = document.getElementById('otpStatus');

            btn.disabled = true;
            btn.style.opacity = '0.7';
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying with Issuing Bank...';
            status.style.color = '#002D62';
            status.innerHTML = 'Authenticating 3D-Secure transaction with bank servers...';

            const simulatedPayId = 'pay_simulated_' + Date.now();
            const simulatedSig = 'simulated_sig_' + Date.now();

            const formData = new FormData();
            formData.append('order_id', localOrderId);
            formData.append('razorpay_order_id', rzpOrderId);
            formData.append('razorpay_payment_id', simulatedPayId);
            formData.append('razorpay_signature', simulatedSig);
            formData.append('csrf_token', csrfToken);

            setTimeout(() => {
                fetch('includes/razorpay_verify.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        status.style.color = '#16a34a';
                        status.innerHTML = '<i class="fa-solid fa-circle-check"></i> Bank Authorization Approved! Redirecting to tax invoice...';
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                        btn.innerHTML = 'SUBMIT OTP & AUTHORIZE PAYMENT';
                        status.style.color = '#dc2626';
                        status.innerHTML = data.message || 'Payment verification failed.';
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.innerHTML = 'SUBMIT OTP & AUTHORIZE PAYMENT';
                    status.style.color = '#dc2626';
                    status.innerHTML = 'Network error verifying payment.';
                });
            }, 1500);
        }

        // Countdown Timer
        let secondsLeft = 899;
        const timerElem = document.getElementById('paymentTimer');
        setInterval(() => {
            if (secondsLeft <= 0) return;
            secondsLeft--;
            const mins = Math.floor(secondsLeft / 60);
            const secs = secondsLeft % 60;
            timerElem.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        }, 1000);
    </script>
</body>
</html>
