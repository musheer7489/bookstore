<?php
require_once '../includes/config.php';

// Redirect logged in users
if (isset($_SESSION['user_id'])) {
    header('Location: account.php');
    exit;
}

$errors = [];
$success = false;
$valid_token = false;

// Check if token is provided
if (!isset($_GET['token']) || empty($_GET['token'])) {
    header('Location: forgot_password.php');
    exit;
}

$token = $_GET['token'];

// Validate token
$stmt = $pdo->prepare("
    SELECT prt.*, u.email 
    FROM password_reset_tokens prt
    JOIN users u ON prt.user_id = u.user_id
    WHERE prt.token = ? AND prt.expires_at > NOW()
");
$stmt->execute([$token]);
$token_data = $stmt->fetch();

if (!$token_data) {
    $errors['token'] = 'Invalid or expired token. Please request a new password reset link.';
} else {
    $valid_token = true;
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate password
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        
        // Validate confirm password
        if ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
        
        if (empty($errors)) {
            // Update password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashed_password, $token_data['user_id']]);
            
            // Delete token
            $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
            $stmt->execute([$token]);
            
            $success = true;
        }
    }
}

$page_title = "Reset Password";
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="card-title text-center mb-4">Reset Your Password</h2>
                    
                    <?php if (!empty($errors['token'])): ?>
                        <div class="alert alert-danger">
                            <?php echo $errors['token']; ?>
                            <p class="mb-0 mt-2"><a href="forgot_password.php">Request a new password reset link</a></p>
                        </div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success">
                            <p>Your password has been updated successfully.</p>
                            <p class="mb-0"><a href="login.php">Log in to your account</a></p>
                        </div>
                    <?php elseif ($valid_token): ?>
                        <p class="text-muted text-center mb-4">Enter a new password for <?php echo htmlspecialchars($token_data['email']); ?></p>
                        
                        <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="post">
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                       id="password" name="password" required>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                       id="confirm_password" name="confirm_password" required>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Reset Password</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>