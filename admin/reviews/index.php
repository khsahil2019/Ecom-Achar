<?php
/**
 * Admin Customer Reviews Moderation
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
$pdo = db();

// Handle Approve / Reject / Delete
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $revId = (int)$_GET['id'];

    if ($action === 'approve') {
        $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?")->execute([$revId]);
        set_flash('success', 'Review approved and published.');
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?")->execute([$revId]);
        set_flash('info', 'Review marked as rejected.');
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$revId]);
        set_flash('info', 'Review deleted.');
    }

    // Refresh product cache
    $pStmt = $pdo->prepare("SELECT product_id FROM reviews WHERE id = ?");
    $pStmt->execute([$revId]);
    $row = $pStmt->fetch();
    if ($row) {
        $prodId = $row['product_id'];
        $pdo->prepare("
            UPDATE products 
            SET rating_cache = COALESCE((SELECT ROUND(AVG(rating), 1) FROM reviews WHERE product_id = ? AND status = 'approved'), 5.0),
                reviews_count = (SELECT COUNT(*) FROM reviews WHERE product_id = ? AND status = 'approved')
            WHERE id = ?
        ")->execute([$prodId, $prodId, $prodId]);
    }

    header('Location: ' . BASE_URL . '/admin/reviews/index.php');
    exit;
}

// Fetch Reviews
$stmt = $pdo->query("
    SELECT r.*, p.name as product_name, p.main_image, p.slug as product_slug
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    ORDER BY r.id DESC
");
$reviews = $stmt->fetchAll();

$adminTitle = 'Customer Reviews Moderation - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="font-heading m-0 fs-3">Customer Reviews Moderation (<?= count($reviews) ?>)</h2>
        <p class="text-muted small m-0 mt-1">Approve genuine customer culinary feedback to build trust across the online store.</p>
    </div>
</div>

<div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
    <?php if (!empty($reviews)): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>Product</th>
                        <th>Diner Name & Email</th>
                        <th>Rating</th>
                        <th>Review Headline & Comment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end">Moderation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $rev): 
                        $statusBadge = match($rev['status']) {
                            'approved' => 'bg-success',
                            'rejected' => 'bg-danger',
                            default => 'bg-warning text-dark'
                        };
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= BASE_URL ?>/uploads/products/<?= e($rev['main_image']) ?>" class="rounded-2 border" style="width: 40px; height: 40px; object-fit: cover;">
                                    <span class="small fw-bold text-dark"><?= e($rev['product_name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold small"><?= e($rev['customer_name']) ?></div>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= e($rev['customer_email'] ?: 'Guest') ?></div>
                            </td>
                            <td>
                                <div class="text-warning small text-nowrap">
                                    <?php for ($i = 0; $i < (int)$rev['rating']; $i++): ?>
                                        <i class="bi bi-star-fill"></i>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td style="max-width: 300px;">
                                <div class="fw-bold small text-dark"><?= e($rev['title'] ?: 'Review') ?></div>
                                <p class="text-muted small mb-0" style="line-height: 1.4;"><?= e($rev['comment']) ?></p>
                            </td>
                            <td>
                                <span class="badge <?= $statusBadge ?>"><?= ucfirst($rev['status']) ?></span>
                            </td>
                            <td class="small text-muted"><?= format_date($rev['created_at'], 'd M Y') ?></td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <?php if ($rev['status'] !== 'approved'): ?>
                                        <a href="<?= BASE_URL ?>/admin/reviews/index.php?action=approve&id=<?= $rev['id'] ?>" class="btn btn-sm btn-outline-success" title="Approve">
                                            <i class="bi bi-check-lg"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($rev['status'] !== 'rejected'): ?>
                                        <a href="<?= BASE_URL ?>/admin/reviews/index.php?action=reject&id=<?= $rev['id'] ?>" class="btn btn-sm btn-outline-warning" title="Reject">
                                            <i class="bi bi-x-lg"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>/admin/reviews/index.php?action=delete&id=<?= $rev['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete review?');" title="Delete">
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
            <i class="bi bi-chat-square-heart display-4 mb-2 d-block"></i>
            <h5>No customer reviews found</h5>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
