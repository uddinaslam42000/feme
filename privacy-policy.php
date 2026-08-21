<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Client Privacy & Data Discretion Policy Page
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Privacy Policy | FeMe Luxury Closet";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<section class="section-hero-compact" style="background: linear-gradient(rgba(26, 26, 26, 0.8), rgba(26, 26, 26, 0.9)), url('assets/images/cat_designer.jpg') center/cover no-repeat; padding: 4.5rem 0; color: #FFF; text-align: center;">
    <div class="container">
        <p class="section-subtitle" style="color: var(--gold-primary); letter-spacing: 3px; font-size: 0.85rem;">✦ Client Discretion Assured ✦</p>
        <h1 style="font-family: var(--font-serif); font-size: 2.6rem; font-weight: 700; margin-top: 0.5rem; color: #FFF;">
            Privacy Policy & Data Security
        </h1>
        <p style="max-width: 600px; margin: 1rem auto 0; font-size: 0.92rem; color: #DDD; line-height: 1.6;">
            Your personal information, measurement data, and purchasing history are safeguarded with strict confidentiality and 256-bit SSL encryption.
        </p>
    </div>
</section>

<!-- Policy Content -->
<section class="section" style="padding: 4rem 0; background-color: var(--bg-cream);">
    <div class="container" style="max-width: 900px;">
        
        <div style="background: #FFF; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 2.5rem; box-shadow: var(--shadow-soft); display: flex; flex-direction: column; gap: 2rem;">
            
            <div>
                <h3 style="font-family: var(--font-serif); font-size: 1.3rem; color: var(--gold-primary); margin-bottom: 0.75rem;">
                    1. Confidentiality Commitment
                </h3>
                <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.7;">
                    At <strong><?= STORE_NAME ?></strong>, we uphold the highest standards of discretion for our discerning clientele. We never sell, rent, or share your personal profile, measurement records, or purchase data with third-party advertising brokers.
                </p>
            </div>

            <div>
                <h3 style="font-family: var(--font-serif); font-size: 1.3rem; color: var(--gold-primary); margin-bottom: 0.75rem;">
                    2. Payment Security & Encryption
                </h3>
                <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.7; margin-bottom: 0.5rem;">
                    All financial transactions processed on our website are protected by industry-standard 256-bit SSL encryption powered by Razorpay.
                </p>
                <ul style="padding-left: 1.25rem; font-size: 0.88rem; color: var(--text-muted); line-height: 1.8;">
                    <li>We do not store your Credit Card, Debit Card, or UPI PIN numbers on our servers.</li>
                    <li>Razorpay holds PCI-DSS Level 1 compliance for guaranteed financial data security.</li>
                </ul>
            </div>

            <div>
                <h3 style="font-family: var(--font-serif); font-size: 1.3rem; color: var(--gold-primary); margin-bottom: 0.75rem;">
                    3. Information We Collect
                </h3>
                <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.7; margin-bottom: 0.5rem;">
                    To fulfill your orders and provide bespoke tailoring services, we collect:
                </p>
                <ul style="padding-left: 1.25rem; font-size: 0.88rem; color: var(--text-muted); line-height: 1.8;">
                    <li>Contact Details: Full Name, Delivery Address, Mobile Phone Number, Email Address.</li>
                    <li>Atelier Records: Tailoring dimensions and custom embroidery preferences.</li>
                    <li>Logistics Information: AWB tracking numbers shared strictly with our onboarded courier partners (Blue Dart, Delhivery, DTDC, DHL, FedEx).</li>
                </ul>
            </div>

            <div>
                <h3 style="font-family: var(--font-serif); font-size: 1.3rem; color: var(--gold-primary); margin-bottom: 0.75rem;">
                    4. Your Rights & Data Requests
                </h3>
                <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.7;">
                    You have the right to request a copy of your stored account data or request account deletion at any time by contacting our Privacy Office at <a href="mailto:<?= STORE_EMAIL ?>" style="color: var(--gold-primary); font-weight: 600;"><?= STORE_EMAIL ?></a>.
                </p>
            </div>

            <div style="border-top: 1px solid var(--border-color); padding-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <span style="font-size: 0.82rem; color: var(--text-muted);">Last Updated: August 2026 &bull; <?= STORE_NAME ?> Legal Office</span>
                <a href="contact.php" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.5rem 1rem;">Contact Privacy Office</a>
            </div>

        </div>

    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
