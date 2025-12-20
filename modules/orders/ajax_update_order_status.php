<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';
$payment_status = isset($_POST['payment_status']) ? $_POST['payment_status'] : null;

$allowed_status = ['pending','preparing','served','completed','cancelled'];
if ($order_id <= 0 || !in_array($status, $allowed_status, true)) {
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ']);
    exit();
}

try {
    // Lấy bàn của đơn để cập nhật trạng thái bàn khi hoàn thành
    $tableStmt = $db->prepare("SELECT table_id FROM orders WHERE order_id = :order_id LIMIT 1");
    $tableStmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $tableStmt->execute();
    $orderData = $tableStmt->fetch(PDO::FETCH_ASSOC);

    if (!$orderData) {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy đơn hàng']);
        exit();
    }

    $db->beginTransaction();

    $sql = "UPDATE orders SET order_status = :status";
    if ($payment_status !== null) {
        $sql .= ", payment_status = :payment_status";
    }
    $sql .= " WHERE order_id = :order_id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':status', $status);
    if ($payment_status !== null) {
        $stmt->bindParam(':payment_status', $payment_status);
    }
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->execute();

    // Nếu đơn hoàn thành/hủy thì mở bàn trống lại
    if (in_array($status, ['completed', 'cancelled'], true) && !empty($orderData['table_id'])) {
        $tableUpdate = $db->prepare("UPDATE restaurant_tables SET status = 'available' WHERE table_id = :table_id");
        $tableUpdate->bindParam(':table_id', $orderData['table_id'], PDO::PARAM_INT);
        $tableUpdate->execute();
    }

    $db->commit();

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>