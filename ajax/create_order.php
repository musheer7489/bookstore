<?php
require_once '../includes/config.php';

// Add Razorpay PHP SDK
require_once '../vendor/autoload.php'; // If using Composer
// OR manually include Razorpay SDK files
use Razorpay\Api\Api;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Redirect unauthorized users
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}


// Get cart ID for the user
$stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cart = $stmt->fetch();
$cart_id = $cart ? $cart['cart_id'] : null;


if (!$cart_id) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

// Get cart items to calculate total
$stmt = $pdo->prepare("
SELECT ci.book_id, ci.quantity, 
IF(b.discount_price > 0, b.discount_price, b.price) AS price
FROM cart_items ci
JOIN books b ON ci.book_id = b.book_id
WHERE ci.cart_id = ?
");
$stmt->execute([$cart_id]);
$cart_items = $stmt->fetchAll();

if (count($cart_items) === 0) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}
// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = 50; // Flat rate shipping
$tax = $subtotal * 0.18; // 18% tax
$total = (int)($subtotal + $shipping + $tax);

// Create order in database first
try {
    $pdo->beginTransaction();
    
    // Get shipping address
    $shipping_address = '';
    if ($_POST['use_existing_address'] === 'yes') {
        $stmt = $pdo->prepare("SELECT * FROM addresses WHERE address_id = ? AND user_id = ?");
        $stmt->execute([$_POST['address_id'], $_SESSION['user_id']]);
        $address = $stmt->fetch();
        
        if ($address) {
            $shipping_address = implode(', ', [
                $address['street'],
                $address['city'],
                $address['state'],
                $address['country'],
                $address['postal_code']
            ]);
        }
    } else {
        $shipping_address = implode(', ', [
            $_POST['street'],
            $_POST['city'],
            $_POST['state'],
            $_POST['country'],
            $_POST['postal_code']
        ]);
    }
    
    
    // Create order
    $stmt = $pdo->prepare("
    INSERT INTO orders (user_id, total_amount, status, payment_method, payment_status, shipping_address)
    VALUES (?, ?, 'Pending', 'razorpay', 'Pending', ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $total,
        $shipping_address
    ]);
    
    $order_id = $pdo->lastInsertId();
    
    // Add order items
    foreach ($cart_items as $item) {
        $stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, book_id, quantity, price)
        VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $order_id,
            $item['book_id'],
            $item['quantity'],
            $item['price']
        ]);
    }
    
    // Create Razorpay order
    $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    $razorpayOrder = $api->order->create([
        'receipt' => 'order_' . $order_id,
        'amount' => $total * 100, // Razorpay uses paise
        'currency' => 'INR',
        'payment_capture' => 1 // Auto-capture payment
    ]);
    
    // Save Razorpay order ID
    $stmt = $pdo->prepare("UPDATE orders SET razorpay_order_id = ? WHERE order_id = ?");
    $stmt->execute([$razorpayOrder->id, $order_id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'razorpay_order_id' => $razorpayOrder->id,
        'amount' => $total * 100
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error creating order: ' . $e->getMessage()
    ]);
}
?>