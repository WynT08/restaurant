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
    $limit = isset($_GET['limit']) ? max(1, (int) $_GET['limit']) : 20;
    try {
        $sql = "SELECT o.order_id, o.order_number, o.table_id, o.order_type, o.subtotal, o.tax, o.discount, o.total_amount,
                       o.customer_name, o.customer_phone, o.order_status, o.payment_status, o.created_at,
                       t.table_number
                FROM orders o
                LEFT JOIN restaurant_tables t ON o.table_id = t.table_id
                ORDER BY o.created_at DESC
                LIMIT :limit";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch items per order
        $orderIds = array_column($orders, 'order_id');
        $itemsMap = [];
        if (!empty($orderIds)) {
            $in = implode(',', array_fill(0, count($orderIds), '?'));
            $itemSql = "SELECT oi.order_id, oi.item_id, oi.quantity, oi.unit_price, oi.subtotal, oi.special_instructions,
                               mi.item_name
                        FROM order_items oi
                        JOIN menu_items mi ON oi.item_id = mi.item_id
                        WHERE oi.order_id IN ($in)";
            $itemStmt = $db->prepare($itemSql);
            $itemStmt->execute($orderIds);
            while ($row = $itemStmt->fetch(PDO::FETCH_ASSOC)) {
                $itemsMap[$row['order_id']][] = $row;
            }
        }

        foreach ($orders as &$order) {
            $order['items'] = $itemsMap[$order['order_id']] ?? [];
        }

        echo json_encode(['success' => true, 'data' => $orders]);
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

    $items = $input['items'] ?? [];
    if (empty($items)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order items required']);
        exit();
    }

    // Fetch item prices to prevent client tampering
    $itemIds = array_map('intval', array_column($items, 'item_id'));
    $in = implode(',', array_fill(0, count($itemIds), '?'));
    $menuSql = "SELECT item_id, price FROM menu_items WHERE item_id IN ($in) AND is_available = 1";
    $menuStmt = $db->prepare($menuSql);
    $menuStmt->execute($itemIds);
    $priceMap = [];
    foreach ($menuStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $priceMap[$row['item_id']] = (float) $row['price'];
    }

    $subtotal = 0;
    foreach ($items as &$item) {
        $id = (int) $item['item_id'];
        $qty = max(1, (int) ($item['quantity'] ?? 1));
        $price = $priceMap[$id] ?? null;
        if ($price === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid or unavailable menu item: ' . $id]);
            exit();
        }
        $item['quantity'] = $qty;
        $item['unit_price'] = $price;
        $item['line_total'] = $qty * $price;
        $subtotal += $item['line_total'];
    }

    $tax = isset($input['tax']) ? (float) $input['tax'] : round($subtotal * (TAX_RATE / 100), 2);
    $discount = isset($input['discount']) ? (float) $input['discount'] : 0;
    $total = $subtotal + $tax - $discount;

    $orderNumber = generateOrderNumber();
    $tableId = isset($input['table_id']) ? (int) $input['table_id'] : null;
    $orderType = $input['order_type'] ?? 'dine_in';
    $customerName = $input['customer_name'] ?? null;
    $customerPhone = $input['customer_phone'] ?? null;
    $notes = $input['notes'] ?? null;
    $paymentMethod = $input['payment_method'] ?? null;

    try {
        $db->beginTransaction();

        $orderSql = "INSERT INTO orders (
            order_number, table_id, order_type, waiter_id,
            subtotal, tax, discount, total_amount,
            customer_name, customer_phone, notes,
            order_status, payment_status
        ) VALUES (
            :order_number, :table_id, :order_type, :waiter_id,
            :subtotal, :tax, :discount, :total_amount,
            :customer_name, :customer_phone, :notes,
            :order_status, :payment_status
        )";

        $orderStmt = $db->prepare($orderSql);
        $orderStatus = (!empty($input['send_to_kitchen'])) ? 'preparing' : 'pending';
        $paymentStatus = $paymentMethod ? 'paid' : 'unpaid';

        $orderStmt->bindValue(':order_number', $orderNumber);
        $orderStmt->bindValue(':table_id', $tableId);
        $orderStmt->bindValue(':order_type', $orderType);
        $orderStmt->bindValue(':waiter_id', $_SESSION['user_id']);
        $orderStmt->bindValue(':subtotal', $subtotal);
        $orderStmt->bindValue(':tax', $tax);
        $orderStmt->bindValue(':discount', $discount);
        $orderStmt->bindValue(':total_amount', $total);
        $orderStmt->bindValue(':customer_name', $customerName);
        $orderStmt->bindValue(':customer_phone', $customerPhone);
        $orderStmt->bindValue(':notes', $notes);
        $orderStmt->bindValue(':order_status', $orderStatus);
        $orderStmt->bindValue(':payment_status', $paymentStatus);
        $orderStmt->execute();

        $orderId = (int) $db->lastInsertId();

        $itemSql = "INSERT INTO order_items (order_id, item_id, quantity, unit_price, subtotal, special_instructions)
                    VALUES (:order_id, :item_id, :quantity, :unit_price, :subtotal, :special_instructions)";
        $itemStmt = $db->prepare($itemSql);
        foreach ($items as $item) {
            $itemStmt->execute([
                ':order_id' => $orderId,
                ':item_id' => $item['item_id'],
                ':quantity' => $item['quantity'],
                ':unit_price' => $item['unit_price'],
                ':subtotal' => $item['line_total'],
                ':special_instructions' => $item['notes'] ?? null,
            ]);
            updateIngredientStock($db, $item['item_id'], $item['quantity']);
        }

        if ($paymentStatus === 'paid') {
            $paySql = "INSERT INTO payments (order_id, amount, payment_method, cashier_id)
                       VALUES (:order_id, :amount, :payment_method, :cashier_id)";
            $payStmt = $db->prepare($paySql);
            $payStmt->execute([
                ':order_id' => $orderId,
                ':amount' => $total,
                ':payment_method' => $paymentMethod,
                ':cashier_id' => $_SESSION['user_id']
            ]);
        }

        if ($tableId && $orderType === 'dine_in') {
            $tableStmt = $db->prepare("UPDATE restaurant_tables SET status = 'occupied' WHERE table_id = :table_id");
            $tableStmt->execute([':table_id' => $tableId]);
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Order created',
            'order_id' => $orderId,
            'order_number' => $orderNumber
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create order']);
    }
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);

