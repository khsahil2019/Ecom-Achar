<?php
/**
 * Admin Sidebar Component
 */
$currentUri = $_SERVER['REQUEST_URI'];
?>
<!-- Admin Sidebar -->
<aside class="admin-sidebar d-flex flex-column flex-shrink-0" style="width: 260px;">
    <!-- Brand Header -->
    <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex align-items-center gap-2">
        <div class="brand-logo-badge" style="width: 38px; height: 38px; font-size: 1.2rem;">
            <i class="bi bi-fire"></i>
        </div>
        <div>
            <div class="fw-bold font-heading text-white fs-5 leading-none">Achar Admin</div>
            <div class="text-warning small" style="font-size: 0.72rem; letter-spacing: 0.5px;">ESTD 1968 CONSOLE</div>
        </div>
    </div>

    <!-- Navigation Links -->
    <div class="py-3 flex-grow-1 overflow-y-auto">
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link <?= strpos($currentUri, 'dashboard.php') !== false ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            
            <li class="px-3 pt-3 pb-1 text-uppercase text-secondary" style="font-size: 0.68rem; letter-spacing: 1px; font-weight: 700;">Catalog & Orders</li>
            
            <li>
                <a href="<?= BASE_URL ?>/admin/products/index.php" class="nav-link <?= (strpos($currentUri, '/admin/products/') !== false && strpos($currentUri, 'inventory.php') === false) ? 'active' : '' ?>">
                    <i class="bi bi-box2-heart"></i> Products & Jars
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/categories/index.php" class="nav-link <?= strpos($currentUri, '/admin/categories/') !== false ? 'active' : '' ?>">
                    <i class="bi bi-tags"></i> Categories
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/products/inventory.php" class="nav-link <?= strpos($currentUri, 'inventory.php') !== false ? 'active' : '' ?>">
                    <i class="bi bi-boxes"></i> Inventory Control
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/orders/index.php" class="nav-link d-flex justify-content-between align-items-center <?= strpos($currentUri, '/admin/orders/') !== false ? 'active' : '' ?>">
                    <span><i class="bi bi-cart-check"></i> Orders</span>
                    <?php if (!empty($pendingOrdersCount)): ?>
                        <span class="badge bg-warning text-dark rounded-pill"><?= $pendingOrdersCount ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="px-3 pt-3 pb-1 text-uppercase text-secondary" style="font-size: 0.68rem; letter-spacing: 1px; font-weight: 700;">Community & Marketing</li>

            <li>
                <a href="<?= BASE_URL ?>/admin/customers/index.php" class="nav-link <?= strpos($currentUri, '/admin/customers/') !== false ? 'active' : '' ?>">
                    <i class="bi bi-people"></i> Customers
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/coupons/index.php" class="nav-link <?= strpos($currentUri, '/admin/coupons/') !== false ? 'active' : '' ?>">
                    <i class="bi bi-ticket-perforated"></i> Discount Coupons
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/reviews/index.php" class="nav-link <?= strpos($currentUri, '/admin/reviews/') !== false ? 'active' : '' ?>">
                    <i class="bi bi-chat-heart"></i> Product Reviews
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/banners/index.php" class="nav-link <?= strpos($currentUri, '/admin/banners/') !== false ? 'active' : '' ?>">
                    <i class="bi bi-images"></i> Home Banners
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/contact/index.php" class="nav-link d-flex justify-content-between align-items-center <?= strpos($currentUri, '/admin/contact/') !== false ? 'active' : '' ?>">
                    <span><i class="bi bi-envelope"></i> Enquiries</span>
                    <?php if (!empty($unreadMessagesCount)): ?>
                        <span class="badge bg-danger rounded-pill"><?= $unreadMessagesCount ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="px-3 pt-3 pb-1 text-uppercase text-secondary" style="font-size: 0.68rem; letter-spacing: 1px; font-weight: 700;">Analytics & System</li>

            <li>
                <a href="<?= BASE_URL ?>/admin/automation.php" class="nav-link d-flex justify-content-between align-items-center <?= strpos($currentUri, 'automation.php') !== false ? 'active' : '' ?>">
                    <span><i class="bi bi-cpu-fill text-warning"></i> Auto-Pilot Hub</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">ACTIVE</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/reports/index.php" class="nav-link <?= strpos($currentUri, '/admin/reports/') !== false ? 'active' : '' ?>">
                    <i class="bi bi-graph-up-arrow"></i> Sales Reports
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/settings.php" class="nav-link <?= strpos($currentUri, 'settings.php') !== false ? 'active' : '' ?>">
                    <i class="bi bi-gear"></i> Store Settings
                </a>
            </li>
        </ul>
    </div>

    <!-- Bottom Actions -->
    <div class="p-3 border-top border-secondary border-opacity-25">
        <a href="<?= BASE_URL ?>/index.php" target="_blank" class="btn btn-sm btn-outline-light w-100 mb-2 rounded-pill">
            <i class="bi bi-box-arrow-up-right me-1"></i> Live Storefront
        </a>
        <a href="<?= BASE_URL ?>/admin/logout.php" class="btn btn-sm btn-danger w-100 rounded-pill">
            <i class="bi bi-power me-1"></i> Logout
        </a>
    </div>
</aside>

<!-- Main Wrapper Begins -->
<div class="admin-main-content">
    
    <!-- Topbar -->
    <nav class="admin-topbar d-flex justify-content-between align-items-center">
        <div>
            <span class="text-muted small">Current Portal:</span>
            <strong class="text-dark ms-1">Achar Heritage Operations Console</strong>
        </div>

        <div class="d-flex align-items-center gap-3">
            <a href="<?= BASE_URL ?>/admin/orders/index.php?status=Pending" class="btn btn-sm btn-light border position-relative" title="Pending Orders">
                <i class="bi bi-bell"></i>
                <?php if (!empty($pendingOrdersCount)): ?>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                <?php endif; ?>
            </a>

            <div class="dropdown">
                <button class="btn btn-light border btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:26px;height:26px;font-size:0.75rem;">
                        <i class="bi bi-person"></i>
                    </div>
                    <span><?= e($admin['name'] ?? 'Admin') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 small">
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>/admin/logout.php"><i class="bi bi-power me-2"></i> Sign Out</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Admin Content Body -->
    <div class="admin-content-body">
        <?= render_flash() ?>
