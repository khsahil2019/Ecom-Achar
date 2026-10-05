<?php
/**
 * Admin Order Inspector & Lifecycle Management
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
$pdo = db();

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    header('Location: ' . BASE_URL . '/admin/orders/index.php');
    exit;
}

// Fetch Order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found.');
    header('Location: ' . BASE_URL . '/admin/orders/index.php');
    exit;
}

// Handle Status & Tracking Update
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    if (verify_csrf()) {
        $orderStatus = sanitize($_POST['order_status'] ?? $order['order_status']);
        $paymentStatus = sanitize($_POST['payment_status'] ?? $order['payment_status']);
        $trackingNumber = sanitize($_POST['tracking_number'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');

        $uStmt = $pdo->prepare("
            UPDATE orders SET 
                order_status = ?, payment_status = ?, tracking_number = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $uStmt->execute([$orderStatus, $paymentStatus, $trackingNumber, $notes, $orderId]);

        set_flash('success', 'Order #' . $order['order_number'] . ' updated successfully.');
        header('Location: ' . BASE_URL . '/admin/orders/view.php?id=' . $orderId);
        exit;
    }
}

// Fetch Order Items
$itemStmt = $pdo->prepare("
    SELECT oi.*, p.main_image, p.slug as product_slug 
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$orderId]);
$orderItems = $itemStmt->fetchAll();

$allStatuses = ['Pending', 'Confirmed', 'Processing', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled'];
$paymentStatuses = ['pending', 'paid', 'failed', 'refunded'];

$adminTitle = 'Inspect Order ' . e($order['order_number']);
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="font-heading m-0 fs-3">Order Details: <?= e($order['order_number']) ?></h2>
        <p class="text-muted small m-0 mt-1">Placed on <?= format_date($order['created_at']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/admin/orders/invoice.php?id=<?= $orderId ?>&print=1" target="_blank" class="btn btn-outline-secondary rounded-pill btn-sm">
            <i class="bi bi-printer me-1"></i> Print Tax Invoice
        </a>
        <a href="<?= BASE_URL ?>/admin/orders/index.php" class="btn btn-outline-brand rounded-pill btn-sm">
            &larr; Back to Orders
        </a>
    </div>
</div>

<div class="row g-4">
    
    <!-- Left: Order Items & Customer Details -->
    <div class="col-lg-8">
        <!-- Purchased Items Card -->
        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
            <h5 class="fw-bold font-heading mb-3">Purchased Pickles</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light small">
                        <tr>
                            <th>Pickle Variety</th>
                            <th>Weight</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th class="text-end">Subtotal</th>
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
                                            <div class="fw-bold small text-dark"><?= e($item['product_name']) ?></div>
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

            <!-- Price Breakdown -->
            <div class="row mt-3 pt-3 border-top justify-content-end">
                <div class="col-md-5">
                    <div class="d-flex justify-content-between mb-1 small text-muted">
                        <span>Items Subtotal:</span>
                        <span><?= format_price($order['subtotal']) ?></span>
                    </div>
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-1 small text-success">
                            <span>Coupon (<?= e($order['coupon_code']) ?>):</span>
                            <span>-<?= format_price($order['discount_amount']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-2 small text-muted">
                        <span>Delivery Fee:</span>
                        <span><?= $order['shipping_fee'] == 0 ? '<strong class="text-success">FREE</strong>' : format_price($order['shipping_fee']) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fs-5 fw-bold text-success">
                        <span>Grand Total:</span>
                        <span><?= format_price($order['total_amount']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer & Destination Card -->
        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
            <h5 class="fw-bold font-heading mb-3">Customer & Delivery Destination</h5>
            <div class="row g-3 text-secondary">
                <div class="col-md-6">
                    <h6 class="fw-bold text-dark mb-1">Customer Information</h6>
                    <div><?= e($order['customer_name']) ?></div>
                    <div><i class="bi bi-envelope text-primary me-1"></i> <?= e($order['customer_email']) ?></div>
                    <div><i class="bi bi-phone text-success me-1"></i> <?= e($order['customer_mobile']) ?></div>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold text-dark mb-1">Shipping Address</h6>
                    <div><?= nl2br(e($order['shipping_address'])) ?></div>
                    <div><?= e($order['shipping_city']) ?>, <?= e($order['shipping_state']) ?> - <strong><?= e($order['shipping_pincode']) ?></strong></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Fulfillment & Lifecycle Controls -->
    <div class="col-lg-4">
        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white position-sticky" style="top: 20px;">
            <h5 class="fw-bold font-heading mb-3">Order Lifecycle Control</h5>

            <form action="<?= BASE_URL ?>/admin/orders/view.php?id=<?= $orderId ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="update_order" value="1">

                <div class="mb-3">
                    <label class="form-label small fw-bold">Fulfillment Status</label>
                    <select name="order_status" class="form-select fw-semibold">
                        <?php foreach ($allStatuses as $st): ?>
                            <option value="<?= e($st) ?>" <?= ($order['order_status'] === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Payment Method & Status</label>
                    <div class="badge bg-light text-dark border p-2 w-100 text-start mb-2">
                        Method: <strong><?= strtoupper(e($order['payment_method'])) ?></strong>
                    </div>
                    <select name="payment_status" class="form-select">
                        <?php foreach ($paymentStatuses as $ps): ?>
                            <option value="<?= e($ps) ?>" <?= ($order['payment_status'] === $ps) ? 'selected' : '' ?>><?= strtoupper(e($ps)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Courier Tracking ID</label>
                    <input type="text" name="tracking_number" class="form-control" placeholder="e.g. DELHIVERY-ACH-89210" value="<?= e($order['tracking_number'] ?? '') ?>">
                    <small class="text-muted">Visible to customer on their order tracking journey.</small>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Internal Fulfillment Notes</label>
                    <textarea name="notes" rows="3" class="form-control" placeholder="e.g. Packed in double cushion box, dispatched via BlueDart express"><?= e($order['notes'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-brand-primary w-100 py-3 rounded-pill fw-bold shadow">
                    <i class="bi bi-check-circle me-2"></i> Update Order Status
                </button>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
