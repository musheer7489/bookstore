<?php
require_once '../includes/config.php';

// Redirect unauthorized users
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get wishlist count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_items = $stmt->fetchColumn();
$total_pages = ceil($total_items / $per_page);

// Get wishlist items
$stmt = $pdo->prepare("
    SELECT w.*, b.title, b.isbn, b.price, b.discount_price, b.stock_quantity, 
           a.name AS author_name, b.book_id
    FROM wishlist w
    JOIN books b ON w.book_id = b.book_id
    JOIN authors a ON b.author_id = a.author_id
    WHERE w.user_id = ?
    ORDER BY w.added_date DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, $per_page, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$wishlist_items = $stmt->fetchAll();

$page_title = "My Wishlist";
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <?php include 'account_nav.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>My Wishlist</h2>
                <span class="text-muted"><?php echo $total_items; ?> <?php echo $total_items == 1 ? 'item' : 'items'; ?></span>
            </div>
            
            <?php if (count($wishlist_items) > 0): ?>
                <div class="row">
                    <?php foreach ($wishlist_items as $item): ?>
                        <div class="col-md-4 col-6 mb-4">
                            <div class="card h-100">
                                <button class="btn btn-sm btn-outline-danger wishlist-remove" 
                                        data-book-id="<?php echo $item['book_id']; ?>"
                                        style="position: absolute; top: 10px; right: 10px; z-index: 1;">
                                    <i class="fas fa-times"></i>
                                </button>
                                
                                <?php if ($item['discount_price']): ?>
                                    <span class="badge bg-danger badge-discount"><?php echo round((($item['price'] - $item['discount_price']) / $item['price'] * 100)); ?>% OFF</span>
                                <?php endif; ?>
                                
                                <a href="<?php echo SITE_URL; ?>/products/book.php?id=<?php echo $item['book_id']; ?>">
                                    <img src="<?php echo SITE_URL; ?>/assets/images/books/<?php echo $item['isbn']; ?>.jpg" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                </a>
                                
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="<?php echo SITE_URL; ?>/products/book.php?id=<?php echo $item['book_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted small">by <?php echo htmlspecialchars($item['author_name']); ?></p>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <?php if ($item['discount_price']): ?>
                                            <span class="text-primary fw-bold">₹<?php echo number_format($item['discount_price'], 2); ?></span>
                                            <del class="text-muted small">₹<?php echo number_format($item['price'], 2); ?></del>
                                        <?php else: ?>
                                            <span class="text-primary fw-bold">₹<?php echo number_format($item['price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="card-footer bg-white border-top-0">
                                    <button class="btn btn-sm btn-outline-primary w-100 add-to-cart" 
                                            data-book-id="<?php echo $item['book_id']; ?>"
                                            <?php echo $item['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
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
                        <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                        <h5>Your wishlist is empty</h5>
                        <p class="text-muted">Save your favorite books here to keep track of them.</p>
                        <a href="<?php echo SITE_URL; ?>/products/" class="btn btn-primary">Browse Books</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Remove from wishlist
    $('.wishlist-remove').click(function() {
        const bookId = $(this).data('book-id');
        const card = $(this).closest('.card');
        
        if (confirm('Are you sure you want to remove this item from your wishlist?')) {
            $.ajax({
                url: '<?php echo SITE_URL; ?>/ajax/toggle_wishlist.php',
                type: 'POST',
                data: { book_id: bookId, action: 'remove' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        card.fadeOut(300, function() {
                            $(this).remove();
                            // Update wishlist count
                            const currentCount = parseInt($('.wishlist-count').text());
                            $('.wishlist-count').text(currentCount - 1);
                        });
                    } else {
                        alert(response.message || 'Failed to remove from wishlist');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        }
    });
    
    // Add to cart from wishlist
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