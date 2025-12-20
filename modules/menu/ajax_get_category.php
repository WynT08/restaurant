<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();
requirePermission('manager');

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT * FROM categories WHERE category_id = :id LIMIT 1");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cat) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy danh mục']);
        exit;
    }
    echo json_encode(['success' => true, 'data' => $cat]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
