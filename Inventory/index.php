<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// If already logged in, redirect to appropriate dashboard
if (Auth::isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: pages/admin/dashboard.php');
            break;
        case 'owner':
            header('Location: pages/owner/dashboard.php');
            break;
        case 'staff':
            header('Location: pages/staff/dashboard.php');
            break;
        default:
            header('Location: login.php');
    }
    exit();
}

// If not logged in, show login page
header('Location: login.php');
exit();
?>
