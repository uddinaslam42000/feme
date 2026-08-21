<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Product Details & Buying Page
 */
require_once __DIR__ . '/includes/header.php';

$productSlug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$product = null;
$productImages = [];

// Fetch product details
try {
    if (!empty($productSlug)) {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name, c.slug AS category_slug 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.slug = ?
        ");
        $stmt->execute([$productSlug]);
        $product = $stmt->fetch();
    } elseif ($productId > 0) {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name, c.slug AS category_slug 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
    }

    if ($product) {
        // Fetch all product images
        $imgStmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
        $imgStmt->execute([$product['id']]);
        $productImages = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

        // JSON fallback if product_images table is empty for this product
        if (empty($productImages) && !empty($product['images'])) {
            $decoded = json_decode($product['images'], true);
            if (is_array($decoded)) {
                $productImages = $decoded;
            }
        }
    }
} catch (PDOException $e) {
    $product = null;
}

// Fallback if product not found
if (!$product) {
    echo '<div class="container section" style="text-align: center; padding: 6rem 1rem;">
            <h2>Product Not Found</h2>
            <p style="color: var(--text-muted); margin: 1rem 0 2rem 0;">The luxury item you are looking for is no longer available.</p>
            <a href="category.php" class="btn-gold">Explore Collection</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Image fallback
if (empty($productImages)) {
    $productImages = ['assets/images/cat_sarees.jpg'];
}

// Calculate discount percentage badge
$discountPercent = 0;
if (!empty($product['discount_price']) && $product['price'] > 0) {
    $discountPercent = round((($product['price'] - $product['discount_price']) / $product['price']) * 100);
}

// Fetch 4 Related Products from same category
$relatedProducts = [];
try {
    $relStmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name,
               (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) AS primary_img
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.category_id = ? AND p.id != ?
        ORDER BY p.id DESC
        LIMIT 4
    ");
    $relStmt->execute([$product['category_id'], $product['id']]);
    $relatedProducts = $relStmt->fetchAll();
} catch (PDOException $e) {
    $relatedProducts = [];
}
?>

<section class="section product-detail-section">
    <div class="container">
        <!-- Breadcrumb Navigation -->
        <div class="breadcrumb-trail">
            <a href="index.php">Home</a> &rsaquo;
            <a href="category.php?slug=<?= sanitize($product['category_slug'] ?? '') ?>"><?= sanitize($product['category_name'] ?? 'Collection') ?></a> &rsaquo;
            <span><?= sanitize($product['name']) ?></span>
        </div>

        <div class="product-detail-layout">
            <!-- Left Column: Image Gallery -->
            <div class="product-gallery">
                <div class="main-image-box">
                    <img src="<?= sanitize($productImages[0]) ?>" id="mainProductImg" alt="<?= sanitize($product['name']) ?>">
                </div>

                <?php if (count($productImages) > 1): ?>
                    <div class="thumbnail-strip" id="thumbnailStrip">
                        <?php foreach ($productImages as $idx => $img): ?>
                            <div class="thumb-box <?= $idx === 0 ? 'active' : '' ?>" onclick="switchProductImage('<?= sanitize($img) ?>', this)">
                                <img src="<?= sanitize($img) ?>" alt="Thumbnail <?= $idx + 1 ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Product Specifications & Actions -->
            <div class="product-info-panel">
                <span class="product-category-tag"><?= sanitize($product['category_name'] ?? 'Luxury Couture') ?></span>
                <h1 class="product-detail-title"><?= sanitize($product['name']) ?></h1>

                <div class="product-price-box">
                    <?php if (!empty($product['discount_price'])): ?>
                        <span class="price-current-lg"><?= format_price($product['discount_price']) ?></span>
                        <span class="price-original-lg"><?= format_price($product['price']) ?></span>
                        <?php if ($discountPercent > 0): ?>
                            <span class="badge-gold"><?= $discountPercent ?>% OFF</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="price-current-lg"><?= format_price($product['price']) ?></span>
                    <?php endif; ?>
                    <div style="font-size: 0.8rem; color: var(--gold-primary); font-weight: 600; margin-top: 6px;">
                        <i class="fa-solid fa-shield-halved"></i> Inclusive of <?= number_format($product['gst_percent'] ?? 5, 1) ?>% GST (Official GST Receipt Issued)
                    </div>
                </div>

                <?php if (!empty($product['fabric'])): ?>
                    <div class="product-spec-row">
                        <span class="spec-label">Fabric / Material:</span>
                        <span class="spec-value"><?= sanitize($product['fabric']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="product-spec-row">
                    <span class="spec-label">Availability:</span>
                    <?php if ($product['stock_qty'] > 0): ?>
                        <span class="stock-status in-stock"><i class="fa-solid fa-circle-check"></i> In Stock (<?= $product['stock_qty'] ?> pieces available)</span>
                    <?php else: ?>
                        <span class="stock-status out-of-stock"><i class="fa-solid fa-circle-xmark"></i> Out of Stock</span>
                    <?php endif; ?>
                </div>

                <div class="product-description-box">
                    <h3>Craftsmanship & Details</h3>
                    <p><?= nl2br(sanitize($product['description'] ?? 'Handcrafted piece designed with classic elegance.')) ?></p>
                </div>

                <!-- Product Form Actions -->
                <form id="addToCartForm" class="add-to-cart-form">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    
                    <div class="quantity-selector-wrapper">
                        <label for="qtyInput" class="qty-label">Quantity:</label>
                        <div class="quantity-input-group">
                            <button type="button" class="qty-btn" onclick="updateQty(-1)">-</button>
                            <input type="number" name="quantity" id="qtyInput" value="1" min="1" max="<?= max(1, $product['stock_qty']) ?>" readonly>
                            <button type="button" class="qty-btn" onclick="updateQty(1)">+</button>
                        </div>
                    </div>

                    <div class="action-buttons-group">
                        <button type="submit" class="btn-gold btn-add-cart" <?= $product['stock_qty'] < 1 ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-bag-shopping"></i> ADD TO CART
                        </button>

                        <button type="button" id="buyNowBtn" class="btn-buy-now" <?= $product['stock_qty'] < 1 ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-bolt"></i> BUY NOW
                        </button>
                    </div>

                    <div id="cartFormMsg" class="form-feedback-msg"></div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Related Products Section ("You May Also Like") -->
<?php if (!empty($relatedProducts)): ?>
<section class="section related-products-section">
    <div class="container">
        <div class="section-header">
            <p class="section-subtitle">Complementary Masterpieces</p>
            <h2 class="section-title">You May Also Like</h2>
        </div>

        <div class="related-products-strip">
            <?php foreach ($relatedProducts as $rel): ?>
                <?php 
                    $relImg = !empty($rel['primary_img']) && file_exists($rel['primary_img']) 
                        ? $rel['primary_img'] 
                        : 'assets/images/cat_suits.jpg';
                ?>
                <a href="product.php?slug=<?= sanitize($rel['slug']) ?>" class="smaller-product-card">
                    <div class="product-img-wrapper">
                        <img src="<?= sanitize($relImg) ?>" alt="<?= sanitize($rel['name']) ?>" loading="lazy">
                    </div>
                    <div class="product-card-info">
                        <div>
                            <span class="product-category-tag"><?= sanitize($rel['category_name'] ?? 'Collection') ?></span>
                            <h3 class="product-title"><?= sanitize($rel['name']) ?></h3>
                        </div>
                        <div class="product-price-wrapper">
                            <?php if (!empty($rel['discount_price'])): ?>
                                <span class="price-current"><?= format_price($rel['discount_price']) ?></span>
                                <span class="price-original"><?= format_price($rel['price']) ?></span>
                            <?php else: ?>
                                <span class="price-current"><?= format_price($rel['price']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
