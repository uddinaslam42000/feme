<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Logout Script
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);
unset($_SESSION['user_role']);

session_destroy();

header("Location: " . BASE_URL . "admin/login.php");
exit;
