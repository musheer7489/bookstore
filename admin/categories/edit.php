<?php
require_once '../../includes/config.php';
require_once '../admin_auth.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$category_id = intval($_GET['id']);

// Get category details
$stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: index.php');
    exit;
}

$errors = [];
$name = $category['name'];
$description = $category['description'];
$parent_id = $category['parent_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $new_parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

    // Validate name
    if (empty($name)) {
        $errors['name'] = 'Category name is required';
    } elseif (strlen($name) < 2) {
        $errors['name'] = 'Category name must be at least 2 characters';
    } else {
        // Check if category already exists (excluding current category)
        $stmt = $pdo->prepare("
            SELECT category_id FROM categories 
            WHERE name = ? AND parent_id " . ($new_parent_id ? "= ?" : "IS ?") . " AND category_id != ?
        ");
        $params = [$name];
        if ($new_parent_id) {
            $params[] = $new_parent_id;
        } else {
            $params[] = null;
        }
        $params[] = $category_id;
        $stmt->execute($params);
        
        if ($stmt->fetch()) {
            $errors['name'] = 'Category with this name already exists' . ($new_parent_id ? ' under the selected parent' : '');
        }
    }

    // Prevent making a category its own parent
    if ($new_parent_id == $category_id) {
        $errors['parent_id'] = 'A category cannot be its own parent';
    }

    // Prevent circular references (parent can't be a child of this category)
    if ($new_parent_id) {
        $stmt = $pdo->prepare("SELECT 1 FROM categories WHERE parent_id = ? AND category_id = ?");
        $stmt->execute([$category_id, $new_parent_id]);
        if ($stmt->fetch()) {
            $errors['parent_id'] = 'Selected parent category is already a child of this category';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE categories SET 
                name = ?, 
                description = ?, 
                parent_id = ?
            WHERE category_id = ?
        ");
        $stmt->execute([$name, $description, $new_parent_id, $category_id]);
        
        $_SESSION['success_message'] = 'Category updated successfully';
        header('Location: index.php');
        exit;
    }
}

// Get parent categories for dropdown (excluding current category and its children)
$stmt = $pdo->prepare("
    SELECT * FROM categories 
    WHERE parent_id IS NULL AND category_id != ?
    ORDER BY name
");
$stmt->execute([$category_id]);
$parent_categories = $stmt->fetchAll();

$page_title = "Edit Category";
require_once '../admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        
        <main class="col-md-12 ms-sm-auto col-lg-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit Category</h1>
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
                            <select class="form-select <?php echo isset($errors['parent_id']) ? 'is-invalid' : ''; ?>" id="parent_id" name="parent_id">
                                <option value="">None (Top-level Category)</option>
                                <?php foreach ($parent_categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo $parent_id == $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['parent_id'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['parent_id']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Category</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../admin_footer.php'; ?>