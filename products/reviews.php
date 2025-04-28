<?php
require_once '../includes/config.php';

if (!isset($_GET['book_id']) || !is_numeric($_GET['book_id'])) {
    header('Location: ' . SITE_URL . '/products/');
    exit;
}

$book_id = intval($_GET['book_id']);

// Get book details
$stmt = $pdo->prepare("
    SELECT b.*, a.name AS author_name 
    FROM books b
    JOIN authors a ON b.author_id = a.author_id
    WHERE b.book_id = ?
");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: ' . SITE_URL . '/products/');
    exit;
}

// Calculate average rating
$stmt = $pdo->prepare("
    SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count 
    FROM reviews 
    WHERE book_id = ?
");
$stmt->execute([$book_id]);
$rating_info = $stmt->fetch();

// Get all reviews for this book
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 5;
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("
    SELECT r.*, u.name AS user_name, u.user_id
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.book_id = ?
    ORDER BY r.review_date DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $book_id, PDO::PARAM_INT);
$stmt->bindValue(2, $per_page, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();

// Get total count for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE book_id = ?");
$stmt->execute([$book_id]);
$total_reviews = $stmt->fetchColumn();
$total_pages = ceil($total_reviews / $per_page);

// Check if current user has purchased this book (for review eligibility)
$has_purchased = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT 1 FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        WHERE o.user_id = ? AND oi.book_id = ? AND o.status = 'Delivered'
    ");
    $stmt->execute([$_SESSION['user_id'], $book_id]);
    $has_purchased = (bool)$stmt->fetch();
}

// Check if current user has already reviewed this book
$user_review = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE user_id = ? AND book_id = ?");
    $stmt->execute([$_SESSION['user_id'], $book_id]);
    $user_review = $stmt->fetch();
}

