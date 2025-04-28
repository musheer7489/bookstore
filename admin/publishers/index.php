<?php
require_once '../../includes/config.php';
require_once '../admin_auth.php';


// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $publisher_id = intval($_GET['id']);
    
    // Check if publisher has books
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE publisher_id = ?");
    $stmt->execute([$publisher_id]);
    $book_count = $stmt->fetchColumn();
    
    if ($book_count > 0) {
        $_SESSION['error'] = "Cannot delete publisher with books assigned. Please reassign books first.";
    } else {
        // Delete logo file if exists
        $stmt = $pdo->prepare("SELECT logo FROM publishers WHERE publisher_id = ?");
        $stmt->execute([$publisher_id]);
        $publisher = $stmt->fetch();
        
        if ($publisher && $publisher['logo'] && file_exists('../../assets/images/publishers/' . $publisher['logo'])) {
            unlink('../../assets/images/publishers/' . $publisher['logo']);
        }
        
        $stmt = $pdo->prepare("DELETE FROM publishers WHERE publisher_id = ?");
        if ($stmt->execute([$publisher_id])) {
            $_SESSION['success'] = "Publisher deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete publisher.";
        }
    }
    
    header('Location: manage.php');
    exit;
}

// Get all publishers
$stmt = $pdo->query("
    SELECT p.*, 
           (SELECT COUNT(*) FROM books WHERE publisher_id = p.publisher_id) AS book_count
    FROM publishers p
    ORDER BY p.name
");
$publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Manage Publishers";
require_once '../admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manage Publishers</h2>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> Add New Publisher
                </a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Books</th>
                                    <th>Logo</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($publishers as $publisher): ?>
                                    <tr>
                                        <td><?php echo $publisher['publisher_id']; ?></td>
                                        <td>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($publisher['name']); ?></h6>
                                            <?php if (!empty($publisher['description'])): ?>
                                                <small class="text-muted"><?php echo substr(htmlspecialchars($publisher['description']), 0, 50); ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $publisher['book_count']; ?></td>
                                        <td>
                                            <?php if (!empty($publisher['logo'])): ?>
                                                <img src="<?php echo SITE_URL; ?>/assets/images/publishers/<?php echo $publisher['logo']; ?>" 
                                                     alt="<?php echo htmlspecialchars($publisher['name']); ?>" 
                                                     width="40" height="40" class="rounded">
                                            <?php else: ?>
                                                <div class="avatar bg-secondary text-white rounded" 
                                                     style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                    <?php echo strtoupper(substr($publisher['name'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit.php?id=<?php echo $publisher['publisher_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage.php?action=delete&id=<?php echo $publisher['publisher_id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to delete this publisher?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../admin_footer.php'; ?>