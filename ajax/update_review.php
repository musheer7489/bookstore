<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to update a review']);
    exit;
}

$review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// Validate input
if ($review_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid review ID']);
    exit;
}

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

// Check if review exists and belongs to the user
$stmt = $pdo->prepare("SELECT 1 FROM reviews WHERE review_id = ? AND user_id = ?");
$stmt->execute([$review_id, $_SESSION['user_id']]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Review not found or not authorized']);
    exit;
}

// Update review
$stmt = $pdo->prepare("
    UPDATE reviews 
    SET rating = ?, comment = ?, review_date = CURRENT_TIMESTAMP
    WHERE review_id = ?
");
if ($stmt->execute([$rating, $comment, $review_id])) {
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
    echo json_encode(['success' => false, 'message' => 'Failed to update review']);
}
?>