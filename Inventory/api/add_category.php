<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!Auth::hasRole('admin')) {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: ../pages/admin/manage_categories.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid request');
}

$name = sanitize($_POST['name'] ?? '');
$description = sanitize($_POST['description'] ?? '');
$user_id = $_SESSION['user_id'];

// Validation
if (empty($name)) {
    $_SESSION['error'] = 'Category name is required';
    header('Location: ../pages/admin/manage_categories.php');
    exit();
}

// Check if category already exists
$check = $conn->query("SELECT id FROM categories WHERE name = '$name'");
if ($check->num_rows > 0) {
    $_SESSION['error'] = 'Category already exists';
    header('Location: ../pages/admin/manage_categories.php');
    exit();
}

// Insert category
$query = "INSERT INTO categories (name, description, status) VALUES (?, ?, 'active')";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $name, $description);

if (!$stmt->execute()) {
    $_SESSION['error'] = 'Failed to add category: ' . $conn->error;
    header('Location: ../pages/admin/manage_categories.php');
    exit();
}

// Log activity
$log_query = "INSERT INTO activity_logs (user_id, action, description) VALUES (?, 'ADD_CATEGORY', ?)";
$log_stmt = $conn->prepare($log_query);
$desc = "Added category: $name";
$log_stmt->bind_param("is", $user_id, $desc);
$log_stmt->execute();

$_SESSION['success'] = 'Category added successfully!';
header('Location: ../pages/admin/manage_categories.php');
exit();

$conn->close();
?>
