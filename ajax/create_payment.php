<?php
require_once '../includes/config.php';
require_once '../vendor/autoload.php';

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

if (empty($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

$order_id = intval($_POST['order_id']);

// Get order details
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE order_id = ? AND user_id = ? 
    AND payment_status = 'Pending'
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found or payment already processed']);
    exit;
}

try {
    $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    
    // Create Razorpay order if not already exists
    if (empty($order['razorpay_order_id'])) {
        $razorpayOrder = $api->order->create([
            'receipt' => 'order_' . $order_id,
            'amount' => $order['total_amount'] * 100, // Razorpay uses paise
            'currency' => 'INR',
            'payment_capture' => 1 // Auto-capture payment
        ]);
        
        // Save Razorpay order ID
        $stmt = $pdo->prepare("UPDATE orders SET razorpay_order_id = ? WHERE order_id = ?");
        $stmt->execute([$razorpayOrder->id, $order_id]);
        
        $razorpay_order_id = $razorpayOrder->id;
    } else {
        $razorpay_order_id = $order['razorpay_order_id'];
    }
    
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'razorpay_order_id' => $razorpay_order_id,
        'amount' => $order['total_amount'] * 100
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error creating payment: ' . $e->getMessage()
    ]);
}
?>