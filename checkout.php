<?php
/**
 * Achar Heritage - Secure Checkout Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = db();
$cartId = get_or_create_cart_id();
$isLoggedIn = is_logged_in();
$user = current_user();

// Fetch Cart Items
$stmt = $pdo->prepare("
    SELECT ci.quantity, 
           p.id as product_id, p.name as product_name, p.slug as product_slug, p.main_image, p.sku as product_sku,
           COALESCE(pv.id, NULL) as variant_id,
           COALESCE(pv.price, p.price) as unit_price,
           COALESCE(pv.weight_label, p.weight) as weight_label,
           COALESCE(pv.sku_variant, p.sku) as sku_label,
           COALESCE(pv.stock, p.stock) as available_stock
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    LEFT JOIN product_variants pv ON ci.variant_id = pv.id
    WHERE ci.cart_id = ?
");
$stmt->execute([$cartId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    set_flash('warning', 'Your basket is empty. Please add items before checking out.');
    header('Location: ' . BASE_URL . '/shop.php');
    exit;
}

// Subtotal & Totals
$subtotal = 0.0;
foreach ($cartItems as $item) {
    $subtotal += ($item['quantity'] * (float)$item['unit_price']);
}

$couponDiscount = 0.0;
$appliedCoupon = $_SESSION['applied_coupon'] ?? null;
if ($appliedCoupon && $subtotal > 0) {
    if ($appliedCoupon['type'] === 'percentage') {
        $couponDiscount = ($subtotal * (float)$appliedCoupon['value']) / 100;
    } else {
        $couponDiscount = (float)$appliedCoupon['value'];
    }
    $couponDiscount = min($couponDiscount, $subtotal);
}

$shippingFee = ($subtotal - $couponDiscount >= FREE_SHIPPING_THRESHOLD) ? 0.0 : DEFAULT_SHIPPING_FEE;
$finalTotal = max(0, ($subtotal - $couponDiscount) + $shippingFee);

// Fetch saved addresses if customer is logged in
$savedAddresses = [];
if ($isLoggedIn) {
    $addrStmt = $pdo->prepare("SELECT * FROM addresses WHERE customer_id = ? ORDER BY is_default DESC, id DESC");
    $addrStmt->execute([customer_id()]);
    $savedAddresses = $addrStmt->fetchAll();
}

// Handle Order Placement
$errors = [];
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!verify_csrf()) {
        $errors[] = 'Security verification failed. Please try submitting again.';
    } else {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $mobile = sanitize($_POST['mobile'] ?? '');
        $houseFlat = sanitize($_POST['house_flat'] ?? '');
        $street = sanitize($_POST['street'] ?? '');
        $landmark = sanitize($_POST['landmark'] ?? '');
        $city = sanitize($_POST['city'] ?? '');
        $state = sanitize($_POST['state'] ?? '');
        $pincode = sanitize($_POST['pincode'] ?? '');
        $paymentMethod = in_array($_POST['payment_method'] ?? '', ['cod', 'online']) ? $_POST['payment_method'] : 'cod';
        $saveAddress = !empty($_POST['save_address']);
        $orderNotes = sanitize($_POST['order_notes'] ?? '');

        if (empty($fullName)) $errors[] = 'Full name is required.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email address is required.';
        if (empty($mobile) || strlen($mobile) < 10) $errors[] = 'Valid 10-digit mobile number is required.';
        if (empty($houseFlat) || empty($street)) $errors[] = 'Complete delivery street address is required.';
        if (empty($city)) $errors[] = 'City is required.';
        if (empty($state)) $errors[] = 'State is required.';
        if (empty($pincode) || strlen($pincode) < 6) $errors[] = 'Valid 6-digit Pincode is required.';

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $orderNumber = generate_order_number();
                $shippingAddressFull = $houseFlat . ', ' . $street . ($landmark ? ' (Near ' . $landmark . ')' : '');

                // 1. Insert Order
                $orderStmt = $pdo->prepare("
                    INSERT INTO orders (
                        order_number, customer_id, customer_name, customer_email, customer_mobile,
                        shipping_address, shipping_city, shipping_state, shipping_pincode,
                        subtotal, discount_amount, coupon_code, shipping_fee, total_amount,
                        payment_method, payment_status, order_status, notes
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, 'Confirmed', ?
                    )
                ");

                $paymentStatus = ($paymentMethod === 'online') ? 'paid' : 'pending';
                $couponCode = $appliedCoupon ? $appliedCoupon['code'] : null;

                $orderStmt->execute([
                    $orderNumber,
                    customer_id(),
                    $fullName,
                    $email,
                    $mobile,
                    $shippingAddressFull,
                    $city,
                    $state,
                    $pincode,
                    $subtotal,
                    $couponDiscount,
                    $couponCode,
                    $shippingFee,
                    $finalTotal,
                    $paymentMethod,
                    $paymentStatus,
                    $orderNotes
                ]);
                $orderId = (int)$pdo->lastInsertId();

                // 2. Insert Order Items & Deduct Stock
                $itemInsertStmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, product_name, variant_label, sku, unit_price, quantity, subtotal)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stockDeductStmt = $pdo->prepare("UPDATE products SET stock = MAX(0, stock - ?) WHERE id = ?");
                $variantDeductStmt = $pdo->prepare("UPDATE product_variants SET stock = MAX(0, stock - ?) WHERE id = ?");

                foreach ($cartItems as $item) {
                    $itemSubtotal = $item['quantity'] * (float)$item['unit_price'];
                    $itemInsertStmt->execute([
                        $orderId,
                        $item['product_id'],
                        $item['product_name'],
                        $item['weight_label'],
                        $item['sku_label'],
                        $item['unit_price'],
                        $item['quantity'],
                        $itemSubtotal
                    ]);

                    // Deduct stock
                    $stockDeductStmt->execute([$item['quantity'], $item['product_id']]);
                    if (!empty($item['variant_id'])) {
                        $variantDeductStmt->execute([$item['quantity'], $item['variant_id']]);
                    }
                }

                // 3. Record Coupon Usage if applicable
                if ($appliedCoupon) {
                    $pdo->prepare("INSERT INTO coupon_usage (coupon_id, customer_id, order_id, discount_applied) VALUES (?, ?, ?, ?)")
                        ->execute([$appliedCoupon['id'], customer_id(), $orderId, $couponDiscount]);
                    $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")
                        ->execute([$appliedCoupon['id']]);
                    unset($_SESSION['applied_coupon']);
                }

                // 4. Save Address if requested and logged in
                if ($isLoggedIn && $saveAddress) {
                    $saveAddrStmt = $pdo->prepare("
                        INSERT INTO addresses (customer_id, full_name, mobile, house_flat, street, landmark, city, state, pincode, type)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'home')
                    ");
                    $saveAddrStmt->execute([customer_id(), $fullName, $mobile, $houseFlat, $street, $landmark, $city, $state, $pincode]);
                }

                // 5. If Online Payment, record dummy payment transaction
                if ($paymentMethod === 'online') {
                    $dummyTxnId = 'TXN_' . strtoupper(bin2hex(random_bytes(6)));
                    $payStmt = $pdo->prepare("
                        INSERT INTO payments (order_id, payment_method, transaction_id, amount, status)
                        VALUES (?, 'Online Gateway', ?, ?, 'captured')
                    ");
                    $payStmt->execute([$orderId, $dummyTxnId, $finalTotal]);
                }

                // 6. Clear Cart
                $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cartId]);

                $pdo->commit();

                // Redirect to success page
                header('Location: ' . BASE_URL . '/checkout/success.php?order_number=' . urlencode($orderNumber));
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Order processing error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Secure Checkout - Achar Heritage';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="py-4 bg-white border-bottom">
    <div class="container">
        <h1 class="font-heading display-6 m-0">Express Checkout</h1>
        <p class="text-muted small m-0 mt-1">Provide your delivery details to receive your handcrafted pickle jars.</p>
    </div>
</div>

<div class="container py-5">
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger rounded-4 shadow-sm mb-4">
            <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please fix the following:</h6>
            <ul class="mb-0 small ps-3">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/checkout.php" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="place_order" value="1">

        <div class="row g-5">
            
            <!-- Left: Checkout Steps Form -->
            <div class="col-lg-7">
                
                <!-- STEP 1: Customer Contact -->
                <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="bg-brand-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:30px;height:30px;">1</div>
                        <h5 class="fw-bold font-heading m-0">Contact Information</h5>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Full Name *</label>
                            <input type="text" name="full_name" required class="form-control" 
                                   value="<?= e($_POST['full_name'] ?? ($user['name'] ?? '')) ?>" placeholder="e.g. Ramesh Kumar">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Mobile Phone * (For Delivery SMS)</label>
                            <input type="tel" name="mobile" required class="form-control" 
                                   value="<?= e($_POST['mobile'] ?? ($user['mobile'] ?? '')) ?>" placeholder="10-digit mobile number">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Email Address * (For Order Confirmation)</label>
                            <input type="email" name="email" required class="form-control" 
                                   value="<?= e($_POST['email'] ?? ($user['email'] ?? '')) ?>" placeholder="name@example.com">
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Delivery Address -->
                <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="bg-brand-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:30px;height:30px;">2</div>
                        <h5 class="fw-bold font-heading m-0">Shipping Destination</h5>
                    </div>

                    <?php if (!empty($savedAddresses)): ?>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Select from Saved Addresses:</label>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($savedAddresses as $idx => $addr): ?>
                                    <div class="border rounded-3 p-3 bg-light d-flex align-items-start gap-3">
                                        <input class="form-check-input mt-1" type="radio" name="saved_address_id" id="addr_<?= $addr['id'] ?>" <?= $idx === 0 ? 'checked' : '' ?>
                                               onchange="fillAddress(<?= htmlspecialchars(json_encode($addr), ENT_QUOTES) ?>)">
                                        <label class="form-check-label w-100 cursor-pointer" for="addr_<?= $addr['id'] ?>">
                                            <div class="fw-bold"><?= e($addr['full_name']) ?> <span class="badge bg-secondary small"><?= strtoupper(e($addr['type'])) ?></span></div>
                                            <div class="small text-muted"><?= e($addr['house_flat']) ?>, <?= e($addr['street']) ?></div>
                                            <div class="small text-muted"><?= e($addr['city']) ?>, <?= e($addr['state']) ?> - <?= e($addr['pincode']) ?> | Ph: <?= e($addr['mobile']) ?></div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">House / Flat / Building No. *</label>
                            <input type="text" name="house_flat" id="field_house_flat" required class="form-control" 
                                   value="<?= e($_POST['house_flat'] ?? ($savedAddresses[0]['house_flat'] ?? '')) ?>" placeholder="e.g. Flat 302, Green Residency">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Street / Colony / Area *</label>
                            <input type="text" name="street" id="field_street" required class="form-control" 
                                   value="<?= e($_POST['street'] ?? ($savedAddresses[0]['street'] ?? '')) ?>" placeholder="e.g. 12th Main Road, Gandhi Nagar">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nearby Landmark (Optional)</label>
                            <input type="text" name="landmark" id="field_landmark" class="form-control" 
                                   value="<?= e($_POST['landmark'] ?? ($savedAddresses[0]['landmark'] ?? '')) ?>" placeholder="e.g. Near Shiv Mandir">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Pincode *</label>
                            <input type="text" name="pincode" id="field_pincode" required class="form-control" 
                                   value="<?= e($_POST['pincode'] ?? ($savedAddresses[0]['pincode'] ?? '')) ?>" placeholder="6-digit Pincode">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">City *</label>
                            <input type="text" name="city" id="field_city" required class="form-control" 
                                   value="<?= e($_POST['city'] ?? ($savedAddresses[0]['city'] ?? '')) ?>" placeholder="City">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">State *</label>
                            <input type="text" name="state" id="field_state" required class="form-control" 
                                   value="<?= e($_POST['state'] ?? ($savedAddresses[0]['state'] ?? '')) ?>" placeholder="State">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Special Delivery Note (Optional)</label>
                            <input type="text" name="order_notes" class="form-control" placeholder="e.g. Please ring doorbell twice, leave at gate if absent">
                        </div>
                        <?php if ($isLoggedIn): ?>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="save_address" value="1" id="saveAddressCheck" checked>
                                    <label class="form-check-label small" for="saveAddressCheck">
                                        Save this address to my profile for future orders
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- STEP 3: Payment Method -->
                <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="bg-brand-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:30px;height:30px;">3</div>
                        <h5 class="fw-bold font-heading m-0">Payment Options</h5>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <!-- Cash on Delivery -->
                        <div class="border rounded-3 p-3 bg-light d-flex align-items-start gap-3">
                            <input class="form-check-input mt-1" type="radio" name="payment_method" id="pay_cod" value="cod" checked>
                            <label class="form-check-label w-100 cursor-pointer" for="pay_cod">
                                <div class="fw-bold d-flex align-items-center gap-2">
                                    <i class="bi bi-cash-stack text-brand-primary fs-5"></i> Cash on Delivery (COD)
                                </div>
                                <div class="small text-muted">Pay securely with cash or UPI QR code when our courier partner arrives.</div>
                            </label>
                        </div>

                        <!-- Online Payment -->
                        <div class="border rounded-3 p-3 bg-light d-flex align-items-start gap-3">
                            <input class="form-check-input mt-1" type="radio" name="payment_method" id="pay_online" value="online">
                            <label class="form-check-label w-100 cursor-pointer" for="pay_online">
                                <div class="fw-bold d-flex align-items-center gap-2">
                                    <i class="bi bi-credit-card-2-front text-primary fs-5"></i> Instant Online Payment (Razorpay / UPI / Cards)
                                </div>
                                <div class="small text-muted">Google Pay, PhonePe, Paytm UPI, Debit/Credit Cards & NetBanking (Instant Confirmation).</div>
                            </label>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right: Order Items & Grand Total -->
            <div class="col-lg-5">
                <div class="card border-0 rounded-4 shadow-sm p-4 bg-white position-sticky" style="top: 100px;">
                    <h5 class="fw-bold font-heading mb-3">Order Summary (<?= count($cartItems) ?> Items)</h5>
                    
                    <div class="d-flex flex-column gap-3 mb-4" style="max-height: 260px; overflow-y: auto;">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="d-flex align-items-center gap-3 pb-2 border-bottom">
                                <img src="<?= BASE_URL ?>/uploads/products/<?= e($item['main_image']) ?>" class="rounded-3 border" style="width: 50px; height: 50px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <div class="fw-bold small text-dark"><?= e($item['product_name']) ?></div>
                                    <div class="text-muted" style="font-size:0.75rem"><?= e($item['weight_label']) ?> × <?= (int)$item['quantity'] ?></div>
                                </div>
                                <div class="fw-bold small"><?= format_price($item['quantity'] * $item['unit_price']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Subtotal</span>
                        <span class="fw-semibold"><?= format_price($subtotal) ?></span>
                    </div>

                    <?php if ($couponDiscount > 0): ?>
                        <div class="d-flex justify-content-between mb-2 small text-success">
                            <span>Voucher Discount (<?= e($appliedCoupon['code']) ?>)</span>
                            <span class="fw-bold">-<?= format_price($couponDiscount) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mb-3 small">
                        <span class="text-muted">Courier Delivery Fee</span>
                        <span class="fw-semibold">
                            <?= $shippingFee == 0 ? '<span class="text-success fw-bold">FREE</span>' : format_price($shippingFee) ?>
                        </span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-baseline mb-4">
                        <span class="fs-5 fw-bold font-heading">Total Amount</span>
                        <span class="fs-3 fw-bold text-success"><?= format_price($finalTotal) ?></span>
                    </div>

                    <button type="submit" class="btn btn-brand-primary btn-lg w-100 py-3 rounded-pill shadow fw-bold">
                        <i class="bi bi-bag-check-fill me-2"></i> Confirm & Place Order
                    </button>

                    <div class="text-center text-muted small mt-3">
                        <i class="bi bi-shield-lock-fill text-success me-1"></i> 256-Bit SSL Encrypted & Protected
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
function fillAddress(addr) {
    document.getElementById('field_house_flat').value = addr.house_flat || '';
    document.getElementById('field_street').value = addr.street || '';
    document.getElementById('field_landmark').value = addr.landmark || '';
    document.getElementById('field_city').value = addr.city || '';
    document.getElementById('field_state').value = addr.state || '';
    document.getElementById('field_pincode').value = addr.pincode || '';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
