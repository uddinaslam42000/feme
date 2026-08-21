<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Orders Management
 * Multi-Parameter Search, Order Fulfillment & Courier Tagging
 */
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/../includes/mailer.php';

$message = '';
$errorMsg = '';

// Fetch Active Couriers list for tagging & filter dropdowns
try {
    $courierStmt = $pdo->query("SELECT * FROM couriers WHERE status = 'active' ORDER BY name ASC");
    $activeCouriers = $courierStmt->fetchAll();

    $allCouriersStmt = $pdo->query("SELECT * FROM couriers ORDER BY name ASC");
    $allCouriers = $allCouriersStmt->fetchAll();
    $courierMap = [];
    foreach ($allCouriers as $c) {
        $courierMap[$c['id']] = $c;
    }
} catch (PDOException $e) {
    $activeCouriers = [];
    $allCouriers = [];
    $courierMap = [];
}

// Handle Status Change Request via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token()) {
        $errorMsg = 'Invalid session token.';
    } else {
        $action = $_POST['action'];

        if ($action === 'update_status') {
            $orderId = (int)$_POST['order_id'];
            $newStatus = sanitize($_POST['status']);

            $allowedStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
            if (in_array($newStatus, $allowedStatuses)) {
                try {
                    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                    $stmt->execute([$newStatus, $orderId]);
                    log_admin_activity($pdo, 'Updated Order Status to ' . strtoupper($newStatus), 'orders', $orderId);
                    
                    // Send Status Update Email
                    send_order_status_update_email($pdo, $orderId, $newStatus);
                    
                    if (isset($_POST['is_ajax']) && $_POST['is_ajax'] == '1') {
                        header('Content-Type: application/json');
                        echo json_encode(['status' => 'success', 'message' => 'Status updated to ' . strtoupper($newStatus)]);
                        exit;
                    }
                    $message = 'Order #' . sprintf('%06d', $orderId) . ' status updated to ' . strtoupper($newStatus);
                } catch (PDOException $e) {
                    $errorMsg = 'Failed to update order status.';
                }
            }
        } elseif ($action === 'tag_courier') {
            $orderId = (int)$_POST['order_id'];
            $courierId = (int)$_POST['courier_id'];
            $trackingNumber = sanitize($_POST['tracking_number'] ?? '');
            $customTrackingUrl = sanitize($_POST['tracking_url'] ?? '');
            $updateStatusShipped = isset($_POST['auto_ship']) && $_POST['auto_ship'] == '1';

            if ($courierId <= 0) {
                $errorMsg = 'Please select a courier service partner.';
            } else {
                try {
                    // Generate tracking URL from courier template if custom tracking URL is empty
                    $finalTrackingUrl = $customTrackingUrl;
                    if (empty($finalTrackingUrl) && isset($courierMap[$courierId])) {
                        $template = $courierMap[$courierId]['tracking_url_template'] ?? '';
                        if (!empty($template) && !empty($trackingNumber)) {
                            $finalTrackingUrl = str_replace('{tracking_number}', urlencode($trackingNumber), $template);
                        }
                    }

                    $courierName = $courierMap[$courierId]['name'] ?? 'Courier Partner';

                    if ($updateStatusShipped) {
                        $stmt = $pdo->prepare("
                            UPDATE orders 
                            SET courier_id = ?, tracking_number = ?, tracking_url = ?, status = 'shipped', shipped_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$courierId, $trackingNumber, $finalTrackingUrl, $orderId]);
                        log_admin_activity($pdo, 'Tagged Order #' . $orderId . ' with ' . $courierName . ' (AWB: ' . $trackingNumber . ') & marked SHIPPED', 'orders', $orderId);
                        
                        // Send Email notification
                        send_order_status_update_email($pdo, $orderId, 'shipped');
                        $message = 'Order #' . sprintf('%06d', $orderId) . ' successfully tagged with ' . $courierName . ' and marked as SHIPPED!';
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE orders 
                            SET courier_id = ?, tracking_number = ?, tracking_url = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$courierId, $trackingNumber, $finalTrackingUrl, $orderId]);
                        log_admin_activity($pdo, 'Tagged Order #' . $orderId . ' with ' . $courierName . ' (AWB: ' . $trackingNumber . ')', 'orders', $orderId);
                        $message = 'Courier details updated for Order #' . sprintf('%06d', $orderId);
                    }
                } catch (PDOException $e) {
                    $errorMsg = 'Failed to tag courier: ' . $e->getMessage();
                }
            }
        }
    }
}

