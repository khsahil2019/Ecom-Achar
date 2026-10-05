<?php
/**
 * Reusable Product Card Component
 * Expects $product array
 */

if (!function_exists('render_product_card')) {
    function render_product_card(array $product): void {
        $imgUrl = BASE_URL . '/uploads/products/' . e($product['main_image']);
        $productUrl = BASE_URL . '/product.php?slug=' . urlencode($product['slug']);
        $discount = (int)$product['discount_percent'];
        $rating = number_format((float)($product['rating_cache'] ?? 5.0), 1);
        $reviewsCount = (int)($product['reviews_count'] ?? 0);
        $weight = e($product['weight'] ?? '500g');
        ?>
        <div class="product-card">
            <!-- Thumbnail Wrapper -->
            <div class="product-thumb-wrapper">
                <a href="<?= $productUrl ?>">
                    <img src="<?= $imgUrl ?>" alt="<?= e($product['name']) ?>" class="product-thumb" loading="lazy">
                </a>
                
                <!-- Badges -->
                <div class="product-badges">
                    <?php if (!empty($product['is_bestseller'])): ?>
                        <span class="badge-bestseller"><i class="bi bi-star-fill text-dark me-1"></i> Best Seller</span>
                    <?php elseif (!empty($product['is_new'])): ?>
                        <span class="badge-festive"><i class="bi bi-sparkles me-1"></i> New Arrival</span>
                    <?php endif; ?>
                    
                    <?php if ($discount > 0): ?>
                        <span class="badge-festive"><?= $discount ?>% OFF</span>
                    <?php endif; ?>
                </div>

                <!-- Wishlist Toggle -->
                <button type="button" class="product-wishlist-btn btn-toggle-wishlist" data-product-id="<?= (int)$product['id'] ?>" title="Save to Wishlist">
                    <i class="bi bi-heart"></i>
                </button>
            </div>

            <!-- Content Area -->
            <div class="product-content">
                <div class="product-category-tag"><?= e($product['category_name'] ?? 'Pickle') ?> • <?= $weight ?></div>
                <h3 class="product-title">
                    <a href="<?= $productUrl ?>"><?= e($product['name']) ?></a>
                </h3>

                <!-- Rating -->
                <div class="product-rating">
                    <i class="bi bi-star-fill"></i>
                    <span class="fw-bold text-dark"><?= $rating ?></span>
                    <span class="rating-count">(<?= $reviewsCount ?>)</span>
                </div>

                <!-- Pricing -->
                <div class="product-pricing">
                    <span class="price-current"><?= format_price($product['price']) ?></span>
                    <?php if ($product['mrp'] > $product['price']): ?>
                        <span class="price-mrp"><?= format_price($product['mrp']) ?></span>
                        <span class="price-discount"><?= $discount ?>% off</span>
                    <?php endif; ?>
                </div>

                <!-- Action Button -->
                <div class="d-grid mt-2">
                    <button type="button" 
                            class="btn btn-brand-primary btn-sm btn-add-to-cart d-flex align-items-center justify-content-center gap-2"
                            data-product-id="<?= (int)$product['id'] ?>"
                            data-quantity="1">
                        <i class="bi bi-bag-plus"></i> Add to Cart
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}
