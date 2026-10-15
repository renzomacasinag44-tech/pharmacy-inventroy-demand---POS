<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/navbar.php';

// Check if user is logged in and is admin
if (!Auth::hasRole('admin')) {
    header('Location: ../../login.php?error=Unauthorized access');
    exit();
}

$stats = getDashboardStats($conn, 'admin');

// Build last 7 days sales chart data
$sales_labels = [];
$sales_values = [];
$sales_by_day = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $sales_labels[] = date('D', strtotime($day));
    $sales_by_day[$day] = 0;
}

$sales_result = $conn->query("SELECT DATE(created_at) as day, SUM(quantity) as total FROM sales WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at)");
while ($row = $sales_result->fetch_assoc()) {
    $day = $row['day'];
    if (isset($sales_by_day[$day])) {
        $sales_by_day[$day] = (int) $row['total'];
    }
}

foreach ($sales_by_day as $value) {
    $sales_values[] = $value;
}

// Build inventory chart counts
$out_of_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity <= 0")->fetch_assoc()['count'];
$low_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity > 0 AND quantity <= min_stock")->fetch_assoc()['count'];
$in_stock = max(0, $stats['total_products'] - $out_of_stock - $low_stock);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MangaRingMed Inventory System</title>
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
                    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>! Here's your system overview.</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
            
            <!-- STATS CARDS -->
            <div class="dashboard-grid">
                <!-- Total Users -->
                <div class="stat-card info">
                    <div class="stat-card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-change">Active users in system</div>
                </div>
                
                <!-- Total Products -->
                <div class="stat-card success">
                    <div class="stat-card-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value"><?php echo $stats['total_products']; ?></div>
                    <div class="stat-change">In inventory</div>
                </div>
                
                <!-- Low Stock -->
                <div class="stat-card warning">
                    <div class="stat-card-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-label">Low Stock Products</div>
                    <div class="stat-value"><?php echo $stats['low_stock']; ?></div>
                    <div class="stat-change">Needs attention</div>
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
                <div class="stat-card info">
                    <div class="stat-card-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="stat-label">Units Sold</div>
                    <div class="stat-value"><?php echo $stats['today_sales']; ?></div>
                    <div class="stat-change">Items sold today</div>
                </div>
            </div>
            
            <!-- CHARTS ROW -->
            <div class="row">
                <!-- Sales Chart -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line"></i> Sales Trend (Last 7 Days)</h5>
                        </div>
                        <div class="card-body" style="position: relative; height: 300px;">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Inventory Distribution -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-pie-chart"></i> Inventory Status</h5>
                        </div>
                        <div class="card-body" style="position: relative; height: 300px;">
                            <canvas id="inventoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- RECENT ACTIVITIES -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-history"></i> Recent Activities</h5>
                </div>
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $logs_result = $conn->query("SELECT l.*, u.full_name FROM activity_logs l 
                                                         JOIN users u ON l.user_id = u.id 
                                                         ORDER BY l.timestamp DESC LIMIT 10");
                            
                            while ($log = $logs_result->fetch_assoc()):
                            ?>
                            <tr>
                                <td>
                                    <div class="user-avatar" style="display: inline-flex; width: 30px; height: 30px; font-size: 12px;">
                                        <?php echo strtoupper($log['full_name'][0]); ?>
                                    </div>
                                    <?php echo htmlspecialchars($log['full_name']); ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($log['action']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($log['description']); ?></td>
                                <td><?php echo formatDateTime($log['timestamp']); ?></td>
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
        
        // Sales Chart
        const ctx1 = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($sales_labels); ?>,
                datasets: [{
                    label: 'Sales',
                    data: <?php echo json_encode($sales_values); ?>,
                    borderColor: '#1e7e34',
                    backgroundColor: 'rgba(30, 126, 52, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: '#1e7e34',
                    pointBorderColor: 'white',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
        
        // Inventory Chart
        const ctx2 = document.getElementById('inventoryChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                datasets: [{
                    data: [<?php echo $in_stock; ?>, <?php echo $low_stock; ?>, <?php echo $out_of_stock; ?>],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
