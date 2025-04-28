<?php
require_once '../includes/config.php';

// Redirect unauthorized users
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!password_verify($current_password, $user['password'])) {
        $errors['current_password'] = 'Current password is incorrect';
    }
    
    // Validate new password
    if (empty($new_password)) {
        $errors['new_password'] = 'New password is required';
    } elseif (strlen($new_password) < 8) {
        $errors['new_password'] = 'Password must be at least 8 characters';
    }
    
    // Validate confirm password
    if ($new_password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([$hashed_password, $_SESSION['user_id']]);
        
        $success = true;
    }
}

$page_title = "Change Password";
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <?php include 'account_nav.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Change Password</h2>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Password updated successfully!
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control <?php echo isset($errors['current_password']) ? 'is-invalid' : ''; ?>" 
                                   id="current_password" name="current_password" required>
                            <?php if (isset($errors['current_password'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['current_password']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control <?php echo isset($errors['new_password']) ? 'is-invalid' : ''; ?>" 
                                   id="new_password" name="new_password" required>
                            <?php if (isset($errors['new_password'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['new_password']; ?></div>
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
                        
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>