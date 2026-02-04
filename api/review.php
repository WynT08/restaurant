<?php
header('Content-Type: application/json');
require_once '../config/database.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(["error" => "Kết nối thất bại"]);
    exit;
}
$result = $conn->query("SELECT r.id, r.user_name, r.rating, r.content, r.created_at FROM reviews r ORDER BY r.created_at DESC LIMIT 10");
$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}
$conn->close();
echo json_encode($reviews);
?>