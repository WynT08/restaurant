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

$ingredient_id = isset($_POST['ingredient_id']) ? intval($_POST['ingredient_id']) : 0;
$quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;

if ($ingredient_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu hoặc dữ liệu không hợp lệ']);
    exit;
}

// Tìm công thức có nguyên liệu này cho món nào (nếu cần truyền thêm item_id thì bổ sung)
try {
    // Nếu cần truyền item_id, hãy lấy thêm từ $_POST['item_id'] và thêm vào WHERE
    $query = "UPDATE recipes SET quantity = :quantity WHERE ingredient_id = :ingredient_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':ingredient_id', $ingredient_id);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy công thức hoặc không có thay đổi']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
