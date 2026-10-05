<?php
/**
 * AJAX Cart Handler
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo = db();
$cartId = get_or_create_cart_id();

switch ($action) {
    case 'add':
        $productId = (int)($_POST['product_id'] ?? 0);
        $variantId = !empty($_POST['variant_id']) ? (int)$_POST['variant_id'] : null;
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        if (!$productId) {
            json_response(['success' => false, 'message' => 'Invalid product.'], 400);
        }

        // Verify Product
        $pStmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id = ? AND status = 'active'");
        $pStmt->execute([$productId]);
        $product = $pStmt->fetch();

        if (!$product) {
            json_response(['success' => false, 'message' => 'Product is currently unavailable.'], 404);
        }

        // If variant specified, verify variant
        if ($variantId) {
            $vStmt = $pdo->prepare("SELECT id, stock FROM product_variants WHERE id = ? AND product_id = ?");
            $vStmt->execute([$variantId, $productId]);
            $variant = $vStmt->fetch();
            if (!$variant) {
                $variantId = null;
            } elseif ($variant['stock'] <= 0) {
                json_response(['success' => false, 'message' => 'This variant size is out of stock.'], 400);
            }
        }

        // Check if item already in cart
        if ($variantId) {
            $itemStmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? AND variant_id = ?");
            $itemStmt->execute([$cartId, $productId, $variantId]);
        } else {
            $itemStmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? AND variant_id IS NULL");
            $itemStmt->execute([$cartId, $productId]);
        }
        $existing = $itemStmt->fetch();

        if ($existing) {
            $newQty = $existing['quantity'] + $quantity;
            $uStmt = $pdo->prepare("UPDATE cart_items SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $uStmt->execute([$newQty, $existing['id']]);
        } else {
            $iStmt = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)");
            $iStmt->execute([$cartId, $productId, $variantId, $quantity]);
        }

        $cartCount = get_cart_count();
        json_response([
            'success' => true,
            'message' => 'Added ' . $product['name'] . ' to your basket!',
            'cart_count' => $cartCount
        ]);
        break;

    case 'update':
        $itemId = (int)($_POST['item_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);

        if (!$itemId) {
            json_response(['success' => false, 'message' => 'Invalid item.'], 400);
        }

        if ($quantity <= 0) {
            $dStmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
            $dStmt->execute([$itemId, $cartId]);
        } else {
            $uStmt = $pdo->prepare("UPDATE cart_items SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND cart_id = ?");
            $uStmt->execute([$quantity, $itemId, $cartId]);
        }

        json_response([
            'success' => true,
            'message' => 'Cart updated.',
            'cart_count' => get_cart_count()
        ]);
        break;

    case 'remove':
        $itemId = (int)($_POST['item_id'] ?? 0);
        if ($itemId) {
            $dStmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
            $dStmt->execute([$itemId, $cartId]);
        }

        json_response([
            'success' => true,
            'message' => 'Item removed from basket.',
            'cart_count' => get_cart_count()
        ]);
        break;

    case 'update_pincode':
        $pin = sanitize($_POST['pincode'] ?? 'New Delhi 110001');
        $_SESSION['delivery_pincode'] = $pin;
        json_response([
            'success' => true,
            'message' => 'Pincode updated.',
            'pincode' => $pin
        ]);
        break;

    default:
        json_response(['error' => 'Unknown action.'], 400);
}
