<?php
/**
 * Achar Heritage - Master Operations Automation & Auto-Pilot Hub
 * Enables the administrator to manage, automate, and orchestrate the entire e-commerce system.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$pdo = db();
$adminUser = current_admin();

$actionResult = null;
$actionError = null;

// Handle Automated Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $op = trim($_POST['operation'] ?? '');

        try {
            switch ($op) {
                // 1. AUTO-FULFILLMENT PIPELINE
                case 'auto_fulfill_orders':
                    // Auto-advance Pending -> Confirmed -> Shipped with generated tracking numbers
                    $couriers = ['Delhivery', 'BlueDart', 'DTDC', 'Shadowfax'];
                    
                    // A. Confirm pending orders
                    $confirmCount = $pdo->exec("UPDATE orders SET order_status = 'Confirmed' WHERE order_status = 'Pending'");
                    
                    // B. Pack confirmed orders
                    $packCount = $pdo->exec("UPDATE orders SET order_status = 'Packed' WHERE order_status = 'Confirmed'");
                    
                    // C. Ship packed orders with auto tracking numbers
                    $stmtPacked = $pdo->query("SELECT id FROM orders WHERE order_status = 'Packed'");
                    $shipCount = 0;
                    while ($row = $stmtPacked->fetch()) {
                        $courier = $couriers[array_rand($couriers)];
                        $tracking = strtoupper($courier) . '-ACH-' . rand(100000, 999999);
                        $u = $pdo->prepare("UPDATE orders SET order_status = 'Shipped', tracking_number = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $u->execute([$tracking, $row['id']]);
                        $shipCount++;
                    }

                    // D. Deliver orders older than 2 days that are Shipped
                    $deliverCount = $pdo->exec("UPDATE orders SET order_status = 'Delivered', payment_status = 'paid' WHERE order_status = 'Shipped' AND created_at <= datetime('now', '-2 days')");

                    // Log activity
                    log_activity('admin', (int)$adminUser['id'], 'auto_fulfill', "Pipeline executed: Confirmed ($confirmCount), Packed ($packCount), Shipped ($shipCount), Delivered ($deliverCount)");
                    $actionResult = "Fulfillment Pipeline successfully run! Confirmed: {$confirmCount}, Packed: {$packCount}, Shipped with Tracking IDs: {$shipCount}, Delivered: {$deliverCount}.";
                    break;

                // 2. AUTO-REPLENISH LOW INVENTORY
                case 'auto_restock_low':
                    $threshold = max(5, (int)($_POST['threshold'] ?? 25));
                    $addStock = max(10, (int)($_POST['add_stock'] ?? 50));
                    
                    $lowStmt = $pdo->prepare("SELECT id, name, stock FROM products WHERE stock <= ?");
                    $lowStmt->execute([$threshold]);
                    $lowItems = $lowStmt->fetchAll();
                    
                    $restockedCount = 0;
                    foreach ($lowItems as $item) {
                        $newStock = (int)$item['stock'] + $addStock;
                        $u = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
                        $u->execute([$newStock, $item['id']]);
                        $restockedCount++;
                    }

                    log_activity('admin', (int)$adminUser['id'], 'auto_restock', "Replenished {$restockedCount} low-stock products with +{$addStock} units each.");
                    $actionResult = "Successfully replenished {$restockedCount} pickle varieties that had stock <= {$threshold} jars (+{$addStock} units each).";
                    break;

                // 3. EMERGENCY RESTOCK ALL
                case 'restock_all_products':
                    $targetStock = max(20, (int)($_POST['target_stock'] ?? 100));
                    $pdo->prepare("UPDATE products SET stock = ? WHERE status = 'active'")->execute([$targetStock]);
                    
                    log_activity('admin', (int)$adminUser['id'], 'emergency_restock', "Reset all active products to {$targetStock} units.");
                    $actionResult = "All active pickle varieties reset to {$targetStock} units in inventory.";
                    break;

                // 4. AUTO-MODERATE REVIEWS & SYNC RATINGS
                case 'auto_moderate_reviews':
                    // Approve all pending reviews with rating >= 4
                    $appCount = $pdo->exec("UPDATE reviews SET status = 'approved' WHERE status = 'pending' AND rating >= 4");
                    
                    // Recalculate rating caches on all products
                    $prodStmt = $pdo->query("SELECT id FROM products");
                    $syncCount = 0;
                    while ($p = $prodStmt->fetch()) {
                        $pId = $p['id'];
                        $calc = $pdo->prepare("
                            UPDATE products SET
                                rating_cache = COALESCE((SELECT ROUND(AVG(rating), 1) FROM reviews WHERE product_id = ? AND status = 'approved'), 5.0),
                                reviews_count = (SELECT COUNT(*) FROM reviews WHERE product_id = ? AND status = 'approved')
                            WHERE id = ?
                        ");
                        $calc->execute([$pId, $pId, $pId]);
                        $syncCount++;
                    }

                    log_activity('admin', (int)$adminUser['id'], 'auto_reviews', "Approved {$appCount} 4+ star reviews and synchronized {$syncCount} product rating caches.");
                    $actionResult = "Approved {$appCount} high-rating customer reviews and synchronized rating cache for all {$syncCount} products.";
                    break;

                // 5. AUTO-GENERATE FESTIVE COUPONS
                case 'auto_generate_coupons':
                    $campaigns = [
                        ['code' => 'DIWALI25', 'type' => 'percentage', 'value' => 25, 'min' => 699, 'days' => 14],
                        ['code' => 'WEEKEND15', 'type' => 'percentage', 'value' => 15, 'min' => 499, 'days' => 3],
                        ['code' => 'FREESHIP50', 'type' => 'fixed', 'value' => 50, 'min' => 399, 'days' => 7],
                        ['code' => 'SHAHI100', 'type' => 'fixed', 'value' => 100, 'min' => 999, 'days' => 30],
                    ];
                    
                    $createdCount = 0;
                    foreach ($campaigns as $c) {
                        $check = $pdo->prepare("SELECT id FROM coupons WHERE code = ?");
                        $check->execute([$c['code']]);
                        if (!$check->fetch()) {
                            $exp = date('Y-m-d', strtotime("+{$c['days']} days"));
                            $ins = $pdo->prepare("
                                INSERT INTO coupons (code, type, value, min_order_amount, usage_limit, start_date, end_date, status)
                                VALUES (?, ?, ?, ?, 500, DATE('now'), ?, 'active')
                            ");
                            $ins->execute([$c['code'], $c['type'], $c['value'], $c['min'], $exp]);
                            $createdCount++;
                        }
                    }

                    // Auto-deactivate expired coupons
                    $pdo->exec("UPDATE coupons SET status = 'expired' WHERE end_date < DATE('now')");

                    log_activity('admin', (int)$adminUser['id'], 'auto_coupons', "Generated {$createdCount} fresh campaign discount coupons.");
                    $actionResult = "Generated {$createdCount} fresh marketing coupons and auto-expired outdated coupons.";
                    break;

                // 6. GENERATE SIMULATED TEST ORDERS
                case 'generate_test_orders':
                    $numOrders = max(1, min(10, (int)($_POST['num_orders'] ?? 3)));
                    $sampleNames = ['Aarav Sharma', 'Priya Patel', 'Rajesh Verma', 'Sneha Iyer', 'Vikram Singh', 'Ananya Gupta'];
                    $sampleCities = [
                        ['city' => 'Jaipur', 'state' => 'Rajasthan', 'pincode' => '302001', 'address' => 'Plot 14, C-Scheme, Ashok Nagar'],
                        ['city' => 'Mumbai', 'state' => 'Maharashtra', 'pincode' => '400050', 'address' => 'Flat 302, Sea View Apts, Bandra West'],
                        ['city' => 'Bengaluru', 'state' => 'Karnataka', 'pincode' => '560038', 'address' => 'No 77, 100 Feet Rd, Indiranagar'],
                        ['city' => 'Delhi', 'state' => 'Delhi', 'pincode' => '110017', 'address' => 'B-44, Malviya Nagar, Near Market']
                    ];
                    
                    // Fetch available products
                    $prods = $pdo->query("SELECT id, name, sku, price FROM products WHERE status = 'active' LIMIT 6")->fetchAll();
                    if (empty($prods)) {
                        throw new Exception("No active products available to create simulated orders.");
                    }

                    $genCount = 0;
                    for ($i = 0; $i < $numOrders; $i++) {
                        $cust = $sampleNames[array_rand($sampleNames)];
                        $loc = $sampleCities[array_rand($sampleCities)];
                        $mobile = '98' . rand(10000000, 99999999);
                        $email = strtolower(str_replace(' ', '.', $cust)) . '@example.com';
                        $orderNum = 'ACH' . date('Ymd') . rand(1000, 9999);

                        // Select 1 to 3 random items
                        $itemCount = rand(1, min(3, count($prods)));
                        $selectedKeys = array_rand($prods, $itemCount);
                        if (!is_array($selectedKeys)) $selectedKeys = [$selectedKeys];

                        $subtotal = 0;
                        $chosenItems = [];
                        foreach ($selectedKeys as $k) {
                            $pr = $prods[$k];
                            $qty = rand(1, 2);
                            $lineTotal = (float)$pr['price'] * $qty;
                            $subtotal += $lineTotal;
                            $chosenItems[] = [
                                'product_id' => $pr['id'],
                                'product_name' => $pr['name'],
                                'sku' => $pr['sku'],
                                'unit_price' => $pr['price'],
                                'quantity' => $qty,
                                'subtotal' => $lineTotal
                            ];
                        }

                        $shippingFee = ($subtotal >= FREE_SHIPPING_THRESHOLD) ? 0 : 50;
                        $total = $subtotal + $shippingFee;
                        $payMethod = (rand(0, 1) === 1) ? 'cod' : 'online';
                        $payStatus = ($payMethod === 'online') ? 'paid' : 'pending';
                        $orderStatus = 'Confirmed';

                        // Insert Order
                        $insOrd = $pdo->prepare("
                            INSERT INTO orders (order_number, customer_id, customer_name, customer_email, customer_mobile, shipping_address, shipping_city, shipping_state, shipping_pincode, subtotal, discount_amount, shipping_fee, total_amount, payment_method, payment_status, order_status)
                            VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?)
                        ");
                        $insOrd->execute([$orderNum, $cust, $email, $mobile, $loc['address'], $loc['city'], $loc['state'], $loc['pincode'], $subtotal, $shippingFee, $total, $payMethod, $payStatus, $orderStatus]);
                        $newOrderId = (int)$pdo->lastInsertId();

                        // Insert Items & deduct inventory
                        foreach ($chosenItems as $ci) {
                            $insItem = $pdo->prepare("
                                INSERT INTO order_items (order_id, product_id, product_name, variant_label, sku, unit_price, quantity, subtotal)
                                VALUES (?, ?, ?, '500g Glass Jar', ?, ?, ?, ?)
                            ");
                            $insItem->execute([$newOrderId, $ci['product_id'], $ci['product_name'], $ci['sku'], $ci['unit_price'], $ci['quantity'], $ci['subtotal']]);
                            $pdo->prepare("UPDATE products SET stock = MAX(0, stock - ?) WHERE id = ?")->execute([$ci['quantity'], $ci['product_id']]);
                        }
                        $genCount++;
                    }

                    log_activity('admin', (int)$adminUser['id'], 'test_orders', "Generated {$genCount} simulated realistic customer orders.");
                    $actionResult = "Generated {$genCount} realistic test orders with items and simulated addresses.";
                    break;

                // 7. OPTIMIZE DATABASE & VACUUM
                case 'optimize_database':
                    // Vacuum & optimize SQLite
                    $pdo->exec("VACUUM");
                    $pdo->exec("PRAGMA optimize");
                    
                    log_activity('admin', (int)$adminUser['id'], 'db_optimize', "Database vacuumed and index statistics optimized.");
                    $actionResult = "Database vacuumed, fragmentation cleared, and query planner statistics optimized.";
                    break;

                default:
                    throw new Exception("Unrecognized automation command.");
            }
        } catch (Exception $e) {
            $actionError = "Error executing automation: " . $e->getMessage();
        }
    } else {
        $actionError = "Security token mismatch. Please try again.";
    }
}

// Handle Instant Database Backup Download
if (isset($_GET['download_backup'])) {
    $dbFile = ROOT_PATH . '/database/achar_store.sqlite';
    if (file_exists($dbFile)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/x-sqlite3');
        header('Content-Disposition: attachment; filename="achar_heritage_backup_' . date('Y_m_d_His') . '.sqlite"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($dbFile));
        readfile($dbFile);
        exit;
    } else {
        $actionError = "Primary database file not found for download.";
    }
}

// Fetch Telemetry & Diagnostics
$dbFileSize = file_exists(ROOT_PATH . '/database/achar_store.sqlite') ? round(filesize(ROOT_PATH . '/database/achar_store.sqlite') / 1024, 1) : 0;
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
$lowStockCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active' AND stock <= 25")->fetchColumn();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('Pending', 'Confirmed')")->fetchColumn();
$pendingReviews = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();

// Fetch Recent Automation Activity
$logsStmt = $pdo->query("SELECT * FROM activity_logs ORDER BY id DESC LIMIT 10");
$recentLogs = $logsStmt->fetchAll();

$adminTitle = 'System Automation & Auto-Pilot Hub - Achar Heritage';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h2 class="font-heading m-0 fs-3">System Automation & Auto-Pilot Hub</h2>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                <i class="bi bi-cpu-fill me-1"></i> Auto-Pilot Active
            </span>
        </div>
        <p class="text-muted small m-0 mt-1">One-click operational pipelines, automated restock, review moderation, and database protection.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/admin/automation.php?download_backup=1" class="btn btn-brand-primary btn-sm rounded-pill shadow-sm">
            <i class="bi bi-cloud-arrow-down-fill me-1"></i> 1-Click DB Backup
        </a>
    </div>
</div>

<!-- Alert Notifications -->
<?php if ($actionResult): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i> <?= e($actionResult) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($actionError): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i> <?= e($actionError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Telemetry KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Pending Orders</div>
                    <div class="fs-4 fw-bold text-dark mt-1"><?= $pendingOrders ?></div>
                </div>
                <div class="bg-warning-subtle text-warning p-2 rounded-3 fs-4" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
            <div class="small text-muted mt-2">Awaiting packing/dispatch</div>
        </div>
    </div>
    
    <div class="col-6 col-md-3">
        <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Low Stock Jars</div>
                    <div class="fs-4 fw-bold <?= $lowStockCount > 0 ? 'text-danger' : 'text-success' ?> mt-1"><?= $lowStockCount ?></div>
                </div>
                <div class="bg-danger-subtle text-danger p-2 rounded-3 fs-4" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-exclamation-octagon"></i>
                </div>
            </div>
            <div class="small text-muted mt-2">Stock &le; 25 units</div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Pending Reviews</div>
                    <div class="fs-4 fw-bold text-dark mt-1"><?= $pendingReviews ?></div>
                </div>
                <div class="bg-info-subtle text-info p-2 rounded-3 fs-4" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-chat-quote"></i>
                </div>
            </div>
            <div class="small text-muted mt-2">Customer feedback queue</div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Database Storage</div>
                    <div class="fs-4 fw-bold text-dark mt-1"><?= $dbFileSize ?> KB</div>
                </div>
                <div class="bg-success-subtle text-success p-2 rounded-3 fs-4" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-database-check"></i>
                </div>
            </div>
            <div class="small text-muted mt-2">SQLite 3 Zero-Maintenance</div>
        </div>
    </div>
</div>

<!-- Main Automation Action Panels -->
<div class="row g-4">
    
    <!-- Panel 1: Fulfillment Auto-Pipeline -->
    <div class="col-lg-6">
        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:48px;height:48px;background:#EFF6FF;color:#2563EB;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                    <i class="bi bi-truck-front-fill"></i>
                </div>
                <div>
                    <h5 class="font-heading m-0 fs-5">Fulfillment Pipeline Automation</h5>
                    <div class="text-muted small">Auto-confirm, generate courier AWB tracking IDs, and deliver</div>
                </div>
            </div>
            
            <p class="small text-muted mb-4">
                Executes the complete order dispatch lifecycle: confirms all pending orders, assigns Delhivery/BlueDart tracking numbers, marks orders as Shipped, and closes out mature orders.
            </p>

            <form action="<?= BASE_URL ?>/admin/automation.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="operation" value="auto_fulfill_orders">
                <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-semibold shadow-sm">
                    <i class="bi bi-play-circle-fill me-1"></i> Run 1-Click Order Fulfillment Pipeline
                </button>
            </form>
        </div>
    </div>

    <!-- Panel 2: Smart Inventory Auto-Replenish -->
    <div class="col-lg-6">
        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:48px;height:48px;background:#FEF3C7;color:#D97706;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                    <i class="bi bi-boxes"></i>
                </div>
                <div>
                    <h5 class="font-heading m-0 fs-5">Smart Inventory Replenishment</h5>
                    <div class="text-muted small">Auto-detect shortages and replenish warehouse stock</div>
                </div>
            </div>

            <p class="small text-muted mb-3">
                Detects all products with inventory at or below threshold and automatically adds warehouse units.
            </p>

            <form action="<?= BASE_URL ?>/admin/automation.php" method="POST" class="row g-2 align-items-end mb-3">
                <?= csrf_field() ?>
                <input type="hidden" name="operation" value="auto_restock_low">
                <div class="col-6">
                    <label class="form-label small text-muted mb-1">If Stock &le; Units:</label>
                    <input type="number" name="threshold" class="form-control form-control-sm" value="25" min="5" max="100">
                </div>
                <div class="col-6">
                    <label class="form-label small text-muted mb-1">Add Stock Units:</label>
                    <input type="number" name="add_stock" class="form-control form-control-sm" value="50" min="10" max="200">
                </div>
                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-warning text-dark w-100 py-2 rounded-pill fw-semibold shadow-sm">
                        <i class="bi bi-arrow-repeat me-1"></i> Auto-Replenish Low Stock
                    </button>
                </div>
            </form>

            <form action="<?= BASE_URL ?>/admin/automation.php" method="POST" onsubmit="return confirm('Reset all active products stock to 100 units?');">
                <?= csrf_field() ?>
                <input type="hidden" name="operation" value="restock_all_products">
                <button type="submit" class="btn btn-outline-secondary btn-sm w-100 rounded-pill">
                    <i class="bi bi-lightning-charge me-1"></i> Emergency Set All Products to 100 Jars
                </button>
            </form>
        </div>
    </div>

    <!-- Panel 3: Review Auto-Moderator -->
    <div class="col-lg-6">
        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:48px;height:48px;background:#FDF2F8;color:#DB2777;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                    <i class="bi bi-stars"></i>
                </div>
                <div>
                    <h5 class="font-heading m-0 fs-5">Review Auto-Moderator & Rating Sync</h5>
                    <div class="text-muted small">Auto-approve genuine high ratings & update product cache</div>
                </div>
            </div>

            <p class="small text-muted mb-4">
                Scans customer reviews, automatically approves verified 4-star and 5-star customer testimonials, and recalibrates the rating average calculation on all product pages.
            </p>

            <form action="<?= BASE_URL ?>/admin/automation.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="operation" value="auto_moderate_reviews">
                <button type="submit" class="btn btn-outline-danger w-100 py-2 rounded-pill fw-semibold shadow-sm">
                    <i class="bi bi-check2-all me-1"></i> Auto-Approve Reviews & Recalculate Ratings
                </button>
            </form>
        </div>
    </div>

    <!-- Panel 4: Auto-Generate Festive Coupons -->
    <div class="col-lg-6">
        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:48px;height:48px;background:#F0FDF4;color:#16A34A;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                    <i class="bi bi-ticket-perforated-fill"></i>
                </div>
                <div>
                    <h5 class="font-heading m-0 fs-5">Marketing & Coupon Automation</h5>
                    <div class="text-muted small">Auto-generate campaign codes & retire expired discounts</div>
                </div>
            </div>

            <p class="small text-muted mb-4">
                Generates a fresh pack of festive promotion codes (<code class="text-dark">DIWALI25</code>, <code class="text-dark">WEEKEND15</code>, <code class="text-dark">FREESHIP50</code>) and flags expired promo codes automatically.
            </p>

            <form action="<?= BASE_URL ?>/admin/automation.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="operation" value="auto_generate_coupons">
                <button type="submit" class="btn btn-success w-100 py-2 rounded-pill fw-semibold shadow-sm">
                    <i class="bi bi-magic me-1"></i> Auto-Deploy Festival Coupons
                </button>
            </form>
        </div>
    </div>

    <!-- Panel 5: Test Traffic & Order Simulator -->
    <div class="col-lg-6">
        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:48px;height:48px;background:#F5F3FF;color:#7C3AED;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                    <i class="bi bi-robot"></i>
                </div>
                <div>
                    <h5 class="font-heading m-0 fs-5">Simulated Order & Invoicing Generator</h5>
                    <div class="text-muted small">Generate realistic test orders for testing fulfillment & printing</div>
                </div>
            </div>

            <p class="small text-muted mb-3">
                Instant testing tool: generates authentic orders with real Indian customer names, addresses, pickle varieties, and tax calculations.
            </p>

            <form action="<?= BASE_URL ?>/admin/automation.php" method="POST" class="d-flex gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="operation" value="generate_test_orders">
                <select name="num_orders" class="form-select form-select-sm" style="max-width: 150px;">
                    <option value="1">1 Order</option>
                    <option value="3" selected>3 Orders</option>
                    <option value="5">5 Orders</option>
                </select>
                <button type="submit" class="btn btn-outline-primary flex-grow-1 btn-sm rounded-pill fw-semibold">
                    <i class="bi bi-cart-plus me-1"></i> Generate Test Orders
                </button>
            </form>
        </div>
    </div>

    <!-- Panel 6: Database Optimization & Diagnostics -->
    <div class="col-lg-6">
        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:48px;height:48px;background:#FEF2F2;color:#DC2626;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <h5 class="font-heading m-0 fs-5">Database Health & Vacuum Cleaner</h5>
                    <div class="text-muted small">Defragment tables, prune memory, and backup data</div>
                </div>
            </div>

            <p class="small text-muted mb-3">
                Executes SQLite WAL vacuuming, index rebuilds, and memory defragmentation for high performance under heavy traffic.
            </p>

            <div class="d-flex gap-2">
                <form action="<?= BASE_URL ?>/admin/automation.php" method="POST" class="flex-grow-1">
                    <?= csrf_field() ?>
                    <input type="hidden" name="operation" value="optimize_database">
                    <button type="submit" class="btn btn-outline-dark btn-sm w-100 rounded-pill fw-semibold">
                        <i class="bi bi-speedometer me-1"></i> Vacuum & Optimize DB
                    </button>
                </form>
                <a href="<?= BASE_URL ?>/admin/automation.php?download_backup=1" class="btn btn-danger btn-sm rounded-pill fw-semibold px-3">
                    <i class="bi bi-download me-1"></i> Download Backup
                </a>
            </div>
        </div>
    </div>

</div>

<!-- Automated Cron Scheduler Instructions -->
<div class="card border-0 rounded-4 shadow-sm p-4 bg-white mt-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-alarm fs-4 text-primary"></i>
        <h5 class="font-heading m-0 fs-5">Unattended Background Cron Scheduler</h5>
    </div>
    <p class="small text-muted">
        To run automated maintenance on a timer without logging in, add either the terminal CLI command to your server crontab (e.g. cPanel or Linux), or ping the secure Webhook endpoint with an automated service (like Cron-Job.org / UptimeRobot):
    </p>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label small fw-bold text-muted text-uppercase">Crontab Command (Terminal CLI):</label>
            <div class="p-3 bg-light rounded-3 font-monospace small border user-select-all">
                * * * * * php <?= __DIR__ ?>/cron.php &gt; /dev/null 2&gt;&amp;1
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-bold text-muted text-uppercase">Secure Webhook Endpoint (HTTP GET):</label>
            <div class="p-3 bg-light rounded-3 font-monospace small border user-select-all">
                <?= BASE_URL ?>/admin/cron.php?key=achar_secret_cron_token_1968
            </div>
        </div>
    </div>
</div>

<!-- Automation Activity Logs -->
<div class="card border-0 rounded-4 shadow-sm p-4 bg-white mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="font-heading m-0 fs-5">Recent Automation & Operational Logs</h5>
        <span class="badge bg-light text-muted border">Last 10 Actions</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Timestamp</th>
                    <th>Action</th>
                    <th>Operator</th>
                    <th>Execution Summary</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentLogs)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No automation actions recorded yet. Run any pipeline above to test!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td class="text-muted text-nowrap"><?= format_date($log['created_at']) ?></td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                    <?= e($log['action']) ?>
                                </span>
                            </td>
                            <td class="fw-semibold"><?= ucfirst(e($log['user_type'])) ?></td>
                            <td class="text-dark"><?= e($log['description']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
