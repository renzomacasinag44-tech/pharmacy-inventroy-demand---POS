<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Only logged-in Admin or Owner can access the registration page
if (!Auth::isLoggedIn() || !Auth::hasAnyRole(['admin', 'owner'])) {
    header('Location: login.php?error=Only Admin or Owner can create accounts');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MangaringMed Pharmacy - Create Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: linear-gradient(135deg, #1e7e34 0%, #2d9b4a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .login-header {
            background: linear-gradient(135deg, #1e7e34 0%, #2d9b4a 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }
        .logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .logo i {
            font-size: 45px;
            color: #1e7e34;
        }
        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .login-header p {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 0;
        }
        .login-body {
            padding: 40px 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #1e7e34;
            box-shadow: 0 0 0 3px rgba(30, 126, 52, 0.1);
            background-color: #f0f9f3;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #1e7e34 0%, #2d9b4a 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(30, 126, 52, 0.3);
        }
        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
            font-size: 14px;
            animation: slideDown 0.3s ease-out;
        }
        .alert-danger { background-color: #f8d7da; color: #721c24; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .login-footer {
            padding: 20px 30px;
            background-color: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            font-size: 13px;
            color: #666;
        }
        @media (max-width: 576px) {
            .login-container { margin: 20px; }
            .login-body { padding: 25px 20px; }
            .logo { width: 60px; height: 60px; }
            .logo i { font-size: 35px; }
            .login-header h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo"><i class="fas fa-pills"></i></div>
            <h1>Create Account</h1>
            <p>Register as Staff for MangaRingMed Pharmacy</p>
        </div>
        <div class="login-body">
            <?php
            if (isset($_GET['msg'])) {
                echo '<div class="alert alert-info">' . htmlspecialchars($_GET['msg']) . '</div>';
            }
            if (isset($_GET['error'])) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            if (isset($_GET['success'])) {
                echo '<div class="alert alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
            }
            ?>
            <form method="POST" action="process_register.php">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" placeholder="Your full name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Your email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm password" required>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            <div class="text-center" style="margin-top: 10px;">
                Already have an account? <a href="login.php" style="color: #1e7e34; text-decoration: none;">Sign in</a>
            </div>
        </div>
        <div class="login-footer">
            <p>© 2026 MangaRingMed Pharmacy. All rights reserved.</p>
            <p>Inventory Management System v1.0</p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
