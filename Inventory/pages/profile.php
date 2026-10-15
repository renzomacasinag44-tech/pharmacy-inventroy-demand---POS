<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/navbar.php';

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header('Location: ' . APP_URL . 'login.php');
    exit();
}

$user = (new Auth($conn))->getUserById($_SESSION['user_id']);

if (!$user) {
    header('Location: ' . APP_URL . 'login.php');
    exit();
}

$profileMessage = $_SESSION['success'] ?? $_SESSION['error'] ?? '';
$profileMessageType = isset($_SESSION['success']) ? 'success' : 'danger';
unset($_SESSION['success'], $_SESSION['error']);
$profileInitial = strtoupper(substr(trim($user['full_name'] ?? ''), 0, 1)) ?: 'U';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - MangaRingMed Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- SIDEBAR -->
    <?php renderSidebar('profile'); ?>
    
    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <!-- TOP NAVBAR -->
        <?php renderTopNavbar(); ?>
        
        <!-- MAIN CONTENT -->
        <div class="main-content">
            <?php if ($profileMessage !== ''): ?>
                <div class="alert alert-<?php echo $profileMessageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($profileMessage, ENT_QUOTES, 'UTF-8'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- PAGE HEADER -->
            <div class="page-header">
                <div class="page-title">
                    <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                    <p>Manage your account information</p>
                </div>
            </div>
            
            <div class="row">
                <!-- Profile Info -->
                <div class="col-lg-4">
                    <div class="card text-center">
                        <div class="card-body" style="padding: 40px 20px;">
                            <div class="user-avatar" style="width: 100px; height: 100px; font-size: 40px; margin: 0 auto 20px;">
                                <?php echo htmlspecialchars($profileInitial, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                            <p class="text-muted mb-3" style="text-transform: uppercase; font-weight: 600;">
                                <?php echo htmlspecialchars($user['role_name']); ?>
                            </p>
                            <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                            <?php if (!empty($user['phone'])): ?>
                            <p class="text-muted"><?php echo htmlspecialchars($user['phone']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <small class="text-muted">
                                Joined: <?php echo !empty($user['created_at']) ? htmlspecialchars(formatDate($user['created_at']), ENT_QUOTES, 'UTF-8') : '—'; ?>
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-edit"></i> Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="../api/update_profile.php">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="100" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                                        <small class="text-muted">Email cannot be changed</small>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="20">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Role</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['role_name'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                                        <small class="text-muted">Role cannot be changed</small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Change Password -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5><i class="fas fa-lock"></i> Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="../api/change_password.php">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" name="new_password" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-key"></i> Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>
<?php $conn->close(); ?>
