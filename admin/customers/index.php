<?php
/**
 * Admin Customer Management
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
$pdo = db();

// Handle Status Toggle
if (isset($_GET['toggle_status'])) {
    $custToggleId = (int)$_GET['toggle_status'];
    $c = $pdo->prepare("SELECT status FROM customers WHERE id = ?");
    $c->execute([$custToggleId]);
    $row = $c->fetch();
    if ($row) {
        $newStatus = ($row['status'] === 'active') ? 'banned' : 'active';
        $pdo->prepare("UPDATE customers SET status = ? WHERE id = ?")->execute([$newStatus, $custToggleId]);
        set_flash('info', 'Customer status updated to ' . $newStatus . '.');
    }
    header('Location: ' . BASE_URL . '/admin/customers/index.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$where = ["1=1"];
$params = [];

if ($search !== '') {
    $where[] = "(c.name LIKE ? OR c.email LIKE ? OR c.mobile LIKE ?)";
    $kw = '%' . $search . '%';
    $params[] = $kw; $params[] = $kw; $params[] = $kw;
}

$whereSql = implode(' AND ', $where);

// Fetch customers with total orders and lifetime spend
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(o.id) as total_orders,
           COALESCE(SUM(CASE WHEN o.payment_status = 'paid' OR o.order_status = 'Delivered' THEN o.total_amount ELSE 0 END), 0) as total_spent
    FROM customers c
    LEFT JOIN orders o ON c.id = o.customer_id
    WHERE $whereSql
    GROUP BY c.id
    ORDER BY c.id DESC
");
$stmt->execute($params);
$customers = $stmt->fetchAll();

$adminTitle = 'Customer Management - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="font-heading m-0 fs-3">Customer Accounts (<?= count($customers) ?>)</h2>
        <p class="text-muted small m-0 mt-1">Review verified diners, order frequencies, and account statuses.</p>
    </div>
</div>

<!-- Search Bar -->
<div class="card border-0 rounded-4 shadow-sm p-3 bg-white mb-4">
    <form action="<?= BASE_URL ?>/admin/customers/index.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-9">
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search customer by name, email, or mobile number..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-outline-brand flex-grow-1 rounded-pill">Search Customers</button>
            <?php if ($search): ?>
                <a href="<?= BASE_URL ?>/admin/customers/index.php" class="btn btn-outline-secondary rounded-pill">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Customers Table -->
<div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
    <?php if (!empty($customers)): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>Customer</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Joined On</th>
                        <th>Total Orders</th>
                        <th>Total Spend</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $cust): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; font-size: 0.9rem;">
                                        <?= strtoupper(substr($cust['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= e($cust['name']) ?></div>
                                        <small class="text-muted">ID: #<?= $cust['id'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($cust['mobile']) ?></td>
                            <td><a href="mailto:<?= e($cust['email']) ?>" class="text-decoration-none"><?= e($cust['email']) ?></a></td>
                            <td class="small text-muted"><?= format_date($cust['created_at'], 'd M Y') ?></td>
                            <td><span class="badge bg-light text-dark border"><?= (int)$cust['total_orders'] ?> orders</span></td>
                            <td class="fw-bold text-success"><?= format_price($cust['total_spent']) ?></td>
                            <td>
                                <span class="badge <?= ($cust['status'] === 'active') ? 'bg-success' : 'bg-danger' ?>">
                                    <?= ucfirst($cust['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/admin/customers/index.php?toggle_status=<?= $cust['id'] ?>" 
                                   class="btn btn-sm <?= ($cust['status'] === 'active') ? 'btn-outline-danger' : 'btn-outline-success' ?> rounded-pill px-3"
                                   onclick="return confirm('Change status for this customer?');">
                                    <?= ($cust['status'] === 'active') ? 'Ban Account' : 'Activate' ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-people display-4 mb-2 d-block"></i>
            <h5>No customers found</h5>
            <p class="small">Try searching with a different name, email or mobile number.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
