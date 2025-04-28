<?php
require_once '../../includes/config.php';
require_once '../admin_auth.php';

$errors = [];
$name = $bio = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $bio = trim($_POST['bio']);

    // Validate name
    if (empty($name)) {
        $errors['name'] = 'Author name is required';
    } elseif (strlen($name) < 2) {
        $errors['name'] = 'Author name must be at least 2 characters';
    } else {
        // Check if author already exists
        $stmt = $pdo->prepare("SELECT author_id FROM authors WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            $errors['name'] = 'Author with this name already exists';
        }
    }

    if (empty($errors)) {
        // Handle file upload
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../assets/images/authors/';
            $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = strtolower(str_replace(' ', '-', $name)) . '.' . $file_ext;
            $target_file = $upload_dir . $filename;
            
            // Check if image file is a actual image
            $check = getimagesize($_FILES['photo']['tmp_name']);
            if ($check !== false) {
                // Move uploaded file
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                    $photo = $filename;
                } else {
                    $errors['photo'] = 'Failed to upload photo';
                }
            } else {
                $errors['photo'] = 'File is not an image';
            }
        }
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("
                INSERT INTO authors (name, bio, photo) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$name, $bio, $photo]);
            
            $_SESSION['success_message'] = 'Author added successfully';
            header('Location: index.php');
            exit;
        }
    }
}

$page_title = "Add New Author";
require_once '../admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">     
        <main class="col-md-12 ms-sm-auto col-lg-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Add New Author</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Authors
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
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Author Name</label>
                            <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                   id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Biography</label>
                            <textarea class="form-control" id="bio" name="bio" rows="5"><?php echo htmlspecialchars($bio); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="photo" class="form-label">Author Photo</label>
                            <input type="file" class="form-control <?php echo isset($errors['photo']) ? 'is-invalid' : ''; ?>" 
                                   id="photo" name="photo" accept="image/*">
                            <?php if (isset($errors['photo'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['photo']; ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Optional. JPG, PNG or GIF.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Add Author</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../admin_footer.php'; ?>