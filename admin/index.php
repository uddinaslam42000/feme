<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Dashboard Overview
 */
require_once __DIR__ . '/includes/admin_header.php';

// Fetch Stat Card Numbers
try {
    $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
    $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
} catch (PDOException $e) {
    $totalOrders = 0;
    $totalRevenue = 0;
    $totalProducts = 0;
    $pendingOrders = 0;
}

// Fetch Last 10 Recent Orders
try {
    $stmt = $pdo->query("
        SELECT o.*, u.name AS user_name, u.email AS user_email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.id DESC 
        LIMIT 10
    ");
    $recentOrders = $stmt->fetchAll();
} catch (PDOException $e) {
    $recentOrders = [];
}
?>

<div class="page-header-flex">
    <div>
        <h1 class="page-title">Dashboard Overview</h1>
        <p style="font-size: 0.88rem; color: var(--admin-text-muted);">Real-time metrics & management console</p>
    </div>
</div>

<!-- 1. Top Stat Cards Row -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalOrders) ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-icon"><i class="fa-solid fa-bag-shopping"></i></div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-value"><?= format_price($totalRevenue) ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalProducts) ?></div>
            <div class="stat-label">Total Products</div>
        </div>
        <div class="stat-icon"><i class="fa-solid fa-shirt"></i></div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-value"><?= number_format($pendingOrders) ?></div>
            <div class="stat-label">Pending Orders</div>
        </div>
        <div class="stat-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
    </div>
</div>

<!-- 2. Recent Orders Table -->
<div class="admin-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
        <h3 class="card-title" style="margin-bottom: 0;">Recent Orders</h3>
        <a href="orders.php" style="color: var(--admin-gold); font-size: 0.85rem; font-weight: 600; text-decoration: none;">View All Orders &rarr;</a>
    </div>

    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Date</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recentOrders)): ?>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><strong>#<?= sprintf('%06d', $order['id']) ?></strong></td>
                            <td><?= sanitize($order['user_name'] ?? 'Guest Client') ?></td>
                            <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                            <td><strong><?= format_price($order['total_amount']) ?></strong></td>
                            <td>
                                <span class="badge-status badge-<?= sanitize($order['status']) ?>">
                                    <?= strtoupper(sanitize($order['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <a href="orders.php?view=<?= $order['id'] ?>" class="btn-action edit" title="View Order Details">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--admin-text-muted); padding: 2rem;">
                            No orders placed yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
