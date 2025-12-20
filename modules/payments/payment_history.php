<?php
$page_title = 'Lịch sử thanh toán';
include '../../includes/header.php';
requirePermission('manager');

// Get date filter
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] :  date('Y-m-d');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] :  date('Y-m-d');

// Get payments
$query = "SELECT p.*, o.order_number, u.full_name as cashier_name
          FROM payments p
          JOIN orders o ON p. order_id = o.order_id
          LEFT JOIN users u ON p.cashier_id = u. user_id
          WHERE DATE(p.payment_date) BETWEEN : from_date AND :to_date
          ORDER BY p.payment_date DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':from_date', $from_date);
$stmt->bindParam(':to_date', $to_date);
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_amount = 0;
$totals_by_method = [];

foreach ($payments as $payment) {
    $total_amount += $payment['amount'];
    $method = $payment['payment_method'];
    if (!isset($totals_by_method[$method])) {
        $totals_by_method[$method] = 0;
    }
    $totals_by_method[$method] += $payment['amount'];
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Lịch sử thanh toán</h1>
        <button class="btn btn-success" onclick="exportToExcel()">
            <i class="fas fa-file-excel"></i> Xuất Excel
        </button>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h4 class="text-success mb-0"><?php echo formatMoney($total_amount); ?></h4>
                    <small class="text-muted">Tổng thu</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="mb-0"><?php echo formatMoney($totals_by_method['cash'] ?? 0); ?></h5>
                    <small class="text-muted">Tiền mặt</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="mb-0"><?php echo formatMoney($totals_by_method['card'] ?? 0); ?></h5>
                    <small class="text-muted">Thẻ</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="mb-0"><?php echo formatMoney($totals_by_method['momo'] ?? 0); ?></h5>
                    <small class="text-muted">MoMo</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Mã thanh toán</th>
                            <th>Đơn hàng</th>
                            <th>Số tiền</th>
                            <th>Phương thức</th>
                            <th>Thu ngân</th>
                            <th>Thời gian</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><strong>#<?php echo $payment['payment_id']; ?></strong></td>
                            <td><?php echo $payment['order_number']; ? ></td>
                            <td><strong class="text-success"><?php echo formatMoney($payment['amount']); ?></strong></td>
                            <td>
                                <? php
                                $method_labels = [
                                    'cash' => ['success', 'Tiền mặt'],
                                    'card' => ['primary', 'Thẻ'],
                                    'momo' => ['danger', 'MoMo'],
                                    'bank_transfer' => ['info', 'CK']
                                ];
                                $label = $method_labels[$payment['payment_method']];
                                ?>
                                <span class="badge bg-<?php echo $label[0]; ? >"><?php echo $label[1]; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($payment['cashier_name']); ?></td>
                            <td><?php echo formatDateTime($payment['payment_date']); ?></td>
                            <td><?php echo htmlspecialchars($payment['notes']); ?></td>
                        </tr>
                        <? php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>