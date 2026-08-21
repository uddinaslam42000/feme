<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Hero Banners Management (CRUD)
 */
require_once __DIR__ . '/includes/admin_header.php';

$message = '';
$errorMsg = '';

// Delete Banner
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
        $stmt->execute([$deleteId]);
        log_admin_activity($pdo, 'Deleted Banner', 'banners', $deleteId);
        $message = 'Hero banner removed.';
    } catch (PDOException $e) {
        $errorMsg = 'Failed to delete banner.';
    }
}

// Form Submission (Add / Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errorMsg = 'Invalid session token. Please refresh and try again.';
    } else {
        $editId = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $buttonText = trim($_POST['button_text'] ?? 'Explore Collection');
        $buttonLink = trim($_POST['button_link'] ?? 'category.php');
        $sortOrder = (int)($_POST['sort_order'] ?? 1);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title)) {
            $errorMsg = 'Please enter a banner title.';
        } else {
            try {
                $imgPath = NULL;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    if (validate_image_file($_FILES['image']['tmp_name'], $_FILES['image']['name'])) {
                        $uploadDir = __DIR__ . '/../uploads/banners/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                        $newFilename = 'banner_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFilename)) {
                            $imgPath = 'uploads/banners/' . $newFilename;
                        }
                    }
                }

                if ($editId > 0) {
                    if ($imgPath) {
                        $stmt = $pdo->prepare("UPDATE banners SET title = ?, subtitle = ?, button_text = ?, button_link = ?, sort_order = ?, is_active = ?, image = ? WHERE id = ?");
                        $stmt->execute([$title, $subtitle, $buttonText, $buttonLink, $sortOrder, $isActive, $imgPath, $editId]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE banners SET title = ?, subtitle = ?, button_text = ?, button_link = ?, sort_order = ?, is_active = ? WHERE id = ?");
                        $stmt->execute([$title, $subtitle, $buttonText, $buttonLink, $sortOrder, $isActive, $editId]);
                    }
                    log_admin_activity($pdo, 'Updated Hero Banner', 'banners', $editId);
                    $message = 'Hero banner updated successfully.';
                } else {
                    $imgPath = $imgPath ?: 'uploads/banners/hero_banner_1.jpg';
                    $stmt = $pdo->prepare("INSERT INTO banners (title, subtitle, button_text, button_link, sort_order, is_active, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $subtitle, $buttonText, $buttonLink, $sortOrder, $isActive, $imgPath]);
                    $newId = $pdo->lastInsertId();
                    log_admin_activity($pdo, 'Added Hero Banner', 'banners', $newId);
                    $message = 'Hero banner added successfully.';
                }
            } catch (PDOException $e) {
                $errorMsg = 'Banner save operation failed.';
            }
        }
    }
}

// Fetch Banners
try {
    $banners = $pdo->query("SELECT * FROM banners ORDER BY sort_order ASC")->fetchAll();
} catch (PDOException $e) {
    $banners = [];
}
?>

