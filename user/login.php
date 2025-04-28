<?php
require_once '../includes/config.php';

// Redirect logged in users to their account page
if (isset($_SESSION['user_id'])) {
    header('Location: account.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email';
    }

    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }

    if (empty($errors)) {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            // Remember me functionality
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30); // 30 days
                
                $stmt = $pdo->prepare("INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['user_id'], $token, $expiry]);
                
                setcookie('remember_token', $token, time() + 60 * 60 * 24 * 30, '/');
            }
            
            // Redirect to account page or previous page
            if (isset($_SESSION['return_to'])) {
                $return_to = $_SESSION['return_to'];
                unset($_SESSION['return_to']);
                header("Location: $return_to");
            } else {
                header('Location: account.php');
            }
            exit;
        } else {
            $errors['general'] = 'Invalid email or password';
        }
    }
}

$page_title = "Login";
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="card-title text-center mb-4">Login to Your Account</h2>
                    
                    <?php if (!empty($errors['general'])): ?>
                        <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                    <?php endif; ?>
                    
                    <form action="login.php" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                   id="password" name="password" required>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">Login</button>
                        </div>
                        
                        <div class="text-center mb-3">
                            <a href="forgot_password.php">Forgot your password?</a>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <p class="text-center mb-0">Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>