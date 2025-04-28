<?php
require_once '../../includes/config.php';
require_once '../admin_auth.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$author_id = intval($_GET['id']);

// Get author details
$stmt = $pdo->prepare("SELECT * FROM authors WHERE author_id = ?");
$stmt->execute([$author_id]);
$author = $stmt->fetch();

if (!$author) {
    header('Location: index.php');
    exit;
}


$errors = [];
$name = $author['name'];
$bio = $author['bio'];
$current_photo = $author['photo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $bio = trim($_POST['bio']);
    
    // Validate
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    } elseif (strlen($name) < 2) {
        $errors['name'] = 'Name must be at least 2 characters';
    }
    
    // Handle file upload
    $photo = $current_photo;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($ext, $allowed)) {
            $errors['photo'] = 'Only JPG, PNG, and GIF files are allowed';
        } elseif ($file['size'] > 2 * 1024 * 1024) { // 2MB
            $errors['photo'] = 'File size must be less than 2MB';
        } else {
            // Delete old photo if exists
            if ($photo && file_exists('../../assets/images/authors/' . $photo)) {
                unlink('../../assets/images/authors/' . $photo);
            }
            
            $photo = uniqid('author_') . '.' . $ext;
            $upload_path = '../../assets/images/authors/' . $photo;
            
            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                $errors['photo'] = 'Failed to upload file';
                $photo = $current_photo; // Revert to current photo
            }
        }
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE authors SET name = ?, bio = ?, photo = ? WHERE author_id = ?");
        if ($stmt->execute([$name, $bio, $photo, $author_id])) {
            $_SESSION['success_message'] = "Author updated successfully!";
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['error'] = "Failed to update author.";
            header('Location: index.php');
            exit;
        }
    }
}
$page_title = "Edit Author";
require_once '../admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Edit Author</h2>
                <a href="manage.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Authors
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name *</label>
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
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="photo" class="form-label">Author Photo</label>
                                    <input type="file" class="form-control <?php echo isset($errors['photo']) ? 'is-invalid' : ''; ?>" 
                                           id="photo" name="photo" accept="image/*">
                                    <?php if (isset($errors['photo'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['photo']; ?></div>
                                    <?php endif; ?>
                                    <small class="text-muted">Max size: 2MB (JPG, PNG, GIF)</small>
                                </div>
                                
                                <div class="preview-container text-center mt-3">
                                    <?php if ($current_photo && file_exists('../../assets/images/authors/' . $current_photo)): ?>
                                        <img src="<?php echo SITE_URL; ?>/assets/images/authors/<?php echo $current_photo; ?>" 
                                             alt="Current Photo" class="img-thumbnail" style="max-height: 200px;">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="remove_photo" name="remove_photo">
                                            <label class="form-check-label" for="remove_photo">
                                                Remove current photo
                                            </label>
                                        </div>
                                    <?php else: ?>
                                        <div class="avatar bg-primary text-white rounded-circle mx-auto" 
                                             style="width: 200px; height: 200px; display: flex; align-items: center; justify-content: center; font-size: 5rem;">
                                            <?php echo strtoupper(substr($name, 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Author</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Preview image before upload
document.getElementById('photo').addEventListener('change', function(e) {
    const previewContainer = document.querySelector('.preview-container');
    const file = e.target.files[0];
    
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Replace current preview with new image
            let preview = document.getElementById('preview');
            if (!preview) {
                preview = document.createElement('img');
                preview.id = 'preview';
                preview.className = 'img-thumbnail';
                preview.style.maxHeight = '200px';
                previewContainer.innerHTML = '';
                previewContainer.appendChild(preview);
            }
            
            preview.src = e.target.result;
            
            // Remove the "remove photo" checkbox if it exists
            const removeCheckbox = document.getElementById('remove_photo');
            if (removeCheckbox) {
                removeCheckbox.parentNode.remove();
            }
        }
        
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once '../admin_footer.php'; ?>