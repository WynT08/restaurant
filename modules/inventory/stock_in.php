<?php
$page_title = 'Nhập kho';
include '../../includes/header.php';
requirePermission('manager');

// Get ingredient if ID provided
$ingredient = null;
if (isset($_GET['id'])) {
    $query = "SELECT * FROM ingredients WHERE ingredient_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();
    $ingredient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();
        
        $ingredient_id = $_POST['ingredient_id'];
        $quantity = $_POST['quantity'];
        $unit_price = $_POST['unit_price'];
        $total_cost = $quantity * $unit_price;
        
        // Update stock (schema uses cost_price)
        $query = "UPDATE ingredients 
                  SET current_stock = current_stock + :quantity,
                      cost_price = :unit_price,
                      last_restocked = CURDATE()
                  WHERE ingredient_id = :ingredient_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':unit_price', $unit_price);
        $stmt->bindParam(':ingredient_id', $ingredient_id);
        $stmt->execute();
        
        // Log transaction
        $query = "INSERT INTO inventory_transactions (
            ingredient_id, transaction_type, quantity, unit_price, 
            total_cost, reference_type, notes, performed_by
        ) VALUES (
            :ingredient_id, 'in', :quantity, :unit_price,
            :total_cost, 'purchase', :notes, :user_id
        )";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':ingredient_id', $ingredient_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':unit_price', $unit_price);
        $stmt->bindParam(':total_cost', $total_cost);
        $stmt->bindParam(':notes', $_POST['notes']);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        $db->commit();
        setAlert('Nhập kho thành công', 'success');
        header("Location: ingredients.php");
        exit();
        
    } catch (Exception $e) {
        $db->rollBack();
        setAlert('Có lỗi xảy ra: ' . $e->getMessage(), 'danger');
    }
}

// Get all ingredients for dropdown
$ingredients = $db->query("SELECT * FROM ingredients ORDER BY ingredient_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Nhập kho</h1>
        <a href="ingredients.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Chọn nguyên liệu *</label>
                            <select name="ingredient_id" id="ingredient-select" class="form-select" required>
                                <option value="">-- Chọn nguyên liệu --</option>
                                <?php foreach ($ingredients as $item): ?>
                                <option value="<?php echo $item['ingredient_id']; ?>"
                                        data-unit="<?php echo $item['unit']; ?>"
                                        data-price="<?php echo $item['cost_price'] ?? 0; ?>"
                                        data-current="<?php echo $item['current_stock']; ?>"
                                        <?php echo $ingredient && $ingredient['ingredient_id'] == $item['ingredient_id'] ?  'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($item['ingredient_name']); ?>
                                    (Tồn: <?php echo $item['current_stock']; ?> <?php echo $item['unit']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="ingredient-info" class="alert alert-info" style="display: none;">
                            <strong>Thông tin hiện tại:</strong><br>
                            Tồn kho: <span id="current-stock">0</span> <span id="unit-display"></span><br>
                            Giá gần nhất: <span id="last-price">0đ</span>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Số lượng nhập *</label>
                                <div class="input-group">
                                    <input type="number" name="quantity" id="quantity" 
                                           class="form-control" required step="0.01" min="0. 01">
                                    <span class="input-group-text" id="unit-text">-</span>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Giá đơn vị *</label>
                                <div class="input-group">
                                    <input type="number" name="unit_price" id="unit-price" 
                                           class="form-control" required step="0.01" min="0">
                                    <span class="input-group-text">đ</span>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tổng tiền</label>
                                <div class="input-group">
                                    <input type="text" id="total-cost" class="form-control" readonly>
                                    <span class="input-group-text">đ</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="notes" class="form-control" rows="3" 
                                      placeholder="VD: Nhập từ nhà cung cấp ABC, số hóa đơn... "></textarea>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Xác nhận nhập kho
                            </button>
                            <a href="ingredients.php" class="btn btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Hướng dẫn</h6>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li class="mb-2">Chọn nguyên liệu cần nhập kho</li>
                        <li class="mb-2">Nhập số lượng và giá đơn vị</li>
                        <li class="mb-2">Kiểm tra tổng tiền</li>
                        <li class="mb-2">Thêm ghi chú nếu cần</li>
                        <li>Nhấn "Xác nhận nhập kho"</li>
                    </ol>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header bg-warning">
                    <h6 class="mb-0"><i class="fas fa-lightbulb"></i> Lưu ý</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Kiểm tra kỹ số lượng trước khi nhập</li>
                        <li>Giá đơn vị sẽ được cập nhật</li>
                        <li>Ghi chú rõ ràng để dễ theo dõi</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$('#ingredient-select').on('change', function() {
    const selected = $(this).find(':selected');
    const unit = selected.data('unit');
    const price = selected.data('price');
    const current = selected.data('current');
    
    if (selected.val()) {
        $('#ingredient-info').show();
        $('#current-stock').text(current);
        $('#unit-display').text(unit);
        $('#last-price').text(price.toLocaleString('vi-VN') + 'đ');
        $('#unit-text').text(unit);
        $('#unit-price').val(price);
        calculateTotal();
    } else {
        $('#ingredient-info').hide();
    }
});

$('#quantity, #unit-price').on('input', calculateTotal);

function calculateTotal() {
    const quantity = parseFloat($('#quantity').val()) || 0;
    const unitPrice = parseFloat($('#unit-price').val()) || 0;
    const total = quantity * unitPrice;
    $('#total-cost').val(total.toLocaleString('vi-VN'));
}

// Auto-select if ingredient provided in URL
<?php if ($ingredient): ?>
$('#ingredient-select').trigger('change');
<?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>