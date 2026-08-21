<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Shopping Cart Page
 */
require_once __DIR__ . '/includes/header.php';

$cartItems = get_cart_items($pdo);

$subtotal = 0;
foreach ($cartItems as $item) {
    $itemPrice = !empty($item['discount_price']) ? (float)$item['discount_price'] : (float)$item['price'];
    $subtotal += $itemPrice * (int)$item['quantity'];
}

$shippingFee = ($subtotal > 5000 || $subtotal === 0) ? 0 : 250;
$grandTotal = $subtotal + $shippingFee;
?>

<section class="section cart-page-section">
    <div class="container">
        <div class="section-header" style="text-align: left; margin-bottom: 2rem;">
            <p class="section-subtitle">Your Selected Masterpieces</p>
            <h1 class="section-title" style="display: block;">Shopping Closet Cart</h1>
        </div>

        <?php if (!empty($cartItems)): ?>
            <div class="cart-layout">
                <!-- Left: Cart Items List -->
                <div class="cart-items-wrapper">
                    <!-- Desktop Table View -->
                    <table class="cart-table desktop-cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Line Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <?php 
                                    $unitPrice = !empty($item['discount_price']) ? (float)$item['discount_price'] : (float)$item['price'];
                                    $lineTotal = $unitPrice * (int)$item['quantity'];
                                    $imgSrc = !empty($item['primary_img']) && file_exists($item['primary_img']) ? $item['primary_img'] : 'assets/images/cat_sarees.jpg';
                                ?>
                                <tr class="cart-item-row" data-product-id="<?= $item['id'] ?>" data-price="<?= $unitPrice ?>">
                                    <td class="product-cell">
                                        <div class="cart-product-thumb">
                                            <img src="<?= sanitize($imgSrc) ?>" alt="<?= sanitize($item['name']) ?>">
                                            <div>
                                                <span class="product-category-tag"><?= sanitize($item['category_name'] ?? 'Collection') ?></span>
                                                <h4 class="cart-item-title"><a href="product.php?slug=<?= sanitize($item['slug']) ?>"><?= sanitize($item['name']) ?></a></h4>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="price-cell">
                                        <?= format_price($unitPrice) ?>
                                    </td>
                                    <td class="qty-cell">
                                        <div class="quantity-input-group">
                                            <button type="button" class="qty-btn cart-qty-change" data-delta="-1">-</button>
                                            <input type="number" class="cart-qty-input" value="<?= $item['quantity'] ?>" min="1" readonly>
                                            <button type="button" class="qty-btn cart-qty-change" data-delta="1">+</button>
                                        </div>
                                    </td>
                                    <td class="line-total-cell font-serif font-bold">
                                        <?= format_price($lineTotal) ?>
                                    </td>
                                    <td class="action-cell">
                                        <button class="remove-cart-btn cart-item-remove" title="Remove Item">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Mobile Stacked Cards View -->
                    <div class="mobile-cart-list">
                        <?php foreach ($cartItems as $item): ?>
                            <?php 
                                $unitPrice = !empty($item['discount_price']) ? (float)$item['discount_price'] : (float)$item['price'];
                                $lineTotal = $unitPrice * (int)$item['quantity'];
                                $imgSrc = !empty($item['primary_img']) && file_exists($item['primary_img']) ? $item['primary_img'] : 'assets/images/cat_sarees.jpg';
                            ?>
                            <div class="mobile-cart-card cart-item-row" data-product-id="<?= $item['id'] ?>" data-price="<?= $unitPrice ?>">
                                <div class="mobile-card-top">
                                    <img src="<?= sanitize($imgSrc) ?>" alt="<?= sanitize($item['name']) ?>" class="mobile-card-img">
                                    <div class="mobile-card-details">
                                        <span class="product-category-tag"><?= sanitize($item['category_name'] ?? 'Collection') ?></span>
                                        <h4 class="cart-item-title"><a href="product.php?slug=<?= sanitize($item['slug']) ?>"><?= sanitize($item['name']) ?></a></h4>
                                        <div class="mobile-card-price"><?= format_price($unitPrice) ?></div>
                                    </div>
                                    <button class="remove-cart-btn cart-item-remove" title="Remove Item">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </div>
                                <div class="mobile-card-bottom">
                                    <div class="quantity-input-group">
                                        <button type="button" class="qty-btn cart-qty-change" data-delta="-1">-</button>
                                        <input type="number" class="cart-qty-input" value="<?= $item['quantity'] ?>" min="1" readonly>
                                        <button type="button" class="qty-btn cart-qty-change" data-delta="1">+</button>
                                    </div>
                                    <div class="mobile-line-total">
                                        Total: <strong class="line-total-cell"><?= format_price($lineTotal) ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right: Order Summary Card -->
                <div class="cart-summary-wrapper">
                    <div class="summary-card">
                        <h3 class="summary-title">Order Summary</h3>

                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="summarySubtotal"><?= format_price($subtotal) ?></span>
                        </div>

                        <div class="summary-row">
                            <span>Estimated Shipping</span>
                            <span id="summaryShipping"><?= $shippingFee === 0 ? '<span style="color:#2e7d32; font-weight:600;">FREE</span>' : format_price($shippingFee) ?></span>
                        </div>

                        <?php if ($shippingFee === 0): ?>
                            <p class="free-shipping-note"><i class="fa-solid fa-truck-fast"></i> Complimentary Royal Shipping Applied</p>
                        <?php endif; ?>

                        <div class="summary-divider"></div>

                        <div class="summary-row grand-total-row">
                            <span>Total</span>
                            <span id="summaryGrandTotal"><?= format_price($grandTotal) ?></span>
                        </div>

                        <a href="checkout.php" class="btn-gold btn-checkout" style="width: 100%; margin-top: 1.5rem; text-align: center;">
                            PROCEED TO CHECKOUT
                        </a>

                        <div class="summary-guarantee">
                            <p><i class="fa-solid fa-shield-halved"></i> 100% Authentic Handmade Couture Guaranteed</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Empty Cart State -->
            <div class="empty-catalog-state" style="padding: 5rem 1rem;">
                <i class="fa-solid fa-bag-shopping" style="font-size: 3.5rem; color: var(--gold-primary); margin-bottom: 1rem;"></i>
                <h2>Your Closet Cart is Empty</h2>
                <p style="color: var(--text-muted); margin: 0.75rem 0 1.75rem 0;">You haven't selected any luxury pieces yet.</p>
                <a href="category.php" class="btn-gold">Explore Collection</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
