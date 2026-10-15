<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$auth = new Auth($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        header('Location: login.php?error=Email and password are required');
        exit();
    }
    
    if (!validateEmail($email)) {
        header('Location: login.php?error=Invalid email format');
        exit();
    }
    
    // Attempt login
    if ($auth->login($email, $password)) {
        // Redirect based on role
        switch ($_SESSION['role']) {
            case 'admin':
            case 'superadmin':
                header('Location: pages/admin/dashboard.php');
                break;
            case 'owner':
                header('Location: pages/owner/dashboard.php');
                break;
            case 'staff':
                header('Location: pages/staff/dashboard.php');
                break;
            default:
                header('Location: login.php?error=Invalid role');
        }
        exit();
    } else {
        header('Location: login.php?error=Invalid email or password');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}
?>
