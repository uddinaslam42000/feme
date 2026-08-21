<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Courier Partners Management
 * Onboard Couriers & Order Courier Assignment Tracker
 */
require_once __DIR__ . '/includes/admin_header.php';

$message = '';
$errorMsg = '';

// Handle Courier Form Actions (Add / Edit / Toggle / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token()) {
        $errorMsg = 'Invalid session security token.';
    } else {
        $action = $_POST['action'];

        if ($action === 'add_courier' || $action === 'edit_courier') {
            $courierId = isset($_POST['courier_id']) ? (int)$_POST['courier_id'] : 0;
            $name = sanitize($_POST['name'] ?? '');
            $code = strtoupper(sanitize($_POST['code'] ?? ''));
            $contactPerson = sanitize($_POST['contact_person'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $trackingUrlTemplate = sanitize($_POST['tracking_url_template'] ?? '');
            $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

            if (empty($name) || empty($code)) {
                $errorMsg = 'Courier Name and Code are required.';
            } else {
                try {
                    if ($action === 'add_courier') {
                        $stmt = $pdo->prepare("
                            INSERT INTO couriers (name, code, contact_person, phone, email, tracking_url_template, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$name, $code, $contactPerson, $phone, $email, $trackingUrlTemplate, $status]);
                        log_admin_activity($pdo, 'Added Courier Partner: ' . $name, 'couriers', $pdo->lastInsertId());
                        $message = 'Courier partner "' . $name . '" onboarded successfully!';
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE couriers 
                            SET name = ?, code = ?, contact_person = ?, phone = ?, email = ?, tracking_url_template = ?, status = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$name, $code, $contactPerson, $phone, $email, $trackingUrlTemplate, $status, $courierId]);
                        log_admin_activity($pdo, 'Updated Courier Partner #' . $courierId, 'couriers', $courierId);
                        $message = 'Courier partner updated successfully!';
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $errorMsg = 'Courier code "' . $code . '" already exists. Please use a unique code.';
                    } else {
                        $errorMsg = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        } elseif ($action === 'toggle_status') {
            $courierId = (int)$_POST['courier_id'];
            $newStatus = $_POST['status'] === 'active' ? 'active' : 'inactive';
            try {
                $stmt = $pdo->prepare("UPDATE couriers SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $courierId]);
                log_admin_activity($pdo, 'Changed Courier Status #' . $courierId . ' to ' . strtoupper($newStatus), 'couriers', $courierId);
                $message = 'Courier status updated to ' . strtoupper($newStatus);
            } catch (PDOException $e) {
                $errorMsg = 'Failed to update status.';
            }
        } elseif ($action === 'delete_courier') {
            $courierId = (int)$_POST['courier_id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM couriers WHERE id = ?");
                $stmt->execute([$courierId]);
                log_admin_activity($pdo, 'Deleted Courier Partner #' . $courierId, 'couriers', $courierId);
                $message = 'Courier partner deleted successfully.';
            } catch (PDOException $e) {
                $errorMsg = 'Failed to delete courier partner.';
            }
        }
    }
}

// Search and Filter Parameters
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$selectedCourierId = isset($_GET['courier_id']) ? (int)$_GET['courier_id'] : 0;

// Fetch All Couriers with Handled Orders Stats
try {
    $sql = "
        SELECT c.*, 
               COUNT(o.id) AS total_orders,
               SUM(CASE WHEN o.status IN ('pending', 'confirmed', 'shipped') THEN 1 ELSE 0 END) AS active_orders,
               SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) AS delivered_orders
        FROM couriers c
        LEFT JOIN orders o ON o.courier_id = c.id
        WHERE 1=1
    ";
    $params = [];

    if ($searchQuery !== '') {
        $sql .= " AND (c.name LIKE ? OR c.code LIKE ? OR c.contact_person LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)";
        $term = '%' . $searchQuery . '%';
        $params = array_merge($params, [$term, $term, $term, $term, $term]);
    }

    if (in_array($statusFilter, ['active', 'inactive'])) {
        $sql .= " AND c.status = ?";
        $params[] = $statusFilter;
    }

    $sql .= " GROUP BY c.id ORDER BY c.id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $couriers = $stmt->fetchAll();
} catch (PDOException $e) {
    $couriers = [];
}

// Fetch Handled Orders for selected courier (if any)
$handledOrders = [];
$selectedCourier = null;
if ($selectedCourierId > 0) {
    try {
        // Fetch selected courier details
        $cStmt = $pdo->prepare("SELECT * FROM couriers WHERE id = ?");
        $cStmt->execute([$selectedCourierId]);
        $selectedCourier = $cStmt->fetch();

        // Fetch orders handled by this courier
        $oStmt = $pdo->prepare("
            SELECT o.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.courier_id = ?
            ORDER BY o.id DESC
        ");
        $oStmt->execute([$selectedCourierId]);
        $handledOrders = $oStmt->fetchAll();
    } catch (PDOException $e) {
        $handledOrders = [];
    }
}
?>

<div class="page-header-flex">
    <div>
        <h1 class="page-title">Onboard Courier Partners</h1>
        <p style="font-size: 0.88rem; color: var(--admin-text-muted);">Manage delivery partners & track courier order assignments</p>
    </div>
    <div>
        <button class="btn-gold-admin" onclick="openAddCourierModal()">
            <i class="fa-solid fa-plus"></i> Onboard New Courier
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div style="background: #f6ffed; border: 1px solid #b7eb8f; color: #389e0d; padding: 0.85rem 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <i class="fa-solid fa-circle-check"></i> <?= sanitize($message) ?>
    </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div style="background: #fff2f0; border: 1px solid #ffa39e; color: #cf1322; padding: 0.85rem 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errorMsg) ?>
    </div>
<?php endif; ?>

<!-- Search & Filter Controls -->
<div class="admin-card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="couriers.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
        <?php if ($selectedCourierId > 0): ?>
            <input type="hidden" name="courier_id" value="<?= $selectedCourierId ?>">
        <?php endif; ?>
        <div style="flex: 2; min-width: 220px;">
            <input type="text" name="q" value="<?= sanitize($searchQuery) ?>" class="form-control" placeholder="Search courier name, code, contact, phone...">
        </div>
        <div style="flex: 1; min-width: 150px;">
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Only</option>
                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn-gold-admin" style="padding: 0.55rem 1.2rem;">
                <i class="fa-solid fa-magnifying-glass"></i> Filter
            </button>
            <?php if ($searchQuery !== '' || $statusFilter !== ''): ?>
                <a href="couriers.php<?= $selectedCourierId ? '?courier_id='.$selectedCourierId : '' ?>" class="btn-action edit" style="padding: 0.55rem 1rem; text-decoration: none;">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Onboard Couriers Listing Table -->
<div class="admin-card">
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Courier Partner</th>
                    <th>Contact Person</th>
                    <th>Phone / Email</th>
                    <th>Handled Orders</th>
                    <th>Active Orders</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($couriers)): ?>
                    <?php foreach ($couriers as $c): ?>
                        <tr class="<?= $selectedCourierId === (int)$c['id'] ? 'highlight-row' : '' ?>" style="<?= $selectedCourierId === (int)$c['id'] ? 'background-color: rgba(201, 162, 75, 0.12);' : '' ?>">
                            <td><strong style="color: var(--admin-gold); font-family: monospace; font-size: 0.95rem;"><?= sanitize($c['code']) ?></strong></td>
                            <td>
                                <strong><?= sanitize($c['name']) ?></strong>
                                <?php if (!empty($c['tracking_url_template'])): ?>
                                    <div style="font-size: 0.75rem; color: var(--admin-text-muted);" title="Tracking Template">
                                        <i class="fa-solid fa-link"></i> Live Tracking Ready
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($c['contact_person'] ?? '—') ?></td>
                            <td>
                                <div><?= sanitize($c['phone'] ?? '—') ?></div>
                                <div style="font-size: 0.78rem; color: var(--admin-text-muted);"><?= sanitize($c['email'] ?? '') ?></div>
                            </td>
                            <td>
                                <span class="badge-status badge-confirmed" style="background: rgba(201,162,75,0.15); color: var(--admin-gold); border: 1px solid var(--admin-gold);">
                                    <strong><?= (int)$c['total_orders'] ?></strong> Orders
                                </span>
                            </td>
                            <td>
                                <span class="badge-status <?= (int)$c['active_orders'] > 0 ? 'badge-pending' : '' ?>">
                                    <strong><?= (int)$c['active_orders'] ?></strong> Active
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="couriers.php" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="courier_id" value="<?= $c['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $c['status'] === 'active' ? 'inactive' : 'active' ?>">
                                    <button type="submit" class="badge-status <?= $c['status'] === 'active' ? 'badge-delivered' : 'badge-cancelled' ?>" style="border: none; cursor: pointer;">
                                        <?= strtoupper($c['status']) ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                                    <a href="couriers.php?courier_id=<?= $c['id'] ?><?= $searchQuery ? '&q='.urlencode($searchQuery) : '' ?>#handled-orders-section" class="btn-action edit" style="text-decoration: none;" title="See Which Orders Managed by this Courier">
                                        <i class="fa-solid fa-boxes-packing"></i> Orders (<?= (int)$c['total_orders'] ?>)
                                    </a>
                                    <button class="btn-action edit" onclick='openEditCourierModal(<?= json_encode($c) ?>)' title="Edit Courier Details">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <form method="POST" action="couriers.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this courier partner?');">
                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                        <input type="hidden" name="action" value="delete_courier">
                                        <input type="hidden" name="courier_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn-action delete" title="Delete Courier">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align: center; padding: 2rem;">No courier partners onboarded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Section: Which Courier Handles Which Orders -->
<?php if ($selectedCourier): ?>
    <div id="handled-orders-section" class="admin-card" style="border: 2px solid var(--admin-gold); padding: 1.75rem; margin-top: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--admin-gold);">
                    <i class="fa-solid fa-truck-ramp-box"></i> Orders Handled by <?= sanitize($selectedCourier['name']) ?> (<?= sanitize($selectedCourier['code']) ?>)
                </h2>
                <p style="font-size: 0.85rem; color: var(--admin-text-muted); margin-top: 4px;">
                    Contact: <?= sanitize($selectedCourier['contact_person'] ?? 'N/A') ?> &bull; Phone: <?= sanitize($selectedCourier['phone'] ?? 'N/A') ?>
                </p>
            </div>
            <div>
                <a href="couriers.php" class="btn-action edit" style="text-decoration: none; padding: 0.5rem 1rem;">
                    <i class="fa-solid fa-xmark"></i> Close Order View
                </a>
            </div>
        </div>

        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer Name & Contact</th>
                        <th>Shipping Address</th>
                        <th>AWB / Tracking #</th>
                        <th>Total Amount</th>
                        <th>Order Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($handledOrders)): ?>
                        <?php foreach ($handledOrders as $o): ?>
                            <tr>
                                <td><strong>#<?= sprintf('%06d', $o['id']) ?></strong></td>
                                <td>
                                    <strong><?= sanitize($o['user_name'] ?? 'Guest Customer') ?></strong>
                                    <div style="font-size: 0.78rem; color: var(--admin-text-muted);"><?= sanitize($o['user_email'] ?? '') ?></div>
                                    <div style="font-size: 0.78rem; color: var(--admin-text-muted);"><?= sanitize($o['user_phone'] ?? '') ?></div>
                                </td>
                                <td>
                                    <div style="max-width: 250px; font-size: 0.82rem; white-space: pre-line; color: var(--admin-text-dark); max-height: 80px; overflow-y: auto;">
                                        <?= sanitize($o['shipping_address']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($o['tracking_number'])): ?>
                                        <span style="font-family: monospace; font-weight: 700; color: var(--admin-gold); display: block; font-size: 0.9rem;">
                                            <?= sanitize($o['tracking_number']) ?>
                                        </span>
                                        <?php if (!empty($o['tracking_url'])): ?>
                                            <a href="<?= sanitize($o['tracking_url']) ?>" target="_blank" style="font-size: 0.75rem; color: #1890ff; text-decoration: none;">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Track Package
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: var(--admin-text-muted); font-size: 0.82rem; italic">No AWB Tagged</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= format_price($o['total_amount']) ?></strong></td>
                                <td>
                                    <span class="badge-status badge-<?= strtolower($o['status']) ?>">
                                        <?= strtoupper($o['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="orders.php?view=<?= $o['id'] ?>#order-row-<?= $o['id'] ?>" class="btn-action edit" style="text-decoration: none;" target="_blank">
                                        <i class="fa-solid fa-eye"></i> Manage Order
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
                                No orders currently assigned to <?= sanitize($selectedCourier['name']) ?>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Courier Modal (Add / Edit) -->
<div id="courierModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: #FFF; width: 100%; max-width: 550px; border-radius: var(--radius-md); padding: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: relative;">
        <button onclick="closeCourierModal()" style="position: absolute; top: 1.2rem; right: 1.2rem; background: none; border: none; font-size: 1.4rem; cursor: pointer; color: var(--admin-text-muted);">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <h3 id="modalTitle" style="font-family: var(--font-serif); font-size: 1.4rem; margin-bottom: 1.5rem; color: var(--admin-gold);">
            Onboard New Courier Partner
        </h3>

        <form method="POST" action="couriers.php">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" id="modalAction" value="add_courier">
            <input type="hidden" name="courier_id" id="modalCourierId" value="0">

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Courier Name *</label>
                    <input type="text" name="name" id="modalName" class="form-control" required placeholder="e.g. Blue Dart Express">
                </div>
                <div>
                    <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Code *</label>
                    <input type="text" name="code" id="modalCode" class="form-control" required placeholder="e.g. BLUEDART" style="text-transform: uppercase;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Contact Person</label>
                    <input type="text" name="contact_person" id="modalContact" class="form-control" placeholder="Account manager name">
                </div>
                <div>
                    <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Phone</label>
                    <input type="text" name="phone" id="modalPhone" class="form-control" placeholder="+91 98765 43210">
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Support Email</label>
                <input type="email" name="email" id="modalEmail" class="form-control" placeholder="dispatch@courier.com">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">
                    Tracking URL Template
                </label>
                <input type="url" name="tracking_url_template" id="modalTrackingUrl" class="form-control" placeholder="https://www.bluedart.com/tracking?awb={tracking_number}">
                <div style="color: var(--admin-text-muted); font-size: 0.78rem; margin-top: 4px;">Use <code>{tracking_number}</code> placeholder for dynamic AWB link generation.</div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Initial Status</label>
                <select name="status" id="modalStatus" class="form-control">
                    <option value="active">ACTIVE</option>
                    <option value="inactive">INACTIVE</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn-action edit" onclick="closeCourierModal()" style="padding: 0.6rem 1.2rem;">Cancel</button>
                <button type="submit" class="btn-gold-admin" style="padding: 0.6rem 1.4rem;">Save Courier</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddCourierModal() {
        document.getElementById('modalTitle').innerText = 'Onboard New Courier Partner';
        document.getElementById('modalAction').value = 'add_courier';
        document.getElementById('modalCourierId').value = '0';
        document.getElementById('modalName').value = '';
        document.getElementById('modalCode').value = '';
        document.getElementById('modalContact').value = '';
        document.getElementById('modalPhone').value = '';
        document.getElementById('modalEmail').value = '';
        document.getElementById('modalTrackingUrl').value = '';
        document.getElementById('modalStatus').value = 'active';
        document.getElementById('courierModal').style.display = 'flex';
    }

    function openEditCourierModal(c) {
        document.getElementById('modalTitle').innerText = 'Edit Courier Partner: ' + c.name;
        document.getElementById('modalAction').value = 'edit_courier';
        document.getElementById('modalCourierId').value = c.id;
        document.getElementById('modalName').value = c.name || '';
        document.getElementById('modalCode').value = c.code || '';
        document.getElementById('modalContact').value = c.contact_person || '';
        document.getElementById('modalPhone').value = c.phone || '';
        document.getElementById('modalEmail').value = c.email || '';
        document.getElementById('modalTrackingUrl').value = c.tracking_url_template || '';
        document.getElementById('modalStatus').value = c.status || 'active';
        document.getElementById('courierModal').style.display = 'flex';
    }

    function closeCourierModal() {
        document.getElementById('courierModal').style.display = 'none';
    }
</script>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
