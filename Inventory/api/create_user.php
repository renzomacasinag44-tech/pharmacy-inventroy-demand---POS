<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!Auth::hasRole('admin')) {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: ../pages/admin/manage_users.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid request');
}

$full_name = sanitize($_POST['full_name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$phone = sanitize($_POST['phone'] ?? '');
$role_id = intval($_POST['role_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Validation
if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password) || $role_id <= 0) {
    $_SESSION['error'] = 'All required fields must be filled';
    header('Location: ../pages/admin/manage_users.php');
    exit();
}

if (!validateEmail($email)) {
    $_SESSION['error'] = 'Invalid email format';
    header('Location: ../pages/admin/manage_users.php');
    exit();
}

if ($password !== $confirm_password) {
    $_SESSION['error'] = 'Passwords do not match';
    header('Location: ../pages/admin/manage_users.php');
    exit();
}

if (strlen($password) < 6) {
    $_SESSION['error'] = 'Password must be at least 6 characters';
    header('Location: ../pages/admin/manage_users.php');
    exit();
}

// Check if email already exists
$email_check = $conn->query("SELECT id FROM users WHERE email = '$email'");
if ($email_check->num_rows > 0) {
    $_SESSION['error'] = 'Email already exists';
    header('Location: ../pages/admin/manage_users.php');
    exit();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Insert user
$query = "INSERT INTO users (email, password, full_name, role_id, phone, status) 
          VALUES (?, ?, ?, ?, ?, 'active')";

$stmt = $conn->prepare($query);
$stmt->bind_param("sssss", $email, $hashed_password, $full_name, $role_id, $phone);

if (!$stmt->execute()) {
    $_SESSION['error'] = 'Failed to create user: ' . $conn->error;
    header('Location: ../pages/admin/manage_users.php');
    exit();
}

// Log activity
$log_query = "INSERT INTO activity_logs (user_id, action, description) VALUES (?, 'CREATE_USER', ?)";
$log_stmt = $conn->prepare($log_query);
$desc = "Created new user: $full_name ($email)";
$log_stmt->bind_param("is", $user_id, $desc);
$log_stmt->execute();

$_SESSION['success'] = 'User created successfully!';
header('Location: ../pages/admin/manage_users.php');
exit();

$conn->close();
?>
