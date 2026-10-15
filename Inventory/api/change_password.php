<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: ../pages/profile.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid request');
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    $_SESSION['error'] = 'All password fields are required';
    header('Location: ../pages/profile.php');
    exit();
}

if ($new_password !== $confirm_password) {
    $_SESSION['error'] = 'New passwords do not match';
    header('Location: ../pages/profile.php');
    exit();
}

if (strlen($new_password) < 6) {
    $_SESSION['error'] = 'Password must be at least 6 characters long';
    header('Location: ../pages/profile.php');
    exit();
}

// Get current password hash
$query = "SELECT password FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Verify current password
if (!password_verify($current_password, $user['password'])) {
    $_SESSION['error'] = 'Current password is incorrect';
    header('Location: ../pages/profile.php');
    exit();
}

// Hash new password
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

// Update password
$update_query = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("si", $hashed_password, $user_id);

if (!$update_stmt->execute()) {
    $_SESSION['error'] = 'Failed to update password: ' . $conn->error;
    header('Location: ../pages/profile.php');
    exit();
}

// Log activity
$log_query = "INSERT INTO activity_logs (user_id, action, description) VALUES (?, 'PASSWORD_CHANGE', ?)";
$log_stmt = $conn->prepare($log_query);
$desc = "Changed password";
$log_stmt->bind_param("is", $user_id, $desc);
$log_stmt->execute();

$_SESSION['success'] = 'Password changed successfully!';
header('Location: ../pages/profile.php');
exit();

$conn->close();
?>
