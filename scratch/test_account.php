<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 2;
$_SESSION['user_role'] = 'customer';
$_SESSION['user_name'] = 'Eleanor Vance';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

ob_start();
include __DIR__ . '/../account.php';
$html = ob_get_clean();

echo "Account Panel HTML Output Size: " . strlen($html) . " bytes\n";
echo "Contains Eleanor Vance: " . (strpos($html, 'Eleanor Vance') !== false ? 'YES' : 'NO') . "\n";
echo "Contains Order #100001: " . (strpos($html, '100001') !== false ? 'YES' : 'NO') . "\n";
echo "Contains Track Package Live: " . (strpos($html, 'Track Package Live') !== false ? 'YES' : 'NO') . "\n";
