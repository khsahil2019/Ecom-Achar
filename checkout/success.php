<?php
/**
 * Achar Heritage - Order Confirmation & Success Page
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();
$orderNumber = trim($_GET['order_number'] ?? '');

if (empty($orderNumber)) {
    header('Location: ' . BASE_URL . '/shop.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? LIMIT 1");
$stmt->execute([$orderNumber]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found.');
    header('Location: ' . BASE_URL . '/shop.php');
    exit;
}

// Fetch order items
$itemStmt = $pdo->prepare("
    SELECT oi.*, p.main_image, p.slug as product_slug 
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$order['id']]);
$orderItems = $itemStmt->fetchAll();

$pageTitle = 'Order Confirmed - ' . e($order['order_number']) . ' | Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    
    <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-white mb-4 text-center">
        <div class="bg-brand-primary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center shadow" style="width: 80px; height: 80px;">
            <i class="bi bi-patch-check-fill display-4"></i>
        </div>
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill fw-bold text-uppercase mb-2">Order Confirmed</span>
        <h1 class="font-heading display-6 mb-2">Thank You for Your Order, <?= e(explode(' ', $order['customer_name'])[0]) ?>!</h1>
        <p class="text-muted mx-auto" style="max-width: 550px;">
            Your authentic pickle jars are being carefully packaged with love. We have dispatched confirmation details to <strong><?= e($order['customer_email']) ?></strong>.
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-3 mt-3">
            <span class="badge bg-light text-dark border p-2 fs-6">Order ID: <strong><?= e($order['order_number']) ?></strong></span>
            <span class="badge bg-light text-dark border p-2 fs-6">Amount: <strong><?= format_price($order['total_amount']) ?></strong></span>
            <span class="badge bg-light text-dark border p-2 fs-6">Payment: <strong><?= strtoupper(e($order['payment_method'])) ?></strong></span>
        </div>
    </div>

    <!-- Visual Order Timeline Stepper -->
    <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
        <h5 class="fw-bold font-heading mb-4 text-center">Order Journey</h5>
        
        <?php
        $statuses = ['Pending', 'Confirmed', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered'];
        $currentStatus = $order['order_status'];
        $currentIndex = array_search($currentStatus, $statuses);
        if ($currentIndex === false) $currentIndex = 1;
        ?>

        <div class="order-timeline">
            <?php foreach ($statuses as $idx => $st): 
                $isCompleted = $idx < $currentIndex;
                $isActive = $idx === $currentIndex;
                $stepClass = $isCompleted ? 'completed' : ($isActive ? 'active' : '');
            ?>
                <div class="timeline-step <?= $stepClass ?>">
                    <div class="timeline-icon">
                        <?php if ($isCompleted): ?>
                            <i class="bi bi-check-lg"></i>
                        <?php else: ?>
                            <?= $idx + 1 ?>
                        <?php endif; ?>
                    </div>
                    <div class="timeline-label"><?= $st ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Order Details Grid -->
    <div class="row g-4">
        
        <!-- Left: Item Breakdown -->
        <div class="col-lg-8">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
                <h5 class="fw-bold font-heading mb-3">Purchased Pickles</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light small">
                            <tr>
                                <th>Item</th>
                                <th>Size</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if ($item['main_image']): ?>
                                                <img src="<?= BASE_URL ?>/uploads/products/<?= e($item['main_image']) ?>" class="rounded-2 border" style="width: 44px; height: 44px; object-fit: cover;">
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold small"><?= e($item['product_name']) ?></div>
                                                <div class="text-muted" style="font-size:0.75rem">SKU: <?= e($item['sku']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= e($item['variant_label']) ?></span></td>
                                    <td><?= format_price($item['unit_price']) ?></td>
                                    <td><?= (int)$item['quantity'] ?></td>
                                    <td class="text-end fw-bold"><?= format_price($item['subtotal']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print Invoice
                    </button>
                    <a href="<?= BASE_URL ?>/shop.php" class="btn btn-brand-primary btn-sm rounded-pill">
                        <i class="bi bi-arrow-right me-1"></i> Continue Shopping
                    </a>
                </div>
            </div>
        </div>

        <!-- Right: Shipping Address & Summary -->
        <div class="col-lg-4">
            
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <h5 class="fw-bold font-heading mb-3">Delivery Address</h5>
                <div class="small text-muted">
                    <div class="fw-bold text-dark fs-6 mb-1"><?= e($order['customer_name']) ?></div>
                    <div><?= nl2br(e($order['shipping_address'])) ?></div>
                    <div><?= e($order['shipping_city']) ?>, <?= e($order['shipping_state']) ?> - <?= e($order['shipping_pincode']) ?></div>
                    <div class="mt-2"><i class="bi bi-telephone text-success me-1"></i> <?= e($order['customer_mobile']) ?></div>
                    <div><i class="bi bi-envelope text-primary me-1"></i> <?= e($order['customer_email']) ?></div>
                </div>
            </div>

            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
                <h5 class="fw-bold font-heading mb-3">Payment Summary</h5>
                <div class="d-flex justify-content-between mb-2 small text-muted">
                    <span>Subtotal:</span>
                    <span><?= format_price($order['subtotal']) ?></span>
                </div>
                <?php if ($order['discount_amount'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2 small text-success">
                        <span>Discount (<?= e($order['coupon_code']) ?>):</span>
                        <span>-<?= format_price($order['discount_amount']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between mb-2 small text-muted">
                    <span>Shipping Fee:</span>
                    <span><?= $order['shipping_fee'] == 0 ? '<strong class="text-success">FREE</strong>' : format_price($order['shipping_fee']) ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-baseline mb-3">
                    <span class="fw-bold">Total Paid / Payable:</span>
                    <span class="fs-4 fw-bold text-success"><?= format_price($order['total_amount']) ?></span>
                </div>
                <div class="badge bg-light text-dark border p-2 w-100 text-center">
                    Method: <?= strtoupper(e($order['payment_method'])) ?> • Status: <?= strtoupper(e($order['payment_status'])) ?>
                </div>
            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
