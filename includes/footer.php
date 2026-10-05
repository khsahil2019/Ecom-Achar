<?php
/**
 * Master Footer Component - Amazon-Style Gourmet Red & White Architecture
 */
$whatsappNumber = get_setting('whatsapp_number', DEFAULT_WHATSAPP);
$phone = get_setting('contact_phone', DEFAULT_PHONE);
$email = get_setting('contact_email', DEFAULT_EMAIL);
$address = get_setting('business_address', DEFAULT_ADDRESS);
$gst = get_setting('gst_number', '08AAAAA0000A1Z5');
?>

<!-- Toast Notification Live Popover Container -->
<div class="toast-container-custom" id="toastContainer"></div>

<!-- Floating WhatsApp Quick Support Floating Widget -->
<a href="https://wa.me/<?= e($whatsappNumber) ?>?text=<?= urlencode('Namaste! I would like to enquire about Achar Heritage authentic pickles.') ?>" 
   class="floating-whatsapp" target="_blank" rel="noopener noreferrer" title="Chat with Culinary Experts on WhatsApp">
    <i class="bi bi-whatsapp"></i>
</a>

<!-- ==========================================================================
     AMAZON-STYLE FOOTER ARCHITECTURE
     ========================================================================== -->
<footer class="amz-footer mt-auto">
    
    <!-- 1. Amazon "Back to top" Full-Width Action -->
    <button type="button" class="amz-back-to-top" onclick="window.scrollTo({ top: 0, behavior: 'smooth' });" aria-label="Back to Top">
        Back to top
    </button>

    <!-- 2. Amazon 4-Column Directory -->
    <div class="amz-footer-main">
        <div class="container-fluid px-lg-5">
            <div class="row g-4 justify-content-between">
                
                <!-- Col 1: Get to Know Us -->
                <div class="col-6 col-md-3 amz-footer-col">
                    <h5>Get to Know Us</h5>
                    <ul class="amz-footer-links">
                        <li><a href="<?= BASE_URL ?>/about.php">About Achar Heritage</a></li>
                        <li><a href="<?= BASE_URL ?>/about.php#curing">Traditional 21-Day Sun Curing</a></li>
                        <li><a href="<?= BASE_URL ?>/about.php#ingredients">Cold-Pressed Mustard Oil</a></li>
                        <li><a href="<?= BASE_URL ?>/about.php#purity">Zero Chemical Guarantee</a></li>
                        <li><a href="<?= BASE_URL ?>/shop.php?category=royal-combos">Shahi Gift Collections</a></li>
                        <li><a href="<?= BASE_URL ?>/about.php">Our Heritage Since 1968</a></li>
                    </ul>
                </div>

                <!-- Col 2: Connect with Us -->
                <div class="col-6 col-md-3 amz-footer-col">
                    <h5>Connect with Us</h5>
                    <ul class="amz-footer-links">
                        <li><a href="https://wa.me/<?= e($whatsappNumber) ?>" target="_blank"><i class="bi bi-whatsapp text-warning me-1"></i> WhatsApp Support</a></li>
                        <li><a href="<?= e(get_setting('facebook_url', '#')) ?>" target="_blank"><i class="bi bi-facebook me-1"></i> Facebook Community</a></li>
                        <li><a href="<?= e(get_setting('instagram_url', '#')) ?>" target="_blank"><i class="bi bi-instagram me-1"></i> Instagram Kitchen</a></li>
                        <li><a href="<?= e(get_setting('youtube_url', '#')) ?>" target="_blank"><i class="bi bi-youtube me-1"></i> YouTube Masterclasses</a></li>
                        <li><a href="<?= BASE_URL ?>/contact.php">Culinary Hotline & Help</a></li>
                    </ul>
                </div>

                <!-- Col 3: Make Money with Us -->
                <div class="col-6 col-md-3 amz-footer-col">
                    <h5>Make Money with Us</h5>
                    <ul class="amz-footer-links">
                        <li><a href="<?= BASE_URL ?>/contact.php?subject=supply">Supply Farm Produce</a></li>
                        <li><a href="<?= BASE_URL ?>/contact.php?subject=artisan">Join as Cottage Artisan</a></li>
                        <li><a href="<?= BASE_URL ?>/contact.php?subject=b2b">Bulk & Corporate Gifting</a></li>
                        <li><a href="<?= BASE_URL ?>/contact.php?subject=affiliate">Become an Affiliate Partner</a></li>
                        <li><a href="<?= BASE_URL ?>/contact.php?subject=wholesale">Wholesale & Horeca Supply</a></li>
                        <li><a href="<?= BASE_URL ?>/admin/login.php">Vendor & Admin Portal</a></li>
                    </ul>
                </div>

                <!-- Col 4: Let Us Help You -->
                <div class="col-6 col-md-3 amz-footer-col">
                    <h5>Let Us Help You</h5>
                    <ul class="amz-footer-links">
                        <li><a href="<?= BASE_URL ?>/account/dashboard.php">Your Account</a></li>
                        <li><a href="<?= BASE_URL ?>/account/orders.php">Returns & Orders</a></li>
                        <li><a href="<?= BASE_URL ?>/account/orders.php">Track Your Shipment</a></li>
                        <li><a href="<?= BASE_URL ?>/policies.php?p=shipping">Shipping Rates & Policies</a></li>
                        <li><a href="<?= BASE_URL ?>/policies.php?p=returns">Return & Refund Policy</a></li>
                        <li><a href="<?= BASE_URL ?>/policies.php?p=privacy">Privacy Notice & Security</a></li>
                        <li><a href="<?= BASE_URL ?>/contact.php">Help & Grievance Cell</a></li>
                    </ul>
                </div>

            </div>
        </div>
    </div>

    <!-- 3. Amazon Middle Bar: Logo, Language, Country -->
    <div class="amz-footer-mid text-center">
        <div class="container d-flex flex-wrap justify-content-center align-items-center gap-3">
            <a href="<?= BASE_URL ?>/index.php" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none">
                <div class="brand-logo-badge" style="width:34px;height:34px;font-size:1.1rem;">
                    <i class="bi bi-fire"></i>
                </div>
                <span class="font-heading fw-bold fs-5 text-white">Achar Heritage<span class="text-warning small fs-6">.in</span></span>
            </a>

            <div class="d-flex align-items-center gap-2 ms-sm-4">
                <a href="#" class="amz-footer-pill" onclick="event.preventDefault();">
                    <i class="bi bi-globe2"></i> English
                </a>
                <a href="#" class="amz-footer-pill" onclick="event.preventDefault();">
                    🇮🇳 India
                </a>
                <span class="text-muted small ps-2">GSTIN: <strong><?= e($gst) ?></strong></span>
            </div>
        </div>
    </div>

    <!-- 4. Amazon Exclusive Sub-Services Grid -->
    <div class="amz-footer-services-grid">
        <div class="container-fluid px-lg-5">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <a href="<?= BASE_URL ?>/policies.php?p=shipping" class="amz-service-link">
                        <span class="amz-service-title">Achar Prime</span>
                        <span class="amz-service-desc">Fast 24-48hr Dispatch<br>Tamper-proof Glass Jars</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="<?= BASE_URL ?>/shop.php?category=royal-combos" class="amz-service-link">
                        <span class="amz-service-title">Shahi Tasting Club</span>
                        <span class="amz-service-desc">Curated Seasonal Trio Jars<br>Special Festival Packs</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="<?= BASE_URL ?>/about.php" class="amz-service-link">
                        <span class="amz-service-title">Heritage Pantry</span>
                        <span class="amz-service-desc">Pure Kachi Ghani Mustard Oil<br>Stoneground Degi Mirch</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="<?= BASE_URL ?>/contact.php?subject=b2b" class="amz-service-link">
                        <span class="amz-service-title">Bulk & B2B Gifting</span>
                        <span class="amz-service-desc">Custom Branding Labels<br>Weddings & Restaurants</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Bottom Legal & Copyright -->
    <div class="amz-footer-bottom">
        <div class="container">
            <div class="mb-2">
                <a href="<?= BASE_URL ?>/policies.php?p=terms">Conditions of Use & Sale</a>
                <a href="<?= BASE_URL ?>/policies.php?p=privacy">Privacy Notice</a>
                <a href="<?= BASE_URL ?>/policies.php?p=shipping">Shipping Policy</a>
                <a href="<?= BASE_URL ?>/policies.php?p=returns">Returns & Refunds</a>
            </div>
            <div>
                &copy; <?= date('Y') ?> <strong><?= e(get_setting('site_name', 'Achar Heritage')) ?>, Inc.</strong> or its affiliates. Handcrafted with traditional love in India.
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Master JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js?v=1.1"></script>
</body>
</html>
