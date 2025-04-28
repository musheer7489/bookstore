<?php
require_once '../includes/config.php';
// Include email template
require_once '../includes/email_templates.php';

// Redirect logged in users
if (isset($_SESSION['user_id'])) {
    header('Location: account.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email';
    }

    if (empty($errors)) {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration

            // Delete any existing tokens for this user
            $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);

            // Insert new token
            $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['user_id'], $token, $expires]);

            // Send email with reset link
            $reset_link = SITE_URL . '/user/reset_password.php?token=' . $token;
            $subject = 'Password Reset Request - ' . SITE_NAME;
            $message = get_password_reset_email($email, $reset_link);
            $headers = "From: " . SITE_EMAIL . "\r\n";
            $headers .= "Reply-To: " . SITE_EMAIL . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            // In production, use a proper mailer like PHPMailer
            mail($email, $subject, $message, $headers);

            $success = true;
        } else {
            // For security, don't reveal if email exists
            $success = true;
        }
    }
}

$page_title = "Forgot Password";
require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="card-title text-center mb-4">Forgot Your Password?</h2>

                    <?php if ($success) : ?>
                        <div class="alert alert-success">
                            <p>If an account exists with that email, we've sent a password reset link. Please check your email.</p>
                            <p class="mb-0">Didn't receive an email? <a href="forgot_password.php">Try again</a> or contact support.</p>
                        </div>
                    <?php else : ?>
                        <p class="text-muted text-center mb-4">Enter your email address and we'll send you a link to reset your password.</p>

                        <form action="forgot_password.php" method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                <?php if (isset($errors['email'])) : ?>
                                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Send Reset Link</button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <p class="text-center mb-0">
                            Remember your password? <a href="login.php">Log in</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>