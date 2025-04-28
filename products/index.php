<?php
require_once '../includes/config.php';

$page_title = "Browse All Books";
require_once '../includes/header.php';

// Get all main categories
$stmt = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");
$main_categories = $stmt->fetchAll();

// Get all authors for filter
$stmt = $pdo->query("SELECT author_id, name FROM authors ORDER BY name");
$authors = $stmt->fetchAll();

// Get all publishers for filter
$stmt = $pdo->query("SELECT publisher_id, name FROM publishers ORDER BY name");
$publishers = $stmt->fetchAll();

// Get price range
$stmt = $pdo->query("SELECT MIN(price) AS min_price, MAX(price) AS max_price FROM books WHERE stock_quantity > 0");
$price_range = $stmt->fetch();

// Get filters from query string
$filters = [
    'q' => isset($_GET['q']) ? trim($_GET['q']) : '',
    'category' => isset($_GET['category']) ? intval($_GET['category']) : null,
    'author' => isset($_GET['author']) ? intval($_GET['author']) : null,
    'publisher' => isset($_GET['publisher']) ? intval($_GET['publisher']) : null,
    'min_price' => isset($_GET['min_price']) ? floatval($_GET['min_price']) : null,
    'max_price' => isset($_GET['max_price']) ? floatval($_GET['max_price']) : null,
    'binding' => isset($_GET['binding']) ? $_GET['binding'] : null,
    'sort' => isset($_GET['sort']) ? $_GET['sort'] : 'newest',
    'page' => isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1
];

// Pagination
$per_page = 12;
$offset = ($filters['page'] - 1) * $per_page;

// Base query
$query = "
    SELECT b.*, a.name as author_name 
    FROM books b
    JOIN authors a ON b.author_id = a.author_id
    WHERE b.stock_quantity > 0
";

$params = [];

// Apply filters
if (!empty($filters['q'])) {
    $query .= " AND (b.title LIKE ? OR a.name LIKE ? OR b.isbn = ?)";
    $search_term = "%{$filters['q']}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $filters['q'];
}

if ($filters['category']) {
    $query .= " AND b.book_id IN (
        SELECT bc.book_id FROM book_categories bc 
        WHERE bc.category_id = ? OR bc.category_id IN (
            SELECT c.category_id FROM categories c WHERE c.parent_id = ?
        )
    )";
    $params[] = $filters['category'];
    $params[] = $filters['category'];
}

if ($filters['author']) {
    $query .= " AND b.author_id = ?";
    $params[] = $filters['author'];
}

if ($filters['publisher']) {
    $query .= " AND b.publisher_id = ?";
    $params[] = $filters['publisher'];
}

if ($filters['min_price']) {
    $query .= " AND (b.discount_price > 0 AND b.discount_price >= ? OR b.price >= ?)";
    $params[] = $filters['min_price'];
    $params[] = $filters['min_price'];
}

if ($filters['max_price']) {
    $query .= " AND (b.discount_price > 0 AND b.discount_price <= ? OR b.price <= ?)";
    $params[] = $filters['max_price'];
    $params[] = $filters['max_price'];
}

if ($filters['binding']) {
    $query .= " AND b.binding_type = ?";
    $params[] = $filters['binding'];
}

// Sorting
switch ($filters['sort']) {
    case 'price_asc':
        $query .= " ORDER BY (CASE WHEN b.discount_price > 0 THEN b.discount_price ELSE b.price END) ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY (CASE WHEN b.discount_price > 0 THEN b.discount_price ELSE b.price END) DESC";
        break;
    case 'title':
        $query .= " ORDER BY b.title ASC";
        break;
    case 'popular':
        $query .= " ORDER BY (SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.order_id WHERE oi.book_id = b.book_id AND o.status = 'Delivered') DESC";
        break;
    default:
        $query .= " ORDER BY b.created_at DESC";
}

// Add pagination
$query .= " LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM books b JOIN authors a ON b.author_id = a.author_id WHERE b.stock_quantity > 0";
$count_params = [];

if (!empty($filters['q'])) {
    $count_query .= " AND (b.title LIKE ? OR a.name LIKE ? OR b.isbn = ?)";
    $count_params[] = $search_term;
    $count_params[] = $search_term;
    $count_params[] = $filters['q'];
}

if ($filters['category']) {
    $count_query .= " AND b.book_id IN (
        SELECT bc.book_id FROM book_categories bc 
        WHERE bc.category_id = ? OR bc.category_id IN (
            SELECT c.category_id FROM categories c WHERE c.parent_id = ?
        )
    )";
    $count_params[] = $filters['category'];
    $count_params[] = $filters['category'];
}

if ($filters['author']) {
    $count_query .= " AND b.author_id = ?";
    $count_params[] = $filters['author'];
}

if ($filters['publisher']) {
    $count_query .= " AND b.publisher_id = ?";
    $count_params[] = $filters['publisher'];
}

