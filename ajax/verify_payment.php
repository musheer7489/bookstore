<?php
require_once '../includes/config.php';
require_once '../vendor/autoload.php';

use Razorpay\Api\Api;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate required parameters
$required = ['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature', 'order_id'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "$field is required"]);
        exit;
    }
}

try {
    $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

    // Verify payment signature
    $attributes = [
        'razorpay_order_id' => $_POST['razorpay_order_id'],
        'razorpay_payment_id' => $_POST['razorpay_payment_id'],
        'razorpay_signature' => $_POST['razorpay_signature']
    ];

    $api->utility->verifyPaymentSignature($attributes);

    // Update order in database
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET payment_status = 'Completed', 
            status = 'Processing',
            razorpay_payment_id = ?,
            payment_date = NOW()
        WHERE order_id = ? AND razorpay_order_id = ?
    ");
    $stmt->execute([
        $_POST['razorpay_payment_id'],
        $_POST['order_id'],
        $_POST['razorpay_order_id']
    ]);
    // If order was previously cancelled, update status
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 'Processing',
            cancellation_reason = NULL,
            cancelled_at = NULL
        WHERE order_id = ? AND status = 'Cancelled'
    ");
    $stmt->execute([$_POST['order_id']]);

    echo json_encode(['success' => true]);
    // Clear cart
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id IN (SELECT cart_id FROM cart WHERE user_id = ?)");
    $stmt->execute([$_SESSION['user_id']]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Payment verification failed: ' . $e->getMessage()
    ]);
}
