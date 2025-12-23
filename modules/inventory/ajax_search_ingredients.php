<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$search = $_GET['q'] ?? '';
$stmt = $db->prepare("SELECT ingredient_id, ingredient_name, unit FROM ingredients WHERE ingredient_name LIKE :q LIMIT 20");
$stmt->execute([':q' => "%$search%"]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $results]);
