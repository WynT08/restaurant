<?php
header('Content-Type: application/json');
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT table_id, table_number, table_name, capacity, location, status FROM restaurant_tables");
$stmt->execute();
try {
	$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode([
		'success' => true,
		'data' => $tables
	]);
} catch (Exception $e) {
	echo json_encode([
		'success' => false,
		'message' => 'Lỗi truy vấn danh sách bàn',
		'error' => $e->getMessage()
	]);
}
?>