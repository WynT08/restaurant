<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../config/database.php';
ob_clean();
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
if (!hasPermission('manager')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

$recipe_id = isset($_POST['recipe_id']) ? intval($_POST['recipe_id']) : 0;
if ($recipe_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu hoặc dữ liệu không hợp lệ']);
    exit;
}

try {
    $query = "DELETE FROM recipes WHERE recipe_id = :recipe_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':recipe_id', $recipe_id);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy công thức hoặc không xóa được']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
