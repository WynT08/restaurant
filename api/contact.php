<?php
// api/contact.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $subject = trim($data['subject'] ?? '');
    $message = trim($data['message'] ?? '');

    if (!$name || !$phone || !$subject || !$message) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc.']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO contact (name, phone, email, subject, message, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
    $result = $stmt->execute([$name, $phone, $email, $subject, $message]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Liên hệ của bạn đã được gửi. Cảm ơn bạn!']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu liên hệ.']);
    }
    exit();
}


// API cho admin lấy danh sách liên hệ (GET) hoặc lấy phản hồi cho người dùng
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Nếu có phone + subject thì trả về phản hồi cho người dùng
    if (isset($_GET['phone']) && isset($_GET['subject'])) {
        $phone = trim($_GET['phone']);
        $subject = trim($_GET['subject']);
        $stmt = $conn->prepare("SELECT response FROM contact WHERE phone = ? AND subject = ? AND status = 'responded' ORDER BY responded_at DESC LIMIT 1");
        $stmt->execute([$phone, $subject]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data && !empty($data['response'])) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Chưa có phản hồi từ nhà hàng.']);
        }
        exit();
    }
    // Mặc định: trả về danh sách liên hệ cho admin
    $stmt = $conn->prepare("SELECT id, name, phone, email, subject, message, status, response, created_at, responded_at FROM contact ORDER BY created_at DESC");
    $stmt->execute();
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $contacts]);
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
