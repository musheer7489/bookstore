<?php
// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Check admin permissions if needed
// Example: if (!hasPermission('view_dashboard')) { ... }
?>