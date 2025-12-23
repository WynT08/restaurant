<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Lấy danh sách nguyên liệu sắp hết
$stmt = $db->query("SELECT ingredient_id, ingredient_name, current_stock, reorder_level FROM ingredients WHERE current_stock <= reorder_level");
$lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $lowStock]);
