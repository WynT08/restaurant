<?php
$page_title = 'Lịch đặt bàn';
include '../../includes/header.php';

// Month filter (YYYY-MM)
$monthParam = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    $monthParam = date('Y-m');
}
$startDate = $monthParam . '-01';
$endDate = date('Y-m-t', strtotime($startDate));

// Fetch reservations for the month
$query = "SELECT r.*, rt.table_number
          FROM reservations r
          LEFT JOIN restaurant_tables rt ON r.table_id = rt.table_id
          WHERE r.reservation_date BETWEEN :start AND :end
          ORDER BY r.reservation_date, r.reservation_time";
$stmt = $db->prepare($query);
$stmt->bindParam(':start', $startDate);
$stmt->bindParam(':end', $endDate);
$stmt->execute();
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by date
$byDate = [];
foreach ($reservations as $res) {
    $byDate[$res['reservation_date']][] = $res;
}

$statusClasses = [
    'pending' => 'warning',
    'confirmed' => 'primary',
    'completed' => 'success',
    'cancelled' => 'secondary',
    'no_show' => 'dark'
];
$statusText = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy',
    'no_show' => 'Không đến'
];
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Lịch đặt bàn</h1>
        <div class="d-flex gap-2">
            <a href="list.php" class="btn btn-secondary"><i class="fas fa-list"></i> Danh sách</a>
            <a href="create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Đặt bàn mới</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <div>
                <label class="form-label mb-1">Chọn tháng</label>
                <form method="get" class="d-flex align-items-center gap-2">
                    <input type="month" name="month" value="<?php echo htmlspecialchars($monthParam); ?>" class="form-control" style="width: 200px;">
                    <button class="btn btn-outline-primary" type="submit"><i class="fas fa-calendar"></i> Xem</button>
                </form>
            </div>
            <div class="ms-auto text-muted">
                Khoảng: <?php echo date('d/m/Y', strtotime($startDate)); ?> - <?php echo date('d/m/Y', strtotime($endDate)); ?>
            </div>
        </div>
    </div>

    <?php if (empty($byDate)): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle"></i> Không có đặt bàn nào trong tháng này.
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($byDate as $date => $items): ?>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <strong><i class="fas fa-calendar-day"></i> <?php echo date('d/m/Y', strtotime($date)); ?></strong>
                        <span class="badge bg-secondary"><?php echo count($items); ?> lịch hẹn</span>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($items as $res): ?>
                        <?php
                            $cls = $statusClasses[$res['status']] ?? 'secondary';
                            $txt = $statusText[$res['status']] ?? ucfirst($res['status']);
                        ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($res['customer_name']); ?> (<?php echo htmlspecialchars($res['customer_phone']); ?>)</div>
                                    <div class="text-muted small">
                                        Bàn: <?php echo $res['table_number'] ? htmlspecialchars($res['table_number']) : 'Chưa chọn'; ?> · Khách: <?php echo (int) $res['number_of_guests']; ?>
                                    </div>
                                    <div class="text-muted small">Ghi chú: <?php echo htmlspecialchars($res['special_requests'] ?: 'Không'); ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold"><i class="fas fa-clock"></i> <?php echo htmlspecialchars(substr($res['reservation_time'], 0, 5)); ?></div>
                                    <span class="badge bg-<?php echo $cls; ?>"><?php echo $txt; ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
