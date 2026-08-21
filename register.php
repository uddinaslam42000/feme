<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Customer Registration Page
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('index.php');
}

$redirectTarget = isset($_GET['redirect']) ? sanitize($_GET['redirect']) : (isset($_POST['redirect']) ? sanitize($_POST['redirect']) : 'index.php');
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verify_csrf_token()) {
        $errorMsg = 'Invalid session token. Please refresh and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $phone = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if (empty($name) || !$email || empty($password)) {
            $errorMsg = 'Please fill in all required fields with a valid email address.';
        } elseif (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            // 2. Hardened Password Policy Enforcement
            $errorMsg = 'Password must be at least 8 characters long and contain at least one letter and one number.';
        } elseif ($password !== $confirmPassword) {
            $errorMsg = 'Password confirmation does not match.';
        } else {
            try {
                // Check email uniqueness
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    $errorMsg = 'An account with this email address already exists.';
                } else {
                    // Hash Password using BCRYPT
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                    $insertStmt = $pdo->prepare("
                        INSERT INTO users (name, email, password_hash, phone, role) 
                        VALUES (?, ?, ?, ?, 'customer')
                    ");
                    $insertStmt->execute([$name, $email, $passwordHash, $phone]);
                    $newUserId = $pdo->lastInsertId();

                    // Regenerate Session ID
                    session_regenerate_id(true);

                    // Set Session Values
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = 'customer';

                    // Set 1-Year Persistent Remember Token Cookie
                    set_persistent_login_token($pdo, $newUserId);

                    // Record Login in Database for Admin Monitoring
                    record_customer_login($pdo, $newUserId, 'registration');

                    // Merge session guest cart into database cart
                    merge_session_cart_to_db($pdo, $newUserId);

                    // Send Welcome Email (Trigger 1)
                    send_welcome_email($email, $name);

                    redirect($redirectTarget);
                }
            } catch (PDOException $e) {
                $errorMsg = 'Registration failed due to a database error. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section auth-section">
    <div class="container">
        <div class="auth-card-container">
            <div class="auth-card">
                <!-- Brand Header -->
                <div class="auth-brand-header">
                    <a href="index.php" class="brand-logo">
                        <span class="logo-text">Fe<span>Me</span></span>
                        <span class="logo-tagline">Ultimate Luxury Closet</span>
                    </a>
                    <h2 class="auth-title">Create an Account</h2>
                    <p class="auth-subtitle">Join our royal guest list for exclusive couture privileges</p>
                </div>

                <!-- Error Alert -->
                <?php if (!empty($errorMsg)): ?>
                    <div class="auth-alert error">
                        <i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errorMsg) ?>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form action="register.php" method="POST" class="auth-form">
                    <?= csrf_token_input() ?>
                    <input type="hidden" name="redirect" value="<?= sanitize($redirectTarget) ?>">

                    <div class="form-group">
                        <label for="nameInput">Full Name *</label>
                        <input type="text" name="name" id="nameInput" class="form-control" placeholder="Eleanor Vance" required value="<?= isset($_POST['name']) ? sanitize($_POST['name']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="emailInput">Email Address *</label>
                        <input type="email" name="email" id="emailInput" class="form-control" placeholder="client@feme.com" required value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="phoneInput">Phone Number</label>
                        <input type="tel" name="phone" id="phoneInput" class="form-control" placeholder="+91 98765 43210" value="<?= isset($_POST['phone']) ? sanitize($_POST['phone']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="passwordInput">Password * (Min 8 chars, letter + number)</label>
                        <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Min 8 characters (letter + number)" minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" required>
                    </div>

                    <div class="form-group">
                        <label for="confirmPasswordInput">Confirm Password *</label>
                        <input type="password" name="confirm_password" id="confirmPasswordInput" class="form-control" placeholder="Re-enter password" minlength="8" required>
                    </div>

                    <button type="submit" class="btn-gold auth-submit-btn">REGISTER ACCOUNT</button>
                </form>

                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php?redirect=<?= urlencode($redirectTarget) ?>">Sign In</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
