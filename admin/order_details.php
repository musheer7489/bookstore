<?php
require_once '../includes/config.php';
require_once 'admin_auth.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = intval($_GET['id']);

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.name AS customer_name, u.email AS customer_email
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, b.title, b.isbn, a.name AS author_name
    FROM order_items oi
    JOIN books b ON oi.book_id = b.book_id
    JOIN authors a ON b.author_id = a.author_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Calculate subtotal
$subtotal = array_sum(array_map(function($item) { 
    return $item['price'] * $item['quantity']; 
}, $order_items));

$page_title = "Order #" . $order['order_id'];
require_once 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Order #<?php echo $order['order_id']; ?></h2>
                <a href="orders.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Orders
                </a>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Order Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-2"><strong>Order Date:</strong></p>
                            <p class="mb-2"><strong>Customer:</strong></p>
                            <p class="mb-2"><strong>Email:</strong></p>
                            <p class="mb-2"><strong>Payment Method:</strong></p>
                            <p class="mb-0"><strong>Payment Status:</strong></p>
                        </div>
                        <div class="col-6">
                            <p class="mb-2"><?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></p>
                            <p class="mb-2"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p class="mb-2"><?php echo htmlspecialchars($order['customer_email']); ?></p>
                            <p class="mb-2">
                                <?php 
                                switch($order['payment_method']) {
                                    case 'cod': echo 'Cash on Delivery'; break;
                                    case 'razorpay': echo 'Credit/Debit Card'; break;
                                    case 'paypal': echo 'PayPal'; break;
                                    default: echo $order['payment_method'];
                                }
                                ?>
                            </p>
                            <p class="mb-0">
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
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Shipping Information</h6>
                </div>
                <div class="card-body">
                    <address><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></address>
                    
                    <div class="mt-3">
                        <h6 class="font-weight-bold">Order Status</h6>
                        <div class="d-flex align-items-center">
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
                                ?> me-3">
                                <?php echo $order['status']; ?>
                            </span>
                            
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                        id="statusDropdown" data-bs-toggle="dropdown">
                                    Update Status
                                </button>
                                <ul class="dropdown-menu">
                                    <?php if ($order['status'] !== 'Pending'): ?>
                                        <li>
                                            <a class="dropdown-item" href="update_order_status.php?id=<?php echo $order['order_id']; ?>&status=Pending">
                                                Mark as Pending
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['status'] !== 'Processing'): ?>
                                        <li>
                                            <a class="dropdown-item" href="update_order_status.php?id=<?php echo $order['order_id']; ?>&status=Processing">
                                                Mark as Processing
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['status'] !== 'Shipped'): ?>
                                        <li>
                                            <a class="dropdown-item" href="update_order_status.php?id=<?php echo $order['order_id']; ?>&status=Shipped">
                                                Mark as Shipped
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['status'] !== 'Delivered'): ?>
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
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Order Items</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <img src="<?php echo SITE_URL; ?>/assets/images/books/<?php echo $item['isbn']; ?>.jpg" 
                                                         alt="<?php echo htmlspecialchars($item['title']); ?>" width="60">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                                    <p class="small text-muted mb-0">by <?php echo htmlspecialchars($item['author_name']); ?></p>
                                                    <p class="small mb-0">ISBN: <?php echo htmlspecialchars($item['isbn']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <div class="table-responsive">
                                <table class="table">
                                    <tbody>
                                        <tr>
                                            <td><strong>Subtotal:</strong></td>
                                            <td class="text-end">₹<?php echo number_format($subtotal, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Shipping:</strong></td>
                                            <td class="text-end">₹50.00</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tax (18%):</strong></td>
                                            <td class="text-end">₹<?php echo number_format(($order['total_amount'] - $subtotal - 50), 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total:</strong></td>
                                            <td class="text-end">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>