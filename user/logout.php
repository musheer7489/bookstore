<?php
require_once '../includes/config.php';

// Unset all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Delete remember me token if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
    
    // Delete token from database
    $token = $_COOKIE['remember_token'];
    $stmt = $pdo->prepare("DELETE FROM auth_tokens WHERE token = ?");
    $stmt->execute([$token]);
}

// Redirect to home page
header('Location: ' . SITE_URL);
exit;
?>