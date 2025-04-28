<?php
require_once '../includes/config.php';
require_once 'admin_auth.php';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total books count
$stmt = $pdo->query("SELECT COUNT(*) FROM books");
$total_books = $stmt->fetchColumn();
$total_pages = ceil($total_books / $per_page);

// Get books with author and publisher info
$stmt = $pdo->prepare("
    SELECT b.*, a.name AS author_name, p.name AS publisher_name
    FROM books b
    JOIN authors a ON b.author_id = a.author_id
    JOIN publishers p ON b.publisher_id = p.publisher_id
    ORDER BY b.book_id DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$books = $stmt->fetchAll();

$page_title = "Manage Books";
require_once 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Manage Books</h2>
                <a href="add_book.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add New Book
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Publisher</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($books as $book): ?>
                                    <tr>
                                        <td><?php echo $book['book_id']; ?></td>
                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td><?php echo htmlspecialchars($book['author_name']); ?></td>
                                        <td><?php echo htmlspecialchars($book['publisher_name']); ?></td>
                                        <td>
                                            <?php if ($book['discount_price']): ?>
                                                <span class="text-primary">₹<?php echo number_format($book['discount_price'], 2); ?></span>
                                                <del class="text-muted small d-block">₹<?php echo number_format($book['price'], 2); ?></del>
                                            <?php else: ?>
                                                ₹<?php echo number_format($book['price'], 2); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="<?php echo $book['stock_quantity'] < 5 ? 'text-danger' : ''; ?>">
                                            <?php echo $book['stock_quantity']; ?>
                                        </td>
                                        <td>
                                            <?php if ($book['stock_quantity'] > 0): ?>
                                                <span class="badge bg-success">In Stock</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Out of Stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit_book.php?id=<?php echo $book['book_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger delete-book" data-id="<?php echo $book['book_id']; ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this book? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="post" action="delete_book.php">
                    <input type="hidden" name="book_id" id="deleteBookId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle delete button clicks
    $('.delete-book').click(function() {
        const bookId = $(this).data('id');
        $('#deleteBookId').val(bookId);
        $('#deleteModal').modal('show');
    });
});
</script>

<?php require_once 'admin_footer.php'; ?>