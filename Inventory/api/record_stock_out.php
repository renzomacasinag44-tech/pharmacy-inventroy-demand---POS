<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Validate authentication
if (!Auth::isLoggedIn()) {
    header('Location: ../login.php?error=Unauthorized access');
    exit();
}

if (!Auth::hasAnyRole(['staff', 'admin', 'owner'])) {
    header('Location: ../login.php?error=Unauthorized access');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/staff/record_stock_out.php');
    exit();
}

$product_id = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 0);
$transaction_type = isset($_POST['transaction_type']) ? htmlspecialchars($_POST['transaction_type']) : 'stock_out';
$customer_name = isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : '';
$price = floatval($_POST['price'] ?? 0);
$notes = isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '';
$transaction_date = isset($_POST['transaction_date']) ? $_POST['transaction_date'] : date('Y-m-d');
$user_id = $_SESSION['user_id'];

if ($product_id <= 0 || $quantity <= 0) {
    $_SESSION['error'] = 'Invalid product or quantity';
    header('Location: ' . APP_URL . 'pages/staff/record_stock_out.php');
    exit();
}

// Check if enough stock
$stock_check = $conn->query("SELECT quantity FROM products WHERE id = $product_id");
if ($stock_check->num_rows === 0) {
    $_SESSION['error'] = 'Product not found';
    header('Location: ' . APP_URL . 'pages/staff/record_stock_out.php');
    exit();
}

$product = $stock_check->fetch_assoc();
if ($product['quantity'] < $quantity) {
    $_SESSION['error'] = 'Insufficient stock. Available: ' . $product['quantity'];
    header('Location: ' . APP_URL . 'pages/staff/record_stock_out.php');
    exit();
}

// Insert into inventory transactions
$query = "INSERT INTO inventory_transactions (product_id, transaction_type, quantity, notes, recorded_by, transaction_date) 
          VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param("isissi", $product_id, $transaction_type, $quantity, $notes, $user_id, $transaction_date);
$stmt->execute();

// If it's a sale, also record in sales table
if (strtolower($transaction_type) === 'sale') {
    $total_amount = $quantity * $price;
    $sales_query = "INSERT INTO sales (product_id, quantity, price, total_amount, customer_name, recorded_by) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    
    $sales_stmt = $conn->prepare($sales_query);
    $sales_stmt->bind_param("iidsi", $product_id, $quantity, $price, $total_amount, $customer_name, $user_id);
    $sales_stmt->execute();
}

// Update product quantity
$update_query = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("ii", $quantity, $product_id);
$update_stmt->execute();

$_SESSION['success'] = 'Stock out recorded successfully!';
header('Location: ' . APP_URL . 'pages/staff/record_stock_out.php');
exit();
?>
