<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();

header('Content-Type: application/json');

$reservation_id = isset($_POST['reservation_id']) ? (int) $_POST['reservation_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

$allowed = ['pending','confirmed','completed','cancelled','no_show'];
if ($reservation_id <= 0 || !in_array($status, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("UPDATE reservations SET status = :status WHERE reservation_id = :id");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT);
    $stmt->execute();

    // Nếu chuyển sang completed, cập nhật trạng thái bàn về 'available'
    if ($status === 'completed') {
        // Lấy table_id của reservation này
        $tableStmt = $db->prepare("SELECT table_id FROM reservations WHERE reservation_id = :id");
        $tableStmt->bindParam(':id', $reservation_id, PDO::PARAM_INT);
        $tableStmt->execute();
        $row = $tableStmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($row['table_id'])) {
            $updateTable = $db->prepare("UPDATE restaurant_tables SET status = 'available' WHERE table_id = :tid");
            $updateTable->bindParam(':tid', $row['table_id'], PDO::PARAM_INT);
            $updateTable->execute();
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
