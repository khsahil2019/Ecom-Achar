<?php
/**
 * Admin Orders Management List with Bulk Operations & Invoicing
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
$pdo = db();
$adminUser = current_admin();

// Handle Bulk Operations
$bulkMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    if (verify_csrf()) {
        $action = trim($_POST['bulk_action']);
        $selectedIds = $_POST['order_ids'] ?? [];

        if (!empty($selectedIds) && is_array($selectedIds)) {
            $sanitizedIds = array_map('intval', $selectedIds);
            $inClause = implode(',', $sanitizedIds);
            $count = count($sanitizedIds);

            switch ($action) {
                case 'mark_confirmed':
                    $pdo->exec("UPDATE orders SET order_status = 'Confirmed' WHERE id IN ($inClause)");
                    $bulkMessage = "Marked {$count} orders as Confirmed.";
                    break;

                case 'mark_shipped':
                    $couriers = ['Delhivery', 'BlueDart', 'DTDC'];
                    foreach ($sanitizedIds as $oid) {
                        $courier = $couriers[array_rand($couriers)];
                        $awb = strtoupper($courier) . '-ACH-' . rand(100000, 999999);
                        $pdo->prepare("UPDATE orders SET order_status = 'Shipped', tracking_number = COALESCE(NULLIF(tracking_number, ''), ?) WHERE id = ?")
                            ->execute([$awb, $oid]);
                    }
                    $bulkMessage = "Marked {$count} orders as Shipped with courier tracking IDs.";
                    break;

                case 'mark_delivered':
                    $pdo->exec("UPDATE orders SET order_status = 'Delivered', payment_status = 'paid' WHERE id IN ($inClause)");
                    $bulkMessage = "Marked {$count} orders as Delivered and Payment Paid.";
                    break;

                case 'mark_cancelled':
                    $pdo->exec("UPDATE orders SET order_status = 'Cancelled' WHERE id IN ($inClause)");
                    $bulkMessage = "Cancelled {$count} orders.";
                    break;
            }
            log_activity('admin', (int)$adminUser['id'], 'bulk_orders', $bulkMessage);
        }
    }
}

$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

if ($statusFilter !== '') {
    $where[] = "order_status = ?";
    $params[] = $statusFilter;
}

if ($search !== '') {
    $where[] = "(order_number LIKE ? OR customer_name LIKE ? OR customer_email LIKE ? OR customer_mobile LIKE ?)";
    $kw = '%' . $search . '%';
    $params[] = $kw; $params[] = $kw; $params[] = $kw; $params[] = $kw;
}

$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT * FROM orders WHERE $whereSql ORDER BY id DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$allStatuses = ['Pending', 'Confirmed', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled'];

$adminTitle = 'Orders Management - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="font-heading m-0 fs-3">Customer Orders (<?= count($orders) ?>)</h2>
        <p class="text-muted small m-0 mt-1">Track pickle orders, update fulfillment lifecycle, and assign courier dispatch numbers.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/admin/automation.php" class="btn btn-warning text-dark btn-sm rounded-pill fw-bold shadow-sm">
            <i class="bi bi-lightning-charge-fill me-1"></i> Auto-Fulfill Pipeline
        </a>
    </div>
</div>

<?php if ($bulkMessage): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= e($bulkMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Filters Bar -->
<div class="card border-0 rounded-4 shadow-sm p-3 bg-white mb-4">
    <form action="<?= BASE_URL ?>/admin/orders/index.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search by order #, customer name, mobile..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-4">
            <select name="status" class="form-select">
                <option value="">All Fulfillment Statuses</option>
                <?php foreach ($allStatuses as $st): ?>
                    <option value="<?= e($st) ?>" <?= ($statusFilter === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-outline-brand flex-grow-1 rounded-pill">Filter Orders</button>
            <?php if ($search || $statusFilter): ?>
                <a href="<?= BASE_URL ?>/admin/orders/index.php" class="btn btn-outline-secondary rounded-pill">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Orders Table with Bulk Form -->
<form action="<?= BASE_URL ?>/admin/orders/index.php" method="POST" id="bulkForm">
    <?= csrf_field() ?>
    
    <div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
        
        <!-- Bulk Action Controls -->
        <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 mb-3 border-bottom gap-2">
            <div class="d-flex align-items-center gap-2">
                <input type="checkbox" id="selectAllCheckbox" class="form-check-input">
                <label for="selectAllCheckbox" class="small fw-semibold text-muted">Select All</label>
            </div>
            
            <div class="d-flex gap-2 align-items-center">
                <span class="small text-muted d-none d-sm-inline">Bulk Action:</span>
                <select name="bulk_action" class="form-select form-select-sm" style="width: 180px;" required>
                    <option value="">Choose Action...</option>
                    <option value="mark_confirmed">Mark Confirmed</option>
                    <option value="mark_shipped">Mark Shipped (Auto AWB)</option>
                    <option value="mark_delivered">Mark Delivered</option>
                    <option value="mark_cancelled">Cancel Orders</option>
                </select>
                <button type="submit" class="btn btn-dark btn-sm rounded-pill px-3" onclick="return confirm('Apply bulk action to selected orders?');">
                    Apply
                </button>
            </div>
        </div>

        <?php if (!empty($orders)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Destination</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $ord): 
                            $badgeColor = match($ord['order_status']) {
                                'Delivered' => 'bg-success',
                                'Shipped', 'Out for Delivery' => 'bg-info text-dark',
                                'Cancelled' => 'bg-danger',
                                default => 'bg-warning text-dark'
                            };
                        ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="order_ids[]" value="<?= $ord['id'] ?>" class="form-check-input order-row-checkbox">
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/admin/orders/view.php?id=<?= $ord['id'] ?>" class="fw-bold text-dark text-decoration-none">
                                        <?= e($ord['order_number']) ?>
                                    </a>
                                    <?php if ($ord['tracking_number']): ?>
                                        <div class="text-muted" style="font-size:0.7rem;"><i class="bi bi-truck me-1"></i><?= e($ord['tracking_number']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold small"><?= e($ord['customer_name']) ?></div>
                                    <div class="text-muted" style="font-size:0.75rem"><?= e($ord['customer_mobile']) ?></div>
                                </td>
                                <td class="small text-muted">
                                    <?= e($ord['shipping_city']) ?>, <?= e($ord['shipping_state']) ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-success"><?= format_price($ord['total_amount']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border small"><?= strtoupper(e($ord['payment_method'])) ?></span>
                                    <div class="text-muted" style="font-size:0.7rem;"><?= strtoupper(e($ord['payment_status'])) ?></div>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeColor ?>"><?= e($ord['order_status']) ?></span>
                                </td>
                                <td class="small text-muted"><?= format_date($ord['created_at'], 'd M Y') ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>/admin/orders/invoice.php?id=<?= $ord['id'] ?>&print=1" target="_blank" class="btn btn-outline-secondary" title="Print Tax Invoice">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/orders/view.php?id=<?= $ord['id'] ?>" class="btn btn-outline-brand">
                                            Manage
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-cart-x display-4 mb-2 d-block"></i>
                <h5>No orders found</h5>
                <p class="small">No orders matching the selected filter criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</form>

<script>
    // Handle Select All Checkbox
    document.getElementById('selectAllCheckbox')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.order-row-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
