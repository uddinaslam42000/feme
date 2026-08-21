-- ============================================================
-- Complete Database Dump for FeMe – Ultimate Luxury Closet
-- Applicable for: feme_store (Local) / eamslive_feme (Production)
-- Compatible with phpMyAdmin, MySQL CLI, and cPanel Import
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  `remember_token` VARCHAR(255) DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `login_count` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_remember_token` (`remember_token`),
  INDEX `idx_last_login` (`last_login_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Safe column migrations for existing users table
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `remember_token` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `last_login_at` DATETIME DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `last_login_ip` VARCHAR(45) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `login_count` INT NOT NULL DEFAULT 0;

-- 2. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `image` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Products Table
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `category_id` INT NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `discount_price` DECIMAL(10, 2) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `fabric` VARCHAR(255) DEFAULT NULL,
  `stock_qty` INT NOT NULL DEFAULT 0,
  `gst_percent` DECIMAL(5, 2) NOT NULL DEFAULT 5.00,
  `images` JSON DEFAULT NULL,
  `is_new_arrival` TINYINT(1) NOT NULL DEFAULT 0,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_category_id` (`category_id`),
  INDEX `idx_is_featured` (`is_featured`),
  INDEX `idx_is_new_arrival` (`is_new_arrival`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Product Images Table
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Banners Table (for homepage hero slider)
CREATE TABLE IF NOT EXISTS `banners` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `subtitle` VARCHAR(255) DEFAULT NULL,
  `button_text` VARCHAR(100) DEFAULT NULL,
  `button_link` VARCHAR(255) DEFAULT NULL,
  `image` VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Discounts Table (for countdown/offer section)
CREATE TABLE IF NOT EXISTS `discounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `discount_percent` INT NOT NULL,
  `start_date` DATETIME DEFAULT NULL,
  `end_date` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Couriers Table
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

