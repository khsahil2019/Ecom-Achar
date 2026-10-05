<?php
/**
 * Achar Heritage - Shopping Cart Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = db();
$cartId = get_or_create_cart_id();

// Handle "Buy Now" POST redirection
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buy_now') {
    if (verify_csrf()) {
        $productId = (int)($_POST['product_id'] ?? 0);
        $variantId = !empty($_POST['variant_id']) ? (int)$_POST['variant_id'] : null;
        $qty = max(1, (int)($_POST['quantity'] ?? 1));

        if ($productId) {
            // Check if already in cart
            if ($variantId) {
                $check = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? AND variant_id = ?");
                $check->execute([$cartId, $productId, $variantId]);
            } else {
                $check = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? AND variant_id IS NULL");
                $check->execute([$cartId, $productId]);
            }
            $existing = $check->fetch();

            if ($existing) {
                $pdo->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE id = ?")->execute([$qty, $existing['id']]);
            } else {
                $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)")->execute([$cartId, $productId, $variantId, $qty]);
            }

            header('Location: ' . BASE_URL . '/checkout.php');
            exit;
        }
    }
}

// Fetch Cart Items
$stmt = $pdo->prepare("
    SELECT ci.id as item_id, ci.quantity, 
           p.id as product_id, p.name as product_name, p.slug as product_slug, p.main_image, p.sku as product_sku,
           COALESCE(pv.price, p.price) as unit_price,
           COALESCE(pv.mrp, p.mrp) as unit_mrp,
           COALESCE(pv.weight_label, p.weight) as weight_label,
           COALESCE(pv.sku_variant, p.sku) as sku_label,
           COALESCE(pv.stock, p.stock) as available_stock
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    LEFT JOIN product_variants pv ON ci.variant_id = pv.id
    WHERE ci.cart_id = ?
    ORDER BY ci.id DESC
");
$stmt->execute([$cartId]);
$cartItems = $stmt->fetchAll();

// Calculations
$subtotal = 0.0;
foreach ($cartItems as $item) {
    $subtotal += ($item['quantity'] * (float)$item['unit_price']);
}

// Check Applied Coupon
$couponDiscount = 0.0;
$appliedCoupon = $_SESSION['applied_coupon'] ?? null;
if ($appliedCoupon && $subtotal > 0) {
    // Recalculate discount
    if ($appliedCoupon['type'] === 'percentage') {
        $couponDiscount = ($subtotal * (float)$appliedCoupon['value']) / 100;
    } else {
        $couponDiscount = (float)$appliedCoupon['value'];
    }
    $couponDiscount = min($couponDiscount, $subtotal);
} else {
    unset($_SESSION['applied_coupon']);
    $appliedCoupon = null;
}

$shippingFee = ($subtotal - $couponDiscount >= FREE_SHIPPING_THRESHOLD || $subtotal == 0) ? 0.0 : DEFAULT_SHIPPING_FEE;
$finalTotal = max(0, ($subtotal - $couponDiscount) + $shippingFee);
$freeShippingDiff = max(0, FREE_SHIPPING_THRESHOLD - $subtotal);

$pageTitle = 'Your Pickle Basket - Achar Heritage';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="py-4 bg-white border-bottom">
    <div class="container">
        <h1 class="font-heading display-6 m-0">Your Shopping Basket</h1>
        <p class="text-muted small m-0 mt-1">Review your authentic pickles, adjust quantities, and apply discount vouchers.</p>
    </div>
</div>

<div class="container py-5">
    <?= render_flash() ?>

    <?php if (!empty($cartItems)): ?>
        
        <!-- Free Shipping Progress Alert -->
        <?php if ($freeShippingDiff > 0): ?>
            <div class="alert alert-warning border-0 rounded-4 shadow-sm d-flex align-items-center justify-content-between p-3 mb-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-truck fs-4 text-warning"></i>
                    <span>Add <strong><?= format_price($freeShippingDiff) ?></strong> more to unlock <strong>FREE Delivery</strong>!</span>
                </div>
                <a href="<?= BASE_URL ?>/shop.php" class="btn btn-sm btn-dark rounded-pill px-3">Add More Pickles</a>
            </div>
        <?php else: ?>
            <div class="alert border-0 rounded-4 shadow-sm d-flex align-items-center gap-2 p-3 mb-4" style="background: #FFF1F2; color: #C5161D; border: 1.5px solid #FECDCA !important;">
                <i class="bi bi-patch-check-fill fs-5" style="color: #C5161D;"></i>
                <span class="fw-bold">Yay! You have qualified for FREE Pan-India Delivery!</span>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <!-- Left: Items Table -->
            <div class="col-lg-8">
                <div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" style="min-width: 250px;">Pickle Variety</th>
                                    <th scope="col">Price</th>
                                    <th scope="col" style="width: 140px;">Quantity</th>
                                    <th scope="col">Subtotal</th>
                                    <th scope="col" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): 
                                    $itemSubtotal = $item['quantity'] * (float)$item['unit_price'];
                                ?>
                                    <tr id="cartRow_<?= $item['item_id'] ?>">
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <a href="<?= BASE_URL ?>/product.php?slug=<?= urlencode($item['product_slug']) ?>">
                                                    <img src="<?= BASE_URL ?>/uploads/products/<?= e($item['main_image']) ?>" alt="<?= e($item['product_name']) ?>" class="rounded-3 border" style="width: 64px; height: 64px; object-fit: cover;">
                                                </a>
                                                <div>
                                                    <a href="<?= BASE_URL ?>/product.php?slug=<?= urlencode($item['product_slug']) ?>" class="fw-bold text-dark text-decoration-none d-block">
                                                        <?= e($item['product_name']) ?>
                                                    </a>
                                                    <span class="badge bg-light text-muted border small mt-1">
                                                        Size: <?= e($item['weight_label']) ?>
                                                    </span>
                                                    <div class="text-muted small">SKU: <?= e($item['sku_label']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= format_price($item['unit_price']) ?></div>
                                            <?php if ($item['unit_mrp'] > $item['unit_price']): ?>
                                                <small class="text-muted text-decoration-line-through"><?= format_price($item['unit_mrp']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="quantity-selector d-flex align-items-center border rounded-pill bg-white px-2 py-1 shadow-sm" style="max-width: 110px;">
                                                <button type="button" class="btn btn-sm btn-link text-dark qty-btn qty-minus p-0 px-2 text-decoration-none">-</button>
                                                <input type="number" 
                                                       class="form-control form-control-sm text-center border-0 qty-input p-0 fw-bold" 
                                                       value="<?= (int)$item['quantity'] ?>" 
                                                       min="1" max="99" 
                                                       onchange="updateCartItem(<?= $item['item_id'] ?>, this.value)">
                                                <button type="button" class="btn btn-sm btn-link text-dark qty-btn qty-plus p-0 px-2 text-decoration-none">+</button>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-success fs-6"><?= format_price($itemSubtotal) ?></div>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger rounded-circle border-0" 
                                                    onclick="removeCartItem(<?= $item['item_id'] ?>)" 
                                                    title="Remove item">
                                                <i class="bi bi-trash fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/shop.php" class="btn btn-outline-brand rounded-pill">
                            <i class="bi bi-arrow-left me-1"></i> Continue Shopping
                        </a>
                        <button type="button" class="btn btn-outline-secondary rounded-pill" onclick="window.location.reload();">
                            <i class="bi bi-arrow-clockwise me-1"></i> Refresh Basket
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right: Order Summary & Coupon -->
            <div class="col-lg-4">
                
                <!-- Coupon Box -->
                <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                    <h5 class="fw-bold font-heading mb-3">Promotional Voucher</h5>
                    <?php if ($appliedCoupon): ?>
                        <div class="alert alert-success d-flex justify-content-between align-items-center p-2 rounded-3 mb-0">
                            <div>
                                <i class="bi bi-ticket-perforated-fill me-1"></i>
                                <strong><?= e($appliedCoupon['code']) ?></strong> applied
                                <div class="small text-muted">You save <?= format_price($couponDiscount) ?></div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeCoupon()">
                                <i class="bi bi-x-circle-fill fs-5"></i>
                            </button>
                        </div>
                    <?php else: ?>
                        <form id="couponForm" onsubmit="applyCoupon(event)" class="d-flex gap-2">
                            <input type="text" id="couponCodeInput" class="form-control text-uppercase" placeholder="e.g. WELCOME10" required>
                            <button type="submit" class="btn btn-brand-primary text-nowrap px-3">Apply</button>
                        </form>
                        <small class="text-muted d-block mt-2">Available: <strong>WELCOME10</strong> (10% off) or <strong>FESTIVE100</strong> (₹100 off on ₹499+)</small>
                    <?php endif; ?>
                </div>

                <!-- Price Summary Card -->
                <div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
                    <h5 class="fw-bold font-heading mb-3">Order Summary</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Pickles Subtotal</span>
                        <span class="fw-semibold"><?= format_price($subtotal) ?></span>
                    </div>

                    <?php if ($couponDiscount > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Coupon Discount (<?= e($appliedCoupon['code']) ?>)</span>
                            <span class="fw-bold">-<?= format_price($couponDiscount) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Estimated Delivery Fee</span>
                        <span class="fw-semibold">
                            <?= $shippingFee == 0 ? '<span class="text-success fw-bold">FREE</span>' : format_price($shippingFee) ?>
                        </span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-baseline mb-4">
                        <span class="fs-5 fw-bold font-heading">Grand Total</span>
                        <span class="fs-3 fw-bold text-success"><?= format_price($finalTotal) ?></span>
                    </div>

                    <a href="<?= BASE_URL ?>/checkout.php" class="btn btn-brand-accent btn-lg w-100 py-3 d-flex align-items-center justify-content-center gap-2 rounded-pill shadow">
                        <i class="bi bi-lock-fill"></i> Proceed to Checkout
                    </a>

                    <div class="mt-4 text-center text-muted small">
                        <div class="d-flex justify-content-center gap-3 mb-2">
                            <span><i class="bi bi-shield-check text-success me-1"></i> 100% Secure Checkout</span>
                            <span>•</span>
                            <span><i class="bi bi-truck text-primary me-1"></i> Express Courier</span>
                        </div>
                        <p class="m-0 text-muted" style="font-size: 0.78rem;">We accept Cash on Delivery, UPI, Cards, and Net Banking.</p>
                    </div>
                </div>

            </div>

        </div>

    <?php else: ?>
        <!-- Empty Basket -->
        <div class="text-center py-5 bg-white rounded-4 shadow-sm p-5 my-4">
            <div class="bg-light rounded-circle mx-auto mb-4 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                <i class="bi bi-bag-x display-4 text-muted"></i>
            </div>
            <h3 class="font-heading mb-2">Your Basket is Empty</h3>
            <p class="text-muted mb-4" style="max-width: 450px; margin: 0 auto;">
                You haven't added any authentic pickles to your basket yet. Taste grandma’s heritage recipes today!
            </p>
            <a href="<?= BASE_URL ?>/shop.php" class="btn btn-brand-primary btn-lg px-5 rounded-pill">
                <i class="bi bi-bag-check me-2"></i> Explore Our Pickles
            </a>
        </div>
    <?php endif; ?>

</div>

<script>
function updateCartItem(itemId, qty) {
    const formData = new FormData();
    formData.append('item_id', itemId);
    formData.append('quantity', qty);

    fetch('<?= BASE_URL ?>/ajax/cart.php?action=update', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            showToast(data.message || 'Error updating item.', 'error');
        }
    });
}

function removeCartItem(itemId) {
    if (!confirm('Are you sure you want to remove this pickle jar from your basket?')) return;

    const formData = new FormData();
    formData.append('item_id', itemId);

    fetch('<?= BASE_URL ?>/ajax/cart.php?action=remove', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            showToast(data.message || 'Error removing item.', 'error');
        }
    });
}

function applyCoupon(e) {
    e.preventDefault();
    const code = document.getElementById('couponCodeInput').value.trim();
    if (!code) return;

    const formData = new FormData();
    formData.append('code', code);

    fetch('<?= BASE_URL ?>/ajax/coupon.php?action=apply', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 800);
        } else {
            showToast(data.message || 'Invalid coupon.', 'error');
        }
    });
}

function removeCoupon() {
    fetch('<?= BASE_URL ?>/ajax/coupon.php?action=remove', {
        method: 'POST'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'info');
            setTimeout(() => window.location.reload(), 600);
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
