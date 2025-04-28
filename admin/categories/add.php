<?php
require_once '../../includes/config.php';
require_once '../admin_auth.php';

$errors = [];
$name = $description = '';
$parent_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

    // Validate name
    if (empty($name)) {
        $errors['name'] = 'Category name is required';
    } elseif (strlen($name) < 2) {
        $errors['name'] = 'Category name must be at least 2 characters';
    } else {
        // Check if category already exists
        $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE name = ? AND parent_id " . ($parent_id ? "= ?" : "IS ?"));
        $params = [$name];
        if ($parent_id) {
            $params[] = $parent_id;
        } else {
            $params[] = null;
        }
        $stmt->execute($params);
        
        if ($stmt->fetch()) {
            $errors['name'] = 'Category with this name already exists' . ($parent_id ? ' under the selected parent' : '');
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO categories (name, description, parent_id) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$name, $description, $parent_id]);
        
        $_SESSION['success_message'] = 'Category added successfully';
        header('Location: index.php');
        exit;
    }
}

// Get parent categories for dropdown
$stmt = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");
$parent_categories = $stmt->fetchAll();

$page_title = "Add New Category";
require_once '../admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        
        <main class="col-md-12 ms-sm-auto col-lg-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Add New Category</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Categories
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
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                   id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Parent Category (optional)</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="">None (Top-level Category)</option>
                                <?php foreach ($parent_categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo $parent_id == $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../admin_footer.php'; ?>