// Reduce ingredient stock based on recipe
function updateIngredientStock(PDO $db, int $itemId, int $quantity): void
{
    $sql = "SELECT r.ingredient_id, r.quantity, i.current_stock
            FROM recipes r
            JOIN ingredients i ON r.ingredient_id = i.ingredient_id
            WHERE r.item_id = :item_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':item_id' => $itemId]);
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($recipes)) {
        return;
    }

    $updateSql = "UPDATE ingredients SET current_stock = :new_stock WHERE ingredient_id = :ingredient_id";
    $logSql = "INSERT INTO inventory_transactions (ingredient_id, transaction_type, quantity, reference_type, reference_id, performed_by)
               VALUES (:ingredient_id, 'out', :quantity, 'order', :order_id, :user_id)";

    foreach ($recipes as $recipe) {
        $usedQty = (float) $recipe['quantity'] * $quantity;
        $newStock = (float) $recipe['current_stock'] - $usedQty;

        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute([
            ':new_stock' => $newStock,
            ':ingredient_id' => $recipe['ingredient_id']
        ]);

        $logStmt = $db->prepare($logSql);
        $logStmt->execute([
            ':ingredient_id' => $recipe['ingredient_id'],
            ':quantity' => $usedQty,
            ':order_id' => $itemId,
            ':user_id' => $_SESSION['user_id'] ?? null
        ]);
    }
}
