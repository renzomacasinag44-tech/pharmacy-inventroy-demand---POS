<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/navbar.php';

if (!Auth::hasAnyRole(['owner', 'admin'])) {
    header('Location: ../../login.php?error=Unauthorized access');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Record Stock - Owner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php renderSidebar('stock_in_out'); ?>
    <div class="main-wrapper">
        <?php renderTopNavbar(); ?>
        <div class="main-content">
            <div class="page-header">
                <div class="page-title">
                    <h1><i class="fas fa-plus-minus"></i> Record Stock</h1>
                    <p>Choose stock in or stock out for inventory updates.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-arrow-up"></i> Stock In</h5>
                            <p>Record incoming inventory from suppliers.</p>
                            <a href="../staff/record_stock_in.php" class="btn btn-success">Go to Stock In</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-arrow-down"></i> Stock Out</h5>
                            <p>Record outgoing inventory movements.</p>
                            <a href="../staff/record_stock_out.php" class="btn btn-warning">Go to Stock Out</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/script.js"></script>
</body>
</html>
