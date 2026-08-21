<?php
echo "=== Razorpay API Connection Test ===\n\n";

// Test 1: cURL availability
echo "1. cURL enabled: " . (function_exists('curl_init') ? 'YES' : 'NO') . "\n";

// Test 2: Raw API call WITHOUT SSL verification
$ch = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, 'rzp_test_TRhfK7BdnxZqDz:wBt0nPaR2Jbo55iowwBN9CMr');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'amount'          => 100,
    'currency'        => 'INR',
    'receipt'         => 'diag_test_1',
    'payment_capture' => 1
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
// Try WITH SSL first
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
unset($ch);

echo "2. WITH SSL Verify ON:\n";
echo "   HTTP Code : $httpCode\n";
echo "   cURL Error: " . ($curlError ?: 'none') . "\n";
echo "   Response  : $response\n\n";

// Test 3: WITHOUT SSL verification (XAMPP local fix)
$ch2 = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_USERPWD, 'rzp_test_TRhfK7BdnxZqDz:wBt0nPaR2Jbo55iowwBN9CMr');
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode([
    'amount'          => 100,
    'currency'        => 'INR',
    'receipt'         => 'diag_test_2',
    'payment_capture' => 1
]));
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch2, CURLOPT_TIMEOUT, 15);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);   // <-- bypass SSL cert check
curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$curlError2 = curl_error($ch2);
unset($ch2);

echo "3. WITHOUT SSL Verify (XAMPP fix):\n";
echo "   HTTP Code : $httpCode2\n";
echo "   cURL Error: " . ($curlError2 ?: 'none') . "\n";
echo "   Response  : $response2\n\n";

if ($httpCode2 === 200) {
    $data = json_decode($response2, true);
    echo "=== SUCCESS! Razorpay Order Created ===\n";
    echo "Order ID: " . ($data['id'] ?? 'N/A') . "\n";
    echo "Amount  : " . ($data['amount'] ?? 'N/A') . " paise\n";
    echo "\nFIX NEEDED: Add SSL_VERIFYPEER=false to razorpay_client.php for localhost.\n";
} elseif ($httpCode === 200) {
    echo "=== SSL works fine — something else is failing. Check credentials.\n";
} else {
    echo "=== BOTH FAILED. Check internet connection and cURL PHP extension.\n";
}
