<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Master Header
 */
require_once __DIR__ . '/admin_auth.php';

$currentAdminPage = basename($_SERVER['PHP_SELF']);
$adminName = $_SESSION['user_name'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FeMe Console – Luxury Management</title>
    
    <!-- Google Fonts & FontAwesome Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Master CSS -->
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>

    <div class="admin-wrapper">
        <!-- 1. Left Sidebar Navigation -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-brand" style="display: flex; align-items: center; gap: 12px; padding: 1.25rem 1.5rem;">
                <img src="../assets/images/logo.png" alt="FeMe Logo" style="height: 45px; width: 45px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--admin-gold); flex-shrink: 0;">
                <div>
                    <span class="logo-text" style="font-size: 1.5rem;">Fe<span>Me</span></span>
                    <span class="logo-tagline">Console Panel</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item <?= $currentAdminPage === 'index.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-line"></i> <span>Dashboard</span>
                </a>
                <a href="products.php" class="nav-item <?= $currentAdminPage === 'products.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-shirt"></i> <span>Products</span>
                </a>
                <a href="categories.php" class="nav-item <?= $currentAdminPage === 'categories.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-layer-group"></i> <span>Categories</span>
                </a>
                <a href="orders.php" class="nav-item <?= $currentAdminPage === 'orders.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-bag-shopping"></i> <span>Orders</span>
                </a>
                <a href="couriers.php" class="nav-item <?= $currentAdminPage === 'couriers.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-truck-fast"></i> <span>Couriers</span>
                </a>
                <a href="banners.php" class="nav-item <?= $currentAdminPage === 'banners.php' ? 'active' : '' ?>">
                    <i class="fa-regular fa-image"></i> <span>Hero Banners</span>
                </a>
                <a href="discounts.php" class="nav-item <?= $currentAdminPage === 'discounts.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-tags"></i> <span>Discounts</span>
                </a>
                <a href="customers.php" class="nav-item <?= $currentAdminPage === 'customers.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i> <span>Customers</span>
                </a>
                <a href="customer_logins.php" class="nav-item <?= $currentAdminPage === 'customer_logins.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-user-clock"></i> <span>Customer Logins</span>
                </a>
                <a href="newsletter.php" class="nav-item <?= $currentAdminPage === 'newsletter.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-envelope-open-text"></i> <span>Newsletter</span>
                </a>
                <a href="settings.php" class="nav-item <?= $currentAdminPage === 'settings.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-gears"></i> <span>Store Settings</span>
                </a>
                <a href="activity_log.php" class="nav-item <?= $currentAdminPage === 'activity_log.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-list-check"></i> <span>Activity Log</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item" style="color: #ff4d4f;">
                    <i class="fa-solid fa-arrow-right-from-bracket" style="color: #ff4d4f;"></i> <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- 2. Main Content Wrapper -->
        <main class="admin-main">
            <!-- Topbar Header -->
            <header class="admin-topbar">
                <button class="sidebar-toggle-btn" id="sidebarToggleBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div style="font-size: 0.88rem; color: var(--admin-text-muted);">
                    <a href="../index.php" target="_blank" style="color: var(--admin-gold); font-weight: 600; text-decoration: none;">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Visit Storefront
                    </a>
                </div>

                <div class="admin-user-info">
                    <div class="user-avatar"><?= strtoupper(substr($adminName, 0, 1)) ?></div>
                    <span><?= sanitize($adminName) ?></span>
                </div>
            </header>

            <!-- Admin Content Container -->
            <div class="admin-content">
