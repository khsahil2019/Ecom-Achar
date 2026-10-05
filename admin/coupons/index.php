<?php
/**
 * Admin Coupon Management
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
$pdo = db();
$errors = [];

// Handle Create Coupon
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    if (verify_csrf()) {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = in_array($_POST['type'] ?? '', ['percentage', 'fixed']) ? $_POST['type'] : 'percentage';
        $value = (float)($_POST['value'] ?? 0);
        $minOrder = (float)($_POST['min_order_amount'] ?? 0);
        $maxDiscount = !empty($_POST['max_discount_amount']) ? (float)$_POST['max_discount_amount'] : null;
        $usageLimit = max(1, (int)($_POST['usage_limit'] ?? 500));
        $startDate = $_POST['start_date'] ?? date('Y-m-d');
        $endDate = $_POST['end_date'] ?? date('Y-m-d', strtotime('+1 year'));
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if (empty($code) || $value <= 0) {
            $errors[] = 'Valid coupon code and positive discount value are required.';
        } else {
            // Check code uniqueness
            $check = $pdo->prepare("SELECT id FROM coupons WHERE code = ?");
            $check->execute([$code]);
            if ($check->fetch()) {
                $errors[] = 'A coupon with code "' . $code . '" already exists.';
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO coupons (code, type, value, min_order_amount, max_discount_amount, usage_limit, start_date, end_date, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([$code, $type, $value, $minOrder, $maxDiscount, $usageLimit, $startDate, $endDate, $status]);
                set_flash('success', 'Coupon ' . $code . ' created.');
                header('Location: ' . BASE_URL . '/admin/coupons/index.php');
                exit;
            }
        }
    }
}

// Handle Delete Coupon
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([$delId]);
    set_flash('info', 'Coupon removed.');
    header('Location: ' . BASE_URL . '/admin/coupons/index.php');
    exit;
}

// Fetch Coupons
$coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();

$adminTitle = 'Coupons & Vouchers - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="font-heading m-0 fs-3">Discount Coupons (<?= count($coupons) ?>)</h2>
        <p class="text-muted small m-0 mt-1">Configure percentage or flat discounts, festive promotions, and usage limits.</p>
    </div>
    <button class="btn btn-brand-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addCouponModal">
        <i class="bi bi-plus-lg me-1"></i> Create Coupon
    </button>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger rounded-4 mb-4 small">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $e): ?>
                <li><?= e($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Coupons Table -->
<div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th>Code</th>
                    <th>Discount</th>
                    <th>Min. Order</th>
                    <th>Max Cap</th>
                    <th>Usage</th>
                    <th>Validity</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $cp): ?>
                    <tr>
                        <td>
                            <strong class="text-dark fs-6 font-monospace"><?= e($cp['code']) ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-success-subtle text-success border border-success fw-bold">
                                <?= $cp['type'] === 'percentage' ? ((int)$cp['value'] . '% OFF') : format_price($cp['value']) . ' OFF' ?>
                            </span>
                        </td>
                        <td class="small"><?= format_price($cp['min_order_amount']) ?></td>
                        <td class="small"><?= $cp['max_discount_amount'] ? format_price($cp['max_discount_amount']) : 'No Cap' ?></td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= (int)$cp['used_count'] ?> / <?= (int)$cp['usage_limit'] ?></span>
                        </td>
                        <td class="small text-muted">
                            <?= date('d M Y', strtotime($cp['start_date'])) ?> to <?= date('d M Y', strtotime($cp['end_date'])) ?>
                        </td>
                        <td>
                            <span class="badge <?= ($cp['status'] === 'active') ? 'bg-success' : 'bg-secondary' ?>">
                                <?= ucfirst($cp['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/admin/coupons/index.php?delete=<?= $cp['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this coupon?');" title="Delete">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Coupon Modal -->
<div class="modal fade" id="addCouponModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title font-heading">Create New Coupon Voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/coupons/index.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="add_coupon" value="1">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Coupon Code *</label>
                        <input type="text" name="code" required class="form-control text-uppercase" placeholder="e.g. DIWALI20">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Discount Type</label>
                            <select name="type" class="form-select">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (₹)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Value *</label>
                            <input type="number" step="0.01" name="value" required class="form-control" placeholder="10 or 100">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Min. Order Amount (₹)</label>
                            <input type="number" step="0.01" name="min_order_amount" class="form-control" value="299">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Max Discount Cap (₹)</label>
                            <input type="number" step="0.01" name="max_discount_amount" class="form-control" placeholder="150 (Leave blank if none)">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Valid From</label>
                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Valid Until</label>
                            <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Usage Limit</label>
                            <input type="number" name="usage_limit" class="form-control" value="500">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand-primary rounded-pill px-4">Create Voucher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
