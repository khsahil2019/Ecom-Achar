<?php
/**
 * AJAX Live Search Handler
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$query = trim($_GET['q'] ?? '');
$catSlug = trim($_GET['category'] ?? '');

if (strlen($query) < 2 && empty($catSlug)) {
    json_response(['results' => []]);
}

try {
    $where = ["p.status = 'active'"];
    $params = [];

    if ($query !== '') {
        $searchTerm = '%' . $query . '%';
        $where[] = "(p.name LIKE ? OR p.short_description LIKE ? OR p.sku LIKE ? OR c.name LIKE ? OR p.ingredients LIKE ?)";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }

    if ($catSlug !== '') {
        $where[] = "c.slug = ?";
        $params[] = $catSlug;
    }

    $whereSql = implode(' AND ', $where);

    $stmt = db()->prepare("
        SELECT p.id, p.name, p.slug, p.price, p.weight, p.main_image, c.name as category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE $whereSql
        ORDER BY p.is_bestseller DESC, p.id DESC
        LIMIT 6
    ");
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    $results = [];
    foreach ($items as $item) {
        $results[] = [
            'id' => (int)$item['id'],
            'name' => $item['name'],
            'url' => BASE_URL . '/product.php?slug=' . urlencode($item['slug']),
            'image' => BASE_URL . '/uploads/products/' . $item['main_image'],
            'category' => $item['category_name'],
            'weight' => $item['weight'] ?? '500g',
            'price' => (float)$item['price'],
            'formatted_price' => format_price($item['price'])
        ];
    }

    json_response(['results' => $results]);
} catch (Exception $e) {
    json_response(['error' => 'Search error', 'results' => []], 500);
}
