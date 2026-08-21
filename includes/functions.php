<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Core Helper & Security Functions
 */

// 1. Persistent Session Security Settings (1 Year Lifetime until explicit logout)
$sessionLifetime = 365 * 24 * 60 * 60; // 1 Year (31,536,000 seconds)

ini_set('session.gc_maxlifetime', $sessionLifetime);
ini_set('session.cookie_lifetime', $sessionLifetime);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    if (session_id() === '') {
        session_set_cookie_params([
            'lifetime' => $sessionLifetime,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    session_start();
}

// Enable Output Buffering to prevent headers already sent issues
if (ob_get_level() === 0) {
    ob_start();
}

// Include CSRF Token Helper
require_once __DIR__ . '/csrf.php';

// 2. Production Error Logging & Display Configurations
ini_set('display_errors', 0);
ini_set('log_errors', 1);
$logDir = __DIR__ . '/../logs/';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . 'app_errors.log');

/**
 * Send Central Security Headers
 */
function send_security_headers() {
    if (!headers_sent()) {
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://checkout.razorpay.com https://api.razorpay.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:; img-src 'self' data: https: blob:; connect-src 'self' https://api.razorpay.com https://lumberjack.razorpay.com https://lumberjack-cx.razorpay.com https://*.razorpay.com; frame-src 'self' https://api.razorpay.com https://checkout.razorpay.com https://*.razorpay.com https://www.google.com;");
    }
}

send_security_headers();

/**
 * Sanitize output string
 */
function sanitize($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency in INR
 */
function format_price($price) {
    return '₹' . number_format((float)$price, 2);
}

/**
 * Redirect to a specific URL
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if logged in user is admin
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Set session flash message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'info'
        'message' => $message
    ];
}

/**
 * Get and clear session flash message
 */
function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Get user IP address
 */
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Detect Device Type from User-Agent
 */
function detect_device_type($userAgent) {
    if (empty($userAgent)) return 'Desktop';
    if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $userAgent)) {
        return 'Tablet';
    }
    if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile|iphone|ipod)/i', $userAgent)) {
        return 'Mobile';
    }
    return 'Desktop';
}

/**
 * Record Customer Login Session in Database
 */
function record_customer_login($pdo, $userId, $method = 'password') {
    if (!$pdo || empty($userId)) return;

    try {
        $ip = get_client_ip();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Browser';
        $deviceType = detect_device_type($userAgent);

        // 1. Insert into customer_logins table
        $stmt = $pdo->prepare("
            INSERT INTO customer_logins (user_id, ip_address, user_agent, device_type, login_method)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $ip, $userAgent, $deviceType, $method]);

        // 2. Update users summary columns
        $uStmt = $pdo->prepare("
            UPDATE users 
            SET last_login_at = NOW(), 
                last_login_ip = ?, 
                login_count = COALESCE(login_count, 0) + 1 
            WHERE id = ?
        ");
        $uStmt->execute([$ip, $userId]);
    } catch (Exception $e) {
        // Silently skip if table is not yet created
    }
}

/**
 * Format timestamp into human-readable relative time string (e.g., '5 mins ago')
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = (int)floor($diff->d / 7);
    $days = (int)($diff->d - ($weeks * 7));

    $units = [
        'year'  => $diff->y,
        'month' => $diff->m,
        'week'  => $weeks,
        'day'   => $days,
        'hour'  => $diff->h,
        'min'   => $diff->i,
        'sec'   => $diff->s,
    ];

    $string = [];
    foreach ($units as $unit => $count) {
        if ($count > 0) {
            $string[] = $count . ' ' . $unit . ($count > 1 ? 's' : '');
        }
    }

    if (!$full) {
        $string = array_slice($string, 0, 1);
    }
    return !empty($string) ? implode(', ', $string) . ' ago' : 'just now';
}

/**
 * Rate Limiting: Check login attempts for email and IP (Max 5 attempts per 15 minutes)
 */
function check_login_rate_limit($pdo, $email) {
    $ip = get_client_ip();
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM login_attempts 
            WHERE (email = ? OR ip_address = ?) 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$email, $ip]);
        $attempts = (int)$stmt->fetchColumn();

        if ($attempts >= 5) {
            return true; // Locked out
        }
    } catch (PDOException $e) {
        // Fallback
    }
    return false;
}

