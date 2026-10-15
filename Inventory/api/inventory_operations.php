<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid request');
}

$action = $_GET['action'] ?? '';

// Record Stock In
if ($action === 'stock_in') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $reference_number = sanitize($_POST['reference_number'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');
    $user_id = $_SESSION['user_id'];
    
    if ($product_id <= 0 || $quantity <= 0) {
        $_SESSION['error'] = 'Invalid product or quantity';
        header('Location: ../pages/staff/record_stock_in.php');
        exit();
    }
    
    // Insert into inventory transactions
    $query = "INSERT INTO inventory_transactions (product_id, transaction_type, quantity, reference_number, notes, recorded_by, transaction_date) 
              VALUES (?, 'stock_in', ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iissi", $product_id, $quantity, $reference_number, $notes, $user_id);
    
    if (!$stmt->execute()) {
        $_SESSION['error'] = 'Failed to record stock in: ' . $conn->error;
        header('Location: ../pages/staff/record_stock_in.php');
        exit();
    }
    
    // Update product quantity
    $update_query = "UPDATE products SET quantity = quantity + ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ii", $quantity, $product_id);
    $update_stmt->execute();
    
    // Log activity
    $log_query = "INSERT INTO activity_logs (user_id, action, description) VALUES (?, 'STOCK_IN', ?)";
    $log_stmt = $conn->prepare($log_query);
    $desc = "Added $quantity units of product ID $product_id";
    $log_stmt->bind_param("is", $user_id, $desc);
    $log_stmt->execute();
    
    $_SESSION['success'] = 'Stock in recorded successfully!';
    header('Location: ../pages/staff/record_stock_in.php');
    exit();
}

// Record Stock Out
elseif ($action === 'stock_out') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $transaction_type = sanitize($_POST['transaction_type'] ?? 'stock_out');
    $customer_name = sanitize($_POST['customer_name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');
    $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');
    $user_id = $_SESSION['user_id'];
    
    if ($product_id <= 0 || $quantity <= 0) {
        $_SESSION['error'] = 'Invalid product or quantity';
        header('Location: ../pages/staff/record_stock_out.php');
        exit();
    }
    
    // Check if enough stock
    $stock_check = $conn->query("SELECT quantity FROM products WHERE id = $product_id");
    if ($stock_check->num_rows === 0) {
        $_SESSION['error'] = 'Product not found';
        header('Location: ../pages/staff/record_stock_out.php');
        exit();
    }
    
    $product = $stock_check->fetch_assoc();
    if ($product['quantity'] < $quantity) {
        $_SESSION['error'] = 'Insufficient stock. Available: ' . $product['quantity'];
        header('Location: ../pages/staff/record_stock_out.php');
        exit();
    }
    
    // Insert into inventory transactions
    $query = "INSERT INTO inventory_transactions (product_id, transaction_type, quantity, notes, recorded_by, transaction_date) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isissi", $product_id, $transaction_type, $quantity, $notes, $user_id, $transaction_date);
    $stmt->execute();
    
    // If it's a sale, also record in sales table
    if ($transaction_type === 'sale') {
        $total_amount = $quantity * $price;
        $sales_query = "INSERT INTO sales (product_id, quantity, price, total_amount, customer_name, recorded_by) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        
        $sales_stmt = $conn->prepare($sales_query);
        $sales_stmt->bind_param("iidds", $product_id, $quantity, $price, $total_amount, $customer_name, $user_id);
        $sales_stmt->execute();
    }
    
    // Update product quantity
    $update_query = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ii", $quantity, $product_id);
    $update_stmt->execute();
    
    // Log activity
    $log_query = "INSERT INTO activity_logs (user_id, action, description) VALUES (?, 'STOCK_OUT', ?)";
    $log_stmt = $conn->prepare($log_query);
    $desc = "Removed $quantity units of product ID $product_id ($transaction_type)";
    $log_stmt->bind_param("is", $user_id, $desc);
    $log_stmt->execute();
    
    $_SESSION['success'] = 'Stock out recorded successfully!';
    header('Location: ../pages/staff/record_stock_out.php');
    exit();
}

else {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid action');
}

$conn->close();
?>