$page_title = "Reviews for " . $book['title'];
require_once '../includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/products/">Books</a></li>
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/products/book.php?id=<?php echo $book_id; ?>"><?php echo htmlspecialchars($book['title']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Reviews</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Customer Reviews</h2>
                <div class="d-flex align-items-center">
                    <?php if ($rating_info['avg_rating']): ?>
                        <div class="rating-stars me-2">
                            <?php
                            $full_stars = floor($rating_info['avg_rating']);
                            $half_star = ceil($rating_info['avg_rating'] - $full_stars);
                            $empty_stars = 5 - $full_stars - $half_star;
                            
                            for ($i = 0; $i < $full_stars; $i++) {
                                echo '<i class="fas fa-star text-warning"></i>';
                            }
                            if ($half_star) {
                                echo '<i class="fas fa-star-half-alt text-warning"></i>';
                            }
                            for ($i = 0; $i < $empty_stars; $i++) {
                                echo '<i class="far fa-star text-warning"></i>';
                            }
                            ?>
                        </div>
                        <span class="me-2"><?php echo number_format($rating_info['avg_rating'], 1); ?></span>
                    <?php endif; ?>
                    <span class="text-muted">(<?php echo $total_reviews; ?> reviews)</span>
                </div>
            </div>

            <!-- User Review Section -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><?php echo $user_review ? 'Your Review' : 'Write a Review'; ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if ($user_review): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <div class="rating-stars">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $user_review['rating']) {
                                                echo '<i class="fas fa-star text-warning"></i>';
                                            } else {
                                                echo '<i class="far fa-star text-warning"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                <small class="text-muted"><?php echo date('F j, Y', strtotime($user_review['review_date'])); ?></small>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($user_review['comment'])); ?></p>
                            <div class="text-end">
                                <button class="btn btn-sm btn-outline-primary edit-review-btn" 
                                        data-review-id="<?php echo $user_review['review_id']; ?>"
                                        data-rating="<?php echo $user_review['rating']; ?>"
                                        data-comment="<?php echo htmlspecialchars($user_review['comment']); ?>">
                                    <i class="fas fa-edit me-1"></i> Edit Review
                                </button>
                            </div>
                        <?php else: ?>
                            <?php if ($has_purchased): ?>
                                <form id="reviewForm">
                                    <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Rating *</label>
                                        <div class="rating-input">
                                            <input type="hidden" name="rating" id="ratingValue" value="5">
                                            <div class="stars">
                                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                                    <i class="fas fa-star star" data-rating="<?php echo $i; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reviewComment" class="form-label">Review *</label>
                                        <textarea class="form-control" id="reviewComment" name="comment" rows="5" required></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Submit Review</button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> 
                                    You must purchase this book to leave a review.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i> 
                    <a href="<?php echo SITE_URL; ?>/user/login.php?return_to=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="alert-link">Sign in</a> 
                    to leave a review.
                </div>
            <?php endif; ?>

            <!-- Reviews List -->
            <div class="reviews-list">
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                                        <div class="rating-stars">
                                            <?php
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $review['rating']) {
                                                    echo '<i class="fas fa-star text-warning"></i>';
                                                } else {
                                                    echo '<i class="far fa-star text-warning"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo date('F j, Y', strtotime($review['review_date'])); ?></small>
                                </div>
                                <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                
                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['user_id']): ?>
                                    <div class="text-end">
                                        <button class="btn btn-sm btn-outline-primary edit-review-btn" 
                                                data-review-id="<?php echo $review['review_id']; ?>"
                                                data-rating="<?php echo $review['rating']; ?>"
                                                data-comment="<?php echo htmlspecialchars($review['comment']); ?>">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Reviews pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?book_id=<?php echo $book_id; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?book_id=<?php echo $book_id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?book_id=<?php echo $book_id; ?>&page=<?php echo $page + 1; ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-comment-alt fa-3x text-muted mb-3"></i>
                            <h5>No reviews yet</h5>
                            <p class="text-muted">Be the first to review this book!</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <a href="<?php echo SITE_URL; ?>/products/book.php?id=<?php echo $book_id; ?>">
                        <img src="<?php echo SITE_URL; ?>/assets/images/books/<?php echo $book['isbn']; ?>.jpg" 
                             alt="<?php echo htmlspecialchars($book['title']); ?>" 
                             class="img-fluid mb-3" style="max-height: 300px;">
                    </a>
                    <h5>
                        <a href="<?php echo SITE_URL; ?>/products/book.php?id=<?php echo $book_id; ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($book['title']); ?>
                        </a>
                    </h5>
                    <p class="text-muted">by <?php echo htmlspecialchars($book['author_name']); ?></p>
                    
                    <div class="d-flex justify-content-center mb-3">
                        <?php if ($book['discount_price']): ?>
                            <span class="h4 text-primary me-2">₹<?php echo number_format($book['discount_price'], 2); ?></span>
                            <del class="text-muted">₹<?php echo number_format($book['price'], 2); ?></del>
                        <?php else: ?>
                            <span class="h4 text-primary">₹<?php echo number_format($book['price'], 2); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <a href="<?php echo SITE_URL; ?>/products/book.php?id=<?php echo $book_id; ?>" class="btn btn-outline-primary w-100">
                        <i class="fas fa-chevron-left me-1"></i> Back to Book
                    </a>
                </div>
            </div>
            
            <?php if ($rating_info['avg_rating']): ?>
                <div class="card mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Rating Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get rating distribution
                        $stmt = $pdo->prepare("
                            SELECT rating, COUNT(*) AS count 
                            FROM reviews 
                            WHERE book_id = ?
                            GROUP BY rating
                            ORDER BY rating DESC
                        ");
                        $stmt->execute([$book_id]);
                        $rating_distribution = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                        
                        for ($i = 5; $i >= 1; $i--):
                            $count = $rating_distribution[$i] ?? 0;
                            $percentage = $total_reviews > 0 ? ($count / $total_reviews) * 100 : 0;
                            ?>
                            <div class="row align-items-center mb-2">
                                <div class="col-2">
                                    <span class="text-muted"><?php echo $i; ?> star</span>
                                </div>
                                <div class="col-8">
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: <?php echo $percentage; ?>%" 
                                             aria-valuenow="<?php echo $percentage; ?>" 
                                             aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                <div class="col-2 text-end">
                                    <span class="text-muted small"><?php echo $count; ?></span>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Review Modal -->
<div class="modal fade" id="editReviewModal" tabindex="-1" aria-labelledby="editReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editReviewModalLabel">Edit Your Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editReviewForm">
                <input type="hidden" id="editReviewId" name="review_id">
                <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rating *</label>
                        <div class="rating-input">
                            <input type="hidden" name="rating" id="editRatingValue" value="5">
                            <div class="stars">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <i class="fas fa-star star" data-rating="<?php echo $i; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editReviewComment" class="form-label">Review *</label>
                        <textarea class="form-control" id="editReviewComment" name="comment" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Star rating functionality for new review
    $('.star').hover(function() {
        const rating = $(this).data('rating');
        $(this).parent().find('.star').each(function() {
            if ($(this).data('rating') <= rating) {
                $(this).addClass('text-warning');
            } else {
                $(this).removeClass('text-warning');
            }
        });
    });
    
    $('.star').click(function() {
        const rating = $(this).data('rating');
        $(this).parent().find('input[type="hidden"]').val(rating);
    });
    
    // Submit new review
    $('#reviewForm').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '<?php echo SITE_URL; ?>/ajax/submit_review.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload(); // Refresh to show new review
                } else {
                    alert(response.message || 'Failed to submit review');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Edit review modal
    $('.edit-review-btn').click(function() {
        const reviewId = $(this).data('review-id');
        const rating = $(this).data('rating');
        const comment = $(this).data('comment');
        
        $('#editReviewId').val(reviewId);
        $('#editRatingValue').val(rating);
        $('#editReviewComment').val(comment);
        
        // Set stars to current rating
        $('#editReviewModal .star').each(function() {
            if ($(this).data('rating') <= rating) {
                $(this).addClass('text-warning');
            } else {
                $(this).removeClass('text-warning');
            }
        });
        
        $('#editReviewModal').modal('show');
    });
    
    // Star rating functionality for edit modal
    $('#editReviewModal .star').hover(function() {
        const rating = $(this).data('rating');
        $(this).parent().find('.star').each(function() {
            if ($(this).data('rating') <= rating) {
                $(this).addClass('text-warning');
            } else {
                $(this).removeClass('text-warning');
            }
        });
    });
    
    $('#editReviewModal .star').click(function() {
        const rating = $(this).data('rating');
        $(this).parent().find('input[type="hidden"]').val(rating);
    });
    
    // Submit edited review
    $('#editReviewForm').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '<?php echo SITE_URL; ?>/ajax/update_review.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload(); // Refresh to show updated review
                } else {
                    alert(response.message || 'Failed to update review');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>