<?php
/**
 * Admin Product Management - Catalog List
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
$pdo = db();

$search = trim($_GET['search'] ?? '');
$catFilter = trim($_GET['category'] ?? '');

$where = ["1=1"];
$params = [];

if ($search !== '') {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ?)";
    $kw = '%' . $search . '%';
    $params[] = $kw;
    $params[] = $kw;
}

if ($catFilter !== '') {
    $where[] = "c.slug = ?";
    $params[] = $catFilter;
}

$whereSql = implode(' AND ', $where);

// Fetch products with variant count
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, COUNT(pv.id) as variant_count
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_variants pv ON p.id = pv.product_id
    WHERE $whereSql
    GROUP BY p.id
    ORDER BY p.id DESC
");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Fetch categories for filter dropdown
$categories = $pdo->query("SELECT name, slug FROM categories ORDER BY name ASC")->fetchAll();

$adminTitle = 'Product Management - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="font-heading m-0 fs-3">Pickle Products (<?= count($products) ?>)</h2>
        <p class="text-muted small m-0 mt-1">Manage pickle catalogue, pricing, weights, ingredients, and stock levels.</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/products/add.php" class="btn btn-brand-primary rounded-pill px-4 shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Add New Product
    </a>
</div>

<!-- Search & Filter Bar -->
<div class="card border-0 rounded-4 shadow-sm p-3 bg-white mb-4">
    <form action="<?= BASE_URL ?>/admin/products/index.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search by pickle name or SKU..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-4">
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat['slug']) ?>" <?= ($catFilter === $cat['slug']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-outline-brand flex-grow-1 rounded-pill">Filter</button>
            <?php if ($search || $catFilter): ?>
                <a href="<?= BASE_URL ?>/admin/products/index.php" class="btn btn-outline-secondary rounded-pill">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Products Table -->
<div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
    <?php if (!empty($products)): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 70px;">Image</th>
                        <th>Product & SKU</th>
                        <th>Category</th>
                        <th>Price / MRP</th>
                        <th>Stock</th>
                        <th>Variants</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $prod): 
                        $stockBadge = $prod['stock'] > 20 ? 'bg-success-subtle text-success border border-success' : 
                                      ($prod['stock'] > 0 ? 'bg-warning-subtle text-dark border border-warning' : 'bg-danger-subtle text-danger border border-danger');
                    ?>
                        <tr>
                            <td>
                                <img src="<?= BASE_URL ?>/uploads/products/<?= e($prod['main_image']) ?>" alt="<?= e($prod['name']) ?>" class="rounded-3 border" style="width: 54px; height: 54px; object-fit: cover;">
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($prod['name']) ?></div>
                                <div class="text-muted small">SKU: <code><?= e($prod['sku']) ?></code></div>
                                <div class="d-flex gap-1 mt-1">
                                    <?php if ($prod['is_bestseller']): ?>
                                        <span class="badge bg-warning text-dark" style="font-size:0.65rem;">Best Seller</span>
                                    <?php endif; ?>
                                    <?php if ($prod['is_new']): ?>
                                        <span class="badge bg-danger" style="font-size:0.65rem;">New</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= e($prod['category_name']) ?></span></td>
                            <td>
                                <div class="fw-bold text-success"><?= format_price($prod['price']) ?></div>
                                <small class="text-muted text-decoration-line-through"><?= format_price($prod['mrp']) ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $stockBadge ?> px-2 py-1">
                                    <?= (int)$prod['stock'] ?> jars
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info-subtle text-dark border"><?= (int)$prod['variant_count'] ?> weights</span>
                            </td>
                            <td>
                                <span class="badge <?= ($prod['status'] === 'active') ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= ucfirst($prod['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="<?= BASE_URL ?>/admin/products/edit.php?id=<?= $prod['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit Product">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/product.php?slug=<?= urlencode($prod['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View in Store">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/products/delete.php?id=<?= $prod['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this pickle product?');" title="Delete">
                                        <i class="bi bi-trash"></i>
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
            <i class="bi bi-box2 display-4 mb-2 d-block"></i>
            <h5>No products found</h5>
            <p class="small">Try changing your search keywords or add a new recipe.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
