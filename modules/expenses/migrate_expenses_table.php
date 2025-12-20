<?php
// One-time helper to create expenses table if missing
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();
requirePermission('manager');

$database = new Database();
$db = $database->getConnection();

try {
    $db->exec("CREATE TABLE IF NOT EXISTS expenses (
        expense_id INT AUTO_INCREMENT PRIMARY KEY,
        expense_type VARCHAR(50) NOT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        description TEXT,
        expense_date DATE NOT NULL,
        payment_method VARCHAR(50) DEFAULT NULL,
        receipt_image VARCHAR(255) DEFAULT NULL,
        recorded_by INT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_expenses_user FOREIGN KEY (recorded_by) REFERENCES users(user_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Expenses table ready.";
} catch (Exception $e) {
    echo "Failed to create expenses table: " . $e->getMessage();
}
