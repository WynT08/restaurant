<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT * FROM menu_items WHERE is_available = 1";

if ($category_id != 'all') {
    $query .= " AND category_id = :category_id";
}

if (! empty($search)) {
    $query .= " AND item_name LIKE :search";
}

$query .= " ORDER BY item_name";

$stmt = $db->prepare($query);

if ($category_id != 'all') {
    $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
}

if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bindParam(':search', $search_param);
}

$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($items);
?>