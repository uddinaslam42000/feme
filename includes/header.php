<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Master Header Template
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$cartCount = get_cart_count($pdo);

// Determine current active page/slug
$currentSlug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FeMe – Ultimate Luxury Closet</title>
    <meta name="description" content="Elegance draped in distinction. Discover luxury sarees, salwar suits, haute couture designer wear, and limited edition artisan masterworks.">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- FontAwesome / Feather Icons SVG Support -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

    <!-- 1. Top Promo Strip -->
    <div class="promo-strip" id="promoStrip">
        <span>✦ 30% OFF SELECT DESIGNER WEAR — LIMITED TIME OFFER ✦</span>
        <button class="close-promo" id="closePromo" aria-label="Close Announcement">&times;</button>
    </div>

    <!-- 2. Sticky Navbar -->
    <header class="main-header">
        <div class="container">
            <nav class="navbar">
                <!-- Brand Logo -->
                <a href="index.php" class="brand-logo">
                    <img src="<?= BASE_URL ?>assets/images/logo.png" alt="FeMe Luxury Closet Logo" class="brand-logo-img">
                    <div class="brand-logo-text-wrapper">
                        <span class="logo-text">Fe<span>Me</span></span>
                        <span class="logo-tagline">Ultimate Luxury Closet</span>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <ul class="nav-menu">
                    <li>
                        <a href="category.php?slug=sarees" class="nav-link <?= $currentSlug === 'sarees' ? 'active' : '' ?>">Sarees</a>
                    </li>
                    <li>
                        <a href="category.php?slug=salwar-suits" class="nav-link <?= $currentSlug === 'salwar-suits' ? 'active' : '' ?>">Salwar Suits</a>
                    </li>
                    <li>
                        <a href="category.php?slug=designer-wear" class="nav-link <?= $currentSlug === 'designer-wear' ? 'active' : '' ?>">Designer Wear</a>
                    </li>
                    <li>
                        <a href="bespoke-fitting.php" class="nav-link">Bespoke Fitting</a>
                    </li>
                    <li class="nav-dropdown">
                        <a href="#" class="nav-link">More <i class="fa-solid fa-angle-down" style="font-size: 0.75rem; margin-left: 3px;"></i></a>
                        <div class="nav-dropdown-menu">
                            <a href="about.php" class="nav-dropdown-item"><i class="fa-regular fa-compass" style="color: var(--gold-primary); margin-right: 8px;"></i> About FeMe</a>
                            <a href="contact.php" class="nav-dropdown-item"><i class="fa-regular fa-envelope" style="color: var(--gold-primary); margin-right: 8px;"></i> Contact & Appointments</a>
                            <a href="shipping-returns.php" class="nav-dropdown-item"><i class="fa-solid fa-truck-fast" style="color: var(--gold-primary); margin-right: 8px;"></i> Shipping & Returns</a>
                            <a href="privacy-policy.php" class="nav-dropdown-item"><i class="fa-solid fa-shield-halved" style="color: var(--gold-primary); margin-right: 8px;"></i> Privacy Policy</a>
                        </div>
                    </li>
                </ul>

                <!-- Header Action Buttons -->
                <div class="header-actions">
                    <!-- Search Icon Button -->
                    <button class="icon-btn" id="searchToggle" title="Search Products" aria-label="Search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>

                    <!-- Cart Icon Button -->
                    <a href="cart.php" class="icon-btn" title="Shopping Cart" aria-label="Cart">
                        <i class="fa-solid fa-bag-shopping"></i>
                        <span class="cart-count-badge" id="cartBadge"><?= $cartCount ?></span>
                    </a>

                    <!-- User Account / Login Button -->
                    <a href="<?= isset($_SESSION['user_id']) ? ($_SESSION['user_role'] === 'admin' ? 'admin/index.php' : 'account.php') : 'login.php' ?>" class="icon-btn" title="Account" aria-label="Account">
                        <i class="fa-regular fa-user"></i>
                    </a>

                    <!-- Mobile Hamburger Menu Button -->
                    <button class="icon-btn mobile-toggle" id="mobileToggle" aria-label="Toggle Menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                </div>
            </nav>
        </div>
    </header>

    <!-- Mobile Slide-in Navigation Drawer -->
    <div class="mobile-drawer-overlay" id="drawerOverlay"></div>
    <aside class="mobile-drawer" id="mobileDrawer">
        <div class="drawer-header">
            <div class="brand-logo">
                <span class="logo-text">Fe<span>Me</span></span>
                <span class="logo-tagline">Ultimate Luxury Closet</span>
            </div>
            <button class="drawer-close" id="drawerClose" aria-label="Close Menu">&times;</button>
        </div>
        <ul class="mobile-nav-links">
            <li><a href="index.php" class="nav-link">Home</a></li>
            <li><a href="category.php?slug=sarees" class="nav-link">Sarees</a></li>
            <li><a href="category.php?slug=salwar-suits" class="nav-link">Salwar Suits</a></li>
            <li><a href="category.php?slug=designer-wear" class="nav-link">Designer Wear</a></li>
            <li><a href="bespoke-fitting.php" class="nav-link">Bespoke Fitting</a></li>
            <li style="margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <a href="about.php" class="nav-link">About FeMe</a>
            </li>
            <li><a href="contact.php" class="nav-link">Contact & Appointments</a></li>
            <li><a href="shipping-returns.php" class="nav-link">Shipping & Returns</a></li>
            <li><a href="privacy-policy.php" class="nav-link">Privacy Policy</a></li>
        </ul>
    </aside>

    <!-- Search Modal Overlay -->
    <div class="search-modal-overlay" id="searchModal">
        <div class="search-box-container">
            <button class="close-search-btn" id="searchClose">&times;</button>
            <form action="category.php" method="GET" class="search-form">
                <input type="text" name="search" id="searchInput" class="search-input" placeholder="Search luxury sarees, salwar suits, lehengas..." required>
                <button type="submit" class="search-submit-btn">SEARCH</button>
            </form>
        </div>
    </div>
