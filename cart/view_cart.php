<?php
require_once '../includes/config.php';

// Get cart ID based on user status
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart = $stmt->fetch();
    $cart_id = $cart ? $cart['cart_id'] : null;
} elseif (isset($_SESSION['cart_id'])) {
    $cart_id = $_SESSION['cart_id'];
} else {
    $cart_id = null;
}

// Get cart items with book details
$cart_items = [];
$subtotal = 0;
$total_items = 0;

if ($cart_id) {
    $stmt = $pdo->prepare("
        SELECT ci.*, b.title, b.isbn, b.price, b.discount_price, b.stock_quantity, 
               a.name AS author_name, b.book_id
        FROM cart_items ci
        JOIN books b ON ci.book_id = b.book_id
        JOIN authors a ON b.author_id = a.author_id
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

$page_title = "Your Shopping Cart";
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Shopping Cart</h2>
                <span class="text-muted"><?php echo $total_items; ?> <?php echo $total_items == 1 ? 'item' : 'items'; ?></span>
            </div>

            <?php if (count($cart_items) > 0): ?>
                <div class="card mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th scope="col">Product</th>
                                        <th scope="col">Price</th>
                                        <th scope="col">Quantity</th>
                                        <th scope="col">Total</th>
                                        <th scope="col"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                        <?php 
                                        $price = $item['discount_price'] ? $item['discount_price'] : $item['price'];
                                        $item_total = $price * $item['quantity'];
                                        ?>
                                        <tr class="border-bottom">
                                            <td>
                                                <div class="d-flex">
                                                    <a href="<?php echo SITE_URL; ?>/products/book.php?id=<?php echo $item['book_id']; ?>">
                                                        <img src="<?php echo SITE_URL; ?>/assets/images/books/<?php echo $item['isbn']; ?>.jpg" 
                                                             alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                                             width="80" class="me-3">
                                                    </a>
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <a href="<?php echo SITE_URL; ?>/products/book.php?id=<?php echo $item['book_id']; ?>" class="text-decoration-none">
                                                                <?php echo htmlspecialchars($item['title']); ?>
                                                            </a>
                                                        </h6>
                                                        <p class="small text-muted mb-0">by <?php echo htmlspecialchars($item['author_name']); ?></p>
                                                        <?php if ($item['stock_quantity'] <= 0): ?>
                                                            <span class="badge bg-danger">Out of Stock</span>
                                                        <?php elseif ($item['stock_quantity'] < $item['quantity']): ?>
                                                            <span class="badge bg-warning text-dark">Only <?php echo $item['stock_quantity']; ?> available</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($item['discount_price']): ?>
                                                    <span class="text-primary">₹<?php echo number_format($item['discount_price'], 2); ?></span>
                                                    <del class="text-muted small d-block">₹<?php echo number_format($item['price'], 2); ?></del>
                                                <?php else: ?>
                                                    <span class="text-primary">₹<?php echo number_format($item['price'], 2); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="input-group" style="width: 120px;">
                                                    <button class="btn btn-outline-secondary btn-sm quantity-minus update-cart"
                                                            type="button" data-item-id="<?php echo $item['item_id']; ?>">-</button>
                                                    <input type="number" class="form-control form-control-sm text-center quantity-input" 
                                                           value="<?php echo $item['quantity']; ?>" min="1" max="10" 
                                                           data-item-id="<?php echo $item['item_id']; ?>">
                                                    <button class="btn btn-outline-secondary btn-sm quantity-plus update-cart"
                                                            type="button" data-item-id="<?php echo $item['item_id']; ?>">+</button>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fw-bold">₹<?php echo number_format($item_total, 2); ?></span>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-danger remove-item" 
                                                        data-item-id="<?php echo $item['item_id']; ?>">
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

                <div class="d-flex justify-content-between mb-4">
                    <a href="<?php echo SITE_URL; ?>/products/" class="btn btn-outline-primary">
                        <i class="fas fa-chevron-left me-1"></i> Continue Shopping
                    </a>
                    <button class="btn btn-primary" id="update-cart">
                        <i class="fas fa-sync-alt me-1"></i> Update Cart
                    </button>
                </div>
            <?php else: ?>
                <div class="card mb-4">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h5>Your cart is empty</h5>
                        <p class="text-muted">Looks like you haven't added any items to your cart yet.</p>
                        <a href="<?php echo SITE_URL; ?>/products/" class="btn btn-primary">Browse Books</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping</span>
                        <span>Calculated at checkout</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax</span>
                        <span>Calculated at checkout</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-bold">Estimated Total</span>
                        <span class="fw-bold">₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>

                    <?php if (count($cart_items) > 0): ?>
                        <a href="<?php echo SITE_URL; ?>/cart/checkout.php" class="btn btn-primary w-100">
                            Proceed to Checkout
                        </a>
                    <?php else: ?>
                        <button class="btn btn-primary w-100" disabled>Proceed to Checkout</button>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="text-center mt-3">
                            <a href="<?php echo SITE_URL; ?>/user/wishlist.php" class="text-decoration-none">
                                <i class="fas fa-heart text-danger me-1"></i> View your wishlist
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Quantity controls
    $('.quantity-minus').click(function() {
        const input = $(this).siblings('.quantity-input');
        let value = parseInt(input.val());
        if (value > 1) {
            input.val(value - 1);
        }
    });
    
    $('.quantity-plus').click(function() {
        const input = $(this).siblings('.quantity-input');
        let value = parseInt(input.val());
        if (value < 10) {
            input.val(value + 1);
        }
    });

    // Update cart item quantity
    $('.quantity-input').change(function() {
        const itemId = $(this).data('item-id');
        const quantity = $(this).val();
        
        if (quantity < 1 || quantity > 10) {
            $(this).val(1);
            return;
        }
        
        updateCartItem(itemId, quantity);
    });

    // Remove item from cart
    $('.remove-item').click(function() {
        const itemId = $(this).data('item-id');
        
        if (confirm('Are you sure you want to remove this item from your cart?')) {
            $.ajax({
                url: '<?php echo SITE_URL; ?>/ajax/remove_from_cart.php',
                type: 'POST',
                data: { item_id: itemId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload(); // Refresh to show updated cart
                    } else {
                        alert(response.message || 'Failed to remove item');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        }
    });

    // Update all quantities
    $('.update-cart').click(function() {
        $('.quantity-input').each(function() {
            const itemId = $(this).data('item-id');
            const quantity = $(this).val();
            
            if (quantity < 1 || quantity > 10) {
                $(this).val(1);
                return;
            }
            
            updateCartItem(itemId, quantity);
        });
        
        // Show message
        const toast = $(`
            <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
                <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header bg-success text-white">
                        <strong class="me-auto">Success</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        Cart updated successfully!
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(toast);
        
        // Remove toast after 3 seconds
        setTimeout(() => {
            toast.remove();
        }, 2000);
        window.location.reload();
    });

    function updateCartItem(itemId, quantity) {
        $.ajax({
            url: '<?php echo SITE_URL; ?>/ajax/update_cart_item.php',
            type: 'POST',
            data: { item_id: itemId, quantity: quantity },
            dataType: 'json',
            success: function(response) {
                if (!response.success) {
                    alert(response.message || 'Failed to update item');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>