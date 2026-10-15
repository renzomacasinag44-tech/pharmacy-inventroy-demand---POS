<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/navbar.php';

// Check if user is logged in and is staff
if (!Auth::hasRole('staff')) {
    header('Location: ../../login.php?error=Unauthorized access');
    exit();
}

$stats = getDashboardStats($conn, 'staff');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - MangaRingMed Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <!-- SIDEBAR -->
    <?php renderSidebar('dashboard'); ?>
    
    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <!-- TOP NAVBAR -->
        <?php renderTopNavbar(); ?>
        
        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <div class="page-title">
                    <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
                    <p>Your daily inventory management tasks</p>
                </div>
            </div>
            
            <!-- QUICK STATS -->
            <div class="dashboard-grid">
                <!-- Total Products -->
                <div class="stat-card info">
                    <div class="stat-card-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value"><?php echo $stats['total_products']; ?></div>
                    <div class="stat-change">Available</div>
                </div>
                
                <!-- Today's Stock In -->
                <div class="stat-card success">
                    <div class="stat-card-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="stat-label">Stock In Today</div>
                    <div class="stat-value">0</div>
                    <div class="stat-change">Units received</div>
                </div>
                
                <!-- Today's Sales -->
                <div class="stat-card success">
                    <div class="stat-card-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-label">Sales Today</div>
                    <div class="stat-value"><?php echo formatCurrency($stats['today_sales_amount'] ?? 0); ?></div>
                    <div class="stat-change">Sales in pesos</div>
                </div>

                <!-- Units Sold -->
                <div class="stat-card warning">
                    <div class="stat-card-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="stat-label">Units Sold</div>
                    <div class="stat-value"><?php echo $stats['today_sales']; ?></div>
                    <div class="stat-change">Items sold today</div>
                </div>
                
                <!-- Low Stock Alert -->
                <div class="stat-card danger">
                    <div class="stat-card-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-label">Low Stock Alert</div>
                    <div class="stat-value"><?php echo $stats['low_stock']; ?></div>
                    <div class="stat-change">Notify manager</div>
                </div>
            </div>
            
            <!-- QUICK ACTIONS -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div style="text-align: center;">
                                <i class="fas fa-plus-circle" style="font-size: 40px; color: #1e7e34; margin-bottom: 15px;"></i>
                                <h5>Record Stock In</h5>
                                <p class="text-muted">Add received products to inventory</p>
                                <a href="record_stock_in.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-arrow-up"></i> Record
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div style="text-align: center;">
                                <i class="fas fa-minus-circle" style="font-size: 40px; color: #ffc107; margin-bottom: 15px;"></i>
                                <h5>Record Stock Out</h5>
                                <p class="text-muted">Record sold or used products</p>
                                <a href="record_stock_out.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-arrow-down"></i> Record
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div style="text-align: center;">
                                <i class="fas fa-search" style="font-size: 40px; color: #17a2b8; margin-bottom: 15px;"></i>
                                <h5>View Products</h5>
                                <p class="text-muted">Search and view product details</p>
                                <a href="view_products.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-box"></i> Browse
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- LOW STOCK ALERTS -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-bell"></i> Low Stock Alerts</h5>
                </div>
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>SKU</th>
                                <th>Current Stock</th>
                                <th>Min Level</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $alerts = $conn->query("SELECT * FROM products WHERE quantity <= min_stock ORDER BY quantity ASC LIMIT 10");
                            
                            if ($alerts->num_rows > 0):
                                while ($product = $alerts->fetch_assoc()):
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($product['sku']); ?></span></td>
                                <td><?php echo $product['quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?></td>
                                <td><?php echo $product['min_stock']; ?></td>
                                <td>
                                    <?php if ($product['quantity'] <= 0): ?>
                                        <span class="badge bg-danger">OUT OF STOCK</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">LOW STOCK</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle"></i> All products are in good stock level
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/script.js"></script>
    <script>
            document.querySelector('.sidebar').classList.toggle('show');
            document.querySelector('.main-wrapper').classList.toggle('sidebar-collapsed');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
