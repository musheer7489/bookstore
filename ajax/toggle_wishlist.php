<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to use the wishlist']);
    exit;
}

$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($book_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

// Check if book exists
$stmt = $pdo->prepare("SELECT 1 FROM books WHERE book_id = ?");
$stmt->execute([$book_id]);
$book_exists = $stmt->fetch();

if (!$book_exists) {
    echo json_encode(['success' => false, 'message' => 'Book not found']);
    exit;
}

if ($action === 'add') {
    // Check if already in wishlist
    $stmt = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND book_id = ?");
    $stmt->execute([$_SESSION['user_id'], $book_id]);
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, book_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $book_id]);
    }
    
    echo json_encode(['success' => true]);
} elseif ($action === 'remove') {
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND book_id = ?");
    $stmt->execute([$_SESSION['user_id'], $book_id]);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>