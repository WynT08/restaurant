<?php
$page_title = 'Quản lý chi phí';
include '../../includes/header.php';
requirePermission('manager');

// Ensure expenses table exists for fresh installs
try {
    $db->exec("CREATE TABLE IF NOT EXISTS expenses (
        expense_id INT AUTO_INCREMENT PRIMARY KEY,
        expense_type VARCHAR(50) NOT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        description TEXT,
        expense_date DATE NOT NULL,
        payment_method VARCHAR(50) DEFAULT NULL,
        receipt_image VARCHAR(255) DEFAULT NULL,
        recorded_by INT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_expenses_user FOREIGN KEY (recorded_by) REFERENCES users(user_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {
    setAlert('Không thể tạo bảng expenses: ' . $e->getMessage(), 'danger');
}

// Handle delete
if (isset($_GET['delete'])) {
    $expense_id = $_GET['delete'];
    $query = "DELETE FROM expenses WHERE expense_id = :expense_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':expense_id', $expense_id);
    
    if ($stmt->execute()) {
        setAlert('Xóa chi phí thành công', 'success');
    }
    
    header("Location: expense_list.php");
    exit();
}

// Get date filter
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build query for list (respect filters) and compute grand total across all types within date range
// Schema seed không có cột recorded_by, nên bỏ JOIN và default tên ghi nhận
// Guard missing expenses table
try {
    $query = "SELECT e.*, NULL as recorded_by_name
              FROM expenses e
              WHERE DATE(e.expense_date) BETWEEN :from_date AND :to_date";

    if ($type_filter != 'all') {
        $query .= " AND e.expense_type = :type";
    }

    $query .= " ORDER BY e.expense_date DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':from_date', $from_date);
    $stmt->bindParam(':to_date', $to_date);

    if ($type_filter != 'all') {
        $stmt->bindParam(':type', $type_filter);
    }

    $stmt->execute();
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Grand total should cover all types in the date range, regardless of type filter
    $totalStmt = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE(expense_date) BETWEEN :from_date AND :to_date");
    $totalStmt->bindParam(':from_date', $from_date);
    $totalStmt->bindParam(':to_date', $to_date);
    $totalStmt->execute();
    $grand_total = (float) $totalStmt->fetchColumn();
} catch (Exception $e) {
    $expenses = [];
    $grand_total = 0;
    setAlert('Bảng expenses chưa tồn tại. Vui lòng import schema hoặc tạo bảng.', 'warning');
}

// Calculate totals by type for the displayed list (still respects type filter)
$totals_by_type = [];
foreach ($expenses as $expense) {
    $type = $expense['expense_type'];
    if (!isset($totals_by_type[$type])) {
        $totals_by_type[$type] = 0;
    }
    $totals_by_type[$type] += $expense['amount'];
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Quản lý chi phí</h1>
        <a href="add_expense.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Thêm chi phí
        </a>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Loại chi phí</label>
                    <select name="type" class="form-select">
                        <option value="all">Tất cả</option>
                        <option value="salary" <?php echo $type_filter == 'salary' ? 'selected' : ''; ?>>Lương</option>
                        <option value="utilities" <?php echo $type_filter == 'utilities' ? 'selected' : ''; ?>>Điện nước</option>
                        <option value="rent" <?php echo $type_filter == 'rent' ? 'selected' : ''; ?>>Thuê mặt bằng</option>
                        <option value="maintenance" <?php echo $type_filter == 'maintenance' ? 'selected' : ''; ?>>Bảo trì</option>
                        <option value="supplies" <?php echo $type_filter == 'supplies' ? 'selected' : ''; ?>>Vật tư</option>
                        <option value="marketing" <?php echo $type_filter == 'marketing' ? 'selected' : ''; ?>>Marketing</option>
                        <option value="other" <?php echo $type_filter == 'other' ? 'selected' : ''; ?>>Khác</option>
                    </select>
                </div>
                <div class="col-md-3">
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
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h4 class="text-danger mb-0"><?php echo formatMoney($grand_total); ?></h4>
                    <small class="text-muted">Tổng chi phí</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h5 class="text-primary mb-0"><?php echo formatMoney($totals_by_type['salary'] ?? 0); ?></h5>
                    <small class="text-muted">Lương</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h5 class="text-warning mb-0"><?php echo formatMoney($totals_by_type['utilities'] ?? 0); ?></h5>
                    <small class="text-muted">Điện nước</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h5 class="text-info mb-0"><?php echo formatMoney($totals_by_type['rent'] ?? 0); ?></h5>
                    <small class="text-muted">Thuê MB</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Expenses List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Loại chi phí</th>
                            <th>Mô tả</th>
                            <th>Số tiền</th>
                            <th>Phương thức</th>
                            <th>Người ghi</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?php echo formatDate($expense['expense_date']); ?></td>
                            <td>
                                <?php
                                $type_labels = [
                                    'salary' => ['primary', 'Lương'],
                                    'utilities' => ['warning', 'Điện nước'],
                                    'rent' => ['info', 'Thuê MB'],
                                    'maintenance' => ['secondary', 'Bảo trì'],
                                    'supplies' => ['success', 'Vật tư'],
                                    'marketing' => ['danger', 'Marketing'],
                                    'other' => ['dark', 'Khác']
                                ];
                                $label = $type_labels[$expense['expense_type']];
                                ?>
                                <span class="badge bg-<?php echo $label[0]; ?>"><?php echo $label[1]; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($expense['description']); ?></td>
                            <td><strong class="text-danger"><?php echo formatMoney($expense['amount']); ?></strong></td>
                            <td>
                                <?php
                                $payment_icons = [
                                    'cash' => 'fa-money-bill',
                                    'card' => 'fa-credit-card',
                                    'bank_transfer' => 'fa-exchange-alt'
                                ];
                                ?>
                                <i class="fas <?php echo $payment_icons[$expense['payment_method']]; ?>"></i>
                                <?php echo ucfirst($expense['payment_method']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($expense['recorded_by_name'] ?? ''); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($expense['receipt_image']): ?>
                                    <a href="<?php echo SITE_URL . '/uploads/receipts/' . $expense['receipt_image']; ?>" 
                                       target="_blank" class="btn btn-info" title="Xem hóa đơn">
                                        <i class="fas fa-file-image"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="edit_expense.php?id=<?php echo $expense['expense_id']; ?>" 
                                       class="btn btn-primary" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $expense['expense_id']; ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('Xóa chi phí này?')" 
                                       title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-danger">
                        <tr>
                            <th colspan="3" class="text-end">Tổng cộng: </th>
                            <th colspan="4"><strong><?php echo formatMoney($grand_total); ?></strong></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>