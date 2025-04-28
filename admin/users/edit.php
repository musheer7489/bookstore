<?php
require_once '../../includes/config.php';
require_once '../admin_auth.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$user_id = intval($_GET['id']);

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php');
    exit;
}

$errors = [];
$name = $user['name'];
$email = $user['email'];
$phone = $user['phone'];
$is_admin = $user['is_admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $change_password = isset($_POST['change_password']) && !empty($_POST['new_password']);

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
        // Check if email already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email is already registered';
        }
    }

    // Validate phone
    if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        $errors['phone'] = 'Please enter a valid 10-digit phone number';
    }

    // Validate password if changing
    if ($change_password) {
        $new_password = $_POST['new_password'];
        if (strlen($new_password) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters';
        }
    }

    if (empty($errors)) {
        if ($change_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    name = ?, 
                    email = ?, 
                    phone = ?, 
                    is_admin = ?,
                    password = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$name, $email, $phone, $is_admin, $hashed_password, $user_id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    name = ?, 
                    email = ?, 
                    phone = ?, 
                    is_admin = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$name, $email, $phone, $is_admin, $user_id]);
        }
        
        $_SESSION['success_message'] = 'User updated successfully';
        header('Location: index.php');
        exit;
    }
}

$page_title = "Edit User";
require_once '../admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">       
        <main class="col-md-12 ms-sm-auto col-lg-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit User</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Users
                    </a>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                   id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" 
                                   id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin" <?php echo $is_admin ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_admin">Admin User</label>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="change_password" name="change_password">
                            <label class="form-check-label" for="change_password">Change Password</label>
                        </div>
                        
                        <div class="mb-3 password-fields" style="display: none;">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control <?php echo isset($errors['new_password']) ? 'is-invalid' : ''; ?>" 
                                   id="new_password" name="new_password">
                            <?php if (isset($errors['new_password'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['new_password']; ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Minimum 8 characters</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
$(document).ready(function() {
    // Show/hide password fields
    $('#change_password').change(function() {
        if ($(this).is(':checked')) {
            $('.password-fields').show();
            $('#new_password').attr('required', true);
        } else {
            $('.password-fields').hide();
            $('#new_password').attr('required', false);
        }
    });
});
</script>

<?php require_once '../admin_footer.php'; ?>