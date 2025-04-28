<?php
require_once 'includes/config.php';
$page_title = "Home";
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero text-center mb-5">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">Welcome to <?php echo SITE_NAME; ?></h1>
        <p class="lead mb-4">Discover your next favorite book from our extensive collection</p>
        <a href="<?php echo SITE_URL; ?>/products/" class="btn btn-primary btn-lg px-4 me-2 mb-2">Browse Books</a>
        <a href="<?php echo SITE_URL; ?>/blog/" class="btn btn-outline-light btn-lg px-4">Read Our Blog</a>
    </div>
</section>

<!-- Featured Books -->
<section class="mb-5">
    <div class="container">
        <h2 class="text-center mb-4">Featured Books</h2>
        <div class="row">
            <?php
            $stmt = $pdo->query("SELECT b.*, a.name as author_name FROM books b JOIN authors a ON b.author_id = a.author_id WHERE b.stock_quantity > 0 ORDER BY b.created_at DESC LIMIT 4");
            while ($book = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $discount = $book['discount_price'] ? round((($book['price'] - $book['discount_price']) / $book['price']) * 100) : 0;
                ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <?php if ($discount > 0): ?>
                            <span class="badge bg-danger badge-discount"><?php echo $discount; ?>% OFF</span>
                        <?php endif; ?>
                        <img src="<?php echo SITE_URL; ?>/assets/images/books/<?php echo $book['isbn']; ?>.jpg" class="card-img-top" alt="<?php echo htmlspecialchars($book['title']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                            <p class="card-text text-muted">by <?php echo htmlspecialchars($book['author_name']); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <?php if ($book['discount_price']): ?>
                                    <span class="card-text price">₹<?php echo number_format($book['discount_price'], 2); ?></span>
                                    <span class="card-text old-price">₹<?php echo number_format($book['price'], 2); ?></span>
                                <?php else: ?>
                                    <span class="card-text price">₹<?php echo number_format($book['price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <a href="<?php echo SITE_URL; ?>/products/book.php?id=<?php echo $book['book_id']; ?>" class="btn btn-outline-primary w-100">View Details</a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
        <div class="text-center mt-3">
            <a href="<?php echo SITE_URL; ?>/products/" class="btn btn-primary">View All Books</a>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="mb-5">
    <div class="container">
        <h2 class="text-center mb-4">Browse by Category</h2>
        <div class="row">
            <?php
            $stmt = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL LIMIT 6");
            while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
                ?>
                <div class="col-md-4 mb-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <a href="<?php echo SITE_URL; ?>/products/category.php?id=<?php echo $category['category_id']; ?>" class="btn btn-sm btn-outline-primary">Explore</a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="mb-5 bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-4">What Our Customers Say</h2>
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text">"The best online bookstore I've found. Great selection and fast delivery!"</p>
                        <p class="text-muted">- Rahul Sharma</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <p class="card-text">"I love the personalized recommendations. Found so many great books I wouldn't have discovered otherwise."</p>
                        <p class="text-muted">- Priya Patel</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text">"Excellent customer service and the books always arrive in perfect condition."</p>
                        <p class="text-muted">- Amit Singh</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>