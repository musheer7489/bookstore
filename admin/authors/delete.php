<?php
require_once '../../includes/config.php';
require_once '../admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['author_id'])) {
    header('Location: index.php');
    exit;
}

$author_id = intval($_POST['author_id']);

// Verify user exists and is not the current admin


// Delete user (cascade deletes will handle related data)
$stmt = $pdo->prepare("DELETE FROM authors WHERE author_id = ?");
$stmt->execute([$author_id]);

$_SESSION['success_message'] = 'Author deleted successfully';
header('Location: index.php');
exit;
?>