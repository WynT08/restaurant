<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$date = $_GET['date'] ?? null;
$time = $_GET['time'] ?? null;

if (!$date || !$time) {
    echo json_encode(['available_tables' => []]);
    exit();
}

$db = (new Database())->getConnection();

// Lấy danh sách các bàn chưa bị đặt trong khoảng ±3 giờ quanh thời gian chọn
$sql = "SELECT table_id FROM restaurant_tables WHERE status = 'available' AND table_id NOT IN (
    SELECT table_id FROM reservations
    WHERE status IN ('pending','confirmed')
      AND ABS(TIMESTAMPDIFF(MINUTE, CONCAT(reservation_date,' ',reservation_time), :dt)) < 180
)";
$stmt = $db->prepare($sql);
$stmt->bindValue(':dt', $date . ' ' . $time);
$stmt->execute();
$available = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['available_tables' => array_map('intval', $available)]);
