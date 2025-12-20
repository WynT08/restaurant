<?php
ob_start();
$page_title = 'Quản lý nhân viên';
include '../../includes/header.php';
requirePermission('admin');

// Handle delete
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Don't allow deleting yourself
    if ($user_id == $_SESSION['user_id']) {
        setAlert('Không thể xóa tài khoản của chính bạn', 'danger');
    } else {
        $query = "DELETE FROM users WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            setAlert('Xóa nhân viên thành công', 'success');
        }
    }
    
    header("Location: manage_staff.php");
    exit();
}

// Get filter
$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';
$status_filter = isset($_GET['status']) ? $_GET['status'] :  'all';

// Build query
$query = "SELECT * FROM users WHERE 1=1";

if ($role_filter != 'all') {
    $query .= " AND role = :role";
}

// users table không có cột status; bỏ lọc này

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);

if ($role_filter != 'all') {
    $stmt->bindParam(':role', $role_filter);
}

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count by role
$role_counts = [
    'admin' => 0,
    'manager' => 0,
    'waiter' => 0,
    'chef' => 0,
    'cashier' => 0,
    'staff' => 0
];

foreach ($users as $user) {
    $role_key = $user['role'] ?? ($user['user_role'] ?? 'staff');
    if (isset($role_counts[$role_key])) {
        $role_counts[$role_key]++;
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Quản lý nhân viên</h1>
        <a href="add_staff.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Thêm nhân viên
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h3 class="text-danger mb-0"><?php echo $role_counts['admin']; ?></h3>
                    <small class="text-muted">Admin</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h3 class="text-primary mb-0"><?php echo $role_counts['manager']; ?></h3>
                    <small class="text-muted">Manager</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h3 class="text-success mb-0"><?php echo $role_counts['waiter']; ?></h3>
                    <small class="text-muted">Phục vụ</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h3 class="text-warning mb-0"><?php echo $role_counts['chef']; ?></h3>
                    <small class="text-muted">Đầu bếp</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h3 class="text-info mb-0"><?php echo $role_counts['cashier']; ?></h3>
                    <small class="text-muted">Thu ngân</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-secondary">
                <div class="card-body text-center">
                    <h3 class="text-secondary mb-0"><?php echo count($users); ?></h3>
                    <small class="text-muted">Tổng cộng</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <select id="role-filter" class="form-select" onchange="filterStaff()">
                        <option value="all" <?php echo $role_filter == 'all' ? 'selected' : ''; ?>>Tất cả chức vụ</option>
                        <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' :  ''; ?>>Admin</option>
                        <option value="manager" <?php echo $role_filter == 'manager' ? 'selected' : ''; ?>>Manager</option>
                        <option value="waiter" <?php echo $role_filter == 'waiter' ? 'selected' :  ''; ?>>Phục vụ</option>
                        <option value="chef" <?php echo $role_filter == 'chef' ? 'selected' :  ''; ?>>Đầu bếp</option>
                        <option value="cashier" <?php echo $role_filter == 'cashier' ? 'selected' : ''; ?>>Thu ngân</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="status-filter" class="form-select" onchange="filterStaff()">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Đang làm</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Nghỉ việc</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="text" id="search-staff" class="form-control" placeholder="Tìm kiếm nhân viên... ">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Staff List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="staff-table">
                    <thead>
                        <tr>
                            <th width="80">Avatar</th>
                            <th>Họ tên</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                            <th>Chức vụ</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th width="150">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <?php if ($user['avatar']): ?>
                                    <img src="<?php echo SITE_URL .  '/uploads/avatars/' . $user['avatar']; ?>" 
                                         class="rounded-circle" width="50" height="50" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                         style="width: 50px; height: 50px; font-size: 20px; font-weight: bold;">
                                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($user['username'] ?? ($user['email'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                            <td>
                                <?php
                                $role_badges = [
                                    'admin' => ['danger', 'Admin'],
                                    'manager' => ['primary', 'Manager'],
                                    'waiter' => ['success', 'Phục vụ'],
                                    'chef' => ['warning', 'Đầu bếp'],
                                    'cashier' => ['info', 'Thu ngân'],
                                    'staff' => ['secondary', 'Staff']
                                ];
                                $role_key = $user['user_role'] ?? 'staff';
                                $badge = $role_badges[$role_key] ?? ['secondary', ucfirst($role_key)];
                                ?>
                                <span class="badge bg-<?php echo $badge[0]; ?>">
                                    <?php echo $badge[1]; ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $user_status = $user['status'] ?? 'active';
                                if ($user_status == 'active'): ?>
                                    <span class="badge bg-success">Đang làm</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nghỉ việc</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatDate($user['created_at']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="staff_profile.php?id=<?php echo $user['user_id']; ?>" 
                                       class="btn btn-info" title="Xem">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_staff.php?id=<?php echo $user['user_id']; ?>" 
                                       class="btn btn-primary" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                    <a href="?delete=<?php echo $user['user_id']; ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('Xóa nhân viên này?')" 
                                       title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function filterStaff() {
    const role = $('#role-filter').val();
    const status = $('#status-filter').val();
    window.location.href = `manage_staff.php?role=${role}&status=${status}`;
}

// Search
$('#search-staff').on('keyup', function() {
    const value = $(this).val().toLowerCase();
    $('#staff-table tbody tr').filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
});
</script>

<?php include '../../includes/footer.php'; ?>