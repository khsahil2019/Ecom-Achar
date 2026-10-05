<?php
/**
 * Achar Heritage - Homepage
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/product_card.php';

$pdo = db();

// Fetch Hero Banner
$bannerStmt = $pdo->query("SELECT * FROM banners WHERE status = 'active' ORDER BY sort_order ASC LIMIT 1");
$heroBanner = $bannerStmt->fetch();

// Fetch Categories
$catStmt = $pdo->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active' WHERE c.status = 'active' GROUP BY c.id ORDER BY c.display_order ASC");
$categories = $catStmt->fetchAll();

// Fetch Best Sellers
$bestsellerStmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' AND p.is_bestseller = 1 ORDER BY p.id ASC LIMIT 4");
$bestSellers = $bestsellerStmt->fetchAll();

// Fetch New Arrivals & Popular
$newStmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.id DESC LIMIT 4");
$newArrivals = $newStmt->fetchAll();

// Fetch Combo Packs
$comboStmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' AND c.slug = 'royal-combos' LIMIT 4");
$combos = $comboStmt->fetchAll();

// Fetch Reviews
$reviewStmt = $pdo->query("SELECT r.*, p.name as product_name FROM reviews r JOIN products p ON r.product_id = p.id WHERE r.status = 'approved' ORDER BY r.rating DESC, r.id DESC LIMIT 4");
$reviews = $reviewStmt->fetchAll();

$pageTitle = 'Achar Heritage - Authentic Handcrafted Indian Pickles Online';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- 1. Hero Section -->
<section class="hero-section">
    <div class="container position-relative">
        <div class="row align-items-center g-5">
            <!-- Left Hero Content -->
            <div class="col-lg-6">
                <span class="badge px-3 py-2 rounded-pill fw-bold text-uppercase mb-3 shadow-sm" style="background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A;">
                    <i class="bi bi-patch-check-fill text-warning me-1"></i> Fresh • Traditional • Made with Care
                </span>
                <h1 class="hero-headline font-heading">
                    Authentic Achar.<br>
                    Traditional Taste.<br>
                    <span style="color: var(--brand-primary);">Delivered to Your Door.</span>
                </h1>
                <p class="hero-subheading">
                    Discover handcrafted Indian pickles made with authentic flavours, heirloom spice mixes, cold-pressed mustard oil, and time-honored grandma recipes.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= BASE_URL ?>/shop.php" class="btn btn-brand-primary btn-lg px-4 py-3 shadow-sm">
                        <i class="bi bi-bag-check me-2"></i> Shop All Pickles
                    </a>
                    <a href="<?= BASE_URL ?>/shop.php?category=royal-combos" class="btn btn-outline-dark btn-lg px-4 py-3 rounded-pill bg-white shadow-sm">
                        <i class="bi bi-gift me-2"></i> Explore Gift Combos
                    </a>
                </div>

                <!-- Trust Micro-Badges -->
                <div class="row mt-5 pt-4 border-top border-secondary-subtle g-3">
                    <div class="col-4">
                        <div class="fw-bold fs-3 text-dark">100%</div>
                        <div class="small text-muted fw-medium">Pure Mustard Oil</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-3 text-dark">0%</div>
                        <div class="small text-muted fw-medium">No Preservatives</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-3 text-dark">50K+</div>
                        <div class="small text-muted fw-medium">Happy Diners</div>
                    </div>
                </div>
            </div>

            <!-- Right Hero Visual -->
            <div class="col-lg-6 text-center">
                <div class="position-relative d-inline-block">
                    <img src="<?= BASE_URL ?>/uploads/products/prod-mango.jpg" alt="Traditional Mango Achar Jar" class="img-fluid rounded-4 shadow-lg border border-3 border-white" style="max-height: 480px; object-fit: cover;">
                    <div class="position-absolute bottom-0 start-0 translate-middle-y bg-white text-dark p-3 rounded-4 shadow-lg text-start d-none d-sm-flex align-items-center gap-3 ms-n3 border">
                        <div class="bg-warning text-dark rounded-circle p-2 fs-4" style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-star-fill text-dark"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-6">4.9 / 5.0 Star Rating</div>
                            <div class="text-muted small">From 2,400+ authentic reviews</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Flash notification messages if any -->
<div class="container mt-4">
    <?= render_flash() ?>
</div>

<!-- 2. Shop by Category -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="text-uppercase fw-bold text-muted small letter-spacing-1">Pure Artisanal Collections</span>
                <h2 class="font-heading m-0">Shop by Category</h2>
            </div>
            <a href="<?= BASE_URL ?>/shop.php" class="text-decoration-none fw-bold text-brand-primary d-flex align-items-center gap-1">
                View All Categories <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($categories as $cat): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="<?= BASE_URL ?>/shop.php?category=<?= urlencode($cat['slug']) ?>" class="category-card">
                        <img src="<?= BASE_URL ?>/uploads/categories/<?= e($cat['image']) ?>" alt="<?= e($cat['name']) ?>" class="category-img">
                        <h4 class="category-title"><?= e($cat['name']) ?></h4>
                        <div class="pb-3 text-muted small"><?= $cat['product_count'] ?> Jars</div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 3. Best Sellers -->
<section class="py-5 bg-white border-top border-bottom border-light-subtle">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill fw-bold text-uppercase mb-2">Most Cherished Flavours</span>
            <h2 class="font-heading display-6">Our Best Sellers</h2>
            <p class="text-muted mx-auto" style="max-width: 600px;">
                Traditional staples beloved in households across India. Handpicked ingredients slow-steeped in sun-drenched porcelain and glass containers.
            </p>
        </div>

        <div class="row g-4">
            <?php foreach ($bestSellers as $prod): ?>
                <div class="col-6 col-md-6 col-lg-3">
                    <?php render_product_card($prod); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="<?= BASE_URL ?>/shop.php?filter=bestseller" class="btn btn-outline-brand btn-lg px-5">
                Explore All Best Sellers
            </a>
        </div>
    </div>
</section>

<!-- 4. Promotional Banner (Festive Offer) -->
<section class="py-5">
    <div class="container">
        <div class="p-5 rounded-4 text-white position-relative overflow-hidden shadow-lg" style="background: linear-gradient(135deg, #1F2937 0%, #111827 100%); border-left: 6px solid #B91C1C;">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold text-uppercase mb-3 shadow-sm">
                        <i class="bi bi-star-fill me-1"></i> Limited Batch Heritage Production
                    </span>
                    <h2 class="font-heading text-white display-5 mb-3">Grandmother's Secret Recipes Cured in Earthen Jars</h2>
                    <p class="fs-5 text-light opacity-90 mb-4" style="max-width: 650px;">
                        Each batch undergoes 21 days of rooftop sun incubation in cold-pressed Kachi Ghani mustard oil and unrefined rock salt.
                    </p>
                    <a href="<?= BASE_URL ?>/shop.php?category=royal-combos" class="btn btn-warning text-dark fw-bold btn-lg rounded-pill px-4 shadow">
                        <i class="bi bi-gift-fill me-2"></i> Order Shahi Combo Pack
                    </a>
                </div>
                <div class="col-lg-4 text-center mt-4 mt-lg-0">
                    <img src="<?= BASE_URL ?>/uploads/products/prod-combo.jpg" alt="Achar Heritage Combos" class="img-fluid rounded-4 shadow-lg border border-secondary border-opacity-25" style="max-height: 250px;">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 5. Why Choose Us -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-uppercase fw-bold text-muted small">Pure Authenticity Guaranteed</span>
            <h2 class="font-heading display-6">Why Choose Achar Heritage?</h2>
            <p class="text-muted mx-auto" style="max-width: 600px;">
                We never compromise on tradition, purity, and hygiene. Here is what makes our pickles unmatched:
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-4 col-lg-2">
                <div class="feature-box">
                    <div class="feature-icon-circle"><i class="bi bi-heart-pulse"></i></div>
                    <h5 class="fs-6 fw-bold">Authentic Taste</h5>
                    <p class="text-muted small m-0">Grandmother's recipes preserved across generations.</p>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="feature-box">
                    <div class="feature-icon-circle"><i class="bi bi-patch-check"></i></div>
                    <h5 class="fs-6 fw-bold">Pure Spices</h5>
                    <p class="text-muted small m-0">Zero chemicals, zero artificial vinegars or colors.</p>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="feature-box">
                    <div class="feature-icon-circle"><i class="bi bi-shield-check"></i></div>
                    <h5 class="fs-6 fw-bold">Hygienic Prep</h5>
                    <p class="text-muted small m-0">Sterilized glass containers and clean preparation.</p>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="feature-box">
                    <div class="feature-icon-circle"><i class="bi bi-box2-heart"></i></div>
                    <h5 class="fs-6 fw-bold">Secure Jars</h5>
                    <p class="text-muted small m-0">Special leak-proof inner seal and cushion box.</p>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="feature-box">
                    <div class="feature-icon-circle"><i class="bi bi-truck"></i></div>
                    <h5 class="fs-6 fw-bold">Fast Delivery</h5>
                    <p class="text-muted small m-0">Quick dispatch to your doorstep across India.</p>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="feature-box">
                    <div class="feature-icon-circle"><i class="bi bi-arrow-repeat"></i></div>
                    <h5 class="fs-6 fw-bold">Easy Support</h5>
                    <p class="text-muted small m-0">Direct WhatsApp support and replacement guarantee.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 6. New Arrivals & Trending -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="text-uppercase fw-bold text-muted small">Fresh From The Sun Terraces</span>
                <h2 class="font-heading m-0">New Arrivals & Trending</h2>
            </div>
            <a href="<?= BASE_URL ?>/shop.php?sort=latest" class="text-decoration-none fw-bold text-brand-primary d-flex align-items-center gap-1">
                View All New <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($newArrivals as $prod): ?>
                <div class="col-6 col-md-6 col-lg-3">
                    <?php render_product_card($prod); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 7. How It Works (The Traditional Sun Process) -->
<section class="py-5 bg-white border-top">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-uppercase fw-bold text-muted small">From Farm To Dining Table</span>
            <h2 class="font-heading display-6">How Our Pickles Are Made</h2>
            <p class="text-muted">The traditional 4-step slow curing method.</p>
        </div>

        <div class="row g-4 text-center">
            <div class="col-md-3">
                <div class="p-4 rounded-4 bg-light h-100">
                    <div class="bg-brand-primary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold fs-4" style="width:50px;height:50px;">1</div>
                    <h5 class="fw-bold">Artisanal Sourcing</h5>
                    <p class="text-muted small m-0">Fresh raw mangoes, Kagzi lemons, and hand-peeled garlic sourced straight from domestic orchards.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 rounded-4 bg-light h-100">
                    <div class="bg-brand-primary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold fs-4" style="width:50px;height:50px;">2</div>
                    <h5 class="fw-bold">Sun Curing (Dhoop)</h5>
                    <p class="text-muted small m-0">Steeped in cold-pressed mustard oil with whole roasted spices under pure natural sunlight for 21 days.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 rounded-4 bg-light h-100">
                    <div class="bg-brand-primary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold fs-4" style="width:50px;height:50px;">3</div>
                    <h5 class="fw-bold">Vacuum Glass Jars</h5>
                    <p class="text-muted small m-0">Carefully poured into sanitized food-grade glass jars with leak-proof golden lids and tamper seals.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 rounded-4 bg-light h-100">
                    <div class="bg-brand-primary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold fs-4" style="width:50px;height:50px;">4</div>
                    <h5 class="fw-bold">Delivered With Love</h5>
                    <p class="text-muted small m-0">Safely boxed in impact-resistant cushioned packaging and dispatched straight to your dinner plate.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 8. Customer Reviews -->
<section class="py-5" style="background: #FFF5F5;">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-uppercase fw-bold text-muted small">Loved Across India</span>
            <h2 class="font-heading display-6">Words From Our Diners</h2>
            <div class="d-flex justify-content-center align-items-center gap-1 text-warning fs-5 mt-2">
                <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                <span class="text-dark fs-6 fw-bold ms-2">4.9 Average Rating</span>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($reviews as $rev): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 rounded-4 shadow-sm p-4 h-100 bg-white">
                        <div class="text-warning mb-2">
                            <?php for ($i = 0; $i < (int)$rev['rating']; $i++): ?>
                                <i class="bi bi-star-fill"></i>
                            <?php endfor; ?>
                        </div>
                        <h6 class="fw-bold mb-2">"<?= e($rev['title'] ?: 'Incredible Authenticity') ?>"</h6>
                        <p class="text-muted small flex-grow-1" style="line-height: 1.6;">
                            <?= e($rev['comment']) ?>
                        </p>
                        <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-bold small text-dark"><?= e($rev['customer_name']) ?></div>
                                <div class="text-muted" style="font-size:0.75rem"><?= e($rev['product_name']) ?></div>
                            </div>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:0.7rem;">Verified Buyer</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 9. Social Proof / Instagram Grid -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="text-uppercase fw-bold text-muted small">Follow Our Culinary Journey</span>
                <h3 class="font-heading m-0">@AcharHeritage on Instagram</h3>
            </div>
            <a href="<?= e(get_setting('instagram_url', 'https://instagram.com')) ?>" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill">
                <i class="bi bi-instagram me-1"></i> Follow Us
            </a>
        </div>

        <div class="row g-3">
            <div class="col-4 col-md-2">
                <img src="<?= BASE_URL ?>/uploads/products/prod-mango.jpg" class="img-fluid rounded-3" alt="Instagram Achar 1">
            </div>
            <div class="col-4 col-md-2">
                <img src="<?= BASE_URL ?>/uploads/products/prod-lemon.jpg" class="img-fluid rounded-3" alt="Instagram Achar 2">
            </div>
            <div class="col-4 col-md-2">
                <img src="<?= BASE_URL ?>/uploads/products/prod-garlic.jpg" class="img-fluid rounded-3" alt="Instagram Achar 3">
            </div>
            <div class="col-4 col-md-2">
                <img src="<?= BASE_URL ?>/uploads/products/prod-chilli.jpg" class="img-fluid rounded-3" alt="Instagram Achar 4">
            </div>
            <div class="col-4 col-md-2">
                <img src="<?= BASE_URL ?>/uploads/products/prod-mixed.jpg" class="img-fluid rounded-3" alt="Instagram Achar 5">
            </div>
            <div class="col-4 col-md-2">
                <img src="<?= BASE_URL ?>/uploads/products/prod-combo.jpg" class="img-fluid rounded-3" alt="Instagram Achar 6">
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
