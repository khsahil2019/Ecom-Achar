<?php
/**
 * Achar Heritage - Product Details Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/product_card.php';

$pdo = db();
$slug = trim($_GET['slug'] ?? '');

if (empty($slug)) {
    header('Location: ' . BASE_URL . '/shop.php');
    exit;
}

// Fetch Product with Category
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.slug = ? AND p.status = 'active'
    LIMIT 1
");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('error', 'Pickle product not found.');
    header('Location: ' . BASE_URL . '/shop.php');
    exit;
}

$productId = (int)$product['id'];

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (verify_csrf()) {
        $reviewerName = sanitize($_POST['reviewer_name'] ?? '');
        $reviewerEmail = sanitize($_POST['reviewer_email'] ?? '');
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $reviewTitle = sanitize($_POST['review_title'] ?? '');
        $reviewComment = sanitize($_POST['review_comment'] ?? '');

        if ($reviewerName && $reviewComment) {
            $insertRev = $pdo->prepare("
                INSERT INTO reviews (product_id, customer_id, customer_name, customer_email, rating, title, comment, is_verified_buyer, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'approved')
            ");
            $insertRev->execute([$productId, customer_id(), $reviewerName, $reviewerEmail, $rating, $reviewTitle, $reviewComment]);

            // Update rating cache on product
            $updateRating = $pdo->prepare("
                UPDATE products 
                SET rating_cache = (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE product_id = ? AND status = 'approved'),
                    reviews_count = (SELECT COUNT(*) FROM reviews WHERE product_id = ? AND status = 'approved')
                WHERE id = ?
            ");
            $updateRating->execute([$productId, $productId, $productId]);

            set_flash('success', 'Thank you! Your verified review has been published.');
            header('Location: ' . BASE_URL . '/product.php?slug=' . urlencode($slug) . '#reviews');
            exit;
        } else {
            set_flash('error', 'Please complete your name and review message.');
        }
    } else {
        set_flash('error', 'Security session expired. Please try again.');
    }
}

// Fetch Product Variants
$varStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY price ASC");
$varStmt->execute([$productId]);
$variants = $varStmt->fetchAll();

// Default Variant (first or is_default = 1)
$defaultVariant = null;
foreach ($variants as $v) {
    if (!empty($v['is_default'])) {
        $defaultVariant = $v;
        break;
    }
}
if (!$defaultVariant && !empty($variants)) {
    $defaultVariant = $variants[0];
}

// Fetch Product Gallery Images
$imgStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
$imgStmt->execute([$productId]);
$galleryImages = $imgStmt->fetchAll();

// Fetch Reviews
$revStmt = $pdo->prepare("SELECT * FROM reviews WHERE product_id = ? AND status = 'approved' ORDER BY id DESC");
$revStmt->execute([$productId]);
$reviews = $revStmt->fetchAll();

// Fetch Related Products (same category)
$relStmt = $pdo->prepare("
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
    ORDER BY p.is_bestseller DESC, p.id DESC
    LIMIT 4
");
$relStmt->execute([$product['category_id'], $productId]);
$relatedProducts = $relStmt->fetchAll();

$pageTitle = e($product['name']) . ' - Authentic Indian Pickle | Achar Heritage';
$pageDescription = e($product['short_description']);
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$activePrice = $defaultVariant ? (float)$defaultVariant['price'] : (float)$product['price'];
$activeMrp = $defaultVariant ? (float)$defaultVariant['mrp'] : (float)$product['mrp'];
$activeDiscount = $defaultVariant ? (int)$defaultVariant['discount_percent'] : (int)$product['discount_percent'];
$activeStock = $defaultVariant ? (int)$defaultVariant['stock'] : (int)$product['stock'];
?>

<div class="py-3 bg-white border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/shop.php">Pickle Shop</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/shop.php?category=<?= urlencode($product['category_slug']) ?>"><?= e($product['category_name']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($product['name']) ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-4">
    <?= render_flash() ?>

    <div class="row g-5">
        <!-- Left: Product Image Gallery -->
        <div class="col-lg-6">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white mb-3">
                <div class="position-relative text-center overflow-hidden rounded-3 bg-light">
                    <img id="mainProductImage" src="<?= BASE_URL ?>/uploads/products/<?= e($product['main_image']) ?>" alt="<?= e($product['name']) ?>" class="img-fluid" style="max-height: 480px; width: 100%; object-fit: cover;">
                    
                    <button type="button" class="product-wishlist-btn btn-toggle-wishlist position-absolute top-0 end-0 m-3 shadow" data-product-id="<?= $productId ?>" title="Save to Wishlist">
                        <i class="bi bi-heart"></i>
                    </button>
                </div>
            </div>

            <!-- Thumbnails -->
            <?php if (!empty($galleryImages)): ?>
                <div class="d-flex gap-2">
                    <img src="<?= BASE_URL ?>/uploads/products/<?= e($product['main_image']) ?>" 
                         class="rounded-3 border border-2 border-warning p-1 cursor-pointer" 
                         style="width:70px;height:70px;object-fit:cover;" 
                         onclick="document.getElementById('mainProductImage').src = this.src;" 
                         alt="Thumbnail Main">
                    <?php foreach ($galleryImages as $img): ?>
                        <img src="<?= BASE_URL ?>/uploads/products/<?= e($img['image_path']) ?>" 
                             class="rounded-3 border p-1 cursor-pointer" 
                             style="width:70px;height:70px;object-fit:cover;" 
                             onclick="document.getElementById('mainProductImage').src = this.src;" 
                             alt="Thumbnail Gallery">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Product Details & Purchase Controls -->
        <div class="col-lg-6">
            <div class="ps-lg-3">
                
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 small"><?= e($product['category_name']) ?></span>
                    <span class="text-muted small">SKU: <strong id="detailSku"><?= e($defaultVariant ? $defaultVariant['sku_variant'] : $product['sku']) ?></strong></span>
                </div>

                <h1 class="font-heading display-6 mb-2"><?= e($product['name']) ?></h1>
                
                <!-- Rating and Reviews Anchor -->
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="text-warning">
                        <i class="bi bi-star-fill"></i>
                        <span class="fw-bold text-dark ms-1"><?= number_format((float)$product['rating_cache'], 1) ?></span>
                    </div>
                    <span class="text-muted small">•</span>
                    <a href="#reviews" class="text-muted small text-decoration-none hover-underline">
                        <?= count($reviews) ?> Customer Reviews
                    </a>
                    <span class="text-muted small">•</span>
                    <span id="detailStockBadge" class="badge <?= $activeStock > 0 ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger' ?>">
                        <?= $activeStock > 0 ? "In Stock ({$activeStock} jars available)" : "Out of Stock" ?>
                    </span>
                </div>

                <!-- Price Area -->
                <div class="p-3 bg-light rounded-4 mb-4 d-flex align-items-baseline gap-3">
                    <div class="price-current fs-2" id="detailCurrentPrice"><?= format_price($activePrice) ?></div>
                    <div class="price-mrp fs-5 text-muted" id="detailMrp"><?= format_price($activeMrp) ?></div>
                    <span class="badge bg-danger text-white fs-6 px-2 py-1" id="detailDiscountBadge"><?= $activeDiscount ?>% OFF</span>
                    <span class="text-muted small ms-auto">(Inclusive of all taxes)</span>
                </div>

                <!-- Short Description -->
                <p class="text-secondary mb-4" style="line-height: 1.7;">
                    <?= e($product['short_description']) ?>
                </p>

                <!-- Variant Selector -->
                <?php if (!empty($variants)): ?>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted d-block">Select Weight Variant:</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($variants as $v): ?>
                                <button type="button" 
                                        class="variant-btn <?= ($defaultVariant && $defaultVariant['id'] == $v['id']) ? 'active' : '' ?>"
                                        data-variant-id="<?= (int)$v['id'] ?>"
                                        data-price="<?= (float)$v['price'] ?>"
                                        data-mrp="<?= (float)$v['mrp'] ?>"
                                        data-discount="<?= (int)$v['discount_percent'] ?>"
                                        data-stock="<?= (int)$v['stock'] ?>">
                                    <?= e($v['weight_label']) ?> • <?= format_price($v['price']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Quantity & Actions Form -->
                <form action="<?= BASE_URL ?>/cart.php" method="POST" class="mb-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="buy_now">
                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                    <input type="hidden" name="variant_id" id="selectedVariantId" value="<?= $defaultVariant ? (int)$defaultVariant['id'] : '' ?>">

                    <div class="row g-3 align-items-center mb-3">
                        <div class="col-auto">
                            <label class="form-label fw-bold small text-uppercase text-muted m-0">Quantity:</label>
                        </div>
                        <div class="col-auto">
                            <div class="quantity-selector d-flex align-items-center border rounded-pill bg-white px-2 py-1 shadow-sm">
                                <button type="button" class="btn btn-sm btn-link text-dark qty-btn qty-minus p-0 px-2 text-decoration-none fs-5">-</button>
                                <input type="number" name="quantity" class="form-control form-control-sm text-center border-0 qty-input fw-bold" value="1" min="1" max="99" style="width: 45px;">
                                <button type="button" class="btn btn-sm btn-link text-dark qty-btn qty-plus p-0 px-2 text-decoration-none fs-5">+</button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <button type="button" 
                                    class="btn btn-brand-primary btn-lg w-100 btn-add-to-cart py-3 d-flex align-items-center justify-content-center gap-2"
                                    data-product-id="<?= $productId ?>"
                                    data-variant-id="<?= $defaultVariant ? (int)$defaultVariant['id'] : '' ?>">
                                <i class="bi bi-bag-plus fs-5"></i> Add to Cart
                            </button>
                        </div>
                        <div class="col-sm-6">
                            <button type="submit" class="btn btn-brand-accent btn-lg w-100 py-3 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-lightning-charge fs-5"></i> Buy Now
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Value Proposition Highlights -->
                <div class="card border-0 rounded-4 bg-light p-3">
                    <div class="row g-2 text-center text-muted small">
                        <div class="col-3 border-end">
                            <i class="bi bi-patch-check-fill text-danger fs-5 d-block"></i>
                            <span>100% Traditional</span>
                        </div>
                        <div class="col-3 border-end">
                            <i class="bi bi-droplet-half text-warning fs-5 d-block"></i>
                            <span>Mustard Oil</span>
                        </div>
                        <div class="col-3 border-end">
                            <i class="bi bi-calendar2-check text-brand-primary fs-5 d-block"></i>
                            <span><?= e($product['shelf_life'] ?? '12 Months') ?> Shelf Life</span>
                        </div>
                        <div class="col-3">
                            <i class="bi bi-truck text-dark fs-5 d-block"></i>
                            <span>Pan-India Dispatch</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Product Information Tabs -->
    <div class="mt-5 pt-3">
        <ul class="nav nav-tabs nav-fill border-bottom border-2" id="productTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold py-3 text-uppercase" id="desc-tab" data-bs-toggle="tab" data-bs-target="#tab-description" type="button" role="tab">The Heritage Story</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold py-3 text-uppercase" id="ingredients-tab" data-bs-toggle="tab" data-bs-target="#tab-ingredients" type="button" role="tab">Ingredients & Spices</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold py-3 text-uppercase" id="storage-tab" data-bs-toggle="tab" data-bs-target="#tab-storage" type="button" role="tab">Storage & Instructions</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold py-3 text-uppercase" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#tab-reviews" type="button" role="tab">Customer Reviews (<?= count($reviews) ?>)</button>
            </li>
        </ul>

        <div class="tab-content bg-white p-4 p-md-5 rounded-bottom-4 shadow-sm border border-top-0 mb-5" id="productTabsContent">
            
            <!-- Tab 1: Description -->
            <div class="tab-pane fade show active" id="tab-description" role="tabpanel">
                <h4 class="font-heading mb-3">Authentic Preparation</h4>
                <p style="line-height: 1.8; font-size: 1.05rem;" class="text-secondary">
                    <?= nl2br(e($product['description'])) ?>
                </p>
                <div class="alert alert-warning-subtle border border-warning rounded-3 mt-4">
                    <i class="bi bi-info-circle-fill me-2 text-warning"></i>
                    <strong>Artisanal Small Batch:</strong> Because our pickles are slow-cured in sunlight with zero synthetic acidity regulators, subtle seasonal variations in color and pungency are completely natural and mark genuine craftsmanship.
                </div>
            </div>

            <!-- Tab 2: Ingredients -->
            <div class="tab-pane fade" id="tab-ingredients" role="tabpanel">
                <h4 class="font-heading mb-3">Pure Traditional Ingredients</h4>
                <p class="fs-6 text-secondary mb-4">
                    <?= e($product['ingredients']) ?>
                </p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <strong><i class="bi bi-check-circle text-danger me-1"></i> No Preservatives</strong>
                            <p class="text-muted small m-0 mt-1">Preserved only through natural sea/rock salt, whole spices, and cold-pressed oil.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <strong><i class="bi bi-check-circle text-danger me-1"></i> No Added Colors</strong>
                            <p class="text-muted small m-0 mt-1">Rich deep hue comes purely from stone-ground Kashmiri Degi Mirch and pure turmeric.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <strong><i class="bi bi-check-circle text-danger me-1"></i> 100% Vegetarian</strong>
                            <p class="text-muted small m-0 mt-1">Prepared in a dedicated sanctified Indian kitchen environment.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Storage -->
            <div class="tab-pane fade" id="tab-storage" role="tabpanel">
                <h4 class="font-heading mb-3">Grandma's Care Tips</h4>
                <ul class="list-unstyled d-flex flex-column gap-3 text-secondary">
                    <li class="d-flex gap-2">
                        <i class="bi bi-shield-check text-danger fs-5"></i>
                        <span><strong>Dry Spoon Rule:</strong> Always use a clean, completely dry stainless steel or wooden spoon. Moisture is the only enemy of authentic natural pickle.</span>
                    </li>
                    <li class="d-flex gap-2">
                        <i class="bi bi-droplet text-warning fs-5"></i>
                        <span><strong>Oil Blanket:</strong> Keep the mustard oil level floating at or above the pickle pieces. Cold-pressed mustard oil creates an airtight natural antimicrobial shield.</span>
                    </li>
                    <li class="d-flex gap-2">
                        <i class="bi bi-sun text-warning fs-5"></i>
                        <span><strong>Sun Friendly:</strong> In humid climates, you can place the jar in mild sun for a couple of hours once a month to rejuvenate the spice aroma.</span>
                    </li>
                </ul>
            </div>

            <!-- Tab 4: Reviews -->
            <div class="tab-pane fade" id="tab-reviews" role="tabpanel">
                <div class="row g-5" id="reviews">
                    <!-- Reviews List -->
                    <div class="col-lg-7">
                        <h4 class="font-heading mb-4">Customer Testimonials</h4>
                        <?php if (!empty($reviews)): ?>
                            <div class="d-flex flex-column gap-4">
                                <?php foreach ($reviews as $rev): ?>
                                    <div class="border-bottom pb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="text-warning">
                                                <?php for ($i = 0; $i < (int)$rev['rating']; $i++): ?>
                                                    <i class="bi bi-star-fill"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="text-muted small"><?= format_date($rev['created_at'], 'd M Y') ?></span>
                                        </div>
                                        <h6 class="fw-bold mb-1"><?= e($rev['title'] ?: 'Verified Purchase') ?></h6>
                                        <p class="text-muted small mb-2"><?= nl2br(e($rev['comment'])) ?></p>
                                        <div class="small fw-semibold text-dark">
                                            <i class="bi bi-person-check-fill text-danger me-1"></i> <?= e($rev['customer_name']) ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-2">Verified Diner</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Be the first to share your thoughts on this pickle recipe!</p>
                        <?php endif; ?>
                    </div>

                    <!-- Submit Review Form -->
                    <div class="col-lg-5">
                        <div class="card border rounded-4 p-4 bg-light shadow-sm">
                            <h5 class="fw-bold font-heading mb-3">Write a Review</h5>
                            <form action="<?= BASE_URL ?>/product.php?slug=<?= urlencode($slug) ?>" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="submit_review" value="1">

                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Rating</label>
                                    <select name="rating" class="form-select">
                                        <option value="5">⭐⭐⭐⭐⭐ (5/5) - Outstanding Authentic Taste</option>
                                        <option value="4">⭐⭐⭐⭐ (4/5) - Very Good Traditional Flavour</option>
                                        <option value="3">⭐⭐⭐ (3/5) - Average Taste</option>
                                        <option value="2">⭐⭐ (2/5) - Could Be Better</option>
                                        <option value="1">⭐ (1/5) - Disappointed</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Your Name</label>
                                    <input type="text" name="reviewer_name" required class="form-control" value="<?= is_logged_in() ? e(current_user()['name']) : '' ?>" placeholder="e.g. Suman Sharma">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Email Address</label>
                                    <input type="email" name="reviewer_email" class="form-control" value="<?= is_logged_in() ? e(current_user()['email']) : '' ?>" placeholder="e.g. suman@example.com">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Review Headline</label>
                                    <input type="text" name="review_title" class="form-control" placeholder="e.g. Just like Dadi’s kitchen!">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Your Review</label>
                                    <textarea name="review_comment" rows="4" required class="form-control" placeholder="Describe the aroma, crunch, spice level, or how you enjoyed it with parathas/rice..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-brand-primary w-100 py-2">Submit Verified Review</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Related Pickles Section -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="mt-5 pt-3">
            <h3 class="font-heading mb-4">You May Also Relish</h3>
            <div class="row g-4">
                <?php foreach ($relatedProducts as $rel): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <?php render_product_card($rel); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
