<?php
header('Content-Type: application/json');
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT item_id, item_name, description, price, image, category_id, is_available FROM menu_items WHERE is_available = 1");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode([
	'success' => true,
	'items' => $items
]);
?>