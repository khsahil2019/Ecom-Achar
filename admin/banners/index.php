<?php
/**
 * Admin Banner & Promotions Management
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
$pdo = db();
$errors = [];

// Handle Add Banner
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_banner'])) {
    if (verify_csrf()) {
        $title = sanitize($_POST['title'] ?? '');
        $subtitle = sanitize($_POST['subtitle'] ?? '');
        $badgeText = sanitize($_POST['badge_text'] ?? '');
        $buttonText = sanitize($_POST['button_text'] ?? 'Shop Now');
        $buttonUrl = sanitize($_POST['button_url'] ?? '/shop.php');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        $imageName = 'banner-hero.jpg';
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $up = upload_image($_FILES['banner_image'], 'banners');
            if ($up['success']) {
                $imageName = $up['filename'];
            }
        }

        if (empty($title)) {
            $errors[] = 'Banner title is required.';
        } else {
            $ins = $pdo->prepare("
                INSERT INTO banners (title, subtitle, badge_text, image, button_text, button_url, sort_order, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$title, $subtitle, $badgeText, $imageName, $buttonText, $buttonUrl, $sortOrder, $status]);
            set_flash('success', 'Banner created successfully.');
            header('Location: ' . BASE_URL . '/admin/banners/index.php');
            exit;
        }
    }
}

// Handle Delete Banner
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM banners WHERE id = ?")->execute([$delId]);
    set_flash('info', 'Banner removed.');
    header('Location: ' . BASE_URL . '/admin/banners/index.php');
    exit;
}

$banners = $pdo->query("SELECT * FROM banners ORDER BY sort_order ASC, id DESC")->fetchAll();

$adminTitle = 'Banners & Promotions - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="font-heading m-0 fs-3">Homepage Banners (<?= count($banners) ?>)</h2>
        <p class="text-muted small m-0 mt-1">Manage festive promotional banners, hero carousel graphics, and offer links.</p>
    </div>
    <button class="btn btn-brand-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addBannerModal">
        <i class="bi bi-plus-lg me-1"></i> Add New Banner
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

<div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th style="width: 140px;">Preview</th>
                    <th>Title & Subtitle</th>
                    <th>Button</th>
                    <th>Sort Order</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($banners as $bn): ?>
                    <tr>
                        <td>
                            <img src="<?= BASE_URL ?>/uploads/banners/<?= e($bn['image']) ?>" class="rounded-3 border" style="width: 120px; height: 60px; object-fit: cover;">
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= e($bn['title']) ?></div>
                            <div class="text-muted small"><?= e($bn['subtitle']) ?></div>
                            <?php if ($bn['badge_text']): ?>
                                <span class="badge bg-warning text-dark small mt-1"><?= e($bn['badge_text']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL . e($bn['button_url']) ?>" target="_blank" class="btn btn-sm btn-outline-brand rounded-pill">
                                <?= e($bn['button_text']) ?> &rarr;
                            </a>
                        </td>
                        <td><?= (int)$bn['sort_order'] ?></td>
                        <td>
                            <span class="badge <?= ($bn['status'] === 'active') ? 'bg-success' : 'bg-secondary' ?>">
                                <?= ucfirst($bn['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/admin/banners/index.php?delete=<?= $bn['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete banner?');" title="Delete">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Banner Modal -->
<div class="modal fade" id="addBannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title font-heading">Add Storefront Banner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/admin/banners/index.php" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="add_banner" value="1">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Banner Headline *</label>
                        <input type="text" name="title" required class="form-control" placeholder="e.g. Traditional Sun-Cured Pickles">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Subtitle</label>
                        <input type="text" name="subtitle" class="form-control" placeholder="e.g. Pure Cold Pressed Mustard Oil • Zero Chemicals">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Badge Text</label>
                        <input type="text" name="badge_text" class="form-control" placeholder="e.g. Special Festive Offer">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Banner Image</label>
                        <input type="file" name="banner_image" class="form-control" accept="image/*">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Button Text</label>
                            <input type="text" name="button_text" class="form-control" value="Shop Now">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Button Link</label>
                            <input type="text" name="button_url" class="form-control" value="/shop.php">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="0">
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
                    <button type="submit" class="btn btn-brand-primary rounded-pill px-4">Create Banner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
