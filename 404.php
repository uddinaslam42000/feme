<?php
/**
 * FeMe – Ultimate Luxury Closet
 * Custom 404 Error Page
 */
require_once __DIR__ . '/includes/header.php';
?>

<section class="section error-404-section">
    <div class="container">
        <div class="confirmation-card" style="border-color: var(--gold-primary); max-width: 600px;">
            <span class="category-eyebrow" style="letter-spacing: 3px;">ERROR 404</span>
            <h1 class="confirmation-title" style="font-size: clamp(2.5rem, 6vw, 4rem); color: var(--gold-primary);">404</h1>
            <h2 style="font-family: var(--font-serif); margin-bottom: 0.75rem;">Page Not Found</h2>
            <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 2rem;">
                The luxury page or creation you are searching for has moved, been renamed, or is no longer available in our closet.
            </p>
            <div>
                <a href="index.php" class="btn-gold">RETURN TO HOMEPAGE</a>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
