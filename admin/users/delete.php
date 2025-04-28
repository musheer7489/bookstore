<?php
require_once '../../includes/config.php';
require_once '../admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = intval($_POST['user_id']);

// Verify user exists and is not the current admin
$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error_message'] = 'User not found';
    header('Location: index.php');
    exit;
}

if ($user['is_admin'] && $user_id == $_SESSION['user_id']) {
    $_SESSION['error_message'] = 'You cannot delete your own admin account';
    header('Location: index.php');
    exit;
}

// Delete user (cascade deletes will handle related data)
$stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);

$_SESSION['success_message'] = 'User deleted successfully';
header('Location: index.php');
exit;
?>