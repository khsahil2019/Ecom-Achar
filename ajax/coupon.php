<?php
/**
 * AJAX Coupon Validation & Processing
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();
$action = $_GET['action'] ?? $_POST['action'] ?? 'apply';

if ($action === 'remove') {
    unset($_SESSION['applied_coupon']);
    json_response(['success' => true, 'message' => 'Coupon removed.']);
}

$code = strtoupper(trim($_POST['code'] ?? ''));
if (empty($code)) {
    json_response(['success' => false, 'message' => 'Please enter a coupon code.'], 400);
}

// Calculate current cart subtotal
$cartId = get_or_create_cart_id();
$itemsStmt = $pdo->prepare("
    SELECT ci.quantity, COALESCE(pv.price, p.price) as unit_price
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    LEFT JOIN product_variants pv ON ci.variant_id = pv.id
    WHERE ci.cart_id = ?
");
$itemsStmt->execute([$cartId]);
$cartItems = $itemsStmt->fetchAll();

$subtotal = 0.0;
foreach ($cartItems as $item) {
    $subtotal += ($item['quantity'] * (float)$item['unit_price']);
}

if ($subtotal <= 0) {
    json_response(['success' => false, 'message' => 'Your cart is empty.'], 400);
}

// Find coupon
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT * FROM coupons 
    WHERE code = ? AND status = 'active' AND start_date <= ? AND end_date >= ?
    LIMIT 1
");
$stmt->execute([$code, $today, $today]);
$coupon = $stmt->fetch();

if (!$coupon) {
    json_response(['success' => false, 'message' => 'Invalid or expired coupon code.'], 400);
}

if ($coupon['used_count'] >= $coupon['usage_limit']) {
    json_response(['success' => false, 'message' => 'This coupon has reached its maximum usage limit.'], 400);
}

$minOrder = (float)$coupon['min_order_amount'];
if ($subtotal < $minOrder) {
    json_response([
        'success' => false, 
        'message' => 'Minimum order amount for coupon ' . $code . ' is ' . format_price($minOrder) . '. Add more pickles to qualify!'
    ], 400);
}

// Calculate discount amount
$discount = 0.0;
if ($coupon['type'] === 'percentage') {
    $discount = ($subtotal * (float)$coupon['value']) / 100;
    if (!empty($coupon['max_discount_amount'])) {
        $discount = min($discount, (float)$coupon['max_discount_amount']);
    }
} else {
    $discount = (float)$coupon['value'];
}
$discount = min($discount, $subtotal);

$_SESSION['applied_coupon'] = [
    'id' => (int)$coupon['id'],
    'code' => $coupon['code'],
    'type' => $coupon['type'],
    'value' => (float)$coupon['value'],
    'discount' => $discount
];

$shipping = ($subtotal - $discount >= FREE_SHIPPING_THRESHOLD) ? 0.0 : DEFAULT_SHIPPING_FEE;
$total = ($subtotal - $discount) + $shipping;

json_response([
    'success' => true,
    'message' => 'Coupon ' . $code . ' applied! You saved ' . format_price($discount),
    'discount' => $discount,
    'formatted_discount' => format_price($discount),
    'new_subtotal' => $subtotal,
    'shipping' => $shipping,
    'new_total' => $total,
    'formatted_total' => format_price($total)
]);
