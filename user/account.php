<?php
require_once '../includes/config.php';

// Redirect unauthorized users to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    // User not found (shouldn't happen)
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get user addresses
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC");
$stmt->execute([$_SESSION['user_id']]);
$addresses = $stmt->fetchAll();

// Get recent orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$page_title = "My Account";
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" 
                             style="width: 80px; height: 80px; font-size: 2rem;">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                    </div>
                    <h5 class="card-title"><?php echo htmlspecialchars($user['name']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    
                    <hr>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="account.php"><i class="fas fa-user me-2"></i> Account</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php"><i class="fas fa-shopping-bag me-2"></i> Orders</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="wishlist.php"><i class="fas fa-heart me-2"></i> Wishlist</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="addresses.php"><i class="fas fa-map-marker-alt me-2"></i> Addresses</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Account Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6>Personal Information</h6>
                            <p><?php echo htmlspecialchars($user['name']); ?></p>
                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                            <?php if (!empty($user['phone'])): ?>
                                <p><?php echo htmlspecialchars($user['phone']); ?></p>
                            <?php endif; ?>
                            <a href="edit_profile.php" class="btn btn-sm btn-outline-primary">Edit Profile</a>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <h6>Default Address</h6>
                            <?php 
                            $default_address = null;
                            foreach ($addresses as $address) {
                                if ($address['is_default']) {
                                    $default_address = $address;
                                    break;
                                }
                            }
                            
                            if ($default_address): ?>
                                <address>
                                    <?php echo htmlspecialchars($default_address['street']); ?><br>
                                    <?php echo htmlspecialchars($default_address['city'] . ', ' . $default_address['state']); ?><br>
                                    <?php echo htmlspecialchars($default_address['country'] . ' - ' . $default_address['postal_code']); ?>
                                </address>
                                <a href="addresses.php" class="btn btn-sm btn-outline-primary">Manage Addresses</a>
                            <?php else: ?>
                                <p>No default address set</p>
                                <a href="addresses.php" class="btn btn-sm btn-primary">Add Address</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Orders</h5>
                </div>
                <div class="card-body">
                    <?php if (count($orders) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['order_id']; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                            <td>
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
                                                    ?>">
                                                    <?php echo $order['status']; ?>
                                                </span>
                                            </td>
                                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end">
                            <a href="orders.php" class="btn btn-primary">View All Orders</a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                            <h5>No orders yet</h5>
                            <p>You haven't placed any orders with us yet.</p>
                            <a href="../products/" class="btn btn-primary">Start Shopping</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>