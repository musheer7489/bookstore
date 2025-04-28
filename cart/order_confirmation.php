<?php
require_once '../includes/config.php';

// Redirect unauthorized users
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/user/login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . SITE_URL . '/user/account.php');
    exit;
}

$order_id = intval($_GET['id']);

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) AS item_count
    FROM orders o
    WHERE o.order_id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . SITE_URL . '/user/account.php');
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

$page_title = "Order Confirmation";
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <div class="icon-circle bg-success text-white mx-auto mb-3">
                            <i class="fas fa-check fa-2x"></i>
                        </div>
                        <h2 class="mb-3">Thank You for Your Order!</h2>
                        <p class="lead">Your order has been placed successfully.</p>
                        <p class="text-muted">Order #<?php echo $order['order_id']; ?></p>
                    </div>
                    
                    <div class="row justify-content-center mb-4">
                        <div class="col-md-8">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6 text-start">
                                            <p class="mb-1"><strong>Order Date</strong></p>
                                            <p class="mb-1"><strong>Total Items</strong></p>
                                            <p class="mb-1"><strong>Payment Method</strong></p>
                                            <p class="mb-1"><strong>Order Total</strong></p>
                                        </div>
                                        <div class="col-6 text-end">
                                            <p class="mb-1"><?php echo date('F j, Y', strtotime($order['order_date'])); ?></p>
                                            <p class="mb-1"><?php echo $order['item_count']; ?></p>
                                            <p class="mb-1">
                                                <?php 
                                                switch($order['payment_method']) {
                                                    case 'cod': echo 'Cash on Delivery'; break;
                                                    case 'razorpay': echo 'Credit/Debit Card'; break;
                                                    case 'paypal': echo 'PayPal'; break;
                                                    default: echo $order['payment_method'];
                                                }
                                                ?>
                                            </p>
                                            <p class="mb-1">₹<?php echo number_format($order['total_amount'], 2); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="mb-3">Shipping Address</h5>
                        <address>
                            <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                        </address>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="<?php echo SITE_URL; ?>/user/order_details.php?id=<?php echo $order_id; ?>" class="btn btn-primary">
                            View Order Details
                        </a>
                        <a href="<?php echo SITE_URL; ?>/products/" class="btn btn-outline-primary">
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="card mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($order_items as $item): ?>
                        <div class="d-flex mb-3 pb-3 border-bottom">
                            <div class="flex-shrink-0 me-3">
                                <img src="<?php echo SITE_URL; ?>/assets/images/books/<?php echo $item['isbn']; ?>.jpg" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>" width="80">
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                <p class="small text-muted mb-1">by <?php echo htmlspecialchars($item['author_name']); ?></p>
                                <p class="mb-1">Qty: <?php echo $item['quantity']; ?></p>
                                <p class="mb-0 fw-bold">₹<?php echo number_format($item['price'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>₹<?php echo number_format(array_sum(array_map(function($item) { return $item['price'] * $item['quantity']; }, $order_items)), 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span>₹50.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (18%)</span>
                            <span>₹<?php echo number_format(($order['total_amount'] - 50) / 1.18 * 0.18, 2); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Total</span>
                            <span class="fw-bold">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>