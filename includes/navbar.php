<?php
/**
 * Master Navigation Component - Amazon-Style Gourmet Red & White Architecture
 */
$cartCount = get_cart_count();
$wishlistCount = get_wishlist_count();
$isLoggedIn = is_logged_in();
$user = current_user();

// Fetch active categories for dropdown & subnav
try {
    $navCatStmt = db()->query("SELECT name, slug FROM categories WHERE status = 'active' ORDER BY display_order ASC");
    $navCategories = $navCatStmt->fetchAll();
} catch (Exception $e) {
    $navCategories = [];
}

$currentPincode = $_SESSION['delivery_pincode'] ?? 'New Delhi 110001';
?>

<!-- Announcement Strip -->
<div class="announcement-bar text-center py-1">
    <div class="container-fluid px-lg-4 d-flex justify-content-between align-items-center">
        <span class="d-none d-md-inline small">
            <i class="bi bi-patch-check-fill text-warning me-1"></i> 100% Sun-Cured in Cold-Pressed Mustard Oil • Zero Added Chemicals
        </span>
        <span class="mx-auto mx-md-0 small fw-semibold">
            Use code <span class="badge bg-warning text-dark px-2 py-1">WELCOME10</span> for 10% OFF | Free Shipping above <?= format_price(FREE_SHIPPING_THRESHOLD) ?>
        </span>
        <span class="d-none d-lg-inline small">
            <a href="https://wa.me/<?= e(get_setting('whatsapp_number', DEFAULT_WHATSAPP)) ?>" target="_blank" class="text-white text-decoration-none">
                <i class="bi bi-whatsapp text-warning me-1"></i> WhatsApp: <?= e(get_setting('contact_phone', DEFAULT_PHONE)) ?>
            </a>
        </span>
    </div>
</div>

