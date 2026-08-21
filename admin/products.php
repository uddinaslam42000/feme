<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Products Management (CRUD)
 */
require_once __DIR__ . '/includes/admin_header.php';

$message = '';
$errorMsg = '';

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];
    try {
        // Fetch image paths to remove physical files
        $imgStmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
        $imgStmt->execute([$deleteId]);
        $images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($images as $imgPath) {
            if (file_exists(__DIR__ . '/../' . $imgPath)) {
                unlink(__DIR__ . '/../' . $imgPath);
            }
        }

        // Delete Product (Cascades to product_images via FK)
        $delStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $delStmt->execute([$deleteId]);

        log_admin_activity($pdo, 'Deleted Product', 'products', $deleteId);
        $message = 'Product deleted successfully.';
    } catch (PDOException $e) {
        $errorMsg = 'Failed to delete product.';
    }
}

// Handle Add / Edit Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errorMsg = 'Invalid session token. Please refresh and try again.';
    } else {
        $editId = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $discountPrice = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : NULL;
        $description = trim($_POST['description'] ?? '');
        $fabric = trim($_POST['fabric'] ?? '');
        $stockQty = (int)($_POST['stock_qty'] ?? 0);
        $gstPercent = (float)($_POST['gst_percent'] ?? 5.00);
        $isNewArrival = isset($_POST['is_new_arrival']) ? 1 : 0;
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

        if (empty($name) || $categoryId <= 0 || $price <= 0) {
            $errorMsg = 'Please fill in product name, category, and a valid price.';
        } else {
            try {
                if ($editId > 0) {
                    // Update Product
                    $updateStmt = $pdo->prepare("
                        UPDATE products 
                        SET name = ?, slug = ?, category_id = ?, price = ?, discount_price = ?, description = ?, fabric = ?, stock_qty = ?, gst_percent = ?, is_new_arrival = ?, is_featured = ?
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$name, $slug, $categoryId, $price, $discountPrice, $description, $fabric, $stockQty, $gstPercent, $isNewArrival, $isFeatured, $editId]);
                    $productId = $editId;
                    log_admin_activity($pdo, 'Updated Product', 'products', $productId);
                    $message = 'Product updated successfully.';
                } else {
                    // Insert New Product
                    $insertStmt = $pdo->prepare("
                        INSERT INTO products (name, slug, category_id, price, discount_price, description, fabric, stock_qty, gst_percent, is_new_arrival, is_featured) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insertStmt->execute([$name, $slug, $categoryId, $price, $discountPrice, $description, $fabric, $stockQty, $gstPercent, $isNewArrival, $isFeatured]);
                    $productId = $pdo->lastInsertId();
                    log_admin_activity($pdo, 'Added New Product', 'products', $productId);
                    $message = 'Product added successfully.';
                }

                // Handle Image Uploads with Hardened File Validation
                if (isset($_FILES['product_images']) && !empty($_FILES['product_images']['name'][0])) {
                    $uploadDir = __DIR__ . '/../uploads/products/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $files = $_FILES['product_images'];
                    $totalFiles = count($files['name']);

                    for ($i = 0; $i < $totalFiles; $i++) {
                        if ($files['error'][$i] === UPLOAD_ERR_OK) {
                            if (validate_image_file($files['tmp_name'][$i], $files['name'][$i])) {
                                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                                $newFilename = 'prod_' . $productId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                                $targetPath = $uploadDir . $newFilename;
                                $dbPath = 'uploads/products/' . $newFilename;

                                if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                                    $imgInsert = $pdo->prepare("INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)");
                                    $imgInsert->execute([$productId, $dbPath, $i + 1]);
                                }
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                $errorMsg = 'Database operation failed.';
            }
        }
    }
}

// Fetch All Categories for Dropdown
$allCategories = get_all_categories($pdo);

// Fetch All Products
try {
    $stmt = $pdo->query("
        SELECT p.*, c.name AS category_name,
               (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) AS primary_img
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.id DESC
    ");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}
?>

<div class="page-header-flex">
    <div>
        <h1 class="page-title">Products Management</h1>
        <p style="font-size: 0.88rem; color: var(--admin-text-muted);">Manage your luxury catalog & inventory</p>
    </div>
    <button class="btn-gold-admin" onclick="openProductModal()">
        <i class="fa-solid fa-plus"></i> Add New Product
    </button>
</div>

<?php if ($message): ?>
    <div style="background: #f6ffed; border: 1px solid #b7eb8f; color: #389e0d; padding: 0.85rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <i class="fa-solid fa-circle-check"></i> <?= sanitize($message) ?>
    </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div style="background: #fff2f0; border: 1px solid #ffa39e; color: #cf1322; padding: 0.85rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errorMsg) ?>
    </div>
<?php endif; ?>

<!-- Products Table -->
<div class="admin-card">
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Discount Price</th>
                    <th>GST Rate</th>
                    <th>Stock</th>
                    <th>Badges</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $p): ?>
                        <?php 
                            $imgSrc = !empty($p['primary_img']) && file_exists(__DIR__ . '/../' . $p['primary_img']) 
                                ? '../' . $p['primary_img'] 
                                : '../assets/images/cat_sarees.jpg';
                        ?>
                        <tr>
                            <td><img src="<?= sanitize($imgSrc) ?>" alt="" class="table-thumb"></td>
                            <td>
                                <strong><?= sanitize($p['name']) ?></strong>
                                <div style="font-size: 0.78rem; color: var(--admin-text-muted);"><?= sanitize($p['fabric'] ?? '') ?></div>
                            </td>
                            <td><?= sanitize($p['category_name'] ?? 'Uncategorized') ?></td>
                            <td><?= format_price($p['price']) ?></td>
                            <td><?= !empty($p['discount_price']) ? format_price($p['discount_price']) : '—' ?></td>
                            <td>
                                <span class="badge-status badge-confirmed" style="font-size: 0.75rem; font-weight: 600;">
                                    <?= number_format($p['gst_percent'] ?? 5, 1) ?>% GST
                                </span>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: <?= $p['stock_qty'] > 0 ? '#389e0d' : '#cf1322' ?>;">
                                    <?= $p['stock_qty'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($p['is_new_arrival']): ?><span class="badge-status badge-confirmed" style="margin-right:4px;">NEW</span><?php endif; ?>
                                <?php if ($p['is_featured']): ?><span class="badge-status badge-pending">FEATURED</span><?php endif; ?>
                            </td>
                            <td>
                                <button class="btn-action edit" onclick='editProduct(<?= json_encode($p) ?>)' title="Edit Product">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <a href="products.php?action=delete&id=<?= $p['id'] ?>" class="btn-action delete" onclick="return confirm('Are you sure you want to delete this product?');" title="Delete Product">
                                    <i class="fa-regular fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align: center; padding: 2rem;">No products found in catalog.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit Product Modal -->
<div class="admin-modal-overlay" id="productModal">
    <div class="admin-modal">
        <div class="modal-header">
            <h3 id="modalTitle" style="font-family: var(--font-serif);">Add New Product</h3>
            <button class="modal-close" onclick="closeProductModal()">&times;</button>
        </div>

        <form action="products.php" method="POST" enctype="multipart/form-data">
            <?= csrf_token_input() ?>
            <input type="hidden" name="edit_id" id="editId" value="0">

            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="font-size: 0.85rem; font-weight:600;">Product Name *</label>
                <input type="text" name="name" id="prodName" class="form-control" required style="border: 1px solid var(--admin-border);">
            </div>

            <div class="form-row" style="margin-bottom: 1rem;">
                <div class="form-group flex-1">
                    <label style="font-size: 0.85rem; font-weight:600;">Category *</label>
                    <select name="category_id" id="prodCat" class="form-control" required style="border: 1px solid var(--admin-border);">
                        <option value="">Select Category</option>
                        <?php foreach ($allCategories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group flex-1">
                    <label style="font-size: 0.85rem; font-weight:600;">Price (₹) *</label>
                    <input type="number" step="0.01" name="price" id="prodPrice" class="form-control" required style="border: 1px solid var(--admin-border);">
                </div>

                <div class="form-group flex-1">
                    <label style="font-size: 0.85rem; font-weight:600;">Discount Price (₹)</label>
                    <input type="number" step="0.01" name="discount_price" id="prodDiscPrice" class="form-control" style="border: 1px solid var(--admin-border);">
                </div>
            </div>

            <div class="form-row" style="margin-bottom: 1rem;">
                <div class="form-group flex-1">
                    <label style="font-size: 0.85rem; font-weight:600;">Fabric / Material</label>
                    <input type="text" name="fabric" id="prodFabric" class="form-control" placeholder="Pure Mulberry Silk" style="border: 1px solid var(--admin-border);">
                </div>

                <div class="form-group flex-1">
                    <label style="font-size: 0.85rem; font-weight:600;">Stock Quantity *</label>
                    <input type="number" name="stock_qty" id="prodStock" class="form-control" value="10" style="border: 1px solid var(--admin-border);">
                </div>

                <div class="form-group flex-1">
                    <label style="font-size: 0.85rem; font-weight:600;">GST Rate (%) *</label>
                    <select name="gst_percent" id="prodGst" class="form-control" style="border: 1px solid var(--admin-border);">
                        <option value="0">0% (Tax Exempt)</option>
                        <option value="5" selected>5% GST (Textiles & Apparel)</option>
                        <option value="12">12% GST (Luxury Apparel)</option>
                        <option value="18">18% GST (Fashion Accessories)</option>
                        <option value="28">28% GST (Bespoke Luxury)</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="font-size: 0.85rem; font-weight:600;">Description</label>
                <textarea name="description" id="prodDesc" class="form-control" rows="3" style="border: 1px solid var(--admin-border);"></textarea>
            </div>

            <div class="form-row" style="margin-bottom: 1rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                    <input type="checkbox" name="is_new_arrival" id="prodNew" value="1"> New Arrival Badge
                </label>

                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                    <input type="checkbox" name="is_featured" id="prodFeatured" value="1"> Featured Collection
                </label>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="font-size: 0.85rem; font-weight:600;">Product Images Upload (Select Multiple)</label>
                <input type="file" name="product_images[]" multiple class="form-control" accept="image/*" style="border: 1px solid var(--admin-border);">
            </div>

            <button type="submit" class="btn-gold-admin" style="width: 100%; justify-content: center;">SAVE PRODUCT</button>
        </form>
    </div>
</div>

<script>
    function openProductModal() {
        document.getElementById('editId').value = 0;
        document.getElementById('modalTitle').textContent = 'Add New Product';
        document.getElementById('prodName').value = '';
        document.getElementById('prodCat').value = '';
        document.getElementById('prodPrice').value = '';
        document.getElementById('prodDiscPrice').value = '';
        document.getElementById('prodFabric').value = '';
        document.getElementById('prodStock').value = 10;
        document.getElementById('prodGst').value = '5';
        document.getElementById('prodDesc').value = '';
        document.getElementById('prodNew').checked = false;
        document.getElementById('prodFeatured').checked = false;
        document.getElementById('productModal').classList.add('active');
    }

    function editProduct(p) {
        document.getElementById('editId').value = p.id;
        document.getElementById('modalTitle').textContent = 'Edit Product #' + p.id;
        document.getElementById('prodName').value = p.name;
        document.getElementById('prodCat').value = p.category_id;
        document.getElementById('prodPrice').value = p.price;
        document.getElementById('prodDiscPrice').value = p.discount_price || '';
        document.getElementById('prodFabric').value = p.fabric || '';
        document.getElementById('prodStock').value = p.stock_qty;
        document.getElementById('prodGst').value = p.gst_percent || '5';
        document.getElementById('prodDesc').value = p.description || '';
        document.getElementById('prodNew').checked = p.is_new_arrival == 1;
        document.getElementById('prodFeatured').checked = p.is_featured == 1;
        document.getElementById('productModal').classList.add('active');
    }

    function closeProductModal() {
        document.getElementById('productModal').classList.remove('active');
    }
</script>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
