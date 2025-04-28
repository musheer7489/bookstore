<div class="card">
    <div class="card-body">
        <div class="text-center mb-3">
            <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" 
                 style="width: 80px; height: 80px; font-size: 2rem;">
                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
            </div>
            <h5 class="mt-3 mb-0"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h5>
            <p class="text-muted small"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
        </div>
        
        <hr>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'account.php' ? 'active' : ''; ?>" href="account.php">
                    <i class="fas fa-user me-2"></i> Account Overview
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                    <i class="fas fa-shopping-bag me-2"></i> My Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'wishlist.php' ? 'active' : ''; ?>" href="wishlist.php">
                    <i class="fas fa-heart me-2"></i> Wishlist
                    <span class="badge bg-primary rounded-pill ms-2 wishlist-count">
                        <?php 
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        echo $stmt->fetchColumn();
                        ?>
                    </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'addresses.php' ? 'active' : ''; ?>" href="addresses.php">
                    <i class="fas fa-map-marker-alt me-2"></i> Addresses
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'edit_profile.php' ? 'active' : ''; ?>" href="edit_profile.php">
                    <i class="fas fa-edit me-2"></i> Edit Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'change_password.php' ? 'active' : ''; ?>" href="change_password.php">
                    <i class="fas fa-lock me-2"></i> Change Password
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>