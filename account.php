<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Customer Account Panel & Orders Logistics Portal
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Authentication Guard - Redirect if not logged in
if (!is_logged_in()) {
    set_flash_message('error', 'Please log in to access your customer account panel.');
    redirect('login.php?redirect=account.php');
}

$userId = (int)$_SESSION['user_id'];
$message = '';
$errorMsg = '';

// Handle POST Form Submissions (Update Profile & Password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token()) {
        $errorMsg = 'Invalid session token. Please refresh and try again.';
    } else {
        $action = $_POST['action'];

        if ($action === 'update_profile') {
            $name = sanitize($_POST['name'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $address = sanitize($_POST['address'] ?? '');

            if (empty($name)) {
                $errorMsg = 'Full Name is required.';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
                    $stmt->execute([$name, $phone, $address, $userId]);
                    $_SESSION['user_name'] = $name;
                    $message = 'Profile and delivery address updated successfully!';
                } catch (PDOException $e) {
                    $errorMsg = 'Failed to update profile: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
                $errorMsg = 'Please fill in all password fields.';
            } elseif ($newPass !== $confirmPass) {
                $errorMsg = 'New password and confirmation password do not match.';
            } elseif (strlen($newPass) < 6) {
                $errorMsg = 'New password must be at least 6 characters long.';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $userHash = $stmt->fetchColumn();

                    if (!password_verify($currentPass, $userHash)) {
                        $errorMsg = 'Current password is incorrect.';
                    } else {
                        $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                        $upStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                        $upStmt->execute([$newHash, $userId]);
                        $message = 'Password changed successfully!';
                    }
                } catch (PDOException $e) {
                    $errorMsg = 'Failed to update password.';
                }
            }
        }
    }
}

// Fetch Fresh User Details
try {
    $uStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $uStmt->execute([$userId]);
    $user = $uStmt->fetch();
} catch (PDOException $e) {
    die("Database Error.");
}

