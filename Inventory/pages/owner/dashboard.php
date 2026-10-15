<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/navbar.php';

// Check if user is logged in and is owner
if (!Auth::hasRole('owner')) {
    header('Location: ../../login.php?error=Unauthorized access');
    exit();
}

$stats = getDashboardStats($conn, 'owner');

// Get owner-specific stats
$result = $conn->query("SELECT SUM(quantity * price) as total_value FROM products WHERE status = 'active'");
$owner_stats = $result->fetch_assoc();

// Build chart data for last 7 days
$owner_labels = [];
$sales_data = [];
$sales_by_day = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $owner_labels[] = date('D', strtotime($day));
    $sales_by_day[$day] = 0;
}

$sales_result = $conn->query("SELECT DATE(created_at) as day, SUM(quantity) as total FROM sales WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at)");
while ($row = $sales_result->fetch_assoc()) {
    if (isset($sales_by_day[$row['day']])) {
        $sales_by_day[$row['day']] = (int) $row['total'];
    }
}
foreach ($sales_by_day as $value) {
    $sales_data[] = $value;
}

// Product movement counts
$movement_result = $conn->query("SELECT p.id, COALESCE(SUM(s.quantity), 0) as total FROM products p LEFT JOIN sales s ON p.id = s.product_id AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY p.id");
$fast_moving_count = 0;
$slow_moving_count = 0;
$seasonal_count = 0;
$trending_count = 0;
while ($prod = $movement_result->fetch_assoc()) {
    if ($prod['total'] >= 5) {
        $fast_moving_count++;
    } elseif ($prod['total'] > 0) {
        $slow_moving_count++;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - MangaRingMed Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
                    <p>Inventory overview and business analytics</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <button class="btn btn-primary btn-sm">
                        <i class="fas fa-download"></i> Export Report
                    </button>
                </div>
            </div>
            
            <!-- STATS CARDS -->
            <div class="dashboard-grid">
                <!-- Inventory Value -->
                <div class="stat-card success">
                    <div class="stat-card-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-label">Total Inventory Value</div>
                    <div class="stat-value"><?php echo formatCurrency($owner_stats['total_value'] ?? 0); ?></div>
                    <div class="stat-change">Current stock value</div>
                </div>
                
                <!-- Daily Sales -->
                <div class="stat-card info">
                    <div class="stat-card-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-label">Sales Today</div>
                    <div class="stat-value"><?php echo formatCurrency($stats['today_sales_amount'] ?? 0); ?></div>
                    <div class="stat-change">Sales in pesos</div>
                </div>
                
                <!-- Units Sold -->
                <div class="stat-card success">
                    <div class="stat-card-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="stat-label">Units Sold</div>
                    <div class="stat-value"><?php echo $stats['today_sales']; ?></div>
                    <div class="stat-change">Items sold today</div>
                </div>
                
                <!-- Low Stock Alert -->
                <div class="stat-card warning">
                    <div class="stat-card-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-label">Low Stock Alerts</div>
                    <div class="stat-value"><?php echo $stats['low_stock']; ?></div>
                    <div class="stat-change negative">Needs restocking</div>
                </div>
                
                <!-- Total Products -->
                <div class="stat-card success">
                    <div class="stat-card-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value"><?php echo $stats['total_products']; ?></div>
                    <div class="stat-change">Active SKUs</div>
                </div>
            </div>
            
            <!-- FORECAST & ANALYTICS -->
            <div class="row">
                <!-- Demand Forecast -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-area"></i> 7-Day Demand Forecast</h5>
                        </div>
                        <div class="card-body" style="position: relative; height: 300px;">
                            <canvas id="forecastChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Product Movement -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-tachometer-alt"></i> Product Movement Analysis</h5>
                        </div>
                        <div class="card-body" style="position: relative; height: 300px;">
                            <canvas id="movementChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- LOW STOCK PRODUCTS -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-exclamation-circle"></i> Products Requiring Reorder</h5>
                </div>
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Current Stock</th>
                                <th>Min Stock</th>
                                <th>Status</th>
                                <th>Recommended Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $low_stock = $conn->query("SELECT * FROM products WHERE quantity <= min_stock ORDER BY quantity ASC LIMIT 8");
                            
                            while ($product = $low_stock->fetch_assoc()):
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                <td><?php echo $product['quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?></td>
                                <td><?php echo $product['min_stock']; ?></td>
                                <td>
                                    <?php if ($product['quantity'] <= 0): ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Low Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="alert('Reorder feature coming soon!')">
                                        <i class="fas fa-sync"></i> Reorder
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/script.js"></script>
    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.querySelector('.main-wrapper').classList.toggle('sidebar-collapsed');
        }
        
        // Demand Forecast Chart
        const ctx1 = document.getElementById('forecastChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($owner_labels); ?>,
                datasets: [
                    {
                        label: 'Actual Sales',
                        data: <?php echo json_encode($sales_data); ?>,
                        borderColor: '#1e7e34',
                        backgroundColor: 'rgba(30, 126, 52, 0.05)',
                        borderWidth: 2,
                        tension: 0.4
                    },
                    {
                        label: 'Forecasted Demand',
                        data: <?php echo json_encode($sales_data); ?>,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.05)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
        // Product Movement Chart
        const ctx2 = document.getElementById('movementChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: ['Fast-Moving', 'Slow-Moving', 'Seasonal', 'Trending'],
                datasets: [{
                    label: 'Product Count',
                    data: [<?php echo $fast_moving_count; ?>, <?php echo $slow_moving_count; ?>, <?php echo $seasonal_count; ?>, <?php echo $trending_count; ?>],
                    backgroundColor: [
                        '#28a745',
                        '#dc3545',
                        '#ffc107',
                        '#17a2b8'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
