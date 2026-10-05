<?php
/**
 * AJAX Wishlist Handler
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    json_response(['success' => false, 'require_login' => true, 'message' => 'Please login to save favorites.'], 401);
}

$pdo = db();
$customerId = customer_id();
$productId = (int)($_POST['product_id'] ?? 0);

if (!$productId) {
    json_response(['success' => false, 'message' => 'Invalid product.'], 400);
}

// Ensure customer has a wishlist record
$wStmt = $pdo->prepare("SELECT id FROM wishlist WHERE customer_id = ?");
$wStmt->execute([$customerId]);
$wishlist = $wStmt->fetch();

if (!$wishlist) {
    $cStmt = $pdo->prepare("INSERT INTO wishlist (customer_id) VALUES (?)");
    $cStmt->execute([$customerId]);
    $wishlistId = (int)$pdo->lastInsertId();
} else {
    $wishlistId = (int)$wishlist['id'];
}

// Check if item exists in wishlist
$iStmt = $pdo->prepare("SELECT id FROM wishlist_items WHERE wishlist_id = ? AND product_id = ?");
$iStmt->execute([$wishlistId, $productId]);
$existing = $iStmt->fetch();

if ($existing) {
    $del = $pdo->prepare("DELETE FROM wishlist_items WHERE id = ?");
    $del->execute([$existing['id']]);
    $action = 'removed';
} else {
    $ins = $pdo->prepare("INSERT INTO wishlist_items (wishlist_id, product_id) VALUES (?, ?)");
    $ins->execute([$wishlistId, $productId]);
    $action = 'added';
}

$cnt = get_wishlist_count();
json_response([
    'success' => true,
    'action' => $action,
    'wishlist_count' => $cnt
]);