<!-- Amazon-Style Main Red Navigation Header -->
<header class="amz-header">
    <div class="container-fluid px-lg-4 py-2">
        <div class="row align-items-center g-2 g-lg-3">
            
            <!-- 1. Left: Mobile Drawer Trigger + Brand Logo -->
            <div class="col-auto d-flex align-items-center gap-2">
                <button class="btn btn-outline-light d-lg-none p-1 border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#amazonOffcanvas" aria-controls="amazonOffcanvas" aria-label="Open Navigation Menu">
                    <i class="bi bi-list fs-2 text-white"></i>
                </button>
                
                <a href="<?= BASE_URL ?>/index.php" class="amz-nav-box d-flex align-items-center gap-2 text-white">
                    <div class="brand-logo-badge" style="width:38px;height:38px;font-size:1.25rem;">
                        <i class="bi bi-fire"></i>
                    </div>
                    <div>
                        <div class="brand-title text-white fs-4 leading-none" style="letter-spacing:-0.5px;">Achar Heritage<span class="text-warning small" style="font-size:0.75rem;">.in</span></div>
                        <div class="small text-uppercase text-light opacity-75" style="font-size:0.65rem;letter-spacing:1px;margin-top:-2px;">Pure Artisanal</div>
                    </div>
                </a>
            </div>

            <!-- 2. Deliver To Location Widget (Amazon Style) -->
            <div class="col-auto d-none d-xl-block">
                <div class="amz-nav-box amz-deliver-box" data-bs-toggle="modal" data-bs-target="#pincodeModal" title="Click to change delivery pincode">
                    <i class="bi bi-geo-alt amz-deliver-icon"></i>
                    <div>
                        <span class="amz-nav-line-1">Deliver to <?= $isLoggedIn ? e(explode(' ', $user['name'])[0]) : 'India' ?></span>
                        <span class="amz-nav-line-2" id="navPincodeDisplay"><?= e($currentPincode) ?></span>
                    </div>
                </div>
            </div>

            <!-- 3. Center: Amazon Mega Search Bar -->
            <div class="col">
                <div class="live-search-wrapper">
                    <form action="<?= BASE_URL ?>/shop.php" method="GET" class="amz-search-form">
                        <!-- Category Department Filter -->
                        <select name="category" class="amz-search-cat-select d-none d-md-block" aria-label="Select Category">
                            <option value="">All Pickles</option>
                            <?php foreach ($navCategories as $cat): ?>
                                <option value="<?= e($cat['slug']) ?>" <?= (isset($_GET['category']) && $_GET['category'] === $cat['slug']) ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Live Search Input -->
                        <input type="text" name="search" class="amz-search-input-field live-search-input" 
                               placeholder="Search authentic mango, lemon, garlic, stuffed chilli pickles..." 
                               value="<?= e($_GET['search'] ?? '') ?>" autocomplete="off" aria-label="Search Pickles">
                        
                        <!-- Search Submit Button -->
                        <button type="submit" class="amz-search-btn" title="Search Catalogue">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>

                    <!-- AJAX Results Popover -->
                    <div class="search-results-dropdown shadow-lg"></div>
                </div>
            </div>

            <!-- 4. Right: Language, Account & Lists, Orders, Cart -->
            <div class="col-auto d-flex align-items-center gap-1 gap-sm-2">
                
                <!-- Regional Language Indicator -->
                <div class="dropdown d-none d-lg-block">
                    <button class="amz-nav-box bg-transparent border-0 d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="fs-6">🇮🇳</span>
                        <span class="amz-nav-line-2">EN</span>
                        <i class="bi bi-caret-down-fill text-light opacity-50" style="font-size:0.6rem;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2" style="font-size:0.85rem;">
                        <li><h6 class="dropdown-header">Language Preferences</h6></li>
                        <li><a class="dropdown-item active" href="#"><i class="bi bi-check2 text-danger me-1"></i> English - EN</a></li>
                        <li><a class="dropdown-item" href="#">हिन्दी - HI</a></li>
                    </ul>
                </div>

                <!-- Account & Lists Dropdown (Amazon Signature) -->
                <div class="dropdown">
                    <button class="amz-nav-box bg-transparent border-0 text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="amz-nav-line-1">
                            Hello, <?= $isLoggedIn ? e(explode(' ', $user['name'])[0]) : 'sign in' ?>
                        </span>
                        <span class="amz-nav-line-2">
                            Account & Lists <i class="bi bi-caret-down-fill text-light opacity-50" style="font-size:0.6rem;"></i>
                        </span>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 amz-account-dropdown">
                        <?php if (!$isLoggedIn): ?>
                            <!-- Non-logged in Call To Action -->
                            <div class="text-center pb-3 mb-3 border-bottom">
                                <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-warning w-100 fw-bold py-2 mb-2 shadow-sm rounded-3">
                                    Sign In
                                </a>
                                <div class="small text-muted">
                                    New customer? <a href="<?= BASE_URL ?>/auth/register.php" class="fw-bold text-danger text-decoration-none">Start here.</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Logged In Welcome -->
                            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                                <div>
                                    <div class="fw-bold fs-6 text-dark"><?= e($user['name']) ?></div>
                                    <div class="text-muted small"><?= e($user['email']) ?></div>
                                </div>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Customer Member</span>
                            </div>
                        <?php endif; ?>

                        <!-- 2-Column Directory (Amazon style) -->
                        <div class="row g-3">
                            <!-- Column 1: Your Lists -->
                            <div class="col-6 border-end">
                                <h6 class="fw-bold text-dark fs-6 mb-2">Your Lists</h6>
                                <ul class="list-unstyled d-flex flex-column gap-2 small m-0">
                                    <li><a href="<?= BASE_URL ?>/account/wishlist.php" class="text-decoration-none text-dark hover-underline"><i class="bi bi-heart me-1 text-danger"></i> Saved Wishlist</a></li>
                                    <li><a href="<?= BASE_URL ?>/shop.php?category=royal-combos" class="text-decoration-none text-dark hover-underline"><i class="bi bi-gift me-1 text-warning"></i> Gift Combos</a></li>
                                    <li><a href="<?= BASE_URL ?>/shop.php?filter=bestseller" class="text-decoration-none text-dark hover-underline"><i class="bi bi-stars me-1 text-warning"></i> Explore Best Sellers</a></li>
                                </ul>
                            </div>

                            <!-- Column 2: Your Account -->
                            <div class="col-6">
                                <h6 class="fw-bold text-dark fs-6 mb-2">Your Account</h6>
                                <ul class="list-unstyled d-flex flex-column gap-2 small m-0">
                                    <?php if ($isLoggedIn): ?>
                                        <li><a href="<?= BASE_URL ?>/account/dashboard.php" class="text-decoration-none text-dark hover-underline">Your Account</a></li>
                                        <li><a href="<?= BASE_URL ?>/account/orders.php" class="text-decoration-none text-dark hover-underline">Your Orders</a></li>
                                        <li><a href="<?= BASE_URL ?>/account/addresses.php" class="text-decoration-none text-dark hover-underline">Delivery Addresses</a></li>
                                        <li><a href="<?= BASE_URL ?>/account/profile.php" class="text-decoration-none text-dark hover-underline">Profile & Security</a></li>
                                        <li class="pt-2 border-top"><a href="<?= BASE_URL ?>/auth/logout.php" class="text-danger fw-bold text-decoration-none"><i class="bi bi-box-arrow-right me-1"></i> Sign Out</a></li>
                                    <?php else: ?>
                                        <li><a href="<?= BASE_URL ?>/account/orders.php" class="text-decoration-none text-dark hover-underline">Track Orders</a></li>
                                        <li><a href="<?= BASE_URL ?>/about.php" class="text-decoration-none text-dark hover-underline">About Heritage</a></li>
                                        <li><a href="<?= BASE_URL ?>/contact.php" class="text-decoration-none text-dark hover-underline">Customer Support</a></li>
                                        <li class="pt-2 border-top"><a href="<?= BASE_URL ?>/admin/login.php" class="text-muted small text-decoration-none"><i class="bi bi-shield-lock me-1"></i> Admin Portal</a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Returns & Orders Box -->
                <a href="<?= BASE_URL ?>/account/orders.php" class="amz-nav-box d-none d-md-inline-flex flex-column text-start" title="Returns & Previous Orders">
                    <span class="amz-nav-line-1">Returns</span>
                    <span class="amz-nav-line-2">& Orders</span>
                </a>

                <!-- Amazon Cart Widget -->
                <a href="<?= BASE_URL ?>/cart.php" class="amz-nav-box amz-cart-wrapper" title="Shopping Basket">
                    <div class="amz-cart-icon-wrap">
                        <i class="bi bi-cart3 text-white"></i>
                        <span class="amz-cart-badge cart-count-badge"><?= $cartCount ?></span>
                    </div>
                    <span class="amz-nav-line-2 d-none d-sm-inline ms-1">Cart</span>
                </a>

            </div>

        </div>
    </div>

    <!-- Amazon Subnav Bar (Department Quick Strip) -->
    <nav class="amz-subnav">
        <div class="container-fluid px-lg-4 d-flex align-items-center justify-content-between overflow-x-auto">
            
            <div class="d-flex align-items-center gap-1 flex-nowrap">
                <!-- "All" Hamburger Button -->
                <button type="button" class="amz-subnav-link amz-subnav-all-btn bg-transparent border-0 text-white" data-bs-toggle="offcanvas" data-bs-target="#amazonOffcanvas" aria-controls="amazonOffcanvas">
                    <i class="bi bi-list fs-5"></i> All
                </button>

                <!-- Quick Horizontal Links -->
                <a href="<?= BASE_URL ?>/shop.php?filter=bestseller" class="amz-subnav-link">
                    <i class="bi bi-stars text-warning me-1"></i> Best Sellers
                </a>
                <a href="<?= BASE_URL ?>/shop.php?category=royal-combos" class="amz-subnav-link">
                    <i class="bi bi-gift-fill text-warning me-1"></i> Gift Combos
                </a>
                <a href="<?= BASE_URL ?>/shop.php?category=mango-pickles" class="amz-subnav-link">
                    Mango Specials
                </a>
                <a href="<?= BASE_URL ?>/shop.php?category=lemon-citrus" class="amz-subnav-link">
                    Khatta Lemon
                </a>
                <a href="<?= BASE_URL ?>/shop.php?category=garlic-ginger" class="amz-subnav-link">
                    Garlic & Ginger
                </a>
                <a href="<?= BASE_URL ?>/shop.php?category=fiery-chilli" class="amz-subnav-link">
                    Banarasi Chilli
                </a>
                <a href="<?= BASE_URL ?>/shop.php?sort=latest" class="amz-subnav-link">
                    New Arrivals
                </a>
                <a href="<?= BASE_URL ?>/about.php" class="amz-subnav-link">
                    Grandma's Story
                </a>
                <a href="<?= BASE_URL ?>/contact.php" class="amz-subnav-link">
                    Customer Service
                </a>
            </div>

            <!-- Right Promotional Callout -->
            <div class="d-none d-xl-flex align-items-center gap-2 small text-nowrap">
                <span class="badge bg-warning text-dark fw-bold px-2 py-1">FESTIVE OFFER</span>
                <span class="text-white-50">Free Delivery above ₹499 • 100% Sun-Cured Jars</span>
            </div>

        </div>
    </nav>
