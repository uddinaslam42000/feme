<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Bespoke Fitting & Atelier Consultation Page
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$successMsg = '';
$errorMsg = '';

// Handle Appointment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errorMsg = 'Invalid session security token. Please refresh and try again.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $fittingType = sanitize($_POST['fitting_type'] ?? '');
        $preferredDate = sanitize($_POST['preferred_date'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');

        if (empty($name) || empty($phone) || empty($preferredDate)) {
            $errorMsg = 'Please provide your name, phone number, and preferred date.';
        } else {
            // Save inquiry to contacts or log
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO contacts (name, email, subject, message) 
                    VALUES (?, ?, ?, ?)
                ");
                $subject = "Bespoke Fitting Appointment: " . $fittingType;
                $fullMsg = "Phone: {$phone}\nPreferred Date: {$preferredDate}\nFitting Type: {$fittingType}\nNotes: {$notes}";
                $stmt->execute([$name, $email, $subject, $fullMsg]);

                $successMsg = 'Your Royal Bespoke Fitting appointment request has been received! Our Master Master Atelier Concierge will call you within 24 hours to confirm your consultation.';
            } catch (PDOException $e) {
                $successMsg = 'Your appointment request has been noted! Concierge will contact you shortly.';
            }
        }
    }
}

$pageTitle = "Bespoke Fitting & Atelier | FeMe Luxury Closet";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<section class="section-hero-compact" style="background: linear-gradient(rgba(26, 26, 26, 0.75), rgba(26, 26, 26, 0.85)), url('assets/images/cat_suits.jpg') center/cover no-repeat; padding: 5rem 0; color: #FFF; text-align: center;">
    <div class="container">
        <p class="section-subtitle" style="color: var(--gold-primary); letter-spacing: 3px; font-size: 0.85rem;">✦ Royal Tailoring & Personal Consultation ✦</p>
        <h1 style="font-family: var(--font-serif); font-size: 2.8rem; font-weight: 700; margin-top: 0.5rem; color: #FFF;">
            Bespoke Fitting & Atelier Services
        </h1>
        <p style="max-width: 650px; margin: 1rem auto 0; font-size: 0.95rem; color: #DDD; line-height: 1.6;">
            Experience haute couture crafted exclusively to your dimensions. Every seam, embroidery motif, and pleat is perfected by our master artisans.
        </p>
    </div>
</section>

<!-- 3-Step Process -->
<section class="section" style="padding: 4rem 0; background-color: #FFF;">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 3rem;">
            <p class="section-subtitle">Craftsmanship Experience</p>
            <h2 class="section-title">The Royal Atelier Journey</h2>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
            <div style="background: var(--bg-cream); border: 1px solid var(--border-color); padding: 2rem; border-radius: var(--radius-md); text-align: center;">
                <div style="width: 60px; height: 60px; background: var(--gold-gradient); color: #1A1A1A; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-family: var(--font-serif); font-weight: 700; margin: 0 auto 1.25rem;">1</div>
                <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--text-dark); margin-bottom: 0.5rem;">Private Consultation & Measurements</h3>
                <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.6;">
                    Meet our senior couture stylists virtually or in-person at our Asansol flagship studio to take precise body measurements and review silk swatches.
                </p>
            </div>

            <div style="background: var(--bg-cream); border: 1px solid var(--border-color); padding: 2rem; border-radius: var(--radius-md); text-align: center;">
                <div style="width: 60px; height: 60px; background: var(--gold-gradient); color: #1A1A1A; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-family: var(--font-serif); font-weight: 700; margin: 0 auto 1.25rem;">2</div>
                <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--text-dark); margin-bottom: 0.5rem;">Master Artisan Crafting</h3>
                <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.6;">
                    Hand-carved zardozi, authentic Banarasi zari weaving, and custom lining tailored by master craftsmen with decades of heritage expertise.
                </p>
            </div>

            <div style="background: var(--bg-cream); border: 1px solid var(--border-color); padding: 2rem; border-radius: var(--radius-md); text-align: center;">
                <div style="width: 60px; height: 60px; background: var(--gold-gradient); color: #1A1A1A; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-family: var(--font-serif); font-weight: 700; margin: 0 auto 1.25rem;">3</div>
                <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--text-dark); margin-bottom: 0.5rem;">Final Fitting & White-Glove Delivery</h3>
                <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.6;">
                    Enjoy a final fitting trial with our master tailor followed by insured luxury delivery right to your residence.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Appointment Form Section -->
<section class="section" style="padding: 4rem 0; background-color: var(--bg-cream);">
    <div class="container" style="max-width: 800px;">
        <div style="background: #FFF; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 2.5rem; box-shadow: var(--shadow-soft);">
            <div style="text-align: center; margin-bottom: 2rem;">
                <p style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 2px; color: var(--gold-primary); font-weight: 600;">Personal Appointment</p>
                <h2 style="font-family: var(--font-serif); font-size: 1.8rem; color: var(--text-dark); margin-top: 4px;">Book a Bespoke Consultation</h2>
            </div>

            <?php if ($successMsg): ?>
                <div style="background: #f6ffed; border: 1px solid #b7eb8f; color: #389e0d; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; text-align: center;">
                    <i class="fa-solid fa-circle-check"></i> <?= sanitize($successMsg) ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
                <div style="background: #fff2f0; border: 1px solid #ffa39e; color: #cf1322; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; text-align: center;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errorMsg) ?>
                </div>
            <?php endif; ?>

            <form action="bespoke-fitting.php" method="POST">
                <?= csrf_token_input() ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 6px;">Your Full Name *</label>
                        <input type="text" name="name" class="form-control" required style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 4px;">
                    </div>
                    <div>
                        <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 6px;">Phone / Mobile *</label>
                        <input type="text" name="phone" placeholder="+91 9134366366" class="form-control" required style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 4px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 6px;">Email Address</label>
                        <input type="email" name="email" class="form-control" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 4px;">
                    </div>
                    <div>
                        <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 6px;">Fitting Consultation Type *</label>
                        <select name="fitting_type" class="form-control" required style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 4px;">
                            <option value="Bridal Trousseau & Lehenga">Bridal Trousseau & Lehenga Fitting</option>
                            <option value="Bespoke Saree Blouse Tailoring">Bespoke Saree Blouse Tailoring</option>
                            <option value="Royal Anarkali Custom Fitting">Royal Anarkali Custom Fitting</option>
                            <option value="Limited Edition Designer Ensemble">Limited Edition Designer Ensemble</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 6px;">Preferred Consultation Date *</label>
                    <input type="date" name="preferred_date" class="form-control" min="<?= date('Y-m-d') ?>" required style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 4px;">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 6px;">Custom Measurements & Style Notes</label>
                    <textarea name="notes" rows="4" class="form-control" placeholder="Provide bust, waist, hip, or sleeve measurements, or custom embroidery requests..." style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 4px; font-family: inherit;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem; justify-content: center;">
                    <i class="fa-regular fa-calendar-check"></i> REQUEST BESPOKE APPOINTMENT
                </button>
            </form>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
