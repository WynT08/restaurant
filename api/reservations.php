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
        $sql = "SELECT r.reservation_id, r.customer_name, r.customer_phone, r.customer_email,
                       r.table_id, r.reservation_date, r.reservation_time, r.number_of_guests,
                       r.special_requests, r.status, r.created_at,
                       t.table_number
                FROM reservations r
                LEFT JOIN restaurant_tables t ON r.table_id = t.table_id
                WHERE r.reservation_date >= CURDATE()
                ORDER BY r.reservation_date, r.reservation_time";
        $reservations = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $reservations]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
    exit();
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
        exit();
    }

    $required = ['customer_name','customer_phone','reservation_date','reservation_time','number_of_guests'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Missing field: $field"]);
            exit();
        }
    }

    $sql = "INSERT INTO reservations (
                customer_name, customer_phone, customer_email,
                table_id, reservation_date, reservation_time,
                number_of_guests, special_requests, status, created_by
            ) VALUES (
                :customer_name, :customer_phone, :customer_email,
                :table_id, :reservation_date, :reservation_time,
                :number_of_guests, :special_requests, 'pending', :created_by
            )";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':customer_name' => $input['customer_name'],
            ':customer_phone' => $input['customer_phone'],
            ':customer_email' => $input['customer_email'] ?? null,
            ':table_id' => $input['table_id'] ?? null,
            ':reservation_date' => $input['reservation_date'],
            ':reservation_time' => $input['reservation_time'],
            ':number_of_guests' => (int) $input['number_of_guests'],
            ':special_requests' => $input['special_requests'] ?? null,
            ':created_by' => $_SESSION['user_id'] ?? null
        ]);

        echo json_encode(['success' => true, 'message' => 'Reservation created']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create reservation']);
    }
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