</header>

<!-- Amazon-Style "All" Hamburger Offcanvas Drawer -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="amazonOffcanvas" aria-labelledby="amazonOffcanvasLabel" style="max-width: 360px;">
    <!-- Drawer Header -->
    <div class="amz-offcanvas-header d-flex justify-content-between align-items-center">
        <div class="amz-offcanvas-title">
            <i class="bi bi-person-circle fs-3 text-warning"></i>
            <div>
                <div class="fs-6 m-0 leading-none">Hello, <?= $isLoggedIn ? e($user['name']) : 'Sign in' ?></div>
                <div class="small opacity-75 fw-normal" style="font-size:0.75rem;">Achar Heritage Connoisseur</div>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <!-- Drawer Body -->
    <div class="offcanvas-body p-0">
        <!-- Section 1: Trending -->
        <div class="amz-menu-section-header">Trending & Popular</div>
        <a href="<?= BASE_URL ?>/shop.php?filter=bestseller" class="amz-menu-item">
            <span><i class="bi bi-stars text-warning me-2"></i> Best Sellers</span>
            <i class="bi bi-chevron-right text-muted small"></i>
        </a>
        <a href="<?= BASE_URL ?>/shop.php?sort=latest" class="amz-menu-item">
            <span><i class="bi bi-fire text-danger me-2"></i> New Releases</span>
            <i class="bi bi-chevron-right text-muted small"></i>
        </a>
        <a href="<?= BASE_URL ?>/shop.php?category=royal-combos" class="amz-menu-item">
            <span><i class="bi bi-box2-heart text-danger me-2"></i> Shahi Combo Gift Packs</span>
            <i class="bi bi-chevron-right text-muted small"></i>
        </a>

        <hr class="my-2 border-secondary border-opacity-10">

        <!-- Section 2: Shop by Department -->
        <div class="amz-menu-section-header">Shop by Pickle Variety</div>
        <a href="<?= BASE_URL ?>/shop.php" class="amz-menu-item fw-bold text-danger">
            <span>All Authentic Pickles</span>
            <i class="bi bi-chevron-right text-muted small"></i>
        </a>
        <?php foreach ($navCategories as $cat): ?>
            <a href="<?= BASE_URL ?>/shop.php?category=<?= urlencode($cat['slug']) ?>" class="amz-menu-item">
                <span><?= e($cat['name']) ?></span>
                <i class="bi bi-chevron-right text-muted small"></i>
            </a>
        <?php endforeach; ?>

        <hr class="my-2 border-secondary border-opacity-10">

        <!-- Section 3: Programs & Features -->
        <div class="amz-menu-section-header">Programs & Heritage</div>
        <a href="<?= BASE_URL ?>/about.php" class="amz-menu-item">
            <span>21-Day Sun Curing Process</span>
            <i class="bi bi-chevron-right text-muted small"></i>
        </a>
        <a href="<?= BASE_URL ?>/policies.php?p=shipping" class="amz-menu-item">
            <span>Pan-India Safe Delivery</span>
            <i class="bi bi-chevron-right text-muted small"></i>
        </a>
        <a href="<?= BASE_URL ?>/contact.php" class="amz-menu-item">
            <span>Corporate & Bulk Gifting</span>
            <i class="bi bi-chevron-right text-muted small"></i>
        </a>

        <hr class="my-2 border-secondary border-opacity-10">

        <!-- Section 4: Help & Settings -->
        <div class="amz-menu-section-header">Help & Settings</div>
        <?php if ($isLoggedIn): ?>
            <a href="<?= BASE_URL ?>/account/dashboard.php" class="amz-menu-item">
                <span>Your Account</span>
                <i class="bi bi-chevron-right text-muted small"></i>
            </a>
            <a href="<?= BASE_URL ?>/account/orders.php" class="amz-menu-item">
                <span>Your Orders & Tracking</span>
                <i class="bi bi-chevron-right text-muted small"></i>
            </a>
            <a href="<?= BASE_URL ?>/account/wishlist.php" class="amz-menu-item">
                <span>Wishlist</span>
                <i class="bi bi-chevron-right text-muted small"></i>
            </a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="amz-menu-item text-danger fw-bold">
                <span>Sign Out</span>
                <i class="bi bi-box-arrow-right text-danger"></i>
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/auth/login.php" class="amz-menu-item fw-bold text-danger">
                <span>Sign In</span>
                <i class="bi bi-box-arrow-in-right text-danger"></i>
            </a>
            <a href="<?= BASE_URL ?>/contact.php" class="amz-menu-item">
                <span>Customer Service Hotline</span>
                <i class="bi bi-chevron-right text-muted small"></i>
            </a>
        <?php endif; ?>
        <div class="p-3"></div>
    </div>
