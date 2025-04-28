<?php
require_once '../../includes/config.php';
require_once '../admin_auth.php';

$page_title = "Manage Categories";
require_once '../admin_header.php';

// Get all categories with parent category names
$stmt = $pdo->query("
    SELECT c.*, p.name AS parent_name 
    FROM categories c
    LEFT JOIN categories p ON c.parent_id = p.category_id
    ORDER BY c.parent_id IS NULL DESC, c.parent_id, c.name
");
$categories = $stmt->fetchAll();

$page_title = "Manage Categories";
require_once '../admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">        
        <main class="col-md-12 ms-sm-auto col-lg-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Categories</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Add New Category
                    </a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message']; ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Parent Category</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo $category['category_id']; ?></td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo $category['parent_name'] ? htmlspecialchars($category['parent_name']) : 'None'; ?></td>
                                        <td><?php echo $category['description'] ? htmlspecialchars($category['description']) : 'N/A'; ?></td>
                                        <td>
                                            <a href="edit.php?id=<?php echo $category['category_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger delete-category" 
                                                    data-id="<?php echo $category['category_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($category['name']); ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the category "<span id="categoryName"></span>"?</p>
                <p class="text-danger">This will also delete all subcategories and remove this category from all books.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="post" action="delete.php">
                    <input type="hidden" name="category_id" id="deleteCategoryId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Delete category button click
    $('.delete-category').click(function() {
        const categoryId = $(this).data('id');
        const categoryName = $(this).data('name');
        
        $('#deleteCategoryId').val(categoryId);
        $('#categoryName').text(categoryName);
        $('#deleteModal').modal('show');
    });
});
</script>

<?php require_once '../admin_footer.php'; ?>