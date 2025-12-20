<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$reservation_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($reservation_id <= 0) {
    header('Location: list.php');
    exit();
}

$stmt = $db->prepare("SELECT r.*, rt.table_number, rt.location, rt.capacity FROM reservations r
    LEFT JOIN restaurant_tables rt ON r.table_id = rt.table_id
    WHERE r.reservation_id = :id LIMIT 1");
$stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT);
$stmt->execute();
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    setAlert('Không tìm thấy đặt bàn', 'danger');
    header('Location: list.php');
    exit();
}

$page_title = 'Chi tiết đặt bàn #' . $reservation_id;
$status_labels = [
    'pending' => ['warning', 'Chờ xác nhận'],
    'confirmed' => ['success', 'Đã xác nhận'],
    'cancelled' => ['danger', 'Đã hủy'],
    'completed' => ['info', 'Hoàn thành'],
    'no_show' => ['secondary', 'Không đến']
];
$badge = $status_labels[$reservation['status']] ?? ['secondary', ucfirst($reservation['status'])];

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Chi tiết đặt bàn #<?php echo $reservation_id; ?></h1>
        <div class="d-flex gap-2">
            <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Danh sách</a>
            <a href="edit.php?id=<?php echo $reservation_id; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Sửa</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-<?php echo $badge[0]; ?>"><?php echo $badge[1]; ?></span>
                        <span class="ms-2 text-muted">Đặt lúc: <?php echo formatDateTime($reservation['created_at']); ?></span>
                    </div>
                    <div class="text-muted">Mã: #<?php echo $reservation_id; ?></div>
                </div>
                <div class="card-body">
                    <h5 class="mb-3">Khách hàng</h5>
                    <p class="mb-1"><strong><?php echo htmlspecialchars($reservation['customer_name']); ?></strong></p>
                    <p class="mb-1"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($reservation['customer_phone']); ?></p>
                    <p class="mb-3"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($reservation['customer_email'] ?? ''); ?></p>

                    <h5 class="mb-3">Thông tin bàn</h5>
                    <p class="mb-1">Bàn: <?php echo $reservation['table_number'] ? htmlspecialchars($reservation['table_number']) : 'Chưa chọn'; ?></p>
                    <p class="mb-1">Vị trí: <?php echo $reservation['location'] ? ucfirst($reservation['location']) : 'N/A'; ?></p>
                    <p class="mb-3">Sức chứa: <?php echo $reservation['capacity'] ?? 'N/A'; ?></p>

                    <h5 class="mb-3">Thời gian</h5>
                    <p class="mb-1">Ngày: <?php echo formatDate($reservation['reservation_date']); ?></p>
                    <p class="mb-3">Giờ: <?php echo date('H:i', strtotime($reservation['reservation_time'])); ?></p>

                    <h5 class="mb-3">Chi tiết khác</h5>
                    <p class="mb-1">Số khách: <?php echo (int) $reservation['number_of_guests']; ?></p>
                    <p class="mb-0">Ghi chú: <?php echo htmlspecialchars($reservation['special_requests'] ?: 'Không'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white">Hành động</div>
                <div class="card-body d-grid gap-2">
                    <?php if ($reservation['status'] === 'pending'): ?>
                    <button class="btn btn-success" onclick="updateStatus('confirmed')"><i class="fas fa-check"></i> Xác nhận</button>
                    <button class="btn btn-danger" onclick="updateStatus('cancelled')"><i class="fas fa-times"></i> Hủy</button>
                    <?php endif; ?>
                    <?php if ($reservation['status'] === 'confirmed'): ?>
                    <button class="btn btn-info" onclick="updateStatus('completed')"><i class="fas fa-check-double"></i> Hoàn thành</button>
                    <button class="btn btn-secondary" onclick="updateStatus('no_show')"><i class="fas fa-user-slash"></i> Không đến</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(status) {
    const messages = {
        confirmed: 'Xác nhận đặt bàn này?',
        cancelled: 'Hủy đặt bàn này?',
        completed: 'Đánh dấu đã hoàn thành?',
        no_show: 'Đánh dấu khách không đến?'
    };
    if (!confirm(messages[status] || 'Cập nhật trạng thái?')) return;
    $.post('ajax_update_reservation.php', {
        reservation_id: <?php echo $reservation_id; ?>,
        status: status
    }, function(res) {
        if (res.success) {
            location.reload();
        } else {
            alert(res.message || 'Cập nhật thất bại');
        }
    }, 'json');
}
</script>

<?php include '../../includes/footer.php'; ?>
