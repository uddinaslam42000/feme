<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Category & Collection Listing Page
 */
require_once __DIR__ . '/includes/header.php';

// Sanitized Input Parameters
$categorySlug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$fabricFilter = isset($_GET['fabric']) ? sanitize($_GET['fabric']) : '';
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'newest';
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Category Header Details
$pageTitle = 'All Collections';
$pageSubtext = 'Explore handcrafted Indian luxury & heritage masterworks';
$bannerImg = 'assets/images/cat_sarees.jpg';
$currentCategory = null;

if (!empty($categorySlug)) {
    try {
        $catStmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
        $catStmt->execute([$categorySlug]);
        $currentCategory = $catStmt->fetch();
        if ($currentCategory) {
            $pageTitle = $currentCategory['name'];
            $pageSubtext = $currentCategory['description'] ?? 'Elegance draped in distinction.';
            if (!empty($currentCategory['image']) && file_exists($currentCategory['image'])) {
                $bannerImg = $currentCategory['image'];
            }
        }
    } catch (PDOException $e) {
        // Fallback title
    }
} elseif ($filter === 'new') {
    $pageTitle = 'New Arrivals';
    $pageSubtext = 'Curated exclusivity & fresh seasonal creations';
} elseif ($filter === 'featured') {
    $pageTitle = 'Featured Collection';
    $pageSubtext = 'Most coveted luxury designs of the season';
} elseif (!empty($search)) {
    $pageTitle = 'Search Results';
    $pageSubtext = 'Showing items matching "' . sanitize($search) . '"';
}

// Fetch all available categories for sidebar
$allCategories = get_all_categories($pdo);

// Fetch distinct fabrics for filter
try {
    $fabricStmt = $pdo->query("SELECT DISTINCT fabric FROM products WHERE fabric IS NOT NULL AND fabric != ''");
    $fabrics = $fabricStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $fabrics = [];
}

// Build Dynamic SQL Query & Parameters
$whereClauses = [];
$queryParams = [];

if ($currentCategory) {
    $whereClauses[] = "p.category_id = ?";
    $queryParams[] = $currentCategory['id'];
}

if ($filter === 'new') {
    $whereClauses[] = "p.is_new_arrival = 1";
} elseif ($filter === 'featured') {
    $whereClauses[] = "p.is_featured = 1";
}

if (!empty($search)) {
    $whereClauses[] = "(p.name LIKE ? OR p.description LIKE ? OR p.fabric LIKE ?)";
    $searchParam = "%{$search}%";
    $queryParams[] = $searchParam;
    $queryParams[] = $searchParam;
    $queryParams[] = $searchParam;
}

if (!empty($fabricFilter)) {
    $whereClauses[] = "p.fabric = ?";
    $queryParams[] = $fabricFilter;
}

if ($minPrice > 0) {
    $whereClauses[] = "COALESCE(p.discount_price, p.price) >= ?";
    $queryParams[] = $minPrice;
}

