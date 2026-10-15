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
    header('Location: ../pages/staff/record_stock_in.php');
    exit();
}

$product_id = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 0);
$reference_number = isset($_POST['reference_number']) ? htmlspecialchars($_POST['reference_number']) : '';
$notes = isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '';
$transaction_date = isset($_POST['transaction_date']) ? $_POST['transaction_date'] : date('Y-m-d');
$user_id = $_SESSION['user_id'];

if ($product_id <= 0 || $quantity <= 0) {
    $_SESSION['error'] = 'Invalid product or quantity';
    header('Location: ' . APP_URL . 'pages/staff/record_stock_in.php');
    exit();
}

// Insert into inventory transactions
$query = "INSERT INTO inventory_transactions (product_id, transaction_type, quantity, reference_number, notes, recorded_by, transaction_date) 
          VALUES (?, 'stock_in', ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param("iissi", $product_id, $quantity, $reference_number, $notes, $user_id);

if (!$stmt->execute()) {
    $_SESSION['error'] = 'Failed to record stock in';
    header('Location: ' . APP_URL . 'pages/staff/record_stock_in.php');
    exit();
}

// Update product quantity
$update_query = "UPDATE products SET quantity = quantity + ? WHERE id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("ii", $quantity, $product_id);
$update_stmt->execute();

$_SESSION['success'] = 'Stock in recorded successfully!';
header('Location: ' . APP_URL . 'pages/staff/record_stock_in.php');
exit();
?>
