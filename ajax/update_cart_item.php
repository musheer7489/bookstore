<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$quantity = isset($_POST['quantity']) ? max(1, min(10, intval($_POST['quantity']))) : 1;

if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

// Check if item exists in cart
$stmt = $pdo->prepare("
    SELECT ci.quantity, b.stock_quantity 
    FROM cart_items ci
    JOIN books b ON ci.book_id = b.book_id
    WHERE ci.item_id = ?
");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
    exit;
}

// Check stock availability
if ($quantity > $item['stock_quantity']) {
    echo json_encode([
        'success' => false, 
        'message' => 'Only ' . $item['stock_quantity'] . ' items available in stock'
    ]);
    exit;
}

// Update quantity
$stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE item_id = ?");
$success = $stmt->execute([$quantity, $item_id]);

echo json_encode(['success' => $success]);
?>