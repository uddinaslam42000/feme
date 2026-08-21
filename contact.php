<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Contact & Appointments Page
 */
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/mailer.php';

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errorMsg = 'Invalid session token. Please refresh and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $subject = trim($_POST['subject'] ?? 'General Inquiry');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || !$email || empty($message)) {
            $errorMsg = 'Please complete all required fields with a valid email address.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO contact_messages (name, email, subject, message) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$name, $email, $subject, $message]);

                // Send Contact Form Emails (Trigger 6)
                send_contact_form_emails($name, $email, $subject, $message);

                $successMsg = 'Thank you for reaching out. Our royal concierge team will respond within 24 hours.';
            } catch (PDOException $e) {
                $errorMsg = 'Unable to send message right now. Please try again later.';
            }
        }
    }
}
?>

<!-- Banner Strip -->
<section class="category-banner-strip" style="background-image: linear-gradient(180deg, rgba(26,26,26,0.6) 0%, rgba(26,26,26,0.85) 100%), url('assets/images/cat_suits.jpg');">
    <div class="container">
        <div class="category-banner-content">
            <span class="category-eyebrow">CLIENT CARE & CONCIERGE</span>
            <h1 class="category-banner-title">Contact Us</h1>
            <p class="category-banner-desc">Schedule a private boutique appointment or reach our couture concierges.</p>
        </div>
    </div>
</section>

<!-- Contact Layout -->
<section class="section contact-section">
    <div class="container">
        <div class="contact-grid">
            <!-- Left: Contact Form -->
            <div class="contact-form-card">
                <h2 class="contact-card-title">Send a Private Message</h2>
                <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1.5rem;">
                    For bespoke fittings, bridal consultations, or order inquiries, please send us a note below.
                </p>

                <?php if ($successMsg): ?>
                    <div class="auth-alert success" style="margin-bottom: 1.5rem;">
                        <i class="fa-solid fa-circle-check"></i> <?= sanitize($successMsg) ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMsg): ?>
                    <div class="auth-alert error" style="margin-bottom: 1.5rem;">
                        <i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errorMsg) ?>
                    </div>
                <?php endif; ?>

                <form action="contact.php" method="POST">
                    <?= csrf_token_input() ?>
                    <div class="form-group">
                        <label for="cName">Your Full Name *</label>
                        <input type="text" name="name" id="cName" class="form-control" required placeholder="Eleanor Vance" value="<?= isset($_POST['name']) ? sanitize($_POST['name']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="cEmail">Your Email Address *</label>
                        <input type="email" name="email" id="cEmail" class="form-control" required placeholder="client@feme.com" value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="cSubject">Subject</label>
                        <input type="text" name="subject" id="cSubject" class="form-control" placeholder="Bespoke Fitting Appointment" value="<?= isset($_POST['subject']) ? sanitize($_POST['subject']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="cMessage">Message *</label>
                        <textarea name="message" id="cMessage" class="form-control" rows="5" required placeholder="Describe your request or appointment preferred date..."><?= isset($_POST['message']) ? sanitize($_POST['message']) : '' ?></textarea>
                    </div>

                    <button type="submit" class="btn-gold" style="width: 100%; margin-top: 0.5rem;">SEND MESSAGE</button>
                </form>
            </div>

            <!-- Right: Store Info & Map Placeholder -->
            <div class="contact-info-panel">
                <div class="info-card">
                    <h3 class="info-card-title">Flagship Boutique</h3>

                    <div class="info-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <div>
                            <strong>Boutique Address</strong>
                            <p><?= STORE_ADDRESS ?></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <i class="fa-solid fa-phone"></i>
                        <div>
                            <strong>Client Concierge Phone</strong>
                            <p><?= STORE_PHONE ?></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <i class="fa-solid fa-file-invoice"></i>
                        <div>
                            <strong>Official GSTIN</strong>
                            <p><code><?= STORE_GSTIN ?></code></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <i class="fa-solid fa-envelope"></i>
                        <div>
                            <strong>Electronic Mail</strong>
                            <p><?= STORE_EMAIL ?></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <i class="fa-regular fa-clock"></i>
                        <div>
                            <strong>Boutique Hours</strong>
                            <p>Monday – Saturday: 10:30 AM – 8:00 PM<br>Sunday: By Appointment Only</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
