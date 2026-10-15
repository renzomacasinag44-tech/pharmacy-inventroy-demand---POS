<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!Auth::hasRole('admin')) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: ../pages/admin/manage_products.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid request']);
        exit();
    }
    $_SESSION['error'] = 'Invalid request';
    header('Location: ../pages/admin/manage_products.php');
    exit();
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid product ID']);
        exit();
    }
    $_SESSION['error'] = 'Invalid product ID';
    header('Location: ../pages/admin/manage_products.php');
    exit();
}

// Soft-delete: mark as expired
$del = $conn->prepare("UPDATE products SET status = 'expired' WHERE id = ?");
$del->bind_param('i', $id);
if (!$del->execute()) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to delete product: ' . $conn->error]);
        exit();
    }
    $_SESSION['error'] = 'Failed to delete product: ' . $conn->error;
    header('Location: ../pages/admin/manage_products.php');
    exit();
}

// Log activity
$user_id = $_SESSION['user_id'];
$log_query = "INSERT INTO activity_logs (user_id, action, description) VALUES (?, 'DELETE_PRODUCT', ?)";
$log_stmt = $conn->prepare($log_query);
$desc = "Soft-deleted product ID: $id";
$log_stmt->bind_param('is', $user_id, $desc);
$log_stmt->execute();

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
}

$_SESSION['success'] = 'Product deleted successfully';
header('Location: ../pages/admin/manage_products.php');
exit();
?>