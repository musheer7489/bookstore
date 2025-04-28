<?php
require_once '../includes/config.php';
require_once 'admin_auth.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['status'])) {
    header('Location: orders.php');
    exit;
}

$order_id = intval($_GET['id']);
$status = $_GET['status'];

// Validate status
$valid_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
if (!in_array($status, $valid_statuses)) {
    header('Location: orders.php');
    exit;
}

// Update order status
$stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
$stmt->execute([$status, $order_id]);

// If order is cancelled and payment was completed, refund the payment
if ($status === 'Cancelled') {
    $stmt = $pdo->prepare("SELECT payment_status FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $payment_status = $stmt->fetchColumn();
    
    if ($payment_status === 'Completed') {
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'Refunded' WHERE order_id = ?");
        $stmt->execute([$order_id]);
    }
}

// Redirect back to order details
header("Location: order_details.php?id=$order_id");
exit;
?>