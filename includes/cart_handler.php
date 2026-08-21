<?php
/**
 * FeMe – Ultimate Luxury Closet
 * AJAX Shopping Cart Handler
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$action = isset($_POST['action']) ? sanitize($_POST['action']) : 'add';
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

if ($productId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product selected.']);
    exit;
}

// Verify product exists and has stock
try {
    $stmt = $pdo->prepare("SELECT id, name, price, discount_price, stock_qty FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
        exit;
    }

    if ($product['stock_qty'] < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Sorry, this item is out of stock.']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
    exit;
}

// 1. Logged in User Cart Logic (Database cart)
if (is_logged_in()) {
    $userId = $_SESSION['user_id'];

    try {
        if ($action === 'add') {
            // Check if item already in cart
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
        } elseif ($action === 'update') {
            $updateStmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $updateStmt->execute([$quantity, $userId, $productId]);
        } elseif ($action === 'remove') {
            $deleteStmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $deleteStmt->execute([$userId, $productId]);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update database cart.']);
        exit;
    }
} else {
    // 2. Guest User Cart Logic (Session cart)
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if ($action === 'add') {
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
    } elseif ($action === 'update') {
        $_SESSION['cart'][$productId] = $quantity;
    } elseif ($action === 'remove') {
        unset($_SESSION['cart'][$productId]);
    }
}

// Calculate updated total cart count
$cartCount = get_cart_count($pdo);

echo json_encode([
    'status' => 'success',
    'message' => 'Item added to your luxury closet cart.',
    'cart_count' => $cartCount
]);
