<?php
require_once __DIR__ . '/../includes/db.php';

try {
    // Insert seed orders if order table is empty
    $count = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    if ($count === 0) {
        $pdo->exec("
            INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `shipping_address`, `payment_method`, `razorpay_order_id`, `razorpay_payment_id`, `payment_status`, `courier_id`, `tracking_number`, `tracking_url`, `shipped_at`) VALUES
            (100001, 2, 29999.00, 'shipped', '42 Regency Villa, Road No 36, Jubilee Hills, Hyderabad, Telangana - 500033\nPhone: +91 91234 56789', 'razorpay', 'order_N123456789', 'pay_N987654321', 'paid', 1, 'BD-78901234', 'https://www.bluedart.com/tracking?awb=BD-78901234', '2026-08-15 14:30:00'),
            (100002, 2, 21999.00, 'pending', '42 Regency Villa, Road No 36, Jubilee Hills, Hyderabad, Telangana - 500033\nPhone: +91 91234 56789', 'cod', NULL, NULL, 'pending', NULL, NULL, NULL, NULL)
            ON DUPLICATE KEY UPDATE `status` = VALUES(`status`), `courier_id` = VALUES(`courier_id`);
        ");

        $pdo->exec("
            INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
            (1, 100001, 1, 1, 29999.00),
            (2, 100002, 3, 1, 21999.00)
            ON DUPLICATE KEY UPDATE `price` = VALUES(`price`);
        ");
        echo "Sample orders seeded successfully!\n";
    } else {
        echo "Orders table already has data.\n";
    }
} catch (Exception $e) {
    echo "Seed Error: " . $e->getMessage() . "\n";
}
