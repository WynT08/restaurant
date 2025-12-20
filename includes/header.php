<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();
$current_user = getCurrentUser($db);

// Get current page
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Role-based scope whitelist
$current_role = $current_user['role'] ?? ($current_user['user_role'] ?? '');
$uri = $_SERVER['REQUEST_URI'] ?? '';

// Common always-allowed paths for limited roles
$common_allowed = [
    '/modules/dashboard/index.php',
    '/modules/auth/logout.php'
];

if ($current_role === 'waiter') {
    $allowed_paths = array_merge($common_allowed, [
        '/modules/orders/pos.php'
    ]);
    $allowed = false;
    foreach ($allowed_paths as $p) {
        if (strpos($uri, $p) !== false) { $allowed = true; break; }
    }
    if (!$allowed) {
        header('Location: ' . SITE_URL . '/modules/dashboard/index.php');
        exit();
    }
}

if ($current_role === 'chef') {
    $allowed_paths = array_merge($common_allowed, [
        '/modules/orders/kitchen_display.php'
    ]);
    $allowed = false;
    foreach ($allowed_paths as $p) {
        if (strpos($uri, $p) !== false) { $allowed = true; break; }
    }
    if (!$allowed) {
        header('Location: ' . SITE_URL . '/modules/dashboard/index.php');
        exit();
    }
}

if ($current_role === 'cashier') {
    $allowed_paths = array_merge($common_allowed, [
        '/modules/orders/pos.php',
        '/modules/payments/payment_history.php'
    ]);
    $allowed = false;
    foreach ($allowed_paths as $p) {
        if (strpos($uri, $p) !== false) { $allowed = true; break; }
    }
    if (!$allowed) {
        header('Location: ' . SITE_URL . '/modules/dashboard/index.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_TITLE; ?> - Restaurant Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/dashboard.css">
    
    <?php if ($current_page == 'pos'): ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/pos.css">
    <?php endif; ?>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-link" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="navbar-nav ms-auto">
                        <!-- Notifications -->
                        <div class="nav-item dropdown">
                            <a class="nav-link" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <span class="badge bg-danger">3</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                                <h6 class="dropdown-header">Thông báo</h6>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-exclamation-circle text-warning"></i>
                                    Nguyên liệu sắp hết
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-calendar text-info"></i>
                                    Có 5 đặt bàn mới
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-center" href="#">Xem tất cả</a>
                            </div>
                        </div>
                        
                        <!-- User Profile -->
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <img src="<?php echo SITE_URL; ?>/assets/images/default-avatar.png" alt="Avatar" class="rounded-circle" width="32" height="32">
                                <span class="ms-2"><?php echo htmlspecialchars($current_user['full_name']); ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <div class="dropdown-header">
                                    <strong><?php echo htmlspecialchars($current_user['full_name'] ?? ''); ?></strong><br>
                                    <small class="text-muted"><?php echo isset($current_user['user_role']) ? ucfirst($current_user['user_role']) : ''; ?></small>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/users/profile.php">
                                    <i class="fas fa-user"></i> Hồ sơ
                                </a>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/settings/general.php">
                                    <i class="fas fa-cog"></i> Cài đặt
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/modules/auth/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Page Content -->
            <div class="content-wrapper">
                <?php displayAlert(); ?>