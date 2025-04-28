<?php
require_once '../includes/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../index.php');
    exit;
}

$book_id = intval($_GET['id']);

// Fetch book details
$stmt = $pdo->prepare("
    SELECT b.*, a.name AS author_name, a.bio AS author_bio, p.name AS publisher_name
    FROM books b
    JOIN authors a ON b.author_id = a.author_id
    JOIN publishers p ON b.publisher_id = p.publisher_id
    WHERE b.book_id = ?
");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: ../index.php');
    exit;
}

// Fetch categories for this book
$stmt = $pdo->prepare("
    SELECT c.category_id, c.name 
    FROM categories c
    JOIN book_categories bc ON c.category_id = bc.category_id
    WHERE bc.book_id = ?
");
$stmt->execute([$book_id]);
$categories = $stmt->fetchAll();

// Fetch reviews for this book
$stmt = $pdo->prepare("
    SELECT r.*, u.name AS user_name
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.book_id = ?
    ORDER BY r.review_date DESC
    LIMIT 5
");
$stmt->execute([$book_id]);
$reviews = $stmt->fetchAll();

// Calculate average rating
$stmt = $pdo->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count FROM reviews WHERE book_id = ?");
$stmt->execute([$book_id]);
$rating_info = $stmt->fetch();

// Check if book is in user's wishlist
$in_wishlist = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND book_id = ?");
    $stmt->execute([$_SESSION['user_id'], $book_id]);
    $in_wishlist = (bool)$stmt->fetch();
}

// Check if user has purchased this book (for review eligibility)
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

$page_title = $book['title'] . " by " . $book['author_name'];
require_once '../includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/products/">Books</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($book['title']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Book Images -->
        <div class="col-md-5">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div id="image-overlay">
                        <span class="close-button">&times;</span>
                        <img id="full-img" src="<?php echo SITE_URL; ?>/assets/images/books/<?php echo $book['isbn']; ?>.jpg" alt="<?php echo htmlspecialchars($book['title']); ?>" class="img-fluid" style="max-height: 400px;">
                    </div>
                </div>
            </div>
            <div class="row g-2 gallery">
                <div class="col-3">
                    <a href="#" class="d-block border p-1">
                        <img src="<?php echo SITE_URL; ?>/assets/images/books/<?php echo $book['isbn']; ?>_1.jpg" alt="<?php echo htmlspecialchars($book['title']); ?>" class="img-fluid">
                    </a>
                </div>
                <div class="col-3">
                    <a href="#" class="d-block border p-1">
                        <img src="<?php echo SITE_URL; ?>/assets/images/books/<?php echo $book['isbn']; ?>_2.jpg" alt="<?php echo htmlspecialchars($book['title']); ?>" class="img-fluid">
                    </a>
                </div>
                <div class="col-3">
                    <a href="#" class="d-block border p-1">
                        <img src="<?php echo SITE_URL; ?>/assets/images/books/<?php echo $book['isbn']; ?>_3.jpg" alt="<?php echo htmlspecialchars($book['title']); ?>" class="img-fluid">
                    </a>
                </div>
                <div class="col-3">
                    <a href="#" class="d-block border p-1">
                        <img src="<?php echo SITE_URL; ?>/assets/images/books/<?php echo $book['isbn']; ?>_4.jpg" alt="<?php echo htmlspecialchars($book['title']); ?>" class="img-fluid">
                    </a>
                </div>
            </div>
        </div>

        <!-- Book Details -->
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-body">
                    <h1 class="h2"><?php echo htmlspecialchars($book['title']); ?></h1>
                    <p class="text-muted">by <a href="<?php echo SITE_URL; ?>/author/<?php echo strtolower(str_replace(' ', '-', $book['author_name'])); ?>"><?php echo htmlspecialchars($book['author_name']); ?></a></p>

                    <div class="d-flex align-items-center mb-3">
                        <?php if ($rating_info['avg_rating']) : ?>
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
                            <a href="#reviews" class="text-muted">(<?php echo $rating_info['review_count']; ?> reviews)</a>
                        <?php else : ?>
                            <span class="text-muted">No reviews yet</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <?php foreach ($categories as $category) : ?>
                            <a href="<?php echo SITE_URL; ?>/products/category.php?id=<?php echo $category['category_id']; ?>" class="badge bg-light text-dark me-1">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Publisher:</strong> <?php echo htmlspecialchars($book['publisher_name']); ?></p>
                            <p><strong>Publication Date:</strong> <?php echo date('F j, Y', strtotime($book['publication_date'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?></p>
                            <p><strong>Pages:</strong> <?php echo number_format($book['pages']); ?></p>
                            <p><strong>Binding:</strong> <?php echo htmlspecialchars($book['binding_type']); ?></p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="mb-2">Description</h5>
                        <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                    </div>

                    <div class="d-flex align-items-center mb-4">
                        <div class="me-4">
                            <?php if ($book['discount_price']) : ?>
                                <h3 class="text-primary mb-0">₹<?php echo number_format($book['discount_price'], 2); ?></h3>
                                <del class="text-muted">₹<?php echo number_format($book['price'], 2); ?></del>
                                <span class="badge bg-danger ms-2"><?php echo round((($book['price'] - $book['discount_price']) / $book['price'] * 100)); ?>% OFF</span>
                            <?php else : ?>
                                <h3 class="text-primary mb-0">₹<?php echo number_format($book['price'], 2); ?></h3>
                            <?php endif; ?>
                        </div>

                        <div class="stock-status">
                            <?php if ($book['stock_quantity'] > 0) : ?>
                                <span class="text-success"><i class="fas fa-check-circle"></i> In Stock</span>
                            <?php else : ?>
                                <span class="text-danger"><i class="fas fa-times-circle"></i> Out of Stock</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <div class="input-group" style="width: 120px;">
                            <button class="btn btn-outline-secondary quantity-minus" type="button">-</button>
                            <input type="number" class="form-control text-center quantity-input" value="1" min="1" max="10">
                            <button class="btn btn-outline-secondary quantity-plus" type="button">+</button>
                        </div>

                        <button class="btn btn-primary flex-grow-1 add-to-cart" data-book-id="<?php echo $book['book_id']; ?>" <?php echo $book['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-shopping-cart me-2"></i> Add to Cart
                        </button>

                        <button class="btn btn-outline-secondary wishlist-toggle" data-book-id="<?php echo $book['book_id']; ?>" title="<?php echo $in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                            <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Author Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">About the Author</h5>
                    <div class="d-flex">
                        <div class="flex-shrink-0 me-3">
                            <img src="<?php echo SITE_URL; ?>/assets/images/authors/<?php echo strtolower(str_replace(' ', '-', $book['author_name'])); ?>.jpg" alt="<?php echo htmlspecialchars($book['author_name']); ?>" class="rounded-circle" width="80">
                        </div>
                        <div class="flex-grow-1">
                            <h6><?php echo htmlspecialchars($book['author_name']); ?></h6>
                            <p><?php echo nl2br(htmlspecialchars($book['author_bio'])); ?></p>
                            <a href="<?php echo SITE_URL; ?>/author/<?php echo strtolower(str_replace(' ', '-', $book['author_name'])); ?>" class="btn btn-sm btn-outline-primary">
                                View all books by this author
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="row" id="reviews">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Customer Reviews</h5>

                    <?php if (isset($_SESSION['user_id']) && $has_purchased) : ?>
                        <div class="mb-4">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                Write a Review
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if (count($reviews) > 0) : ?>
                        <div class="review-list">
                            <?php foreach ($reviews as $review) : ?>
                                <div class="review-item mb-4 pb-4 border-bottom">
                                    <div class="d-flex justify-content-between mb-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($review['user_name']); ?></strong>
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
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?php echo SITE_URL; ?>/products/reviews.php?book_id=<?php echo $book_id; ?>" class="btn btn-outline-primary">See All Reviews</a>
                    <?php else : ?>
                        <div class="text-center py-4">
                            <i class="fas fa-comment-alt fa-3x text-muted mb-3"></i>
                            <h5>No reviews yet</h5>
                            <p>Be the first to review this book!</p>
                            <?php if (isset($_SESSION['user_id'])) : ?>
                                <?php if ($has_purchased) : ?>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                        Write a Review
                                    </button>
                                <?php else : ?>
                                    <p class="text-muted">You must purchase this book to review it</p>
                                <?php endif; ?>
                            <?php else : ?>
                                <a href="<?php echo SITE_URL; ?>/user/login.php?return_to=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary">
                                    Login to Review
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Write a Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reviewForm" action="<?php echo SITE_URL; ?>/ajax/submit_review.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">

                    <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <div class="rating-input">
                            <input type="hidden" name="rating" id="ratingValue" value="5">
                            <div class="stars">
                                <?php for ($i = 5; $i >= 1; $i--) : ?>
                                    <i class="fas fa-star star" data-rating="<?php echo $i; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reviewComment" class="form-label">Review</label>
                        <textarea class="form-control" id="reviewComment" name="comment" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
    $(document).ready(function() {
        // Quantity controls
        $('.quantity-minus').click(function() {
            const input = $(this).siblings('.quantity-input');
            let value = parseInt(input.val());
            if (value > 1) {
                input.val(value - 1);
            }
        });

        $('.quantity-plus').click(function() {
            const input = $(this).siblings('.quantity-input');
            let value = parseInt(input.val());
            if (value < 10) {
                input.val(value + 1);
            }
        });

        // Add to cart
        $('.add-to-cart').click(function() {
            const bookId = $(this).data('book-id');
            const quantity = $('.quantity-input').val();

            $.ajax({
                url: '<?php echo SITE_URL; ?>/ajax/add_to_cart.php',
                type: 'POST',
                data: {
                    book_id: bookId,
                    quantity: quantity
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update cart count
                        $('.cart-count').text(response.cart_count);

                        // Show success message
                        const toast = $(`
                        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
                            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="toast-header bg-success text-white">
                                    <strong class="me-auto">Success</strong>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                                <div class="toast-body">
                                    Item added to cart!
                                </div>
                            </div>
                        </div>
                    `);

                        $('body').append(toast);

                        // Remove toast after 3 seconds
                        setTimeout(() => {
                            toast.remove();
                            window.location.reload();
                        }, 2000);
                    } else {
                        alert(response.message || 'Failed to add to cart');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        });

        // Wishlist toggle
        $('.wishlist-toggle').click(function() {
            const button = $(this);
            const bookId = button.data('book-id');
            const isInWishlist = button.find('i').hasClass('fas');

            $.ajax({
                url: '<?php echo SITE_URL; ?>/ajax/toggle_wishlist.php',
                type: 'POST',
                data: {
                    book_id: bookId,
                    action: isInWishlist ? 'remove' : 'add'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const icon = button.find('i');
                        if (isInWishlist) {
                            icon.removeClass('fas').addClass('far');
                            button.attr('title', 'Add to Wishlist');
                        } else {
                            icon.removeClass('far').addClass('fas');
                            button.attr('title', 'Remove from Wishlist');
                        }
                    } else {
                        alert(response.message || 'Failed to update wishlist');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        });

        // Star rating in modal
        $('.star').hover(function() {
            const rating = $(this).data('rating');
            $('.star').each(function() {
                if ($(this).data('rating') <= rating) {
                    $(this).addClass('text-warning');
                } else {
                    $(this).removeClass('text-warning');
                }
            });
        });

        $('.star').click(function() {
            const rating = $(this).data('rating');
            $('#ratingValue').val(rating);
        });

        // Review form submission
        $('#reviewForm').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: $(this).attr('action'),
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

        // Get all images within the gallery
        $('.gallery img').click(function() {
            // Get the source of the clicked image
            var imgSrc = $(this).attr('src');
            // Set the source of the image in the overlay
            $('#full-img').attr('src', imgSrc);
            // Show the overlay
            $('#image-overlay').fadeIn();
        });

        // Click event for the close button
        $('#image-overlay .close-button').click(function() {
            // Hide the overlay
            $('#image-overlay').fadeOut();
            window.location.reload();
        });

        // Optional: Close overlay by clicking outside the image
        $('#image-overlay').on('click', function(event) {
            if (event.target === this) { // 'this' refers to the overlay div
                $('#image-overlay').fadeOut();
                window.location.reload();
            }
        });
    });
</script>