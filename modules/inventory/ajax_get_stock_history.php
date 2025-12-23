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

$ingredient_id = $_GET['ingredient_id'] ?? 0;
$stmt = $db->prepare("SELECT * FROM inventory_transactions WHERE ingredient_id = :id ORDER BY created_at DESC LIMIT 50");
$stmt->execute([':id' => $ingredient_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $history]);
