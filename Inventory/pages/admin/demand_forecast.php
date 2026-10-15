<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/navbar.php';

if (!Auth::hasRole('admin')) {
    header('Location: ../../login.php?error=Unauthorized access');
    exit();
}

generateDemandForecasts($conn);

$forecast_data = $conn->query("SELECT df.*, p.name as product_name FROM demand_forecasts df JOIN products p ON df.product_id = p.id ORDER BY df.created_at DESC LIMIT 50");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Demand Forecast - Admin</title>
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
                <h1><i class="fas fa-chart-area"></i> Demand Forecast</h1>
            </div>

            <div class="card">
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Days</th>
                                <th>Forecast Qty</th>
                                <th>Type</th>
                                <th>Accuracy</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($f = $forecast_data->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($f['created_at']); ?></td>
                                <td><?php echo htmlspecialchars($f['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($f['forecast_days']); ?></td>
                                <td><?php echo htmlspecialchars($f['forecasted_quantity']); ?></td>
                                <td><?php echo htmlspecialchars($f['forecast_type']); ?></td>
                                <td><?php echo htmlspecialchars($f['accuracy']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
