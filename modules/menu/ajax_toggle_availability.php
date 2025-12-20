<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();
requirePermission('manager');

header('Content-Type: application/json');

$item_id = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
$is_available = isset($_POST['is_available']) ? (int) $_POST['is_available'] : 0;

if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Thiếu item_id']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("UPDATE menu_items SET is_available = :av WHERE item_id = :id");
    $stmt->bindParam(':av', $is_available, PDO::PARAM_INT);
    $stmt->bindParam(':id', $item_id, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
