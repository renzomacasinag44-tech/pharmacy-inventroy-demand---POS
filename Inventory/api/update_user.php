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
    header('Location: ../pages/admin/manage_users.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid request']);
        exit();
    }
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid request');
}

$id = intval($_POST['id'] ?? 0);
$full_name = sanitize($_POST['full_name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$role_id = intval($_POST['role_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($id <= 0 || empty($full_name) || empty($email) || $role_id <= 0) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'All required fields must be filled']);
        exit();
    }
    $_SESSION['error'] = 'All required fields must be filled';
    header('Location: ../pages/admin/manage_users.php');
    exit();
}

if (!validateEmail($email)) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid email format']);
        exit();
    }
    $_SESSION['error'] = 'Invalid email format';
    header('Location: ../pages/admin/manage_users.php');
    exit();
}

// Check email uniqueness
$email_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
$email_check->bind_param('si', $email, $id);
$email_check->execute();
$res = $email_check->get_result();
if ($res->num_rows > 0) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Email already used by another account']);
        exit();
    }
    $_SESSION['error'] = 'Email already used by another account';
    header('Location: ../pages/admin/manage_users.php');
    exit();
}

$update = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role_id = ? WHERE id = ?");
$update->bind_param('sssii', $full_name, $email, $phone, $role_id, $id);
if (!$update->execute()) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to update user: ' . $conn->error]);
        exit();
    }
    $_SESSION['error'] = 'Failed to update user: ' . $conn->error;
    header('Location: ../pages/admin/manage_users.php');
    exit();
}

// Log
$log_query = "INSERT INTO activity_logs (user_id, action, description) VALUES (?, 'UPDATE_USER', ?)";
$log_stmt = $conn->prepare($log_query);
$desc = "Updated user: $full_name (ID: $id)";
$log_stmt->bind_param('is', $user_id, $desc);
$log_stmt->execute();

// Return updated user for AJAX
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $r = $stmt->get_result();
    $user = $r->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'user' => $user]);
    exit();
}

$_SESSION['success'] = 'User updated successfully';
header('Location: ../pages/admin/manage_users.php');
exit();
?>