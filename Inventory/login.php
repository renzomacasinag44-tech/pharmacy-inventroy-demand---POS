<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MangaringMed Pharmacy - Inventory Login</title>
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
            max-width: 450px;
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #1e7e34;
            box-shadow: 0 0 0 3px rgba(30, 126, 52, 0.1);
            background-color: #f0f9f3;
        }
        
        .form-group input::placeholder {
            color: #999;
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .remember-forgot input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #1e7e34;
        }
        
        .remember-forgot a {
            color: #1e7e34;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .remember-forgot a:hover {
            color: #2d9b4a;
            text-decoration: underline;
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
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
            font-size: 14px;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .demo-credentials {
            background-color: #f0f9f3;
            border-left: 4px solid #1e7e34;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .demo-credentials strong {
            color: #1e7e34;
            display: block;
            margin-bottom: 8px;
        }
        
        .demo-item {
            margin-bottom: 8px;
            padding-left: 10px;
        }
        
        .demo-item span {
            background-color: #e8f5e9;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            color: #1e7e34;
            font-weight: 500;
        }
        
        .login-footer {
            padding: 20px 30px;
            background-color: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            font-size: 13px;
            color: #666;
        }
        
        .spinner-border {
            display: none;
            width: 18px;
            height: 18px;
            margin-right: 8px;
        }
        
        .btn-login.loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .btn-login.loading .spinner-border {
            display: inline-block;
        }
        
        @media (max-width: 576px) {
            .login-container {
                margin: 20px;
            }
            
            .login-body {
                padding: 25px 20px;
            }
            
            .logo {
                width: 60px;
                height: 60px;
            }
            
            .logo i {
                font-size: 35px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Header with Logo -->
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-pills"></i>
            </div>
            <h1>MangaRingMed</h1>
            <p>Pharmacy Inventory System</p>
        </div>
        
        <!-- Login Form -->
        <div class="login-body">
            <?php
            // Display messages
            if (isset($_GET['msg'])) {
                $msg = htmlspecialchars($_GET['msg']);
                echo '<div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle"></i> ' . $msg . '
                </div>';
            }
            
            if (isset($_GET['error'])) {
                $error = htmlspecialchars($_GET['error']);
                echo '<div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i> ' . $error . '
                </div>';
            }
            
            if (isset($_GET['success'])) {
                $success = htmlspecialchars($_GET['success']);
                echo '<div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i> ' . $success . '
                </div>';
            }
            ?>
            
            <form method="POST" action="process_login.php">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="Enter your email"
                        required
                        autocomplete="email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <div class="remember-forgot">
                    <label>
                        <input type="checkbox" name="remember" value="1">
                        Remember me
                    </label>
                    <a href="#" onclick="alert('Password reset feature coming soon!')">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn-login">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
            <!-- Sign-up disabled for public; only Admin/Owner can create accounts -->
            
            <!-- Demo Credentials -->
            <div class="demo-credentials">
                <strong><i class="fas fa-user-secret"></i> Demo Credentials:</strong>
                <div class="demo-item">
                    <strong>Admin:</strong> <span>admin@mangaringmed.com</span> / <span>admin123</span>
                </div>
                <div class="demo-item">
                    <strong>Owner:</strong> <span>owner@mangaringmed.com</span> / <span>admin123</span>
                </div>
                <div class="demo-item">
                    <strong>Staff:</strong> <span>staff@mangaringmed.com</span> / <span>admin123</span>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="login-footer">
            <p>© 2026 MangaRingMed Pharmacy. All rights reserved.</p>
            <p>Inventory Management System v1.0</p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form submission
        document.querySelector('form').addEventListener('submit', function() {
            const btn = document.querySelector('.btn-login');
            btn.classList.add('loading');
            btn.disabled = true;
        });
        
        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.3s ease';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    </script>
</body>
</html>
