<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/navbar.php';

if (!Auth::hasRole('staff')) {
    header('Location: ../../login.php?error=Unauthorized access');
    exit();
}

generateDemandForecasts($conn);

$forecast_data = $conn->query("SELECT df.*, p.name as product_name FROM demand_forecasts df 
                               JOIN products p ON df.product_id = p.id 
                               ORDER BY df.created_at DESC LIMIT 20");

$product_sales = $conn->query("SELECT p.id, p.name, p.sku, 
                              COALESCE(SUM(s.quantity), 0) as total_units,
                              COALESCE(SUM(s.quantity * s.price), 0) as total_sales
                              FROM products p
                              LEFT JOIN sales s ON p.id = s.product_id AND DATE(s.created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                              GROUP BY p.id, p.name, p.sku
                              ORDER BY total_units DESC, total_sales DESC");

$best_sellers = [];
$slow_sellers = [];
$high_demand_count = 0;
$low_demand_count = 0;

while ($row = $product_sales->fetch_assoc()) {
    $sales_units = (int) $row['total_units'];
    $row['rank'] = count($best_sellers) + 1;
    $best_sellers[] = $row;
    if ($sales_units >= 5) {
        $high_demand_count++;
    }
    if ($sales_units <= 1 || $sales_units === 0) {
        $low_demand_count++;
    }
}

$slow_sellers = array_filter($best_sellers, function($item) {
    return (int) $item['total_units'] <= 1;
});
$slow_sellers = array_values($slow_sellers);
$top_sellers = array_slice($best_sellers, 0, 5);
$bottom_sellers = array_slice(array_reverse($best_sellers), 0, 5);

$low_stock_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity <= min_stock")->fetch_assoc()['count'];
$optimal_stock_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity > min_stock AND quantity < max_stock")->fetch_assoc()['count'];

$high_demand_count = max(0, $high_demand_count);
$low_demand_count = max(0, $low_demand_count);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demand Forecast - MangaRingMed Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php renderSidebar('forecast'); ?>
    <div class="main-wrapper">
        <?php renderTopNavbar(); ?>
        <div class="main-content">
            <div class="page-header">
                <div class="page-title">
                    <h1><i class="fas fa-chart-area"></i> Demand Forecast</h1>
                    <p>AI-powered inventory predictions and recommendations</p>
                </div>
                <div class="page-actions">
                    <select class="form-select form-select-sm" style="width: 180px;">
                        <option value="7">Last 7 Days</option>
                        <option value="30">Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                    </select>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card success">
                        <div class="stat-card-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <div class="stat-label">High Demand Alert</div>
                        <div class="stat-value"><?php echo $high_demand_count; ?></div>
                        <div class="stat-change">Products trending up</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card warning">
                        <div class="stat-card-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-label">Low Stock Warning</div>
                        <div class="stat-value"><?php echo $low_stock_products ?? 0; ?></div>
                        <div class="stat-change">Needs reorder</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card info">
                        <div class="stat-card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-label">Optimal Stock</div>
                        <div class="stat-value"><?php echo $optimal_stock_products ?? 0; ?></div>
                        <div class="stat-change">Balanced inventory</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card danger">
                        <div class="stat-card-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="stat-label">Low Demand</div>
                        <div class="stat-value"><?php echo count($slow_sellers); ?></div>
                        <div class="stat-change">Slow-moving items</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line"></i> Sales vs Forecasted Demand</h5>
                        </div>
                        <div class="card-body" style="position: relative; height: 350px;">
                            <canvas id="forecastChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-sync"></i> Reorder Recommendations</h5>
                </div>
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Current Stock</th>
                                <th>Forecasted Demand</th>
                                <th>Recommended Qty</th>
                                <th>Suggested Date</th>
                                <th>Priority</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $reorder_query = "SELECT p.*, COUNT(s.id) as recent_sales 
                                            FROM products p 
                                            LEFT JOIN sales s ON p.id = s.product_id AND DATE(s.created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                            WHERE p.quantity <= p.min_stock
                                            GROUP BY p.id
                                            LIMIT 10";
                            $reorders = $conn->query($reorder_query);

                            while ($product = $reorders->fetch_assoc()):
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                <td><?php echo $product['quantity']; ?> units</td>
                                <td><?php echo ceil(($product['recent_sales'] / 7) * 14); ?> units (14d)</td>
                                <td><strong><?php echo ceil(($product['recent_sales'] / 7) * 30); ?> units</strong></td>
                                <td><?php echo date('M d, Y', strtotime('+3 days')); ?></td>
                                <td>
                                    <?php if ($product['quantity'] == 0): ?>
                                        <span class="badge bg-danger">URGENT</span>
                                    <?php elseif ($product['quantity'] <= $product['min_stock'] / 2): ?>
                                        <span class="badge bg-warning">HIGH</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">NORMAL</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-trophy"></i> Best-Selling Products</h5>
                        </div>
                        <div class="table-container">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Units Sold</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($best_sellers, 0, 5) as $index => $product): ?>
                                    <tr>
                                        <td><span class="badge bg-success">#<?php echo $index + 1; ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                        <td><?php echo (int)$product['total_units']; ?> units</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-arrow-down"></i> Lowest-Selling Products</h5>
                        </div>
                        <div class="table-container">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Units Sold</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice(array_reverse($best_sellers), 0, 5) as $index => $product): ?>
                                    <tr>
                                        <td><span class="badge bg-warning text-dark">#<?php echo $index + 1; ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                        <td><?php echo (int)$product['total_units']; ?> units</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/script.js"></script>
    <script>
        const ctx = document.getElementById('forecastChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
                    datasets: [
                        {
                            label: 'Actual Sales',
                            data: [45, 52, 48, 61, 58, 67, 72],
                            borderColor: '#1e7e34',
                            backgroundColor: 'rgba(30, 126, 52, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5
                        },
                        {
                            label: 'Moving Average Forecast',
                            data: [48, 52, 52, 57, 62, 66, 70],
                            borderColor: '#ffc107',
                            backgroundColor: 'rgba(255, 193, 7, 0.05)',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3
                        },
                        {
                            label: 'Linear Regression Forecast',
                            data: [50, 54, 58, 62, 66, 70, 74],
                            borderColor: '#17a2b8',
                            backgroundColor: 'rgba(23, 162, 184, 0.05)',
                            borderWidth: 2,
                            borderDash: [10, 5],
                            fill: false,
                            tension: 0.4,
                            pointRadius: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { usePointStyle: true }
                        }
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
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
