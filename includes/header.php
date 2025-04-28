<?php
// Start output buffering
ob_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | ' . SITE_NAME : SITE_NAME; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Razorpay Payment -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <!-- JQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!-- Custom CSS -->
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/site_images/book-haven-logo.png">
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>"><?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            Categories
                        </a>
                        <ul class="dropdown-menu">
                            <?php
                            // Fetch main categories from database
                            $stmt = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL LIMIT 8");
                            while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<li><a class="dropdown-item" href="' . SITE_URL . '/products/category.php?id=' . $category['category_id'] . '">' . htmlspecialchars($category['name']) . '</a></li>';
                            }
                            ?>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/products/">All Categories</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/blog/">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/contact.php">Contact</a>
                    </li>
                </ul>

                <div class="d-flex">
                    <div class="dropdown">
                        <a class="btn btn-outline-light position-relative" href="<?php echo SITE_URL; ?>/cart/view_cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php
                                $cart_count = 0;
                                if (isset($_SESSION['user_id'])) {
                                    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart_items WHERE cart_id IN (SELECT cart_id FROM cart WHERE user_id = ?)");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $cart_count = $stmt->fetchColumn();
                                } elseif (isset($_SESSION['cart_id'])) {
                                    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart_items WHERE cart_id = ?");
                                    $stmt->execute([$_SESSION['cart_id']]);
                                    $cart_count = $stmt->fetchColumn();
                                }
                                echo $cart_count ?: 0;
                                ?>
                            </span>
                        </a>
                    </div>

                    <?php if (isset($_SESSION['user_id'])) : ?>
                        <div class="dropdown ms-2">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> My Account
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/user/account.php">Account</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/user/orders.php">My Orders</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/user/wishlist.php">Wishlist</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/user/logout.php">Logout</a></li>
                            </ul>
                        </div>
                    <?php else : ?>
                        <div class="ms-2">
                            <a href="<?php echo SITE_URL; ?>/user/login.php" class="btn btn-outline-light">Login</a>
                            <a href="<?php echo SITE_URL; ?>/user/register.php" class="btn btn-primary">Register</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <div class="navbar navbar-dark bg-dark">
        <div class="d-flex" style="margin: auto;">
            <form class="d-flex me-2" action="<?php echo SITE_URL; ?>/search.php" method="get">
                <div class="input-group">
                    <input class="form-control" type="search" name="q" placeholder="Search books..." aria-label="Search">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                    <div id="search-suggestions" class="mt-1"></div>
                </div>
            </form>
        </div>
    </div>
    <!-- Main Content Container -->
    <main class="container my-4">