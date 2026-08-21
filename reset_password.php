<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Reset Password Handler Page
 */
require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('index.php');
}

$rawToken = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$errorMsg = '';
$tokenValid = false;
$userId = 0;

if (!empty($rawToken)) {
    $tokenHash = hash('sha256', $rawToken);
    try {
        $stmt = $pdo->prepare("
            SELECT pr.*, u.email 
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token_hash = ? AND pr.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$tokenHash]);
        $resetRecord = $stmt->fetch();

        if ($resetRecord) {
            $tokenValid = true;
            $userId = $resetRecord['user_id'];
        } else {
            $errorMsg = 'This password reset link is invalid or has expired. Please request a new link.';
        }
    } catch (PDOException $e) {
        $errorMsg = 'A system error occurred. Please try again.';
    }
} else {
    $errorMsg = 'No reset token provided. Please request a new password reset link.';
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    if (!verify_csrf_token()) {
        $errorMsg = 'Invalid session token. Please refresh and try again.';
    } else {
        $newPassword = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if (empty($newPassword) || strlen($newPassword) < 8 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $errorMsg = 'Password must be at least 8 characters long and contain at least one letter and one number.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMsg = 'Password confirmation does not match.';
        } else {
            try {
                // Update User Password Hash
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $updateStmt->execute([$newHash, $userId]);

                // Delete used tokens for user
                $delStmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
                $delStmt->execute([$userId]);

                redirect('login.php?reset=success');

            } catch (PDOException $e) {
                $errorMsg = 'Failed to reset password. Please try again.';
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
                    <h2 class="auth-title">Set New Password</h2>
                    <p class="auth-subtitle">Create a new secure password for your account</p>
                </div>

                <?php if (!empty($errorMsg)): ?>
                    <div class="auth-alert error" style="margin-bottom: 1.5rem;">
                        <i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errorMsg) ?>
                    </div>
                <?php endif; ?>

                <?php if ($tokenValid): ?>
                    <form action="reset_password.php?token=<?= sanitize($rawToken) ?>" method="POST" class="auth-form">
                        <?= csrf_token_input() ?>
                        <input type="hidden" name="token" value="<?= sanitize($rawToken) ?>">

                        <div class="form-group">
                            <label for="passInput">New Password * (Min 8 chars, letter + number)</label>
                            <input type="password" name="password" id="passInput" class="form-control" placeholder="••••••••" minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" required>
                        </div>

                        <div class="form-group">
                            <label for="confirmPassInput">Confirm New Password *</label>
                            <input type="password" name="confirm_password" id="confirmPassInput" class="form-control" placeholder="••••••••" minlength="8" required>
                        </div>

                        <button type="submit" class="btn-gold auth-submit-btn">UPDATE PASSWORD</button>
                    </form>
                <?php else: ?>
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="forgot_password.php" class="btn-gold">REQUEST NEW RESET LINK</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
