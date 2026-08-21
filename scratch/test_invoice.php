<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['user_name'] = 'FeMe Admin';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Test Order 100001
$_GET['id'] = 100001;
ob_start();
include __DIR__ . '/../admin/invoice.php';
$html1 = ob_get_clean();
echo "Order 100001 Render Length: " . strlen($html1) . " bytes\n";
echo "Contains Eleanor Vance: " . (strpos($html1, 'Eleanor Vance') !== false ? 'YES' : 'NO') . "\n";
echo "Contains Tax Invoice: " . (strpos($html1, 'Tax Invoice') !== false ? 'YES' : 'NO') . "\n";

// Test Order 100002
$_GET['id'] = 100002;
ob_start();
include __DIR__ . '/../admin/invoice.php';
$html2 = ob_get_clean();
echo "\nOrder 100002 Render Length: " . strlen($html2) . " bytes\n";
echo "Contains Eleanor Vance: " . (strpos($html2, 'Eleanor Vance') !== false ? 'YES' : 'NO') . "\n";
echo "Contains Tax Invoice: " . (strpos($html2, 'Tax Invoice') !== false ? 'YES' : 'NO') . "\n";
