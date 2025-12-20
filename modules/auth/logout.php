<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (isLoggedIn()) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Log activity
    logActivity($db, $_SESSION['user_id'], 'logout', 'User logged out');
}

// Destroy session
session_destroy();

// Redirect to login
header("Location: " . SITE_URL .  "/modules/auth/login.php");
exit();
?>