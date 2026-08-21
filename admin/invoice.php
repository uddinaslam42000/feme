<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Official A4 Tax Invoice & Order Receipt Generator
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin_auth.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$autoPrint = isset($_GET['print']) && $_GET['print'] == '1';

if ($orderId <= 0) {
    die("Invalid Order ID specified.");
}

// Fetch Order, Customer & Courier Details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               u.name AS user_name, u.email AS user_email, u.phone AS user_phone, u.address AS user_profile_address,
               c.name AS courier_name, c.code AS courier_code, c.phone AS courier_phone
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        LEFT JOIN couriers c ON o.courier_id = c.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        die("Order #" . sprintf('%06d', $orderId) . " not found in database.");
    }

    // Fetch Order Items
    $itemStmt = $pdo->prepare("
        SELECT oi.*, p.name AS product_name, p.gst_percent, cat.name AS category_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN categories cat ON p.category_id = cat.id
        WHERE oi.order_id = ?
    ");
    $itemStmt->execute([$orderId]);
    $orderItems = $itemStmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Calculate Dynamic GST Tax Breakdown
$grandTotal = (float)$order['total_amount'];
$taxableValue = 0;
$totalTax = 0;

foreach ($orderItems as &$item) {
    $itemGstRate = (float)($item['gst_percent'] ?? 5.00);
    $itemLineTotal = (float)($item['price'] * $item['quantity']);
    $itemTaxable = round($itemLineTotal / (1 + ($itemGstRate / 100)), 2);
    $itemTaxAmount = round($itemLineTotal - $itemTaxable, 2);

    $item['gst_rate'] = $itemGstRate;
    $item['taxable_value'] = $itemTaxable;
    $item['tax_amount'] = $itemTaxAmount;

    $taxableValue += $itemTaxable;
    $totalTax += $itemTaxAmount;
}
unset($item);

$cgst = round($totalTax / 2, 2);
$sgst = round($totalTax - $cgst, 2);
$amountInWords = number_to_words_indian($grandTotal);

$invoiceNo = 'INV-' . date('Y', strtotime($order['created_at'])) . '-' . sprintf('%06d', $order['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - <?= $invoiceNo ?> | FeMe Luxury Closet</title>
    
    <!-- Google Fonts & FontAwesome Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --invoice-primary: #1A1A1A;
            --invoice-gold: #C9A24B;
            --invoice-border: #E5D9C5;
            --invoice-bg-light: #FDFBF7;
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
            background-color: #F4F1EA;
            color: var(--invoice-primary);
            line-height: 1.5;
            padding: 20px 0;
        }

        /* Non-Printable Action Bar */
        .action-bar {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #FFF;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid var(--invoice-border);
        }

        .btn-print {
            background: linear-gradient(135deg, #D4AF37 0%, #C9A24B 100%);
            color: #1A1A1A;
            border: none;
            padding: 10px 22px;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-print:hover {
            background: #B8923D;
            color: #FFF;
        }

        .btn-back {
            background: #E5E5E5;
            color: #333;
            border: none;
            padding: 10px 18px;
            font-size: 0.88rem;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }

        /* Standard A4 Page Layout */
        .invoice-box {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #FFF;
            padding: 15mm 18mm;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #E2D9C8;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Header Section */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 18px;
            border-bottom: 2px solid var(--invoice-gold);
            margin-bottom: 20px;
        }

        .brand-logo {
            font-family: var(--font-serif);
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--invoice-primary);
            letter-spacing: 2px;
            line-height: 1;
        }

        .brand-logo span {
            color: var(--invoice-gold);
        }

        .brand-tagline {
            font-size: 0.72rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--invoice-gold);
            margin-top: 4px;
            font-weight: 600;
        }

        .store-details {
            font-size: 0.82rem;
            color: #4A4A4A;
            margin-top: 8px;
            line-height: 1.45;
        }

        .invoice-title-block {
            text-align: right;
        }

        .invoice-badge {
            display: inline-block;
            background: var(--invoice-primary);
            color: var(--invoice-gold);
            padding: 6px 16px;
            font-family: var(--font-serif);
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .invoice-meta-table {
            font-size: 0.82rem;
            margin-left: auto;
        }

        .invoice-meta-table td {
            padding: 2px 6px;
        }

        .invoice-meta-table td.label {
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            font-size: 0.75rem;
        }

        /* Addresses Grid */
        .address-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 20px;
            margin-bottom: 22px;
        }

        .address-card {
            background: var(--invoice-bg-light);
            border: 1px solid var(--invoice-border);
            border-radius: 6px;
            padding: 14px;
        }

        .card-title {
            font-family: var(--font-serif);
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--invoice-gold);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            border-bottom: 1px solid var(--invoice-border);
            padding-bottom: 4px;
        }

        .customer-name {
            font-weight: 700;
            font-size: 1rem;
            color: var(--invoice-primary);
            margin-bottom: 4px;
        }

        .address-text {
            font-size: 0.85rem;
            color: #333;
            white-space: pre-line;
            line-height: 1.45;
        }

        /* Itemized Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 0.86rem;
        }

        .items-table th {
            background: var(--invoice-primary);
            color: var(--invoice-gold);
            font-family: var(--font-serif);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 10px 12px;
            text-align: left;
        }

        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #EEE;
            vertical-align: middle;
        }

        .items-table tr:nth-child(even) {
            background-color: #FAF8F5;
        }

        /* Calculation Summary Grid */
        .summary-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .words-box {
            background: var(--invoice-bg-light);
            border: 1px solid var(--invoice-border);
            border-radius: 6px;
            padding: 12px 16px;
            font-size: 0.85rem;
        }

        .words-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--invoice-gold);
            margin-bottom: 4px;
        }

        .calculation-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .calculation-table td {
            padding: 6px 10px;
            text-align: right;
        }

        .calculation-table td.label {
            color: #555;
            font-weight: 500;
        }

        .calculation-table tr.total-row {
            border-top: 2px solid var(--invoice-gold);
            border-bottom: 2px solid var(--invoice-gold);
            font-weight: 700;
            font-size: 1.05rem;
            background: #FDFBF7;
        }

        .calculation-table tr.total-row td {
            color: var(--invoice-primary);
            padding: 10px;
        }

        /* Terms & Signature Footer */
        .invoice-footer {
            border-top: 1px solid var(--invoice-border);
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 0.78rem;
            color: #666;
        }

        .terms-block {
            max-width: 60%;
        }

        .signature-block {
            text-align: center;
            min-width: 160px;
        }

        .signature-line {
            width: 100%;
            border-top: 1px dashed #999;
            margin-top: 40px;
            padding-top: 6px;
            font-weight: 600;
            color: var(--invoice-primary);
        }

        /* Print Media CSS Formatting for Exact A4 Page */
        @media print {
            body {
                background: #FFF !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .action-bar, .no-print {
                display: none !important;
            }

            .invoice-box {
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            @page {
                size: A4 portrait;
                margin: 12mm 15mm 12mm 15mm;
            }

            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    </style>
</head>
<body>

    <!-- Non-Printable Floating Action Header -->
    <div class="action-bar no-print">
        <div>
            <a href="orders.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Orders</a>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn-print" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Print A4 Receipt / Save PDF
            </button>
        </div>
    </div>

    <!-- Printable A4 Receipt Sheet -->
    <div class="invoice-box">
        <div>
            <!-- 1. Header & Store Info -->
            <div class="invoice-header">
                <div>
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                        <img src="../assets/images/logo.png" alt="FeMe Luxury Closet Logo" style="height: 75px; width: 75px; border-radius: 50%; object-fit: cover; border: 2px solid var(--invoice-gold); box-shadow: 0 4px 12px rgba(201, 162, 75, 0.25);">
                        <div>
                            <div class="brand-logo">Fe<span>Me</span></div>
                            <div class="brand-tagline"><?= STORE_TAGLINE ?></div>
                        </div>
                    </div>
                    <div class="store-details">
                        <strong>GSTIN:</strong> <code><?= STORE_GSTIN ?></code><br>
                        <strong>Shop Address:</strong> <?= STORE_ADDRESS ?><br>
                        <strong>Shop Mobile:</strong> <?= STORE_PHONE ?> | <strong>Email:</strong> <?= STORE_EMAIL ?>
                    </div>
                </div>

                <div class="invoice-title-block">
                    <div class="invoice-badge">Tax Invoice</div>
                    <table class="invoice-meta-table">
                        <tr>
                            <td class="label">Invoice No:</td>
                            <td><strong><?= $invoiceNo ?></strong></td>
                        </tr>
                        <tr>
                            <td class="label">Invoice Date:</td>
                            <td><?= date('d-M-Y H:i', strtotime($order['created_at'])) ?></td>
                        </tr>
                        <tr>
                            <td class="label">Order Ref #:</td>
                            <td><strong>#<?= sprintf('%06d', $order['id']) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="label">Payment Mode:</td>
                            <td><strong style="text-transform: uppercase;"><?= sanitize($order['payment_method']) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="label">Payment Status:</td>
                            <td>
                                <strong style="color: <?= $order['payment_status'] === 'paid' ? '#389e0d' : '#d48806' ?>; text-transform: uppercase;">
                                    <?= sanitize($order['payment_status']) ?>
                                </strong>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- 2. Addresses & Courier Logistics Grid -->
            <div class="address-grid">
                <!-- Customer Details -->
                <div class="address-card">
                    <div class="card-title"><i class="fa-solid fa-user"></i> Billed & Shipped To</div>
                    <div class="customer-name"><?= sanitize($order['user_name'] ?? 'Valued Client') ?></div>
                    <div style="font-size: 0.83rem; color: #555; margin-bottom: 6px;">
                        <strong>Mobile:</strong> <?= sanitize($order['user_phone'] ?? 'N/A') ?><br>
                        <strong>Email:</strong> <?= sanitize($order['user_email'] ?? 'N/A') ?>
                    </div>
                    <div class="address-text">
                        <strong>Shipping Address:</strong><br><?= sanitize($order['shipping_address']) ?>
                    </div>
                </div>

                <!-- Logistics & Payment Reference Details -->
                <div class="address-card">
                    <div class="card-title"><i class="fa-solid fa-truck-fast"></i> Logistics & References</div>
                    <div style="font-size: 0.83rem; line-height: 1.6;">
                        <div><strong>Assigned Courier:</strong> <?= sanitize($order['courier_name'] ?? 'Unassigned / Self Dispatch') ?></div>
                        <div><strong>AWB / Tracking #:</strong> <code><?= sanitize($order['tracking_number'] ?? 'N/A') ?></code></div>
                        <?php if (!empty($order['shipped_at'])): ?>
                            <div><strong>Dispatch Date:</strong> <?= date('d-M-Y', strtotime($order['shipped_at'])) ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($order['razorpay_order_id'])): ?>
                            <div style="margin-top: 8px; border-top: 1px dashed #DDD; padding-top: 6px;">
                                <div><strong>Razorpay Order:</strong> <code><?= sanitize($order['razorpay_order_id']) ?></code></div>
                                <div><strong>Razorpay Payment:</strong> <code><?= sanitize($order['razorpay_payment_id'] ?? 'Pending') ?></code></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 3. Itemized Products Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;">#</th>
                        <th style="width: 45%;">Description of Ensembles & Products</th>
                        <th style="width: 15%;">HSN Code</th>
                        <th style="width: 8%; text-align: center;">Qty</th>
                        <th style="width: 12%; text-align: right;">Unit Price</th>
                        <th style="width: 15%; text-align: right;">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orderItems)): ?>
                        <?php $sn = 1; foreach ($orderItems as $item): ?>
                            <tr>
                                <td style="text-align: center; color: #777;"><?= $sn++ ?></td>
                                <td>
                                    <strong style="color: var(--invoice-primary);"><?= sanitize($item['product_name']) ?></strong>
                                    <div style="font-size: 0.75rem; color: #777;"><?= sanitize($item['category_name'] ?? 'Luxury Couture') ?></div>
                                </td>
                                <td><code>6204.29</code></td>
                                <td style="text-align: center;"><strong><?= $item['quantity'] ?></strong></td>
                                <td style="text-align: right;"><?= format_price($item['price']) ?></td>
                                <td style="text-align: right;"><strong><?= format_price($item['price'] * $item['quantity']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; padding: 15px;">No item details available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- 4. Summary & Calculations -->
            <div class="summary-grid">
                <div class="words-box">
                    <div class="words-title">Amount in Words</div>
                    <div style="font-weight: 600; color: var(--invoice-primary); font-size: 0.9rem;">
                        <?= $amountInWords ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #777; margin-top: 8px;">
                        * Prices are inclusive of all statutory Central & State GST taxes.
                    </div>
                </div>

                <div>
                    <table class="calculation-table">
                        <tr>
                            <td class="label">Taxable Value (Subtotal):</td>
                            <td><?= format_price($taxableValue) ?></td>
                        </tr>
                        <tr>
                            <td class="label">CGST (2.5%):</td>
                            <td><?= format_price($cgst) ?></td>
                        </tr>
                        <tr>
                            <td class="label">SGST (2.5%):</td>
                            <td><?= format_price($sgst) ?></td>
                        </tr>
                        <tr>
                            <td class="label">Shipping & Delivery:</td>
                            <td><strong style="color: #389e0d;">FREE</strong></td>
                        </tr>
                        <tr class="total-row">
                            <td class="label" style="text-align: right;">Grand Total:</td>
                            <td><?= format_price($grandTotal) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- 5. Footer, Terms & Authorized Signatory -->
        <div class="invoice-footer">
            <div class="terms-block">
                <strong style="color: var(--invoice-primary);">Terms & Conditions:</strong>
                <ol style="margin-left: 14px; margin-top: 4px; line-height: 1.4;">
                    <li>Goods once sold are eligible for luxury exchange within 7 days.</li>
                    <li>All legal matters are subject to Mumbai Jurisdiction.</li>
                    <li>This is a computer-generated tax receipt.</li>
                </ol>
                <div style="margin-top: 8px; font-weight: 600; color: var(--invoice-gold);">
                    Thank you for choosing FeMe Luxury Closet!
                </div>
            </div>

            <div class="signature-block">
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--invoice-gold); text-transform: uppercase;">
                    For FeMe Luxury Closet
                </div>
                <div class="signature-line">
                    Authorized Signatory
                </div>
            </div>
        </div>
    </div>

    <?php if ($autoPrint): ?>
        <script>
            window.onload = function() {
                window.print();
            };
        </script>
    <?php endif; ?>

</body>
</html>
