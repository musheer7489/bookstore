<?php
require_once '../includes/config.php';
require_once 'admin_auth.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: books.php');
    exit;
}

$book_id = intval($_GET['id']);

// Get book details
$stmt = $pdo->prepare("
    SELECT b.*, a.name AS author_name, p.name AS publisher_name
    FROM books b
    JOIN authors a ON b.author_id = a.author_id
    JOIN publishers p ON b.publisher_id = p.publisher_id
    WHERE b.book_id = ?
");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: books.php');
    exit;
}

// Get all authors and publishers for dropdowns
$authors = $pdo->query("SELECT * FROM authors ORDER BY name")->fetchAll();
$publishers = $pdo->query("SELECT * FROM publishers ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get book categories
$stmt = $pdo->prepare("
    SELECT c.category_id 
    FROM categories c
    JOIN book_categories bc ON c.category_id = bc.category_id
    WHERE bc.book_id = ?
");
$stmt->execute([$book_id]);
$book_categories = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $title = trim($_POST['title']);
    $author_id = intval($_POST['author_id']);
    $publisher_id = intval($_POST['publisher_id']);
    $isbn = trim($_POST['isbn']);
    $publication_date = trim($_POST['publication_date']);
    $pages = intval($_POST['pages']);
    $binding_type = $_POST['binding_type'];
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $discount_price = !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null;
    $stock_quantity = intval($_POST['stock_quantity']);
    $selected_categories = isset($_POST['categories']) ? $_POST['categories'] : [];
    
    // Basic validation
    if (empty($title)) $errors['title'] = 'Title is required';
    if ($author_id <= 0) $errors['author_id'] = 'Please select an author';
    if ($publisher_id <= 0) $errors['publisher_id'] = 'Please select a publisher';
    if (empty($isbn)) $errors['isbn'] = 'ISBN is required';
    if (empty($publication_date)) $errors['publication_date'] = 'Publication date is required';
    if ($pages <= 0) $errors['pages'] = 'Pages must be a positive number';
    if (empty($binding_type)) $errors['binding_type'] = 'Binding type is required';
    if (empty($description)) $errors['description'] = 'Description is required';
    if ($price <= 0) $errors['price'] = 'Price must be a positive number';
    if ($discount_price !== null && $discount_price >= $price) $errors['discount_price'] = 'Discount price must be less than regular price';
    if ($stock_quantity < 0) $errors['stock_quantity'] = 'Stock quantity cannot be negative';
    if (empty($selected_categories)) $errors['categories'] = 'Please select at least one category';
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update book
            $stmt = $pdo->prepare("
                UPDATE books SET 
                    title = ?,
                    author_id = ?,
                    publisher_id = ?,
                    isbn = ?,
                    publication_date = ?,
                    pages = ?,
                    binding_type = ?,
                    description = ?,
                    price = ?,
                    discount_price = ?,
                    stock_quantity = ?
                WHERE book_id = ?
            ");
            $stmt->execute([
                $title,
                $author_id,
                $publisher_id,
                $isbn,
                $publication_date,
                $pages,
                $binding_type,
                $description,
                $price,
                $discount_price,
                $stock_quantity,
                $book_id
            ]);
            
            // Update categories
            // First remove existing categories
            $stmt = $pdo->prepare("DELETE FROM book_categories WHERE book_id = ?");
            $stmt->execute([$book_id]);
            
            // Add selected categories
            $stmt = $pdo->prepare("INSERT INTO book_categories (book_id, category_id) VALUES (?, ?)");
            foreach ($selected_categories as $category_id) {
                $stmt->execute([$book_id, $category_id]);
            }
            
            $pdo->commit();
            $success = true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['general'] = 'Error updating book: ' . $e->getMessage();
        }
    }
}

