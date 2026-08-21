<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Razorpay API Client & Signature Verification Helper
 */

require_once __DIR__ . '/../config.php';

/**
 * Create a new Order in Razorpay API
 *
 * @param float|int $amountInRupees Total order amount in INR
 * @param string    $receipt        Internal reference receipt ID
 * @return array|null Order data array on success, null on API failure
 */
function create_razorpay_order($amountInRupees, $receipt): ?array {
    $amountInPaise = (int)round($amountInRupees * 100);
    
    $payload = json_encode([
        'amount' => $amountInPaise,
        'currency' => 'INR',
        'receipt' => (string)$receipt,
        'payment_capture' => 1
    ]);

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    // Allow SSL on local development where CA certs may not be installed in php.ini
    if (in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || ($_SERVER['SERVER_NAME'] ?? '') === 'localhost') {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);

    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['id'])) {
            return [
                'success'  => true,
                'id'       => $result['id'],
                'amount'   => $amountInPaise,
                'currency' => 'INR'
            ];
        }
    }

    // Real API failed — log and return null so checkout shows user-friendly error
    error_log('[Razorpay] Order creation failed. HTTP ' . $httpCode . ' | Response: ' . $response);
    return null;
}

/**
 * Server-side Razorpay Payment Signature Verification
 *
 * @param string $razorpayOrderId
 * @param string $razorpayPaymentId
 * @param string $razorpaySignature
 * @return bool
 */
function verify_razorpay_signature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature) {
    if (empty($razorpayOrderId) || empty($razorpayPaymentId) || empty($razorpaySignature)) {
        return false;
    }

    // HMAC-SHA256 signature verification using Key Secret
    $expectedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, RAZORPAY_KEY_SECRET);
    return hash_equals($expectedSignature, $razorpaySignature);
}

/**
 * Server-side Razorpay Webhook Signature Verification
 *
 * @param string $payload Raw POST body string
 * @param string $signature HTTP_X_RAZORPAY_SIGNATURE header
 * @return bool
 */
function verify_razorpay_webhook_signature($payload, $signature) {
    if (empty($payload) || empty($signature)) {
        return false;
    }

    $expectedSignature = hash_hmac('sha256', $payload, RAZORPAY_WEBHOOK_SECRET);
    return hash_equals($expectedSignature, $signature);
}
