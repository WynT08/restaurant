<?php
header('Content-Type: application/json');
require_once '../config/database.php';
// Xử lý tạo đơn hàng mới (POST)
$db = new Database();
$conn = $db->getConnection();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Debug: log dữ liệu nhận được để kiểm tra
	file_put_contents(__DIR__ . '/orders_debug.log', date('Y-m-d H:i:s') . "\n" . file_get_contents('php://input') . "\n\n", FILE_APPEND);
	// Đọc lại input vì file_get_contents đã đọc hết stream
	$input = json_decode(file_get_contents('php://input'), true);
	// Nếu $input null, thử lấy lại từ biến tạm
	if (!$input) {
		$input = json_decode(file_get_contents(__DIR__ . '/orders_debug.log'), true);
	}
	$input = json_decode(file_get_contents('php://input'), true);
	if (!$input || !isset($input['items']) || !is_array($input['items']) || count($input['items']) === 0) {
		echo json_encode(['success' => false, 'message' => 'Dữ liệu đơn hàng không hợp lệ!']);
		exit;
	}
	// Tạo order_number duy nhất
	$order_number = 'ORD' . date('YmdHis') . rand(100,999);
	$order_type = isset($input['order_type']) ? $input['order_type'] : 'delivery';
	$customer_name = $input['customer_name'] ?? '';
	$customer_phone = $input['customer_phone'] ?? '';
	$customer_email = $input['customer_email'] ?? '';
	$table_id = !empty($input['table_id']) ? $input['table_id'] : null;
	$calc_subtotal = 0;
	foreach ($input['items'] as $item) {
		$qty = isset($item['quantity']) ? (float)$item['quantity'] : 0;
		$price = isset($item['price']) ? (float)$item['price'] : 0;
		$calc_subtotal += $qty * $price;
	}
	$subtotal = $calc_subtotal;
	$tax = isset($input['tax']) ? (float)$input['tax'] : 0;
	$discount = isset($input['discount']) ? (float)$input['discount'] : 0;
	$total_amount = $subtotal + $tax - $discount;
	$order_status = 'preparing';
	$payment_status = 'paid';
	$notes = $input['special_requests'] ?? '';
	try {
		$conn->beginTransaction();
		$stmt = $conn->prepare("INSERT INTO orders (order_number, table_id, order_type, waiter_id, customer_name, customer_phone, subtotal, tax, discount, total_amount, notes, order_status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$waiter_id = 1; // waiter_id mặc định (có thể sửa thành 0 hoặc lấy từ session nếu có)
		$stmt->execute([
			$order_number,
			$table_id,
			$order_type,
			$waiter_id,
			$customer_name,
			$customer_phone,
			$subtotal,
			$tax,
			$discount,
			$total_amount,
			$notes,
			$order_status,
			$payment_status
		]);
		$order_id = $conn->lastInsertId();
		// Lưu từng món vào order_items (đầy đủ unit_price, subtotal, special_instructions)
		$stmtItem = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, unit_price, subtotal, special_instructions) VALUES (?, ?, ?, ?, ?, ?)");
		foreach ($input['items'] as $item) {
			$qty = isset($item['quantity']) ? (float)$item['quantity'] : 0;
			$price = isset($item['price']) ? (float)$item['price'] : 0;
			$item_subtotal = $qty * $price;
			$special_instructions = $item['notes'] ?? '';
			$stmtItem->execute([
				$order_id,
				$item['item_id'],
				$qty,
				$price,
				$item_subtotal,
				$special_instructions
			]);
		}
		$conn->commit();
		echo json_encode(['success' => true, 'order_number' => $order_number]);
	} catch (Exception $e) {
		$conn->rollBack();
		echo json_encode(['success' => false, 'message' => 'Lưu đơn hàng thất bại!', 'error' => $e->getMessage()]);
	}
	exit;
}
// GET: Lấy danh sách đơn hàng
$stmt = $conn->prepare("SELECT order_id, order_number, created_at, total_amount, order_status FROM orders");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(["success"=>true, "data"=>$orders]);
?>