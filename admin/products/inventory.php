<?php
/**
 * Admin Inventory Management
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
$pdo = db();

// Handle Bulk Stock Update
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    if (verify_csrf()) {
        if (!empty($_POST['stock']) && is_array($_POST['stock'])) {
            $uStmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
            foreach ($_POST['stock'] as $pId => $qty) {
                $uStmt->execute([max(0, (int)$qty), (int)$pId]);
            }
        }

        if (!empty($_POST['variant_stock']) && is_array($_POST['variant_stock'])) {
            $vStmt = $pdo->prepare("UPDATE product_variants SET stock = ? WHERE id = ?");
            foreach ($_POST['variant_stock'] as $vId => $vQty) {
                $vStmt->execute([max(0, (int)$vQty), (int)$vId]);
            }
        }

        set_flash('success', 'Inventory stock levels synchronized successfully.');
        header('Location: ' . BASE_URL . '/admin/products/inventory.php');
        exit;
    }
}

// Fetch all products with variants
$stmt = $pdo->query("
    SELECT p.id, p.name, p.sku, p.stock, p.main_image, p.price, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active'
    ORDER BY p.stock ASC
");
$products = $stmt->fetchAll();

// Fetch variants mapped by product_id
$vRows = $pdo->query("SELECT * FROM product_variants ORDER BY price ASC")->fetchAll();
$variantsByProduct = [];
foreach ($vRows as $v) {
    $variantsByProduct[$v['product_id']][] = $v;
}

$adminTitle = 'Inventory & Stock Control - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="font-heading m-0 fs-3">Inventory & Stock Control</h2>
        <p class="text-muted small m-0 mt-1">Live jar stock monitoring, low-inventory alerts, and bulk restocking.</p>
    </div>
</div>

<form action="<?= BASE_URL ?>/admin/products/inventory.php" method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="update_stock" value="1">

    <div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted small">Products with stock under 20 are highlighted for immediate sun-curing restock.</span>
            <button type="submit" class="btn btn-brand-primary btn-sm rounded-pill px-4 shadow-sm">
                <i class="bi bi-save me-1"></i> Save All Stock Updates
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 60px;">Image</th>
                        <th>Product & SKU</th>
                        <th>Category</th>
                        <th>Master Stock (Jars)</th>
                        <th>Status</th>
                        <th>Weight Variants Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $prod): 
                        $isLow = $prod['stock'] <= 20;
                        $isOut = $prod['stock'] <= 0;
                        $statusBadge = $isOut ? 'bg-danger text-white' : ($isLow ? 'bg-warning text-dark' : 'bg-success text-white');
                        $statusLabel = $isOut ? 'Out of Stock' : ($isLow ? 'Low Stock' : 'In Stock');
                    ?>
                        <tr class="<?= $isLow ? 'table-warning-subtle' : '' ?>">
                            <td>
                                <img src="<?= BASE_URL ?>/uploads/products/<?= e($prod['main_image']) ?>" class="rounded-2 border" style="width: 44px; height: 44px; object-fit: cover;">
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($prod['name']) ?></div>
                                <div class="text-muted small">SKU: <code><?= e($prod['sku']) ?></code></div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= e($prod['category_name']) ?></span></td>
                            <td style="max-width: 140px;">
                                <div class="input-group input-group-sm">
                                    <input type="number" name="stock[<?= $prod['id'] ?>]" class="form-control fw-bold <?= $isLow ? 'text-danger' : 'text-success' ?>" value="<?= (int)$prod['stock'] ?>" min="0">
                                    <span class="input-group-text bg-light">jars</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $statusBadge ?> px-2 py-1"><?= $statusLabel ?></span>
                            </td>
                            <td>
                                <?php if (!empty($variantsByProduct[$prod['id']])): ?>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($variantsByProduct[$prod['id']] as $var): ?>
                                            <div class="d-flex align-items-center gap-1 bg-light border p-1 rounded-2" style="font-size: 0.78rem;">
                                                <span class="fw-semibold"><?= e($var['weight_label']) ?>:</span>
                                                <input type="number" name="variant_stock[<?= $var['id'] ?>]" class="form-control form-control-sm text-center p-0" style="width: 45px; height: 24px;" value="<?= (int)$var['stock'] ?>" min="0">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">Standard 500g</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-3 border-top text-end">
            <button type="submit" class="btn btn-brand-primary rounded-pill px-5 shadow-sm">
                <i class="bi bi-save me-2"></i> Save All Stock Updates
            </button>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
