<?php
require_once '../../includes/config.php';
require_once '../admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['category_id'])) {
    header('Location: index.php');
    exit;
}

$category_id = intval($_POST['category_id']);

// Verify category exists
$stmt = $pdo->prepare("SELECT 1 FROM categories WHERE category_id = ?");
$stmt->execute([$category_id]);

if (!$stmt->fetch()) {
    $_SESSION['error_message'] = 'Category not found';
    header('Location: index.php');
    exit;
}

// Delete category (cascade deletes will handle subcategories and book relationships)
$stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
$stmt->execute([$category_id]);

$_SESSION['success_message'] = 'Category deleted successfully';
header('Location: index.php');
exit;
?>