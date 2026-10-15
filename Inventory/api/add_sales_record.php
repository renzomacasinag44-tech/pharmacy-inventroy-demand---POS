<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($is_ajax) header('Content-Type: application/json');
if (!Auth::isLoggedIn()) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    } else {
        $_SESSION['error'] = 'Unauthorized';
        header('Location: ' . APP_URL . 'pages/admin/sales_records.php');
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit();
}

$product_id = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 0);
$price = floatval($_POST['price'] ?? 0);
$reference = isset($_POST['batch']) ? trim($_POST['batch']) : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$transaction_date = isset($_POST['transaction_date']) ? $_POST['transaction_date'] : date('Y-m-d H:i:s');
$user_id = $_SESSION['user_id'] ?? 0;

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product or quantity']);
    exit();
}

// Check stock
$pstmt = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
$pstmt->bind_param('i', $product_id);
$pstmt->execute();
$pres = $pstmt->get_result();
if ($pres->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit();
}
$p = $pres->fetch_assoc();
if ($p['quantity'] < $quantity) {
    echo json_encode(['success' => false, 'error' => 'Insufficient stock. Available: ' . $p['quantity']]);
    exit();
}

$conn->begin_transaction();
try {
    // Insert inventory transaction
    $itq = "INSERT INTO inventory_transactions (product_id, transaction_type, quantity, reference_number, notes, recorded_by, transaction_date) VALUES (?, 'sale', ?, ?, ?, ?, ?)";
    $itstmt = $conn->prepare($itq);
    $itstmt->bind_param('iissis', $product_id, $quantity, $reference, $notes, $user_id, $transaction_date);
    $itstmt->execute();

    // Insert sale record
    $total = $quantity * $price;
    $sq = "INSERT INTO sales (product_id, quantity, price, total_amount, recorded_by, created_at, notes) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $sstmt = $conn->prepare($sq);
    $sstmt->bind_param('iiddiss', $product_id, $quantity, $price, $total, $user_id, $transaction_date, $notes);
    $sstmt->execute();
    $sale_id = $conn->insert_id;

    // Update product quantity
    $up = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
    $up->bind_param('ii', $quantity, $product_id);
    $up->execute();

    $conn->commit();

    // Fetch the inserted sale with product/user names
    $fq = "SELECT s.id as sale_id, s.created_at, s.product_id, p.name as product_name, p.sku, s.quantity, s.price, s.total_amount, u.full_name as recorded_by_name, 
            (SELECT it.reference_number FROM inventory_transactions it WHERE it.product_id = s.product_id AND it.transaction_type = 'stock_in' AND it.transaction_date <= s.created_at ORDER BY it.transaction_date DESC LIMIT 1) as batch_number
           FROM sales s LEFT JOIN products p ON s.product_id = p.id LEFT JOIN users u ON s.recorded_by = u.id WHERE s.id = ?";
    $ff = $conn->prepare($fq);
    $ff->bind_param('i', $sale_id);
    $ff->execute();
    $res = $ff->get_result();
    $row = $res->fetch_assoc();

    if ($is_ajax) {
        echo json_encode(['success' => true, 'sale' => $row]);
        exit();
    } else {
        $_SESSION['success'] = 'Sale recorded successfully';
        header('Location: ' . APP_URL . 'pages/admin/sales_records.php');
        exit();
    }
} catch (Exception $e) {
    $conn->rollback();
    if ($is_ajax) {
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
        exit();
    } else {
        $_SESSION['error'] = 'Server error: ' . $e->getMessage();
        header('Location: ' . APP_URL . 'pages/admin/sales_records.php');
        exit();
    }
}

?>