/**
 * Rate Limiting: Record failed login attempt
 */
function record_failed_login($pdo, $email) {
    $ip = get_client_ip();
    try {
        $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
        $stmt->execute([$email, $ip]);
    } catch (PDOException $e) {
        // Ignore log failure
    }
}

/**
 * Rate Limiting: Clear failed attempts upon successful login
 */
function clear_login_attempts($pdo, $email) {
    $ip = get_client_ip();
    try {
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE email = ? OR ip_address = ?");
        $stmt->execute([$email, $ip]);
    } catch (PDOException $e) {
        // Ignore
    }
}

/**
 * Admin Audit Logger
 */
function log_admin_activity($pdo, $action, $targetTable = null, $targetId = null) {
    if (!is_admin()) return;
    $adminId = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, target_table, target_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$adminId, $action, $targetTable, $targetId]);
    } catch (PDOException $e) {
        // Ignore
    }
}

/**
 * Validate Image File Upload (MIME, Extension, and Size)
 */
function validate_image_file($tmpName, $originalName, $maxBytes = 5242880) { // 5MB limit
    if (!file_exists($tmpName) || filesize($tmpName) > $maxBytes) {
        return false;
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        return false;
    }

    $mime = function_exists('mime_content_type') ? mime_content_type($tmpName) : false;
    if (!$mime) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpName);
    }

    if (!in_array($mime, $allowedMimes)) {
        return false;
    }

    return true;
}

/**
 * Get total items count in user's cart (session or database)
 */
function get_cart_count($pdo = null) {
    if (is_logged_in() && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT SUM(quantity) AS total FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $row = $stmt->fetch();
            return $row && $row['total'] ? (int)$row['total'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    // Session fallback for guest users
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $total = 0;
        foreach ($_SESSION['cart'] as $qty) {
            $total += (int)$qty;
        }
        return $total;
    }
    
    return 0;
}

/**
 * Merge session cart items into database cart when user logs in
 */
function merge_session_cart_to_db($pdo, $userId) {
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        try {
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $checkStmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
                $checkStmt->execute([$userId, $productId]);
                $existing = $checkStmt->fetch();

                if ($existing) {
                    $newQty = $existing['quantity'] + $quantity;
                    $updateStmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    $updateStmt->execute([$newQty, $existing['id']]);
                } else {
                    $insertStmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                    $insertStmt->execute([$userId, $productId, $quantity]);
                }
            }
            unset($_SESSION['cart']);
        } catch (PDOException $e) {
            // Ignore error
        }
    }
}

/**
 * Get full cart items list with product details
 */
