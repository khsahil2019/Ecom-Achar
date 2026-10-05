<?php
/**
 * Achar Heritage - Background Automated Cron Worker
 * Can be run via CLI: php admin/cron.php
 * Or via HTTP Webhook: curl http://127.0.0.1:8000/admin/cron.php?key=achar_secret_cron_token_1968
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    $providedKey = $_GET['key'] ?? '';
    $validKey = 'achar_secret_cron_token_1968';
    if ($providedKey !== $validKey) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized cron access key.']);
        exit;
    }
}

$pdo = db();
$report = [];

// 1. Expire past coupons
$expiredCoupons = $pdo->exec("UPDATE coupons SET status = 'expired' WHERE end_date < DATE('now') AND status = 'active'");
$report['expired_coupons'] = $expiredCoupons;

// 2. Auto-advance matured shipped orders to Delivered
$deliveredOrders = $pdo->exec("UPDATE orders SET order_status = 'Delivered', payment_status = 'paid' WHERE order_status = 'Shipped' AND created_at <= datetime('now', '-2 days')");
$report['delivered_orders'] = $deliveredOrders;

// 3. Auto-Moderate High Rating Reviews
$approvedReviews = $pdo->exec("UPDATE reviews SET status = 'approved' WHERE status = 'pending' AND rating >= 4");
$report['approved_reviews'] = $approvedReviews;

// 4. Synchronize product rating caches
$syncCount = 0;
$prods = $pdo->query("SELECT id FROM products")->fetchAll();
foreach ($prods as $p) {
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
$report['synced_products'] = $syncCount;

// 5. Clean stale cart sessions older than 30 days
$cleanedCarts = $pdo->exec("DELETE FROM cart WHERE updated_at <= datetime('now', '-30 days')");
$report['cleaned_stale_carts'] = $cleanedCarts;

// Log to activity logs
log_activity('system', 0, 'cron_execution', "Automated background cron executed successfully. Expired coupons: {$expiredCoupons}, Delivered orders: {$deliveredOrders}, Reviews approved: {$approvedReviews}");

if ($isCli) {
    echo "========================================\n";
    echo "Achar Heritage - Automated Cron Complete\n";
    echo "========================================\n";
    foreach ($report as $task => $count) {
        echo sprintf("%-25s: %d\n", $task, $count);
    }
    echo "Execution Timestamp: " . date('Y-m-d H:i:s') . "\n";
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'results' => $report
    ], JSON_PRETTY_PRINT);
}
