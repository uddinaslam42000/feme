<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Authentication & Inactivity Timeout Guard
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Check if user is logged in and has admin role
if (!is_logged_in() || !is_admin()) {
    header("Location: " . BASE_URL . "admin/login.php");
    exit;
}

// 2. Admin 30-Minute Inactivity Session Timeout Guard
$maxInactivity = 1800; // 30 minutes in seconds

if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > $maxInactivity)) {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
    unset($_SESSION['user_role']);
    unset($_SESSION['admin_last_activity']);
    session_destroy();

    header("Location: " . BASE_URL . "admin/login.php?error=timeout");
    exit;
}

$_SESSION['admin_last_activity'] = time();

// 3. Re-verify admin role against DB on every request for hardened security
try {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $role = $stmt->fetchColumn();

    if ($role !== 'admin') {
        session_destroy();
        header("Location: " . BASE_URL . "admin/login.php");
        exit;
    }
} catch (PDOException $e) {
    // Fallback
}