if ($maxPrice > 0) {
    $whereClauses[] = "COALESCE(p.discount_price, p.price) <= ?";
    $queryParams[] = $maxPrice;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Sorting Order
$orderSql = "ORDER BY p.id DESC";
if ($sort === 'price_asc') {
    $orderSql = "ORDER BY COALESCE(p.discount_price, p.price) ASC";
} elseif ($sort === 'price_desc') {
    $orderSql = "ORDER BY COALESCE(p.discount_price, p.price) DESC";
}

// Count Total Products for Pagination
$totalProducts = 0;
try {
    $countSql = "SELECT COUNT(*) FROM products p {$whereSql}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($queryParams);
    $totalProducts = (int)$countStmt->fetchColumn();
} catch (PDOException $e) {
    $totalProducts = 0;
}

$totalPages = ceil($totalProducts / $limit);

// Fetch Products for Current Page
$products = [];
try {
    $sql = "
        SELECT p.*, c.name AS category_name, c.slug AS category_slug,
               (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) AS primary_img,
               (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1 OFFSET 1) AS secondary_img
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        {$whereSql}
        {$orderSql}
        LIMIT {$limit} OFFSET {$offset}
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($queryParams);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}
?>

<!-- 1. Category Banner Strip -->
<section class="category-banner-strip" style="background-image: linear-gradient(180deg, rgba(26,26,26,0.65) 0%, rgba(26,26,26,0.85) 100%), url('<?= sanitize($bannerImg) ?>');">
    <div class="container">
        <div class="category-banner-content">
            <span class="category-eyebrow">FeMe Luxury Closet</span>
            <h1 class="category-banner-title"><?= sanitize($pageTitle) ?></h1>
            <p class="category-banner-desc"><?= sanitize($pageSubtext) ?></p>
        </div>
    </div>
</section>

<!-- 2. Main Catalog Section -->
<section class="section catalog-section">
    <div class="container">
        <!-- Top Toolbar for Mobile Filter Trigger & Desktop Items Count -->
        <div class="catalog-toolbar">
            <div class="toolbar-left">
                <button class="mobile-filter-btn" id="mobileFilterToggle">
                    <i class="fa-solid fa-sliders"></i> Filter & Sort
                </button>
                <span class="items-count-text">Showing <strong><?= count($products) ?></strong> of <strong><?= $totalProducts ?></strong> items</span>
            </div>
            
            <div class="toolbar-right">
                <form method="GET" class="sort-form" id="sortForm">
                    <!-- Preserve existing GET parameters -->
                    <?php if ($categorySlug): ?><input type="hidden" name="slug" value="<?= sanitize($categorySlug) ?>"><?php endif; ?>
                    <?php if ($filter): ?><input type="hidden" name="filter" value="<?= sanitize($filter) ?>"><?php endif; ?>
                    <?php if ($search): ?><input type="hidden" name="search" value="<?= sanitize($search) ?>"><?php endif; ?>
                    <?php if ($fabricFilter): ?><input type="hidden" name="fabric" value="<?= sanitize($fabricFilter) ?>"><?php endif; ?>
                    <?php if ($minPrice): ?><input type="hidden" name="min_price" value="<?= sanitize($minPrice) ?>"><?php endif; ?>
                    <?php if ($maxPrice): ?><input type="hidden" name="max_price" value="<?= sanitize($maxPrice) ?>"><?php endif; ?>
                    
                    <label for="sortSelect" class="sort-label">Sort By:</label>
                    <select name="sort" id="sortSelect" class="sort-select" onchange="document.getElementById('sortForm').submit();">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="catalog-layout">
            <!-- Sidebar Filter Panel -->
            <aside class="catalog-sidebar" id="filterSidebar">
                <div class="sidebar-header">
                    <h3>Filter Catalog</h3>
                    <button class="close-sidebar-btn" id="closeFilterBtn">&times;</button>
                </div>

                <form method="GET" action="category.php" class="filter-form">
                    <?php if ($categorySlug): ?><input type="hidden" name="slug" value="<?= sanitize($categorySlug) ?>"><?php endif; ?>
                    <?php if ($filter): ?><input type="hidden" name="filter" value="<?= sanitize($filter) ?>"><?php endif; ?>
                    <?php if ($search): ?><input type="hidden" name="search" value="<?= sanitize($search) ?>"><?php endif; ?>
                    <input type="hidden" name="sort" value="<?= sanitize($sort) ?>">

                    <!-- Filter 1: Categories Links -->
                    <div class="filter-group">
                        <h4 class="filter-title">Categories</h4>
                        <ul class="sidebar-cat-list">
                            <li><a href="category.php" class="<?= empty($categorySlug) && empty($filter) ? 'active' : '' ?>">All Collections</a></li>
                            <?php foreach ($allCategories as $cat): ?>
                                <li>
                                    <a href="category.php?slug=<?= sanitize($cat['slug']) ?>" class="<?= $categorySlug === $cat['slug'] ? 'active' : '' ?>">
                                        <?= sanitize($cat['name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Filter 2: Price Range -->
                    <div class="filter-group">
                        <h4 class="filter-title">Price Range (₹)</h4>
                        <div class="price-inputs">
                            <input type="number" name="min_price" placeholder="Min" value="<?= $minPrice > 0 ? $minPrice : '' ?>" class="filter-input">
                            <span>-</span>
                            <input type="number" name="max_price" placeholder="Max" value="<?= $maxPrice > 0 ? $maxPrice : '' ?>" class="filter-input">
                        </div>
                    </div>

                    <!-- Filter 3: Fabric -->
                    <?php if (!empty($fabrics)): ?>
                        <div class="filter-group">
                            <h4 class="filter-title">Fabric</h4>
                            <select name="fabric" class="filter-select">
                                <option value="">All Fabrics</option>
                                <?php foreach ($fabrics as $fab): ?>
                                    <option value="<?= sanitize($fab) ?>" <?= $fabricFilter === $fab ? 'selected' : '' ?>>
                                        <?= sanitize($fab) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn-gold" style="width: 100%; margin-top: 1rem;">Apply Filters</button>
                    <?php if (!empty($fabricFilter) || $minPrice > 0 || $maxPrice > 0): ?>
                        <a href="category.php<?= $categorySlug ? '?slug='.$categorySlug : '' ?>" class="reset-filter-btn">Reset Filters</a>
                    <?php endif; ?>
                </form>
            </aside>

            <!-- Product Grid -->
            <main class="catalog-products-container">
                <?php if (!empty($products)): ?>
                    <div class="product-grid">
                        <?php foreach ($products as $product): ?>
                            <?php 
                                // Image resolution logic
                                $primaryImg = !empty($product['primary_img']) && file_exists($product['primary_img']) 
                                    ? $product['primary_img'] 
                                    : 'assets/images/cat_sarees.jpg';
                                
                                $secondaryImg = !empty($product['secondary_img']) && file_exists($product['secondary_img']) 
                                    ? $product['secondary_img'] 
                                    : $primaryImg;
                            ?>
                            <div class="catalog-product-card">
                                <div class="catalog-img-wrapper">
                                    <?php if ($product['is_new_arrival']): ?>
                                        <span class="badge-gold">NEW</span>
                                    <?php endif; ?>

                                    <a href="product.php?slug=<?= sanitize($product['slug']) ?>">
                                        <img src="<?= sanitize($primaryImg) ?>" alt="<?= sanitize($product['name']) ?>" class="main-prod-img" loading="lazy">
                                        <img src="<?= sanitize($secondaryImg) ?>" alt="<?= sanitize($product['name']) ?>" class="hover-prod-img" loading="lazy">
                                    </a>

                                    <button class="quick-add-btn ajax-add-cart" data-product-id="<?= $product['id'] ?>" title="Add to Cart">
                                        <i class="fa-solid fa-bag-shopping"></i> Add to Cart
                                    </button>
                                </div>

                                <div class="catalog-card-info">
                                    <span class="product-category-tag"><?= sanitize($product['category_name'] ?? 'Luxury Couture') ?></span>
                                    <h3 class="catalog-product-title">
                                        <a href="product.php?slug=<?= sanitize($product['slug']) ?>"><?= sanitize($product['name']) ?></a>
                                    </h3>
                                    
                                    <div class="product-price-wrapper">
                                        <?php if (!empty($product['discount_price'])): ?>
                                            <span class="price-current"><?= format_price($product['discount_price']) ?></span>
                                            <span class="price-original"><?= format_price($product['price']) ?></span>
                                        <?php else: ?>
                                            <span class="price-current"><?= format_price($product['price']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-wrapper">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php 
                                    $pageParams = $_GET;
                                    $pageParams['page'] = $i;
                                    $pageUrl = 'category.php?' . http_build_query($pageParams);
                                ?>
                                <a href="<?= sanitize($pageUrl) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-catalog-state">
                        <i class="fa-solid fa-box-open" style="font-size: 3rem; color: var(--gold-primary); margin-bottom: 1rem;"></i>
                        <h3>No Products Found</h3>
                        <p>We couldn't find any luxury pieces matching your active filter criteria.</p>
                        <a href="category.php" class="btn-gold" style="margin-top: 1.5rem;">Explore All Collections</a>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
