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

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
