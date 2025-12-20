<?php
$page_title = 'Tạo đặt bàn mới';
include '../../includes/header.php';

// Get available tables
$query = "SELECT * FROM restaurant_tables WHERE status IN ('available', 'reserved') ORDER BY table_number";
$tables = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $query = "INSERT INTO reservations (
            customer_name, customer_phone, customer_email,
            table_id, reservation_date, reservation_time,
            number_of_guests, special_requests, status, created_by
        ) VALUES (
            :customer_name, : customer_phone, :customer_email,
            :table_id, :reservation_date, :reservation_time,
            :number_of_guests, :special_requests, 'pending', :created_by
        )";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':customer_name', $_POST['customer_name']);
        $stmt->bindParam(':customer_phone', $_POST['customer_phone']);
        $stmt->bindParam(':customer_email', $_POST['customer_email']);
        $stmt->bindParam(':table_id', $_POST['table_id']);
        $stmt->bindParam(':reservation_date', $_POST['reservation_date']);
        $stmt->bindParam(': reservation_time', $_POST['reservation_time']);
        $stmt->bindParam(':number_of_guests', $_POST['number_of_guests']);
        $stmt->bindParam(':special_requests', $_POST['special_requests']);
        $stmt->bindParam(':created_by', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            setAlert('Đặt bàn thành công', 'success');
            header("Location: list.php");
            exit();
        }
    } catch (Exception $e) {
        setAlert('Có lỗi xảy ra: ' . $e->getMessage(), 'danger');
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Tạo đặt bàn mới</h1>
        <a href="list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <h5 class="mb-3">Thông tin khách hàng</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tên khách hàng *</label>
                                <input type="text" name="customer_name" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số điện thoại *</label>
                                <input type="tel" name="customer_phone" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="customer_email" class="form-control">
                        </div>
                        
                        <hr>
                        
                        <h5 class="mb-3">Thông tin đặt bàn</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ngày *</label>
                                <input type="date" name="reservation_date" class="form-control" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Giờ *</label>
                                <input type="time" name="reservation_time" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số khách *</label>
                                <input type="number" name="number_of_guests" class="form-control" 
                                       min="1" max="50" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Chọn bàn</label>
                                <select name="table_id" class="form-select">
                                    <option value="">-- Chọn sau --</option>
                                    <?php foreach ($tables as $table): ?>
                                    <option value="<?php echo $table['table_id']; ?>">
                                        <?php echo $table['table_number']; ? > 
                                        (<?php echo $table['capacity']; ?> chỗ - <? php echo ucfirst($table['location']); ?>)
                                    </option>
                                    <? php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Yêu cầu đặc biệt</label>
                            <textarea name="special_requests" class="form-control" rows="3" 
                                      placeholder="VD: Sinh nhật, cần trang trí, thực đơn đặc biệt..."></textarea>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> Tạo đặt bàn
                            </button>
                            <a href="list.php" class="btn btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Lưu ý</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="mb-2">Kiểm tra kỹ thông tin khách hàng</li>
                        <li class="mb-2">Chọn bàn phù hợp với số lượng khách</li>
                        <li class="mb-2">Ghi chú rõ yêu cầu đặc biệt</li>
                        <li>Xác nhận lại với khách trước giờ đặt</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<? php include '../../includes/footer. php'; ?>