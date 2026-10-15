<?php
/**
 * Seeder for sales records based on current products.
 * Run from browser or CLI to insert sample sales for testing.
 * Usage (browser): http://localhost/Inventory/tools/seed_sales_records.php
 * Usage (cli): php tools/seed_sales_records.php
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Only allow when accessed locally
if (php_sapi_name() !== 'cli') {
    // simple check to allow local dev only
    if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
        echo "Forbidden\n"; exit;
    }
}

$maxInserts = 20; // number of sales to create
$products = $conn->query("SELECT id, quantity, price FROM products WHERE status = 'active' AND quantity > 0 ORDER BY id LIMIT 200");
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

echo "Inserted $inserted sample sales records.\n";

?>
