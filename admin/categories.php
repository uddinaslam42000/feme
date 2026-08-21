<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Categories Management (CRUD)
 */
require_once __DIR__ . '/includes/admin_header.php';

$message = '';
$errorMsg = '';

// Delete Category
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$deleteId]);
        log_admin_activity($pdo, 'Deleted Category', 'categories', $deleteId);
        $message = 'Category deleted successfully.';
    } catch (PDOException $e) {
        $errorMsg = 'Cannot delete category with active assigned products.';
    }
}

// Form Submission (Add / Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errorMsg = 'Invalid session token. Please refresh and try again.';
    } else {
        $editId = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

        if (empty($name)) {
            $errorMsg = 'Please enter a category name.';
        } else {
            try {
                $imgPath = NULL;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    if (validate_image_file($_FILES['image']['tmp_name'], $_FILES['image']['name'])) {
                        $uploadDir = __DIR__ . '/../assets/images/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        
                        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                        $newFilename = 'cat_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFilename)) {
                            $imgPath = 'assets/images/' . $newFilename;
                        }
                    }
                }

                if ($editId > 0) {
                    if ($imgPath) {
                        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, image = ? WHERE id = ?");
                        $stmt->execute([$name, $slug, $description, $imgPath, $editId]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?");
                        $stmt->execute([$name, $slug, $description, $editId]);
                    }
                    log_admin_activity($pdo, 'Updated Category', 'categories', $editId);
                    $message = 'Category updated successfully.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, image) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $slug, $description, $imgPath]);
                    $newId = $pdo->lastInsertId();
                    log_admin_activity($pdo, 'Added Category', 'categories', $newId);
                    $message = 'Category added successfully.';
                }
            } catch (PDOException $e) {
                $errorMsg = 'Category operation failed.';
            }
        }
    }
}

$categories = get_all_categories($pdo);
?>

<div class="page-header-flex">
    <div>
        <h1 class="page-title">Categories Management</h1>
        <p style="font-size: 0.88rem; color: var(--admin-text-muted);">Manage your luxury collection classifications</p>
    </div>
    <button class="btn-gold-admin" onclick="openCategoryModal()">
        <i class="fa-solid fa-plus"></i> Add New Category
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

<div class="admin-card">
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Category Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                        <?php 
                            $imgSrc = !empty($cat['image']) && file_exists(__DIR__ . '/../' . $cat['image']) 
                                ? '../' . $cat['image'] 
                                : '../assets/images/cat_sarees.jpg';
                        ?>
                        <tr>
                            <td><img src="<?= sanitize($imgSrc) ?>" alt="" class="table-thumb"></td>
                            <td><strong><?= sanitize($cat['name']) ?></strong></td>
                            <td><code><?= sanitize($cat['slug']) ?></code></td>
                            <td><?= sanitize($cat['description'] ?? '—') ?></td>
                            <td>
                                <button class="btn-action edit" onclick='editCategory(<?= json_encode($cat) ?>)' title="Edit">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <a href="categories.php?action=delete&id=<?= $cat['id'] ?>" class="btn-action delete" onclick="return confirm('Are you sure you want to delete this category?');" title="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; padding: 2rem;">No categories found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="admin-modal-overlay" id="catModal">
    <div class="admin-modal">
        <div class="modal-header">
            <h3 id="catModalTitle" style="font-family: var(--font-serif);">Add New Category</h3>
            <button class="modal-close" onclick="closeCategoryModal()">&times;</button>
        </div>

        <form action="categories.php" method="POST" enctype="multipart/form-data">
            <?= csrf_token_input() ?>
            <input type="hidden" name="edit_id" id="catEditId" value="0">

            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="font-size: 0.85rem; font-weight:600;">Category Name *</label>
                <input type="text" name="name" id="catName" class="form-control" required style="border: 1px solid var(--admin-border);">
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="font-size: 0.85rem; font-weight:600;">Description</label>
                <textarea name="description" id="catDesc" class="form-control" rows="3" style="border: 1px solid var(--admin-border);"></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="font-size: 0.85rem; font-weight:600;">Category Image Banner</label>
                <input type="file" name="image" class="form-control" accept="image/*" style="border: 1px solid var(--admin-border);">
            </div>

            <button type="submit" class="btn-gold-admin" style="width: 100%; justify-content: center;">SAVE CATEGORY</button>
        </form>
    </div>
</div>

<script>
    function openCategoryModal() {
        document.getElementById('catEditId').value = 0;
        document.getElementById('catModalTitle').textContent = 'Add New Category';
        document.getElementById('catName').value = '';
        document.getElementById('catDesc').value = '';
        document.getElementById('catModal').classList.add('active');
    }

    function editCategory(c) {
        document.getElementById('catEditId').value = c.id;
        document.getElementById('catModalTitle').textContent = 'Edit Category #' + c.id;
        document.getElementById('catName').value = c.name;
        document.getElementById('catDesc').value = c.description || '';
        document.getElementById('catModal').classList.add('active');
    }

    function closeCategoryModal() {
        document.getElementById('catModal').classList.remove('active');
    }
</script>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