<div class="page-header-flex">
    <div>
        <h1 class="page-title">Hero Banners Management</h1>
        <p style="font-size: 0.88rem; color: var(--admin-text-muted);">Manage your homepage hero slider showcase</p>
    </div>
    <button class="btn-gold-admin" onclick="openBannerModal()">
        <i class="fa-solid fa-plus"></i> Add New Banner
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
                    <th>Title & Subtitle</th>
                    <th>Button</th>
                    <th>Sort</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($banners)): ?>
                    <?php foreach ($banners as $b): ?>
                        <?php 
                            $imgSrc = !empty($b['image']) && file_exists(__DIR__ . '/../' . $b['image']) 
                                ? '../' . $b['image'] 
                                : '../assets/images/cat_sarees.jpg';
                        ?>
                        <tr>
                            <td><img src="<?= sanitize($imgSrc) ?>" alt="" style="width:75px; height:45px; object-fit:cover; border-radius:4px;"></td>
                            <td>
                                <strong><?= sanitize($b['title']) ?></strong>
                                <div style="font-size:0.78rem; color:var(--admin-text-muted); font-style:italic;"><?= sanitize($b['subtitle'] ?? '') ?></div>
                            </td>
                            <td>
                                <a href="../<?= sanitize($b['button_link']) ?>" target="_blank" style="color:var(--admin-gold); font-weight:600; text-decoration:none;">
                                    <?= sanitize($b['button_text']) ?>
                                </a>
                            </td>
                            <td><strong><?= $b['sort_order'] ?></strong></td>
                            <td>
                                <span class="badge-status badge-<?= $b['is_active'] ? 'delivered' : 'cancelled' ?>">
                                    <?= $b['is_active'] ? 'ACTIVE' : 'HIDDEN' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-action edit" onclick='editBanner(<?= json_encode($b) ?>)' title="Edit">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <a href="banners.php?action=delete&id=<?= $b['id'] ?>" class="btn-action delete" onclick="return confirm('Delete this hero slide?');" title="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align: center; padding: 2rem;">No hero banners configured.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="admin-modal-overlay" id="bannerModal">
    <div class="admin-modal">
        <div class="modal-header">
            <h3 id="bannerModalTitle" style="font-family: var(--font-serif);">Add Hero Banner</h3>
            <button class="modal-close" onclick="closeBannerModal()">&times;</button>
        </div>

        <form action="banners.php" method="POST" enctype="multipart/form-data">
            <?= csrf_token_input() ?>
            <input type="hidden" name="edit_id" id="banEditId" value="0">

            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="font-size: 0.85rem; font-weight:600;">Banner Title *</label>
                <input type="text" name="title" id="banTitle" class="form-control" required style="border: 1px solid var(--admin-border);" placeholder="Elegance Draped in Distinction">
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="font-size: 0.85rem; font-weight:600;">Subtitle (Italic Script Style)</label>
                <input type="text" name="subtitle" id="banSubtitle" class="form-control" style="border: 1px solid var(--admin-border);" placeholder="Discover the Royal Festive Collection 2026">
            </div>

            <div class="form-row" style="margin-bottom: 1rem;">
                <div class="form-group flex-1">
                    <label style="font-size: 0.85rem; font-weight:600;">Button Text</label>
                    <input type="text" name="button_text" id="banBtnText" class="form-control" value="Explore Collection" style="border: 1px solid var(--admin-border);">
                </div>

                <div class="form-group flex-1">
                    <label style="font-size: 0.85rem; font-weight:600;">Button Link URL</label>
                    <input type="text" name="button_link" id="banBtnLink" class="form-control" value="category.php" style="border: 1px solid var(--admin-border);">
                </div>
            </div>

            <div class="form-row" style="margin-bottom: 1rem;">
                <div class="form-group flex-1">
                    <label style="font-size: 0.85rem; font-weight:600;">Sort Order</label>
                    <input type="number" name="sort_order" id="banSort" class="form-control" value="1" style="border: 1px solid var(--admin-border);">
                </div>

                <div class="form-group flex-1" style="display:flex; align-items:center; margin-top:1.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                        <input type="checkbox" name="is_active" id="banActive" value="1" checked> Active on Homepage
                    </label>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="font-size: 0.85rem; font-weight:600;">Background Image Banner Upload</label>
                <input type="file" name="image" class="form-control" accept="image/*" style="border: 1px solid var(--admin-border);">
            </div>

            <button type="submit" class="btn-gold-admin" style="width: 100%; justify-content: center;">SAVE BANNER</button>
        </form>
    </div>
</div>

<script>
    function openBannerModal() {
        document.getElementById('banEditId').value = 0;
        document.getElementById('bannerModalTitle').textContent = 'Add Hero Banner';
        document.getElementById('banTitle').value = '';
        document.getElementById('banSubtitle').value = '';
        document.getElementById('banBtnText').value = 'Explore Collection';
        document.getElementById('banBtnLink').value = 'category.php';
        document.getElementById('banSort').value = 1;
        document.getElementById('banActive').checked = true;
        document.getElementById('bannerModal').classList.add('active');
    }

    function editBanner(b) {
        document.getElementById('banEditId').value = b.id;
        document.getElementById('bannerModalTitle').textContent = 'Edit Hero Banner #' + b.id;
        document.getElementById('banTitle').value = b.title;
        document.getElementById('banSubtitle').value = b.subtitle || '';
        document.getElementById('banBtnText').value = b.button_text || 'Explore Collection';
        document.getElementById('banBtnLink').value = b.button_link || 'category.php';
        document.getElementById('banSort').value = b.sort_order;
        document.getElementById('banActive').checked = b.is_active == 1;
        document.getElementById('bannerModal').classList.add('active');
    }

    function closeBannerModal() {
        document.getElementById('bannerModal').classList.remove('active');
    }
</script>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
