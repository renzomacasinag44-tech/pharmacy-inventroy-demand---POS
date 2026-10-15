<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

Auth::logout();
header('Location: login.php?msg=You have been logged out successfully');
exit();
?>
