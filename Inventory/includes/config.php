<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pharmacy_inventory');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8");

// Application Configuration
define('APP_NAME', 'MangaringMed Pharmacy Inventory System');
define('APP_URL', 'http://localhost/Inventory/');
define('APP_VERSION', '1.0.0');

// Session timeout in seconds (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Timezone
date_default_timezone_set('Asia/Manila');
?>
