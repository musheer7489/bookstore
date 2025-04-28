<?php
require_once '../../includes/config.php';
require_once '../admin_auth.php';

$page_title = "Manage Users";
require_once '../admin_header.php';

// Pagination setup
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search and filter inputs
$search = trim($_GET['search'] ?? '');
$role = $_GET['role'] ?? '';

// Initialize query parts
$filters = [];
$params = [];

// Search conditions
if ($search !== '') {
    $filters[] = '(name LIKE ? OR email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Role-based filter
if ($role !== '') {
    if ($role === 'admin') {
        $filters[] = 'is_admin = 1';
    } elseif ($role === 'customer') {
        $filters[] = 'is_admin = 0';
    }
}

// Base query
$base_query = "FROM users";

// Apply filters if any
if (!empty($filters)) {
    $base_query .= ' WHERE ' . implode(' AND ', $filters);
}

// Query to count total users
$count_sql = "SELECT COUNT(*) " . $base_query;
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_users = (int) $stmt->fetchColumn();
$total_pages = (int) ceil($total_users / $per_page);

// Query to fetch paginated users
$data_sql = "
    SELECT * 
    " . $base_query . "
    ORDER BY created_at DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $pdo->prepare($data_sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Now $users contains the paginated list

?>

<div class="container-fluid py-4">
    <div class="row">  
        <main class="col-md-12 ms-sm-auto col-lg-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Users</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Add New User
                    </a>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="role">
                                <option value="">All Roles</option>
                                <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="customer" <?php echo $role === 'customer' ? 'selected' : ''; ?>>Customer</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="index.php" class="btn btn-outline-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'N/A'; ?></td>
                                        <td>
                                            <span class="badge <?php echo $user['is_admin'] ? 'bg-primary' : 'bg-secondary'; ?>">
                                                <?php echo $user['is_admin'] ? 'Admin' : 'Customer'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <a href="edit.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger delete-user" data-id="<?php echo $user['user_id']; ?>">
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
                            <ul class="pagination justify-content-center mt-4">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
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
                <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                <p class="text-danger">All associated data (orders, reviews, etc.) will also be deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="post" action="delete.php">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Delete user button click
    $('.delete-user').click(function() {
        const userId = $(this).data('id');
        $('#deleteUserId').val(userId);
        $('#deleteModal').modal('show');
    });
});
</script>

<?php require_once '../admin_footer.php'; ?>