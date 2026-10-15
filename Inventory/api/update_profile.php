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
$full_name = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');

// Validation
if (empty($full_name)) {
    $_SESSION['error'] = 'Full name is required';
    header('Location: ../pages/profile.php');
    exit();
}

if (strlen($full_name) > 100 || strlen($phone) > 20) {
    $_SESSION['error'] = 'Name must be 100 characters or fewer and phone must be 20 characters or fewer';
    header('Location: ../pages/profile.php');
    exit();
}

// Update user
$query = "UPDATE users SET full_name = ?, phone = ?, updated_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssi", $full_name, $phone, $user_id);

if (!$stmt->execute()) {
    $_SESSION['error'] = 'Failed to update profile: ' . $conn->error;
    header('Location: ../pages/profile.php');
    exit();
}

// Update session
$_SESSION['name'] = $full_name;

// Log activity
$log_query = "INSERT INTO activity_logs (user_id, action, description) VALUES (?, 'PROFILE_UPDATE', ?)";
$log_stmt = $conn->prepare($log_query);
$desc = "Updated profile information";
$log_stmt->bind_param("is", $user_id, $desc);
$log_stmt->execute();

$_SESSION['success'] = 'Profile updated successfully!';
header('Location: ../pages/profile.php');
exit();

$conn->close();
?>
