<?php
/**
 * Customer Wishlist Page
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/product_card.php';

require_login();

$pdo = db();
$cid = customer_id();

// Handle remove from wishlist
if (isset($_GET['remove'])) {
    $removeId = (int)$_GET['remove'];
    $pdo->prepare("DELETE wi FROM wishlist_items wi JOIN wishlist w ON wi.wishlist_id = w.id WHERE w.customer_id = ? AND wi.product_id = ?")
        ->execute([$cid, $removeId]);
    set_flash('info', 'Item removed from your wishlist.');
    header('Location: ' . BASE_URL . '/account/wishlist.php');
    exit;
}

// Fetch wishlist items
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name
    FROM wishlist_items wi
    JOIN wishlist w ON wi.wishlist_id = w.id
    JOIN products p ON wi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    WHERE w.customer_id = ? AND p.status = 'active'
    ORDER BY wi.id DESC
");
$stmt->execute([$cid]);
$wishlistProducts = $stmt->fetchAll();

$pageTitle = 'My Saved Wishlist - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="py-4 bg-white border-bottom">
    <div class="container">
        <h1 class="font-heading display-6 m-0">My Favorite Pickles</h1>
        <p class="text-muted small m-0 mt-1">Pickle jars you have saved to cherish later.</p>
    </div>
</div>

<div class="container py-5">
    <?= render_flash() ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <?php require_once __DIR__ . '/account_nav.php'; ?>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold font-heading m-0">Saved Wishlist (<?= count($wishlistProducts) ?>)</h5>
                    <a href="<?= BASE_URL ?>/shop.php" class="btn btn-outline-brand btn-sm rounded-pill">
                        <i class="bi bi-plus-lg me-1"></i> Discover More
                    </a>
                </div>

                <?php if (!empty($wishlistProducts)): ?>
                    <div class="row g-4">
                        <?php foreach ($wishlistProducts as $prod): ?>
                            <div class="col-sm-6 col-md-6">
                                <?php render_product_card($prod); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-heart display-4 text-muted d-block mb-3"></i>
                        <h5>Your wishlist is currently empty</h5>
                        <p class="small">Tap the heart icon on any pickle recipe to save it here for later.</p>
                        <a href="<?= BASE_URL ?>/shop.php" class="btn btn-brand-primary rounded-pill px-4">Browse Pickles</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