-- 8. Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
  `shipping_address` TEXT NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cod',
  `razorpay_order_id` VARCHAR(255) DEFAULT NULL,
  `razorpay_payment_id` VARCHAR(255) DEFAULT NULL,
  `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `courier_id` INT DEFAULT NULL,
  `tracking_number` VARCHAR(255) DEFAULT NULL,
  `tracking_url` VARCHAR(500) DEFAULT NULL,
  `shipped_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_courier` FOREIGN KEY (`courier_id`) REFERENCES `couriers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX `idx_orders_user_id` (`user_id`),
  INDEX `idx_orders_status` (`status`),
  INDEX `idx_orders_courier_id` (`courier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Safe column migrations for existing orders tables (ignored if column already exists)
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `razorpay_order_id` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `razorpay_payment_id` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `payment_status` ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending';
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `courier_id` INT DEFAULT NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `tracking_number` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `tracking_url` VARCHAR(500) DEFAULT NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `shipped_at` DATETIME DEFAULT NULL;

-- 8. Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(10, 2) NOT NULL,
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_order_items_order_id` (`order_id`),
  INDEX `idx_order_items_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Cart Table
CREATE TABLE IF NOT EXISTS `cart` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_cart_user_id` (`user_id`),
  INDEX `idx_cart_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Newsletter Subscribers Table
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `subscribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Contact Messages Table
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Login Attempts Table (Rate Limiting)
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email_ip` (`email`, `ip_address`),
  INDEX `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Admin Activity Logs Table
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `target_table` VARCHAR(100) DEFAULT NULL,
  `target_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Password Resets Table
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_token` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Store Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Customer Logins Audit Trail Table
CREATE TABLE IF NOT EXISTS `customer_logins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `device_type` VARCHAR(50) DEFAULT 'Desktop',
  `login_method` VARCHAR(50) DEFAULT 'password',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_customer_logins_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_customer_logins_user_id` (`user_id`),
  INDEX `idx_customer_logins_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Seed Users (Passwords: admin123 and customer123)
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `phone`, `address`, `role`) VALUES
(1, 'FeMe Admin', 'admin@feme.com', '$2y$10$nH9GpRJmAwHHm0YAqcAQyejuIeqU/hPgGGulu11owvonOfna6wNra', '+91 98765 43210', 'Luxury Tower, MG Road, Mumbai', 'admin'),
(2, 'Eleanor Vance', 'customer@feme.com', '$2y$10$3ruQZqMT1XGsUSh710qpk.UvDL1aO2RxyWxnwzABhDnsR.MmEFtT6', '+91 91234 56789', '42 Regency Villa, Jubilee Hills, Hyderabad', 'customer')
ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `name` = VALUES(`name`);

-- Seed Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `image`, `description`) VALUES
(1, 'Sarees', 'sarees', 'assets/images/cat_sarees.jpg', 'Handcrafted silk, Kanjeevarams, and sheer organza sarees woven with distinction.'),
(2, 'Salwar Suits', 'salwar-suits', 'assets/images/cat_suits.jpg', 'Royal tailored silhouettes, Anarkalis, and handcrafted zari ensembles.'),
(3, 'Designer Wear', 'designer-wear', 'assets/images/cat_designer.jpg', 'Exclusive haute couture lehengas and opulent evening gagra sets.'),
(4, 'Limited Edition', 'limited-edition', 'assets/images/cat_limited.jpg', 'Rare artisanal masterworks produced in strictly limited runs.')
ON DUPLICATE KEY UPDATE `image` = VALUES(`image`), `description` = VALUES(`description`);

-- Seed Sample Products (6 products)
INSERT INTO `products` (`id`, `name`, `slug`, `category_id`, `price`, `discount_price`, `description`, `fabric`, `stock_qty`, `images`, `is_new_arrival`, `is_featured`) VALUES
(1, 'Kanjeevaram Royal Zari Silk Saree', 'kanjeevaram-royal-zari-silk-saree', 1, 34999.00, 29999.00, 'Hand-woven Mulberry Kanjeevaram silk saree featuring intricate real gold zari borders and a contrasting royal crimson pallu.', 'Pure Mulberry Silk & Pure Zari', 15, '["uploads/products/saree1_1.jpg", "uploads/products/saree1_2.jpg"]', 1, 1),
(2, 'Organza Embellished Floral Saree', 'organza-embellished-floral-saree', 1, 18500.00, 15999.00, 'Delicate powder pastel organza saree adorned with delicate pearl threadwork and intricate zardozi hand borders.', 'Sheer Organza Silk', 10, '["uploads/products/saree2_1.jpg"]', 1, 0),
(3, 'Emerald Velvet Embroidered Anarkali', 'emerald-velvet-embroidered-anarkali', 2, 24999.00, 21999.00, 'Floor-length deep emerald green velvet Anarkali set featuring majestic gold tilla threadwork and organza dupatta.', 'Micro Velvet & Organza', 8, '["uploads/products/suit1_1.jpg"]', 0, 1),
(4, 'Ivory Chanderi Silk Gotapatti Suit', 'ivory-chanderi-silk-gotapatti-suit', 2, 16999.00, NULL, 'Timeless ivory handloom Chanderi suit adorned with authentic Rajasthani Gotapatti handwork and silk trousers.', 'Handloom Chanderi Silk', 12, '["uploads/products/suit2_1.jpg"]', 1, 0),
(5, 'Royal Crimson Bridal Raw Silk Lehenga', 'royal-crimson-bridal-raw-silk-lehenga', 3, 89999.00, 79999.00, 'Haute couture bridal lehenga in rich crimson raw silk, fully encrusted with dabka, metallic sequins, and semi-precious stone work.', 'Heritage Raw Silk', 5, '["uploads/products/designer1_1.jpg"]', 0, 1),
(6, 'Artisan Banarasi Tissue Zari Saree', 'artisan-banarasi-tissue-zari-saree', 4, 125000.00, 112000.00, 'Limited collector piece — Antique gold Banarasi tissue silk saree handcrafted over 120 days by master weavers.', 'Antique Banarasi Tissue Zari', 3, '["uploads/products/limited1_1.jpg"]', 1, 1)
ON DUPLICATE KEY UPDATE `images` = VALUES(`images`), `price` = VALUES(`price`);

-- Seed Product Images
INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `sort_order`) VALUES
(1, 1, 'uploads/products/saree1_1.jpg', 1),
(2, 1, 'uploads/products/saree1_2.jpg', 2),
(3, 2, 'uploads/products/saree2_1.jpg', 1),
(4, 3, 'uploads/products/suit1_1.jpg', 1),
(5, 4, 'uploads/products/suit2_1.jpg', 1),
(6, 5, 'uploads/products/designer1_1.jpg', 1),
(7, 6, 'uploads/products/limited1_1.jpg', 1)
ON DUPLICATE KEY UPDATE `image_path` = VALUES(`image_path`);

-- Seed Hero Banners
INSERT INTO `banners` (`id`, `title`, `subtitle`, `button_text`, `button_link`, `image`, `sort_order`, `is_active`) VALUES
(1, 'Elegance Draped in Distinction', 'Discover the Royal Festive Collection 2026', 'Explore Collection', 'category.php', 'uploads/banners/hero_banner_1.jpg', 1, 1),
(2, 'Heritage Masterpieces', 'Strictly Limited Artisan Creations', 'View Limited Edition', 'category.php?slug=limited-edition', 'uploads/banners/hero_banner_2.jpg', 2, 1)
ON DUPLICATE KEY UPDATE `image` = VALUES(`image`);

-- Seed Discounts
INSERT INTO `discounts` (`id`, `title`, `description`, `discount_percent`, `start_date`, `end_date`, `is_active`) VALUES
(1, 'Royal Festive Offer', 'Enjoy up to 15% luxury discount on handpicked Silk Sarees & Designer Outfits.', 15, '2026-08-01 00:00:00', '2026-10-31 23:59:59', 1)
ON DUPLICATE KEY UPDATE `discount_percent` = VALUES(`discount_percent`);

-- Seed Couriers
INSERT INTO `couriers` (`id`, `name`, `code`, `contact_person`, `phone`, `email`, `tracking_url_template`, `status`) VALUES
(1, 'Blue Dart Express', 'BLUEDART', 'Rajesh Sharma', '+91 98200 11223', 'dispatch@bluedart.com', 'https://www.bluedart.com/tracking?awb={tracking_number}', 'active'),
(2, 'Delhivery Courier', 'DELHIVERY', 'Anil Verma', '+91 98111 22334', 'support@delhivery.com', 'https://www.delhivery.com/track/package/{tracking_number}', 'active'),
(3, 'DTDC Express', 'DTDC', 'Vikram Patel', '+91 98333 44556', 'corporate@dtdc.com', 'https://www.dtdc.in/tracking/tracking_results.asp?Tdirect={tracking_number}', 'active'),
(4, 'DHL Express International', 'DHL', 'Sarah Jenkins', '+91 98444 55667', 'vip@dhl.com', 'https://www.dhl.com/en/express/tracking.html?AWB={tracking_number}', 'active'),
(5, 'FedEx Logistics', 'FEDEX', 'Karan Malhotra', '+91 98555 66778', 'support@fedex.com', 'https://www.fedex.com/fedextrack/?trknbr={tracking_number}', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `tracking_url_template` = VALUES(`tracking_url_template`);

-- Seed Sample Orders (without courier_id — applied separately below after couriers are seeded)
INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `shipping_address`, `payment_method`, `razorpay_order_id`, `razorpay_payment_id`, `payment_status`, `tracking_number`, `tracking_url`, `shipped_at`) VALUES
(100001, 2, 29999.00, 'shipped', '42 Regency Villa, Road No 36, Jubilee Hills, Hyderabad, Telangana - 500033\nPhone: +91 91234 56789', 'razorpay', 'order_N123456789', 'pay_N987654321', 'paid', 'BD-78901234', 'https://www.bluedart.com/tracking?awb=BD-78901234', '2026-08-15 14:30:00'),
(100002, 2, 21999.00, 'pending', '42 Regency Villa, Road No 36, Jubilee Hills, Hyderabad, Telangana - 500033\nPhone: +91 91234 56789', 'cod', NULL, NULL, 'pending', NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`), `payment_status` = VALUES(`payment_status`);

