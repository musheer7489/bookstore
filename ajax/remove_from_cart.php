<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

// Remove item from cart
$stmt = $pdo->prepare("DELETE FROM cart_items WHERE item_id = ?");
$success = $stmt->execute([$item_id]);

// Get updated cart count
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart_items WHERE cart_id IN (SELECT cart_id FROM cart WHERE user_id = ?)");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetchColumn();
} elseif (isset($_SESSION['cart_id'])) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$_SESSION['cart_id']]);
    $cart_count = $stmt->fetchColumn();
}

echo json_encode(['success' => $success, 'cart_count' => $cart_count ?: 0]);
?>