// Extract Multi-Parameter Search & Filter Inputs
$searchQuery    = isset($_GET['q']) ? trim($_GET['q']) : '';
$statusFilter    = isset($_GET['status']) ? trim($_GET['status']) : '';
$payStatusFilter = isset($_GET['pay_status']) ? trim($_GET['pay_status']) : '';
$payMethodFilter = isset($_GET['pay_method']) ? trim($_GET['pay_method']) : '';
$courierFilter   = isset($_GET['courier']) ? trim($_GET['courier']) : '';
$dateFrom        = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$dateTo          = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Build Query with Multi-Parameter Search
try {
    $sql = "
        SELECT o.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
               c.name AS courier_name, c.code AS courier_code
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        LEFT JOIN couriers c ON o.courier_id = c.id
        WHERE 1=1
    ";
    $params = [];

    // Search query matches Order ID, User Name, Email, Phone, Address, Tracking #
    if ($searchQuery !== '') {
        $sql .= " AND (
            CAST(o.id AS CHAR) LIKE ? OR
            u.name LIKE ? OR
            u.email LIKE ? OR
            u.phone LIKE ? OR
            o.shipping_address LIKE ? OR
            o.tracking_number LIKE ?
        )";
        $term = '%' . $searchQuery . '%';
        $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
    }

    if (in_array($statusFilter, ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'])) {
        $sql .= " AND o.status = ?";
        $params[] = $statusFilter;
    }

    if (in_array($payStatusFilter, ['pending', 'paid', 'failed', 'refunded'])) {
        $sql .= " AND o.payment_status = ?";
        $params[] = $payStatusFilter;
    }

    if ($payMethodFilter !== '') {
        $sql .= " AND o.payment_method = ?";
        $params[] = $payMethodFilter;
    }

    if ($courierFilter === 'unassigned') {
        $sql .= " AND o.courier_id IS NULL";
    } elseif (is_numeric($courierFilter) && (int)$courierFilter > 0) {
        $sql .= " AND o.courier_id = ?";
        $params[] = (int)$courierFilter;
    }

    if (!empty($dateFrom)) {
        $sql .= " AND o.created_at >= ?";
        $params[] = $dateFrom . ' 00:00:00';
    }

    if (!empty($dateTo)) {
        $sql .= " AND o.created_at <= ?";
        $params[] = $dateTo . ' 23:59:59';
    }

    $sql .= " ORDER BY o.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}

// Filter single order view if requested
$viewOrderId = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$viewOrderItems = [];
if ($viewOrderId > 0) {
    try {
        $itemStmt = $pdo->prepare("
            SELECT oi.*, p.name AS product_name, cat.name AS category_name,
                   (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) AS primary_img
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            WHERE oi.order_id = ?
        ");
        $itemStmt->execute([$viewOrderId]);
        $viewOrderItems = $itemStmt->fetchAll();
    } catch (PDOException $e) {
        $viewOrderItems = [];
    }
}
?>

<div class="page-header-flex">
    <div>
        <h1 class="page-title">Orders & Logistics Management</h1>
        <p style="font-size: 0.88rem; color: var(--admin-text-muted);">Tag courier services, track AWB dispatch, & search client orders</p>
    </div>
    <div>
        <a href="couriers.php" class="btn-gold-admin" style="text-decoration: none;">
            <i class="fa-solid fa-truck-fast"></i> Manage Courier Partners
        </a>
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

<!-- Multi-Parameter Search & Filter Facility -->
<div class="admin-card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
    <form method="GET" action="orders.php">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <!-- 1. Text Search -->
            <div>
                <label style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Search Keyword</label>
                <input type="text" name="q" value="<?= sanitize($searchQuery) ?>" class="form-control" placeholder="Order #, Name, Phone, Address, AWB...">
            </div>

            <!-- 2. Order Status Filter -->
            <div>
                <label style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Order Status</label>
                <select name="status" class="form-control">
                    <option value="">All Order Statuses</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>PENDING</option>
                    <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>CONFIRMED</option>
                    <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>SHIPPED</option>
                    <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>DELIVERED</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>CANCELLED</option>
                </select>
            </div>

            <!-- 3. Payment Status Filter -->
            <div>
                <label style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Payment Status</label>
                <select name="pay_status" class="form-control">
                    <option value="">All Payment Statuses</option>
                    <option value="pending" <?= $payStatusFilter === 'pending' ? 'selected' : '' ?>>PENDING</option>
                    <option value="paid" <?= $payStatusFilter === 'paid' ? 'selected' : '' ?>>PAID</option>
                    <option value="failed" <?= $payStatusFilter === 'failed' ? 'selected' : '' ?>>FAILED</option>
                    <option value="refunded" <?= $payStatusFilter === 'refunded' ? 'selected' : '' ?>>REFUNDED</option>
                </select>
            </div>

            <!-- 4. Assigned Courier Filter -->
            <div>
                <label style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Courier Service</label>
                <select name="courier" class="form-control">
                    <option value="">All Couriers / Unassigned</option>
                    <option value="unassigned" <?= $courierFilter === 'unassigned' ? 'selected' : '' ?>>⚠️ Unassigned Couriers</option>
                    <?php foreach ($allCouriers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $courierFilter == $c['id'] ? 'selected' : '' ?>>
                            <?= sanitize($c['name']) ?> (<?= sanitize($c['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end; justify-content: space-between;">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <div>
                    <label style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">From Date</label>
                    <input type="date" name="date_from" value="<?= sanitize($dateFrom) ?>" class="form-control">
                </div>
                <div>
                    <label style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">To Date</label>
                    <input type="date" name="date_to" value="<?= sanitize($dateTo) ?>" class="form-control">
                </div>
            </div>

            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <button type="submit" class="btn-gold-admin" style="padding: 0.6rem 1.4rem;">
                    <i class="fa-solid fa-filter"></i> Apply Filters
                </button>
                <?php if ($searchQuery || $statusFilter || $payStatusFilter || $payMethodFilter || $courierFilter || $dateFrom || $dateTo): ?>
                    <a href="orders.php" class="btn-action edit" style="padding: 0.6rem 1rem; text-decoration: none;">Reset Filters</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="admin-card">
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer Name & Contact</th>
                    <th>Date</th>
                    <th>Total Amount</th>
                    <th>Courier Service Tag</th>
                    <th>Payment</th>
                    <th>Order Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                        <?php 
                            $payBadgeClass = 'badge-pending';
                            if ($order['payment_status'] === 'paid') $payBadgeClass = 'badge-delivered';
                            if ($order['payment_status'] === 'failed') $payBadgeClass = 'badge-cancelled';
                        ?>
                        <tr id="order-row-<?= $order['id'] ?>" class="<?= $viewOrderId === $order['id'] ? 'highlight-row' : '' ?>">
                            <td><strong>#<?= sprintf('%06d', $order['id']) ?></strong></td>
                            <td>
                                <strong><?= sanitize($order['user_name'] ?? 'Guest Client') ?></strong>
                                <div style="font-size: 0.78rem; color: var(--admin-text-muted);"><?= sanitize($order['user_email'] ?? '') ?></div>
                                <div style="font-size: 0.78rem; color: var(--admin-text-muted);"><?= sanitize($order['user_phone'] ?? '') ?></div>
                            </td>
                            <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                            <td><strong><?= format_price($order['total_amount']) ?></strong></td>
                            <td>
                                <?php if (!empty($order['courier_name'])): ?>
                                    <div style="font-weight: 600; color: var(--admin-gold); font-size: 0.88rem;">
                                        <i class="fa-solid fa-truck"></i> <?= sanitize($order['courier_name']) ?>
                                    </div>
                                    <?php if (!empty($order['tracking_number'])): ?>
                                        <div style="font-size: 0.78rem; font-family: monospace; font-weight: 600; color: var(--admin-text-dark);">
                                            AWB: <?= sanitize($order['tracking_number']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($order['tracking_url'])): ?>
                                        <a href="<?= sanitize($order['tracking_url']) ?>" target="_blank" style="font-size: 0.75rem; color: #1890ff; text-decoration: none;">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Track Package
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #fa8c16; font-size: 0.8rem; font-weight: 500;">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Not Tagged
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-status <?= $payBadgeClass ?>">
                                    <?= strtoupper(sanitize($order['payment_status'])) ?>
                                </span>
                                <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 2px;">
                                    <?= strtoupper(sanitize($order['payment_method'])) ?>
                                </div>
                            </td>
                            <td>
                                <select class="form-control status-dropdown" style="padding: 0.35rem 0.5rem; font-size: 0.82rem; border: 1px solid var(--admin-border);" onchange="updateOrderStatus(<?= $order['id'] ?>, this.value)">
                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>PENDING</option>
                                    <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>CONFIRMED</option>
                                    <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>SHIPPED</option>
                                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>DELIVERED</option>
                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>CANCELLED</option>
                                </select>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                                    <button class="btn-action edit" onclick='openTagCourierModal(<?= json_encode($order) ?>)' title="Tag Courier Service">
                                        <i class="fa-solid fa-truck-fast"></i> Tag
                                    </button>
                                    <button class="btn-action edit" onclick="toggleOrderDetails(<?= $order['id'] ?>)" title="View Full Details">
                                        <i class="fa-solid fa-list-check"></i> Items
                                    </button>
                                    <a href="invoice.php?id=<?= $order['id'] ?>" target="_blank" class="btn-action edit" style="text-decoration: none;" title="Print A4 GST Tax Receipt">
                                        <i class="fa-solid fa-print"></i> Receipt
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- Order Details Accordion Row -->
                        <tr id="details-row-<?= $order['id'] ?>" style="display: <?= $viewOrderId === $order['id'] ? 'table-row' : 'none' ?>; background: #FFF;">
                            <td colspan="8" style="padding: 1.5rem; border-top: 1px dashed var(--admin-border);">
                                <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                                    <!-- Full Shipping Address & Customer Details -->
                                    <div style="flex: 1; min-width: 280px; border-right: 1px solid #F0F0F0; padding-right: 1rem;">
                                        <h4 style="font-family: var(--font-serif); font-size: 1rem; margin-bottom: 0.5rem; color: var(--admin-gold);">
                                            <i class="fa-solid fa-location-dot"></i> Shipping Address & Contact
                                        </h4>
                                        <div style="background: #FDFBF7; padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--admin-border);">
                                            <div style="font-weight: 700; font-size: 0.95rem; color: var(--admin-text-dark); margin-bottom: 4px;">
                                                <?= sanitize($order['user_name'] ?? 'Guest Client') ?>
                                            </div>
                                            <div style="font-size: 0.85rem; color: var(--admin-text-muted); margin-bottom: 8px;">
                                                Email: <?= sanitize($order['user_email'] ?? 'N/A') ?> | Phone: <?= sanitize($order['user_phone'] ?? 'N/A') ?>
                                            </div>
                                            <p style="font-size: 0.88rem; white-space: pre-line; color: var(--admin-text-dark); line-height: 1.5; font-weight: 500;">
                                                <?= sanitize($order['shipping_address']) ?>
                                            </p>
                                        </div>

                                        <!-- Courier Logistics Tag Info -->
                                        <div style="margin-top: 1.25rem; background: rgba(201, 162, 75, 0.08); padding: 1rem; border-radius: var(--radius-sm); border: 1px dashed var(--admin-gold);">
                                            <h5 style="font-size: 0.88rem; font-weight: 600; color: var(--admin-gold); margin-bottom: 6px;">
                                                <i class="fa-solid fa-box"></i> Courier Logistics Details
                                            </h5>
                                            <?php if (!empty($order['courier_name'])): ?>
                                                <div style="font-size: 0.85rem;"><strong>Partner:</strong> <?= sanitize($order['courier_name']) ?> (<?= sanitize($order['courier_code']) ?>)</div>
                                                <div style="font-size: 0.85rem;"><strong>Tracking / AWB #:</strong> <code><?= sanitize($order['tracking_number'] ?? 'N/A') ?></code></div>
                                                <?php if (!empty($order['shipped_at'])): ?>
                                                    <div style="font-size: 0.8rem; color: var(--admin-text-muted);"><strong>Dispatched At:</strong> <?= date('Y-m-d H:i', strtotime($order['shipped_at'])) ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div style="font-size: 0.82rem; color: var(--admin-text-muted);">No courier tagged yet.</div>
                                            <?php endif; ?>
                                            <button class="btn-gold-admin" style="margin-top: 8px; padding: 0.35rem 0.8rem; font-size: 0.78rem;" onclick='openTagCourierModal(<?= json_encode($order) ?>)'>
                                                <i class="fa-solid fa-tag"></i> Update Courier Tag
                                            </button>
                                        </div>

                                        <?php if (!empty($order['razorpay_order_id'])): ?>
                                            <div style="margin-top: 1rem; font-size: 0.8rem; color: var(--admin-text-muted);">
                                                <div><strong>Razorpay Order ID:</strong> <code><?= sanitize($order['razorpay_order_id']) ?></code></div>
                                                <div><strong>Razorpay Payment ID:</strong> <code><?= sanitize($order['razorpay_payment_id'] ?? 'Pending') ?></code></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Ordered Items -->
                                    <div style="flex: 1.5; min-width: 320px;">
                                        <h4 style="font-family: var(--font-serif); font-size: 1rem; margin-bottom: 0.5rem; color: var(--admin-gold);">
                                            <i class="fa-solid fa-bag-shopping"></i> Ordered Royal Ensembles
                                        </h4>
                                        <div id="items-container-<?= $order['id'] ?>">
                                            <?php if ($viewOrderId === $order['id'] && !empty($viewOrderItems)): ?>
                                                <?php foreach ($viewOrderItems as $item): ?>
                                                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #F0F0F0; font-size: 0.88rem;">
                                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                            <?php if (!empty($item['primary_img'])): ?>
                                                                <img src="../<?= sanitize($item['primary_img']) ?>" class="table-thumb" alt="<?= sanitize($item['product_name']) ?>">
                                                            <?php endif; ?>
                                                            <div>
                                                                <strong><?= sanitize($item['product_name']) ?></strong>
                                                                <div style="font-size: 0.78rem; color: var(--admin-text-muted);"><?= sanitize($item['category_name'] ?? '') ?></div>
                                                            </div>
                                                        </div>
                                                        <div><?= $item['quantity'] ?> &times; <?= format_price($item['price']) ?> = <strong><?= format_price($item['price'] * $item['quantity']) ?></strong></div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <button class="btn-gold-admin" style="padding: 0.4rem 0.8rem; font-size: 0.78rem;" onclick="loadOrderItems(<?= $order['id'] ?>)">Load Order Items</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align: center; padding: 2rem;">No matching client orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Tag Courier Service -->
<div id="tagCourierModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: #FFF; width: 100%; max-width: 500px; border-radius: var(--radius-md); padding: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: relative;">
        <button onclick="closeTagCourierModal()" style="position: absolute; top: 1.2rem; right: 1.2rem; background: none; border: none; font-size: 1.4rem; cursor: pointer; color: var(--admin-text-muted);">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <h3 style="font-family: var(--font-serif); font-size: 1.3rem; margin-bottom: 1.25rem; color: var(--admin-gold);">
            <i class="fa-solid fa-truck-fast"></i> Tag Order with Courier Partner
        </h3>

        <form method="POST" action="orders.php">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="tag_courier">
            <input type="hidden" name="order_id" id="tagOrderId" value="0">

            <div style="margin-bottom: 1rem;">
                <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Target Order</label>
                <input type="text" id="tagOrderLabel" class="form-control" readonly style="background: #F7F3EC; font-weight: 700;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Select Courier Service *</label>
                <select name="courier_id" id="tagCourierSelect" class="form-control" required>
                    <option value="">-- Choose Courier Partner --</option>
                    <?php foreach ($activeCouriers as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?> (<?= sanitize($c['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">AWB / Tracking Number</label>
                <input type="text" name="tracking_number" id="tagTrackingNumber" class="form-control" placeholder="e.g. BD-89234101">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Tracking URL (Optional Override)</label>
                <input type="url" name="tracking_url" id="tagTrackingUrl" class="form-control" placeholder="Leave empty to auto-generate from courier template">
            </div>

            <div style="margin-bottom: 1.5rem; background: #FDFBF7; padding: 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--admin-border);">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.88rem; cursor: pointer;">
                    <input type="checkbox" name="auto_ship" value="1" checked>
                    <span>Automatically update Order Status to <strong>SHIPPED</strong> & send notification email</span>
                </label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn-action edit" onclick="closeTagCourierModal()" style="padding: 0.6rem 1.2rem;">Cancel</button>
                <button type="submit" class="btn-gold-admin" style="padding: 0.6rem 1.4rem;">Save Tag</button>
            </div>
        </form>
    </div>
</div>

<script>
    const csrfToken = '<?= generate_csrf_token() ?>';

    function updateOrderStatus(orderId, newStatus) {
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('order_id', orderId);
        formData.append('status', newStatus);
        formData.append('csrf_token', csrfToken);
        formData.append('is_ajax', '1');

        fetch('orders.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Order #' + orderId + ' status updated successfully!');
            }
        });
    }

    function openTagCourierModal(order) {
        document.getElementById('tagOrderId').value = order.id;
        document.getElementById('tagOrderLabel').value = 'Order #' + String(order.id).padStart(6, '0') + ' (' + (order.user_name || 'Guest') + ')';
        document.getElementById('tagCourierSelect').value = order.courier_id || '';
        document.getElementById('tagTrackingNumber').value = order.tracking_number || '';
        document.getElementById('tagTrackingUrl').value = order.tracking_url || '';
        document.getElementById('tagCourierModal').style.display = 'flex';
    }

    function closeTagCourierModal() {
        document.getElementById('tagCourierModal').style.display = 'none';
    }

    function toggleOrderDetails(orderId) {
        const detailsRow = document.getElementById('details-row-' + orderId);
        if (detailsRow.style.display === 'none') {
            detailsRow.style.display = 'table-row';
            loadOrderItems(orderId);
        } else {
            detailsRow.style.display = 'none';
        }
    }

    function loadOrderItems(orderId) {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('view', orderId);
        window.location.href = currentUrl.toString();
    }
</script>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
