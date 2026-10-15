<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!Auth::isLoggedIn()) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Forbidden';
    exit();
}

$product_search = isset($_GET['product_search']) ? trim($_GET['product_search']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

$where = [];
$params = [];
$types = '';
if ($product_search !== '') {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? )";
    $like = "%" . $product_search . "%";
    $params[] = $like; $params[] = $like;
    $types .= 'ss';
}
if ($date_from !== '') {
    $where[] = 's.created_at >= ?';
    $params[] = $date_from . ' 00:00:00';
    $types .= 's';
}
if ($date_to !== '') {
    $where[] = 's.created_at <= ?';
    $params[] = $date_to . ' 23:59:59';
    $types .= 's';
}
$where_sql = '';
if (count($where) > 0) $where_sql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT s.id as sale_id, s.created_at, p.name as product_name, p.sku, s.quantity, s.price, s.total_amount, u.full_name as recorded_by_name
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.id
        LEFT JOIN users u ON s.recorded_by = u.id
        $where_sql
        ORDER BY s.created_at DESC";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="sales_export_' . date('Ymd_His') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Sale ID','Date & Time','Product','SKU','Quantity','Unit Price','Total','Recorded By']);

if ($stmt = $conn->prepare($sql)) {
    if (!empty($types)) {
        $bind_names = [];
        $bind_names[] = $types;
        for ($i=0; $i<count($params); $i++) {
            $bind_name = 'b' . $i;
            $$bind_name = $params[$i];
            $bind_names[] = &$$bind_name;
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        fputcsv($out, [$r['sale_id'], $r['created_at'], $r['product_name'], $r['sku'], $r['quantity'], $r['price'], $r['total_amount'], $r['recorded_by_name']]);
    }
} else {
    $res = $conn->query($sql);
    while ($r = $res->fetch_assoc()) fputcsv($out, [$r['sale_id'], $r['created_at'], $r['product_name'], $r['sku'], $r['quantity'], $r['price'], $r['total_amount'], $r['recorded_by_name']]);
}

fclose($out);
exit();

?>
