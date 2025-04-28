<?php
require_once '../includes/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../index.php');
    exit;
}

$category_id = intval($_GET['id']);

// Fetch category details
$stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: ../index.php');
    exit;
}

// Get all parent categories for breadcrumb
$breadcrumbs = [];
$current_category = $category;
while ($current_category) {
    $breadcrumbs[] = $current_category;
    if ($current_category['parent_id']) {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
        $stmt->execute([$current_category['parent_id']]);
        $current_category = $stmt->fetch();
    } else {
        $current_category = null;
    }
}
$breadcrumbs = array_reverse($breadcrumbs);

// Get subcategories
$stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ?");
$stmt->execute([$category_id]);
$subcategories = $stmt->fetchAll();

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get books in this category
$stmt = $pdo->prepare("
    SELECT b.*, a.name as author_name 
    FROM books b
    JOIN book_categories bc ON b.book_id = bc.book_id
    JOIN authors a ON b.author_id = a.author_id
    WHERE bc.category_id = ? AND b.stock_quantity > 0
    ORDER BY b.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $category_id, PDO::PARAM_INT);
$stmt->bindValue(2, $per_page, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$books = $stmt->fetchAll();

// Get total count for pagination
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM books b
    JOIN book_categories bc ON b.book_id = bc.book_id
    WHERE bc.category_id = ? AND b.stock_quantity > 0
");
$stmt->execute([$category_id]);
$total_books = $stmt->fetchColumn();
$total_pages = ceil($total_books / $per_page);

// Get filters from query string
$filters = [
    'min_price' => isset($_GET['min_price']) ? floatval($_GET['min_price']) : null,
    'max_price' => isset($_GET['max_price']) ? floatval($_GET['max_price']) : null,
    'author' => isset($_GET['author']) ? intval($_GET['author']) : null,
    'publisher' => isset($_GET['publisher']) ? intval($_GET['publisher']) : null,
    'binding' => isset($_GET['binding']) ? $_GET['binding'] : null,
    'sort' => isset($_GET['sort']) ? $_GET['sort'] : 'newest'
];

// Apply filters if any
if ($filters['min_price'] || $filters['max_price'] || $filters['author'] || $filters['publisher'] || $filters['binding']) {
    $query = "
        SELECT b.*, a.name as author_name 
        FROM books b
        JOIN book_categories bc ON b.book_id = bc.book_id
        JOIN authors a ON b.author_id = a.author_id
        WHERE bc.category_id = ? AND b.stock_quantity > 0
    ";
    
    $params = [$category_id];
    
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
    
    if ($filters['author']) {
        $query .= " AND b.author_id = ?";
        $params[] = $filters['author'];
    }
    
    if ($filters['publisher']) {
        $query .= " AND b.publisher_id = ?";
        $params[] = $filters['publisher'];
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
    
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $books = $stmt->fetchAll();
    
    // Get filtered count
    $count_query = "
        SELECT COUNT(*) 
        FROM books b
        JOIN book_categories bc ON b.book_id = bc.book_id
        WHERE bc.category_id = ? AND b.stock_quantity > 0
    ";
    
    $count_params = [$category_id];
    
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
    
    if ($filters['author']) {
        $count_query .= " AND b.author_id = ?";
        $count_params[] = $filters['author'];
    }
    
    if ($filters['publisher']) {
        $count_query .= " AND b.publisher_id = ?";
        $count_params[] = $filters['publisher'];
    }
    
    if ($filters['binding']) {
        $count_query .= " AND b.binding_type = ?";
        $count_params[] = $filters['binding'];
    }
    
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($count_params);
    $total_books = $stmt->fetchColumn();
    $total_pages = ceil($total_books / $per_page);
}

// Get authors in this category for filter
$stmt = $pdo->prepare("
    SELECT DISTINCT a.author_id, a.name
    FROM authors a
    JOIN books b ON a.author_id = b.author_id
    JOIN book_categories bc ON b.book_id = bc.book_id
    WHERE bc.category_id = ?
    ORDER BY a.name
");
$stmt->execute([$category_id]);
$authors = $stmt->fetchAll();

// Get publishers in this category for filter
$stmt = $pdo->prepare("
    SELECT DISTINCT p.publisher_id, p.name
    FROM publishers p
    JOIN books b ON p.publisher_id = b.publisher_id
    JOIN book_categories bc ON b.book_id = bc.book_id
    WHERE bc.category_id = ?
    ORDER BY p.name
");
$stmt->execute([$category_id]);
$publishers = $stmt->fetchAll();

// Get price range for filter
$stmt = $pdo->prepare("
    SELECT 
        MIN(IF(b.discount_price > 0, b.discount_price, b.price)) AS min_price,
        MAX(IF(b.discount_price > 0, b.discount_price, b.price)) AS max_price
    FROM books b
    JOIN book_categories bc ON b.book_id = bc.book_id
    WHERE bc.category_id = ? AND b.stock_quantity > 0
");
$stmt->execute([$category_id]);
$price_range = $stmt->fetch();

$page_title = $category['name'] . " Books";
require_once '../includes/header.php';
?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/products/">Books</a></li>
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index < count($breadcrumbs) - 1): ?>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/products/category.php?id=<?php echo $crumb['category_id']; ?>"><?php echo htmlspecialchars($crumb['name']); ?></a></li>
                <?php else: ?>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($crumb['name']); ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
    
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Filters</h5>
                </div>
                <div class="card-body">
                    <form id="filterForm">
                        <input type="hidden" name="id" value="<?php echo $category_id; ?>">
                        
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
                                <small class="text-muted">Current range: ₹<?php echo number_format($price_range['min_price']); ?> - ₹<?php echo number_format($price_range['max_price']); ?></small>
                            </div>
                        </div>
                        
                        <!-- Authors -->
                        <?php if (count($authors) > 0): ?>
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
                        <?php endif; ?>
                        
                        <!-- Publishers -->
                        <?php if (count($publishers) > 0): ?>
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
                        <?php endif; ?>
                        
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
                        <a href="category.php?id=<?php echo $category_id; ?>" class="btn btn-outline-secondary w-100 mt-2">Reset Filters</a>
                    </form>
                </div>
            </div>
            
            <!-- Subcategories -->
            <?php if (count($subcategories) > 0): ?>
                <div class="card mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Subcategories</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <?php foreach ($subcategories as $subcategory): ?>
                                <li class="mb-2">
                                    <a href="category.php?id=<?php echo $subcategory['category_id']; ?>" class="text-decoration-none">
                                        <i class="fas fa-folder-open me-2 text-primary"></i>
                                        <?php echo htmlspecialchars($subcategory['name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><?php // echo htmlspecialchars($category['name']); ?></h2>
                <div class="text-muted">
                    <?php echo number_format($total_books); ?> <?php echo $total_books == 1 ? 'book' : 'books'; ?>
                </div>
            </div>
            
            <?php if (!empty($category['description'])): ?>
                <div class="mb-4">
                    <p><?php echo nl2br(htmlspecialchars($category['description'])); ?></p>
                </div>
            <?php endif; ?>
            
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
                                            <span class="text-muted text-decoration-line-through small">₹<?php echo number_format($book['price'], 2); ?></span>
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
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?id=<?php echo $category_id; ?>&page=<?php echo $page - 1; ?><?php echo http_build_query($filters); ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?id=<?php echo $category_id; ?>&page=<?php echo $i; ?><?php echo http_build_query($filters); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?id=<?php echo $category_id; ?>&page=<?php echo $page + 1; ?><?php echo http_build_query($filters); ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                    <h4>No books found</h4>
                    <p class="text-muted">Try adjusting your filters or browse our other categories</p>
                    <a href="<?php echo SITE_URL; ?>/products/" class="btn btn-primary">Browse All Books</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add to cart from category page
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
    
    // Filter form submission
    $('#filterForm').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        window.location.href = `category.php?id=<?php echo $category_id; ?>?${formData}`;
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>