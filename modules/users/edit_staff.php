<?php
$page_title = 'Chỉnh sửa nhân viên';
require_once '../../config/config.php';
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Ensure users.user_role enum supports extended roles
// (Không cần ALTER TABLE vì cột là user_role)

requirePermission('admin');

$user_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($user_id <= 0) {
    setAlert('Thiếu mã nhân viên', 'danger');
    header('Location: manage_staff.php');
    exit();
}

// Fetch user
$stmt = $db->prepare('SELECT * FROM users WHERE user_id = :id');
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    setAlert('Không tìm thấy nhân viên', 'danger');
    header('Location: manage_staff.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($_POST['full_name'])) {
            throw new Exception('Vui lòng nhập họ tên');
        }

        // Handle avatar upload (optional)
        $avatar = $user['avatar'];
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

        // Optional password update
        $password_clause = '';
        if (!empty($_POST['password'])) {
            if ($_POST['password'] !== ($_POST['confirm_password'] ?? '')) {
                throw new Exception('Mật khẩu xác nhận không khớp');
            }
            if (strlen($_POST['password']) < 6) {
                throw new Exception('Mật khẩu tối thiểu 6 ký tự');
            }
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $password_clause = ', password_hash = :password_hash';
        }

                // Normalize role to supported set
                $user_role = $_POST['role'];
                $allowed_roles = ['admin','manager','waiter','chef','cashier','staff'];
                if (!in_array($user_role, $allowed_roles, true)) {
                    $user_role = 'staff';
                }

                $query = "UPDATE users SET
                                        full_name = :full_name,
                                        email = :email,
                                        phone = :phone,
                                        user_role = :user_role,
                                        avatar = :avatar
                                        $password_clause
                                    WHERE user_id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':full_name', $_POST['full_name']);
            $stmt->bindParam(':email', $_POST['email']);
            $stmt->bindParam(':phone', $_POST['phone']);
                $stmt->bindParam(':user_role', $user_role);
            $stmt->bindParam(':avatar', $avatar);
            $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        if (!empty($password_clause)) {
            $stmt->bindParam(':password_hash', $hashed_password);
        }

        $stmt->execute();
        logActivity($db, $_SESSION['user_id'], 'update_user', 'Updated user: ' . $user_id);
        setAlert('Cập nhật nhân viên thành công', 'success');
        header('Location: manage_staff.php');
        exit();
    } catch (Exception $e) {
        setAlert($e->getMessage(), 'danger');
    }
}

// Include header after processing to avoid premature output before redirects
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Chỉnh sửa nhân viên</h1>
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
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" disabled>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số điện thoại</label>
                                <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mật khẩu mới</label>
                                <input type="password" name="password" class="form-control" minlength="6" placeholder="Để trống nếu không đổi">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Xác nhận mật khẩu</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Để trống nếu không đổi">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Chức vụ *</label>
                            <select name="role" class="form-select" required>
                                <?php
                                $roles = ['admin' => 'Admin', 'manager' => 'Manager', 'waiter' => 'Phục vụ', 'chef' => 'Đầu bếp', 'cashier' => 'Thu ngân'];
                                $current_role = $user['user_role'] ?? 'staff';
                                ?>
                                <option value="">-- Chọn chức vụ --</option>
                                <?php foreach ($roles as $val => $label): ?>
                                <option value="<?php echo $val; ?>" <?php echo $current_role == $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Avatar</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*">
                            <?php if (!empty($user['avatar'])): ?>
                            <div class="mt-2">
                                <img src="<?php echo SITE_URL . '/uploads/avatars/' . $user['avatar']; ?>" width="80" height="80" style="object-fit: cover;" class="rounded-circle" alt="avatar">
                            </div>
                            <?php endif; ?>
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
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
