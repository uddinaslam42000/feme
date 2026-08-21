<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Customers Listing & Client Profile Detail View
 */
require_once __DIR__ . '/includes/admin_header.php';

// Search and Sort Parameters
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$sortBy      = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
$viewUserId  = isset($_GET['view']) ? (int)$_GET['view'] : 0;

// Fetch Customers with Stats & Lifetime Orders
try {
    $sql = "
        SELECT u.*, 
               COUNT(o.id) AS total_orders,
               COALESCE(SUM(o.total_amount), 0) AS total_spent
        FROM users u
        LEFT JOIN orders o ON o.user_id = u.id
        WHERE u.role = 'customer'
    ";
    $params = [];

    if ($searchQuery !== '') {
        $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.address LIKE ?)";
        $term = '%' . $searchQuery . '%';
        $params = array_merge($params, [$term, $term, $term, $term]);
    }

    $sql .= " GROUP BY u.id";

    if ($sortBy === 'spent') {
        $sql .= " ORDER BY total_spent DESC, u.id DESC";
    } elseif ($sortBy === 'orders') {
        $sql .= " ORDER BY total_orders DESC, u.id DESC";
    } else {
        $sql .= " ORDER BY u.id DESC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    $customers = [];
}

// Fetch Client Order History & Login History if a specific client view is open
$clientOrders = [];
$clientLogins = [];
$selectedCustomer = null;
if ($viewUserId > 0) {
    try {
        $uStmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
        $uStmt->execute([$viewUserId]);
        $selectedCustomer = $uStmt->fetch();

        $oStmt = $pdo->prepare("
            SELECT o.*, c.name AS courier_name, c.code AS courier_code
            FROM orders o
            LEFT JOIN couriers c ON o.courier_id = c.id
            WHERE o.user_id = ?
            ORDER BY o.id DESC
        ");
        $oStmt->execute([$viewUserId]);
        $clientOrders = $oStmt->fetchAll();

        $lStmt = $pdo->prepare("
            SELECT * FROM customer_logins 
            WHERE user_id = ? 
            ORDER BY id DESC 
            LIMIT 5
        ");
        $lStmt->execute([$viewUserId]);
        $clientLogins = $lStmt->fetchAll();
    } catch (PDOException $e) {
        $clientOrders = [];
        $clientLogins = [];
    }
}
?>

<div class="page-header-flex">
    <div>
        <h1 class="page-title">Registered Customers</h1>
        <p style="font-size: 0.88rem; color: var(--admin-text-muted);">Inspect client addresses, lifetime orders & full profile details</p>
    </div>
</div>

<!-- Search & Sort Filter Bar -->
<div class="admin-card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="customers.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 2; min-width: 240px;">
            <input type="text" name="q" value="<?= sanitize($searchQuery) ?>" class="form-control" placeholder="Search customer name, email, phone, or address...">
        </div>
        <div style="flex: 1; min-width: 180px;">
            <select name="sort" class="form-control">
                <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest Registrations</option>
                <option value="spent" <?= $sortBy === 'spent' ? 'selected' : '' ?>>Highest Lifetime Spent</option>
                <option value="orders" <?= $sortBy === 'orders' ? 'selected' : '' ?>>Most Orders Placed</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn-gold-admin" style="padding: 0.55rem 1.2rem;">
                <i class="fa-solid fa-magnifying-glass"></i> Search
            </button>
            <?php if ($searchQuery !== '' || $sortBy !== 'newest'): ?>
                <a href="customers.php" class="btn-action edit" style="padding: 0.55rem 1rem; text-decoration: none;">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="admin-card">
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Client ID</th>
                    <th>Full Name</th>
                    <th>Email & Phone</th>
                    <th>Shipping / Billing Address</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <th>Last Login</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($customers)): ?>
                    <?php foreach ($customers as $c): ?>
                        <tr id="customer-row-<?= $c['id'] ?>" class="<?= $viewUserId === $c['id'] ? 'highlight-row' : '' ?>" style="cursor: pointer;" onclick="toggleCustomerDetails(<?= $c['id'] ?>, event)">
                            <td><strong>#<?= sprintf('%05d', $c['id']) ?></strong></td>
                            <td>
                                <strong><?= sanitize($c['name']) ?></strong>
                            </td>
                            <td>
                                <div><?= sanitize($c['email']) ?></div>
                                <div style="font-size: 0.78rem; color: var(--admin-text-muted);"><?= sanitize($c['phone'] ?? '—') ?></div>
                            </td>
                            <td>
                                <div style="max-width: 250px; font-size: 0.84rem; white-space: pre-line; color: var(--admin-text-dark); line-height: 1.4;">
                                    <?= !empty($c['address']) ? sanitize($c['address']) : '<span style="color: var(--admin-text-muted); italic">No address saved</span>' ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge-status badge-confirmed">
                                    <strong><?= (int)$c['total_orders'] ?></strong> Orders
                                </span>
                            </td>
                            <td><strong><?= format_price($c['total_spent']) ?></strong></td>
                            <td>
                                <?php if (!empty($c['last_login_at'])): ?>
                                    <div style="font-weight: 600; font-size: 0.84rem; color: #2e7d32;">
                                        <i class="fa-solid fa-circle-check" style="font-size: 0.75rem;"></i> <?= date('Y-m-d H:i', strtotime($c['last_login_at'])) ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--admin-text-muted);">
                                        IP: <code><?= sanitize($c['last_login_ip'] ?? '—') ?></code> (<?= (int)($c['login_count'] ?? 1) ?> logins)
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-muted); font-size: 0.82rem; font-style: italic;">No login recorded</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('Y-m-d', strtotime($c['created_at'])) ?></td>
                            <td>
                                <button class="btn-action edit" onclick="toggleCustomerDetails(<?= $c['id'] ?>, event)" title="View Full Details">
                                    <i class="fa-solid fa-address-card"></i> Details
                                </button>
                            </td>
                        </tr>

                        <!-- Customer Full Details Accordion Row -->
                        <tr id="details-row-<?= $c['id'] ?>" style="display: <?= $viewUserId === $c['id'] ? 'table-row' : 'none' ?>; background: #FFF;">
                            <td colspan="9" style="padding: 1.75rem; border-top: 2px solid var(--admin-gold);">
                                <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                                    <!-- Full Profile & Address Details -->
                                    <div style="flex: 1; min-width: 300px; background: #FDFBF7; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--admin-border);">
                                        <h4 style="font-family: var(--font-serif); font-size: 1.1rem; margin-bottom: 1rem; color: var(--admin-gold);">
                                            <i class="fa-solid fa-user"></i> Full Customer Profile
                                        </h4>
                                        <div style="margin-bottom: 0.75rem;">
                                            <span style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block;">Full Name</span>
                                            <strong style="font-size: 1rem; color: var(--admin-text-dark);"><?= sanitize($c['name']) ?></strong>
                                        </div>
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 0.75rem;">
                                            <div>
                                                <span style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block;">Email</span>
                                                <span style="font-size: 0.88rem;"><?= sanitize($c['email']) ?></span>
                                            </div>
                                            <div>
                                                <span style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block;">Phone Number</span>
                                                <span style="font-size: 0.88rem;"><?= sanitize($c['phone'] ?? 'N/A') ?></span>
                                            </div>
                                        </div>

                                        <div style="margin-bottom: 1rem;">
                                            <span style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: var(--admin-gold); display: block; margin-bottom: 4px;">
                                                <i class="fa-solid fa-house-chimney"></i> Primary Address & Location
                                            </span>
                                            <div style="background: #FFF; padding: 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--admin-border); font-size: 0.9rem; white-space: pre-line; color: var(--admin-text-dark); line-height: 1.5;">
                                                <?= !empty($c['address']) ? sanitize($c['address']) : 'No primary address recorded in profile.' ?>
                                            </div>
                                        </div>

                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.85rem; border-top: 1px dashed var(--admin-border); padding-top: 0.75rem; margin-bottom: 1rem;">
                                            <div><strong>Total Lifetime Orders:</strong> <?= (int)$c['total_orders'] ?></div>
                                            <div><strong>Total Lifetime Spent:</strong> <?= format_price($c['total_spent']) ?></div>
                                            <div><strong>Total Sign-ins:</strong> <?= (int)($c['login_count'] ?? 0) ?> times</div>
                                            <div><strong>Last Login:</strong> <?= !empty($c['last_login_at']) ? date('M j, Y H:i', strtotime($c['last_login_at'])) : 'Never' ?></div>
                                            <div style="grid-column: span 2; color: var(--admin-text-muted);">
                                                <strong>Registered On:</strong> <?= date('F j, Y, g:i a', strtotime($c['created_at'])) ?>
                                            </div>
                                        </div>

                                        <!-- Recent Login History Box -->
                                        <div style="border-top: 1px solid var(--admin-border); padding-top: 0.75rem;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                                <span style="font-size: 0.82rem; font-weight: 700; color: var(--admin-gold); text-transform: uppercase;">
                                                    <i class="fa-solid fa-shield-halved"></i> Recent Sign-in Sessions
                                                </span>
                                                <a href="customer_logins.php?user_id=<?= $c['id'] ?>" style="font-size: 0.75rem; color: var(--admin-gold); text-decoration: none; font-weight: 600;">View All &rarr;</a>
                                            </div>
                                            <?php if ($viewUserId === $c['id'] && !empty($clientLogins)): ?>
                                                <div style="background: #FFF; border-radius: var(--radius-sm); border: 1px solid var(--admin-border); padding: 0.5rem; font-size: 0.78rem;">
                                                    <?php foreach ($clientLogins as $clog): ?>
                                                        <div style="display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px dashed #eee;">
                                                            <span><i class="fa-solid fa-<?= $clog['device_type'] === 'Mobile' ? 'mobile-screen' : ($clog['device_type'] === 'Tablet' ? 'tablet-screen-button' : 'desktop') ?>" style="color: var(--admin-gold); margin-right: 4px;"></i> <?= sanitize($clog['ip_address']) ?> (<?= sanitize($clog['device_type']) ?>)</span>
                                                            <span style="color: var(--admin-text-muted);"><?= date('Y-m-d H:i', strtotime($clog['created_at'])) ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="font-size: 0.78rem; color: var(--admin-text-muted); italic">Click "Details" to inspect sign-in logs.</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Client Order History -->
                                    <div style="flex: 1.5; min-width: 320px;">
                                        <h4 style="font-family: var(--font-serif); font-size: 1.1rem; margin-bottom: 1rem; color: var(--admin-gold);">
                                            <i class="fa-solid fa-clock-rotate-left"></i> Client Order History
                                        </h4>
                                        <div id="customer-orders-container-<?= $c['id'] ?>">
                                            <?php if ($viewUserId === $c['id']): ?>
                                                <?php if (!empty($clientOrders)): ?>
                                                    <div class="admin-table-wrapper">
                                                        <table class="admin-table" style="font-size: 0.85rem;">
                                                            <thead>
                                                                <tr>
                                                                    <th>Order #</th>
                                                                    <th>Date</th>
                                                                    <th>Total</th>
                                                                    <th>Status</th>
                                                                    <th>Courier Service</th>
                                                                    <th>Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($clientOrders as $co): ?>
                                                                    <tr>
                                                                        <td><strong>#<?= sprintf('%06d', $co['id']) ?></strong></td>
                                                                        <td><?= date('Y-m-d', strtotime($co['created_at'])) ?></td>
                                                                        <td><strong><?= format_price($co['total_amount']) ?></strong></td>
                                                                        <td>
                                                                            <span class="badge-status badge-<?= strtolower($co['status']) ?>">
                                                                                <?= strtoupper($co['status']) ?>
                                                                            </span>
                                                                        </td>
                                                                        <td>
                                                                            <?php if (!empty($co['courier_name'])): ?>
                                                                                <span style="color: var(--admin-gold); font-weight: 600;">
                                                                                    <?= sanitize($co['courier_name']) ?>
                                                                                </span>
                                                                                <?php if (!empty($co['tracking_number'])): ?>
                                                                                    <div style="font-size: 0.75rem; font-family: monospace; color: var(--admin-text-muted);">
                                                                                        AWB: <?= sanitize($co['tracking_number']) ?>
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                            <?php else: ?>
                                                                                <span style="color: var(--admin-text-muted); font-size: 0.78rem;">Unassigned</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td>
                                                                            <div style="display: flex; gap: 4px;">
                                                                                <a href="orders.php?view=<?= $co['id'] ?>#order-row-<?= $co['id'] ?>" class="btn-action edit" style="text-decoration: none;" target="_blank" title="View Order Details">
                                                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Open
                                                                                </a>
                                                                                <a href="invoice.php?id=<?= $co['id'] ?>" class="btn-action edit" style="text-decoration: none;" target="_blank" title="Print A4 GST Receipt">
                                                                                    <i class="fa-solid fa-print"></i> Receipt
                                                                                </a>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php else: ?>
                                                    <p style="color: var(--admin-text-muted); padding: 1rem 0;">This customer has not placed any orders yet.</p>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <button class="btn-gold-admin" style="padding: 0.45rem 0.9rem; font-size: 0.82rem;" onclick="loadCustomerOrders(<?= $c['id'] ?>, event)">
                                                    <i class="fa-solid fa-clock-rotate-left"></i> Load Order History
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align: center; padding: 2rem;">No registered customers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleCustomerDetails(customerId, event) {
        // Prevent click if button inside cell was clicked
        if (event && (event.target.tagName === 'A' || event.target.tagName === 'INPUT' || event.target.tagName === 'SELECT')) {
            return;
        }

        const detailsRow = document.getElementById('details-row-' + customerId);
        if (detailsRow.style.display === 'none') {
            detailsRow.style.display = 'table-row';
            loadCustomerOrders(customerId);
        } else {
            detailsRow.style.display = 'none';
        }
    }

    function loadCustomerOrders(customerId, event) {
        if (event) event.stopPropagation();
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('view', customerId);
        window.location.href = currentUrl.toString();
    }
</script>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
