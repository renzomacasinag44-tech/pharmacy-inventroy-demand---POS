<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!Auth::hasRole('admin')) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: ../pages/admin/manage_products.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid request']);
        exit();
    }
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid request');
}

$id = intval($_POST['id'] ?? 0);
$name = sanitize($_POST['name'] ?? '');
$sku = sanitize($_POST['sku'] ?? '');
$category_id = intval($_POST['category_id'] ?? 0);
$supplier_id = intval($_POST['supplier_id'] ?? 0);
$price = floatval($_POST['price'] ?? 0);
$cost = floatval($_POST['cost'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 0);
$min_stock = intval($_POST['min_stock'] ?? 0);
$max_stock = intval($_POST['max_stock'] ?? 0);
$description = sanitize($_POST['description'] ?? '');
$user_id = $_SESSION['user_id'];

if ($id <= 0 || empty($name) || empty($sku) || $category_id <= 0 || $supplier_id <= 0 || $price <= 0) {
    $_SESSION['error'] = 'All required fields must be filled';
    header('Location: ../pages/admin/manage_products.php');
    exit();
}

// Check SKU uniqueness excluding this product
$sku_check = $conn->prepare("SELECT id FROM products WHERE sku = ? AND id <> ?");
$sku_check->bind_param('si', $sku, $id);
$sku_check->execute();
$sku_check_res = $sku_check->get_result();
if ($sku_check_res->num_rows > 0) {
    $_SESSION['error'] = 'SKU already exists for another product';
    header('Location: ../pages/admin/manage_products.php');
    exit();
}

$update = $conn->prepare("UPDATE products SET name = ?, description = ?, category_id = ?, supplier_id = ?, sku = ?, price = ?, cost = ?, quantity = ?, min_stock = ?, max_stock = ? WHERE id = ?");
$update->bind_param('ssiisddiiii', $name, $description, $category_id, $supplier_id, $sku, $price, $cost, $quantity, $min_stock, $max_stock, $id);


if (!$update->execute()) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to update product: ' . $conn->error]);
        exit();
    }
    $_SESSION['error'] = 'Failed to update product: ' . $conn->error;
    header('Location: ../pages/admin/manage_products.php');
    exit();
}

// Log activity
$log_query = "INSERT INTO activity_logs (user_id, action, description) VALUES (?, 'UPDATE_PRODUCT', ?)";
$log_stmt = $conn->prepare($log_query);
$desc = "Updated product: $name (ID: $id)";
$log_stmt->bind_param('is', $user_id, $desc);
$log_stmt->execute();

// If AJAX request, return updated product as JSON (including category and supplier names)
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name, s.name as supplier_name FROM products p JOIN categories c ON p.category_id = c.id JOIN suppliers s ON p.supplier_id = s.id WHERE p.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $product = $res->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'product' => $product]);
    exit();
}

$_SESSION['success'] = 'Product updated successfully!';
header('Location: ../pages/admin/manage_products.php');
exit();

?>