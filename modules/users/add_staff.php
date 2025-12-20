<?php
$page_title = 'Thêm nhân viên';
include '../../includes/header. php';
requirePermission('admin');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate
        if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['full_name'])) {
            throw new Exception('Vui lòng điền đầy đủ thông tin');
        }
        
        // Check username exists
        $query = "SELECT COUNT(*) FROM users WHERE username = : username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $_POST['username']);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Username đã tồn tại');
        }
        
        // Upload avatar
        $avatar = '';
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $upload_dir = ROOT_PATH . '/uploads/avatars/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $result = uploadFile($_FILES['avatar'], $upload_dir);
            if ($result['success']) {
                $avatar = $result['filename'];
            }
        }
        
        // Hash password
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // Insert user
        $query = "INSERT INTO users (
            username, password, full_name, email, phone, 
            role, avatar, status
        ) VALUES (
            :username, :password, :full_name, :email, :phone,
            :role, :avatar, 'active'
        )";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $_POST['username']);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':full_name', $_POST['full_name']);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':phone', $_POST['phone']);
        $stmt->bindParam(':role', $_POST['role']);
        $stmt->bindParam(':avatar', $avatar);
        
        if ($stmt->execute()) {
            logActivity($db, $_SESSION['user_id'], 'create_user', 'Created user:  ' . $_POST['username']);
            setAlert('Thêm nhân viên thành công', 'success');
            header("Location: manage_staff.php");
            exit();
        }
        
    } catch (Exception $e) {
        setAlert($e->getMessage(), 'danger');
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Thêm nhân viên mới</h1>
        <a href="manage_staff.php" class="btn btn-secondary">
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
                                <label class="form-label">Họ và tên *</label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số điện thoại</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mật khẩu *</label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                                <small class="text-muted">Tối thiểu 6 ký tự</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Xác nhận mật khẩu *</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Chức vụ *</label>
                            <select name="role" class="form-select" required>
                                <option value="">-- Chọn chức vụ --</option>
                                <option value="admin">Admin</option>
                                <option value="manager">Manager</option>
                                <option value="waiter">Phục vụ</option>
                                <option value="chef">Đầu bếp</option>
                                <option value="cashier">Thu ngân</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Avatar</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*">
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Lưu
                            </button>
                            <a href="manage_staff.php" class="btn btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Hướng dẫn</h6>
                </div>
                <div class="card-body">
                    <h6>Phân quyền: </h6>
                    <ul class="mb-0">
                        <li><strong>Admin:</strong> Toàn quyền hệ thống</li>
                        <li><strong>Manager:</strong> Quản lý nhà hàng</li>
                        <li><strong>Phục vụ:</strong> POS, đặt bàn</li>
                        <li><strong>Đầu bếp:</strong> Kitchen display</li>
                        <li><strong>Thu ngân:</strong> Thanh toán</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation
$('form').on('submit', function(e) {
    const password = $('input[name="password"]').val();
    const confirm = $('input[name="confirm_password"]').val();
    
    if (password !== confirm) {
        e.preventDefault();
        alert('Mật khẩu xác nhận không khớp! ');
        return false;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>