<?php
$page_title = 'Thêm chi phí';
include '../../includes/header.php';
requirePermission('manager');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Upload receipt image
        $receipt_image = '';
        if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] == 0) {
            $upload_dir = ROOT_PATH . '/uploads/receipts/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $result = uploadFile($_FILES['receipt_image'], $upload_dir);
            if ($result['success']) {
                $receipt_image = $result['filename'];
            }
        }
        
        // Insert expense
        $query = "INSERT INTO expenses (
            expense_type, amount, description, expense_date,
            payment_method, receipt_image, recorded_by
        ) VALUES (
            :expense_type, :amount, :description, :expense_date,
            : payment_method, :receipt_image, :recorded_by
        )";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':expense_type', $_POST['expense_type']);
        $stmt->bindParam(': amount', $_POST['amount']);
        $stmt->bindParam(':description', $_POST['description']);
        $stmt->bindParam(':expense_date', $_POST['expense_date']);
        $stmt->bindParam(':payment_method', $_POST['payment_method']);
        $stmt->bindParam(':receipt_image', $receipt_image);
        $stmt->bindParam(':recorded_by', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            setAlert('Thêm chi phí thành công', 'success');
            header("Location: expense_list.php");
            exit();
        }
        
    } catch (Exception $e) {
        setAlert('Có lỗi xảy ra: ' . $e->getMessage(), 'danger');
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Thêm chi phí</h1>
        <a href="expense_list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Loại chi phí *</label>
                                <select name="expense_type" class="form-select" required>
                                    <option value="">-- Chọn loại --</option>
                                    <option value="salary">Lương nhân viên</option>
                                    <option value="utilities">Điện, nước, internet</option>
                                    <option value="rent">Thuê mặt bằng</option>
                                    <option value="maintenance">Bảo trì, sửa chữa</option>
                                    <option value="supplies">Vật tư, dụng cụ</option>
                                    <option value="marketing">Marketing, quảng cáo</option>
                                    <option value="other">Khác</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số tiền *</label>
                                <div class="input-group">
                                    <input type="number" name="amount" class="form-control" required min="0" step="1000">
                                    <span class="input-group-text">đ</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mô tả *</label>
                            <textarea name="description" class="form-control" rows="3" required 
                                      placeholder="VD: Lương tháng 12/2024, Hóa đơn điện tháng 12... "></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ngày chi *</label>
                                <input type="date" name="expense_date" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phương thức thanh toán *</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="cash">Tiền mặt</option>
                                    <option value="card">Thẻ</option>
                                    <option value="bank_transfer">Chuyển khoản</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Hình ảnh hóa đơn</label>
                            <input type="file" name="receipt_image" class="form-control" accept="image/*">
                            <small class="text-muted">Chụp hình hóa đơn/chứng từ để lưu trữ</small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Lưu
                            </button>
                            <a href="expense_list. php" class="btn btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Lưu ý</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Ghi rõ mục đích chi tiêu</li>
                        <li>Chụp hình hóa đơn để đối chiếu</li>
                        <li>Nhập đúng số tiền</li>
                        <li>Chọn đúng ngày phát sinh</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>