<?php
require_once 'includes/config.php';

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Initialize variables
$results = [];
$total_results = 0;
$categories = [];
$authors = [];
$publishers = [];

// Process search if query is not empty
if (!empty($query)) {
    // Search books by title, author, ISBN, or description
    $stmt = $pdo->prepare("
        SELECT b.*, a.name AS author_name, p.name AS publisher_name
        FROM books b
        JOIN authors a ON b.author_id = a.author_id
        JOIN publishers p ON b.publisher_id = p.publisher_id
        WHERE (b.title LIKE :query 
               OR a.name LIKE :query 
               OR b.isbn LIKE :query 
               OR b.description LIKE :query)
        AND b.stock_quantity > 0
        ORDER BY b.title
    ");
    $stmt->execute([':query' => "%$query%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_results = count($results);

    // Get related categories
    if ($total_results > 0) {
        $book_ids = array_column($results, 'book_id');
        $placeholders = implode(',', array_fill(0, count($book_ids), '?'));
        
        $stmt = $pdo->prepare("
            SELECT c.category_id, c.name, COUNT(bc.book_id) AS book_count
            FROM categories c
            JOIN book_categories bc ON c.category_id = bc.category_id
            WHERE bc.book_id IN ($placeholders)
            GROUP BY c.category_id
            ORDER BY book_count DESC
            LIMIT 5
        ");
        $stmt->execute($book_ids);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get related authors
    $stmt = $pdo->prepare("
        SELECT a.author_id, a.name, COUNT(b.book_id) AS book_count
        FROM authors a
        JOIN books b ON a.author_id = b.author_id
        WHERE a.name LIKE :query
        GROUP BY a.author_id
        ORDER BY book_count DESC
        LIMIT 5
    ");
    $stmt->execute([':query' => "%$query%"]);
    $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get related publishers
    $stmt = $pdo->prepare("
        SELECT p.publisher_id, p.name, COUNT(b.book_id) AS book_count
        FROM publishers p
        JOIN books b ON p.publisher_id = b.publisher_id
        WHERE p.name LIKE :query
        GROUP BY p.publisher_id
        ORDER BY book_count DESC
        LIMIT 5
    ");
    $stmt->execute([':query' => "%$query%"]);
    $publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = "Search Results for \"$query\"";
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <!-- Search Filters -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Refine Search</h5>
                </div>
                <div class="card-body">
                    <form action="search.php" method="get">
                        <div class="mb-3">
                            <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search...">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </form>
                </div>
            </div>

            <!-- Related Categories -->
            <?php if (!empty($categories)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Related Categories</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <?php foreach ($categories as $category): ?>
                                <li class="mb-2">
                                    <a href="<?php echo SITE_URL; ?>/products/category.php?id=<?php echo $category['category_id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                        <span class="text-muted small">(<?php echo $category['book_count']; ?>)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Related Authors -->
            <?php if (!empty($authors)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Related Authors</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <?php foreach ($authors as $author): ?>
                                <li class="mb-2">
                                    <a href="<?php echo SITE_URL; ?>/author/<?php echo strtolower(str_replace(' ', '-', $author['name'])); ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($author['name']); ?>
                                        <span class="text-muted small">(<?php echo $author['book_count']; ?>)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Related Publishers -->
            <?php if (!empty($publishers)): ?>
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Related Publishers</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <?php foreach ($publishers as $publisher): ?>
                                <li class="mb-2">
                                    <a href="<?php echo SITE_URL; ?>/publisher/<?php echo strtolower(str_replace(' ', '-', $publisher['name'])); ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($publisher['name']); ?>
                                        <span class="text-muted small">(<?php echo $publisher['book_count']; ?>)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Search Results for "<?php echo htmlspecialchars($query); ?>"</h2>
                <span class="text-muted"><?php echo $total_results; ?> results found</span>
            </div>

            <?php if (empty($query)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4>Please enter a search term</h4>
                        <p class="text-muted">Try searching for a book title, author, or ISBN</p>
                    </div>
                </div>
            <?php elseif ($total_results === 0): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                        <h4>No results found for "<?php echo htmlspecialchars($query); ?>"</h4>
                        <p class="text-muted">Try different keywords or check out our categories</p>
                        <a href="<?php echo SITE_URL; ?>/products/" class="btn btn-primary">Browse All Books</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($results as $book): ?>
                        <div class="col-md-4 col-6 mb-4">
                            <div class="card h-100">
                                <?php if ($book['discount_price']): ?>
                                    <span class="badge bg-danger badge-discount">
                                        <?php echo round((($book['price'] - $book['discount_price']) / $book['price'] * 100)); ?>% OFF
                                    </span>
                                <?php endif; ?>
                                <a href="<?php echo SITE_URL; ?>/products/book.php?id=<?php echo $book['book_id']; ?>">
                                    <img src="<?php echo SITE_URL; ?>/assets/images/books/<?php echo $book['isbn']; ?>.jpg" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($book['title']); ?>">
                                </a>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="<?php echo SITE_URL; ?>/products/book.php?id=<?php echo $book['book_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($book['title']); ?>
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted small">by <?php echo htmlspecialchars($book['author_name']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <?php if ($book['discount_price']): ?>
                                            <span class="text-primary fw-bold">₹<?php echo number_format($book['discount_price'], 2); ?></span>
                                            <span class="text-muted small text-decoration-line-through">₹<?php echo number_format($book['price'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-primary fw-bold">₹<?php echo number_format($book['price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-top-0">
                                    <button class="btn btn-sm btn-outline-primary w-100 add-to-cart" 
                                            data-book-id="<?php echo $book['book_id']; ?>">
                                        <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination would go here if implemented -->
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add to cart functionality
    $('.add-to-cart').click(function() {
        const bookId = $(this).data('book-id');
        
        $.ajax({
            url: '<?php echo SITE_URL; ?>/ajax/add_to_cart.php',
            type: 'POST',
            data: { book_id: bookId, quantity: 1 },
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
                    }, 3000);
                } else {
                    alert(response.message || 'Failed to add to cart');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>