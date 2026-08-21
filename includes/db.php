<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Database Connection Configuration (PDO) & Dynamic Environment Setup
 */

// Load App Configurations
require_once __DIR__ . '/config.php';

// Environment Detection (Production vs Local Development)
$isProduction = (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'feme.eams.live');

// Dynamic Base URL Setup
if (!defined('BASE_URL')) {
    if ($isProduction) {
        define('BASE_URL', 'https://feme.eams.live/');
    } else {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        define('BASE_URL', $protocol . '://' . $host . '/feme/');
    }
}

// Dynamic Database Credentials
if ($isProduction) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'eamslive_feme');
    define('DB_USER', 'eamslive_feme');
    define('DB_PASS', 'feme123@');
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'feme_store');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Auto-schema check & migration for Couriers & Order Courier Tagging
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `couriers` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `name` VARCHAR(255) NOT NULL,
              `code` VARCHAR(50) NOT NULL UNIQUE,
              `contact_person` VARCHAR(255) DEFAULT NULL,
              `phone` VARCHAR(50) DEFAULT NULL,
              `email` VARCHAR(255) DEFAULT NULL,
              `tracking_url_template` VARCHAR(500) DEFAULT NULL,
              `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $cols = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'courier_id'")->fetch();
        if (!$cols) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `courier_id` INT DEFAULT NULL AFTER `payment_status`");
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `tracking_number` VARCHAR(255) DEFAULT NULL AFTER `courier_id`");
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `tracking_url` VARCHAR(500) DEFAULT NULL AFTER `tracking_number`");
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `shipped_at` DATETIME DEFAULT NULL AFTER `tracking_url`");
            try {
                $pdo->exec("ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_courier` FOREIGN KEY (`courier_id`) REFERENCES `couriers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
            } catch (Exception $ex) {}
        }

        $courierCount = (int)$pdo->query("SELECT COUNT(*) FROM `couriers`")->fetchColumn();
        if ($courierCount === 0) {
            $pdo->exec("
                INSERT INTO `couriers` (`id`, `name`, `code`, `contact_person`, `phone`, `email`, `tracking_url_template`, `status`) VALUES
                (1, 'Blue Dart Express', 'BLUEDART', 'Rajesh Sharma', '+91 98200 11223', 'dispatch@bluedart.com', 'https://www.bluedart.com/tracking?awb={tracking_number}', 'active'),
                (2, 'Delhivery Courier', 'DELHIVERY', 'Anil Verma', '+91 98111 22334', 'support@delhivery.com', 'https://www.delhivery.com/track/package/{tracking_number}', 'active'),
                (3, 'DTDC Express', 'DTDC', 'Vikram Patel', '+91 98333 44556', 'corporate@dtdc.com', 'https://www.dtdc.in/tracking/tracking_results.asp?Tdirect={tracking_number}', 'active'),
                (4, 'DHL Express International', 'DHL', 'Sarah Jenkins', '+91 98444 55667', 'vip@dhl.com', 'https://www.dhl.com/en/express/tracking.html?AWB={tracking_number}', 'active'),
                (5, 'FedEx Logistics', 'FEDEX', 'Karan Malhotra', '+91 98555 66778', 'support@fedex.com', 'https://www.fedex.com/fedextrack/?trknbr={tracking_number}', 'active')
            ");
        }

        // Auto-schema check & migration for Store Settings
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `settings` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `setting_key` VARCHAR(100) NOT NULL UNIQUE,
              `setting_value` TEXT DEFAULT NULL,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
            ('store_name', 'FeMe – Ultimate Luxury Closet'),
            ('store_tagline', 'Haute Couture & Heritage Ensembles'),
            ('store_phone', '+91 9134366366'),
            ('store_address', 'Shankar Plaza, 1st Floor, Opp - Idgha High School, Murgasol, Asansol-713303, Paschim Burdwan, West Bengal, India'),
            ('store_gstin', '19AUMPB3683N1Z0'),
            ('store_email', 'concierge@feme.com'),
            ('store_website', 'www.feme.com')
            ON DUPLICATE KEY UPDATE `setting_value` = IF(`setting_key` IN ('store_phone', 'store_address', 'store_gstin'), VALUES(`setting_value`), `setting_value`);
        ");

        $colsGst = $pdo->query("SHOW COLUMNS FROM `products` LIKE 'gst_percent'")->fetch();
        if (!$colsGst) {
            $pdo->exec("ALTER TABLE `products` ADD COLUMN `gst_percent` DECIMAL(5, 2) NOT NULL DEFAULT 5.00 AFTER `stock_qty`");
        }
    } catch (Exception $e) {
        // Migration completed or table structure up to date
    }
} catch (PDOException $e) {
    // If database doesn't exist (error 1049), attempt auto-creation and schema initialization
    if ($e->getCode() == 1049) {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, $options);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `" . DB_NAME . "`");
            
            $sqlFile = __DIR__ . '/../database.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $pdo->exec($sql);
            }
        } catch (PDOException $ex) {
            die("Database Connection & Auto-creation Failed: " . $ex->getMessage());
        }
    } else {
        die("Database Connection Failed: " . $e->getMessage());
    }
}
