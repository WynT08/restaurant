<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();

$order_id = $_GET['order_id'];

$database = new Database();
$db = $database->getConnection();

// Get order details
$query = "SELECT o.*, rt.table_number, u.full_name as waiter_name
          FROM orders o
          LEFT JOIN restaurant_tables rt ON o. table_id = rt.table_id
          LEFT JOIN users u ON o.waiter_id = u.user_id
          WHERE o.order_id = : order_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Get order items
$query = "SELECT oi.*, mi.item_name
          FROM order_items oi
          JOIN menu_items mi ON oi.item_id = mi. item_id
          WHERE oi.order_id = :order_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->execute();
$items = $stmt->fetchAll(PDO:: FETCH_ASSOC);
?>
<! DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn #<?php echo $order['order_number']; ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            width: 80mm;
            margin: 0 auto;
            padding: 10mm;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .info {
            margin-bottom: 15px;
            font-size:  12px;
        }
        . info div {
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin:  15px 0;
        }
        table th {
            text-align: left;
            border-bottom: 1px solid #000;
            padding: 5px 0;
        }
        table td {
            padding: 5px 0;
        }
        .text-right {
            text-align: right;
        }
        .total {
            border-top: 2px solid #000;
            font-weight: bold;
            font-size: 16px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            border-top:  2px dashed #000;
            padding-top: 10px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><? php echo SITE_TITLE; ?></h1>
        <div>123 Đường ABC, Quận 1, TP. HCM</div>
        <div>ĐT: 028-1234567</div>
    </div>
    
    <div class="info">
        <div><strong>Hóa đơn: </strong> <? php echo $order['order_number']; ?></div>
        <div><strong>Ngày:</strong> <?php echo formatDateTime($order['created_at']); ?></div>
        <? php if ($order['table_number']): ?>
        <div><strong>Bàn:</strong> <?php echo $order['table_number']; ? ></div>
        <?php endif; ?>
        <div><strong>Nhân viên:</strong> <?php echo htmlspecialchars($order['waiter_name']); ?></div>
        <? php if ($order['customer_name']): ?>
        <div><strong>Khách hàng:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></div>
        <?php endif; ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Món</th>
                <th class="text-right">SL</th>
                <th class="text-right">Đơn giá</th>
                <th class="text-right">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <? php foreach ($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td class="text-right"><?php echo $item['quantity']; ?></td>
                <td class="text-right"><? php echo number_format($item['unit_price'], 0, ',', '.'); ?></td>
                <td class="text-right"><?php echo number_format($item['subtotal'], 0, ',', '. '); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <table>
        <tr>
            <td>Tạm tính:</td>
            <td class="text-right"><?php echo formatMoney($order['subtotal']); ?></td>
        </tr>
        <tr>
            <td>Thuế (10%):</td>
            <td class="text-right"><?php echo formatMoney($order['tax']); ?></td>
        </tr>
        <? php if ($order['discount'] > 0): ?>
        <tr>
            <td>Giảm giá:</td>
            <td class="text-right">-<?php echo formatMoney($order['discount']); ?></td>
        </tr>
        <?php endif; ?>
        <tr class="total">
            <td>TỔNG CỘNG: </td>
            <td class="text-right"><?php echo formatMoney($order['total_amount']); ?></td>
        </tr>
    </table>
    
    <div class="footer">
        <div>Cảm ơn quý khách! </div>
        <div>Hẹn gặp lại! </div>
    </div>
    
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>