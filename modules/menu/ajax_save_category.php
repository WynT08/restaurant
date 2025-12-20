<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();
requirePermission('manager');

$database = new Database();
$db = $database->getConnection();

header('Content-Type: application/json');

$category_id = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
$category_name = trim($_POST['category_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$display_order = isset($_POST['display_order']) ? (int) $_POST['display_order'] : 0;
$status = $_POST['status'] ?? 'active';

if ($category_name === '') {
    echo json_encode(['success' => false, 'message' => 'Tên danh mục là bắt buộc']);
    exit;
}

// Handle image upload
$imageName = null;
if (!empty($_FILES['image']['name'])) {
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $safeName = uniqid('cat_') . '.' . $ext;
    $target = UPLOAD_PATH . 'categories/' . $safeName;
    if (!is_dir(UPLOAD_PATH . 'categories/')) {
        mkdir(UPLOAD_PATH . 'categories/', 0755, true);
    }
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        $imageName = $safeName;
    }
}

try {
    if ($category_id > 0) {
        // Update
        $sql = "UPDATE categories SET category_name = :name, description = :desc, display_order = :ord, status = :status";
        if ($imageName) {
            $sql .= ", image = :img";
        }
        $sql .= " WHERE category_id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $category_name);
        $stmt->bindParam(':desc', $description);
        $stmt->bindParam(':ord', $display_order);
        $stmt->bindParam(':status', $status);
        if ($imageName) {
            $stmt->bindParam(':img', $imageName);
        }
        $stmt->bindParam(':id', $category_id, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['success' => true]);
    } else {
        // Insert
        $stmt = $db->prepare("INSERT INTO categories (category_name, description, display_order, status, image) VALUES (:name, :desc, :ord, :status, :img)");
        $stmt->bindParam(':name', $category_name);
        $stmt->bindParam(':desc', $description);
        $stmt->bindParam(':ord', $display_order);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':img', $imageName);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
