<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$product_id = intval($_GET['product_id'] ?? 0);
if ($product_id <= 0) {
    echo json_encode(['success' => true, 'batches' => []]);
    exit();
}

$stmt = $conn->prepare("SELECT DISTINCT reference_number, transaction_date FROM inventory_transactions WHERE product_id = ? AND transaction_type = 'stock_in' AND reference_number IS NOT NULL ORDER BY transaction_date DESC LIMIT 100");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$res = $stmt->get_result();
$batches = [];
while ($r = $res->fetch_assoc()) {
    $batches[] = ['reference' => $r['reference_number'], 'date' => $r['transaction_date']];
}

echo json_encode(['success' => true, 'batches' => $batches]);
exit();

?>