</div>

<!-- Amazon-Style Delivery Pincode Modal -->
<div class="modal fade" id="pincodeModal" tabindex="-1" aria-labelledby="pincodeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title font-heading fs-6" id="pincodeModalLabel">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i> Choose Your Delivery Location
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">
                    Enter your 6-digit Indian Pincode to view exact delivery speeds, free delivery eligibility, and nearby dispatch centers.
                </p>

                <form onsubmit="handlePincodeUpdate(event);">
                    <div class="input-group mb-3">
                        <input type="text" id="pincodeInput" maxlength="6" pattern="[0-9]{6}" required 
                               class="form-control" placeholder="Enter 6-digit Pincode (e.g. 110001)" value="<?= e(explode(' ', $currentPincode)[1] ?? '110001') ?>">
                        <button type="submit" class="btn btn-brand-primary px-3">Apply</button>
                    </div>
                </form>

                <div class="p-3 bg-light rounded-3 text-muted small">
                    <i class="bi bi-shield-check text-danger me-1"></i> <strong>Pan-India Express:</strong> Dispatches in 24 hours in sealed tamper-proof glass packaging with bubble-wrap lining.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function handlePincodeUpdate(e) {
    e.preventDefault();
    const pin = document.getElementById('pincodeInput').value.trim();
    if (pin.length === 6 && /^\d+$/.test(pin)) {
        const displayText = 'Pincode ' + pin;
        const el = document.getElementById('navPincodeDisplay');
        if (el) el.innerText = displayText;
        
        // Save via fetch or session
        fetch('<?= BASE_URL ?>/ajax/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=update_pincode&pincode=' + encodeURIComponent(displayText) + '&csrf_token=<?= csrf_token() ?>'
        }).catch(() => {});

        const modalEl = document.getElementById('pincodeModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        if (typeof showToast === 'function') {
            showToast('Delivery location updated to ' + displayText, 'success');
        }
    } else {
        alert('Please enter a valid 6-digit Indian Pincode.');
    }
}
</script>
