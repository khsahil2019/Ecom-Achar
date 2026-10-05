<?php
/**
 * Modern Admin Sidebar & Topbar Component
 */
$currentUri = $_SERVER['REQUEST_URI'];
?>
<!-- Mobile Sidebar Backdrop Overlay -->
<div id="adminSidebarBackdrop" class="admin-sidebar-backdrop"></div>

<!-- Master Admin Sidebar -->
<aside id="adminSidebar" class="admin-sidebar">
    <!-- Brand Header -->
    <div class="admin-sidebar-brand justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <div class="admin-brand-icon">
                <i class="bi bi-fire"></i>
            </div>
            <div>
                <div class="fw-bold font-heading text-white fs-5 leading-none">Achar Admin</div>
                <div class="text-danger-emphasis small" style="font-size: 0.70rem; letter-spacing: 0.5px; font-weight: 600;">ESTD 1968 • PRO CONSOLE</div>
            </div>
        </div>
        <button type="button" class="btn btn-sm text-secondary d-lg-none" id="adminSidebarClose" aria-label="Close Sidebar">
            <i class="bi bi-x-lg fs-5"></i>
        </button>
    </div>

    <!-- Navigation Scrollable Area -->
    <div class="admin-sidebar-scroll">
        <ul class="nav flex-column mb-auto">
            <!-- Overview -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link <?= strpos($currentUri, 'dashboard.php') !== false ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <!-- Section: Catalog & Operations -->
            <li class="admin-sidebar-section-title">Catalog & Inventory</li>
            
            <li>
                <a href="<?= BASE_URL ?>/admin/products/index.php" class="nav-link <?= (strpos($currentUri, '/admin/products/') !== false && strpos($currentUri, 'inventory.php') === false) ? 'active' : '' ?>">
                    <i class="bi bi-box2-heart-fill"></i>
                    <span>Products & Jars</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/categories/index.php" class="nav-link <?= strpos($currentUri, '/admin/categories/') !== false ? 'active' : '' ?>">
                    <i class="bi bi-tags-fill"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/products/inventory.php" class="nav-link <?= strpos($currentUri, 'inventory.php') !== false ? 'active' : '' ?>">
                    <i class="bi bi-boxes"></i>
                    <span>Inventory Control</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/orders/index.php" class="nav-link d-flex justify-content-between align-items-center <?= strpos($currentUri, '/admin/orders/') !== false ? 'active' : '' ?>">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-bag-check-fill"></i>
                        <span>Orders</span>
                    </div>
                    <?php if (!empty($pendingOrdersCount)): ?>
                        <span class="badge bg-danger rounded-pill px-2 py-1" style="font-size:0.7rem;"><?= $pendingOrdersCount ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <!-- Section: Marketing & Customers -->
            <li class="admin-sidebar-section-title">Customers & Growth</li>

            <li>
                <a href="<?= BASE_URL ?>/admin/customers/index.php" class="nav-link <?= strpos($currentUri, '/admin/customers/') !== false ? 'active' : '' ?>">
                    <i class="bi bi-people-fill"></i>
                    <span>Customers</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/coupons/index.php" class="nav-link <?= strpos($currentUri, '/admin/coupons/') !== false ? 'active' : '' ?>">
                    <i class="bi bi-ticket-perforated-fill"></i>
                    <span>Coupons & Offers</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/reviews/index.php" class="nav-link <?= strpos($currentUri, '/admin/reviews/') !== false ? 'active' : '' ?>">
                    <i class="bi bi-chat-quote-fill"></i>
                    <span>Product Reviews</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/banners/index.php" class="nav-link <?= strpos($currentUri, '/admin/banners/') !== false ? 'active' : '' ?>">
                    <i class="bi bi-images"></i>
                    <span>Hero Banners</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/contact/index.php" class="nav-link d-flex justify-content-between align-items-center <?= strpos($currentUri, '/admin/contact/') !== false ? 'active' : '' ?>">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-envelope-fill"></i>
                        <span>Enquiries</span>
                    </div>
                    <?php if (!empty($unreadMessagesCount)): ?>
                        <span class="badge bg-warning text-dark rounded-pill px-2 py-1" style="font-size:0.7rem;"><?= $unreadMessagesCount ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <!-- Section: Automation & System -->
            <li class="admin-sidebar-section-title">Automation & Intelligence</li>

            <li>
                <a href="<?= BASE_URL ?>/admin/automation.php" class="nav-link d-flex justify-content-between align-items-center <?= strpos($currentUri, 'automation.php') !== false ? 'active' : '' ?>">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cpu-fill text-warning"></i>
                        <span class="text-white fw-semibold">Auto-Pilot Hub</span>
                    </div>
                    <span class="badge bg-success text-white" style="font-size: 0.62rem; padding: 2px 6px;">ACTIVE</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/reports/index.php" class="nav-link <?= strpos($currentUri, '/admin/reports/') !== false ? 'active' : '' ?>">
                    <i class="bi bi-bar-chart-line-fill"></i>
                    <span>Sales Analytics</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/settings.php" class="nav-link <?= strpos($currentUri, 'settings.php') !== false ? 'active' : '' ?>">
                    <i class="bi bi-gear-fill"></i>
                    <span>Store Settings</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Admin User Profile & Quick Actions Footer -->
    <div class="admin-sidebar-footer">
        <div class="admin-user-profile-badge">
            <div class="d-flex align-items-center gap-2 overflow-hidden">
                <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 0.85rem; flex-shrink: 0;">
                    <?= strtoupper(substr($admin['name'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="overflow-hidden">
                    <div class="text-white fw-semibold small text-truncate" style="max-width: 140px;"><?= e($admin['name'] ?? 'Admin') ?></div>
                    <div class="text-secondary small" style="font-size: 0.70rem;">Super Admin</div>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/admin/logout.php" class="text-secondary hover-text-danger p-1" title="Sign Out">
                <i class="bi bi-box-arrow-right fs-5"></i>
            </a>
        </div>
        <div class="mt-2 text-center">
            <a href="<?= BASE_URL ?>/index.php" target="_blank" class="btn btn-outline-light btn-sm w-100 rounded-pill" style="font-size: 0.75rem; border-color: rgba(255,255,255,0.15);">
                <i class="bi bi-arrow-up-right me-1"></i> Visit Live Storefront
            </a>
        </div>
    </div>
</aside>

<!-- Main Wrapper Begins -->
<div class="admin-main-content">
    
    <!-- Modern Glass Topbar -->
    <nav class="admin-topbar">
        <div class="d-flex align-items-center gap-3">
            <!-- Mobile Toggle -->
            <button class="btn btn-light border d-lg-none btn-sm" id="adminSidebarToggle" type="button" aria-label="Toggle Sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>

            <!-- Breadcrumb / Portal Badge -->
            <div class="d-flex align-items-center gap-2">
                <span class="live-pulse-dot" title="Store System Active"></span>
                <span class="fw-bold text-dark fs-6 d-none d-sm-inline">Achar Heritage</span>
                <span class="text-muted small d-none d-sm-inline">•</span>
                <span class="text-muted small">Console</span>
            </div>
        </div>

        <!-- Global Search Input with Shortcut -->
        <div class="admin-search-wrapper d-none d-md-block">
            <i class="bi bi-search admin-search-icon"></i>
            <form action="<?= BASE_URL ?>/admin/orders/index.php" method="GET" class="m-0">
                <input type="text" name="search" id="globalAdminSearch" class="admin-search-input" placeholder="Search orders, customers, jars..." autocomplete="off">
            </form>
            <span class="admin-search-kbd">⌘K</span>
        </div>

        <!-- Right Quick Actions -->
        <div class="d-flex align-items-center gap-2">
            <!-- Quick Create Dropdown -->
            <div class="dropdown">
                <button class="btn btn-danger btn-sm dropdown-toggle fw-semibold rounded-pill px-3 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-plus-lg me-1"></i> Create
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 small rounded-3 mt-2">
                    <li><h6 class="dropdown-header text-uppercase" style="font-size: 0.65rem;">Quick Operations</h6></li>
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/products/add.php"><i class="bi bi-box2-heart text-danger me-2"></i> Add Pickle Jar</a></li>
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/coupons/add.php"><i class="bi bi-ticket-perforated text-warning me-2"></i> Create Coupon</a></li>
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/categories/add.php"><i class="bi bi-tags text-primary me-2"></i> Add Category</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/automation.php"><i class="bi bi-cpu text-success me-2"></i> Auto-Pilot Hub</a></li>
                </ul>
            </div>

            <!-- Orders Notification Bell -->
            <a href="<?= BASE_URL ?>/admin/orders/index.php?status=Pending" class="btn btn-light border btn-sm position-relative rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;" title="Pending Orders Alert">
                <i class="bi bi-bell"></i>
                <?php if (!empty($pendingOrdersCount)): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.62rem;">
                        <?= $pendingOrdersCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <!-- Unread Messages Bell -->
            <a href="<?= BASE_URL ?>/admin/contact/index.php" class="btn btn-light border btn-sm position-relative rounded-circle d-flex align-items-center justify-content-center d-none d-sm-flex" style="width:36px;height:36px;" title="Customer Enquiries">
                <i class="bi bi-chat-left-dots"></i>
                <?php if (!empty($unreadMessagesCount)): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark" style="font-size: 0.62rem;">
                        <?= $unreadMessagesCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <!-- Profile Menu Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light border btn-sm dropdown-toggle d-flex align-items-center gap-2 rounded-pill px-2 py-1" type="button" data-bs-toggle="dropdown">
                    <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:26px;height:26px;font-size:0.75rem;">
                        <?= strtoupper(substr($admin['name'] ?? 'A', 0, 1)) ?>
                    </div>
                    <span class="d-none d-md-inline small fw-semibold"><?= e($admin['name'] ?? 'Admin') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 small rounded-3 mt-2">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold text-dark"><?= e($admin['name'] ?? 'Admin') ?></div>
                        <div class="text-muted small" style="font-size:0.72rem;"><?= e($admin['email'] ?? 'admin@achar.com') ?></div>
                    </li>
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/settings.php"><i class="bi bi-gear me-2 text-muted"></i> Store Settings</a></li>
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/automation.php"><i class="bi bi-cpu me-2 text-muted"></i> Auto-Pilot Hub</a></li>
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/reports/index.php"><i class="bi bi-bar-chart me-2 text-muted"></i> Reports & Analytics</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>/admin/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Admin Content Body -->
    <div class="admin-content-body">
        <?= render_flash() ?>
