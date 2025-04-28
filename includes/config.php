<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bookstore_db');

// Site configuration
define('SITE_NAME', 'Book Haven');
define('SITE_URL', 'http://localhost/bookstore');
// Email configuration
define('SITE_EMAIL', 'noreply@bookhaven.com');
// Razorpay Configuration
define('RAZORPAY_KEY_ID', 'rzp_test_64ZQLjjTKXqotI');
define('RAZORPAY_KEY_SECRET', '3zqbJJaTOWsP4nkhBSqjD4uH');

// Create database connection
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Start session
session_start();
// Auto-login with remember token if not already logged in
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    $stmt = $pdo->prepare("SELECT u.* FROM users u JOIN auth_tokens t ON u.user_id = t.user_id WHERE t.token = ? AND t.expires_at > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        
        // Extend token expiry
        $new_expiry = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);
        $stmt = $pdo->prepare("UPDATE auth_tokens SET expires_at = ? WHERE token = ?");
        $stmt->execute([$new_expiry, $token]);
        
        setcookie('remember_token', $token, time() + 60 * 60 * 24 * 30, '/');
    }
}
?>