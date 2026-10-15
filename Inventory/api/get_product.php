<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!Auth::hasRole('admin')) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['error' => 'Invalid ID']);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Product not found']);
    exit();
}
$product = $result->fetch_assoc();

echo json_encode($product);
exit();
?>