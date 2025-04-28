<?php
require_once '../includes/config.php';

// Redirect unauthorized users
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Validate name
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    } elseif (strlen($name) < 2) {
        $errors['name'] = 'Name must be at least 2 characters';
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email';
    } else {
        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email is already registered';
        }
    }
    
    // Validate phone
    if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        $errors['phone'] = 'Please enter a valid 10-digit phone number';
    }
    
    if (empty($errors)) {
        // Update user profile
        $stmt = $pdo->prepare("
            UPDATE users SET 
                name = ?, 
                email = ?, 
                phone = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $name,
            $email,
            $phone,
            $_SESSION['user_id']
        ]);
        
        // Update session variables
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        
        $success = true;
    }
}

$page_title = "Edit Profile";
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <?php include 'account_nav.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Edit Profile</h2>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Profile updated successfully!
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                   id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" 
                                   id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>