<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (empty($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET payment_status = 'Failed',
            status = 'Cancelled',
            razorpay_payment_id = ?,
            payment_error_code = ?,
            payment_error_description = ?
        WHERE order_id = ?
    ");
    $stmt->execute([
        $_POST['razorpay_payment_id'] ?? null,
        $_POST['error_code'] ?? null,
        $_POST['error_description'] ?? null,
        $_POST['order_id']
    ]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating order: ' . $e->getMessage()
    ]);
}
?>