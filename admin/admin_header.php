<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | Admin Panel' : 'Admin Panel'; ?></title>
    
    <!-- JQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Admin CSS -->
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico">
</head>
<body class="admin-dashboard">
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/admin/dashboard.php">Admin Panel</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION['admin_username']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    
    <!-- Sidebar and Main Content -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/orders.php">
                                <i class="fas fa-shopping-bag me-2"></i> Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['books.php', 'add_book.php', 'edit_book.php']) ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/books.php">
                                <i class="fas fa-book me-2"></i> Books
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['PHP_SELF']) === '/bookstore/admin/categories/index.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/categories">
                                <i class="fas fa-tags me-2"></i> Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($_SERVER['PHP_SELF']) === '/bookstore/admin/authors/index.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/authors">
                                <i class="fas fa-user-edit me-2"></i> Authors
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['publishers', 'add_publisher.php', 'edit_publisher.php']) ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/publishers">
                                <i class="fas fa-building me-2"></i> Publishers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/users">
                                <i class="fas fa-users me-2"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reviews' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/reviews">
                                <i class="fas fa-star me-2"></i> Reviews
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/settings.php">
                                <i class="fas fa-cog me-2"></i> Settings
                            </a>
                        </li>
                    </ul>
                    
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Reports</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/sales_report.php">
                                <i class="fas fa-chart-line me-2"></i> Sales Report
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/inventory_report.php">
                                <i class="fas fa-boxes me-2"></i> Inventory Report
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">