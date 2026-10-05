<?php
/**
 * Admin Product Edit Form
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
$pdo = db();
$errors = [];

$productId = (int)($_GET['id'] ?? 0);
if (!$productId) {
    header('Location: ' . BASE_URL . '/admin/products/index.php');
    exit;
}

// Fetch Product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('error', 'Product not found.');
    header('Location: ' . BASE_URL . '/admin/products/index.php');
    exit;
}

// Fetch active categories
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();

// Fetch existing variants
$varStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY price ASC");
$varStmt->execute([$productId]);
$variants = $varStmt->fetchAll();

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Session security token expired. Please try again.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $sku = strtoupper(sanitize($_POST['sku'] ?? ''));
        $shortDesc = sanitize($_POST['short_description'] ?? '');
        $description = $_POST['description'] ?? '';
        $ingredients = sanitize($_POST['ingredients'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $mrp = (float)($_POST['mrp'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 50);
        $weight = sanitize($_POST['weight'] ?? '500g');
        $shelfLife = sanitize($_POST['shelf_life'] ?? '12 Months');
        $storage = sanitize($_POST['storage_instructions'] ?? 'Store in a cool, dry place.');
        $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;
        $isBestseller = !empty($_POST['is_bestseller']) ? 1 : 0;
        $isNew = !empty($_POST['is_new']) ? 1 : 0;
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        $discountPercent = ($mrp > $price && $mrp > 0) ? round((($mrp - $price) / $mrp) * 100) : 0;

        if (empty($name)) $errors[] = 'Product name is required.';
        if (!$categoryId) $errors[] = 'Category must be selected.';
        if ($price <= 0) $errors[] = 'Price must be greater than zero.';

        // Handle Image Replacement
        $mainImageName = $product['main_image'];
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $uploadRes = upload_image($_FILES['main_image'], 'products');
            if ($uploadRes['success']) {
                $mainImageName = $uploadRes['filename'];
            } else {
                $errors[] = $uploadRes['error'];
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $uStmt = $pdo->prepare("
                    UPDATE products SET 
                        category_id = ?, name = ?, sku = ?, short_description = ?, description = ?,
                        ingredients = ?, price = ?, mrp = ?, discount_percent = ?, stock = ?,
                        weight = ?, shelf_life = ?, storage_instructions = ?, main_image = ?,
                        is_featured = ?, is_bestseller = ?, is_new = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $uStmt->execute([
                    $categoryId, $name, $sku, $shortDesc, $description,
                    $ingredients, $price, $mrp, $discountPercent, $stock,
                    $weight, $shelfLife, $storage, $mainImageName,
                    $isFeatured, $isBestseller, $isNew, $status, $productId
                ]);

                // Update Existing Variants
                if (!empty($_POST['existing_variant_id']) && is_array($_POST['existing_variant_id'])) {
                    $uVar = $pdo->prepare("
                        UPDATE product_variants SET 
                            weight_label = ?, price = ?, mrp = ?, discount_percent = ?, stock = ?
                        WHERE id = ? AND product_id = ?
                    ");
                    foreach ($_POST['existing_variant_id'] as $k => $varId) {
                        $vLabel = sanitize($_POST['existing_variant_label'][$k] ?? '');
                        $vPrice = (float)($_POST['existing_variant_price'][$k] ?? 0);
                        $vMrp = (float)($_POST['existing_variant_mrp'][$k] ?? 0);
                        $vStock = (int)($_POST['existing_variant_stock'][$k] ?? 0);
                        $vDisc = ($vMrp > $vPrice && $vMrp > 0) ? round((($vMrp - $vPrice) / $vMrp) * 100) : 0;

                        $uVar->execute([$vLabel, $vPrice, $vMrp, $vDisc, $vStock, $varId, $productId]);
                    }
                }

                // Insert Newly Added Variants
                if (!empty($_POST['new_variant_label']) && is_array($_POST['new_variant_label'])) {
                    $nVar = $pdo->prepare("
                        INSERT INTO product_variants (product_id, weight_label, sku_variant, price, mrp, discount_percent, stock, is_default)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 0)
                    ");
                    foreach ($_POST['new_variant_label'] as $k => $nLabel) {
                        $nLabel = sanitize($nLabel);
                        if (!empty($nLabel)) {
                            $nPrice = (float)($_POST['new_variant_price'][$k] ?? $price);
                            $nMrp = (float)($_POST['new_variant_mrp'][$k] ?? $mrp);
                            $nStock = (int)($_POST['new_variant_stock'][$k] ?? 20);
                            $nDisc = ($nMrp > $nPrice && $nMrp > 0) ? round((($nMrp - $nPrice) / $nMrp) * 100) : 0;
                            $nSku = $sku . '-' . strtoupper(str_replace(' ', '', $nLabel));
                            $nVar->execute([$productId, $nLabel, $nSku, $nPrice, $nMrp, $nDisc, $nStock]);
                        }
                    }
                }

                $pdo->commit();
                set_flash('success', 'Product "' . $name . '" updated successfully.');
                header('Location: ' . BASE_URL . '/admin/products/index.php');
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Update error: ' . $e->getMessage();
            }
        }
    }
}

$adminTitle = 'Edit Pickle - ' . e($product['name']);
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="font-heading m-0 fs-3">Edit: <?= e($product['name']) ?></h2>
        <p class="text-muted small m-0 mt-1">SKU: <code><?= e($product['sku']) ?></code> • Category ID: <?= $product['category_id'] ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/product.php?slug=<?= urlencode($product['slug']) ?>" target="_blank" class="btn btn-outline-brand rounded-pill btn-sm">
            <i class="bi bi-box-arrow-up-right me-1"></i> Preview in Store
        </a>
        <a href="<?= BASE_URL ?>/admin/products/index.php" class="btn btn-outline-secondary rounded-pill btn-sm">
            &larr; Back to Catalog
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger rounded-4 mb-4">
        <ul class="mb-0 small ps-3">
            <?php foreach ($errors as $e): ?>
                <li><?= e($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="<?= BASE_URL ?>/admin/products/edit.php?id=<?= $productId ?>" method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="row g-4">
        
        <!-- Left: Product Details -->
        <div class="col-lg-8">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <h5 class="fw-bold font-heading mb-3">Product Information</h5>
                
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-bold">Pickle Recipe Name *</label>
                        <input type="text" name="name" required class="form-control" value="<?= e($product['name']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Category *</label>
                        <select name="category_id" required class="form-select">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">SKU Code</label>
                        <input type="text" name="sku" required class="form-control" value="<?= e($product['sku']) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold">Short Summary *</label>
                        <textarea name="short_description" rows="2" required class="form-control"><?= e($product['short_description']) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold">Detailed Recipe Description</label>
                        <textarea name="description" rows="5" class="form-control"><?= e($product['description']) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold">Ingredients</label>
                        <textarea name="ingredients" rows="2" class="form-control"><?= e($product['ingredients']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Existing & New Variants -->
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold font-heading m-0">Weight Variants & Sizes</h5>
                    <button type="button" class="btn btn-sm btn-outline-brand rounded-pill" onclick="addNewVariantField()">
                        <i class="bi bi-plus"></i> Add New Size
                    </button>
                </div>

                <div class="d-flex flex-column gap-2 mb-3">
                    <?php foreach ($variants as $var): ?>
                        <div class="row g-2 align-items-center p-2 rounded-3 bg-light border">
                            <input type="hidden" name="existing_variant_id[]" value="<?= $var['id'] ?>">
                            <div class="col-md-3">
                                <label class="small text-muted">Weight</label>
                                <input type="text" name="existing_variant_label[]" class="form-control form-control-sm" value="<?= e($var['weight_label']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted">Price (₹)</label>
                                <input type="number" step="0.01" name="existing_variant_price[]" class="form-control form-control-sm" value="<?= (float)$var['price'] ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted">MRP (₹)</label>
                                <input type="number" step="0.01" name="existing_variant_mrp[]" class="form-control form-control-sm" value="<?= (float)$var['mrp'] ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted">Stock Qty</label>
                                <input type="number" name="existing_variant_stock[]" class="form-control form-control-sm" value="<?= (int)$var['stock'] ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="newVariantsContainer" class="d-flex flex-column gap-2"></div>
            </div>
        </div>

        <!-- Right: Image & Controls -->
        <div class="col-lg-4">
            
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <h5 class="fw-bold font-heading mb-3">Product Jar Image</h5>
                <div class="text-center p-3 bg-light rounded-3 border mb-3">
                    <img id="imgPreview" src="<?= BASE_URL ?>/uploads/products/<?= e($product['main_image']) ?>" alt="Preview" class="img-fluid rounded-2" style="max-height: 180px;">
                </div>
                <div>
                    <label class="form-label small fw-bold">Replace Image (Optional)</label>
                    <input type="file" name="main_image" class="form-control" accept="image/*" onchange="previewImage(this)">
                </div>
            </div>

            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <h5 class="fw-bold font-heading mb-3">Base Pricing & Shelf Life</h5>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Base Price (₹) *</label>
                    <input type="number" step="0.01" name="price" required class="form-control" value="<?= (float)$product['price'] ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Base MRP (₹)</label>
                    <input type="number" step="0.01" name="mrp" class="form-control" value="<?= (float)$product['mrp'] ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Total Stock</label>
                    <input type="number" name="stock" class="form-control" value="<?= (int)$product['stock'] ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Shelf Life</label>
                    <input type="text" name="shelf_life" class="form-control" value="<?= e($product['shelf_life']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Storage Guidelines</label>
                    <input type="text" name="storage_instructions" class="form-control" value="<?= e($product['storage_instructions']) ?>">
                </div>
            </div>

            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <h5 class="fw-bold font-heading mb-3">Tags & Status</h5>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_bestseller" value="1" id="switchBestseller" <?= $product['is_bestseller'] ? 'checked' : '' ?>>
                    <label class="form-check-label small fw-semibold" for="switchBestseller">⭐ Best Seller Tag</label>
                </div>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_new" value="1" id="switchNew" <?= $product['is_new'] ? 'checked' : '' ?>>
                    <label class="form-check-label small fw-semibold" for="switchNew">✨ New Arrival Tag</label>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="switchFeatured" <?= $product['is_featured'] ? 'checked' : '' ?>>
                    <label class="form-check-label small fw-semibold" for="switchFeatured">🔥 Featured Tag</label>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
                        <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-brand-primary w-100 py-3 rounded-pill fw-bold shadow">
                    <i class="bi bi-check-circle me-2"></i> Update Pickle Product
                </button>
            </div>

        </div>

    </div>
</form>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imgPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function addNewVariantField() {
    const container = document.getElementById('newVariantsContainer');
    const div = document.createElement('div');
    div.className = 'row g-2 align-items-center p-2 rounded-3 bg-light border';
    div.innerHTML = `
        <div class="col-md-3">
            <label class="small text-muted">New Weight</label>
            <input type="text" name="new_variant_label[]" class="form-control form-control-sm" placeholder="e.g. 2kg" required>
        </div>
        <div class="col-md-3">
            <label class="small text-muted">Price (₹)</label>
            <input type="number" step="0.01" name="new_variant_price[]" class="form-control form-control-sm" placeholder="₹" required>
        </div>
        <div class="col-md-3">
            <label class="small text-muted">MRP (₹)</label>
            <input type="number" step="0.01" name="new_variant_mrp[]" class="form-control form-control-sm" placeholder="₹" required>
        </div>
        <div class="col-md-2">
            <label class="small text-muted">Stock</label>
            <input type="number" name="new_variant_stock[]" class="form-control form-control-sm" value="25" placeholder="Qty">
        </div>
        <div class="col-md-1 text-end pt-3">
            <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('.row').remove();"><i class="bi bi-trash"></i></button>
        </div>
    `;
    container.appendChild(div);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
