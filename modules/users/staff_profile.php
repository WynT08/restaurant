<?php
$page_title = 'Hồ sơ nhân viên';
include '../../includes/header.php';

$user_id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['user_id'];

// Get user info
$query = "SELECT * FROM users WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO:: FETCH_ASSOC);

if (!$user) {
    header("Location: manage_staff.php");
    exit();
}

// Get user statistics
$stats = [];

// Total orders served
$query = "SELECT COUNT(*) as total_orders, 
          COALESCE(SUM(total_amount), 0) as total_revenue
          FROM orders 
          WHERE waiter_id = :user_id 
          AND payment_status = 'paid'";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$order_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_orders'] = $order_stats['total_orders'];
$stats['total_revenue'] = $order_stats['total_revenue'];

// Recent activity
$query = "SELECT * FROM activity_logs 
          WHERE user_id = :user_id 
          ORDER BY created_at DESC 
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$activities = $stmt->fetchAll(PDO:: FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Hồ sơ nhân viên</h1>
        <div>
            <?php if (hasPermission('admin') || $user_id == $_SESSION['user_id']): ?>
            <a href="edit_staff.php?id=<?php echo $user_id; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Chỉnh sửa
            </a>
            <?php endif; ?>
            <a href="manage_staff.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Profile Info -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <?php if ($user['avatar']): ?>
                        <img src="<?php echo SITE_URL . '/uploads/avatars/' . $user['avatar']; ?>" 
                             class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" 
                             style="width: 150px; height: 150px; font-size: 60px; font-weight: bold;">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                    <p class="text-muted">@<?php echo htmlspecialchars($user['username'] ?? ($user['email'] ?? '')); ?></p>
                    
                    <?php
                    $role_badges = [
                        'admin' => ['danger', 'Admin'],
                        'manager' => ['primary', 'Manager'],
                        'waiter' => ['success', 'Phục vụ'],
                        'chef' => ['warning', 'Đầu bếp'],
                        'cashier' => ['info', 'Thu ngân'],
                        'staff' => ['secondary', 'Staff']
                    ];
                    $role_key = $user['user_role'] ?? 'staff';
                    $badge = $role_badges[$role_key] ?? ['secondary', ucfirst($role_key)];
                    ?>
                    <span class="badge bg-<?php echo $badge[0]; ?> mb-3"><?php echo $badge[1]; ?></span>
                    
                    <hr>
                    
                    <div class="text-start">
                        <p><i class="fas fa-envelope text-muted me-2"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><i class="fas fa-phone text-muted me-2"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                        <p><i class="fas fa-calendar text-muted me-2"></i> Tham gia: <?php echo formatDate($user['created_at']); ?></p>
                        <p>
                            <i class="fas fa-circle text-muted me-2"></i> 
                            <?php if ($user['status'] == 'active'): ?>
                                <span class="badge bg-success">Đang làm việc</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nghỉ việc</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics & Activity -->
        <div class="col-lg-8 mb-4">
            <!-- Statistics -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Tổng đơn hàng</p>
                                    <h3 class="mb-0 text-primary"><?php echo $stats['total_orders']; ?></h3>
                                </div>
                                <i class="fas fa-receipt fa-2x text-primary opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card border-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Tổng doanh thu</p>
                                    <h3 class="mb-0 text-success"><?php echo formatMoney($stats['total_revenue']); ?></h3>
                                </div>
                                <i class="fas fa-dollar-sign fa-2x text-success opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Hoạt động gần đây</h5>
                </div>
                <div class="card-body">
                    <?php if (count($activities) > 0): ?>
                    <div class="timeline">
                        <?php foreach ($activities as $activity): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <p class="mb-1">
                                    <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                                </p>
                                <p class="text-muted mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> <?php echo formatDateTime($activity['created_at']); ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-center text-muted">Chưa có hoạt động nào</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
    border-left: 2px solid #e9ecef;
}

. timeline-item:last-child {
    border-left: none;
}

.timeline-marker {
    position: absolute;
    left: -6px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #667eea;
    border: 2px solid white;
}

.timeline-content {
    padding-left: 20px;
}
</style>

<?php include '../../includes/footer.php'; ?>