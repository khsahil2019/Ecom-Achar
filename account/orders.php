<?php
/**
 * Customer Orders & Live Tracking
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$pdo = db();
$cid = customer_id();
$viewOrderNumber = trim($_GET['order_number'] ?? '');

$selectedOrder = null;
$selectedOrderItems = [];

if ($viewOrderNumber) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND customer_id = ? LIMIT 1");
    $stmt->execute([$viewOrderNumber, $cid]);
    $selectedOrder = $stmt->fetch();

    if ($selectedOrder) {
        $itemStmt = $pdo->prepare("
            SELECT oi.*, p.main_image, p.slug as product_slug 
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $itemStmt->execute([$selectedOrder['id']]);
        $selectedOrderItems = $itemStmt->fetchAll();
    }
}

// Fetch all orders for list
$allStmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC");
$allStmt->execute([$cid]);
$ordersList = $allStmt->fetchAll();

$pageTitle = 'My Pickle Orders - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="py-4 bg-white border-bottom">
    <div class="container">
        <h1 class="font-heading display-6 m-0">My Orders & Tracking</h1>
        <p class="text-muted small m-0 mt-1">Track dispatch status, courier details, and previous pickle deliveries.</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-lg-4">
            <?php require_once __DIR__ . '/account_nav.php'; ?>
        </div>

        <!-- Orders Content -->
        <div class="col-lg-8">
            
            <?php if ($selectedOrder): ?>
                <!-- Single Order Detailed View -->
                <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-3 border-bottom">
                        <div>
                            <span class="text-muted small">Viewing Order Details</span>
                            <h4 class="font-heading m-0">Order: <?= e($selectedOrder['order_number']) ?></h4>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="window.print()">
                                <i class="bi bi-printer me-1"></i> Print Invoice
                            </button>
                            <a href="<?= BASE_URL ?>/account/orders.php" class="btn btn-outline-brand btn-sm rounded-pill">
                                Back to All Orders
                            </a>
                        </div>
                    </div>

                    <!-- Tracking Timeline -->
                    <div class="py-3">
                        <h6 class="fw-bold text-center mb-3">Live Order Tracking</h6>
                        <?php
                        $statuses = ['Pending', 'Confirmed', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered'];
                        $currentStatus = $selectedOrder['order_status'];
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

                        <?php if (!empty($selectedOrder['tracking_number'])): ?>
                            <div class="text-center mt-3">
                                <span class="badge bg-info-subtle text-dark border p-2">
                                    <i class="bi bi-truck me-1"></i> Courier Tracking Code: <strong><?= e($selectedOrder['tracking_number']) ?></strong>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Items Table -->
                    <h6 class="fw-bold mt-4 mb-3">Order Items</h6>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light small">
                                <tr>
                                    <th>Item</th>
                                    <th>Weight</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($selectedOrderItems as $item): ?>
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

                    <!-- Totals breakdown -->
                    <div class="row mt-3 pt-3 border-top g-3">
                        <div class="col-md-6 small text-muted">
                            <strong>Delivered to:</strong><br>
                            <?= e($selectedOrder['customer_name']) ?><br>
                            <?= nl2br(e($selectedOrder['shipping_address'])) ?><br>
                            <?= e($selectedOrder['shipping_city']) ?>, <?= e($selectedOrder['shipping_state']) ?> - <?= e($selectedOrder['shipping_pincode']) ?><br>
                            Phone: <?= e($selectedOrder['customer_mobile']) ?>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="small text-muted mb-1">Subtotal: <?= format_price($selectedOrder['subtotal']) ?></div>
                            <?php if ($selectedOrder['discount_amount'] > 0): ?>
                                <div class="small text-success mb-1">Discount (<?= e($selectedOrder['coupon_code']) ?>): -<?= format_price($selectedOrder['discount_amount']) ?></div>
                            <?php endif; ?>
                            <div class="small text-muted mb-2">Delivery: <?= $selectedOrder['shipping_fee'] == 0 ? '<strong class="text-danger">FREE</strong>' : format_price($selectedOrder['shipping_fee']) ?></div>
                            <div class="fs-4 fw-bold text-danger">Total: <?= format_price($selectedOrder['total_amount']) ?></div>
                            <span class="badge bg-light text-dark border mt-1">Payment: <?= strtoupper(e($selectedOrder['payment_method'])) ?> (<?= strtoupper(e($selectedOrder['payment_status'])) ?>)</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- All Orders List -->
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
                <h5 class="fw-bold font-heading mb-3">All Orders (<?= count($ordersList) ?>)</h5>
                
                <?php if (!empty($ordersList)): ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light small">
                                <tr>
                                    <th>Order #</th>
                                    <th>Placed On</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordersList as $ord): 
                                    $badgeClass = match($ord['order_status']) {
                                        'Delivered' => 'bg-success',
                                        'Shipped', 'Out for Delivery' => 'bg-info text-dark',
                                        'Cancelled' => 'bg-danger',
                                        default => 'bg-warning text-dark'
                                    };
                                ?>
                                    <tr>
                                        <td>
                                            <a href="<?= BASE_URL ?>/account/orders.php?order_number=<?= urlencode($ord['order_number']) ?>" class="fw-bold text-dark text-decoration-none">
                                                <?= e($ord['order_number']) ?>
                                            </a>
                                        </td>
                                        <td class="small text-muted"><?= format_date($ord['created_at'], 'd M Y') ?></td>
                                        <td class="fw-bold text-danger"><?= format_price($ord['total_amount']) ?></td>
                                        <td><span class="badge bg-light text-dark border small"><?= strtoupper(e($ord['payment_method'])) ?></span></td>
                                        <td><span class="badge <?= $badgeClass ?>"><?= e($ord['order_status']) ?></span></td>
                                        <td class="text-end">
                                            <a href="<?= BASE_URL ?>/account/orders.php?order_number=<?= urlencode($ord['order_number']) ?>" class="btn btn-sm btn-outline-brand rounded-pill px-3">
                                                View Journey
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-box2 display-4 d-block mb-3"></i>
                        <h5>No orders found</h5>
                        <p class="small">Treat your dining table with traditional homemade pickles today!</p>
                        <a href="<?= BASE_URL ?>/shop.php" class="btn btn-brand-primary rounded-pill px-4">Start Shopping</a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
