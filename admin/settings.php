<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Store Settings & Business Profile Management
 */
require_once __DIR__ . '/includes/admin_header.php';

$message = '';
$errorMsg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    if (!verify_csrf_token()) {
        $errorMsg = 'Invalid session security token.';
    } else {
        $storeName    = sanitize($_POST['store_name'] ?? '');
        $storeTagline = sanitize($_POST['store_tagline'] ?? '');
        $storePhone   = sanitize($_POST['store_phone'] ?? '');
        $storeAddress = sanitize($_POST['store_address'] ?? '');
        $storeGstin   = strtoupper(sanitize($_POST['store_gstin'] ?? ''));
        $storeEmail   = sanitize($_POST['store_email'] ?? '');
        $storeWebsite = sanitize($_POST['store_website'] ?? '');

        if (empty($storeName) || empty($storePhone) || empty($storeAddress)) {
            $errorMsg = 'Store Name, Phone Number, and Address are required.';
        } else {
            update_setting($pdo, 'store_name', $storeName);
            update_setting($pdo, 'store_tagline', $storeTagline);
            update_setting($pdo, 'store_phone', $storePhone);
            update_setting($pdo, 'store_address', $storeAddress);
            update_setting($pdo, 'store_gstin', $storeGstin);
            update_setting($pdo, 'store_email', $storeEmail);
            update_setting($pdo, 'store_website', $storeWebsite);

            log_admin_activity($pdo, 'Updated Store Business Settings & Legal Contact Info', 'settings', 0);
            $message = 'Store settings, contact info, and GSTIN updated successfully!';
        }
    }
}

// Fetch Current Settings
$currentName    = get_setting($pdo, 'store_name', 'FeMe – Ultimate Luxury Closet');
$currentTagline = get_setting($pdo, 'store_tagline', 'Haute Couture & Heritage Ensembles');
$currentPhone   = get_setting($pdo, 'store_phone', '+91 9134366366');
$currentAddress = get_setting($pdo, 'store_address', 'Shankar Plaza, 1st Floor, Opp - Idgha High School, Murgasol, Asansol-713303, Paschim Burdwan, West Bengal, India');
$currentGstin   = get_setting($pdo, 'store_gstin', '19AUMPB3683N1Z0');
$currentEmail   = get_setting($pdo, 'store_email', 'concierge@feme.com');
$currentWebsite = get_setting($pdo, 'store_website', 'www.feme.com');
?>

<div class="page-header-flex">
    <div>
        <h1 class="page-title">Store & Legal Business Settings</h1>
        <p style="font-size: 0.88rem; color: var(--admin-text-muted);">Manage boutique contact details, address, GSTIN, and receipt header parameters</p>
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

<div class="admin-card" style="max-width: 800px; padding: 2rem;">
    <form method="POST" action="settings.php">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        <input type="hidden" name="action" value="save_settings">

        <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--admin-gold); margin-bottom: 1.25rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.5rem;">
            <i class="fa-solid fa-store"></i> Boutique Branding Details
        </h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            <div>
                <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Store Name *</label>
                <input type="text" name="store_name" value="<?= sanitize($currentName) ?>" class="form-control" required>
            </div>
            <div>
                <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Tagline / Slogan</label>
                <input type="text" name="store_tagline" value="<?= sanitize($currentTagline) ?>" class="form-control">
            </div>
        </div>

        <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--admin-gold); margin-top: 1.5rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.5rem;">
            <i class="fa-solid fa-file-invoice-dollar"></i> GSTIN & Contact Details
        </h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            <div>
                <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Official GSTIN *</label>
                <input type="text" name="store_gstin" value="<?= sanitize($currentGstin) ?>" class="form-control" required style="text-transform: uppercase; font-family: monospace; font-weight: 700;">
            </div>
            <div>
                <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Store Mobile / Phone *</label>
                <input type="text" name="store_phone" value="<?= sanitize($currentPhone) ?>" class="form-control" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            <div>
                <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Support Email</label>
                <input type="email" name="store_email" value="<?= sanitize($currentEmail) ?>" class="form-control">
            </div>
            <div>
                <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Website URL</label>
                <input type="text" name="store_website" value="<?= sanitize($currentWebsite) ?>" class="form-control">
            </div>
        </div>

        <div style="margin-bottom: 1.75rem;">
            <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--admin-text-muted); display: block; margin-bottom: 4px;">Full Store Address (Prints on A4 Receipt & Contact Page) *</label>
            <textarea name="store_address" rows="3" class="form-control" required style="font-family: inherit; line-height: 1.5;"><?= sanitize($currentAddress) ?></textarea>
        </div>

        <div style="display: flex; justify-content: flex-end;">
            <button type="submit" class="btn-gold-admin" style="padding: 0.75rem 1.75rem;">
                <i class="fa-solid fa-floppy-disk"></i> Save Store Settings
            </button>
        </div>
    </form>
</div>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
?>