-- Assign courier to sample shipped order (after couriers table is seeded)
UPDATE `orders` SET `courier_id` = 1 WHERE `id` = 100001 AND `courier_id` IS NULL;

-- Seed Sample Order Items
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 100001, 1, 1, 29999.00),
(2, 100002, 3, 1, 21999.00)
ON DUPLICATE KEY UPDATE `price` = VALUES(`price`);

-- Seed Store Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('store_name', 'FeMe – Ultimate Luxury Closet'),
('store_tagline', 'Haute Couture & Heritage Ensembles'),
('store_phone', '+91 9134366366'),
('store_address', 'Shankar Plaza, 1st Floor, Opp - Idgha High School, Murgasol, Asansol-713303, Paschim Burdwan, West Bengal, India'),
('store_gstin', '19AUMPB3683N1Z0'),
('store_email', 'concierge@feme.com'),
('store_website', 'www.feme.com')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- Seed Sample Customer Logins
INSERT INTO `customer_logins` (`id`, `user_id`, `ip_address`, `user_agent`, `device_type`, `login_method`, `created_at`) VALUES
(1, 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36', 'Desktop', 'password', '2026-08-19 21:30:00')
ON DUPLICATE KEY UPDATE `ip_address` = VALUES(`ip_address`);

SET FOREIGN_KEY_CHECKS = 1;
