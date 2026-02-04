<?php
/**
 * File chứa các hàm tiện ích
 */

// Kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']) && ! empty($_SESSION['user_id']);
}

// Kiểm tra quyền
function hasPermission($role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $allowed_roles = [];
    if ($role == 'admin') {
        $allowed_roles = ['admin'];
    } elseif ($role == 'manager') {
        $allowed_roles = ['admin', 'manager'];
    } elseif ($role == 'staff') {
        $allowed_roles = ['admin', 'manager', 'waiter', 'chef', 'cashier'];
    }
    
    return in_array($_SESSION['user_role'], $allowed_roles);
}

// Redirect nếu chưa đăng nhập
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . SITE_URL . "/modules/auth/login.php");
        exit();
    }
}

// Redirect nếu không có quyền
function requirePermission($role) {
    requireLogin();
    if (!hasPermission($role)) {
        header("Location: " . SITE_URL . "/modules/dashboard/index.php? error=no_permission");
        exit();
    }
}

// Format tiền
function formatMoney($amount) {
    return number_format($amount, 0, ',', '. ') . ' ' . CURRENCY_SYMBOL;
}

// Format ngày
function formatDate($date, $format = DATE_FORMAT) {
    return date($format, strtotime($date));
}

// Format datetime
function formatDateTime($datetime) {
    return date(DATETIME_FORMAT, strtotime($datetime));
}

// Sanitize input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Upload file
function uploadFile($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    $target_file = $target_dir .  basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if file is actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['success' => false, 'message' => 'File không phải là ảnh'];
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'File quá lớn (tối đa 5MB)'];
    }
    
    // Allow certain file formats
    if (!in_array($imageFileType, $allowed_types)) {
        return ['success' => false, 'message' => 'Chỉ chấp nhận file:  ' . implode(', ', $allowed_types)];
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '_' . time() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;

    // Ensure the target directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'message' => 'Lỗi khi upload file'];
    }
}

// Generate order number
function generateOrderNumber() {
    return 'ORD' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

// Alert message
function setAlert($message, $type = 'success') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Get and clear alert
function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

// Display alert
function displayAlert() {
    $alert = getAlert();
    if ($alert) {
        $class = $alert['type'] == 'success' ? 'alert-success' : 'alert-danger';
        echo '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($alert['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

// Get current user
function getCurrentUser($db) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $query = "SELECT * FROM users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->fetch(PDO:: FETCH_ASSOC);
}

// Log activity
function logActivity($db, $user_id, $action, $description) {
    try {
        $query = "INSERT INTO activity_logs (user_id, action, description, ip_address) 
              VALUES (:user_id, :action, :description, :ip_address)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':description', $description);
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt->bindParam(':ip_address', $ip);
        $stmt->execute();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Pagination
function paginate($total_records, $records_per_page, $current_page) {
    $total_pages = ceil($total_records / $records_per_page);
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'limit' => $records_per_page
    ];
}
?>