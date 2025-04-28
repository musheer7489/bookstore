<?php
require_once '../includes/config.php';

// Redirect unauthorized users
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: addresses.php');
    exit;
}

// Validate input
$required_fields = ['street', 'city', 'state', 'country', 'postal_code'];
$errors = [];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $errors[$field] = 'This field is required';
    }
}

if (!empty($errors)) {
    $_SESSION['error_message'] = 'Please fill in all required fields';
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: addresses.php');
    exit;
}

$street = trim($_POST['street']);
$city = trim($_POST['city']);
$state = trim($_POST['state']);
$country = trim($_POST['country']);
$postal_code = trim($_POST['postal_code']);
$is_default = isset($_POST['is_default']) ? 1 : 0;

// Check if this is an update or new address
if (isset($_POST['address_id']) && is_numeric($_POST['address_id'])) {
    $address_id = intval($_POST['address_id']);
    
    // Verify address belongs to user
    $stmt = $pdo->prepare("SELECT 1 FROM addresses WHERE address_id = ? AND user_id = ?");
    $stmt->execute([$address_id, $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        // If setting as default, first reset all other addresses
        if ($is_default) {
            $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        }
        
        // Update address
        $stmt = $pdo->prepare("
            UPDATE addresses SET 
                street = ?, 
                city = ?, 
                state = ?, 
                country = ?, 
                postal_code = ?, 
                is_default = ?
            WHERE address_id = ?
        ");
        $stmt->execute([
            $street,
            $city,
            $state,
            $country,
            $postal_code,
            $is_default,
            $address_id
        ]);
        
        $_SESSION['success_message'] = 'Address updated successfully';
    }
} else {
    // If setting as default, first reset all other addresses
    if ($is_default) {
        $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
    
    // Create new address
    $stmt = $pdo->prepare("
        INSERT INTO addresses (user_id, street, city, state, country, postal_code, is_default)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $street,
        $city,
        $state,
        $country,
        $postal_code,
        $is_default
    ]);
    
    $_SESSION['success_message'] = 'Address added successfully';
}

header('Location: addresses.php');
exit;
?>