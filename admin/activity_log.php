<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Activity Logs Viewer
 */
require_once __DIR__ . '/includes/admin_header.php';

try {
    $stmt = $pdo->query("
        SELECT al.*, u.name AS admin_name, u.email AS admin_email 
        FROM admin_logs al
        LEFT JOIN users u ON al.admin_id = u.id
        ORDER BY al.id DESC
        LIMIT 100
    ");
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    $logs = [];
}
?>

<div class="page-header-flex">
    <div>
        <h1 class="page-title">Admin Activity Logs</h1>
        <p style="font-size: 0.88rem; color: var(--admin-text-muted);">Security & administrative audit trail</p>
    </div>
</div>

<div class="admin-card">
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Admin User</th>
                    <th>Action Executed</th>
                    <th>Target Resource</th>
                    <th>Target ID</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><strong>#<?= sprintf('%06d', $log['id']) ?></strong></td>
                            <td>
                                <strong><?= sanitize($log['admin_name'] ?? 'System Admin') ?></strong>
                                <div style="font-size: 0.78rem; color: var(--admin-text-muted);"><?= sanitize($log['admin_email'] ?? '') ?></div>
                            </td>
                            <td><span class="badge-status badge-confirmed"><?= sanitize($log['action']) ?></span></td>
                            <td><code><?= sanitize($log['target_table'] ?? 'system') ?></code></td>
                            <td><?= $log['target_id'] ? '#' . $log['target_id'] : '—' ?></td>
                            <td><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align: center; padding: 2rem;">No administrative activity logged yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
