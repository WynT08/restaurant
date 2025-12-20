<?php
$page_title = 'Quản lý đặt bàn';
include '../../includes/header.php';

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Build query
$query = "SELECT r.*, rt.table_number
          FROM reservations r
          LEFT JOIN restaurant_tables rt ON r.table_id = rt.table_id
          WHERE DATE(r.reservation_date) = :date";

if ($status_filter != 'all') {
    $query .= " AND r.status = :status";
}

$query .= " ORDER BY r.reservation_time ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':date', $date_filter);
if ($status_filter != 'all') {
    $stmt->bindParam(':status', $status_filter);
}
$stmt->execute();
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Quản lý đặt bàn</h1>
        <div>
            <a href="calendar.php" class="btn btn-info">
                <i class="fas fa-calendar"></i> Xem lịch
            </a>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Đặt bàn mới
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <label class="form-label">Ngày</label>
                          <input type="date" id="date-filter" class="form-control" 
                              value="<?php echo $date_filter; ?>" 
                              onchange="filterReservations()">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Trạng thái</label>
                    <select id="status-filter" class="form-select" onchange="filterReservations()">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>Tất cả</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                        <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' :  ''; ?>>Đã hủy</option>
                        <option value="no_show" <?php echo $status_filter == 'no_show' ? 'selected' : ''; ?>>Không đến</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="text" id="search-reservation" class="form-control" 
                           placeholder="Tên khách, số điện thoại... ">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="row g-3 mb-4">
        <?php
        $stats = [
            'total' => 0,
            'pending' => 0,
            'confirmed' => 0,
            'completed' => 0
        ];
        foreach ($reservations as $res) {
            $stats['total']++;
            if ($res['status'] == 'pending') $stats['pending']++;
            if ($res['status'] == 'confirmed') $stats['confirmed']++;
            if ($res['status'] == 'completed') $stats['completed']++;
        }
        ?>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                    <p class="text-muted mb-0">Tổng đặt bàn</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <h2 class="mb-0 text-warning"><?php echo $stats['pending']; ?></h2>
                    <p class="text-muted mb-0">Chờ xác nhận</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h2 class="mb-0 text-success"><?php echo $stats['confirmed']; ?></h2>
                    <p class="text-muted mb-0">Đã xác nhận</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-info">
                <div class="card-body">
                    <h2 class="mb-0 text-info"><?php echo $stats['completed']; ?></h2>
                    <p class="text-muted mb-0">Hoàn thành</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reservations List -->
    <div class="card">
        <div class="card-body">
            <?php if (count($reservations) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover" id="reservations-table">
                    <thead>
                        <tr>
                            <th>Mã đặt bàn</th>
                            <th>Khách hàng</th>
                            <th>Liên hệ</th>
                            <th>Bàn</th>
                            <th>Ngày giờ</th>
                            <th>Số khách</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $res): ?>
                        <tr>
                            <td><strong>#<?php echo $res['reservation_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($res['customer_name']); ?></td>
                            <td>
                                <i class="fas fa-phone"></i> <?php echo $res['customer_phone']; ?><br>
                                <?php if ($res['customer_email']): ?>
                                <small class="text-muted">
                                    <i class="fas fa-envelope"></i> <?php echo $res['customer_email']; ?>
                                </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($res['table_number']): ?>
                                    <span class="badge bg-info"><?php echo $res['table_number']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Chưa chọn</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo formatDate($res['reservation_date']); ?><br>
                                <strong><?php echo date('H:i', strtotime($res['reservation_time'])); ?></strong>
                            </td>
                            <td>
                                <i class="fas fa-users"></i> <?php echo $res['number_of_guests']; ?> người
                            </td>
                            <td>
                                <?php
                                $status_badges = [
                                    'pending' => ['warning', 'Chờ xác nhận'],
                                    'confirmed' => ['success', 'Đã xác nhận'],
                                    'cancelled' => ['danger', 'Đã hủy'],
                                    'completed' => ['info', 'Hoàn thành'],
                                    'no_show' => ['secondary', 'Không đến']
                                ];
                                $badge = $status_badges[$res['status']];
                                ?>
                                <span class="badge bg-<?php echo $badge[0]; ?>">
                                    <?php echo $badge[1]; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                        <?php if ($res['status'] == 'pending'): ?>
                                    <button class="btn btn-success" 
                                            onclick="updateStatus(<?php echo $res['reservation_id']; ?>, 'confirmed')"
                                            title="Xác nhận">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-danger" 
                                            onclick="updateStatus(<?php echo $res['reservation_id']; ?>, 'cancelled')"
                                            title="Hủy">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($res['status'] == 'confirmed'): ?>
                                    <button class="btn btn-info" 
                                            onclick="updateStatus(<?php echo $res['reservation_id']; ?>, 'completed')"
                                            title="Hoàn thành">
                                        <i class="fas fa-check-double"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <a href="edit.php?id=<?php echo $res['reservation_id']; ?>" 
                                       class="btn btn-primary" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <button class="btn btn-secondary" 
                                            onclick="viewDetails(<?php echo $res['reservation_id']; ?>)"
                                            title="Chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h5>Không có đặt bàn nào</h5>
                <p class="text-muted">Chọn ngày khác hoặc thêm đặt bàn mới</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function filterReservations() {
    const date = $('#date-filter').val();
    const status = $('#status-filter').val();
    window.location.href = `list.php?date=${date}&status=${status}`;
}

function updateStatus(reservationId, status) {
    const messages = {
        'confirmed': 'Xác nhận đặt bàn này? ',
        'cancelled': 'Hủy đặt bàn này?',
        'completed': 'Đánh dấu đã hoàn thành?',
        'no_show': 'Đánh dấu khách không đến?'
    };
    
    if (confirm(messages[status])) {
        $.post('ajax_update_reservation.php', {
            reservation_id: reservationId,
            status: status
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.message);
            }
        }, 'json');
    }
}

function viewDetails(reservationId) {
    // Open modal or redirect to details page
    window.open('view_reservation.php?id=' + reservationId, '_blank');
}

// Search
$('#search-reservation').on('keyup', function() {
    const value = $(this).val().toLowerCase();
    $('#reservations-table tbody tr').filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
});
</script>

<?php include '../../includes/footer.php'; ?>