// Fetch Customer Orders with Courier & Logistics Details
try {
    $oStmt = $pdo->prepare("
        SELECT o.*, c.name AS courier_name, c.code AS courier_code, c.phone AS courier_phone
        FROM orders o
        LEFT JOIN couriers c ON o.courier_id = c.id
        WHERE o.user_id = ?
        ORDER BY o.id DESC
    ");
    $oStmt->execute([$userId]);
    $userOrders = $oStmt->fetchAll();

    // Fetch items for each order
    $ordersWithItems = [];
    foreach ($userOrders as $ord) {
        $iStmt = $pdo->prepare("
            SELECT oi.*, p.name AS product_name, p.slug AS product_slug, cat.name AS category_name,
                   (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) AS primary_img
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            WHERE oi.order_id = ?
        ");
        $iStmt->execute([$ord['id']]);
        $ord['items'] = $iStmt->fetchAll();
        $ordersWithItems[] = $ord;
    }
} catch (PDOException $e) {
    $ordersWithItems = [];
}

// Customer Stats
$totalOrdersCount = count($ordersWithItems);
$activeOrdersCount = 0;
$deliveredOrdersCount = 0;
$totalSpent = 0;

foreach ($ordersWithItems as $o) {
    $totalSpent += (float)$o['total_amount'];
    if (in_array($o['status'], ['pending', 'confirmed', 'shipped'])) {
        $activeOrdersCount++;
    } elseif ($o['status'] === 'delivered') {
        $deliveredOrdersCount++;
    }
}

$pageTitle = "My Royal Account | FeMe Luxury Closet";
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top: 2rem; padding-bottom: 4rem; background-color: var(--bg-cream);">
    <div class="container">

        <!-- Welcome Banner Card -->
        <div style="background: linear-gradient(135deg, #1A1A1A 0%, #2A2A2A 100%); color: #FFF; border-radius: var(--radius-md); padding: 2rem; margin-bottom: 2rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-soft); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
            <div>
                <div style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 2px; color: var(--gold-primary); font-weight: 600; margin-bottom: 4px;">
                    ✦ VIP Client Profile ✦
                </div>
                <h1 style="font-family: var(--font-serif); font-size: 2rem; font-weight: 700; color: #FFF;">
                    Welcome Back, <?= sanitize($user['name']) ?>
                </h1>
                <p style="font-size: 0.88rem; color: #BBB; margin-top: 4px;">
                    Member Email: <strong><?= sanitize($user['email']) ?></strong> &bull; Registered: <?= date('F j, Y', strtotime($user['created_at'])) ?>
                </p>
            </div>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <?php if (is_admin()): ?>
                    <a href="admin/index.php" class="btn btn-secondary" style="font-size: 0.82rem; padding: 0.6rem 1.2rem;">
                        <i class="fa-solid fa-gauge"></i> Admin Console
                    </a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-outline" style="border-color: #ff4d4f; color: #ff4d4f; font-size: 0.82rem; padding: 0.6rem 1.2rem;">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out
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

        <!-- Quick Stats Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem;">
            <div style="background: #FFF; border-radius: var(--radius-md); padding: 1.25rem; border: 1px solid var(--border-color); box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Total Orders</div>
                <div style="font-family: var(--font-serif); font-size: 1.8rem; font-weight: 700; color: var(--text-dark); margin-top: 4px;"><?= $totalOrdersCount ?></div>
            </div>
            <div style="background: #FFF; border-radius: var(--radius-md); padding: 1.25rem; border: 1px solid var(--border-color); box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--gold-primary);">Active Logistics</div>
                <div style="font-family: var(--font-serif); font-size: 1.8rem; font-weight: 700; color: var(--gold-primary); margin-top: 4px;"><?= $activeOrdersCount ?></div>
            </div>
            <div style="background: #FFF; border-radius: var(--radius-md); padding: 1.25rem; border: 1px solid var(--border-color); box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #389e0d;">Delivered Ensembles</div>
                <div style="font-family: var(--font-serif); font-size: 1.8rem; font-weight: 700; color: #389e0d; margin-top: 4px;"><?= $deliveredOrdersCount ?></div>
            </div>
            <div style="background: #FFF; border-radius: var(--radius-md); padding: 1.25rem; border: 1px solid var(--border-color); box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Lifetime Expenditure</div>
                <div style="font-family: var(--font-serif); font-size: 1.8rem; font-weight: 700; color: var(--text-dark); margin-top: 4px;"><?= format_price($totalSpent) ?></div>
            </div>
        </div>

        <!-- Account Tab Controls -->
        <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--border-color); margin-bottom: 2rem; flex-wrap: wrap;">
            <button class="account-tab-btn active" onclick="switchAccountTab('orders', this)">
                <i class="fa-solid fa-box-open"></i> My Orders & Logistics Track
            </button>
            <button class="account-tab-btn" onclick="switchAccountTab('profile', this)">
                <i class="fa-solid fa-address-book"></i> Profile & Delivery Address
            </button>
            <button class="account-tab-btn" onclick="switchAccountTab('security', this)">
                <i class="fa-solid fa-shield-halved"></i> Security & Password
            </button>
        </div>

        <!-- TAB 1: MY ORDERS & LOGISTICS TRACK -->
        <div id="tab-orders" class="account-tab-pane" style="display: block;">
            <?php if (!empty($ordersWithItems)): ?>
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <?php foreach ($ordersWithItems as $o): ?>
                        <div style="background: #FFF; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                            
                            <!-- Order Header Row -->
                            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #F0F0F0; padding-bottom: 1rem; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                                <div>
                                    <span style="font-family: var(--font-serif); font-weight: 700; font-size: 1.1rem; color: var(--text-dark);">
                                        Order #<?= sprintf('%06d', $o['id']) ?>
                                    </span>
                                    <span style="font-size: 0.82rem; color: var(--text-muted); margin-left: 10px;">
                                        Placed on <?= date('d M Y, h:i A', strtotime($o['created_at'])) ?>
                                    </span>
                                </div>

                                <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                                    <span class="badge-status badge-<?= strtolower($o['payment_status']) ?>">
                                        PAYMENT: <?= strtoupper($o['payment_status']) ?>
                                    </span>
                                    <span class="badge-status badge-<?= strtolower($o['status']) ?>" style="font-size: 0.8rem; padding: 0.35rem 0.85rem;">
                                        STATUS: <?= strtoupper($o['status']) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Courier & Logistics Section -->
                            <div style="background: #FDFBF7; padding: 1rem; border-radius: var(--radius-sm); border: 1px dashed var(--border-color); margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                                <div>
                                    <div style="font-size: 0.78rem; font-weight: 600; text-transform: uppercase; color: var(--gold-primary); margin-bottom: 4px;">
                                        <i class="fa-solid fa-truck-fast"></i> Logistics & Package Tracking
                                    </div>
                                    <?php if (!empty($o['courier_name'])): ?>
                                        <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-dark);">
                                            Handled by: <?= sanitize($o['courier_name']) ?> (<?= sanitize($o['courier_code']) ?>)
                                        </div>
                                        <?php if (!empty($o['tracking_number'])): ?>
                                            <div style="font-size: 0.84rem; font-family: monospace; font-weight: 600; color: var(--gold-muted); margin-top: 2px;">
                                                AWB Tracking #: <?= sanitize($o['tracking_number']) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div style="font-size: 0.85rem; color: var(--text-muted); italic">
                                            Logistics carrier tag pending dispatch.
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <?php if (!empty($o['tracking_url'])): ?>
                                        <a href="<?= sanitize($o['tracking_url']) ?>" target="_blank" class="btn btn-primary" style="padding: 0.45rem 1rem; font-size: 0.8rem;">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Track Package Live
                                        </a>
                                    <?php endif; ?>
                                    <a href="admin/invoice.php?id=<?= $o['id'] ?>" target="_blank" class="btn btn-secondary" style="padding: 0.45rem 1rem; font-size: 0.8rem;">
                                        <i class="fa-solid fa-print"></i> Tax Receipt (A4)
                                    </a>
                                </div>
                            </div>

                            <!-- Item List -->
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <?php foreach ($o['items'] as $item): ?>
                                    <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid #F7F7F7;">
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <?php if (!empty($item['primary_img'])): ?>
                                                <img src="<?= sanitize($item['primary_img']) ?>" alt="<?= sanitize($item['product_name']) ?>" style="width: 55px; height: 65px; object-fit: cover; border-radius: 4px;">
                                            <?php endif; ?>
                                            <div>
                                                <h4 style="font-family: var(--font-serif); font-size: 0.95rem; font-weight: 600; color: var(--text-dark);">
                                                    <?= sanitize($item['product_name']) ?>
                                                </h4>
                                                <div style="font-size: 0.78rem; color: var(--text-muted);"><?= sanitize($item['category_name'] ?? 'Luxury Couture') ?></div>
                                                <div style="font-size: 0.82rem; color: var(--text-dark); margin-top: 2px;">
                                                    Qty: <?= $item['quantity'] ?> &times; <?= format_price($item['price']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div style="font-weight: 700; font-size: 1rem; color: var(--text-dark);">
                                            <?= format_price($item['price'] * $item['quantity']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Footer Total -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color);">
                                <div style="font-size: 0.85rem; color: var(--text-muted);">
                                    Payment Method: <strong style="text-transform: uppercase; color: var(--text-dark);"><?= sanitize($o['payment_method']) ?></strong>
                                </div>
                                <div style="font-size: 1.1rem; font-weight: 700; color: var(--text-dark);">
                                    Total: <span style="color: var(--gold-primary); font-family: var(--font-serif);"><?= format_price($o['total_amount']) ?></span>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="background: #FFF; border-radius: var(--radius-md); padding: 3rem; text-align: center; border: 1px solid var(--border-color);">
                    <i class="fa-solid fa-bag-shopping" style="font-size: 3rem; color: var(--gold-primary); margin-bottom: 1rem;"></i>
                    <h3 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--text-dark); margin-bottom: 0.5rem;">No Royal Orders Placed Yet</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">Explore our handcrafted Kanjeevarams, Anarkalis, and Haute Couture ensembles.</p>
                    <a href="category.php" class="btn btn-primary">Explore Luxury Collection</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB 2: EDIT PROFILE & DELIVERY ADDRESS -->
        <div id="tab-profile" class="account-tab-pane" style="display: none;">
            <div style="background: #FFF; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 2rem; max-width: 680px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <h3 style="font-family: var(--font-serif); font-size: 1.3rem; margin-bottom: 1.5rem; color: var(--gold-primary);">
                    <i class="fa-solid fa-user-pen"></i> Update Profile & Delivery Address
                </h3>

                <form method="POST" action="account.php">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="action" value="update_profile">

                    <div style="margin-bottom: 1.25rem;">
                        <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 6px;">Full Name *</label>
                        <input type="text" name="name" value="<?= sanitize($user['name']) ?>" class="form-control" required style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); width: 100%;">
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 6px;">Email Address (Read-only)</label>
                        <input type="email" value="<?= sanitize($user['email']) ?>" class="form-control" readonly style="background: #F7F3EC; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); width: 100%;">
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 6px;">Mobile / Phone Number</label>
                        <input type="text" name="phone" value="<?= sanitize($user['phone'] ?? '') ?>" class="form-control" placeholder="+91 98765 43210" style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); width: 100%;">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 6px;">Default Delivery Address</label>
                        <textarea name="address" rows="4" class="form-control" placeholder="House/Villa No, Street, Landmark, City, State - Pincode" style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); width: 100%; font-family: inherit;"><?= sanitize($user['address'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.75rem;">
                        <i class="fa-solid fa-floppy-disk"></i> Save Profile & Address
                    </button>
                </form>
            </div>
        </div>

        <!-- TAB 3: ACCOUNT SECURITY & PASSWORD -->
        <div id="tab-security" class="account-tab-pane" style="display: none;">
            <div style="background: #FFF; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 2rem; max-width: 550px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <h3 style="font-family: var(--font-serif); font-size: 1.3rem; margin-bottom: 1.5rem; color: var(--gold-primary);">
                    <i class="fa-solid fa-lock"></i> Change Security Password
                </h3>

                <form method="POST" action="account.php">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div style="margin-bottom: 1.25rem;">
                        <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 6px;">Current Password *</label>
                        <input type="password" name="current_password" class="form-control" required style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); width: 100%;">
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 6px;">New Password *</label>
                        <input type="password" name="new_password" class="form-control" required style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); width: 100%;">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 6px;">Confirm New Password *</label>
                        <input type="password" name="confirm_password" class="form-control" required style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm); width: 100%;">
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.75rem;">
                        <i class="fa-solid fa-key"></i> Update Password
                    </button>
                </form>
            </div>
        </div>

    </div>
</section>

<style>
    .account-tab-btn {
        background: none;
        border: none;
        padding: 0.85rem 1.5rem;
        font-family: var(--font-sans);
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .account-tab-btn:hover, .account-tab-btn.active {
        color: var(--gold-primary);
        border-bottom-color: var(--gold-primary);
    }
</style>

<script>
    function switchAccountTab(tabName, btnElement) {
        document.querySelectorAll('.account-tab-pane').forEach(pane => {
            pane.style.display = 'none';
        });
        document.querySelectorAll('.account-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        document.getElementById('tab-' + tabName).style.display = 'block';
        btnElement.classList.add('active');
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
