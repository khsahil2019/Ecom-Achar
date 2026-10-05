<?php
/**
 * Admin Product Add Form
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
$pdo = db();
$errors = [];

// Fetch active categories
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();

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
        $storage = sanitize($_POST['storage_instructions'] ?? 'Store in a cool, dry place. Always use a dry spoon.');
        $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;
        $isBestseller = !empty($_POST['is_bestseller']) ? 1 : 0;
        $isNew = !empty($_POST['is_new']) ? 1 : 0;
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        // Discount percent calculation
        $discountPercent = ($mrp > $price && $mrp > 0) ? round((($mrp - $price) / $mrp) * 100) : 0;
        $slug = slugify($name);

        if (empty($name)) $errors[] = 'Product name is required.';
        if (!$categoryId) $errors[] = 'Category must be selected.';
        if (empty($sku)) $sku = 'ACH-' . strtoupper(bin2hex(random_bytes(3)));
        if ($price <= 0) $errors[] = 'Base price must be greater than zero.';

        // Handle Image Upload
        $mainImageName = 'prod-mango.jpg'; // default fallback
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $uploadRes = upload_image($_FILES['main_image'], 'products');
            if ($uploadRes['success']) {
                $mainImageName = $uploadRes['filename'];
            } else {
                $errors[] = $uploadRes['error'];
            }
        }

        // Check SKU uniqueness
        $checkSku = $pdo->prepare("SELECT id FROM products WHERE sku = ? OR slug = ? LIMIT 1");
        $checkSku->execute([$sku, $slug]);
        if ($checkSku->fetch()) {
            $slug .= '-' . rand(10, 99);
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    INSERT INTO products (
                        category_id, name, slug, sku, short_description, description, ingredients,
                        price, mrp, discount_percent, stock, weight, shelf_life, storage_instructions,
                        main_image, is_featured, is_bestseller, is_new, status
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?
                    )
                ");
                $stmt->execute([
                    $categoryId, $name, $slug, $sku, $shortDesc, $description, $ingredients,
                    $price, $mrp, $discountPercent, $stock, $weight, $shelfLife, $storage,
                    $mainImageName, $isFeatured, $isBestseller, $isNew, $status
                ]);
                $productId = (int)$pdo->lastInsertId();

                // Save Variants if provided
                if (!empty($_POST['variant_label']) && is_array($_POST['variant_label'])) {
                    $vIns = $pdo->prepare("
                        INSERT INTO product_variants (product_id, weight_label, sku_variant, price, mrp, discount_percent, stock, is_default)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($_POST['variant_label'] as $i => $vLabel) {
                        $vLabel = sanitize($vLabel);
                        if (!empty($vLabel)) {
                            $vPrice = (float)($_POST['variant_price'][$i] ?? $price);
                            $vMrp = (float)($_POST['variant_mrp'][$i] ?? $mrp);
                            $vStock = (int)($_POST['variant_stock'][$i] ?? 25);
                            $vDisc = ($vMrp > $vPrice && $vMrp > 0) ? round((($vMrp - $vPrice) / $vMrp) * 100) : 0;
                            $vSku = $sku . '-' . strtoupper(str_replace(' ', '', $vLabel));
                            $vDefault = ($i === 0) ? 1 : 0;
                            $vIns->execute([$productId, $vLabel, $vSku, $vPrice, $vMrp, $vDisc, $vStock, $vDefault]);
                        }
                    }
                }

                $pdo->commit();
                set_flash('success', 'Pickle product "' . $name . '" created successfully.');
                header('Location: ' . BASE_URL . '/admin/products/index.php');
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Failed to save product: ' . $e->getMessage();
            }
        }
    }
}

$adminTitle = 'Add New Pickle Product - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="font-heading m-0 fs-3">Add New Pickle Product</h2>
        <p class="text-muted small m-0 mt-1">Introduce a new handcrafted pickle recipe to the online storefront.</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/products/index.php" class="btn btn-outline-secondary rounded-pill px-4">
        &larr; Back to Catalog
    </a>
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

<form action="<?= BASE_URL ?>/admin/products/add.php" method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="row g-4">
        
        <!-- Left: Product Details -->
        <div class="col-lg-8">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <h5 class="fw-bold font-heading mb-3">Product Overview</h5>
                
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-bold">Pickle Recipe Name *</label>
                        <input type="text" name="name" required class="form-control" placeholder="e.g. Traditional Sun-Cured Hing Mango Achar" value="<?= e($_POST['name'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Category *</label>
                        <select name="category_id" required class="form-select">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">SKU Code (Auto if blank)</label>
                        <input type="text" name="sku" class="form-control" placeholder="e.g. ACH-MNG-101" value="<?= e($_POST['sku'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold">Short Description (Cards & Search) *</label>
                        <textarea name="short_description" rows="2" required class="form-control" placeholder="Crisp raw mangoes slow-steeped in pure cold pressed mustard oil and crushed roasted methi seeds."><?= e($_POST['short_description'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold">Full Heritage Story & Description</label>
                        <textarea name="description" rows="5" class="form-control" placeholder="Detailed culinary story, traditional rooftop sun incubation details..."><?= e($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold">Authentic Ingredients List</label>
                        <textarea name="ingredients" rows="2" class="form-control" placeholder="Raw Green Mangoes, Pure Cold-Pressed Mustard Oil, Fenugreek Seeds, Fennel (Saunf), Rock Salt..."><?= e($_POST['ingredients'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Weight Variants Builder -->
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold font-heading m-0">Weight Variants (Packaging Sizes)</h5>
                    <button type="button" class="btn btn-sm btn-outline-brand rounded-pill" onclick="addVariantRow()">
                        <i class="bi bi-plus"></i> Add Variant Size
                    </button>
                </div>
                <p class="text-muted small">Configure specific weight jars with individual pricing and stock thresholds.</p>

                <div id="variantsContainer" class="d-flex flex-column gap-3">
                    <!-- Default Initial 3 Variants -->
                    <div class="row g-2 align-items-center variant-row p-2 rounded-3 bg-light border">
                        <div class="col-md-3">
                            <label class="small text-muted">Weight Label</label>
                            <input type="text" name="variant_label[]" class="form-control form-control-sm" value="250g" placeholder="e.g. 250g">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted">Selling Price (₹)</label>
                            <input type="number" step="0.01" name="variant_price[]" class="form-control form-control-sm" value="149" placeholder="₹">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted">MRP (₹)</label>
                            <input type="number" step="0.01" name="variant_mrp[]" class="form-control form-control-sm" value="179" placeholder="₹">
                        </div>
                        <div class="col-md-2">
                            <label class="small text-muted">Stock Qty</label>
                            <input type="number" name="variant_stock[]" class="form-control form-control-sm" value="40" placeholder="Qty">
                        </div>
                        <div class="col-md-1 text-end pt-3">
                            <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('.variant-row').remove();"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>

                    <div class="row g-2 align-items-center variant-row p-2 rounded-3 bg-light border">
                        <div class="col-md-3">
                            <label class="small text-muted">Weight Label</label>
                            <input type="text" name="variant_label[]" class="form-control form-control-sm" value="500g" placeholder="e.g. 500g">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted">Selling Price (₹)</label>
                            <input type="number" step="0.01" name="variant_price[]" class="form-control form-control-sm" value="269" placeholder="₹">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted">MRP (₹)</label>
                            <input type="number" step="0.01" name="variant_mrp[]" class="form-control form-control-sm" value="299" placeholder="₹">
                        </div>
                        <div class="col-md-2">
                            <label class="small text-muted">Stock Qty</label>
                            <input type="number" name="variant_stock[]" class="form-control form-control-sm" value="50" placeholder="Qty">
                        </div>
                        <div class="col-md-1 text-end pt-3">
                            <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('.variant-row').remove();"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>

                    <div class="row g-2 align-items-center variant-row p-2 rounded-3 bg-light border">
                        <div class="col-md-3">
                            <label class="small text-muted">Weight Label</label>
                            <input type="text" name="variant_label[]" class="form-control form-control-sm" value="1kg" placeholder="e.g. 1kg">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted">Selling Price (₹)</label>
                            <input type="number" step="0.01" name="variant_price[]" class="form-control form-control-sm" value="499" placeholder="₹">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted">MRP (₹)</label>
                            <input type="number" step="0.01" name="variant_mrp[]" class="form-control form-control-sm" value="560" placeholder="₹">
                        </div>
                        <div class="col-md-2">
                            <label class="small text-muted">Stock Qty</label>
                            <input type="number" name="variant_stock[]" class="form-control form-control-sm" value="25" placeholder="Qty">
                        </div>
                        <div class="col-md-1 text-end pt-3">
                            <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('.variant-row').remove();"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Image Upload & Commercial Settings -->
        <div class="col-lg-4">
            
            <!-- Image Card -->
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <h5 class="fw-bold font-heading mb-3">Primary Jar Image</h5>
                <div class="mb-3">
                    <input type="file" name="main_image" class="form-control" accept="image/*" onchange="previewImage(this)">
                    <small class="text-muted">Accepted formats: JPG, PNG, WEBP (Max 5MB)</small>
                </div>
                <div class="text-center p-3 bg-light rounded-3 border">
                    <img id="imgPreview" src="<?= BASE_URL ?>/uploads/products/prod-mango.jpg" alt="Preview" class="img-fluid rounded-2" style="max-height: 180px;">
                </div>
            </div>

            <!-- Base Commercials -->
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <h5 class="fw-bold font-heading mb-3">Base Pricing & Specs</h5>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Base Price (₹) *</label>
                    <input type="number" step="0.01" name="price" required class="form-control" placeholder="269.00" value="<?= e($_POST['price'] ?? '269.00') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Base MRP (₹)</label>
                    <input type="number" step="0.01" name="mrp" class="form-control" placeholder="299.00" value="<?= e($_POST['mrp'] ?? '299.00') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Total Stock Quantity</label>
                    <input type="number" name="stock" class="form-control" placeholder="50" value="<?= e($_POST['stock'] ?? '50') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Default Weight Display</label>
                    <input type="text" name="weight" class="form-control" value="500g">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Shelf Life</label>
                    <input type="text" name="shelf_life" class="form-control" value="12 Months">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Storage Guidelines</label>
                    <input type="text" name="storage_instructions" class="form-control" value="Store in a cool, dry place. Always use a dry spoon.">
                </div>
            </div>

            <!-- Flags & Visibility -->
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <h5 class="fw-bold font-heading mb-3">Visibility & Tags</h5>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_bestseller" value="1" id="switchBestseller">
                    <label class="form-check-label small fw-semibold" for="switchBestseller">⭐ Mark as Best Seller</label>
                </div>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_new" value="1" id="switchNew" checked>
                    <label class="form-check-label small fw-semibold" for="switchNew">✨ Mark as New Arrival</label>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="switchFeatured">
                    <label class="form-check-label small fw-semibold" for="switchFeatured">🔥 Feature on Homepage</label>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Publish Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active (Visible in Store)</option>
                        <option value="inactive">Inactive / Hidden</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-brand-primary w-100 py-3 rounded-pill fw-bold shadow">
                    <i class="bi bi-cloud-upload me-2"></i> Publish Pickle Product
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

function addVariantRow() {
    const container = document.getElementById('variantsContainer');
    const div = document.createElement('div');
    div.className = 'row g-2 align-items-center variant-row p-2 rounded-3 bg-light border';
    div.innerHTML = `
        <div class="col-md-3">
            <label class="small text-muted">Weight Label</label>
            <input type="text" name="variant_label[]" class="form-control form-control-sm" placeholder="e.g. 2kg Jar">
        </div>
        <div class="col-md-3">
            <label class="small text-muted">Selling Price (₹)</label>
            <input type="number" step="0.01" name="variant_price[]" class="form-control form-control-sm" placeholder="₹">
        </div>
        <div class="col-md-3">
            <label class="small text-muted">MRP (₹)</label>
            <input type="number" step="0.01" name="variant_mrp[]" class="form-control form-control-sm" placeholder="₹">
        </div>
        <div class="col-md-2">
            <label class="small text-muted">Stock Qty</label>
            <input type="number" name="variant_stock[]" class="form-control form-control-sm" value="20" placeholder="Qty">
        </div>
        <div class="col-md-1 text-end pt-3">
            <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('.variant-row').remove();"><i class="bi bi-trash"></i></button>
        </div>
    `;
    container.appendChild(div);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
