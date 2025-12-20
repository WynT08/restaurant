<?php
// Simple installer to initialize the database schema and seed data
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

$sqlFile = __DIR__ . '/restaurant_database.sql';
if (!file_exists($sqlFile)) {
    exit('Không tìm thấy file restaurant_database.sql');
}

try {
    $db = (new Database())->getConnection();
    $sql = file_get_contents($sqlFile);

    // Split statements on semicolon; keep it simple for this schema
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if ($statement === '') {
            continue;
        }
        $db->exec($statement);
    }

    echo '<h3>Cài đặt thành công</h3>';
    echo '<p>Database và dữ liệu mẫu đã được khởi tạo.</p>';
    echo '<p><a href="index.php">Về trang chủ</a></p>';
} catch (Exception $e) {
    http_response_code(500);
    echo '<h3>Cài đặt thất bại</h3>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}
