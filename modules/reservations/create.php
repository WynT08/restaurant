<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$page_title = 'Tạo đặt bàn mới';

// Handle form submission BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $table_id = !empty($_POST['table_id']) ? (int) $_POST['table_id'] : null;
        $customer_email = !empty($_POST['customer_email']) ? $_POST['customer_email'] : null;
        $special_requests = !empty($_POST['special_requests']) ? $_POST['special_requests'] : null;
        $reserveDateTime = $_POST['reservation_date'] . ' ' . $_POST['reservation_time'];

        // Không cho đặt trùng bàn trong khung ±3 giờ với các booking pending/confirmed
        if ($table_id !== null) {
            $conflict = $db->prepare("SELECT 1 FROM reservations
                WHERE table_id = :tid
                  AND status IN ('pending','confirmed')
                  AND ABS(TIMESTAMPDIFF(MINUTE, CONCAT(reservation_date,' ',reservation_time), :dt)) < 180
                LIMIT 1");
            $conflict->bindParam(':tid', $table_id, PDO::PARAM_INT);
            $conflict->bindParam(':dt', $reserveDateTime);
            $conflict->execute();
            if ($conflict->fetch()) {
                throw new Exception('Bàn này đã có lịch gần thời gian đặt (±3 giờ). Chọn bàn khác hoặc giờ khác.');
            }
        }

        $query = "INSERT INTO reservations (
            customer_name, customer_phone, customer_email,
            table_id, reservation_date, reservation_time,
            number_of_guests, special_requests, status
        ) VALUES (
            :customer_name, :customer_phone, :customer_email,
            :table_id, :reservation_date, :reservation_time,
            :number_of_guests, :special_requests, 'pending'
        )";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':customer_name', $_POST['customer_name']);
        $stmt->bindParam(':customer_phone', $_POST['customer_phone']);
        $stmt->bindParam(':customer_email', $customer_email);
        if ($table_id === null) {
            $stmt->bindValue(':table_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':table_id', $table_id, PDO::PARAM_INT);
        }
        $stmt->bindParam(':reservation_date', $_POST['reservation_date']);
        $stmt->bindParam(':reservation_time', $_POST['reservation_time']);
        $stmt->bindParam(':number_of_guests', $_POST['number_of_guests'], PDO::PARAM_INT);
        $stmt->bindParam(':special_requests', $special_requests);

        if ($stmt->execute()) {
            // Đánh dấu bàn đã được giữ nếu có chọn bàn
            if ($table_id !== null) {
                $updateTable = $db->prepare("UPDATE restaurant_tables SET status = 'reserved' WHERE table_id = :tid");
                $updateTable->bindParam(':tid', $table_id, PDO::PARAM_INT);
                $updateTable->execute();
            }

            setAlert('Đặt bàn thành công', 'success');
            header('Location: list.php');
            exit();
        }
    } catch (Exception $e) {
        setAlert('Có lỗi xảy ra: ' . $e->getMessage(), 'danger');
    }
}

// Get available tables after processing POST
$query = "SELECT * FROM restaurant_tables WHERE status IN ('available', 'reserved') ORDER BY table_number";
$tables = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
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
                                        <?php echo $table['table_number']; ?> 
                                        (<?php echo $table['capacity']; ?> chỗ - <?php echo ucfirst($table['location']); ?>)
                                    </option>
                                    <?php endforeach; ?>
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

<?php include '../../includes/footer.php'; ?>