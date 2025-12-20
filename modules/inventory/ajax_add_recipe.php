<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!hasPermission('manager')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get unit from ingredient
    $query = "SELECT unit FROM ingredients WHERE ingredient_id = :ingredient_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':ingredient_id', $_POST['ingredient_id']);
    $stmt->execute();
    $ingredient = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ingredient) {
        echo json_encode(['success' => false, 'message' => 'Nguyên liệu không tồn tại']);
        exit();
    }
    
    // Insert recipe
    $query = "INSERT INTO recipes (item_id, ingredient_id, quantity, unit) 
              VALUES (:item_id, :ingredient_id, :quantity, :unit)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':item_id', $_POST['item_id']);
    $stmt->bindParam(':ingredient_id', $_POST['ingredient_id']);
    $stmt->bindParam(':quantity', $_POST['quantity']);
    $stmt->bindParam(':unit', $ingredient['unit']);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>