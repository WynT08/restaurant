<?php
$page_title = 'Quản lý nguyên liệu';
include '../../includes/header.php';
requirePermission('manager');

// Ensure ingredients table exists (app uses this table)
try {
    $db->exec("CREATE TABLE IF NOT EXISTS ingredients (
        ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
        ingredient_name VARCHAR(100) NOT NULL,
        unit VARCHAR(20) NOT NULL,
        current_stock DECIMAL(10,2) NOT NULL DEFAULT 0,
        reorder_level DECIMAL(10,2) DEFAULT 0,
        cost_price DECIMAL(10,2) DEFAULT 0,
        supplier_name VARCHAR(100) DEFAULT NULL,
        supplier_phone VARCHAR(50) DEFAULT NULL,
        last_restocked DATETIME DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {
    setAlert('Không thể tạo bảng ingredients: ' . $e->getMessage(), 'danger');
}

// Handle add ingredient
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    try {
        // Schema dùng reorder_level thay vì min_stock; cost_price là giá đơn vị
        $query = "INSERT INTO ingredients (
            ingredient_name, unit, current_stock, reorder_level, 
            cost_price, supplier_name, supplier_phone
        ) VALUES (
            :name, :unit, :current_stock, :reorder_level,
            :cost_price, :supplier_name, :supplier_phone
        )";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $_POST['ingredient_name']);
        $stmt->bindParam(':unit', $_POST['unit']);
        $stmt->bindParam(':current_stock', $_POST['current_stock']);
        $stmt->bindParam(':reorder_level', $_POST['min_stock']);
        $stmt->bindParam(':cost_price', $_POST['unit_price']);
        $stmt->bindParam(':supplier_name', $_POST['supplier_name']);
        $stmt->bindParam(':supplier_phone', $_POST['supplier_phone']);
        
        if ($stmt->execute()) {
            setAlert('Thêm nguyên liệu thành công', 'success');
        }
    } catch (Exception $e) {
        setAlert('Có lỗi xảy ra: ' . $e->getMessage(), 'danger');
    }
}

// Handle delete ingredient
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $delete_id = (int)($_POST['ingredient_id'] ?? 0);
    if ($delete_id > 0) {
        try {
            $stmt = $db->prepare("DELETE FROM ingredients WHERE ingredient_id = :id");
            $stmt->bindParam(':id', $delete_id);
            $stmt->execute();
            setAlert('Xóa nguyên liệu thành công', 'success');
        } catch (Exception $e) {
            setAlert('Không thể xóa nguyên liệu: ' . $e->getMessage(), 'danger');
        }
    } else {
        setAlert('Thiếu mã nguyên liệu để xóa', 'warning');
    }
}

// Get all ingredients (guard missing table)
try {
    $query = "SELECT * FROM ingredients ORDER BY ingredient_name";
    $ingredients = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $ingredients = [];
    setAlert('Bảng ingredients chưa tồn tại. Vui lòng import schema hoặc tạo bảng.', 'warning');
}

