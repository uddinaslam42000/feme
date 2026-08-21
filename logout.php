<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Customer & User Logout Handler
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Clear persistent login token from DB and cookie
$currentUserId = $_SESSION['user_id'] ?? null;
clear_persistent_login_token($pdo, $currentUserId);

// Unset all session variables
$_SESSION = array();

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to login page with success flash
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
set_flash_message('success', 'You have been logged out successfully.');
redirect('login.php');
