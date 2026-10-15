<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!Auth::hasRole('admin')) {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: ../pages/admin/manage_suppliers.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid request');
}

$name = sanitize($_POST['name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$city = sanitize($_POST['city'] ?? '');
$country = sanitize($_POST['country'] ?? 'Philippines');
$user_id = $_SESSION['user_id'];

// Validation
if (empty($name)) {
    $_SESSION['error'] = 'Supplier name is required';
    header('Location: ../pages/admin/manage_suppliers.php');
    exit();
}

// Insert supplier
$query = "INSERT INTO suppliers (name, email, phone, address, city, country, status) 
          VALUES (?, ?, ?, ?, ?, ?, 'active')";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssssss", $name, $email, $phone, $address, $city, $country);

if (!$stmt->execute()) {
    $_SESSION['error'] = 'Failed to add supplier: ' . $conn->error;
    header('Location: ../pages/admin/manage_suppliers.php');
    exit();
}

// Log activity
$log_query = "INSERT INTO activity_logs (user_id, action, description) VALUES (?, 'ADD_SUPPLIER', ?)";
$log_stmt = $conn->prepare($log_query);
$desc = "Added supplier: $name";
$log_stmt->bind_param("is", $user_id, $desc);
$log_stmt->execute();

$_SESSION['success'] = 'Supplier added successfully!';
header('Location: ../pages/admin/manage_suppliers.php');
exit();

$conn->close();
?>
