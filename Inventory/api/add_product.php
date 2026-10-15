<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!Auth::hasRole('admin')) {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: ../pages/admin/manage_products.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid request');
}

$name = sanitize($_POST['name'] ?? '');
$sku = sanitize($_POST['sku'] ?? '');
$category_id = intval($_POST['category_id'] ?? 0);
$supplier_id = intval($_POST['supplier_id'] ?? 0);
$price = floatval($_POST['price'] ?? 0);
$cost = floatval($_POST['cost'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 0);
$min_stock = intval($_POST['min_stock'] ?? 10);
$max_stock = intval($_POST['max_stock'] ?? 500);
$description = sanitize($_POST['description'] ?? '');
$user_id = $_SESSION['user_id'];

// Validation
if (empty($name) || empty($sku) || $category_id <= 0 || $supplier_id <= 0 || $price <= 0) {
    $_SESSION['error'] = 'All required fields must be filled';
    header('Location: ../pages/admin/manage_products.php');
    exit();
}

// Check if SKU already exists
$sku_check = $conn->query("SELECT id FROM products WHERE sku = '$sku'");
if ($sku_check->num_rows > 0) {
    $_SESSION['error'] = 'SKU already exists';
    header('Location: ../pages/admin/manage_products.php');
    exit();
}

// Insert product
$query = "INSERT INTO products (name, description, category_id, supplier_id, sku, price, cost, quantity, min_stock, max_stock, status) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssiisddiii", $name, $description, $category_id, $supplier_id, $sku, $price, $cost, $quantity, $min_stock, $max_stock);

if (!$stmt->execute()) {
    $_SESSION['error'] = 'Failed to add product: ' . $conn->error;
    header('Location: ../pages/admin/manage_products.php');
    exit();
}

// Log activity
$log_query = "INSERT INTO activity_logs (user_id, action, description) VALUES (?, 'ADD_PRODUCT', ?)";
$log_stmt = $conn->prepare($log_query);
$desc = "Added product: $name (SKU: $sku)";
$log_stmt->bind_param("is", $user_id, $desc);
$log_stmt->execute();

$_SESSION['success'] = 'Product added successfully!';
header('Location: ../pages/admin/manage_products.php');
exit();

$conn->close();
?>
