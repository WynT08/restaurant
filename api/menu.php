<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = (new Database())->getConnection();

    $categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;

    $catSql = 'SELECT category_id, category_name, description, display_order FROM categories ORDER BY display_order, category_name';
    $categories = $db->query($catSql)->fetchAll(PDO::FETCH_ASSOC);

    $itemSql = 'SELECT item_id, category_id, item_name, description, price, image, is_vegetarian, is_available, display_order 
                FROM menu_items WHERE is_available = 1';
    $params = [];
    if ($categoryId) {
        $itemSql .= ' AND category_id = :category_id';
        $params[':category_id'] = $categoryId;
    }
    $itemSql .= ' ORDER BY display_order, item_name';
    $stmt = $db->prepare($itemSql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'items' => $items
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
