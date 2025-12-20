<?php
// Cấu hình hệ thống
define('SITE_URL', 'http://localhost/restaurant-management');
define('SITE_NAME', 'Restaurant Management System');
define('SITE_TITLE', 'Nhà hàng Ngon Việt');

// Đường dẫn
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH .  '/uploads/');
define('MENU_IMAGE_PATH', UPLOAD_PATH .  'menu_images/');
define('RECEIPT_PATH', UPLOAD_PATH . 'receipts/');
define('REPORT_PATH', UPLOAD_PATH . 'reports/');

// Cấu hình hệ thống
define('TAX_RATE', 10); // 10% VAT
define('CURRENCY', 'VND');
define('CURRENCY_SYMBOL', 'đ');
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoload classes
spl_autoload_register(function ($class_name) {
    $file = ROOT_PATH . '/classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Include functions
require_once ROOT_PATH . '/includes/functions.php';
?>