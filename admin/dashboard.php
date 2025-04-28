<?php
require_once '../includes/config.php';
require_once 'admin_auth.php';

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$total_users = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM books");
$total_books = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$total_orders = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'Delivered'");
$total_revenue = $stmt->fetchColumn();

// Get recent orders
$stmt = $pdo->query("
    SELECT o.order_id, o.order_date, o.total_amount, o.status, u.name AS customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    ORDER BY o.order_date DESC
    LIMIT 5
");
$recent_orders = $stmt->fetchAll();

// Get low stock books
$stmt = $pdo->query("
    SELECT b.book_id, b.title, b.stock_quantity
    FROM books b
    WHERE b.stock_quantity < 10
    ORDER BY b.stock_quantity ASC
    LIMIT 5
");
$low_stock_books = $stmt->fetchAll();

$page_title = "Admin Dashboard";
require_once 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Dashboard Overview</h2>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Books</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_books; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_orders; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-bag fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format($total_revenue, 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-rupee-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                    <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
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
                                            <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
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
    
    <!-- Low Stock Books -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-danger">Low Stock Books</h6>
                    <a href="../admin/books.php" class="btn btn-sm btn-danger">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Book ID</th>
                                    <th>Title</th>
                                    <th>Stock Quantity</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_books as $book): ?>
                                    <tr>
                                        <td><?php echo $book['book_id']; ?></td>
                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td class="<?php echo $book['stock_quantity'] < 5 ? 'text-danger' : 'text-warning'; ?>">
                                            <?php echo $book['stock_quantity']; ?>
                                        </td>
                                        <td>
                                            <a href="edit_book.php?id=<?php echo $book['book_id']; ?>" class="btn btn-sm btn-outline-primary">Restock</a>
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

<?php require_once 'admin_footer.php'; ?>