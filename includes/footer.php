<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Master Footer Template
 */
?>
    <!-- Master Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Column 1: Brand Info -->
                <div class="footer-col footer-about">
                    <div class="brand-logo" style="margin-bottom: 1rem;">
                        <img src="assets/images/logo.png" alt="FeMe Luxury Closet Logo" class="brand-logo-img" style="width: 48px; height: 48px; max-width: 48px; max-height: 48px; border-radius: 50%; object-fit: cover; border: 1.5px solid #C9A24B; flex-shrink: 0;">
                        <div class="brand-logo-text-wrapper">
                            <span class="logo-text">Fe<span style="color: var(--gold-primary);">Me</span></span>
                            <span class="logo-tagline" style="color: var(--gold-primary);">Ultimate Luxury Closet</span>
                        </div>
                    </div>
                    <p>
                        FeMe represents the pinnacle of luxury Indian couture. Handcrafted silk sarees, royal Anarkalis, and bespoke lehengas designed for those who appreciate pure distinction.
                    </p>
                    <p style="font-size: 0.82rem; color: var(--text-light); margin-top: 0.75rem; line-height: 1.5;">
                        <i class="fa-solid fa-location-dot" style="color: var(--gold-primary); margin-right: 6px;"></i> <?= STORE_ADDRESS ?><br>
                        <i class="fa-solid fa-phone" style="color: var(--gold-primary); margin-right: 6px;"></i> <?= STORE_PHONE ?> | GSTIN: <strong><?= STORE_GSTIN ?></strong>
                    </p>
                </div>

                <!-- Column 2: Boutique Links -->
                <div class="footer-col">
                    <h4 class="footer-col-title">Boutique</h4>
                    <div class="footer-links">
                        <a href="category.php?slug=sarees">Silk Sarees</a>
                        <a href="category.php?slug=salwar-suits">Salwar Suits</a>
                        <a href="category.php?slug=designer-wear">Designer Wear</a>
                        <a href="category.php?slug=limited-edition">Limited Edition</a>
                        <a href="category.php?filter=new">New Arrivals</a>
                    </div>
                </div>

                <!-- Column 3: Legal & Assistance -->
                <div class="footer-col">
                    <h4 class="footer-col-title">Client Care</h4>
                    <div class="footer-links">
                        <a href="about.php">About FeMe</a>
                        <a href="contact.php">Contact & Appointments</a>
                        <a href="bespoke-fitting.php">Bespoke Fitting</a>
                        <a href="shipping-returns.php">Shipping & Returns</a>
                        <a href="privacy-policy.php">Privacy Policy</a>
                    </div>
                </div>

                <!-- Column 4: Newsletter Signup -->
                <div class="footer-col">
                    <h4 class="footer-col-title">The Royal Gazette</h4>
                    <p style="font-size: 0.84rem; color: var(--text-light); margin-bottom: 1rem;">
                        Subscribe to receive private invitations to limited artisan drops and private preview sales.
                    </p>
                    <form id="newsletterForm" class="newsletter-form">
                        <?= csrf_token_input() ?>
                        <div class="newsletter-input-group">
                            <input type="email" name="email" class="newsletter-input" placeholder="Enter your email address" required>
                            <button type="submit" class="newsletter-btn">JOIN GAZETTE</button>
                        </div>
                        <div id="newsletterMsg" class="newsletter-msg"></div>
                    </form>
                </div>
            </div>

            <!-- Bottom Copyright Bar -->
            <div class="footer-bottom">
                <p>&copy; 2026 FeMe Luxury Closet. All Rights Reserved. Elegance Draped in Distinction.</p>
            </div>
        </div>
    </footer>

    <!-- Master JS -->
    <script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
