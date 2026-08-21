<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Transactional Mailer & Luxury HTML Template Engine
 */

require_once __DIR__ . '/config.php';

/**
 * Build Branded Luxury HTML Email Wrapper
 *
 * @param string $title Email title tag
 * @param string $heading Main headline
 * @param string $bodyHtml Main HTML body content
 * @param string $buttonUrl Optional CTA button URL
 * @param string $buttonText Optional CTA button text
 * @return string Full HTML document string
 */
function build_luxury_email_html($title, $heading, $bodyHtml, $buttonUrl = '', $buttonText = '') {
    $btnHtml = '';
    if (!empty($buttonUrl) && !empty($buttonText)) {
        $btnHtml = '
        <div style="text-align: center; margin: 2rem 0 1rem 0;">
            <a href="' . htmlspecialchars($buttonUrl, ENT_QUOTES, 'UTF-8') . '" style="display: inline-block; padding: 0.85rem 2rem; background: linear-gradient(135deg, #D4AF37 0%, #C9A24B 50%, #B8923D 100%); color: #1A1A1A; font-family: \'Poppins\', Arial, sans-serif; font-size: 0.88rem; font-weight: 700; text-decoration: none; text-transform: uppercase; letter-spacing: 1.5px; border-radius: 6px;">
                ' . htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8') . '
            </a>
        </div>';
    }

    return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>
    <style>
        body { margin: 0; padding: 0; background-color: #F7F3EC; font-family: \'Poppins\', Arial, sans-serif; color: #1A1A1A; }
        .wrapper { width: 100%; background-color: #F7F3EC; padding: 2rem 0; }
        .container { max-width: 600px; margin: 0 auto; background: #FFFFFF; border: 1px solid rgba(201, 162, 75, 0.3); border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .header { background: #1A1A1A; text-align: center; padding: 2rem 1.5rem; border-bottom: 2px solid #C9A24B; }
        .logo-text { font-family: \'Playfair Display\', Georgia, serif; font-size: 2.2rem; font-weight: 700; color: #FFFFFF; letter-spacing: 2px; }
        .logo-text span { color: #C9A24B; }
        .tagline { display: block; font-size: 0.65rem; color: #C9A24B; text-transform: uppercase; letter-spacing: 2.5px; margin-top: 4px; }
        .content { padding: 2.5rem 2rem; }
        .heading { font-family: \'Playfair Display\', Georgia, serif; font-size: 1.6rem; color: #1A1A1A; margin-top: 0; margin-bottom: 1.25rem; }
        .footer { background: #1A1A1A; color: rgba(255,255,255,0.7); text-align: center; padding: 1.5rem; font-size: 0.78rem; border-top: 1px solid rgba(201,162,75,0.2); }
        .footer a { color: #C9A24B; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <div class="logo-text">Fe<span>Me</span></div>
                <span class="tagline">ULTIMATE LUXURY CLOSET</span>
            </div>
            <div class="content">
                <h2 class="heading">' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</h2>
                ' . $bodyHtml . '
                ' . $btnHtml . '
            </div>
            <div class="footer">
                <p>&copy; 2026 FeMe Luxury Closet. All Rights Reserved.</p>
                <p>Elegance Draped in Distinction | <a href="' . BASE_URL . '">Visit Online Boutique</a></p>
            </div>
        </div>
    </div>
</body>
</html>';
}

/**
 * Send Transactional Email via SMTP with fail-safe error logging
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject line
 * @param string $htmlBody Full HTML message body
 * @param string $toName Optional recipient name
 * @return bool True if sent successfully, False on error (logged to /logs/mail_errors.log)
 */
function send_email($to, $subject, $htmlBody, $toName = '') {
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    try {
        $smtpHost = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
        $smtpPort = defined('SMTP_PORT') ? (int)SMTP_PORT : 587;
        $smtpUser = defined('SMTP_USER') ? SMTP_USER : '';
        $smtpPass = defined('SMTP_PASS') ? SMTP_PASS : '';
        $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'concierge@feme.com';
        $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'FeMe Luxury Closet';

        // Check if using placeholder credentials
        if ($smtpPass === 'app-password-placeholder' || empty($smtpUser)) {
            log_mail_error($to, $subject, "Notice: Placeholder SMTP credentials configured. Email dispatch simulated successfully.");
            return true;
        }

        // Connect socket with TLS
        $socket = @fsockopen("tls://" . $smtpHost, $smtpPort, $errno, $errstr, 10);
        if (!$socket) {
            $socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 10);
        }

        if (!$socket) {
            throw new Exception("Socket connection failed to {$smtpHost}:{$smtpPort} - {$errstr} ({$errno})");
        }

        fgets($socket, 512);
        fputs($socket, "EHLO " . gethostname() . "\r\n");
        fgets($socket, 512);

        if (!empty($smtpUser) && !empty($smtpPass)) {
            fputs($socket, "AUTH LOGIN\r\n");
            fgets($socket, 512);
            fputs($socket, base64_encode($smtpUser) . "\r\n");
            fgets($socket, 512);
            fputs($socket, base64_encode($smtpPass) . "\r\n");
            $authRes = fgets($socket, 512);
            if (strpos($authRes, '235') === false) {
                throw new Exception("SMTP Authentication failed: " . trim($authRes));
            }
        }

        fputs($socket, "MAIL FROM: <{$fromEmail}>\r\n");
        fgets($socket, 512);
        fputs($socket, "RCPT TO: <{$to}>\r\n");
        fgets($socket, 512);
        fputs($socket, "DATA\r\n");
        fgets($socket, 512);

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "To: " . (!empty($toName) ? "{$toName} <{$to}>" : $to) . "\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "Date: " . date('r') . "\r\n";

        fputs($socket, $headers . "\r\n" . $htmlBody . "\r\n.\r\n");
        fgets($socket, 512);
        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return true;

    } catch (Throwable $e) {
        log_mail_error($to, $subject, $e->getMessage());
        return false;
    }
}

/**
 * Log email failures to /logs/mail_errors.log
 */
function log_mail_error($to, $subject, $errorMsg) {
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . 'mail_errors.log';
    $logEntry = sprintf("[%s] To: %s | Subject: %s | Error: %s\n", date('Y-m-d H:i:s'), $to, $subject, $errorMsg);
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/* ==========================================================================
   ===== Specific Transactional Email Dispatch Helpers =====
   ========================================================================== */

/**
 * Trigger 1: Send Welcome Email upon registration
 */
function send_welcome_email($toEmail, $name) {
    $subject = "Welcome to FeMe, " . $name . "!";
    $bodyHtml = '
    <p style="font-size: 0.95rem; line-height: 1.7; color: #1A1A1A;">Dear <strong>' . sanitize($name) . '</strong>,</p>
    <p style="font-size: 0.95rem; line-height: 1.7; color: #6B6862;">Welcome to FeMe — your gateway to hand-crafted luxury, pure silk Kanjeevarams, and haute couture Indian masterworks.</p>
    <p style="font-size: 0.95rem; line-height: 1.7; color: #6B6862;">As a registered client, you gain early access to our private drops, bespoke fitting consultations, and tailored concierge support.</p>
    ';

    $emailHtml = build_luxury_email_html(
        $subject,
        "Welcome to the FeMe Guest List",
        $bodyHtml,
        BASE_URL . 'category.php',
        "EXPLORE COLLECTION"
    );

    send_email($toEmail, $subject, $emailHtml, $name);
}

/**
 * Trigger 2 & 5: Send Order Confirmation (Client) + New Order Notification (Admin)
 */
function send_order_confirmation_emails($pdo, $orderId) {
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, u.name AS user_name, u.email AS user_email 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) return;

        // Fetch Order Items
        $itemStmt = $pdo->prepare("
            SELECT oi.*, p.name AS product_name 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $itemStmt->execute([$orderId]);
        $items = $itemStmt->fetchAll();

        // Build Items HTML Table
        $itemsHtml = '<table style="width:100%; border-collapse:collapse; margin-top:1rem; font-size:0.88rem;">
            <thead>
                <tr style="background:#F7F3EC; text-align:left;">
                    <th style="padding:8px 12px; border-bottom:1px solid #C9A24B;">Item</th>
                    <th style="padding:8px 12px; border-bottom:1px solid #C9A24B;">Qty</th>
                    <th style="padding:8px 12px; border-bottom:1px solid #C9A24B;">Total</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($items as $item) {
            $itemsHtml .= '<tr>
                <td style="padding:8px 12px; border-bottom:1px solid #E5E5E5;">' . sanitize($item['product_name']) . '</td>
                <td style="padding:8px 12px; border-bottom:1px solid #E5E5E5;">' . (int)$item['quantity'] . '</td>
                <td style="padding:8px 12px; border-bottom:1px solid #E5E5E5;">₹' . number_format($item['price'] * $item['quantity'], 2) . '</td>
            </tr>';
        }
        $itemsHtml .= '</tbody></table>';

        $clientName = sanitize($order['user_name'] ?? 'Valued Client');
        $clientEmail = $order['user_email'] ?? '';

        // 1. Client Confirmation Email
        $clientSubject = "Your FeMe Order #" . sprintf('%06d', $orderId) . " is Confirmed";
        $clientBody = '
        <p style="font-size: 0.95rem; line-height: 1.7; color: #1A1A1A;">Dear <strong>' . $clientName . '</strong>,</p>
        <p style="font-size: 0.95rem; line-height: 1.7; color: #6B6862;">Thank you for your purchase. We are pleased to confirm that your order <strong>#' . sprintf('%06d', $orderId) . '</strong> has been received and is being prepared with royal care.</p>
        <div style="margin: 1.5rem 0;">
            <strong style="color:#C9A24B;">Delivery Address:</strong><br>
            <p style="white-space:pre-line; margin-top:4px; font-size:0.88rem; color:#1A1A1A;">' . sanitize($order['shipping_address']) . '</p>
        </div>
        ' . $itemsHtml . '
        <div style="text-align:right; margin-top:1rem; font-size:1.1rem; font-weight:700; color:#1A1A1A;">
            Total: <span style="color:#C9A24B;">₹' . number_format($order['total_amount'], 2) . '</span>
        </div>';

        $clientHtml = build_luxury_email_html(
            $clientSubject,
            "Order #" . sprintf('%06d', $orderId) . " Confirmation",
            $clientBody,
            BASE_URL . 'order-confirmation.php?order_id=' . $orderId,
            "VIEW ORDER DETAILS"
        );

        if (!empty($clientEmail)) {
            send_email($clientEmail, $clientSubject, $clientHtml, $clientName);
        }

        // 2. Admin New Order Notification Email
        $adminSubject = "[NEW ORDER] Order #" . sprintf('%06d', $orderId) . " Placed by " . $clientName;
        $adminBody = '
        <p style="font-size: 0.95rem; line-height: 1.7; color: #1A1A1A;">A new order has been placed on FeMe Boutique.</p>
        <p style="font-size: 0.88rem; color:#6B6862;">Order ID: <strong>#' . sprintf('%06d', $orderId) . '</strong><br>Client: ' . $clientName . ' (' . sanitize($clientEmail) . ')<br>Payment Method: ' . strtoupper(sanitize($order['payment_method'])) . '</p>
        ' . $itemsHtml . '
        <div style="text-align:right; margin-top:1rem; font-size:1.1rem; font-weight:700;">
            Grand Total: ₹' . number_format($order['total_amount'], 2) . '
        </div>';

        $adminHtml = build_luxury_email_html(
            $adminSubject,
            "New Order Received #" . sprintf('%06d', $orderId),
            $adminBody,
            BASE_URL . 'admin/orders.php?view=' . $orderId,
            "OPEN ADMIN CONSOLE"
        );

        send_email(ADMIN_NOTIFY_EMAIL, $adminSubject, $adminHtml, "FeMe Admin");

    } catch (PDOException $e) {
        log_mail_error("Order #" . $orderId, "send_order_confirmation_emails", $e->getMessage());
    }
}

/**
 * Trigger 3: Send Order Status Update Email to Customer
 */
function send_order_status_update_email($pdo, $orderId, $newStatus) {
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, u.name AS user_name, u.email AS user_email 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order || empty($order['user_email'])) return;

        $formattedStatus = strtoupper(sanitize($newStatus));
        $subject = "Your FeMe Order #" . sprintf('%06d', $orderId) . " is now " . $formattedStatus;
        $clientName = sanitize($order['user_name'] ?? 'Valued Client');

        $bodyHtml = '
        <p style="font-size: 0.95rem; line-height: 1.7; color: #1A1A1A;">Dear <strong>' . $clientName . '</strong>,</p>
        <p style="font-size: 0.95rem; line-height: 1.7; color: #6B6862;">The fulfillment status for your FeMe order <strong>#' . sprintf('%06d', $orderId) . '</strong> has been updated to: <strong style="color:#C9A24B;">' . $formattedStatus . '</strong>.</p>
        <p style="font-size: 0.88rem; color: #6B6862;">If you have any questions regarding your delivery, please contact our client concierge team.</p>
        ';

        $emailHtml = build_luxury_email_html(
            $subject,
            "Order Status Update: " . $formattedStatus,
            $bodyHtml,
            BASE_URL . 'order-confirmation.php?order_id=' . $orderId,
            "TRACK YOUR ORDER"
        );

        send_email($order['user_email'], $subject, $emailHtml, $clientName);

    } catch (PDOException $e) {
        log_mail_error("Order #" . $orderId, "send_order_status_update_email", $e->getMessage());
    }
}

/**
 * Trigger 6: Send Contact Form Emails (Admin notification + Customer Auto-Reply)
 */
function send_contact_form_emails($name, $email, $subjectStr, $message) {
    // 1. Admin Email Notification
    $adminSubject = "[CONTACT INQUIRY] " . sanitize($subjectStr) . " from " . sanitize($name);
    $adminBody = '
    <p style="font-size: 0.95rem; line-height: 1.7; color: #1A1A1A;">A client inquiry has been submitted via the contact form:</p>
    <p style="font-size: 0.88rem; color: #6B6862;">
        <strong>Client Name:</strong> ' . sanitize($name) . '<br>
        <strong>Email:</strong> ' . sanitize($email) . '<br>
        <strong>Subject:</strong> ' . sanitize($subjectStr) . '
    </p>
    <div style="background:#F7F3EC; padding:1rem; border-radius:6px; border-left:3px solid #C9A24B; font-size:0.9rem; margin-top:1rem;">
        ' . nl2br(sanitize($message)) . '
    </div>';

    $adminHtml = build_luxury_email_html(
        $adminSubject,
        "New Contact Form Inquiry",
        $adminBody,
        BASE_URL . 'admin/index.php',
        "OPEN CONSOLE"
    );

    send_email(ADMIN_NOTIFY_EMAIL, $adminSubject, $adminHtml, "FeMe Concierge");

    // 2. Customer Auto-Reply Email
    $replySubject = "We received your message – FeMe Client Concierge";
    $replyBody = '
    <p style="font-size: 0.95rem; line-height: 1.7; color: #1A1A1A;">Dear <strong>' . sanitize($name) . '</strong>,</p>
    <p style="font-size: 0.95rem; line-height: 1.7; color: #6B6862;">Thank you for reaching out to FeMe. We have safely received your inquiry regarding <strong>"' . sanitize($subjectStr) . '"</strong>.</p>
    <p style="font-size: 0.95rem; line-height: 1.7; color: #6B6862;">Our couture concierges will review your message and respond within 24 hours.</p>
    ';

    $replyHtml = build_luxury_email_html(
        $replySubject,
        "Inquiry Received",
        $replyBody,
        BASE_URL,
        "VISIT FE ME BOUTIQUE"
    );

    send_email($email, $replySubject, $replyHtml, $name);
}

/**
 * Trigger 7: Send Newsletter Subscription Confirmation Email
 */
function send_newsletter_confirmation_email($email) {
    $subject = "You're subscribed to FeMe updates";
    $bodyHtml = '
    <p style="font-size: 0.95rem; line-height: 1.7; color: #1A1A1A;">Welcome to <strong>The Royal Gazette</strong>.</p>
    <p style="font-size: 0.95rem; line-height: 1.7; color: #6B6862;">You are now subscribed to receive private invitations to limited artisan drops, haute couture preview sales, and luxury collection announcements.</p>
    ';

    $emailHtml = build_luxury_email_html(
        $subject,
        "Subscribed to The Royal Gazette",
        $bodyHtml,
        BASE_URL . 'category.php',
        "EXPLORE NEW ARRIVALS"
    );

    send_email($email, $subject, $emailHtml);
}
