<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    echo json_encode([]);
    exit;
}

$query = trim($_GET['q']);
$results = [];

// Search books
$stmt = $pdo->prepare("
    SELECT b.book_id, b.title, a.name AS author_name
    FROM books b
    JOIN authors a ON b.author_id = a.author_id
    WHERE (b.title LIKE :query OR a.name LIKE :query)
    AND b.stock_quantity > 0
    ORDER BY b.title
    LIMIT 5
");
$stmt->execute([':query' => "%$query%"]);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($books as $book) {
    $results[] = [
        'type' => 'book',
        'id' => $book['book_id'],
        'title' => $book['title'],
        'author' => $book['author_name'],
        'url' => SITE_URL . '/products/book.php?id=' . $book['book_id']
    ];
}

// Search authors
$stmt = $pdo->prepare("
    SELECT author_id, name
    FROM authors
    WHERE name LIKE :query
    ORDER BY name
    LIMIT 3
");
$stmt->execute([':query' => "%$query%"]);
$authors = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($authors as $author) {
    $results[] = [
        'type' => 'author',
        'id' => $author['author_id'],
        'name' => $author['name'],
        'url' => SITE_URL . '/author/' . strtolower(str_replace(' ', '-', $author['name']))
    ];
}

// Search categories
$stmt = $pdo->prepare("
    SELECT category_id, name
    FROM categories
    WHERE name LIKE :query
    ORDER BY name
    LIMIT 3
");
$stmt->execute([':query' => "%$query%"]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($categories as $category) {
    $results[] = [
        'type' => 'category',
        'id' => $category['category_id'],
        'name' => $category['name'],
        'url' => SITE_URL . '/products/category.php?id=' . $category['category_id']
    ];
}

echo json_encode($results);
?>