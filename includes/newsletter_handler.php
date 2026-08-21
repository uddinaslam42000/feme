<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Newsletter Subscription Handler
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

if (!verify_csrf_token()) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid session token. Please refresh and try again.']);
    exit;
}

$email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) : false;

if (!$email) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
    exit;
}

try {
    // Check if already subscribed
    $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'This email is already subscribed to our newsletter.']);
        exit;
    }

    // Insert subscriber
    $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
    $stmt->execute([$email]);

    // Send Newsletter Confirmation Email (Trigger 7)
    send_newsletter_confirmation_email($email);

    echo json_encode([
        'status' => 'success',
        'message' => 'Thank you for subscribing! You are now on our royal guest list.'
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Unable to subscribe right now. Please try again later.']);
}
