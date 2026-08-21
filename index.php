<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Homepage
 */
require_once __DIR__ . '/includes/header.php';

// Fetch active hero banners
try {
    $bannerStmt = $pdo->prepare("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC");
    $bannerStmt->execute();
    $banners = $bannerStmt->fetchAll();
} catch (PDOException $e) {
    $banners = [];
}

// Fallback banner if table is empty
if (empty($banners)) {
    $banners = [
        [
            'id' => 1,
            'title' => 'Elegance Draped in Distinction',
            'subtitle' => 'Discover the Royal Festive Collection 2026',
            'button_text' => 'Explore Collection',
            'button_link' => 'category.php',
            'image' => 'assets/images/hero_fallback_1.jpg'
        ],
        [
            'id' => 2,
            'title' => 'Heritage Masterpieces',
            'subtitle' => 'Strictly Limited Artisan Creations',
            'button_text' => 'View Limited Edition',
            'button_link' => 'category.php?slug=limited-edition',
            'image' => 'assets/images/hero_fallback_2.jpg'
        ]
    ];
}

// Fetch categories for "The Curations"
try {
    $catStmt = $pdo->prepare("SELECT * FROM categories ORDER BY id ASC");
    $catStmt->execute();
    $categories = $catStmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Fetch New Arrivals (Limit 4 for 1 featured + 3 smaller cards)
try {
    $newStmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug,
               (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) AS primary_image
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_new_arrival = 1
        ORDER BY p.id DESC
        LIMIT 4
    ");
    $newStmt->execute();
    $newArrivals = $newStmt->fetchAll();
} catch (PDOException $e) {
    $newArrivals = [];
}

// Fetch Active Discount Promotion
try {
    $discStmt = $pdo->prepare("SELECT * FROM discounts WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    $discStmt->execute();
    $activeDiscount = $discStmt->fetch();
} catch (PDOException $e) {
    $activeDiscount = null;
}
?>

<!-- 1. Hero Section Slider -->
<section class="hero-slider-section" aria-label="Hero Showcase">
    <div class="hero-slider-container">
        <?php foreach ($banners as $index => $banner): ?>
            <?php 
                $bgStyle = !empty($banner['image']) && file_exists($banner['image']) 
                    ? "background-image: url('{$banner['image']}');" 
                    : "background: linear-gradient(135deg, #1A1A1A 0%, #2A2419 50%, #1A1A1A 100%);";
            ?>
            <div class="hero-slide <?= $index === 0 ? 'active' : '' ?>" style="<?= $bgStyle ?>">
                <div class="hero-slide-overlay"></div>
                <div class="hero-content-card">
                    <p class="hero-subtitle"><?= sanitize($banner['subtitle'] ?? 'Elegance Draped in Distinction') ?></p>
                    <h1 class="hero-title"><?= sanitize($banner['title'] ?? 'FeMe Luxury Closet') ?></h1>
                    <div class="hero-actions">
                        <a href="<?= sanitize($banner['button_link'] ?? 'category.php') ?>" class="btn-gold">
                            <?= sanitize($banner['button_text'] ?? 'Explore Collection') ?>
                        </a>
                        <a href="category.php?slug=designer-wear" class="btn-link-gold">
                            View Lookbook <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Navigation Arrows -->
    <button class="slider-arrow prev" id="prevSlide" aria-label="Previous Slide">
        <i class="fa-solid fa-chevron-left"></i>
    </button>
    <button class="slider-arrow next" id="nextSlide" aria-label="Next Slide">
        <i class="fa-solid fa-chevron-right"></i>
    </button>

    <!-- Navigation Dots -->
    <div class="slider-dots" id="sliderDots"></div>
</section>

<!-- 2. "The Curations" Category Section -->
<section class="section curations-section">
    <div class="container">
        <div class="section-header">
            <p class="section-subtitle">Royal Silhouettes & Heritage Weaves</p>
            <h2 class="section-title">The Curations</h2>
        </div>

        <div class="curations-grid">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                    <?php 
                        // Image resolution or gradient fallback
                        $catImgPath = $cat['image'];
                        $catBgStyle = (!empty($catImgPath) && file_exists($catImgPath)) 
                            ? "background-image: url('{$catImgPath}');" 
                            : "background: linear-gradient(180deg, #242424 0%, #1A1A1A 100%);";
                    ?>
                    <a href="category.php?slug=<?= sanitize($cat['slug']) ?>" class="category-card">
                        <?php if (!empty($catImgPath) && file_exists($catImgPath)): ?>
                            <img src="<?= sanitize($catImgPath) ?>" alt="<?= sanitize($cat['name']) ?>" class="category-img" loading="lazy">
                        <?php else: ?>
                            <div class="category-img" style="<?= $catBgStyle ?>"></div>
                        <?php endif; ?>
                        
                        <div class="category-overlay">
                            <span class="category-pill">FeMe Collection</span>
                            <h3 class="category-title"><?= sanitize($cat['name']) ?></h3>
                            <p class="category-desc"><?= sanitize($cat['description'] ?? 'Explore handcrafted luxury garments woven with distinction.') ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback category cards if db is not connected yet -->
                <a href="category.php?slug=sarees" class="category-card">
                    <div class="category-overlay">
                        <span class="category-pill">Silk & Organza</span>
                        <h3 class="category-title">Sarees</h3>
                        <p class="category-desc">Handcrafted Kanjeevaram and organza sarees woven with pure zari.</p>
                    </div>
                </a>
                <a href="category.php?slug=salwar-suits" class="category-card">
                    <div class="category-overlay">
                        <span class="category-pill">Royal Silhouettes</span>
                        <h3 class="category-title">Salwar Suits</h3>
                        <p class="category-desc">Velvet Anarkalis, Chanderi suits & embroidered ensembles.</p>
                    </div>
                </a>
                <a href="category.php?slug=designer-wear" class="category-card">
                    <div class="category-overlay">
                        <span class="category-pill">Haute Couture</span>
                        <h3 class="category-title">Designer Wear</h3>
                        <p class="category-desc">Bespoke bridal lehengas and opulent evening wear.</p>
                    </div>
                </a>
                <a href="category.php?slug=limited-edition" class="category-card">
                    <div class="category-overlay">
                        <span class="category-pill">Artisan Masterpieces</span>
                        <h3 class="category-title">Limited Edition</h3>
                        <p class="category-desc">Rare collector pieces handcrafted in limited runs.</p>
                    </div>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 3. "New Arrivals" Section -->
<section class="section new-arrivals-section">
    <div class="container">
        <div class="section-header-flex">
            <div class="section-header-left">
                <h2 class="section-title-sm">New Arrivals</h2>
                <p class="section-subtext">Curated exclusivity for the season</p>
            </div>
            <a href="category.php?filter=new" class="view-all-link">
                View All <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <?php if (!empty($newArrivals)): ?>
            <?php 
                $featuredProduct = $newArrivals[0];
                $smallerProducts = array_slice($newArrivals, 1);
            ?>
            <div class="new-arrivals-layout">
                <!-- Left: Large Featured Product Card -->
                <div class="featured-card-wrapper">
                    <a href="product.php?slug=<?= sanitize($featuredProduct['slug']) ?>" class="featured-product-card">
                        <div class="product-img-wrapper">
                            <span class="badge-gold">NEW ARRIVAL</span>
                            <?php 
                                // Image resolution helper
                                $imgSrc = !empty($featuredProduct['primary_image']) && file_exists($featuredProduct['primary_image']) 
                                    ? $featuredProduct['primary_image'] 
                                    : 'assets/images/cat_sarees.jpg';
                            ?>
                            <img src="<?= sanitize($imgSrc) ?>" alt="<?= sanitize($featuredProduct['name']) ?>" loading="lazy">
                        </div>
                        <div class="product-card-info">
                            <div>
                                <span class="product-category-tag"><?= sanitize($featuredProduct['category_name'] ?? 'Luxury Couture') ?></span>
                                <h3 class="product-title"><?= sanitize($featuredProduct['name']) ?></h3>
                            </div>
                            <div class="product-price-wrapper">
                                <?php if (!empty($featuredProduct['discount_price'])): ?>
                                    <span class="price-current"><?= format_price($featuredProduct['discount_price']) ?></span>
                                    <span class="price-original"><?= format_price($featuredProduct['price']) ?></span>
                                <?php else: ?>
                                    <span class="price-current"><?= format_price($featuredProduct['price']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Right: Stacked Grid of Smaller Product Cards -->
                <div class="smaller-cards-grid">
                    <?php foreach ($smallerProducts as $prod): ?>
                        <a href="product.php?slug=<?= sanitize($prod['slug']) ?>" class="smaller-product-card">
                            <div class="product-img-wrapper">
                                <span class="badge-gold">NEW</span>
                                <?php 
                                    $smImgSrc = !empty($prod['primary_image']) && file_exists($prod['primary_image']) 
                                        ? $prod['primary_image'] 
                                        : 'assets/images/cat_suits.jpg';
                                ?>
                                <img src="<?= sanitize($smImgSrc) ?>" alt="<?= sanitize($prod['name']) ?>" loading="lazy">
                            </div>
                            <div class="product-card-info">
                                <div>
                                    <span class="product-category-tag"><?= sanitize($prod['category_name'] ?? 'Collection') ?></span>
                                    <h3 class="product-title"><?= sanitize($prod['name']) ?></h3>
                                </div>
                                <div class="product-price-wrapper">
                                    <?php if (!empty($prod['discount_price'])): ?>
                                        <span class="price-current"><?= format_price($prod['discount_price']) ?></span>
                                        <span class="price-original"><?= format_price($prod['price']) ?></span>
                                    <?php else: ?>
                                        <span class="price-current"><?= format_price($prod['price']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- 4. "Heritage / Limited Offer" Banner Section -->
<section class="offer-banner-section">
    <div class="offer-grid">
        <!-- Left Column: Lifestyle Image -->
        <div class="offer-image-panel" style="background-image: url('assets/images/cat_limited.jpg'); background-color: #1A1A1A;">
        </div>

        <!-- Right Column: Content & Countdown Timer -->
        <div class="offer-content-panel">
            <span class="offer-eyebrow">PRIVATE VIEWING</span>
            <h2 class="offer-heading"><?= sanitize($activeDiscount['title'] ?? 'Royal Festive Offer') ?></h2>
            <p class="offer-desc">
                <?= sanitize($activeDiscount['description'] ?? 'Enjoy up to 15% luxury discount on handpicked Silk Sarees & Designer Outfits.') ?>
            </p>

            <!-- Live Countdown Timer -->
            <?php 
                $endDate = $activeDiscount['end_date'] ?? '2026-10-31 23:59:59';
            ?>
            <div class="timer-container" id="offerCountdown" data-end-date="<?= sanitize($endDate) ?>">
                <div class="timer-box">
                    <span class="timer-num" id="timerHours">48</span>
                    <span class="timer-label">HOURS</span>
                </div>
                <span class="timer-colon">:</span>
                <div class="timer-box">
                    <span class="timer-num" id="timerMins">30</span>
                    <span class="timer-label">MINS</span>
                </div>
                <span class="timer-colon">:</span>
                <div class="timer-box">
                    <span class="timer-num" id="timerSecs">00</span>
                    <span class="timer-label">SECS</span>
                </div>
            </div>

            <div>
                <a href="category.php?slug=limited-edition" class="btn-gold">CLAIM OFFER</a>
            </div>
        </div>
    </div>
</section>

<!-- 5. "About FeMe" Teaser Strip -->
<section class="about-teaser-strip">
    <div class="container">
        <div class="about-teaser-container">
            <h2 class="about-teaser-title">About FeMe</h2>
            <p class="about-teaser-text">
                Born out of a reverence for India's timeless textile heritage, FeMe brings together master weavers, heritage embroiderers, and contemporary luxury design. Every drape is an ode to distinction, crafted for royalty.
            </p>
            <a href="about.php" class="view-all-link">
                Learn Our Story <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
