<?php
/**
 * Admin Contact Enquiries Management
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
$pdo = db();

// Handle Status Updates
if (isset($_GET['status_update']) && isset($_GET['id'])) {
    $newStatus = in_array($_GET['status_update'], ['new', 'read', 'resolved']) ? $_GET['status_update'] : 'read';
    $msgId = (int)$_GET['id'];
    $pdo->prepare("UPDATE contact_messages SET status = ? WHERE id = ?")->execute([$newStatus, $msgId]);
    set_flash('info', 'Message marked as ' . $newStatus);
    header('Location: ' . BASE_URL . '/admin/contact/index.php');
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$delId]);
    set_flash('info', 'Message deleted.');
    header('Location: ' . BASE_URL . '/admin/contact/index.php');
    exit;
}

$messages = $pdo->query("SELECT * FROM contact_messages ORDER BY id DESC")->fetchAll();

$adminTitle = 'Customer Enquiries - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="font-heading m-0 fs-3">Customer Inquiries & Support (<?= count($messages) ?>)</h2>
        <p class="text-muted small m-0 mt-1">Review feedback, bulk gifting requests, and recipe assistance inquiries.</p>
    </div>
</div>

<div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
    <?php if (!empty($messages)): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>Sender</th>
                        <th>Subject & Message</th>
                        <th>Contact Channels</th>
                        <th>Status</th>
                        <th>Received On</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): 
                        $statusBadge = match($msg['status']) {
                            'new' => 'bg-danger text-white',
                            'read' => 'bg-warning text-dark',
                            default => 'bg-success text-white'
                        };
                    ?>
                        <tr class="<?= $msg['status'] === 'new' ? 'table-warning-subtle' : '' ?>">
                            <td>
                                <div class="fw-bold text-dark"><?= e($msg['name']) ?></div>
                                <div class="text-muted small">ID: #<?= $msg['id'] ?></div>
                            </td>
                            <td style="max-width: 320px;">
                                <div class="fw-bold small text-dark"><?= e($msg['subject']) ?></div>
                                <p class="text-muted small mb-0" style="line-height: 1.4;"><?= nl2br(e($msg['message'])) ?></p>
                            </td>
                            <td>
                                <div class="small"><i class="bi bi-envelope text-primary me-1"></i> <a href="mailto:<?= e($msg['email']) ?>" class="text-decoration-none"><?= e($msg['email']) ?></a></div>
                                <?php if ($msg['mobile']): ?>
                                    <div class="small mt-1"><i class="bi bi-phone text-success me-1"></i> <a href="https://wa.me/91<?= preg_replace('/\D/', '', $msg['mobile']) ?>" target="_blank" class="text-decoration-none text-success"><?= e($msg['mobile']) ?></a></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $statusBadge ?>"><?= ucfirst($msg['status']) ?></span>
                            </td>
                            <td class="small text-muted"><?= format_date($msg['created_at'], 'd M, h:i A') ?></td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <?php if ($msg['status'] !== 'resolved'): ?>
                                        <a href="<?= BASE_URL ?>/admin/contact/index.php?status_update=resolved&id=<?= $msg['id'] ?>" class="btn btn-sm btn-outline-success" title="Mark as Resolved">
                                            <i class="bi bi-check2-circle"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>/admin/contact/index.php?delete=<?= $msg['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete message?');" title="Delete">
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
            <i class="bi bi-envelope-check display-4 mb-2 d-block"></i>
            <h5>All customer inquiries resolved!</h5>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
