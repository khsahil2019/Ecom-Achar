<?php
/**
 * Achar Heritage - Shop / Product Catalogue
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/product_card.php';

$pdo = db();

// Filter parameters
$currentCategorySlug = trim($_GET['category'] ?? '');
$filterType = trim($_GET['filter'] ?? '');
$searchKeyword = trim($_GET['search'] ?? '');
$minPrice = !empty($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = !empty($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sortBy = trim($_GET['sort'] ?? 'popularity');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;
$offset = ($page - 1) * $perPage;

// Base query builder
$whereClauses = ["p.status = 'active'"];
$params = [];

if ($currentCategorySlug !== '') {
    $whereClauses[] = "c.slug = ?";
    $params[] = $currentCategorySlug;
}

if ($filterType === 'bestseller') {
    $whereClauses[] = "p.is_bestseller = 1";
} elseif ($filterType === 'new') {
    $whereClauses[] = "p.is_new = 1";
}

if ($searchKeyword !== '') {
    $whereClauses[] = "(p.name LIKE ? OR p.short_description LIKE ? OR p.ingredients LIKE ? OR p.sku LIKE ?)";
    $kw = '%' . $searchKeyword . '%';
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
}

if ($minPrice > 0) {
    $whereClauses[] = "p.price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice > 0 && $maxPrice >= $minPrice) {
    $whereClauses[] = "p.price <= ?";
    $params[] = $maxPrice;
}

$whereSql = implode(' AND ', $whereClauses);

// Sorting logic
$orderBySql = match($sortBy) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'latest'     => 'p.id DESC',
    'rating'     => 'p.rating_cache DESC, p.reviews_count DESC',
    default      => 'p.is_bestseller DESC, p.id ASC' // popularity
};

// Count total matching items
$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM products p JOIN categories c ON p.category_id = c.id WHERE $whereSql");
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetch()['total'];
$totalPages = ceil($totalProducts / $perPage);

// Fetch products for current page
$productsSql = "
    SELECT p.*, c.name as category_name, c.slug as category_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE $whereSql
    ORDER BY $orderBySql
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($productsSql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Fetch all categories for sidebar
$allCatsStmt = $pdo->query("SELECT c.*, COUNT(p.id) as count FROM categories c LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active' WHERE c.status = 'active' GROUP BY c.id ORDER BY c.display_order ASC");
$allCategories = $allCatsStmt->fetchAll();

$pageTitle = 'Shop Authentic Traditional Pickles - Achar Heritage';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Shop Header Banner -->
<div class="py-4 bg-white border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pickle Shop</li>
                <?php if ($currentCategorySlug): ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= e(ucwords(str_replace('-', ' ', $currentCategorySlug))) ?></li>
                <?php endif; ?>
            </ol>
        </nav>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="font-heading display-6 m-0">Handcrafted Pickles Collection</h1>
                <p class="text-muted small m-0 mt-1">Sun-cured in pure mustard oil, packed fresh in airtight glass jars.</p>
            </div>
            <div>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6">
                    <i class="bi bi-box2-heart me-1"></i> Showing <?= count($products) ?> of <?= $totalProducts ?> Varieties
                </span>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        
        <!-- Left Sidebar Filters -->
        <div class="col-lg-3">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white position-sticky" style="top: 100px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0 font-heading fs-5">Filters</h5>
                    <?php if ($currentCategorySlug || $filterType || $searchKeyword || $minPrice || $maxPrice): ?>
                        <a href="<?= BASE_URL ?>/shop.php" class="text-danger small fw-bold text-decoration-none">Clear All</a>
                    <?php endif; ?>
                </div>

                <!-- Category List -->
                <div class="mb-4">
                    <label class="form-label fw-bold small text-uppercase text-muted">Categories</label>
                    <div class="d-flex flex-column gap-1">
                        <a href="<?= BASE_URL ?>/shop.php" class="d-flex justify-content-between align-items-center py-2 px-3 rounded text-decoration-none <?= empty($currentCategorySlug) ? 'bg-brand-primary text-white fw-bold' : 'text-dark' ?>">
                            <span>All Pickles</span>
                            <span class="badge <?= empty($currentCategorySlug) ? 'bg-white text-danger' : 'bg-light text-muted' ?>"><?= $totalProducts ?></span>
                        </a>
                        <?php foreach ($allCategories as $cat): ?>
                            <a href="<?= BASE_URL ?>/shop.php?category=<?= urlencode($cat['slug']) ?>" class="d-flex justify-content-between align-items-center py-2 px-3 rounded text-decoration-none <?= ($currentCategorySlug === $cat['slug']) ? 'bg-brand-primary text-white fw-bold' : 'text-dark' ?>">
                                <span><?= e($cat['name']) ?></span>
                                <span class="badge <?= ($currentCategorySlug === $cat['slug']) ? 'bg-white text-danger' : 'bg-light text-muted' ?>"><?= $cat['count'] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Price Filter Form -->
                <form action="<?= BASE_URL ?>/shop.php" method="GET">
                    <?php if ($currentCategorySlug): ?>
                        <input type="hidden" name="category" value="<?= e($currentCategorySlug) ?>">
                    <?php endif; ?>
                    <?php if ($sortBy): ?>
                        <input type="hidden" name="sort" value="<?= e($sortBy) ?>">
                    <?php endif; ?>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Price Range (₹)</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Min ₹" value="<?= $minPrice > 0 ? $minPrice : '' ?>">
                            </div>
                            <div class="col-6">
                                <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Max ₹" value="<?= $maxPrice > 0 ? $maxPrice : '' ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-brand btn-sm w-100 mt-2">Apply Price</button>
                    </div>
                </form>

                <!-- Quick Filter Badges -->
                <div>
                    <label class="form-label fw-bold small text-uppercase text-muted">Collections</label>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= BASE_URL ?>/shop.php?filter=bestseller" class="badge <?= ($filterType === 'bestseller') ? 'bg-warning text-dark' : 'bg-light text-dark border' ?> text-decoration-none p-2">⭐ Best Sellers</a>
                        <a href="<?= BASE_URL ?>/shop.php?filter=new" class="badge <?= ($filterType === 'new') ? 'bg-danger text-white' : 'bg-light text-dark border' ?> text-decoration-none p-2">✨ New Arrivals</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Main Catalog Content -->
        <div class="col-lg-9">
            
            <!-- Sorting & Active Filters Toolbar -->
            <div class="card border-0 rounded-4 shadow-sm p-3 mb-4 bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                <div class="text-muted small">
                    <?php if ($searchKeyword): ?>
                        Search results for <strong>"<?= e($searchKeyword) ?>"</strong>
                    <?php elseif ($currentCategorySlug): ?>
                        Viewing Category: <strong><?= e(ucwords(str_replace('-', ' ', $currentCategorySlug))) ?></strong>
                    <?php else: ?>
                        Showing all authentic artisanal pickles
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <label for="sortSelector" class="form-label small text-muted m-0 text-nowrap">Sort By:</label>
                    <select id="sortSelector" class="form-select form-select-sm" style="width: 190px;" onchange="window.location.href = this.value;">
                        <?php
                        $buildSortUrl = function($s) use ($currentCategorySlug, $filterType, $searchKeyword, $minPrice, $maxPrice) {
                            $q = ['sort' => $s];
                            if ($currentCategorySlug) $q['category'] = $currentCategorySlug;
                            if ($filterType) $q['filter'] = $filterType;
                            if ($searchKeyword) $q['search'] = $searchKeyword;
                            if ($minPrice > 0) $q['min_price'] = $minPrice;
                            if ($maxPrice > 0) $q['max_price'] = $maxPrice;
                            return BASE_URL . '/shop.php?' . http_build_query($q);
                        };
                        ?>
                        <option value="<?= $buildSortUrl('popularity') ?>" <?= $sortBy === 'popularity' ? 'selected' : '' ?>>Popularity</option>
                        <option value="<?= $buildSortUrl('latest') ?>" <?= $sortBy === 'latest' ? 'selected' : '' ?>>Latest Arrivals</option>
                        <option value="<?= $buildSortUrl('price_asc') ?>" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="<?= $buildSortUrl('price_desc') ?>" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="<?= $buildSortUrl('rating') ?>" <?= $sortBy === 'rating' ? 'selected' : '' ?>>Customer Rating</option>
                    </select>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if (!empty($products)): ?>
                <div class="row g-4">
                    <?php foreach ($products as $prod): ?>
                        <div class="col-6 col-md-6 col-xl-4">
                            <?php render_product_card($prod); ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-5" aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <?php
                                $pageQuery = $_GET;
                                $pageQuery['page'] = $p;
                                $pageUrl = BASE_URL . '/shop.php?' . http_build_query($pageQuery);
                                ?>
                                <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= $pageUrl ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <!-- Empty State -->
                <div class="text-center py-5 bg-white rounded-4 shadow-sm p-5">
                    <i class="bi bi-search display-3 text-muted mb-3 d-block"></i>
                    <h4 class="font-heading">No Pickles Found</h4>
                    <p class="text-muted">We couldn't find any pickles matching your filter criteria. Try adjusting your filters or search keywords.</p>
                    <a href="<?= BASE_URL ?>/shop.php" class="btn btn-brand-primary mt-2">View All Pickles</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
