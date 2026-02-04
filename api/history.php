<?php
header('Content-Type: application/json');
require_once '../config/database.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(["error" => "Kết nối thất bại"]);
    exit;
}
$result = $conn->query("SELECT id, order_code, order_date, total, status FROM orders WHERE status='completed'");
$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}
$conn->close();
echo json_encode($history);
?>