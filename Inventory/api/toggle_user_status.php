<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!Auth::hasRole('admin')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid ID']);
    exit();
}

// Get current status
$stmt = $conn->prepare("SELECT status, full_name FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not found']);
    exit();
}
$row = $res->fetch_assoc();
$new_status = ($row['status'] === 'active') ? 'inactive' : 'active';

$up = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
$up->bind_param('si', $new_status, $id);
if (!$up->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to update status']);
    exit();
}

// Log
$user_id = $_SESSION['user_id'];
$log_query = "INSERT INTO activity_logs (user_id, action, description) VALUES (?, 'TOGGLE_USER_STATUS', ?)";
$log_stmt = $conn->prepare($log_query);
$desc = "Toggled user status: {$row['full_name']} (ID: $id) to $new_status";
$log_stmt->bind_param('is', $user_id, $desc);
$log_stmt->execute();

header('Content-Type: application/json');
echo json_encode(['success' => true, 'new_status' => $new_status]);
exit();
?>