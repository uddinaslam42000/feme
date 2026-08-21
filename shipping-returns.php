<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Shipping, Logistics & Return Policy Page
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Shipping & Returns Policy | FeMe Luxury Closet";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<section class="section-hero-compact" style="background: linear-gradient(rgba(26, 26, 26, 0.8), rgba(26, 26, 26, 0.9)), url('assets/images/cat_sarees.jpg') center/cover no-repeat; padding: 4.5rem 0; color: #FFF; text-align: center;">
    <div class="container">
        <p class="section-subtitle" style="color: var(--gold-primary); letter-spacing: 3px; font-size: 0.85rem;">✦ Insured Delivery & White-Glove Client Care ✦</p>
        <h1 style="font-family: var(--font-serif); font-size: 2.6rem; font-weight: 700; margin-top: 0.5rem; color: #FFF;">
            Shipping & Return Policies
        </h1>
        <p style="max-width: 600px; margin: 1rem auto 0; font-size: 0.92rem; color: #DDD; line-height: 1.6;">
            We deliver handcrafted Indian couture worldwide with full transit insurance and real-time tracking through our premiere logistics partners.
        </p>
    </div>
</section>

<!-- Content Section -->
<section class="section" style="padding: 4rem 0; background-color: var(--bg-cream);">
    <div class="container" style="max-width: 900px;">
        
        <!-- Onboard Courier Partners Banner -->
        <div style="background: #FFF; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-soft);">
            <h3 style="font-family: var(--font-serif); font-size: 1.3rem; color: var(--gold-primary); margin-bottom: 1rem;">
                <i class="fa-solid fa-truck-fast"></i> Onboarded Insured Logistics Partners
            </h3>
            <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 1.25rem;">
                Every FeMe luxury order is dispatched in tamper-evident velvet box packaging and tracked live via our official courier partners:
            </p>
            <div style="display: flex; gap: 1.25rem; flex-wrap: wrap; align-items: center; justify-content: space-around; background: #FDFBF7; padding: 1.25rem; border-radius: var(--radius-sm); border: 1px dashed var(--border-color);">
                <div style="font-weight: 700; color: #002D62; font-size: 1rem;"><i class="fa-solid fa-plane"></i> Blue Dart Express</div>
                <div style="font-weight: 700; color: #E31E24; font-size: 1rem;"><i class="fa-solid fa-box"></i> Delhivery Courier</div>
                <div style="font-weight: 700; color: #004B93; font-size: 1rem;"><i class="fa-solid fa-truck"></i> DTDC Express</div>
                <div style="font-weight: 700; color: #D40511; font-size: 1rem;"><i class="fa-solid fa-globe"></i> DHL Express</div>
                <div style="font-weight: 700; color: #4D148C; font-size: 1rem;"><i class="fa-solid fa-paper-plane"></i> FedEx Logistics</div>
            </div>
        </div>

        <!-- Policy Grid -->
        <div style="display: grid; gap: 1.75rem;">
            
            <!-- Shipping Timelines -->
            <div style="background: #FFF; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 2rem;">
                <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--text-dark); margin-bottom: 0.75rem;">
                    1. Shipping & Dispatch Timelines
                </h3>
                <ul style="padding-left: 1.25rem; font-size: 0.9rem; color: var(--text-muted); line-height: 1.8;">
                    <li><strong>Ready-to-Ship Collections:</strong> Dispatched within 24 to 48 hours of order confirmation.</li>
                    <li><strong>Bespoke & Tailored Couture:</strong> Dispatched within 10 to 14 business days to complete hand-embroidered finishing and blouse tailoring.</li>
                    <li><strong>Domestic Delivery (India):</strong> Complimentary express delivery across all cities (3 – 5 business days).</li>
                    <li><strong>International Shipping:</strong> Delivered via DHL Express / FedEx Priority within 5 – 7 business days worldwide.</li>
                </ul>
            </div>

            <!-- Tracking & Insurance -->
            <div style="background: #FFF; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 2rem;">
                <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--text-dark); margin-bottom: 0.75rem;">
                    2. Package Tracking & Insurance
                </h3>
                <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.7; margin-bottom: 0.75rem;">
                    Once your order is tagged and dispatched, you will receive an SMS and email containing your AWB Tracking Number along with a direct link to track your shipment in real time.
                </p>
                <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.7;">
                    You can also track your package anytime from your <a href="account.php" style="color: var(--gold-primary); font-weight: 600;">Customer Account Panel</a>. All shipments are 100% insured against loss or transit damage.
                </p>
            </div>

            <!-- Returns & Exchanges -->
            <div style="background: #FFF; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 2rem;">
                <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--text-dark); margin-bottom: 0.75rem;">
                    3. Returns, Exchanges & Complimentary Alterations
                </h3>
                <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.7; margin-bottom: 0.75rem;">
                    We take immense pride in the quality and authentic weaving of our silk sarees and ensembles. If you receive a damaged or incorrect piece:
                </p>
                <ul style="padding-left: 1.25rem; font-size: 0.9rem; color: var(--text-muted); line-height: 1.8;">
                    <li><strong>7-Day Hassle-Free Exchange:</strong> Notify our client care team within 7 days of delivery for a replacement or store credit.</li>
                    <li><strong>Complimentary Fitting Alteration:</strong> If a custom blouse or Anarkali requires fitting adjustments, we provide free alteration pickups.</li>
                    <li><strong>Condition:</strong> Returned items must be unworn, unwashed, with security tags intact in original packaging.</li>
                </ul>
            </div>

            <!-- Contact Concierge -->
            <div style="background: linear-gradient(135deg, #1A1A1A 0%, #2A2A2A 100%); color: #FFF; border-radius: var(--radius-md); padding: 2rem; text-align: center;">
                <h3 style="font-family: var(--font-serif); font-size: 1.3rem; color: var(--gold-primary); margin-bottom: 0.5rem;">Need Shipping Assistance?</h3>
                <p style="font-size: 0.88rem; color: #CCC; margin-bottom: 1.25rem;">Our Client Concierge team is available to assist you with order status or urgent courier dispatches.</p>
                <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                    <a href="contact.php" class="btn btn-primary" style="padding: 0.65rem 1.5rem;"><i class="fa-solid fa-headset"></i> Contact Concierge</a>
                    <a href="tel:<?= str_replace(' ', '', STORE_PHONE) ?>" class="btn btn-outline" style="border-color: var(--gold-primary); color: var(--gold-primary); padding: 0.65rem 1.5rem;"><i class="fa-solid fa-phone"></i> Call <?= STORE_PHONE ?></a>
                </div>
            </div>

        </div>

    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
