<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Customer & Admin Login Page
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    if (is_admin()) {
        redirect('admin/index.php');
    } else {
        redirect('index.php');
    }
}

$redirectTarget = isset($_GET['redirect']) ? sanitize($_GET['redirect']) : (isset($_POST['redirect']) ? sanitize($_POST['redirect']) : 'index.php');
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verify_csrf_token()) {
        $errorMsg = 'Invalid session token. Please refresh and try again.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = trim($_POST['password'] ?? '');

        if (!$email || empty($password)) {
            $errorMsg = 'Please enter a valid email address and password.';
        } else {
            // 2. Check Rate Limit Lockout (5 failed attempts in 15 mins)
            if (check_login_rate_limit($pdo, $email)) {
                $errorMsg = 'Too many failed login attempts. Account temporarily locked for 15 minutes for your security.';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password_hash'])) {
                        // Clear failed login attempts
                        clear_login_attempts($pdo, $email);

                        // Regenerate Session ID to prevent session fixation
                        session_regenerate_id(true);

                        // Set Session Values
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];

                        // Set 1-Year Persistent Remember Token Cookie
                        set_persistent_login_token($pdo, $user['id']);

                        // Record Login in Database for Admin Monitoring
                        record_customer_login($pdo, $user['id'], 'password');

                        // Merge session guest cart into database cart
                        merge_session_cart_to_db($pdo, $user['id']);

                        // Redirect based on role / redirect param
                        if ($user['role'] === 'admin') {
                            redirect('admin/index.php');
                        } else {
                            redirect($redirectTarget);
                        }
                    } else {
                        // Record Failed Attempt
                        record_failed_login($pdo, $email);
                        $errorMsg = 'Invalid email address or password.';
                    }
                } catch (PDOException $e) {
                    $errorMsg = 'A system error occurred. Please try again later.';
                }
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
                    <h2 class="auth-title">Welcome Back</h2>
                    <p class="auth-subtitle">Sign in to access your royal closet & orders</p>
                </div>

                <!-- Success Alert -->
                <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
                    <div class="auth-alert success" style="margin-bottom: 1.5rem;">
                        <i class="fa-solid fa-circle-check"></i> Your password has been updated successfully. Please sign in below.
                    </div>
                <?php endif; ?>

                <!-- Error Alert -->
                <?php if (!empty($errorMsg)): ?>
                    <div class="auth-alert error">
                        <i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errorMsg) ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form action="login.php" method="POST" class="auth-form">
                    <?= csrf_token_input() ?>
                    <input type="hidden" name="redirect" value="<?= sanitize($redirectTarget) ?>">

                    <div class="form-group">
                        <label for="emailInput">Email Address</label>
                        <input type="email" name="email" id="emailInput" class="form-control" placeholder="client@feme.com" required value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                            <label for="passwordInput" style="margin-bottom: 0;">Password</label>
                            <a href="forgot_password.php" style="font-size: 0.8rem; color: var(--gold-primary); text-decoration: none;">Forgot Password?</a>
                        </div>
                        <input type="password" name="password" id="passwordInput" class="form-control" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn-gold auth-submit-btn">SIGN IN</button>
                </form>

                <div class="auth-footer">
                    <p>New to FeMe Luxury? <a href="register.php?redirect=<?= urlencode($redirectTarget) ?>">Create an Account</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
