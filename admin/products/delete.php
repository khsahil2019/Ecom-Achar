<?php
/**
 * Admin Delete Product
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
$pdo = db();

$productId = (int)($_GET['id'] ?? 0);
if ($productId) {
    $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $prod = $stmt->fetch();

    if ($prod) {
        $del = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $del->execute([$productId]);
        set_flash('info', 'Pickle recipe "' . $prod['name'] . '" has been deleted.');
    }
}

header('Location: ' . BASE_URL . '/admin/products/index.php');
exit;
