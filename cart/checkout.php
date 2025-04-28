<?php
require_once '../includes/config.php';

// Redirect unauthorized users to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . SITE_URL . '/user/login.php');
    exit;
}

// Get cart ID for the user
$stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cart = $stmt->fetch();
$cart_id = $cart ? $cart['cart_id'] : null;

// Get cart items with book details
$cart_items = [];
$subtotal = 0;
$total_items = 0;

if ($cart_id) {
    $stmt = $pdo->prepare("
        SELECT ci.*, b.isbn, b.title, b.price, b.discount_price, b.stock_quantity, 
               (SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.order_id 
                WHERE oi.book_id = b.book_id AND o.status = 'Delivered') AS sales_count
        FROM cart_items ci
        JOIN books b ON ci.book_id = b.book_id
        WHERE ci.cart_id = ?
    ");
    $stmt->execute([$cart_id]);
    $cart_items = $stmt->fetchAll();

    foreach ($cart_items as $item) {
        $price = $item['discount_price'] ? $item['discount_price'] : $item['price'];
        $subtotal += $price * $item['quantity'];
        $total_items += $item['quantity'];
    }
}

// Check if cart is empty
if ($total_items === 0) {
    header('Location: ' . SITE_URL . '/cart/view_cart.php');
    exit;
}

// Check stock availability
$out_of_stock = false;
foreach ($cart_items as $item) {
    if ($item['stock_quantity'] <= 0) {
        $out_of_stock = true;
        break;
    }
}

// Get user addresses
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC");
$stmt->execute([$_SESSION['user_id']]);
$addresses = $stmt->fetchAll();

// Process form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate shipping address
    $address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;
    $use_existing_address = isset($_POST['use_existing_address']) && $_POST['use_existing_address'] === 'yes';

    if ($use_existing_address) {
        // Validate existing address
        $valid_address = false;
        foreach ($addresses as $address) {
            if ($address['address_id'] == $address_id) {
                $valid_address = true;
                $shipping_address = [
                    'street' => $address['street'],
                    'city' => $address['city'],
                    'state' => $address['state'],
                    'country' => $address['country'],
                    'postal_code' => $address['postal_code']
                ];
                break;
            }
        }

        if (!$valid_address) {
            $errors['address'] = 'Please select a valid address';
        }
    } else {
        // Validate new address
        $street = trim($_POST['street']);
        $city = trim($_POST['city']);
        $state = trim($_POST['state']);
        $country = trim($_POST['country']);
        $postal_code = trim($_POST['postal_code']);
        $save_address = isset($_POST['save_address']) && $_POST['save_address'] === 'yes';

        if (empty($street)) {
            $errors['street'] = 'Street address is required';
        }

        if (empty($city)) {
            $errors['city'] = 'City is required';
        }

        if (empty($state)) {
            $errors['state'] = 'State is required';
        }

        if (empty($country)) {
            $errors['country'] = 'Country is required';
        }

        if (empty($postal_code)) {
            $errors['postal_code'] = 'Postal code is required';
        }

        if (empty($errors)) {
            $shipping_address = [
                'street' => $street,
                'city' => $city,
                'state' => $state,
                'country' => $country,
                'postal_code' => $postal_code
            ];

            // Save new address if requested
            if ($save_address) {
                $stmt = $pdo->prepare("
                    INSERT INTO addresses (user_id, street, city, state, country, postal_code, is_default)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                // If this is the first address, set as default
                $is_default = count($addresses) === 0 ? 1 : 0;

                $stmt->execute([
                    $_SESSION['user_id'],
                    $street,
                    $city,
                    $state,
                    $country,
                    $postal_code,
                    $is_default
                ]);
            }
        }
    }

    // Validate payment method
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    if (!in_array($payment_method, ['cod', 'razorpay', 'paypal'])) {
        $errors['payment_method'] = 'Please select a valid payment method';
    }

    // If no errors, process order
    if (empty($errors)) {
        // Calculate shipping (simple flat rate for demo)
        $shipping = 50; // Flat rate shipping
        $tax = $subtotal * 0.18; // 18% tax
        $total = $subtotal + $shipping + $tax;

        // Create order
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, total_amount, status, payment_method, payment_status, shipping_address)
            VALUES (?, ?, 'Pending', ?, 'Pending', ?)
        ");

        $shipping_address_text = implode(', ', $shipping_address);
        $stmt->execute([
            $_SESSION['user_id'],
            $total,
            $payment_method,
            $shipping_address_text
        ]);

        $order_id = $pdo->lastInsertId();

        // Add order items
        foreach ($cart_items as $item) {
            $price = $item['discount_price'] ? $item['discount_price'] : $item['price'];

            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, book_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_id,
                $item['book_id'],
                $item['quantity'],
                $price
            ]);

            // Update book sales count
            $stmt = $pdo->prepare("
                UPDATE books SET stock_quantity = stock_quantity - ? 
                WHERE book_id = ?
            ");
            $stmt->execute([
                $item['quantity'],
                $item['book_id']
            ]);
        }

        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $stmt->execute([$cart_id]);

        // Redirect to order confirmation
        header('Location: ' . SITE_URL . '/cart/order_confirmation.php?id=' . $order_id);
        exit;
    }
}

