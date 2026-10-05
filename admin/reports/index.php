<?php
/**
 * Admin Reports & Analytics
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
$pdo = db();

$filterRange = $_GET['range'] ?? 'this_month';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Determine start and end date
switch ($filterRange) {
    case 'today':
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
        break;
    case 'yesterday':
        $startDate = date('Y-m-d', strtotime('-1 day'));
        $endDate = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'this_week':
        $startDate = date('Y-m-d', strtotime('monday this week'));
        $endDate = date('Y-m-d');
        break;
    case 'custom':
        if (!$startDate) $startDate = date('Y-m-01');
        if (!$endDate) $endDate = date('Y-m-d');
        break;
    case 'this_month':
    default:
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-d');
        break;
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=achar_sales_report_' . $startDate . '_to_' . $endDate . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order Number', 'Date', 'Customer Name', 'Customer Email', 'Customer Phone', 'Payment Method', 'Payment Status', 'Order Status', 'Subtotal', 'Discount', 'Shipping', 'Total Amount']);

    $csvStmt = $pdo->prepare("
        SELECT order_number, created_at, customer_name, customer_email, customer_mobile, payment_method, payment_status, order_status, subtotal, discount_amount, shipping_fee, total_amount
        FROM orders
        WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
        ORDER BY id DESC
    ");
    $csvStmt->execute([$startDate, $endDate]);
    while ($row = $csvStmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Fetch aggregate report metrics
$aggStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(total_amount), 0) as total_sales,
        COALESCE(AVG(total_amount), 0) as avg_order_val,
        SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_orders
    FROM orders
    WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
");
$aggStmt->execute([$startDate, $endDate]);
$reportStats = $aggStmt->fetch();

// Fetch Top Selling Pickles
$topStmt = $pdo->prepare("
    SELECT oi.product_name, oi.variant_label, SUM(oi.quantity) as total_qty, SUM(oi.subtotal) as total_rev
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) >= ? AND DATE(o.created_at) <= ?
    GROUP BY oi.product_name, oi.variant_label
    ORDER BY total_qty DESC
    LIMIT 10
");
$topStmt->execute([$startDate, $endDate]);
$topPickles = $topStmt->fetchAll();

$adminTitle = 'Sales & Revenue Reports - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="font-heading m-0 fs-3">Sales & Analytics Reports</h2>
        <p class="text-muted small m-0 mt-1">Data from <strong><?= date('d M Y', strtotime($startDate)) ?></strong> to <strong><?= date('d M Y', strtotime($endDate)) ?></strong></p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/admin/reports/index.php?range=<?= urlencode($filterRange) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&export=csv" class="btn btn-outline-success rounded-pill px-4">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV Spreadsheet
        </a>
    </div>
</div>

<!-- Date Filter Form -->
<div class="card border-0 rounded-4 shadow-sm p-3 bg-white mb-4">
    <form action="<?= BASE_URL ?>/admin/reports/index.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-3">
            <select name="range" class="form-select" onchange="if(this.value !== 'custom') this.form.submit();">
                <option value="today" <?= $filterRange === 'today' ? 'selected' : '' ?>>Today</option>
                <option value="yesterday" <?= $filterRange === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                <option value="this_week" <?= $filterRange === 'this_week' ? 'selected' : '' ?>>This Week</option>
                <option value="this_month" <?= $filterRange === 'this_month' ? 'selected' : '' ?>>This Month</option>
                <option value="custom" <?= $filterRange === 'custom' ? 'selected' : '' ?>>Custom Date Range</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>">
        </div>
        <div class="col-md-3">
            <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-brand-primary w-100 rounded-pill">Apply Filter</button>
        </div>
    </form>
</div>

<!-- Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="card border-0 rounded-4 shadow-sm p-3 bg-white text-center">
            <span class="text-muted small">Total Sales</span>
            <div class="fs-3 fw-bold text-success mt-1 font-heading"><?= format_price($reportStats['total_sales']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card border-0 rounded-4 shadow-sm p-3 bg-white text-center">
            <span class="text-muted small">Orders Count</span>
            <div class="fs-3 fw-bold mt-1 font-heading"><?= (int)$reportStats['total_orders'] ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card border-0 rounded-4 shadow-sm p-3 bg-white text-center">
            <span class="text-muted small">Average Order Value (AOV)</span>
            <div class="fs-3 fw-bold text-primary mt-1 font-heading"><?= format_price($reportStats['avg_order_val']) ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card border-0 rounded-4 shadow-sm p-3 bg-white text-center">
            <span class="text-muted small">Paid Transactions</span>
            <div class="fs-3 fw-bold text-warning mt-1 font-heading"><?= (int)$reportStats['paid_orders'] ?></div>
        </div>
    </div>
</div>

<!-- Top Selling Pickles Table -->
<div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
    <h5 class="fw-bold font-heading mb-3">Top Performing Pickles in Period</h5>
    
    <?php if (!empty($topPickles)): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>Rank</th>
                        <th>Pickle Recipe</th>
                        <th>Variant Size</th>
                        <th>Jars Sold</th>
                        <th class="text-end">Revenue Generated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topPickles as $idx => $p): ?>
                        <tr>
                            <td><span class="badge bg-light text-dark border"><?= $idx + 1 ?></span></td>
                            <td class="fw-bold text-dark"><?= e($p['product_name']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= e($p['variant_label']) ?></span></td>
                            <td><span class="badge bg-success-subtle text-success border border-success fw-bold"><?= (int)$p['total_qty'] ?> jars</span></td>
                            <td class="text-end fw-bold text-success"><?= format_price($p['total_rev']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-4 text-muted">
            <i class="bi bi-graph-up fs-2 d-block mb-2"></i>
            No sales recorded during this date window.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
