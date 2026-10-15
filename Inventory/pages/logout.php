<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

Auth::logout();
header('Location: ' . APP_URL . 'login.php?msg=You have been logged out successfully');
exit();
?>