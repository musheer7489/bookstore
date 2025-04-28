<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
$quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;

if ($book_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

// Check if book exists and is in stock
$stmt = $pdo->prepare("SELECT stock_quantity FROM books WHERE book_id = ?");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    echo json_encode(['success' => false, 'message' => 'Book not found']);
    exit;
}

if ($book['stock_quantity'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'This book is out of stock']);
    exit;
}

// Get or create cart
$cart_id = null;
if (isset($_SESSION['user_id'])) {
    // For logged-in users, use their permanent cart
    $stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart = $stmt->fetch();
    
    if ($cart) {
        $cart_id = $cart['cart_id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_id = $pdo->lastInsertId();
    }
} else {
    // For guests, use session-based cart
    if (isset($_SESSION['cart_id'])) {
        $cart_id = $_SESSION['cart_id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO cart (session_id) VALUES (?)");
        $stmt->execute([session_id()]);
        $cart_id = $pdo->lastInsertId();
        $_SESSION['cart_id'] = $cart_id;
    }
}

// Check if item already exists in cart
$stmt = $pdo->prepare("SELECT item_id, quantity FROM cart_items WHERE cart_id = ? AND book_id = ?");
$stmt->execute([$cart_id, $book_id]);
$existing_item = $stmt->fetch();

if ($existing_item) {
    // Update quantity
    $new_quantity = $existing_item['quantity'] + $quantity;
    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE item_id = ?");
    $stmt->execute([$new_quantity, $existing_item['item_id']]);
} else {
    // Add new item
    $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, book_id, quantity) VALUES (?, ?, ?)");
    $stmt->execute([$cart_id, $book_id, $quantity]);
}

// Get updated cart count
$stmt = $pdo->prepare("SELECT SUM(quantity) AS total_items FROM cart_items WHERE cart_id = ?");
$stmt->execute([$cart_id]);
$cart_count = $stmt->fetchColumn();

echo json_encode(['success' => true, 'cart_count' => $cart_count ?: 0]);
?>