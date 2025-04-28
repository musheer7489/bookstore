<?php
require_once '../../includes/config.php';
require_once '../admin_auth.php';


// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $review_id = intval($_GET['id']);
    
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE review_id = ?");
    if ($stmt->execute([$review_id])) {
        $_SESSION['success'] = "Review deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete review.";
    }
    
    header('Location: manage.php');
    exit;
}

// Get all reviews with book and user details
$stmt = $pdo->query("
    SELECT r.*, b.title AS book_title, u.name AS user_name
    FROM reviews r
    JOIN books b ON r.book_id = b.book_id
    JOIN users u ON r.user_id = u.user_id
    ORDER BY r.review_date DESC
");
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
$page_title = "Manage Reviews";
require_once '../admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manage Reviews</h2>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Book</th>
                                    <th>User</th>
                                    <th>Rating</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reviews as $review): ?>
                                    <tr>
                                        <td><?php echo $review['review_id']; ?></td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/products/book.php?id=<?php echo $review['book_id']; ?>">
                                                <?php echo htmlspecialchars($review['book_title']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($review['user_name']); ?></td>
                                        <td>
                                            <?php
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $review['rating']) {
                                                    echo '<i class="fas fa-star text-warning"></i>';
                                                } else {
                                                    echo '<i class="far fa-star text-warning"></i>';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($review['review_date'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary view-review" 
                                                    data-bs-toggle="modal" data-bs-target="#reviewModal"
                                                    data-comment="<?php echo htmlspecialchars($review['comment']); ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="manage.php?action=delete&id=<?php echo $review['review_id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to delete this review?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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
                <h5 class="modal-title" id="reviewModalLabel">Review Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Comment</label>
                    <div class="form-control" id="reviewComment" style="min-height: 150px;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // View review modal
    $('.view-review').click(function() {
        const comment = $(this).data('comment');
        $('#reviewComment').text(comment);
    });
});
</script>

<?php require_once '../admin_footer.php'; ?>