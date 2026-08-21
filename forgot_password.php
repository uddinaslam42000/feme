<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Forgot Password Request Page
 */
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/mailer.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('index.php');
}

$message = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errorMsg = 'Invalid session token. Please refresh and try again.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

        if (!$email) {
            $errorMsg = 'Please enter a valid email address.';
        } else {
            try {
                // Find user by email
                $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    // Generate secure 64-char hex token
                    $rawToken = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $rawToken);
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

                    // Remove existing active tokens for this user
                    $delStmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
                    $delStmt->execute([$user['id']]);

                    // Insert new token
                    $insertStmt = $pdo->prepare("
                        INSERT INTO password_resets (user_id, token_hash, expires_at) 
                        VALUES (?, ?, ?)
                    ");
                    $insertStmt->execute([$user['id'], $tokenHash, $expiresAt]);

                    // Build Reset URL
                    $resetUrl = BASE_URL . 'reset_password.php?token=' . $rawToken;

                    // Build Branded Email
                    $bodyHtml = '
                    <p style="font-size: 0.95rem; line-height: 1.7; color: #1A1A1A;">Dear <strong>' . sanitize($user['name']) . '</strong>,</p>
                    <p style="font-size: 0.95rem; line-height: 1.7; color: #6B6862;">We received a request to reset the password for your FeMe account. Click the button below to choose a new password. This link is valid for 30 minutes.</p>
                    ';

                    $emailHtml = build_luxury_email_html(
                        "Reset Your FeMe Password",
                        "Account Password Reset Request",
                        $bodyHtml,
                        $resetUrl,
                        "RESET PASSWORD"
                    );

                    send_email($user['email'], "Reset Your FeMe Account Password", $emailHtml, $user['name']);
                }

                // Always display generic message to prevent email enumeration
                $message = 'If an account exists for ' . sanitize($email) . ', a password reset link has been dispatched to your email address.';

            } catch (PDOException $e) {
                $errorMsg = 'A system error occurred. Please try again later.';
            }
        }
    }
}
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
                    <h2 class="auth-title">Forgot Password</h2>
                    <p class="auth-subtitle">Enter your email to receive a password reset link</p>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="auth-alert success" style="margin-bottom: 1.5rem;">
                        <i class="fa-solid fa-circle-check"></i> <?= sanitize($message) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMsg)): ?>
                    <div class="auth-alert error" style="margin-bottom: 1.5rem;">
                        <i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errorMsg) ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($message)): ?>
                    <form action="forgot_password.php" method="POST" class="auth-form">
                        <?= csrf_token_input() ?>

                        <div class="form-group">
                            <label for="emailInput">Registered Email Address *</label>
                            <input type="email" name="email" id="emailInput" class="form-control" placeholder="client@feme.com" required value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
                        </div>

                        <button type="submit" class="btn-gold auth-submit-btn">SEND RESET LINK</button>
                    </form>
                <?php endif; ?>

                <div class="auth-footer" style="margin-top: 1.5rem;">
                    <p>Remembered your password? <a href="login.php">Back to Sign In</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