$page_title = "Edit Book";
require_once 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Edit Book</h2>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            Book updated successfully! <a href="books.php">Return to books list</a>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" 
                                           id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                                    <?php if (isset($errors['title'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['title']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="author_id" class="form-label">Author</label>
                                    <select class="form-select <?php echo isset($errors['author_id']) ? 'is-invalid' : ''; ?>" 
                                            id="author_id" name="author_id" required>
                                        <option value="">Select Author</option>
                                        <?php foreach ($authors as $author): ?>
                                            <option value="<?php echo $author['author_id']; ?>" 
                                                <?php echo $author['author_id'] == $book['author_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($author['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['author_id'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['author_id']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="publisher_id" class="form-label">Publisher</label>
                                    <select class="form-select <?php echo isset($errors['publisher_id']) ? 'is-invalid' : ''; ?>" 
                                            id="publisher_id" name="publisher_id" required>
                                        <option value="">Select Publisher</option>
                                        <?php foreach ($publishers as $publisher): ?>
                                            <option value="<?php echo $publisher['publisher_id']; ?>" 
                                                <?php echo $publisher['publisher_id'] == $book['publisher_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($publisher['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['publisher_id'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['publisher_id']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="isbn" class="form-label">ISBN</label>
                                    <input type="text" class="form-control <?php echo isset($errors['isbn']) ? 'is-invalid' : ''; ?>" 
                                           id="isbn" name="isbn" value="<?php echo htmlspecialchars($book['isbn']); ?>" required>
                                    <?php if (isset($errors['isbn'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['isbn']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="publication_date" class="form-label">Publication Date</label>
                                    <input type="date" class="form-control <?php echo isset($errors['publication_date']) ? 'is-invalid' : ''; ?>" 
                                           id="publication_date" name="publication_date" 
                                           value="<?php echo htmlspecialchars($book['publication_date']); ?>" required>
                                    <?php if (isset($errors['publication_date'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['publication_date']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="pages" class="form-label">Number of Pages</label>
                                    <input type="number" class="form-control <?php echo isset($errors['pages']) ? 'is-invalid' : ''; ?>" 
                                           id="pages" name="pages" value="<?php echo htmlspecialchars($book['pages']); ?>" required>
                                    <?php if (isset($errors['pages'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['pages']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="binding_type" class="form-label">Binding Type</label>
                                    <select class="form-select <?php echo isset($errors['binding_type']) ? 'is-invalid' : ''; ?>" 
                                            id="binding_type" name="binding_type" required>
                                        <option value="">Select Binding Type</option>
                                        <option value="Paperback" <?php echo $book['binding_type'] === 'Paperback' ? 'selected' : ''; ?>>Paperback</option>
                                        <option value="Hardcover" <?php echo $book['binding_type'] === 'Hardcover' ? 'selected' : ''; ?>>Hardcover</option>
                                        <option value="E-book" <?php echo $book['binding_type'] === 'E-book' ? 'selected' : ''; ?>>E-book</option>
                                    </select>
                                    <?php if (isset($errors['binding_type'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['binding_type']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price (₹)</label>
                                    <input type="number" step="0.01" class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>" 
                                           id="price" name="price" value="<?php echo htmlspecialchars($book['price']); ?>" required>
                                    <?php if (isset($errors['price'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['price']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="discount_price" class="form-label">Discount Price (₹) - Optional</label>
                                    <input type="number" step="0.01" class="form-control <?php echo isset($errors['discount_price']) ? 'is-invalid' : ''; ?>" 
                                           id="discount_price" name="discount_price" 
                                           value="<?php echo htmlspecialchars($book['discount_price']); ?>">
                                    <?php if (isset($errors['discount_price'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['discount_price']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="stock_quantity" class="form-label">Stock Quantity</label>
                                    <input type="number" class="form-control <?php echo isset($errors['stock_quantity']) ? 'is-invalid' : ''; ?>" 
                                           id="stock_quantity" name="stock_quantity" 
                                           value="<?php echo htmlspecialchars($book['stock_quantity']); ?>" required>
                                    <?php if (isset($errors['stock_quantity'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['stock_quantity']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                                              id="description" name="description" rows="5" required><?php echo htmlspecialchars($book['description']); ?></textarea>
                                    <?php if (isset($errors['description'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Categories</label>
                                    <div class="row">
                                        <?php foreach ($categories as $category): ?>
                                            <div class="col-md-3 col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="category_<?php echo $category['category_id']; ?>" 
                                                           name="categories[]" 
                                                           value="<?php echo $category['category_id']; ?>"
                                                           <?php echo in_array($category['category_id'], $book_categories) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="category_<?php echo $category['category_id']; ?>">
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (isset($errors['categories'])): ?>
                                        <div class="text-danger small"><?php echo $errors['categories']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="book_images" class="form-label">Book Images</label>
                                    <input type="file" class="form-control" id="book_images" name="book_images[]" multiple accept="image/*">
                                    <small class="text-muted">Upload book cover and additional images (JPEG, PNG)</small>
                                    
                                    <!-- Display existing images -->
                                    <div class="mt-3">
                                        <h6>Current Images:</h6>
                                        <div class="row g-2">
                                            <div class="col-auto">
                                                <img src="<?php echo SITE_URL; ?>/assets/images/books/<?php echo $book['isbn']; ?>.jpg" 
                                                     alt="Book Cover" width="100" class="img-thumbnail">
                                                <p class="small text-center mt-1">Cover</p>
                                            </div>
                                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                                <?php if (file_exists("../../assets/images/books/{$book['isbn']}_{$i}.jpg")): ?>
                                                    <div class="col-auto">
                                                        <img src="<?php echo SITE_URL; ?>/assets/images/books/<?php echo $book['isbn']; ?>_<?php echo $i; ?>.jpg" 
                                                             alt="Book Image <?php echo $i; ?>" width="100" class="img-thumbnail">
                                                        <p class="small text-center mt-1">Image <?php echo $i; ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Update Book</button>
                                <a href="books.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>