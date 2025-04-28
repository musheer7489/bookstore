<?php
require_once '../includes/config.php';

// Redirect unauthorized users
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/user/login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . SITE_URL . '/user/orders.php');
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
    header('Location: ' . SITE_URL . '/user/orders.php');
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

// Process cancellation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    // Validate cancellation
    if ($order['status'] === 'Pending' || $order['status'] === 'Processing') {
        try {
            $pdo->beginTransaction();

            // Update order status
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'Cancelled', 
                    cancellation_reason = ?,
                    cancelled_at = NOW()
                WHERE order_id = ?
            ");
            $stmt->execute([
                $_POST['cancellation_reason'],
                $order_id
            ]);

            // Restore stock quantities
            foreach ($order_items as $item) {
                $stmt = $pdo->prepare("
                    UPDATE books 
                    SET stock_quantity = stock_quantity + ? 
                    WHERE book_id = ?
                ");
                $stmt->execute([
                    $item['quantity'],
                    $item['book_id']
                ]);
            }

            // Initiate refund if payment was completed
            if ($order['payment_status'] === 'Completed') {
                // For Razorpay payments
                if ($order['payment_method'] === 'razorpay' && !empty($order['razorpay_payment_id'])) {
                    require_once '../vendor/autoload.php';
                    $api = new Razorpay\Api\Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

                    // Create refund
                    $refund = $api->payment->fetch($order['razorpay_payment_id'])->refund([
                        'amount' => $order['total_amount'] * 100, // in paise
                        'speed' => 'normal',
                        'notes' => [
                            'reason' => $_POST['cancellation_reason'],
                            'order_id' => $order_id
                        ]
                    ]);

                    // Update order with refund details
                    $stmt = $pdo->prepare("
                        UPDATE orders 
                        SET refund_id = ?,
                            refund_status = 'Pending',
                            refund_amount = ?,
                            refund_processed_at = NOW()
                        WHERE order_id = ?
                    ");
                    $stmt->execute([
                        $refund->id,
                        $order['total_amount'],
                        $order_id
                    ]);
                } else {
                    // For other payment methods
                    $stmt = $pdo->prepare("
                        UPDATE orders 
                        SET refund_status = 'Pending',
                            refund_amount = ?,
                            refund_processed_at = NOW()
                        WHERE order_id = ?
                    ");
                    $stmt->execute([
                        $order['total_amount'],
                        $order_id
                    ]);
                }
            }

            $pdo->commit();

            // Refresh order data
            $stmt = $pdo->prepare("
                SELECT o.*, 
                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) AS item_count
                FROM orders o
                WHERE o.order_id = ? AND o.user_id = ?
            ");
            $stmt->execute([$order_id, $_SESSION['user_id']]);
            $order = $stmt->fetch();

            $success_message = "Your order has been cancelled successfully.";
            if ($order['payment_status'] === 'Completed') {
                $success_message .= " Refund will be processed within 5-7 business days.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Failed to cancel order: " . $e->getMessage();
        }
    } else {
        $error_message = "Order cannot be cancelled at this stage.";
    }
}

$page_title = "Order Details #" . $order_id;
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Order #<?php echo $order_id; ?></h2>
                <span class="badge 
                    <?php
                    switch ($order['status']) {
                        case 'Pending':
                            echo 'bg-warning';
                            break;
                        case 'Processing':
                            echo 'bg-info';
                            break;
                        case 'Shipped':
                            echo 'bg-primary';
                            break;
                        case 'Delivered':
                            echo 'bg-success';
                            break;
                        case 'Cancelled':
                            echo 'bg-danger';
                            break;
                        default:
                            echo 'bg-secondary';
                    }
                    ?>">
                    <?php echo $order['status']; ?>
                </span>
            </div>

            <?php if (isset($success_message)) : ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php elseif (isset($error_message)) : ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></p>
                            <p><strong>Items:</strong> <?php echo $order['item_count']; ?></p>
                            <p><strong>Payment Method:</strong>
                                <?php
                                switch ($order['payment_method']) {
                                    case 'cod':
                                        echo 'Cash on Delivery';
                                        break;
                                    case 'razorpay':
                                        echo 'Credit/Debit Card (Razorpay)';
                                        break;
                                    case 'paypal':
                                        echo 'PayPal';
                                        break;
                                    default:
                                        echo $order['payment_method'];
                                }
                                ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Payment Status:</strong>
                                <span class="badge 
                                    <?php
                                    switch ($order['payment_status']) {
                                        case 'Pending':
                                            echo 'bg-warning';
                                            break;
                                        case 'Completed':
                                            echo 'bg-success';
                                            break;
                                        case 'Failed':
                                            echo 'bg-danger';
                                            break;
                                        case 'Refunded':
                                            echo 'bg-info';
                                            break;
                                        default:
                                            echo 'bg-secondary';
                                    }
                                    ?>">
                                    <?php echo $order['payment_status']; ?>
                                </span>
                            </p>
                            <?php if ($order['payment_status'] === 'Completed') : ?>
                                <p><strong>Payment Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['payment_date'])); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($order['razorpay_payment_id'])) : ?>
                                <p><strong>Transaction ID:</strong> <?php echo $order['razorpay_payment_id']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Shipping Address</h6>
                            <address>
                                <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                            </address>
                        </div>
                        <div class="col-md-6">
                            <h6>Order Total</h6>
                            <div class="d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <span>₹<?php echo number_format(array_sum(array_map(function ($item) {
                                            return $item['price'] * $item['quantity'];
                                        }, $order_items)), 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Shipping:</span>
                                <span>₹50.00</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Tax (18%):</span>
                                <span>₹<?php echo number_format(($order['total_amount'] - 50) / 1.18 * 0.18, 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total:</span>
                                <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>

                            <?php if ($order['refund_amount'] > 0) : ?>
                                <hr>
                                <div class="d-flex justify-content-between text-danger">
                                    <span>Refund Amount:</span>
                                    <span>-₹<?php echo number_format($order['refund_amount'], 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Refund Status:</span>
                                    <span class="badge 
                                        <?php
                                        switch ($order['refund_status']) {
                                            case 'Pending':
                                                echo 'bg-warning';
                                                break;
                                            case 'Processed':
                                                echo 'bg-success';
                                                break;
                                            case 'Failed':
                                                echo 'bg-danger';
                                                break;
                                            default:
                                                echo 'bg-secondary';
                                        }
                                        ?>">
                                        <?php echo $order['refund_status']; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($order_items as $item) : ?>
                        <div class="d-flex mb-3 pb-3 border-bottom">
                            <div class="flex-shrink-0 me-3">
                                <img src="<?php echo SITE_URL; ?>/assets/images/books/<?php echo $item['isbn']; ?>.jpg" alt="<?php echo htmlspecialchars($item['title']); ?>" width="80">
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <a href="<?php echo SITE_URL; ?>/products/book.php?id=<?php echo $item['book_id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </a>
                                </h6>
                                <p class="small text-muted mb-1">by <?php echo htmlspecialchars($item['author_name']); ?></p>
                                <p class="mb-1">Qty: <?php echo $item['quantity']; ?></p>
                                <p class="mb-0 fw-bold">₹<?php echo number_format($item['price'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Order Actions -->
            <?php if ($order['status'] === 'Pending' || $order['status'] === 'Processing') : ?>
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Order Actions</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($order['payment_status'] === 'Pending') : ?>
                            <button class="btn btn-primary me-2" id="payNowButton">
                                <i class="fas fa-credit-card me-1"></i> Pay Now
                            </button>
                        <?php endif; ?>

                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                            <i class="fas fa-times-circle me-1"></i> Cancel Order
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelOrderModalLabel">Cancel Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <p>Are you sure you want to cancel this order?</p>

                    <div class="mb-3">
                        <label for="cancellationReason" class="form-label">Reason for cancellation</label>
                        <select class="form-select" id="cancellationReason" name="cancellation_reason" required>
                            <option value="">Select a reason</option>
                            <option value="Changed mind">Changed mind</option>
                            <option value="Found better price">Found better price</option>
                            <option value="Shipping too slow">Shipping too slow</option>
                            <option value="Ordered by mistake">Ordered by mistake</option>
                            <option value="Other">Other (please specify)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="otherReasonContainer" style="display: none;">
                        <label for="otherReason" class="form-label">Please specify</label>
                        <textarea class="form-control" id="otherReason" name="other_reason" rows="3"></textarea>
                    </div>

                    <?php if ($order['payment_status'] === 'Completed') : ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Since payment has already been processed, a refund will be initiated upon cancellation.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="cancel_order" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Show/hide other reason field
        $('#cancellationReason').change(function() {
            if ($(this).val() === 'Other') {
                $('#otherReasonContainer').show();
                $('#otherReason').prop('required', true);
            } else {
                $('#otherReasonContainer').hide();
                $('#otherReason').prop('required', false);
            }
        });

        // Handle form submission
        $('form').submit(function() {
            if ($('#cancellationReason').val() === 'Other' && $('#otherReason').val().trim() === '') {
                alert('Please specify your reason for cancellation');
                return false;
            }
            return true;
        });

        //Payment
        // Razorpay Payment for Pending Orders
        $('#payNowButton').click(function() {
            // Show loading state
            const button = $(this);
            button.prop('disabled', true);
            button.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processing...');

            $.ajax({
                url: '<?php echo SITE_URL; ?>/ajax/create_payment.php',
                type: 'POST',
                data: {
                    order_id: <?php echo $order_id; ?>
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Initialize Razorpay
                        const options = {
                            "key": "<?php echo RAZORPAY_KEY_ID; ?>",
                            "amount": response.amount,
                            "currency": "INR",
                            "name": "<?php echo SITE_NAME; ?>",
                            "description": "Payment for Order #<?php echo $order_id; ?>",
                            "image": "<?php echo SITE_URL; ?>/assets/images/logo.png",
                            "order_id": response.razorpay_order_id,
                            "handler": function(razorpayResponse) {
                                // Verify payment on server
                                $.ajax({
                                    url: '<?php echo SITE_URL; ?>/ajax/verify_payment.php',
                                    type: 'POST',
                                    data: {
                                        razorpay_order_id: razorpayResponse.razorpay_order_id,
                                        razorpay_payment_id: razorpayResponse.razorpay_payment_id,
                                        razorpay_signature: razorpayResponse.razorpay_signature,
                                        order_id: <?php echo $order_id; ?>
                                    },
                                    dataType: 'json',
                                    success: function(verifyResponse) {
                                        if (verifyResponse.success) {
                                            // Refresh page to show updated status
                                            window.location.reload(); 
                                        } else {
                                            alert('Payment verification failed: ' + verifyResponse.message);
                                            button.prop('disabled', false);
                                            button.html('<i class="fas fa-credit-card me-1"></i> Pay Now');
                                        }
                                    },
                                    error: function() {
                                        alert('Error verifying payment. Please contact support.');
                                        button.prop('disabled', false);
                                        button.html('<i class="fas fa-credit-card me-1"></i> Pay Now');
                                        window.location.reload();
                                    }
                                });
                            },
                            "prefill": {
                                "name": "<?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : ''; ?>",
                                "email": "<?php echo isset($_SESSION['user_email']) ? $_SESSION['user_email'] : ''; ?>"
                            },
                            "theme": {
                                "color": "#3399cc"
                            }
                        };

                        const rzp = new Razorpay(options);
                        rzp.open();

                        rzp.on('payment.failed', function(response) {
                            // Update order status to failed
                            $.ajax({
                                url: '<?php echo SITE_URL; ?>/ajax/payment_failed.php',
                                type: 'POST',
                                data: {
                                    order_id: <?php echo $order_id; ?>,
                                    razorpay_payment_id: response.error.metadata.payment_id,
                                    error_code: response.error.code,
                                    error_description: response.error.description
                                },
                                dataType: 'json'
                            });

                            alert('Payment failed: ' + response.error.description);
                            button.prop('disabled', false);
                            button.html('<i class="fas fa-credit-card me-1"></i> Pay Now');
                        });
                    } else {
                        alert(response.message || 'Failed to initialize payment');
                        button.prop('disabled', false);
                        button.html('<i class="fas fa-credit-card me-1"></i> Pay Now');
                    }
                },
                error: function() {
                    //alert('An error occurred. Please try again.');
                    button.prop('disabled', false);
                    button.html('<i class="fas fa-credit-card me-1"></i> Pay Now');
                    window.location.reload();
                }
            });
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>