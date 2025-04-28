<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to submit a review']);
    exit;
}

$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// Validate input
if ($book_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit;
}

if (empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Review comment is required']);
    exit;
} elseif (strlen($comment) < 10) {
    echo json_encode(['success' => false, 'message' => 'Review comment must be at least 10 characters']);
    exit;
}

// Check if book exists
$stmt = $pdo->prepare("SELECT 1 FROM books WHERE book_id = ?");
$stmt->execute([$book_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Book not found']);
    exit;
}

// Check if user has purchased this book
$stmt = $pdo->prepare("
    SELECT 1 FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.user_id = ? AND oi.book_id = ? AND o.status = 'Delivered'
");
$stmt->execute([$_SESSION['user_id'], $book_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You must purchase this book before reviewing it']);
    exit;
}

// Check if user has already reviewed this book
$stmt = $pdo->prepare("SELECT 1 FROM reviews WHERE user_id = ? AND book_id = ?");
$stmt->execute([$_SESSION['user_id'], $book_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You have already reviewed this book']);
    exit;
}

// Insert review
$stmt = $pdo->prepare("
    INSERT INTO reviews (book_id, user_id, rating, comment)
    VALUES (?, ?, ?, ?)
");
if ($stmt->execute([$book_id, $_SESSION['user_id'], $rating, $comment])) {
    // Update book average rating
    $stmt = $pdo->prepare("
        UPDATE books b
        SET average_rating = (
            SELECT AVG(rating) FROM reviews WHERE book_id = b.book_id
        )
        WHERE book_id = ?
    ");
    $stmt->execute([$book_id]);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
}
?>