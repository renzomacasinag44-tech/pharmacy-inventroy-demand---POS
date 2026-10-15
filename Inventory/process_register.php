<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth($conn);

// Only logged-in Admin or Owner can create new accounts
if (!Auth::isLoggedIn() || !Auth::hasAnyRole(['admin', 'owner'])) {
    header('Location: login.php?error=Unauthorized access');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit();
}

$fullName = sanitize($_POST['full_name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
    header('Location: register.php?error=Please fill in all fields');
    exit();
}

if (!validateEmail($email)) {
    header('Location: register.php?error=Invalid email address');
    exit();
}

if ($password !== $confirmPassword) {
    header('Location: register.php?error=Passwords do not match');
    exit();
}

if (strlen($password) < 6) {
    header('Location: register.php?error=Password must be at least 6 characters');
    exit();
}

// Make sure email is unique
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param('s', $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    header('Location: register.php?error=Email already exists');
    exit();
}
$check->close();

// Default role for self-registered users is staff (role_id = 3)
$userId = $auth->registerUser($email, $password, $fullName, 3);

if ($userId === false) {
    header('Location: register.php?error=Unable to create account. Please try again.');
    exit();
}

header('Location: login.php?success=Account created successfully. Please login.');
exit();
?>
