<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Newsletter Subscribers Listing
 */
require_once __DIR__ . '/includes/admin_header.php';

try {
    $stmt = $pdo->query("SELECT * FROM newsletter_subscribers ORDER BY id DESC");
    $subscribers = $stmt->fetchAll();
} catch (PDOException $e) {
    $subscribers = [];
}
?>

<div class="page-header-flex">
    <div>
        <h1 class="page-title">Royal Gazette Subscribers</h1>
        <p style="font-size: 0.88rem; color: var(--admin-text-muted);">Manage your newsletter mailing list</p>
    </div>
</div>

<div class="admin-card">
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Subscriber ID</th>
                    <th>Email Address</th>
                    <th>Subscribed Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($subscribers)): ?>
                    <?php foreach ($subscribers as $s): ?>
                        <tr>
                            <td><strong>#<?= sprintf('%05d', $s['id']) ?></strong></td>
                            <td><strong><?= sanitize($s['email']) ?></strong></td>
                            <td><?= date('Y-m-d H:i', strtotime($s['subscribed_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align: center; padding: 2rem;">No newsletter subscribers yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
