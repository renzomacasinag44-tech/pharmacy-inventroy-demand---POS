<?php
/**
 * POS Product Search API
 * Searches products by name, SKU, or barcode
 * Returns JSON with product details including stock and expiration info
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Check authentication
if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check role access (Owner or Staff only)
if (!Auth::hasAnyRole(['owner', 'staff'])) {
    echo json_encode(['success' => false, 'error' => 'POS access denied']);
    exit();
}

$searchTerm = trim($_GET['search'] ?? '');

if (strlen($searchTerm) < 1) {
    $query = "SELECT 
        p.id,
        p.name,
        p.sku,
        p.price,
        p.quantity,
        p.unit,
        p.barcode,
        p.category_id,
        p.min_stock,
        p.status,
        c.name as category_name,
        MAX(it.expiration_date) as expiration_date
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN inventory_transactions it ON p.id = it.product_id AND it.transaction_type = 'stock_in' AND it.expiration_date IS NOT NULL
    WHERE p.status = 'active' AND p.quantity > 0
    GROUP BY p.id
    HAVING MAX(it.expiration_date) IS NULL OR MAX(it.expiration_date) >= CURDATE()
    ORDER BY c.name ASC, p.name ASC
    LIMIT 200";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
        exit();
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];

    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'sku' => $row['sku'],
            'price' => (float)$row['price'],
            'quantity' => (int)$row['quantity'],
            'unit' => $row['unit'] ?? 'unit',
            'barcode' => $row['barcode'],
            'category' => $row['category_name'] ?? 'Uncategorized',
            'min_stock' => (int)$row['min_stock'],
            'status' => $row['status'] ?? 'active',
            'expiration_date' => $row['expiration_date'] ? date('Y-m-d', strtotime($row['expiration_date'])) : null
        ];
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);
    exit();
}

// Search-specific query proceeds below for non-empty search values
$searchLike = '%' . $searchTerm . '%';

$query = "SELECT 
    p.id,
    p.name,
    p.sku,
    p.price,
    p.quantity,
    p.unit,
    p.barcode,
    p.category_id,
    p.min_stock,
    p.status,
    c.name as category_name,
    MAX(it.expiration_date) as expiration_date
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN inventory_transactions it ON p.id = it.product_id AND it.transaction_type = 'stock_in' AND it.expiration_date IS NOT NULL
WHERE p.status = 'active'
AND p.quantity > 0
AND (
    p.name LIKE ? 
    OR p.sku LIKE ?
    OR p.barcode LIKE ?
    OR c.name LIKE ?
)
GROUP BY p.id
HAVING MAX(it.expiration_date) IS NULL OR MAX(it.expiration_date) >= CURDATE()
ORDER BY c.name ASC, p.name ASC
LIMIT 200";

$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param('ssss', $searchLike, $searchLike, $searchLike, $searchLike);
$stmt->execute();
$result = $stmt->get_result();

$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'sku' => $row['sku'],
        'price' => (float)$row['price'],
        'quantity' => (int)$row['quantity'],
        'unit' => $row['unit'] ?? 'unit',
        'barcode' => $row['barcode'],
        'category' => $row['category_name'] ?? 'Uncategorized',
        'min_stock' => (int)$row['min_stock'],
        'status' => $row['status'] ?? 'active',
        'expiration_date' => $row['expiration_date'] ? date('Y-m-d', strtotime($row['expiration_date'])) : null
    ];
}

$stmt->close();

echo json_encode([
    'success' => true,
    'products' => $products,
    'count' => count($products)
]);

?>