$page_title = "Checkout";
require_once '../includes/header.php';
?>
<div class="loading-overlay" style="display: none;">
  <div class="spinner"></div>
</div>
<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <h2 class="mb-4">Checkout</h2>

            <!-- Progress Steps -->
            <div class="mb-5">
                <ul class="nav nav-pills nav-justified">
                    <li class="nav-item">
                        <span class="nav-link active">
                            <i class="fas fa-shopping-cart me-2"></i> Cart
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link active">
                            <i class="fas fa-truck me-2"></i> Shipping
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled">
                            <i class="fas fa-credit-card me-2"></i> Payment
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled">
                            <i class="fas fa-check-circle me-2"></i> Confirmation
                        </span>
                    </li>
                </ul>
            </div>

            <?php if ($out_of_stock) : ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    One or more items in your cart are out of stock. Please update your cart before proceeding.
                </div>
                <a href="<?php echo SITE_URL; ?>/cart/view_cart.php" class="btn btn-primary">
                    Return to Cart
                </a>
                <?php require_once '../includes/footer.php'; ?>
                <?php exit; ?>
            <?php endif; ?>

            <!-- Shipping Address -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Shipping Address</h5>
                </div>
                <div class="card-body">
                    <form id="checkoutForm" method="post">
                        <?php if (count($addresses) > 0) : ?>
                            <div class="mb-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="use_existing_address" id="useExistingAddress" value="yes" checked>
                                    <label class="form-check-label fw-bold" for="useExistingAddress">
                                        Click on Address Below
                                    </label>
                                </div>

                                <div id="existingAddressSection">
                                    <div class="row">
                                        <?php foreach ($addresses as $address) : ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card h-100 <?php echo $address['is_default'] ? 'border-primary' : ''; ?>">
                                                    <div class="card-body">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="address_id" id="address<?php echo $address['address_id']; ?>" value="<?php echo $address['address_id']; ?>" <?php echo $address['is_default'] ? 'checked' : 'checked'; ?>>
                                                            <label class="form-check-label" for="address<?php echo $address['address_id']; ?>">
                                                                <?php if ($address['is_default']) : ?>
                                                                    <span class="badge bg-primary mb-1">Default</span>
                                                                <?php endif; ?>
                                                                <address class="mb-0">
                                                                    <?php echo htmlspecialchars($address['street']); ?><br>
                                                                    <?php echo htmlspecialchars($address['city'] . ', ' . $address['state']); ?><br>
                                                                    <?php echo htmlspecialchars($address['country'] . ' - ' . $address['postal_code']); ?>
                                                                </address>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="use_existing_address" id="useNewAddress" value="no">
                                    <label class="form-check-label fw-bold" for="useNewAddress">
                                        Use a new address
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div id="newAddressSection" <?php echo count($addresses) > 0 ? 'style="display: none;"' : ''; ?>>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="street" class="form-label">Street Address</label>
                                    <input type="text" class="form-control <?php echo isset($errors['street']) ? 'is-invalid' : ''; ?>" id="street" name="street" value="<?php echo isset($_POST['street']) ? htmlspecialchars($_POST['street']) : ''; ?>">
                                    <?php if (isset($errors['street'])) : ?>
                                        <div class="invalid-feedback"><?php echo $errors['street']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control <?php echo isset($errors['city']) ? 'is-invalid' : ''; ?>" id="city" name="city" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                                    <?php if (isset($errors['city'])) : ?>
                                        <div class="invalid-feedback"><?php echo $errors['city']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6">
                                    <label for="state" class="form-label">State</label>
                                    <input type="text" class="form-control <?php echo isset($errors['state']) ? 'is-invalid' : ''; ?>" id="state" name="state" value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>">
                                    <?php if (isset($errors['state'])) : ?>
                                        <div class="invalid-feedback"><?php echo $errors['state']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6">
                                    <label for="country" class="form-label">Country</label>
                                    <select class="form-select <?php echo isset($errors['country']) ? 'is-invalid' : ''; ?>" id="country" name="country">
                                        <option value="">Select Country</option>
                                        <option value="India" <?php echo (isset($_POST['country']) && $_POST['country'] === 'India') ? 'selected' : ''; ?>>India</option>
                                        <option value="United States">United States</option>
                                        <option value="United Kingdom">United Kingdom</option>
                                        <option value="Canada">Canada</option>
                                        <option value="Australia">Australia</option>
                                    </select>
                                    <?php if (isset($errors['country'])) : ?>
                                        <div class="invalid-feedback"><?php echo $errors['country']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6">
                                    <label for="postal_code" class="form-label">Postal Code</label>
                                    <input type="text" class="form-control <?php echo isset($errors['postal_code']) ? 'is-invalid' : ''; ?>" id="postal_code" name="postal_code" value="<?php echo isset($_POST['postal_code']) ? htmlspecialchars($_POST['postal_code']) : ''; ?>">
                                    <?php if (isset($errors['postal_code'])) : ?>
                                        <div class="invalid-feedback"><?php echo $errors['postal_code']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <?php if (count($addresses) > 0) : ?>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="saveAddress" name="save_address" value="yes">
                                            <label class="form-check-label" for="saveAddress">
                                                Save this address for future use
                                            </label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="mt-5">
                            <h5 class="mb-3">Payment Method</h5>

                            <?php if (isset($errors['payment_method'])) : ?>
                                <div class="alert alert-danger"><?php echo $errors['payment_method']; ?></div>
                            <?php endif; ?>

                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" checked>
                                        <label class="form-check-label d-flex justify-content-between align-items-center" for="cod">
                                            <span>
                                                <i class="fas fa-money-bill-wave me-2"></i> Cash on Delivery (COD)
                                            </span>
                                            <img src="<?php echo SITE_URL; ?>/assets/images/cod.png" alt="COD" height="30">
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="razorpay" value="razorpay">
                                        <label class="form-check-label d-flex justify-content-between align-items-center" for="razorpay">
                                            <span>
                                                <i class="fas fa-credit-card me-2"></i> Credit/Debit Card (Razorpay)
                                                <div id="razorpay-info" class="alert alert-info mt-3" style="display: none;">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    You will be redirected to Razorpay's secure payment page to complete your purchase.
                                                </div>
                                            </span>
                                            <img src="<?php echo SITE_URL; ?>/assets/images/razorpay.png" alt="Razorpay" height="30">
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                                        <label class="form-check-label d-flex justify-content-between align-items-center" for="paypal">
                                            <span>
                                                <i class="fab fa-paypal me-2"></i> PayPal
                                            </span>
                                            <img src="<?php echo SITE_URL; ?>/assets/images/paypal.png" alt="PayPal" height="30">
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Place Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal (<?php echo $total_items; ?> items)</span>
                        <span>₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping</span>
                        <span>₹50.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Tax (18%)</span>
                        <span>₹<?php echo number_format($subtotal * 0.18, 2); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold">₹<?php echo number_format($subtotal + 50 + ($subtotal * 0.18), 2); ?></span>
                    </div>

                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-2"></i>
                        All prices include applicable taxes. Shipping costs are estimated.
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Your Items</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($cart_items as $item) : ?>
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0 me-3">
                                <img src="<?php echo SITE_URL; ?>/assets/images/books/<?php echo $item['isbn']; ?>.jpg" alt="<?php echo htmlspecialchars($item['title']); ?>" width="60">
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                <p class="small text-muted mb-1">Qty: <?php echo $item['quantity']; ?></p>
                                <p class="mb-0">
                                    <?php if ($item['discount_price']) : ?>
                                        <span class="text-primary">₹<?php echo number_format($item['discount_price'], 2); ?></span>
                                        <del class="text-muted small">₹<?php echo number_format($item['price'], 2); ?></del>
                                    <?php else : ?>
                                        <span class="text-primary">₹<?php echo number_format($item['price'], 2); ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Toggle between existing and new address
        $('input[name="use_existing_address"]').change(function() {
            if ($(this).val() === 'yes') {
                $('#existingAddressSection').show();
                $('#newAddressSection').hide();
            } else {
                $('#existingAddressSection').hide();
                $('#newAddressSection').show();
            }
        });

        // Form validation
        $('#checkoutForm').submit(function() {
            let valid = true;

            // Validate new address if selected
            if ($('#useNewAddress').is(':checked')) {
                $('input[name="street"], input[name="city"], input[name="state"], select[name="country"], input[name="postal_code"]').each(function() {
                    if ($(this).val().trim() === '') {
                        $(this).addClass('is-invalid');
                        valid = false;
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });
            }

            return valid;
        });
        // Razorpay Payment Integration
        $('input[name="payment_method"]').change(function() {
            if ($(this).val() === 'razorpay') {
                // Show Razorpay payment info
                $('#razorpay-info').show();
            } else {
                $('#razorpay-info').hide();
            }
        });

        $('#checkoutForm').submit(function(e) {
            if ($('input[name="payment_method"]:checked').val() === 'razorpay') {
                $(".loading-overlay").css("display", "block");
                e.preventDefault();
                // Create order first
                $.ajax({
                    url: '<?php echo SITE_URL; ?>/ajax/create_order.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        $(".loading-overlay").css("display", "none");
                        if (response.success) {
                            // Initialize Razorpay
                            const options = {
                                "key": "<?php echo RAZORPAY_KEY_ID; ?>",
                                "amount": response.amount,
                                "currency": "INR",
                                "name": "<?php echo SITE_NAME; ?>",
                                "description": "Order #" + response.order_id,
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
                                            order_id: response.order_id
                                        },
                                        dataType: 'json',
                                        success: function(verifyResponse) {
                                            if (verifyResponse.success) {
                                                // Redirect to success page
                                                window.location.href = '<?php echo SITE_URL; ?>/cart/order_confirmation.php?id=' + response.order_id;
                                            } else {
                                                alert('Payment verification failed: ' + verifyResponse.message);
                                            }
                                        },
                                        error: function() {
                                            alert('Error verifying payment. Please contact support.');
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
                                        order_id: response.order_id,
                                        razorpay_payment_id: response.error.metadata.payment_id,
                                        error_code: response.error.code,
                                        error_description: response.error.description
                                    },
                                    dataType: 'json'
                                });

                                alert('Payment failed: ' + response.error.description);
                            });
                        } else {
                            alert(response.message || 'Failed to create order');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred. Please try again.' + error);
                        window.location.reload();
                    }
                });
            }
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>