<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Discounts & Promotions Management (CRUD)
 */
require_once __DIR__ . '/includes/admin_header.php';

$message = '';
$errorMsg = '';

// Delete Discount
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM discounts WHERE id = ?");
        $stmt->execute([$deleteId]);
        log_admin_activity($pdo, 'Deleted Discount Promotion', 'discounts', $deleteId);
        $message = 'Discount promotion deleted.';
    } catch (PDOException $e) {
        $errorMsg = 'Failed to delete discount.';
    }
}

// Form Submission (Add / Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errorMsg = 'Invalid session token. Please refresh and try again.';
    } else {
        $editId = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $discountPercent = (int)($_POST['discount_percent'] ?? 10);
        $startDate = trim($_POST['start_date'] ?? date('Y-m-d H:i:s'));
        $endDate = trim($_POST['end_date'] ?? date('Y-m-d H:i:s', strtotime('+30 days')));
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title) || $discountPercent <= 0) {
            $errorMsg = 'Please enter a valid title and discount percentage.';
        } else {
            try {
                if ($editId > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE discounts 
                        SET title = ?, description = ?, discount_percent = ?, start_date = ?, end_date = ?, is_active = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $description, $discountPercent, $startDate, $endDate, $isActive, $editId]);
                    log_admin_activity($pdo, 'Updated Discount Promotion', 'discounts', $editId);
                    $message = 'Discount promotion updated successfully.';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO discounts (title, description, discount_percent, start_date, end_date, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$title, $description, $discountPercent, $startDate, $endDate, $isActive]);
                    $newId = $pdo->lastInsertId();
                    log_admin_activity($pdo, 'Created Discount Promotion', 'discounts', $newId);
                    $message = 'Discount promotion created successfully.';
                }
            } catch (PDOException $e) {
                $errorMsg = 'Database operation failed.';
            }
        }
    }
}

// Fetch Discounts
try {
    $discounts = $pdo->query("SELECT * FROM discounts ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    $discounts = [];
}
?>

<div class="page-header-flex">
    <div>
        <h1 class="page-title">Discounts & Promotions</h1>
        <p style="font-size: 0.88rem; color: var(--admin-text-muted);">Manage your countdown timer offers & private sales</p>
    </div>
    <button class="btn-gold-admin" onclick="openDiscountModal()">
        <i class="fa-solid fa-plus"></i> Add New Promotion
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
                    <th>Title</th>
                    <th>Discount %</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($discounts)): ?>
                    <?php foreach ($discounts as $d): ?>
                        <tr>
                            <td>
                                <strong><?= sanitize($d['title']) ?></strong>
                                <div style="font-size: 0.78rem; color: var(--admin-text-muted);"><?= sanitize($d['description'] ?? '') ?></div>
                            </td>
                            <td><span class="badge-status badge-confirmed"><?= $d['discount_percent'] ?>% OFF</span></td>
                            <td><?= date('Y-m-d H:i', strtotime($d['start_date'])) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($d['end_date'])) ?></td>
                            <td>
                                <span class="badge-status badge-<?= $d['is_active'] ? 'delivered' : 'cancelled' ?>">
                                    <?= $d['is_active'] ? 'ACTIVE' : 'INACTIVE' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-action edit" onclick='editDiscount(<?= json_encode($d) ?>)' title="Edit">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <a href="discounts.php?action=delete&id=<?= $d['id'] ?>" class="btn-action delete" onclick="return confirm('Delete this promotion?');" title="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align: center; padding: 2rem;">No discount promotions created.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="admin-modal-overlay" id="discModal">
    <div class="admin-modal">
        <div class="modal-header">
            <h3 id="discModalTitle" style="font-family: var(--font-serif);">Add Promotion</h3>
            <button class="modal-close" onclick="closeDiscountModal()">&times;</button>
        </div>

        <form action="discounts.php" method="POST">
            <?= csrf_token_input() ?>
            <input type="hidden" name="edit_id" id="discEditId" value="0">

            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="font-size: 0.85rem; font-weight:600;">Promotion Title *</label>
                <input type="text" name="title" id="discTitle" class="form-control" required style="border: 1px solid var(--admin-border);" placeholder="Royal Festive Offer">
            </div>

            <div class="form-row" style="margin-bottom: 1rem;">
                <div class="form-group flex-1">
                    <label style="font-size: 0.85rem; font-weight:600;">Discount Percent (%) *</label>
                    <input type="number" name="discount_percent" id="discPercent" class="form-control" value="15" required style="border: 1px solid var(--admin-border);">
                </div>

                <div class="form-group flex-1" style="display:flex; align-items:center; margin-top:1.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                        <input type="checkbox" name="is_active" id="discActive" value="1" checked> Active Countdown
                    </label>
                </div>
            </div>

            <div class="form-row" style="margin-bottom: 1rem;">
                <div class="form-group flex-1">
                    <label style="font-size: 0.85rem; font-weight:600;">Start Date & Time *</label>
                    <input type="datetime-local" name="start_date" id="discStart" class="form-control" required style="border: 1px solid var(--admin-border);">
                </div>

                <div class="form-group flex-1">
                    <label style="font-size: 0.85rem; font-weight:600;">End Date & Time *</label>
                    <input type="datetime-local" name="end_date" id="discEnd" class="form-control" required style="border: 1px solid var(--admin-border);">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="font-size: 0.85rem; font-weight:600;">Offer Description</label>
                <textarea name="description" id="discDesc" class="form-control" rows="3" style="border: 1px solid var(--admin-border);"></textarea>
            </div>

            <button type="submit" class="btn-gold-admin" style="width: 100%; justify-content: center;">SAVE PROMOTION</button>
        </form>
    </div>
</div>

<script>
    function openDiscountModal() {
        document.getElementById('discEditId').value = 0;
        document.getElementById('discModalTitle').textContent = 'Add Promotion';
        document.getElementById('discTitle').value = '';
        document.getElementById('discPercent').value = 15;
        document.getElementById('discDesc').value = '';
        document.getElementById('discActive').checked = true;
        document.getElementById('discStart').value = new Date().toISOString().slice(0, 16);
        
        var endDate = new Date();
        endDate.setDate(endDate.getDate() + 30);
        document.getElementById('discEnd').value = endDate.toISOString().slice(0, 16);
        
        document.getElementById('discModal').classList.add('active');
    }

    function editDiscount(d) {
        document.getElementById('discEditId').value = d.id;
        document.getElementById('discModalTitle').textContent = 'Edit Promotion #' + d.id;
        document.getElementById('discTitle').value = d.title;
        document.getElementById('discPercent').value = d.discount_percent;
        document.getElementById('discDesc').value = d.description || '';
        document.getElementById('discActive').checked = d.is_active == 1;
        document.getElementById('discStart').value = d.start_date.replace(' ', 'T').slice(0, 16);
        document.getElementById('discEnd').value = d.end_date.replace(' ', 'T').slice(0, 16);
        document.getElementById('discModal').classList.add('active');
    }

    function closeDiscountModal() {
        document.getElementById('discModal').classList.remove('active');
    }
</script>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
