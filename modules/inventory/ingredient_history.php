<?php
$page_title = 'Lịch sử nhập kho';
require_once '../../config/config.php';
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();
requirePermission('manager');

$ingredient_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($ingredient_id <= 0) {
    setAlert('Thiếu mã nguyên liệu.', 'danger');
    header('Location: ingredients.php');
    exit();
}

// Lấy thông tin nguyên liệu
$ingredient = null;
try {
    $stmt = $db->prepare('SELECT * FROM ingredients WHERE ingredient_id = :id');
    $stmt->bindParam(':id', $ingredient_id);
    $stmt->execute();
    $ingredient = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $ingredient = null;
}

if (!$ingredient) {
    setAlert('Không tìm thấy nguyên liệu.', 'danger');
    header('Location: ingredients.php');
    exit();
}

// Lấy lịch sử giao dịch, hỗ trợ cả schema cũ và mới
$history = [];
try {
    $columns = $db->query('SHOW COLUMNS FROM inventory_transactions')->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('ingredient_id', $columns)) {
        $sql = "SELECT t.*, u.full_name AS user_name
                FROM inventory_transactions t
                LEFT JOIN users u ON t.performed_by = u.user_id
                WHERE t.ingredient_id = :id
                ORDER BY t.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $ingredient_id);
        $stmt->execute();
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (in_array('inventory_id', $columns)) {
        $sql = "SELECT t.*, u.full_name AS user_name
                FROM inventory_transactions t
                LEFT JOIN users u ON t.created_by = u.user_id
                WHERE t.inventory_id = :id
                ORDER BY t.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $ingredient_id); // best-effort mapping
        $stmt->execute();
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $history = [];
    setAlert('Không thể tải lịch sử: ' . $e->getMessage(), 'warning');
}

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Lịch sử nhập kho</h1>
            <div class="text-muted">Nguyên liệu: <strong><?php echo htmlspecialchars($ingredient['ingredient_name']); ?></strong></div>
        </div>
        <div class="d-flex gap-2">
            <a href="stock_in.php?id=<?php echo $ingredient_id; ?>" class="btn btn-primary">
                <i class="fas fa-arrow-down"></i> Nhập thêm
            </a>
            <a href="ingredients.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (empty($history)): ?>
                <div class="alert alert-info mb-0">Chưa có lịch sử nhập kho.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Loại</th>
                                <th>Số lượng</th>
                                <th>Đơn giá</th>
                                <th>Tổng</th>
                                <th>Ghi chú</th>
                                <th>Người thực hiện</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $row): ?>
                            <tr>
                                <td><?php echo formatDateTime($row['created_at']); ?></td>
                                <td>
                                    <?php
                                    $type = $row['transaction_type'] ?? 'in';
                                    $labels = ['in' => 'Nhập', 'out' => 'Xuất', 'adjust' => 'Điều chỉnh', 'purchase' => 'Mua'];
                                    echo $labels[$type] ?? $type;
                                    ?>
                                </td>
                                <td><?php echo number_format($row['quantity'], 2); ?></td>
                                <td><?php echo formatMoney($row['unit_price'] ?? $row['unit_cost'] ?? 0); ?></td>
                                <td><?php echo formatMoney($row['total_cost'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['user_name'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
