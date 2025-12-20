<?php
$page_title = 'Kitchen Display System';
include '../../includes/header.php';

// Get pending and preparing orders
$query = "SELECT o.*, rt.table_number, u.full_name as waiter_name
          FROM orders o
          LEFT JOIN restaurant_tables rt ON o. table_id = rt.table_id
          LEFT JOIN users u ON o.waiter_id = u.user_id
          WHERE o.order_status IN ('pending', 'preparing', 'confirmed')
          ORDER BY o.created_at ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$orders = $stmt->fetchAll(PDO:: FETCH_ASSOC);
?>

<div class="kitchen-display">
    <div class="kitchen-header">
        <h2><i class="fas fa-fire"></i> Màn hình bếp</h2>
        <div class="clock" id="clock"></div>
    </div>
    
    <div class="orders-board">
        <? php foreach ($orders as $order): ?>
            <? php
            // Get order items
            $query = "SELECT oi.*, mi.item_name 
                      FROM order_items oi
                      JOIN menu_items mi ON oi.item_id = mi.item_id
                      WHERE oi.order_id = :order_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':order_id', $order['order_id']);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate elapsed time
            $created_time = strtotime($order['created_at']);
            $elapsed_minutes = floor((time() - $created_time) / 60);
            
            $urgency_class = '';
            if ($elapsed_minutes > 30) {
                $urgency_class = 'urgent';
            } elseif ($elapsed_minutes > 15) {
                $urgency_class = 'warning';
            }
            ?>
            
            <div class="order-card <? php echo $urgency_class; ? >" data-order-id="<?php echo $order['order_id']; ?>">
                <div class="order-card-header">
                    <div>
                        <h4><?php echo $order['order_number']; ? ></h4>
                        <p class="mb-0">
                            <? php if ($order['table_number']): ?>
                                <i class="fas fa-table"></i> Bàn <?php echo $order['table_number']; ?>
                            <?php else: ?>
                                <i class="fas fa-shopping-bag"></i> Mang về
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="order-time">
                        <div class="elapsed-time"><?php echo $elapsed_minutes; ? > phút</div>
                        <small><? php echo formatDateTime($order['created_at']); ?></small>
                    </div>
                </div>
                
                <div class="order-card-body">
                    <ul class="order-items-list">
                        <?php foreach ($items as $item): ?>
                        <li class="order-item" data-item-id="<?php echo $item['order_item_id']; ?>">
                            <span class="item-quantity"><?php echo $item['quantity']; ?>x</span>
                            <span class="item-name"><? php echo htmlspecialchars($item['item_name']); ?></span>
                            <? php if ($item['special_instructions']): ?>
                            <small class="text-warning d-block">
                                <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($item['special_instructions']); ?>
                            </small>
                            <?php endif; ?>
                            <span class="item-status">
                                <?php if ($item['item_status'] == 'ready'): ?>
                                    <i class="fas fa-check-circle text-success"></i>
                                <?php endif; ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="order-card-footer">
                    <? php if ($order['order_status'] == 'pending'): ?>
                        <button class="btn btn-primary btn-sm btn-confirm" data-order-id="<?php echo $order['order_id']; ?>">
                            <i class="fas fa-check"></i> Xác nhận
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($order['order_status'] == 'preparing'): ?>
                        <button class="btn btn-success btn-sm btn-ready" data-order-id="<?php echo $order['order_id']; ?>">
                            <i class="fas fa-check-double"></i> Sẵn sàng
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <? php if (count($orders) == 0): ?>
            <div class="no-orders">
                <i class="fas fa-check-circle"></i>
                <h3>Không có đơn hàng</h3>
                <p>Tất cả đơn đã hoàn thành</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto refresh every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);

// Update clock
function updateClock() {
    const now = new Date();
    document.getElementById('clock').textContent = now.toLocaleTimeString('vi-VN');
}
setInterval(updateClock, 1000);
updateClock();

// Confirm order
$('.btn-confirm').click(function() {
    const orderId = $(this).data('order-id');
    if (confirm('Xác nhận đơn hàng này?')) {
        $.post('ajax_update_order_status.php', {
            order_id: orderId,
            status: 'preparing'
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        }, 'json');
    }
});

// Mark as ready
$('.btn-ready').click(function() {
    const orderId = $(this).data('order-id');
    if (confirm('Đánh dấu đơn hàng đã sẵn sàng?')) {
        $.post('ajax_update_order_status. php', {
            order_id: orderId,
            status:  'ready'
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        }, 'json');
    }
});
</script>

<?php include '../../includes/footer. php'; ?>