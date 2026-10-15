<?php
session_start();
require_once 'config.php';

class Auth {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    // Check if user is logged in
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['role']);
    }
    
    // Check user role
    public static function hasRole($role) {
        if (!isset($_SESSION['role'])) {
            return false;
        }

        if ($role === 'admin') {
            return in_array($_SESSION['role'], ['admin', 'superadmin'], true);
        }

        return $_SESSION['role'] === $role;
    }
    
    // Check multiple roles
    public static function hasAnyRole($roles) {
        if (!isset($_SESSION['role'])) return false;

        $userRole = strtolower($_SESSION['role']);
        foreach ($roles as $role) {
            $role = strtolower($role);
            if ($role === 'admin' && self::hasRole('admin')) {
                return true;
            }
            if ($userRole === $role) {
                return true;
            }
        }

        return false;
    }
    
    // Login user
    public function login($email, $password) {
        $query = "SELECT u.*, r.role_name FROM users u 
                  JOIN roles r ON u.role_id = r.id 
                  WHERE u.email = ? AND u.status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['name'] = $user['full_name'];
                $_SESSION['role'] = strtolower($user['role_name']);
                $this->logActivity($user['id'], 'LOGIN', 'User logged in');
                
                $stmt->close();
                return true;
            }
        }
        
        $stmt->close();
        return false;
    }
    
    // Logout user
    public static function logout() {
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            session_destroy();
            return true;
        }
        return false;
    }
    
    // Log user activity
    private function logActivity($userId, $action, $description) {
        $query = "INSERT INTO activity_logs (user_id, action, description, timestamp, ip_address) 
                  VALUES (?, ?, ?, NOW(), ?)";
        
        $stmt = $this->conn->prepare($query);
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt->bind_param("isss", $userId, $action, $description, $ip);
        $stmt->execute();
        $stmt->close();
    }
    
    // Register new user (Admin only)
    public function registerUser($email, $password, $fullName, $roleId) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $query = "INSERT INTO users (email, password, full_name, role_id, status, created_at) 
                  VALUES (?, ?, ?, ?, 'active', NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssi", $email, $hashedPassword, $fullName, $roleId);
        
        if ($stmt->execute()) {
            $stmt->close();
            return $this->conn->insert_id;
        }
        
        $stmt->close();
        return false;
    }
    
    // Get user by ID
    public function getUserById($userId) {
        $query = "SELECT u.*, r.role_name FROM users u 
                  JOIN roles r ON u.role_id = r.id 
                  WHERE u.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        return $user;
    }
    
    // Check session timeout
    public static function checkTimeout() {
        if (isset($_SESSION['login_time'])) {
            if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
                session_destroy();
                return false;
            }
        }
        return true;
    }
}

// Redirect if not logged in
$allowed_pages = ['login.php', 'register.php', 'process_login.php', 'process_register.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (!Auth::isLoggedIn() && !in_array($current_page, $allowed_pages, true)) {
    header('Location: ' . APP_URL . 'login.php');
    exit();
}

// Check session timeout
if (!Auth::checkTimeout() && $current_page !== 'login.php' && $current_page !== 'process_login.php') {
    header('Location: ' . APP_URL . 'login.php?msg=Session expired');
    exit();
}
?>