if ($filters['min_price']) {
    $count_query .= " AND (b.discount_price > 0 AND b.discount_price >= ? OR b.price >= ?)";
    $count_params[] = $filters['min_price'];
    $count_params[] = $filters['min_price'];
}

if ($filters['max_price']) {
    $count_query .= " AND (b.discount_price > 0 AND b.discount_price <= ? OR b.price <= ?)";
    $count_params[] = $filters['max_price'];
    $count_params[] = $filters['max_price'];
}

if ($filters['binding']) {
    $count_query .= " AND b.binding_type = ?";
    $count_params[] = $filters['binding'];
}

$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_books = $stmt->fetchColumn();
$total_pages = ceil($total_books / $per_page);
?>

<div class="container py-5">
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Filters</h5>
                </div>
                <div class="card-body">
                    <form id="filterForm" method="get" action="">
                        <!-- Search -->
                        <div class="mb-4">
                            <h6 class="mb-3">Search</h6>
                            <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($filters['q']); ?>" placeholder="Title, author or ISBN">
                        </div>
                        
                        <!-- Categories -->
                        <div class="mb-4">
                            <h6 class="mb-3">Categories</h6>
                            <select class="form-select" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($main_categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo $filters['category'] == $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Range -->
                        <div class="mb-4">
                            <h6 class="mb-3">Price Range</h6>
                            <div class="row g-2">
                                <div class="col">
                                    <input type="number" class="form-control" name="min_price" placeholder="Min" 
                                           value="<?php echo $filters['min_price']; ?>" min="0" step="1">
                                </div>
                                <div class="col">
                                    <input type="number" class="form-control" name="max_price" placeholder="Max" 
                                           value="<?php echo $filters['max_price']; ?>" min="0" step="1">
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Range: ₹<?php echo number_format($price_range['min_price']); ?> - ₹<?php echo number_format($price_range['max_price']); ?></small>
                            </div>
                        </div>
                        
                        <!-- Authors -->
                        <div class="mb-4">
                            <h6 class="mb-3">Authors</h6>
                            <select class="form-select" name="author">
                                <option value="">All Authors</option>
                                <?php foreach ($authors as $author): ?>
                                    <option value="<?php echo $author['author_id']; ?>" <?php echo $filters['author'] == $author['author_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($author['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Publishers -->
                        <div class="mb-4">
                            <h6 class="mb-3">Publishers</h6>
                            <select class="form-select" name="publisher">
                                <option value="">All Publishers</option>
                                <?php foreach ($publishers as $publisher): ?>
                                    <option value="<?php echo $publisher['publisher_id']; ?>" <?php echo $filters['publisher'] == $publisher['publisher_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($publisher['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Binding Type -->
                        <div class="mb-4">
                            <h6 class="mb-3">Binding Type</h6>
                            <select class="form-select" name="binding">
                                <option value="">All Types</option>
                                <option value="Paperback" <?php echo $filters['binding'] === 'Paperback' ? 'selected' : ''; ?>>Paperback</option>
                                <option value="Hardcover" <?php echo $filters['binding'] === 'Hardcover' ? 'selected' : ''; ?>>Hardcover</option>
                            </select>
                        </div>
                        
                        <!-- Sort -->
                        <div class="mb-3">
                            <h6 class="mb-3">Sort By</h6>
                            <select class="form-select" name="sort">
                                <option value="newest" <?php echo $filters['sort'] === 'newest' ? 'selected' : ''; ?>>Newest Arrivals</option>
                                <option value="price_asc" <?php echo $filters['sort'] === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_desc" <?php echo $filters['sort'] === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="title" <?php echo $filters['sort'] === 'title' ? 'selected' : ''; ?>>Title: A to Z</option>
                                <option value="popular" <?php echo $filters['sort'] === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Reset Filters</a>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>All Books</h2>
                <span class="text-muted"><?php echo number_format($total_books); ?> <?php echo $total_books == 1 ? 'book' : 'books'; ?></span>
            </div>
            
            <?php if (count($books) > 0): ?>
                <div class="row">
                    <?php foreach ($books as $book): ?>
                        <div class="col-md-4 col-6 mb-4">
                            <div class="card h-100">
                                <?php if ($book['discount_price']): ?>
                                    <span class="badge bg-danger badge-discount"><?php echo round((($book['price'] - $book['discount_price']) / $book['price'] * 100)); ?>% OFF</span>
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
                                            <del class="text-muted small">₹<?php echo number_format($book['price'], 2); ?></del>
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
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($filters['page'] > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $filters['page'] - 1])); ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $filters['page'] ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($filters['page'] < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $filters['page'] + 1])); ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                        <h4>No books found</h4>
                        <p class="text-muted">Try adjusting your filters or browse our other categories</p>
                        <a href="index.php" class="btn btn-primary">Reset Filters</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add to cart
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
});
</script>

<?php require_once '../includes/footer.php'; ?>