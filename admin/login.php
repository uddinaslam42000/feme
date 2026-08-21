<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Admin Portal Login
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in() && is_admin()) {
    header("Location: " . BASE_URL . "admin/index.php");
    exit;
}

$errorMsg = '';

if (isset($_GET['error']) && $_GET['error'] === 'timeout') {
    $errorMsg = 'Your admin session timed out after 30 minutes of inactivity. Please sign in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verify_csrf_token()) {
        $errorMsg = 'Invalid session token. Please refresh and try again.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = trim($_POST['password'] ?? '');

        if (!$email || empty($password)) {
            $errorMsg = 'Please enter a valid administrator email and password.';
        } else {
            // 2. Check Rate Limit Lockout (5 failed attempts in 15 mins)
            if (check_login_rate_limit($pdo, $email)) {
                $errorMsg = 'Too many failed login attempts. Admin console locked for 15 minutes for your security.';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password_hash'])) {
                        // Clear failed login attempts
                        clear_login_attempts($pdo, $email);

                        // Regenerate Session ID
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['admin_last_activity'] = time();

                        header("Location: " . BASE_URL . "admin/index.php");
                        exit;
                    } else {
                        // Record Failed Attempt
                        record_failed_login($pdo, $email);
                        $errorMsg = 'Invalid admin credentials or unauthorized account.';
                    }
                } catch (PDOException $e) {
                    $errorMsg = 'Database authentication error occurred.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login – FeMe Luxury Closet</title>
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Custom Styles -->
    <style>
        :root {
            --bg-dark: #1A1A1A;
            --bg-cream: #F7F3EC;
            --gold-primary: #C9A24B;
            --gold-dark: #B8923D;
            --font-serif: 'Playfair Display', Georgia, serif;
            --font-sans: 'Poppins', sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--font-sans);
            background-color: var(--bg-dark);
            color: #FFF;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
        }

        .admin-login-card {
            background-color: #242424;
            border: 1px solid var(--gold-primary);
            border-radius: 12px;
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            text-align: center;
        }

        .logo-text {
            font-family: var(--font-serif);
            font-size: 2.2rem;
            font-weight: 700;
            color: #FFF;
            letter-spacing: 2px;
        }

        .logo-text span { color: var(--gold-primary); }

        .logo-tagline {
            display: block;
            font-size: 0.65rem;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--gold-primary);
            margin-top: 4px;
            margin-bottom: 2rem;
        }

        .form-title {
            font-family: var(--font-serif);
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            color: #FFF;
        }

        .alert-error {
            background: rgba(255, 77, 79, 0.15);
            border: 1px solid #ff4d4f;
            color: #ff7875;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--gold-primary);
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-control {
            width: 100%;
            padding: 0.85rem 1rem;
            background: #1A1A1A;
            border: 1px solid rgba(201, 162, 75, 0.3);
            border-radius: 6px;
            color: #FFF;
            font-family: var(--font-sans);
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 3px rgba(201, 162, 75, 0.2);
        }

        .btn-gold {
            width: 100%;
            padding: 0.95rem;
            background: linear-gradient(135deg, #D4AF37 0%, #C9A24B 50%, #B8923D 100%);
            color: #1A1A1A;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            font-size: 0.88rem;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .btn-gold:hover {
            background: var(--gold-dark);
            color: #FFF;
        }
    </style>
</head>
<body>

    <div class="admin-login-card">
        <div class="brand-logo">
            <span class="logo-text">Fe<span>Me</span></span>
            <span class="logo-tagline">Management Console</span>
        </div>

        <h2 class="form-title">Administrator Access</h2>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errorMsg) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <?= csrf_token_input() ?>
            <div class="form-group">
                <label for="emailInput">Admin Email</label>
                <input type="email" name="email" id="emailInput" class="form-control" placeholder="admin@feme.com" required value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="passInput">Password</label>
                <input type="password" name="password" id="passInput" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-gold">LOGIN TO CONSOLE</button>
        </form>
    </div>

</body>
</html>