function get_cart_items($pdo) {
    $items = [];
    if (is_logged_in()) {
        try {
            $stmt = $pdo->prepare("
                SELECT c.id AS cart_id, c.quantity, p.*, cat.name AS category_name,
                       (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) AS primary_img
                FROM cart c
                JOIN products p ON c.product_id = p.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                WHERE c.user_id = ?
                ORDER BY c.id DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $items = $stmt->fetchAll();
        } catch (PDOException $e) {
            $items = [];
        }
    } else {
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && !empty($_SESSION['cart'])) {
            $productIds = array_keys($_SESSION['cart']);
            if (!empty($productIds)) {
                $inQuery = implode(',', array_fill(0, count($productIds), '?'));
                try {
                    $stmt = $pdo->prepare("
                        SELECT p.*, cat.name AS category_name,
                               (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) AS primary_img
                        FROM products p
                        LEFT JOIN categories cat ON p.category_id = cat.id
                        WHERE p.id IN ({$inQuery})
                    ");
                    $stmt->execute($productIds);
                    $products = $stmt->fetchAll();

                    foreach ($products as $p) {
                        $p['quantity'] = $_SESSION['cart'][$p['id']] ?? 1;
                        $p['cart_id'] = $p['id'];
                        $items[] = $p;
                    }
                } catch (PDOException $e) {
                    $items = [];
                }
            }
        }
    }
    return $items;
}

/**
 * Fetch all categories
 */
function get_all_categories($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Fetch active hero banners
 */
function get_active_banners($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Fetch active discount promotion
 */
function get_active_discount($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM discounts WHERE is_active = 1 AND NOW() BETWEEN start_date AND end_date ORDER BY id DESC LIMIT 1");
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Convert Currency Number to Words (Indian Format)
 */
function number_to_words_indian($amount) {
    $number = floor($amount);
    $fraction = round(($amount - $number) * 100);
    
    $no = $number;
    $point = $fraction;
    $hundred = null;
    $digits_1 = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        '0' => '', '1' => 'One', '2' => 'Two',
        '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six',
        '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',
        '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve',
        '13' => 'Thirteen', '14' => 'Fourteen',
        '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen',
        '18' => 'Eighteen', '19' =>'Nineteen', '20' => 'Twenty',
        '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty',
        '60' => 'Sixty', '70' => 'Seventy', '80' => 'Eighty',
        '90' => 'Ninety'
    );
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    while ($i < $digits_1) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : '';
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number] . " " . $digits[$counter] . $plural . " " . $hundred
                : $words[floor($number / 10) * 10] . " " . $words[$number % 10] . " " . $digits[$counter] . $plural . " " . $hundred;
        } else {
            $str[] = null;
        }
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    $result = trim($result);
    if (empty($result)) {
        $result = "Zero";
    }
    
    $points = ($point) ? " and " . $words[floor($point / 10) * 10] . " " . $words[$point % 10] . " Paise" : '';
    return $result . " Rupees" . $points . " Only";
}

/**
 * Fetch Store Setting from Database
 */
function get_setting($pdo, $key, $default = '') {
    static $settingsCache = null;
    if ($settingsCache === null) {
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settingsCache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            $settingsCache = [];
        }
    }
    return isset($settingsCache[$key]) && $settingsCache[$key] !== '' ? $settingsCache[$key] : $default;
}

/**
 * Update or Save Store Setting in Database
 */
function update_setting($pdo, $key, $value) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        return $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Set Persistent Login Token for User (Valid for 1 Year)
 */
function set_persistent_login_token($pdo, $userId) {
    try {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        $stmt->execute([$tokenHash, $userId]);

        $cookieExpire = time() + (365 * 24 * 60 * 60); // 1 Year
        setcookie('feme_remember_token', $rawToken, [
            'expires'  => $cookieExpire,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } catch (Exception $e) {
        // Silently skip if table column is not migrated
    }
}

/**
 * Clear Persistent Login Token on Logout
 */
function clear_persistent_login_token($pdo, $userId = null) {
    try {
        if ($userId && $pdo) {
            $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
            $stmt->execute([$userId]);
        }
        setcookie('feme_remember_token', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } catch (Exception $e) {
        // Ignore
    }
}

/**
 * Validate Persistent Cookie and Auto-Login
 */
function auto_login_from_cookie($pdo) {
    if (is_logged_in() || empty($_COOKIE['feme_remember_token']) || !$pdo) {
        return;
    }

    try {
        $rawToken = $_COOKIE['feme_remember_token'];
        $tokenHash = hash('sha256', $rawToken);

        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? LIMIT 1");
        $stmt->execute([$tokenHash]);
        $persistedUser = $stmt->fetch();

        if ($persistedUser) {
            $_SESSION['user_id'] = $persistedUser['id'];
            $_SESSION['user_name'] = $persistedUser['name'];
            $_SESSION['user_email'] = $persistedUser['email'];
            $_SESSION['user_role'] = $persistedUser['role'];

            // Record persistent auto-login in database
            record_customer_login($pdo, $persistedUser['id'], 'remember_cookie');

            merge_session_cart_to_db($pdo, $persistedUser['id']);
        } else {
            // Invalid token in cookie — clear it
            setcookie('feme_remember_token', '', time() - 3600, '/');
        }
    } catch (Exception $e) {
        // Ignore if remember_token column is not present
    }
}

// Automatically check persistent login on every request if $pdo is available
if (isset($pdo) && $pdo instanceof PDO) {
    auto_login_from_cookie($pdo);
}



