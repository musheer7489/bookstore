<?php
require_once '../includes/config.php';
require_once 'admin_auth.php';


// Initialize variables
$errors = [];
$title = $isbn = $description = '';
$author_id = $publisher_id = $publication_date = '';
$pages = 0;
$binding_type = 'Paperback';
$price = 0;
$discount_price = null;
$stock_quantity = 0;
$selected_categories = [];

// Get authors, publishers, and categories for dropdowns
$authors = $pdo->query("SELECT author_id, name FROM authors ORDER BY name")->fetchAll();
$publishers = $pdo->query("SELECT publisher_id, name FROM publishers ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT category_id, name FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title']);
    $isbn = trim($_POST['isbn']);
    $author_id = intval($_POST['author_id']);
    $publisher_id = intval($_POST['publisher_id']);
    $publication_date = $_POST['publication_date'];
    $pages = intval($_POST['pages']);
    $binding_type = $_POST['binding_type'];
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $discount_price = !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null;
    $stock_quantity = intval($_POST['stock_quantity']);
    $selected_categories = isset($_POST['categories']) ? $_POST['categories'] : [];

    // Validate required fields
    if (empty($title)) {
        $errors['title'] = 'Title is required';
    }

    if (empty($isbn)) {
        $errors['isbn'] = 'ISBN is required';
    } elseif (!preg_match('/^[0-9\-]+$/', $isbn)) {
        $errors['isbn'] = 'ISBN must contain only numbers and hyphens';
    } else {
        // Check if ISBN already exists
        $stmt = $pdo->prepare("SELECT book_id FROM books WHERE isbn = ?");
        $stmt->execute([$isbn]);
        if ($stmt->fetch()) {
            $errors['isbn'] = 'A book with this ISBN already exists';
        }
    }

    if ($author_id <= 0) {
        $errors['author_id'] = 'Please select an author';
    }

    if ($publisher_id <= 0) {
        $errors['publisher_id'] = 'Please select a publisher';
    }

    if (empty($publication_date)) {
        $errors['publication_date'] = 'Publication date is required';
    }

    if ($pages <= 0) {
        $errors['pages'] = 'Number of pages must be positive';
    }

    if ($price <= 0) {
        $errors['price'] = 'Price must be positive';
    }

    if ($discount_price !== null && $discount_price >= $price) {
        $errors['discount_price'] = 'Discount price must be less than regular price';
    }

    if ($stock_quantity < 0) {
        $errors['stock_quantity'] = 'Stock quantity cannot be negative';
    }

    if (count($selected_categories) === 0) {
        $errors['categories'] = 'Please select at least one category';
    }

    // Handle image uploads
    $main_image = '';
    $additional_images = [];

    // Main image validation
    if (!isset($_FILES['main_image']) || $_FILES['main_image']['error'] !== UPLOAD_ERR_OK) {
        $errors['main_image'] = 'Main image is required';
    } else {
        $file = $_FILES['main_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed)) {
            $errors['main_image'] = 'Only JPG, PNG, and GIF files are allowed';
        } elseif ($file['size'] > 2 * 1024 * 1024) { // 2MB
            $errors['main_image'] = 'File size must be less than 2MB';
        }
    }

    // Additional images validation
    for ($i = 1; $i <= 4; $i++) {
        if (isset($_FILES['additional_image_' . $i]) && $_FILES['additional_image_' . $i]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['additional_image_' . $i];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($ext, $allowed)) {
                $errors['additional_image_' . $i] = 'Only JPG, PNG, and GIF files are allowed';
            } elseif ($file['size'] > 2 * 1024 * 1024) { // 2MB
                $errors['additional_image_' . $i] = 'File size must be less than 2MB';
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Upload main image
            $main_image = $isbn . '.' . strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
            $upload_path = '../assets/images/books/' . $main_image;
            if (!move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_path)) {
                throw new Exception('Failed to upload main image');
            }

            // Upload additional images
            for ($i = 1; $i <= 4; $i++) {
                if (isset($_FILES['additional_image_' . $i]) && $_FILES['additional_image_' . $i]['error'] === UPLOAD_ERR_OK) {
                    $additional_image = $isbn . '_' . $i . '.' . strtolower(pathinfo($_FILES['additional_image_' . $i]['name'], PATHINFO_EXTENSION));
                    $upload_path = '../assets/images/books/' . $additional_image;
                    if (!move_uploaded_file($_FILES['additional_image_' . $i]['tmp_name'], $upload_path)) {
                        throw new Exception('Failed to upload additional image ' . $i);
                    }
                    $additional_images[] = $additional_image;
                }
            }

            // Insert book
            $stmt = $pdo->prepare("
                INSERT INTO books (
                    isbn, title, author_id, publisher_id, publication_date, pages, 
                    binding_type, description, price, discount_price, stock_quantity
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $isbn, $title, $author_id, $publisher_id, $publication_date, $pages,
                $binding_type, $description, $price, $discount_price, $stock_quantity
            ]);
            
            $book_id = $pdo->lastInsertId();

            // Insert book categories
            $stmt = $pdo->prepare("INSERT INTO book_categories (book_id, category_id) VALUES (?, ?)");
            foreach ($selected_categories as $category_id) {
                $stmt->execute([$book_id, $category_id]);
            }

            $pdo->commit();
            $_SESSION['success'] = "Book added successfully!";
            header('Location: books.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            
            // Delete any uploaded files if transaction failed
            if ($main_image && file_exists('../assets/images/books/' . $main_image)) {
                unlink('../assets/images/books/' . $main_image);
            }
            
            foreach ($additional_images as $image) {
                if (file_exists('../assets/images/books/' . $image)) {
                    unlink('../assets/images/books/' . $image);
                }
            }
            
            $_SESSION['error'] = "Error adding book: " . $e->getMessage();
            header('Location: add.php');
            exit;
        }
    }
}
$page_title = "Add New Book";
require_once 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">      
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Add New Book</h2>
                <a href="manage.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Books
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Basic Info -->
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title *</label>
                                    <input type="text" class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" 
                                           id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                                    <?php if (isset($errors['title'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['title']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="isbn" class="form-label">ISBN *</label>
                                    <input type="text" class="form-control <?php echo isset($errors['isbn']) ? 'is-invalid' : ''; ?>" 
                                           id="isbn" name="isbn" value="<?php echo htmlspecialchars($isbn); ?>" required>
                                    <?php if (isset($errors['isbn'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['isbn']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="author_id" class="form-label">Author *</label>
                                    <select class="form-select <?php echo isset($errors['author_id']) ? 'is-invalid' : ''; ?>" 
                                            id="author_id" name="author_id" required>
                                        <option value="">Select Author</option>
                                        <?php foreach ($authors as $author): ?>
                                            <option value="<?php echo $author['author_id']; ?>" 
                                                <?php echo $author_id == $author['author_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($author['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['author_id'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['author_id']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="publisher_id" class="form-label">Publisher *</label>
                                    <select class="form-select <?php echo isset($errors['publisher_id']) ? 'is-invalid' : ''; ?>" 
                                            id="publisher_id" name="publisher_id" required>
                                        <option value="">Select Publisher</option>
                                        <?php foreach ($publishers as $publisher): ?>
                                            <option value="<?php echo $publisher['publisher_id']; ?>" 
                                                <?php echo $publisher_id == $publisher['publisher_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($publisher['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['publisher_id'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['publisher_id']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="publication_date" class="form-label">Publication Date *</label>
                                    <input type="date" class="form-control <?php echo isset($errors['publication_date']) ? 'is-invalid' : ''; ?>" 
                                           id="publication_date" name="publication_date" value="<?php echo htmlspecialchars($publication_date); ?>" required>
                                    <?php if (isset($errors['publication_date'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['publication_date']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Details -->
                                <div class="mb-3">
                                    <label for="pages" class="form-label">Number of Pages *</label>
                                    <input type="number" class="form-control <?php echo isset($errors['pages']) ? 'is-invalid' : ''; ?>" 
                                           id="pages" name="pages" value="<?php echo htmlspecialchars($pages); ?>" min="1" required>
                                    <?php if (isset($errors['pages'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['pages']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="binding_type" class="form-label">Binding Type *</label>
                                    <select class="form-select" id="binding_type" name="binding_type" required>
                                        <option value="Paperback" <?php echo $binding_type === 'Paperback' ? 'selected' : ''; ?>>Paperback</option>
                                        <option value="Hardcover" <?php echo $binding_type === 'Hardcover' ? 'selected' : ''; ?>>Hardcover</option>
                                        <option value="E-book" <?php echo $binding_type === 'E-book' ? 'selected' : ''; ?>>E-book</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price (₹) *</label>
                                    <input type="number" step="0.01" class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>" 
                                           id="price" name="price" value="<?php echo htmlspecialchars($price); ?>" min="0" required>
                                    <?php if (isset($errors['price'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['price']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="discount_price" class="form-label">Discount Price (₹)</label>
                                    <input type="number" step="0.01" class="form-control <?php echo isset($errors['discount_price']) ? 'is-invalid' : ''; ?>" 
                                           id="discount_price" name="discount_price" value="<?php echo htmlspecialchars($discount_price); ?>" min="0">
                                    <?php if (isset($errors['discount_price'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['discount_price']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="stock_quantity" class="form-label">Stock Quantity *</label>
                                    <input type="number" class="form-control <?php echo isset($errors['stock_quantity']) ? 'is-invalid' : ''; ?>" 
                                           id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($stock_quantity); ?>" min="0" required>
                                    <?php if (isset($errors['stock_quantity'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['stock_quantity']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <!-- Description -->
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description *</label>
                                    <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($description); ?></textarea>
                                </div>
                                
                                <!-- Categories -->
                                <div class="mb-3">
                                    <label class="form-label">Categories *</label>
                                    <?php if (isset($errors['categories'])): ?>
                                        <div class="alert alert-danger"><?php echo $errors['categories']; ?></div>
                                    <?php endif; ?>
                                    
                                    <div class="row">
                                        <?php foreach ($categories as $category): ?>
                                            <div class="col-md-3 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="category_<?php echo $category['category_id']; ?>" 
                                                           name="categories[]" value="<?php echo $category['category_id']; ?>"
                                                           <?php echo in_array($category['category_id'], $selected_categories) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="category_<?php echo $category['category_id']; ?>">
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Images -->
                                <div class="mb-4">
                                    <h5>Images</h5>
                                    
                                    <!-- Main Image -->
                                    <div class="mb-3">
                                        <label for="main_image" class="form-label">Main Image *</label>
                                        <input type="file" class="form-control <?php echo isset($errors['main_image']) ? 'is-invalid' : ''; ?>" 
                                               id="main_image" name="main_image" accept="image/*" required>
                                        <?php if (isset($errors['main_image'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['main_image']; ?></div>
                                        <?php endif; ?>
                                        <small class="text-muted">This will be the primary display image for the book (max 2MB)</small>
                                        <div class="mt-2">
                                            <img id="main_image_preview" src="#" alt="Main Image Preview" class="img-thumbnail" style="max-height: 200px; display: none;">
                                        </div>
                                    </div>
                                    
                                    <!-- Additional Images -->
                                    <div class="row">
                                        <?php for ($i = 1; $i <= 4; $i++): ?>
                                            <div class="col-md-3 mb-3">
                                                <label for="additional_image_<?php echo $i; ?>" class="form-label">Additional Image <?php echo $i; ?></label>
                                                <input type="file" class="form-control <?php echo isset($errors['additional_image_' . $i]) ? 'is-invalid' : ''; ?>" 
                                                       id="additional_image_<?php echo $i; ?>" name="additional_image_<?php echo $i; ?>" accept="image/*">
                                                <?php if (isset($errors['additional_image_' . $i])): ?>
                                                    <div class="invalid-feedback"><?php echo $errors['additional_image_' . $i]; ?></div>
                                                <?php endif; ?>
                                                <div class="mt-2">
                                                    <img id="additional_image_<?php echo $i; ?>_preview" src="#" alt="Additional Image Preview" class="img-thumbnail" style="max-height: 150px; display: none;">
                                                </div>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-lg">Add Book</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Preview images before upload
function setupImagePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            
            reader.readAsDataURL(file);
        } else {
            preview.src = '#';
            preview.style.display = 'none';
        }
    });
}

// Set up previews for all image inputs
setupImagePreview('main_image', 'main_image_preview');
<?php for ($i = 1; $i <= 4; $i++): ?>
    setupImagePreview('additional_image_<?php echo $i; ?>', 'additional_image_<?php echo $i; ?>_preview');
<?php endfor; ?>
</script>

<?php require_once 'admin_footer.php'; ?>