<?php
/**
 * Admin Category Management
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
$pdo = db();
$errors = [];

// Handle Create Category
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    if (verify_csrf()) {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
        $slug = slugify($name);

        if (empty($name)) {
            $errors[] = 'Category name is required.';
        } else {
            // Check unique slug
            $cCheck = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
            $cCheck->execute([$slug]);
            if ($cCheck->fetch()) {
                $slug .= '-' . rand(10, 99);
            }

            $imageName = 'cat-mango.jpg';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $up = upload_image($_FILES['image'], 'categories');
                if ($up['success']) {
                    $imageName = $up['filename'];
                }
            }

            $ins = $pdo->prepare("
                INSERT INTO categories (name, slug, description, image, status, display_order)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$name, $slug, $description, $imageName, $status, $displayOrder]);
            set_flash('success', 'Category "' . $name . '" created.');
            header('Location: ' . BASE_URL . '/admin/categories/index.php');
            exit;
        }
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $pCheck = $pdo->prepare("SELECT COUNT(*) as cnt FROM products WHERE category_id = ?");
    $pCheck->execute([$delId]);
    if ($pCheck->fetch()['cnt'] > 0) {
        set_flash('error', 'Cannot delete category: products are assigned to this category.');
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$delId]);
        set_flash('info', 'Category removed.');
    }
    header('Location: ' . BASE_URL . '/admin/categories/index.php');
    exit;
}

// Fetch all categories with product count
$stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.display_order ASC
");
$categories = $stmt->fetchAll();

$adminTitle = 'Categories Management - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="font-heading m-0 fs-3">Pickle Categories (<?= count($categories) ?>)</h2>
        <p class="text-muted small m-0 mt-1">Organize your pickle jars by flavor, vegetable, citrus, or heritage combos.</p>
    </div>
    <button class="btn btn-brand-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="bi bi-plus-lg me-1"></i> Add Category
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

<!-- Categories Grid / Table -->
<div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th style="width: 70px;">Image</th>
                    <th>Category Name & Slug</th>
                    <th>Description</th>
                    <th>Assigned Pickles</th>
                    <th>Display Order</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td>
                            <img src="<?= BASE_URL ?>/uploads/categories/<?= e($cat['image'] ?: 'cat-mango.jpg') ?>" class="rounded-2 border" style="width: 50px; height: 50px; object-fit: cover;">
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= e($cat['name']) ?></div>
                            <code class="small text-muted">/category/<?= e($cat['slug']) ?></code>
                        </td>
                        <td class="small text-muted" style="max-width: 250px;">
                            <?= e($cat['description'] ?? 'Traditional recipe blend') ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= (int)$cat['product_count'] ?> recipes</span>
                        </td>
                        <td><?= (int)$cat['display_order'] ?></td>
                        <td>
                            <span class="badge <?= ($cat['status'] === 'active') ? 'bg-success' : 'bg-secondary' ?>">
                                <?= ucfirst($cat['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/shop.php?category=<?= urlencode($cat['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View Storefront">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/admin/categories/index.php?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Delete category?');" title="Delete">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCatLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title font-heading" id="addCatLabel">Create Pickle Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/categories/index.php" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="add_category" value="1">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category Name *</label>
                        <input type="text" name="name" required class="form-control" placeholder="e.g. Royal Heritage Combos">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" rows="3" class="form-control" placeholder="Brief flavor summary..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="0">
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
                    <button type="submit" class="btn btn-brand-primary rounded-pill px-4">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