// Count low stock items using reorder_level
$low_stock_count = 0;
foreach ($ingredients as $item) {
    if ($item['current_stock'] <= ($item['reorder_level'] ?? 0)) {
        $low_stock_count++;
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3">Quản lý nguyên liệu</h1>
            <?php if ($low_stock_count > 0): ?>
            <div class="alert alert-warning py-2 px-3 mt-2 d-inline-block">
                <i class="fas fa-exclamation-triangle"></i>
                Có <?php echo $low_stock_count; ?> nguyên liệu sắp hết
            </div>
            <?php endif; ?>
        </div>
        <div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addIngredientModal">
                <i class="fas fa-plus"></i> Thêm nguyên liệu
            </button>
            <a href="stock_in.php" class="btn btn-primary">
                <i class="fas fa-arrow-down"></i> Nhập kho
            </a>
        </div>
    </div>
    
    <!-- Search -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <input type="text" id="search-ingredient" class="form-control" placeholder="Tìm kiếm nguyên liệu... ">
                </div>
                <div class="col-md-3">
                    <select id="filter-status" class="form-select">
                        <option value="all">Tất cả trạng thái</option>
                        <option value="low">Sắp hết hàng</option>
                        <option value="normal">Bình thường</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ingredients Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="ingredients-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên nguyên liệu</th>
                            <th>Đơn vị</th>
                            <th>Tồn kho</th>
                            <th>Tồn kho tối thiểu</th>
                            <th>Giá đơn vị</th>
                            <th>Tổng giá trị</th>
                            <th>Nhà cung cấp</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ingredients as $index => $item): ?>
                        <?php
                        $is_low_stock = $item['current_stock'] <= ($item['reorder_level'] ?? 0);
                        $cost_price = $item['cost_price'] ?? 0;
                        $total_value = $item['current_stock'] * $cost_price;
                        ?>
                        <tr class="<?php echo $is_low_stock ? 'table-warning' : ''; ?>">
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($item['ingredient_name']); ?></strong>
                            </td>
                            <td><?php echo $item['unit']; ?></td>
                            <td>
                                <strong><?php echo number_format($item['current_stock'], 2); ?></strong>
                            </td>
                            <td><?php echo number_format($item['reorder_level'] ?? 0, 2); ?></td>
                            <td><?php echo formatMoney($cost_price); ?></td>
                            <td><strong><?php echo formatMoney($total_value); ?></strong></td>
                            <td>
                                <?php if (!empty($item['supplier_name'])): ?>
                                    <?php echo htmlspecialchars($item['supplier_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($item['supplier_phone'] ?? ''); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($is_low_stock): ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-exclamation-circle"></i> Sắp hết
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle"></i> Đủ hàng
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="stock_in.php?id=<?php echo $item['ingredient_id']; ?>" 
                                       class="btn btn-primary" title="Nhập kho">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                    <button class="btn btn-info" 
                                            onclick="editIngredient(<?php echo $item['ingredient_id']; ?>)"
                                            title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-secondary" 
                                            onclick="viewHistory(<?php echo $item['ingredient_id']; ?>)"
                                            title="Lịch sử">
                                        <i class="fas fa-history"></i>
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Xóa nguyên liệu này?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="ingredient_id" value="<?php echo $item['ingredient_id']; ?>">
                                        <button type="submit" class="btn btn-danger" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-info">
                            <td colspan="6" class="text-end"><strong>Tổng giá trị kho:</strong></td>
                            <td colspan="4">
                                <strong class="text-primary">
                                    <?php 
                                    $total_inventory = 0;
                                    foreach ($ingredients as $item) {
                                        $total_inventory += $item['current_stock'] * ($item['cost_price'] ?? 0);
                                    }
                                    echo formatMoney($total_inventory);
                                    ?>
                                </strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Ingredient Modal -->
<div class="modal fade" id="addIngredientModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm nguyên liệu mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Tên nguyên liệu *</label>
                            <input type="text" name="ingredient_name" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Đơn vị *</label>
                            <select name="unit" class="form-select" required>
                                <option value="kg">Kg</option>
                                <option value="g">Gram</option>
                                <option value="l">Lít</option>
                                <option value="ml">ML</option>
                                <option value="piece">Cái</option>
                                <option value="pack">Gói</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Số lượng hiện tại</label>
                            <input type="number" name="current_stock" class="form-control" value="0" step="0.01">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tồn kho tối thiểu *</label>
                            <input type="number" name="min_stock" class="form-control" required step="0.01">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Giá đơn vị</label>
                            <input type="number" name="unit_price" class="form-control" step="0.01">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tên nhà cung cấp</label>
                            <input type="text" name="supplier_name" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Số điện thoại NCC</label>
                            <input type="text" name="supplier_phone" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Search functionality
$('#search-ingredient').on('keyup', function() {
    const value = $(this).val().toLowerCase();
    $('#ingredients-table tbody tr').filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
});

// Filter by status
$('#filter-status').on('change', function() {
    const status = $(this).val();
    $('#ingredients-table tbody tr').each(function() {
        if (status === 'all') {
            $(this).show();
        } else if (status === 'low') {
            $(this).toggle($(this).hasClass('table-warning'));
        } else if (status === 'normal') {
            $(this).toggle(!$(this).hasClass('table-warning'));
        }
    });
});

function viewHistory(ingredientId) {
    window.location.href = 'ingredient_history.php?id=' + ingredientId;
}
</script>

<?php include '../../includes/footer.php'; ?>