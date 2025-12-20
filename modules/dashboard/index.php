<?php
$page_title = 'Dashboard';
include '../../includes/header.php';

// Get statistics
$stats = [];

// Today's revenue
$query = "SELECT COALESCE(SUM(total_amount), 0) as today_revenue 
          FROM orders 
          WHERE DATE(created_at) = CURDATE() 
          AND payment_status = 'paid'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['today_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['today_revenue'];

// Today's orders
$query = "SELECT COUNT(*) as today_orders 
          FROM orders 
          WHERE DATE(created_at) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['today_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['today_orders'];

// Available tables
$query = "SELECT COUNT(*) as available_tables 
          FROM restaurant_tables 
          WHERE status = 'available'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['available_tables'] = $stmt->fetch(PDO:: FETCH_ASSOC)['available_tables'];

// Pending reservations
$query = "SELECT COUNT(*) as pending_reservations 
          FROM reservations 
          WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pending_reservations'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_reservations'];

// Recent orders
$query = "SELECT o.*, rt.table_number, u.full_name as waiter_name
          FROM orders o
          LEFT JOIN restaurant_tables rt ON o.table_id = rt.table_id
          LEFT JOIN users u ON o.user_id = u.user_id
          ORDER BY o.created_at DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Low stock alerts (guard missing table)
try {
    $query = "SELECT * FROM ingredients 
              WHERE current_stock <= reorder_level 
              ORDER BY current_stock ASC 
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $low_stock = [];
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Dashboard</h1>
        <div>
            <span class="text-muted">
                <i class="fas fa-calendar"></i>
                <?php echo date('d/m/Y'); ?>
            </span>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Doanh thu hôm nay</p>
                            <h3 class="mb-0"><?php echo formatMoney($stats['today_revenue']); ?></h3>
                        </div>
                        <div class="icon-box bg-primary">
                            <i class="fas fa-dollar-sign text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Đơn hàng hôm nay</p>
                            <h3 class="mb-0"><?php echo $stats['today_orders']; ?></h3>
                        </div>
                        <div class="icon-box bg-success">
                            <i class="fas fa-receipt text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Bàn trống</p>
                            <h3 class="mb-0"><?php echo $stats['available_tables']; ?></h3>
                        </div>
                        <div class="icon-box bg-info">
                            <i class="fas fa-table text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Đặt bàn chờ</p>
                            <h3 class="mb-0"><?php echo $stats['pending_reservations']; ?></h3>
                        </div>
                        <div class="icon-box bg-warning">
                            <i class="fas fa-calendar-check text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Orders -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Đơn hàng gần đây</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Bàn</th>
                                    <th>Nhân viên</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Thời gian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><strong><?php echo $order['order_number']; ?></strong></td>
                                    <td><?php echo $order['table_number'] ?? 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($order['waiter_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo formatMoney($order['total_amount']); ?></td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'pending' => 'warning',
                                            'preparing' => 'primary',
                                            'served' => 'success',
                                            'cancelled' => 'secondary'
                                        ];
                                        $status_text = [
                                            'pending' => 'Chờ xử lý',
                                            'preparing' => 'Đang làm',
                                            'served' => 'Đã phục vụ',
                                            'cancelled' => 'Đã hủy'
                                        ];
                                        $order_status = $order['order_status'];
                                        $badge_class = $status_class[$order_status] ?? 'secondary';
                                        $badge_text = $status_text[$order_status] ?? ucfirst($order_status);
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class; ?>">
                                            <?php echo $badge_text; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDateTime($order['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Low Stock Alerts -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Cảnh báo tồn kho</h5>
                </div>
                <div class="card-body">
                    <?php if (count($low_stock) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($low_stock as $item): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['ingredient_name']); ?></h6>
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Còn <?php echo $item['current_stock']; ?> <?php echo $item['unit']; ?>
                                        </small>
                                    </div>
                                    <a href="<?php echo SITE_URL; ?>/modules/inventory/stock_in.php?id=<?php echo $item['ingredient_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        Nhập kho
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">
                            <i class="fas fa-check-circle text-success"></i>
                            Tất cả nguyên liệu đều đủ
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.icon-box {
    width: 60px;
    height: 60px;
    border-radius:  10px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.icon-box i {
    font-size: 24px;
}
</style>

<?php include '../../includes/footer.php'; ?>