<?php
require_once '../includes/config.php';
require_once 'admin_auth.php';

// Get filter parameters safely
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Pagination setup
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Initialize query parts
$filters = [];
$params = [];

// Build filter conditions
if ($status_filter !== '') {
    $filters[] = 'o.status = ?';
    $params[] = $status_filter;
}

if ($date_from !== '') {
    $filters[] = 'o.order_date >= ?';
    $params[] = $date_from;
}

if ($date_to !== '') {
    $filters[] = 'o.order_date <= ?';
    $params[] = $date_to . ' 23:59:59';
}

// Base query
$base_query = "
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
";

// Apply filters if any
if (!empty($filters)) {
    $base_query .= ' WHERE ' . implode(' AND ', $filters);
}

// Query to count total records
$count_sql = "SELECT COUNT(*) " . $base_query;
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_orders = (int) $stmt->fetchColumn();
$total_pages = (int) ceil($total_orders / $per_page);

// Query to fetch paginated records
$data_sql = "
    SELECT o.*, u.name AS customer_name
    " . $base_query . "
    ORDER BY o.order_date DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $pdo->prepare($data_sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Now $orders contains the list for the current page


$page_title = "Manage Orders";
require_once 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Manage Orders</h2>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Processing" <?php echo $status_filter === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="Shipped" <?php echo $status_filter === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="Delivered" <?php echo $status_filter === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="Cancelled" <?php echo $status_filter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Filter</button>
                            <a href="orders.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
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
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                switch($order['status']) {
                                                    case 'Pending': echo 'bg-warning'; break;
                                                    case 'Processing': echo 'bg-info'; break;
                                                    case 'Shipped': echo 'bg-primary'; break;
                                                    case 'Delivered': echo 'bg-success'; break;
                                                    case 'Cancelled': echo 'bg-danger'; break;
                                                    default: echo 'bg-secondary';
                                                }
                                                ?>">
                                                <?php echo $order['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                switch($order['payment_status']) {
                                                    case 'Completed': echo 'bg-success'; break;
                                                    case 'Pending': echo 'bg-warning'; break;
                                                    case 'Failed': echo 'bg-danger'; break;
                                                    case 'Refunded': echo 'bg-info'; break;
                                                    default: echo 'bg-secondary';
                                                }
                                                ?>">
                                                <?php echo $order['payment_status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                                        id="orderActionsDropdown" data-bs-toggle="dropdown">
                                                    <i class="fas fa-cog"></i> Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <?php if ($order['status'] === 'Pending'): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="update_order_status.php?id=<?php echo $order['order_id']; ?>&status=Processing">
                                                                Mark as Processing
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($order['status'] === 'Processing'): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="update_order_status.php?id=<?php echo $order['order_id']; ?>&status=Shipped">
                                                                Mark as Shipped
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($order['status'] === 'Shipped'): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="update_order_status.php?id=<?php echo $order['order_id']; ?>&status=Delivered">
                                                                Mark as Delivered
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($order['status'] !== 'Cancelled'): ?>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="update_order_status.php?id=<?php echo $order['order_id']; ?>&status=Cancelled">
                                                                Cancel Order
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($order['payment_status'] === 'Pending' && $order['status'] !== 'Cancelled'): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="update_payment_status.php?id=<?php echo $order['order_id']; ?>&status=Completed">
                                                                Mark Payment as Completed
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
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
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>