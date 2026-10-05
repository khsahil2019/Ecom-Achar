<?php
/**
 * Customer Account Sidebar Navigation
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$cust = current_user();
?>
<div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
    <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom">
        <div class="bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4 border border-danger-subtle" style="width: 54px; height: 54px;">
            <?= strtoupper(substr($cust['name'], 0, 1)) ?>
        </div>
        <div>
            <h5 class="fw-bold m-0"><?= e($cust['name']) ?></h5>
            <div class="text-muted small"><?= e($cust['email']) ?></div>
            <div class="text-muted small"><i class="bi bi-phone"></i> <?= e($cust['mobile']) ?></div>
        </div>
    </div>

    <div class="nav flex-column gap-1">
        <a class="nav-link py-2 px-3 rounded-3 <?= ($currentPage === 'dashboard.php') ? 'bg-brand-primary text-white fw-bold shadow-sm' : 'text-dark' ?>" 
           href="<?= BASE_URL ?>/account/dashboard.php">
            <i class="bi bi-grid-fill me-2"></i> Dashboard Overview
        </a>
        <a class="nav-link py-2 px-3 rounded-3 <?= ($currentPage === 'orders.php') ? 'bg-brand-primary text-white fw-bold shadow-sm' : 'text-dark' ?>" 
           href="<?= BASE_URL ?>/account/orders.php">
            <i class="bi bi-box-seam-fill me-2"></i> My Pickle Orders
        </a>
        <a class="nav-link py-2 px-3 rounded-3 <?= ($currentPage === 'wishlist.php') ? 'bg-brand-primary text-white fw-bold shadow-sm' : 'text-dark' ?>" 
           href="<?= BASE_URL ?>/account/wishlist.php">
            <i class="bi bi-heart-fill me-2 text-danger"></i> Saved Wishlist
        </a>
        <a class="nav-link py-2 px-3 rounded-3 <?= ($currentPage === 'addresses.php') ? 'bg-brand-primary text-white fw-bold shadow-sm' : 'text-dark' ?>" 
           href="<?= BASE_URL ?>/account/addresses.php">
            <i class="bi bi-geo-alt-fill me-2 text-warning"></i> Delivery Addresses
        </a>
        <a class="nav-link py-2 px-3 rounded-3 <?= ($currentPage === 'profile.php') ? 'bg-brand-primary text-white fw-bold shadow-sm' : 'text-dark' ?>" 
           href="<?= BASE_URL ?>/account/profile.php">
            <i class="bi bi-person-fill-gear me-2 text-info"></i> Profile & Password
        </a>
        <hr class="my-2">
        <a class="nav-link py-2 px-3 rounded-3 text-danger" href="<?= BASE_URL ?>/auth/logout.php">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>
