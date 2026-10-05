<?php
/**
 * Admin Master Operations Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$pdo = db();

// 1. Fetch KPI Metrics
// Total Sales & Orders
$orderStats = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' OR order_status = 'Delivered' THEN total_amount ELSE 0 END), 0) as total_revenue,
        SUM(CASE WHEN order_status = 'Pending' OR order_status = 'Confirmed' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN order_status = 'Delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN order_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_orders
    FROM orders
")->fetch();

// Total Customers
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) as cnt FROM customers")->fetch()['cnt'];

// Total Products & Low Stock
$productStats = $pdo->query("
    SELECT 
        COUNT(*) as total_products,
        SUM(CASE WHEN stock <= 20 THEN 1 ELSE 0 END) as low_stock_products
    FROM products
    WHERE status = 'active'
")->fetch();

// 2. Recent Orders (Top 8)
$recentOrders = $pdo->query("
    SELECT id, order_number, customer_name, customer_email, total_amount, payment_method, payment_status, order_status, created_at 
    FROM orders 
    ORDER BY id DESC 
    LIMIT 8
")->fetchAll();

// 3. Low Stock Products Table
$lowStockItems = $pdo->query("
    SELECT p.id, p.name, p.sku, p.stock, p.price, p.main_image, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active' AND p.stock <= 25
    ORDER BY p.stock ASC
    LIMIT 6
")->fetchAll();

// 4. Status Breakdown for Doughnut Chart
$statusRows = $pdo->query("
    SELECT order_status, COUNT(*) as cnt 
    FROM orders 
    GROUP BY order_status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$allStatuses = ['Pending', 'Confirmed', 'Packed', 'Shipped', 'Delivered', 'Cancelled'];
$statusCounts = [];
foreach ($allStatuses as $st) {
    $statusCounts[] = (int)($statusRows[$st] ?? 0);
}

$adminTitle = 'Admin Dashboard - Operations Overview';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="font-heading m-0 fs-3">Operations Dashboard</h2>
        <p class="text-muted small m-0 mt-1">Real-time telemetry on orders, inventory, revenue, and customer engagement.</p>
    </div>
        <a href="<?= BASE_URL ?>/admin/automation.php" class="btn btn-warning text-dark fw-bold btn-sm rounded-pill px-3 shadow-sm">
            <i class="bi bi-cpu-fill me-1"></i> Auto-Pilot Hub
        </a>
        <a href="<?= BASE_URL ?>/admin/products/add.php" class="btn btn-brand-primary btn-sm rounded-pill px-3 shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Add New Pickle
        </a>
        <a href="<?= BASE_URL ?>/admin/orders/index.php" class="btn btn-outline-brand btn-sm rounded-pill px-3">
            <i class="bi bi-cart-check me-1"></i> Process Orders
        </a>
    </div>
</div>

<!-- Auto-Pilot Quick Command Bar -->
<div class="card border-0 rounded-4 shadow-sm p-3 bg-white mb-4 border-start border-4 border-warning">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-warning text-dark p-2 rounded-circle fs-5" style="width:38px;height:38px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <div>
                <div class="fw-bold text-dark fs-6">System Automation & Auto-Pilot</div>
                <div class="text-muted small">One-click fulfillment pipeline, low-stock replenishment, and background cron worker.</div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <form action="<?= BASE_URL ?>/admin/automation.php" method="POST" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="operation" value="auto_fulfill_orders">
                <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill">
                    <i class="bi bi-truck me-1"></i> Auto-Fulfill Orders
                </button>
            </form>
            <form action="<?= BASE_URL ?>/admin/automation.php" method="POST" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="operation" value="auto_restock_low">
                <input type="hidden" name="threshold" value="25">
                <input type="hidden" name="add_stock" value="50">
                <button type="submit" class="btn btn-outline-warning text-dark btn-sm rounded-pill">
                    <i class="bi bi-arrow-repeat me-1"></i> Auto-Restock Low Jars
                </button>
            </form>
            <a href="<?= BASE_URL ?>/admin/automation.php" class="btn btn-dark btn-sm rounded-pill px-3">
                Manage All Automations &rarr;
            </a>
        </div>
    </div>
</div>

<!-- KPI Cards Grid -->
<div class="row g-3 mb-4">
    <!-- Total Revenue -->
    <div class="col-sm-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-bold text-uppercase">Total Sales Revenue</span>
                <div class="bg-success-subtle text-success p-2 rounded-3 fs-5"><i class="bi bi-currency-rupee"></i></div>
            </div>
            <div class="fs-3 fw-bold text-success font-heading"><?= format_price($orderStats['total_revenue']) ?></div>
            <span class="badge bg-success-subtle text-success small mt-1">Lifetime Gross</span>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="col-sm-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-bold text-uppercase">Total Orders Placed</span>
                <div class="bg-primary-subtle text-primary p-2 rounded-3 fs-5"><i class="bi bi-box-seam"></i></div>
            </div>
            <div class="fs-3 fw-bold font-heading"><?= (int)$orderStats['total_orders'] ?></div>
            <span class="badge bg-primary-subtle text-primary small mt-1"><?= (int)$orderStats['delivered_orders'] ?> Delivered</span>
        </div>
    </div>

    <!-- Pending / Actionable Orders -->
    <div class="col-sm-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-bold text-uppercase">Pending / Processing</span>
                <div class="bg-warning-subtle text-warning p-2 rounded-3 fs-5"><i class="bi bi-hourglass-split"></i></div>
            </div>
            <div class="fs-3 fw-bold font-heading text-warning"><?= (int)$orderStats['pending_orders'] ?></div>
            <span class="badge bg-warning-subtle text-dark small mt-1">Requires Courier Dispatch</span>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <div class="col-sm-6 col-xl-3">
        <div class="admin-kpi-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-bold text-uppercase">Low Stock Alert</span>
                <div class="bg-danger-subtle text-danger p-2 rounded-3 fs-5"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
            <div class="fs-3 fw-bold font-heading text-danger"><?= (int)$productStats['low_stock_products'] ?></div>
            <span class="badge bg-danger-subtle text-danger small mt-1">Below 25 Units</span>
        </div>
    </div>
</div>

<!-- Secondary KPI Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="card border-0 rounded-4 shadow-sm p-3 bg-white text-center">
            <span class="text-muted small">Registered Customers</span>
            <div class="fs-4 fw-bold mt-1"><?= $totalCustomers ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card border-0 rounded-4 shadow-sm p-3 bg-white text-center">
            <span class="text-muted small">Active Pickle Jars</span>
            <div class="fs-4 fw-bold mt-1"><?= (int)$productStats['total_products'] ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card border-0 rounded-4 shadow-sm p-3 bg-white text-center">
            <span class="text-muted small">Delivered Orders</span>
            <div class="fs-4 fw-bold text-success mt-1"><?= (int)$orderStats['delivered_orders'] ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card border-0 rounded-4 shadow-sm p-3 bg-white text-center">
            <span class="text-muted small">Cancelled Orders</span>
            <div class="fs-4 fw-bold text-danger mt-1"><?= (int)$orderStats['cancelled_orders'] ?></div>
        </div>
    </div>
</div>

<!-- Analytics Charts Section -->
<div class="row g-4 mb-4">
    <!-- Sales Revenue Trend -->
    <div class="col-lg-8">
        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold font-heading m-0">Monthly Revenue Projection (₹)</h5>
                <span class="badge bg-light text-muted border">2026 Fiscal Season</span>
            </div>
            <div style="height: 280px;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Order Status Doughnut -->
    <div class="col-lg-4">
        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white h-100">
            <h5 class="fw-bold font-heading mb-3">Order Status Distribution</h5>
            <div style="height: 250px;" class="d-flex align-items-center justify-content-center">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders & Low Stock Tables -->
<div class="row g-4">
    <!-- Recent Orders -->
    <div class="col-lg-8">
        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold font-heading m-0">Recent Inbound Orders</h5>
                <a href="<?= BASE_URL ?>/admin/orders/index.php" class="small fw-bold text-success text-decoration-none">View All Orders &rarr;</a>
            </div>

            <?php if (!empty($recentOrders)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Payment</th>
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
                                        <a href="<?= BASE_URL ?>/admin/orders/view.php?id=<?= $ord['id'] ?>" class="fw-bold text-dark text-decoration-none">
                                            <?= e($ord['order_number']) ?>
                                        </a>
                                        <div class="text-muted" style="font-size:0.75rem"><?= format_date($ord['created_at'], 'd M, h:i A') ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small"><?= e($ord['customer_name']) ?></div>
                                        <div class="text-muted" style="font-size:0.75rem"><?= e($ord['customer_email']) ?></div>
                                    </td>
                                    <td class="fw-bold text-success"><?= format_price($ord['total_amount']) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border small"><?= strtoupper(e($ord['payment_method'])) ?></span>
                                    </td>
                                    <td><span class="badge <?= $badgeColor ?>"><?= e($ord['order_status']) ?></span></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/admin/orders/view.php?id=<?= $ord['id'] ?>" class="btn btn-sm btn-outline-brand rounded-pill px-3">
                                            Inspect
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-4 mb-0">No orders recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Low Stock Warning Table -->
    <div class="col-lg-4">
        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold font-heading m-0">Low Inventory</h5>
                <a href="<?= BASE_URL ?>/admin/products/inventory.php" class="small fw-bold text-danger text-decoration-none">Manage Stock &rarr;</a>
            </div>

            <?php if (!empty($lowStockItems)): ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($lowStockItems as $item): ?>
                        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light border">
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= BASE_URL ?>/uploads/products/<?= e($item['main_image']) ?>" class="rounded-2 border" style="width: 40px; height: 40px; object-fit: cover;">
                                <div>
                                    <div class="fw-bold small text-dark"><?= e($item['name']) ?></div>
                                    <div class="text-muted" style="font-size:0.75rem"><?= e($item['category_name']) ?></div>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge <?= $item['stock'] == 0 ? 'bg-danger' : 'bg-warning text-dark' ?> fw-bold">
                                    <?= (int)$item['stock'] ?> Left
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-4 small">
                    <i class="bi bi-check-circle fs-3 text-success d-block mb-1"></i>
                    All pickle jars well-stocked!
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart Initialization Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Monthly Revenue Line Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr'],
            datasets: [{
                label: 'Gross Sales (₹)',
                data: [18500, 24600, 31200, 28900, 42000, 56800, 68900, 84200, 92000, 88500, 94000, 105000],
                borderColor: '#C5161D',
                backgroundColor: 'rgba(197, 22, 29, 0.08)',
                fill: true,
                tension: 0.35,
                borderWidth: 3,
                pointBackgroundColor: '#820C12',
                pointBorderColor: '#FFF',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(val) { return '₹' + val.toLocaleString(); }
                    }
                }
            }
        }
    });

    // 2. Order Status Doughnut Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($allStatuses) ?>,
            datasets: [{
                data: <?= json_encode($statusCounts) ?>,
                backgroundColor: ['#F59E0B', '#3B82F6', '#8B5CF6', '#06B6D4', '#10B981', '#EF4444'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
