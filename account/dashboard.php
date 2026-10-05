<?php
/**
 * Customer Account Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$pdo = db();
$cid = customer_id();
$user = current_user();

// Fetch order statistics
$statStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN order_status = 'Pending' OR order_status = 'Confirmed' OR order_status = 'Processing' OR order_status = 'Packed' OR order_status = 'Shipped' OR order_status = 'Out for Delivery' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN order_status = 'Delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN order_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        SUM(CASE WHEN payment_status = 'paid' OR order_status = 'Delivered' THEN total_amount ELSE 0 END) as total_spent
    FROM orders 
    WHERE customer_id = ?
");
$statStmt->execute([$cid]);
$stats = $statStmt->fetch();

// Fetch recent 5 orders
$orderStmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC LIMIT 5");
$orderStmt->execute([$cid]);
$recentOrders = $orderStmt->fetchAll();

$pageTitle = 'Customer Dashboard - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="py-4 bg-white border-bottom">
    <div class="container">
        <h1 class="font-heading display-6 m-0">Customer Portal</h1>
        <p class="text-muted small m-0 mt-1">Manage your orders, saved delivery addresses, and favorite recipes.</p>
    </div>
</div>

<div class="container py-5">
    <?= render_flash() ?>

    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-lg-4">
            <?php require_once __DIR__ . '/account_nav.php'; ?>
        </div>

        <!-- Main Dashboard View -->
        <div class="col-lg-8">
            
            <!-- Welcome Card -->
            <div class="p-4 rounded-4 text-white shadow-sm mb-4" style="background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-light));">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold text-uppercase mb-2">Heritage Connoisseur</span>
                        <h3 class="font-heading text-white m-0">Namaste, <?= e($user['name']) ?>!</h3>
                        <p class="text-light opacity-90 small m-0 mt-1">Thank you for savoring authentic Indian artisanal pickles with us.</p>
                    </div>
                    <div class="fs-1 text-warning opacity-75 d-none d-sm-block">
                        <i class="bi bi-award-fill"></i>
                    </div>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card border-0 rounded-4 shadow-sm p-3 bg-white text-center">
                        <div class="fs-4 text-primary mb-1"><i class="bi bi-box-seam"></i></div>
                        <div class="fs-3 fw-bold"><?= (int)$stats['total_orders'] ?></div>
                        <div class="text-muted small">Total Orders</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 rounded-4 shadow-sm p-3 bg-white text-center">
                        <div class="fs-4 text-warning mb-1"><i class="bi bi-hourglass-split"></i></div>
                        <div class="fs-3 fw-bold"><?= (int)$stats['pending_orders'] ?></div>
                        <div class="text-muted small">In Progress</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 rounded-4 shadow-sm p-3 bg-white text-center">
                        <div class="fs-4 text-success mb-1"><i class="bi bi-check2-circle"></i></div>
                        <div class="fs-3 fw-bold"><?= (int)$stats['delivered_orders'] ?></div>
                        <div class="text-muted small">Delivered</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 rounded-4 shadow-sm p-3 bg-white text-center">
                        <div class="fs-4 text-danger mb-1"><i class="bi bi-x-circle"></i></div>
                        <div class="fs-3 fw-bold"><?= (int)$stats['cancelled_orders'] ?></div>
                        <div class="text-muted small">Cancelled</div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Section -->
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold font-heading m-0">Recent Orders</h5>
                    <a href="<?= BASE_URL ?>/account/orders.php" class="text-success small fw-bold text-decoration-none">View All Orders</a>
                </div>

                <?php if (!empty($recentOrders)): ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light small">
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $ord): 
                                    $badgeColor = match($ord['order_status']) {
                                        'Delivered' => 'bg-success',
                                        'Shipped', 'Out for Delivery' => 'bg-info text-dark',
                                        'Cancelled' => 'bg-danger',
                                        default => 'bg-warning text-dark'
                                    };
                                ?>
                                    <tr>
                                        <td>
                                            <strong class="text-dark"><?= e($ord['order_number']) ?></strong>
                                            <div class="text-muted" style="font-size:0.75rem"><?= strtoupper(e($ord['payment_method'])) ?></div>
                                        </td>
                                        <td class="small text-muted"><?= format_date($ord['created_at'], 'd M Y') ?></td>
                                        <td class="fw-bold text-success"><?= format_price($ord['total_amount']) ?></td>
                                        <td><span class="badge <?= $badgeColor ?>"><?= e($ord['order_status']) ?></span></td>
                                        <td class="text-end">
                                            <a href="<?= BASE_URL ?>/account/orders.php?order_number=<?= urlencode($ord['order_number']) ?>" class="btn btn-sm btn-outline-brand rounded-pill px-3">
                                                Track Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-box2 fs-2 d-block mb-2"></i>
                        No orders placed yet. <a href="<?= BASE_URL ?>/shop.php" class="text-success fw-bold">Explore our pickles</a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
