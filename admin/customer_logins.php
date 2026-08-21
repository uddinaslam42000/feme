<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Customer Logins Audit Trail
 */
require_once __DIR__ . '/includes/admin_header.php';

// Search and Filter Parameters
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$deviceFilter = isset($_GET['device']) ? trim($_GET['device']) : '';
$customerFilter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Stats calculations
try {
    $totalLogins = (int)$pdo->query("SELECT COUNT(*) FROM customer_logins")->fetchColumn();
    $todayLogins = (int)$pdo->query("SELECT COUNT(*) FROM customer_logins WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $uniqueUsersLogged = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM customer_logins")->fetchColumn();
    $mobileLogins = (int)$pdo->query("SELECT COUNT(*) FROM customer_logins WHERE device_type = 'Mobile'")->fetchColumn();
} catch (PDOException $e) {
    $totalLogins = 0;
    $todayLogins = 0;
    $uniqueUsersLogged = 0;
    $mobileLogins = 0;
}

// Fetch Logins with User Details
try {
    $sql = "
        SELECT cl.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
        FROM customer_logins cl
        LEFT JOIN users u ON cl.user_id = u.id
        WHERE 1=1
    ";
    $params = [];

    if ($customerFilter > 0) {
        $sql .= " AND cl.user_id = ?";
        $params[] = $customerFilter;
    }

    if ($deviceFilter !== '') {
        $sql .= " AND cl.device_type = ?";
        $params[] = $deviceFilter;
    }

    if ($searchQuery !== '') {
        $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR cl.ip_address LIKE ? OR cl.user_agent LIKE ?)";
        $term = '%' . $searchQuery . '%';
        $params = array_merge($params, [$term, $term, $term, $term]);
    }

    $sql .= " ORDER BY cl.id DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logins = $stmt->fetchAll();
} catch (PDOException $e) {
    $logins = [];
}
?>

<div class="page-header-flex">
    <div>
        <h1 class="page-title">Customer Login History</h1>
        <p style="font-size: 0.88rem; color: var(--admin-text-muted);">Real-time log of customer sign-ins, device types, and active sessions</p>
    </div>
</div>

<!-- 1. Stats Grid -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalLogins) ?></div>
            <div class="stat-label">Total Login Events</div>
        </div>
        <div class="stat-icon"><i class="fa-solid fa-arrow-right-to-bracket"></i></div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-value"><?= number_format($todayLogins) ?></div>
            <div class="stat-label">Logins Today</div>
        </div>
        <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-value"><?= number_format($uniqueUsersLogged) ?></div>
            <div class="stat-label">Unique Customers Logged</div>
        </div>
        <div class="stat-icon"><i class="fa-solid fa-user-check"></i></div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-value"><?= number_format($mobileLogins) ?></div>
            <div class="stat-label">Mobile Logins</div>
        </div>
        <div class="stat-icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
    </div>
</div>

<!-- 2. Search & Filter Bar -->
<div class="admin-card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="customer_logins.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 2; min-width: 240px;">
            <input type="text" name="q" value="<?= sanitize($searchQuery) ?>" class="form-control" placeholder="Search customer name, email, IP address...">
        </div>
        <div style="flex: 1; min-width: 160px;">
            <select name="device" class="form-control">
                <option value="">All Device Types</option>
                <option value="Desktop" <?= $deviceFilter === 'Desktop' ? 'selected' : '' ?>>Desktop</option>
                <option value="Mobile" <?= $deviceFilter === 'Mobile' ? 'selected' : '' ?>>Mobile</option>
                <option value="Tablet" <?= $deviceFilter === 'Tablet' ? 'selected' : '' ?>>Tablet</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn-gold-admin" style="padding: 0.55rem 1.2rem;">
                <i class="fa-solid fa-magnifying-glass"></i> Filter
            </button>
            <?php if ($searchQuery !== '' || $deviceFilter !== '' || $customerFilter > 0): ?>
                <a href="customer_logins.php" class="btn-action edit" style="padding: 0.55rem 1rem; text-decoration: none;">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- 3. Logins Table -->
<div class="admin-card">
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Customer</th>
                    <th>IP Address</th>
                    <th>Device</th>
                    <th>Login Method</th>
                    <th>Browser User-Agent</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logins)): ?>
                    <?php foreach ($logins as $l): ?>
                        <tr>
                            <td><strong>#<?= sprintf('%06d', $l['id']) ?></strong></td>
                            <td>
                                <?php if (!empty($l['user_name'])): ?>
                                    <a href="customers.php?view=<?= (int)$l['user_id'] ?>" style="color: var(--admin-text-dark); font-weight: 600; text-decoration: none;">
                                        <?= sanitize($l['user_name']) ?>
                                    </a>
                                    <div style="font-size: 0.78rem; color: var(--admin-text-muted);"><?= sanitize($l['user_email'] ?? '') ?></div>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-muted); font-style: italic;">Deleted User (#<?= (int)$l['user_id'] ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code style="font-size: 0.85rem; background: rgba(0,0,0,0.04); padding: 2px 6px; border-radius: 4px;">
                                    <?= sanitize($l['ip_address']) ?>
                                </code>
                            </td>
                            <td>
                                <?php if ($l['device_type'] === 'Mobile'): ?>
                                    <span class="badge-status badge-confirmed" style="background: rgba(46, 125, 50, 0.1); color: #2e7d32;">
                                        <i class="fa-solid fa-mobile-screen"></i> Mobile
                                    </span>
                                <?php elseif ($l['device_type'] === 'Tablet'): ?>
                                    <span class="badge-status badge-shipped" style="background: rgba(2, 136, 209, 0.1); color: #0288d1;">
                                        <i class="fa-solid fa-tablet-screen-button"></i> Tablet
                                    </span>
                                <?php else: ?>
                                    <span class="badge-status badge-pending" style="background: rgba(201, 162, 75, 0.15); color: #8C7335;">
                                        <i class="fa-solid fa-desktop"></i> Desktop
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($l['login_method'] === 'password'): ?>
                                    <span style="font-size: 0.82rem; font-weight: 500; color: #1a1a1a;">
                                        <i class="fa-solid fa-key" style="color: var(--admin-gold); font-size: 0.75rem;"></i> Password
                                    </span>
                                <?php elseif ($l['login_method'] === 'registration'): ?>
                                    <span style="font-size: 0.82rem; font-weight: 500; color: #2e7d32;">
                                        <i class="fa-solid fa-user-plus" style="font-size: 0.75rem;"></i> Registration
                                    </span>
                                <?php else: ?>
                                    <span style="font-size: 0.82rem; font-weight: 500; color: #0288d1;">
                                        <i class="fa-solid fa-cookie-bite" style="font-size: 0.75rem;"></i> Remember Token
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.76rem; color: var(--admin-text-muted);" title="<?= sanitize($l['user_agent']) ?>">
                                    <?= sanitize($l['user_agent']) ?>
                                </div>
                            </td>
                            <td>
                                <div><?= date('Y-m-d H:i:s', strtotime($l['created_at'])) ?></div>
                                <div style="font-size: 0.74rem; color: var(--admin-text-muted);">
                                    <?= time_elapsed_string($l['created_at']) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem; color: var(--admin-text-muted);">
                            <i class="fa-solid fa-user-clock" style="font-size: 2rem; margin-bottom: 0.5rem; display: block; opacity: 0.4;"></i>
                            No customer login events recorded yet.
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
