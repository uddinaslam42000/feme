<?php
/**
 * FeMe – Ultimate Luxury Closet
 * About & Brand Heritage Page
 */
require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Hero Banner -->
<section class="category-banner-strip" style="background-image: linear-gradient(180deg, rgba(26,26,26,0.6) 0%, rgba(26,26,26,0.85) 100%), url('assets/images/cat_designer.jpg');">
    <div class="container">
        <div class="category-banner-content">
            <span class="category-eyebrow">HERITAGE & COUTURE</span>
            <h1 class="category-banner-title">Our Story</h1>
            <p class="category-banner-desc">Elegance draped in distinction. Born out of reverence for India's royal textile tradition.</p>
        </div>
    </div>
</section>

<!-- 2. Story Sections (Alternating Layouts) -->
<section class="section story-section">
    <div class="container">
        
        <!-- Story Block 1: Brand Origin -->
        <div class="story-block fade-in-section">
            <div class="story-content">
                <span class="category-pill">THE BEGINNING</span>
                <h2 class="story-heading">Reimagining Royal Heritage</h2>
                <p class="story-paragraph">
                    Founded with a passion for preserving India’s rich textile legacy, FeMe is a sanctuary of ultimate luxury. We bridge the gap between centuries-old weaving techniques and contemporary haute couture, crafting garments meant to be treasured across generations.
                </p>
                <p class="story-paragraph">
                    Every piece in our closet tells a story of royal grandeur, meticulous artistry, and uncompromising purity of materials.
                </p>
            </div>
            <div class="story-image-box">
                <img src="assets/images/cat_sarees.jpg" alt="FeMe Royal Heritage Sarees" loading="lazy">
            </div>
        </div>

        <!-- Story Block 2: Craftsmanship -->
        <div class="story-block reverse-block fade-in-section">
            <div class="story-image-box">
                <img src="assets/images/cat_suits.jpg" alt="Artisan Craftsmanship" loading="lazy">
            </div>
            <div class="story-content">
                <span class="category-pill">ARTISANAL EXCELLENCE</span>
                <h2 class="story-heading">Master Weavers & Real Gold Zari</h2>
                <p class="story-paragraph">
                    From the silk looms of Kanchipuram and Varanasi to the intricate embroiderers of Rajasthan, FeMe collaborates directly with generational master artisans.
                </p>
                <p class="story-paragraph">
                    Our Kanjeevaram and Banarasi sarees are hand-woven using pure Mulberry silk and authentic gold & silver zari thread, taking up to 120 days of dedication per masterpiece.
                </p>
            </div>
        </div>

        <!-- Story Block 3: The FeMe Promise -->
        <div class="story-block fade-in-section">
            <div class="story-content">
                <span class="category-pill">OUR COMMITMENT</span>
                <h2 class="story-heading">Distinction Without Compromise</h2>
                <p class="story-paragraph">
                    We believe true luxury lies in exclusivity and unblemished quality. Our collections feature strictly limited runs and bespoke bridal commissions, ensuring that every FeMe client wears a creation as unique as her lineage.
                </p>
                <div style="margin-top: 1.5rem;">
                    <a href="category.php" class="btn-gold">EXPLORE COLLECTION</a>
                </div>
            </div>
            <div class="story-image-box">
                <img src="assets/images/cat_limited.jpg" alt="FeMe Limited Edition Collection" loading="lazy">
            </div>
        </div>

    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
