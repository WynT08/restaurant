<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requirePermission('admin');

$database = new Database();
$db = $database->getConnection();

$backupFile = 'backup_restaurant_db_' . date('Ymd_His') . '.sql';
$backupPath = UPLOAD_PATH . $backupFile;

$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
$sql = "";
foreach ($tables as $row) {
    $table = $row[0];
    // Lấy cấu trúc bảng
    $create = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
    $sql .= "-- Table structure for `$table`\n";
    $sql .= $create['Create Table'] . ";\n\n";
    // Lấy dữ liệu bảng
    $data = $db->query("SELECT * FROM `$table`", PDO::FETCH_ASSOC);
    foreach ($data as $record) {
        $cols = array_map(function($col) { return "`$col`"; }, array_keys($record));
        $vals = array_map(function($val) use ($db) {
            return $val === null ? 'NULL' : $db->quote($val);
        }, array_values($record));
        $sql .= "INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
    }
    $sql .= "\n";
}

file_put_contents($backupPath, $sql);
if (file_exists($backupPath)) {
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $backupFile . '"');
    readfile($backupPath);
    @unlink($backupPath);
    exit();
} else {
    setAlert('Không thể sao lưu dữ liệu. Vui lòng kiểm tra quyền ghi thư mục uploads.', 'danger');
    header('Location: general.php');
    exit();
}
