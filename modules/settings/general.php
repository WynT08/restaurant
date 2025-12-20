<?php
$page_title = 'Cài đặt hệ thống';
include '../../includes/header.php';
requirePermission('admin');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        foreach ($_POST as $key => $value) {
            if ($key != 'submit') {
                $query = "UPDATE settings SET setting_value = :value WHERE setting_key = :key";
                $stmt = $db->prepare($query);
                $stmt->bindParam(': value', $value);
                $stmt->bindParam(':key', $key);
                $stmt->execute();
            }
        }
        
        setAlert('Cập nhật cài đặt thành công', 'success');
        header("Location: general. php");
        exit();
        
    } catch (Exception $e) {
        setAlert('Có lỗi xảy ra: ' . $e->getMessage(), 'danger');
    }
}

// Get all settings
$query = "SELECT * FROM settings ORDER BY setting_key";
$settings_result = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

$settings = [];
foreach ($settings_result as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Cài đặt hệ thống</h1>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <h5 class="mb-3">Thông tin nhà hàng</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Tên nhà hàng</label>
                            <input type="text" name="restaurant_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['restaurant_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <input type="text" name="address" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['address'] ?? ''); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số điện thoại</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['phone'] ??  ''); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['email'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Giờ mở cửa</label>
                            <input type="text" name="working_hours" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['working_hours'] ??  ''); ?>"
                                   placeholder="VD: 09:00 - 22:00">
                        </div>
                        
                        <hr>
                        
                        <h5 class="mb-3">Cài đặt hệ thống</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Thuế VAT (%)</label>
                                <input type="number" name="tax_rate" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['tax_rate'] ??  '10'); ?>" 
                                       min="0" max="100">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Đơn vị tiền tệ</label>
                                <input type="text" name="currency" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['currency'] ?? 'VND'); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Số ngày đặt bàn trước tối đa</label>
                            <input type="number" name="booking_advance_days" class="form-control" 
                                   value="<? php echo htmlspecialchars($settings['booking_advance_days'] ?? '30'); ?>" 
                                   min="1">
                        </div>
                        
                        <button type="submit" name="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu cài đặt
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Thông tin hệ thống</h6>
                </div>
                <div class="card-body">
                    <p><strong>Version:</strong> 1.0.0</p>
                    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                    <p><strong>MySQL Version:</strong> 
                        <?php echo $db->query('SELECT VERSION()')->fetchColumn(); ?>
                    </p>
                    <hr>
                    <a href="backup.php" class="btn btn-warning btn-sm w-100 mb-2">
                        <i class="fas fa-database"></i> Sao lưu dữ liệu
                    </a>
                    <a href="clear_cache.php" class="btn btn-secondary btn-sm w-100">
                        <i class="fas fa-broom"></i> Xóa cache
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>