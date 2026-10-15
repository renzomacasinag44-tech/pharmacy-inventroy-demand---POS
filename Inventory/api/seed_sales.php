<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
if (!Auth::isLoggedIn() || !Auth::hasRole('admin')) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$maxInserts = intval($_POST['count'] ?? 20);
if ($maxInserts <= 0) $maxInserts = 20;

$products = $conn->query("SELECT id, quantity, price FROM products WHERE status = 'active' AND quantity > 0 ORDER BY id LIMIT 500");
$inserted = 0;

while ($prod = $products->fetch_assoc()) {
    if ($inserted >= $maxInserts) break;
    $pid = $prod['id'];
    $avail = intval($prod['quantity']);
    if ($avail <= 0) continue;
    $qty = rand(1, min(5, $avail));
    $price = floatval($prod['price']);
    $total = $qty * $price;
    $userRes = $conn->query("SELECT id FROM users WHERE status='active' ORDER BY id LIMIT 1");
    $uid = ($userRes && $userRes->num_rows>0) ? $userRes->fetch_assoc()['id'] : 1;

    $created = date('Y-m-d H:i:s', strtotime('-' . rand(0,14) . ' days -' . rand(0, 86400) . ' seconds'));

    $conn->begin_transaction();
    try {
        $itq = "INSERT INTO inventory_transactions (product_id, transaction_type, quantity, reference_number, notes, recorded_by, transaction_date) VALUES (?, 'sale', ?, NULL, ?, ?, ?)";
        $itstmt = $conn->prepare($itq);
        $notes = 'Seeder generated sale';
        $itstmt->bind_param('iisss', $pid, $qty, $notes, $uid, $created);
        $itstmt->execute();

        $sq = "INSERT INTO sales (product_id, quantity, price, total_amount, recorded_by, created_at, notes) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $sstmt = $conn->prepare($sq);
        $sstmt->bind_param('iiddiss', $pid, $qty, $price, $total, $uid, $created, $notes);
        $sstmt->execute();

        $up = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
        $up->bind_param('ii', $qty, $pid);
        $up->execute();

        $conn->commit();
        $inserted++;
    } catch (Exception $e) {
        $conn->rollback();
    }
}

echo json_encode(['success' => true, 'inserted' => $inserted]);
exit();

?>
