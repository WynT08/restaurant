<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (empty($data['items']) || count($data['items']) == 0) {
        throw new Exception('Đơn hàng trống');
    }
    
    // Generate order number
    $order_number = generateOrderNumber();

    // Calculate amounts safely
    $calc_subtotal = 0;
    foreach ($data['items'] as $item) {
        $qty = isset($item['quantity']) ? (float) $item['quantity'] : 0;
        $price = isset($item['price']) ? (float) $item['price'] : 0;
        $calc_subtotal += $qty * $price;
    }
    $subtotal = isset($data['subtotal']) ? (float) $data['subtotal'] : $calc_subtotal;
    $tax_amount = isset($data['tax']) ? (float) $data['tax'] : 0;
    $discount_amount = isset($data['discount']) ? (float) $data['discount'] : 0;
    $total_amount = isset($data['total']) ? (float) $data['total'] : ($subtotal + $tax_amount - $discount_amount);
    
    // Insert order
    $query = "INSERT INTO orders (
        order_number, table_id, user_id, order_type,
        subtotal, tax_amount, discount_amount, total_amount,
        customer_name, customer_phone, notes,
        order_status, payment_status
    ) VALUES (
        :order_number, :table_id, :user_id, :order_type,
        :subtotal, :tax_amount, :discount_amount, :total_amount,
        :customer_name, :customer_phone, :notes,
        :order_status, :payment_status
    )";
    
    $stmt = $db->prepare($query);
    
    // Yêu cầu: khi đặt đơn phải gửi bếp và thanh toán ngay
    $send_to_kitchen = true;
    if (empty($data['payment_method'])) {
        $data['payment_method'] = 'cash';
    }
    $payment_status = 'paid';
    $order_status = 'preparing';
    
    $stmt->bindParam(':order_number', $order_number);
    $stmt->bindParam(':table_id', $data['table_id']);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':order_type', $data['order_type']);
    $stmt->bindParam(':subtotal', $subtotal);
    $stmt->bindParam(':tax_amount', $tax_amount);
    $stmt->bindParam(':discount_amount', $discount_amount);
    $stmt->bindParam(':total_amount', $total_amount);
    $stmt->bindParam(':customer_name', $data['customer_name']);
    $stmt->bindParam(':customer_phone', $data['customer_phone']);
    $stmt->bindParam(':notes', $data['notes']);
    $stmt->bindParam(':order_status', $order_status);
    $stmt->bindParam(':payment_status', $payment_status);
    
    $stmt->execute();
    $order_id = $db->lastInsertId();
    
    // Insert order items
    $query = "INSERT INTO order_items (order_id, item_id, quantity, unit_price, subtotal, special_instructions)
              VALUES (:order_id, :item_id, :quantity, :unit_price, :subtotal, :special_instructions)";
    $stmt = $db->prepare($query);
    
    foreach ($data['items'] as $item) {
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':item_id', $item['item_id']);
        $qty = isset($item['quantity']) ? (float) $item['quantity'] : 0;
        $price = isset($item['price']) ? (float) $item['price'] : 0;
        $stmt->bindParam(':quantity', $qty);
        $stmt->bindParam(':unit_price', $price);
        $item_subtotal = $qty * $price;
        $stmt->bindParam(':subtotal', $item_subtotal);
        $special_instructions = $item['notes'] ??  '';
        $stmt->bindParam(':special_instructions', $special_instructions);
        $stmt->execute();
        
        // Update ingredient stock
        updateIngredientStock($db, $item['item_id'], $qty);
    }
    
    // Insert payment if paid
    if ($payment_status == 'paid') {
        $query = "INSERT INTO payments (order_id, payment_method, amount, payment_status, paid_at)
                  VALUES (:order_id, :payment_method, :amount, 'completed', NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':payment_method', $data['payment_method']);
        $stmt->bindParam(':amount', $total_amount);
        $stmt->execute();
    }
    
    // Update table status
    if (! empty($data['table_id']) && $data['order_type'] == 'dine_in') {
        $query = "UPDATE restaurant_tables SET status = 'occupied' WHERE table_id = :table_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':table_id', $data['table_id']);
        $stmt->execute();
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đơn hàng đã được tạo',
        'order_id' => $order_id,
        'order_number' => $order_number
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function updateIngredientStock($db, $item_id, $quantity) {
    try {
        $query = "SELECT r.*, i.current_stock 
                  FROM recipes r 
                  JOIN ingredients i ON r.ingredient_id = i.ingredient_id 
                  WHERE r.item_id = :item_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->execute();
        $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // If recipes/ingredients table missing, skip stock update
        return;
    }

    foreach ($recipes as $recipe) {
        $used_quantity = $recipe['quantity'] * $quantity;
        $new_stock = $recipe['current_stock'] - $used_quantity;
        
        try {
            $query = "UPDATE ingredients SET current_stock = :new_stock WHERE ingredient_id = :ingredient_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':new_stock', $new_stock);
            $stmt->bindParam(':ingredient_id', $recipe['ingredient_id']);
            $stmt->execute();
        } catch (Exception $e) {
            // skip if table missing
        }
        
        try {
            $query = "INSERT INTO inventory_transactions (ingredient_id, transaction_type, quantity, reference_type, reference_id, performed_by)
                      VALUES (:ingredient_id, 'out', :quantity, 'order', :order_id, :user_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':ingredient_id', $recipe['ingredient_id']);
            $stmt->bindParam(':quantity', $used_quantity);
            $stmt->bindParam(':order_id', $item_id);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
        } catch (Exception $e) {
            // skip log if table missing
        }
    }
}
?>