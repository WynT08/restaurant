<?php
require_once 'config/config.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header("Location: " .  SITE_URL . "/modules/auth/login.php");
    exit();
}

// Redirect to dashboard
header("Location: " . SITE_URL . "/modules/dashboard/index.php");
exit();
?>