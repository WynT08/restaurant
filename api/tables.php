<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $tables = $db->query("SELECT table_id, table_number, table_name, capacity, location, status FROM restaurant_tables ORDER BY table_number")
                     ->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $tables]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
    exit();
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input) || empty($input['table_id']) || empty($input['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'table_id và status là bắt buộc']);
        exit();
    }

    $allowed = ['available','occupied','reserved','maintenance'];
    if (!in_array($input['status'], $allowed, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
        exit();
    }

    try {
        $stmt = $db->prepare("UPDATE restaurant_tables SET status = :status WHERE table_id = :table_id");
        $stmt->execute([
            ':status' => $input['status'],
            ':table_id' => (int) $input['table_id']
        ]);
        echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật trạng thái']);
    }
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
