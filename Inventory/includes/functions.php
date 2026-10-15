<?php
require_once 'config.php';

// Sanitize input
function sanitize($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

// Ensure system settings table exists
function ensureSystemSettingsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS system_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    return $conn->query($sql);
}

// Read a saved system setting
function getSystemSetting($conn, $key, $default = '') {
    ensureSystemSettingsTable($conn);

    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    if (!$stmt) {
        return $default;
    }

    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? $row['setting_value'] : $default;
}

// Save a system setting
function setSystemSetting($conn, $key, $value) {
    ensureSystemSettingsTable($conn);

    $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $key, $value);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generate random string
function generateRandomString($length = 8) {
    return bin2hex(random_bytes($length / 2));
}

// Format currency
function formatCurrency($amount) {
    return '₱ ' . number_format($amount, 2);
}

// Format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Format datetime
function formatDateTime($datetime) {
    return date('M d, Y - g:i A', strtotime($datetime));
}

// Check stock level
function getStockStatus($quantity, $minStock) {
    if ($quantity <= 0) {
        return '<span class="badge bg-danger">Out of Stock</span>';
    } elseif ($quantity <= $minStock) {
        return '<span class="badge bg-warning">Low Stock</span>';
    } else {
        return '<span class="badge bg-success">In Stock</span>';
    }
}

// Calculate forecast using Moving Average
function calculateMovingAverage($data, $period = 7) {
    $forecast = [];
    
    if (count($data) < $period) {
        return $data;
    }
    
    for ($i = 0; $i < count($data); $i++) {
        if ($i < $period - 1) {
            $forecast[$i] = $data[$i];
        } else {
            $sum = 0;
            for ($j = 0; $j < $period; $j++) {
                $sum += $data[$i - $j];
            }
            $forecast[$i] = round($sum / $period, 2);
        }
    }
    
    return $forecast;
}

// Calculate Linear Regression forecast
function calculateLinearRegression($data, $forecastDays = 7) {
    $n = count($data);
    
    if ($n < 2) {
        return [];
    }
    
    $sumX = $sumY = $sumXY = $sumX2 = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $sumX += $i;
        $sumY += $data[$i];
        $sumXY += $i * $data[$i];
        $sumX2 += $i * $i;
    }
    
    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    $intercept = ($sumY - $slope * $sumX) / $n;
    
    $forecast = [];
    for ($i = $n; $i < $n + $forecastDays; $i++) {
        $forecast[] = round($intercept + $slope * $i, 2);
    }
    
    return $forecast;
}

// Calculate MAPE (Mean Absolute Percentage Error) for forecast accuracy
function calculateForecastAccuracy($actual, $forecast) {
    if (count($actual) === 0 || count($forecast) === 0) {
        return 0;
    }
    
    $sumAPE = 0;
    $count = min(count($actual), count($forecast));
    
    for ($i = 0; $i < $count; $i++) {
        if ($actual[$i] != 0) {
            $sumAPE += abs(($actual[$i] - $forecast[$i]) / $actual[$i]);
        }
    }
    
    $mape = ($sumAPE / $count) * 100;
    $accuracy = max(0, 100 - $mape);
    
    return round($accuracy, 2);
}

// Identify fast and slow moving products
function classifyProductMovement($sales, $threshold = 5) {
    $avgSales = array_sum($sales) / count($sales);
    
    if ($avgSales >= $threshold) {
        return 'fast-moving';
    } else {
        return 'slow-moving';
    }
}

// Get reorder recommendation
function getReorderRecommendation($currentStock, $forecast, $leadTime = 3) {
    $forecastSum = array_sum($forecast);
    $avgDaily = $forecastSum / count($forecast);
    $requiredStock = $avgDaily * $leadTime;
    
    if ($currentStock < $requiredStock) {
        $reorderQty = ($requiredStock * 2) - $currentStock;
        return [
            'status' => 'RESTOCK_NOW',
            'quantity' => round($reorderQty),
            'message' => 'Restock Immediately'
        ];
    } elseif ($currentStock > ($requiredStock * 3)) {
        return [
            'status' => 'OVERSTOCK',
            'quantity' => 0,
            'message' => 'Overstock Warning - Reduce Orders'
        ];
    } else {
        return [
            'status' => 'OPTIMAL',
            'quantity' => 0,
            'message' => 'Stock Level Optimal'
        ];
    }
}

// Get dashboard statistics
function getDashboardStats($conn, $role = null) {
    $stats = [];
    
    // Total Products
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    $stats['total_products'] = $result->fetch_assoc()['count'];
    
    // Low Stock Products
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity <= min_stock");
    $stats['low_stock'] = $result->fetch_assoc()['count'];
    
    // Total Sales
    $result = $conn->query("SELECT 
        SUM(quantity) as units_sold,
        SUM(COALESCE(total_amount, price * quantity)) as sales_amount
        FROM sales WHERE DATE(created_at) = CURDATE()");
    $row = $result->fetch_assoc();
    $stats['today_sales'] = (int) ($row['units_sold'] ?? 0);
    $stats['today_sales_amount'] = (float) ($row['sales_amount'] ?? 0);
    
    // Total Users
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
    $stats['total_users'] = $result->fetch_assoc()['count'];
    
    return $stats;
}

// Pagination
function paginate($totalRecords, $recordsPerPage = 10, $currentPage = 1) {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $offset = ($currentPage - 1) * $recordsPerPage;
    
    return [
        'total' => $totalRecords,
        'per_page' => $recordsPerPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

// Generate forecast rows for each active product from recent sales history
function generateDemandForecasts($conn) {
    $products = $conn->query("SELECT id, name, quantity, min_stock FROM products WHERE status = 'active'");
    if (!$products) {
        return false;
    }

    while ($product = $products->fetch_assoc()) {
        $productId = (int) $product['id'];
        $currentStock = (int) ($product['quantity'] ?? 0);

        $dailySales = $conn->prepare("SELECT DATE(created_at) AS sale_date, SUM(quantity) AS daily_qty
            FROM sales
            WHERE product_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY sale_date ASC");

        if (!$dailySales) {
            continue;
        }

        $dailySales->bind_param('i', $productId);
        $dailySales->execute();
        $dailyResult = $dailySales->get_result();

        $salesSeries = [];
        while ($row = $dailyResult->fetch_assoc()) {
            $salesSeries[] = (int) ($row['daily_qty'] ?? 0);
        }

        if (empty($salesSeries)) {
            continue;
        }

        $window = array_slice($salesSeries, -7);
        if (count($window) === 0) {
            continue;
        }

        $avgDemand = array_sum($window) / count($window);
        $forecastedQuantity = (int) max(0, round($avgDemand));

        $actualDemand = array_sum($window);
        $accuracy = 100.00;
        if ($actualDemand > 0) {
            $difference = abs($actualDemand - $forecastedQuantity);
            $accuracy = max(0, round(100 - (($difference / $actualDemand) * 100), 2));
        }

        $reorderQuantity = max(0, $forecastedQuantity * 2 - $currentStock);
        $reorderDate = date('Y-m-d', strtotime('+3 days'));
        $status = 'normal';
        if ($currentStock <= 0 || $reorderQuantity > 0) {
            $status = 'restock_needed';
        }

        $conn->query("DELETE FROM demand_forecasts WHERE product_id = $productId AND forecast_period = '7_days'");

        $insert = $conn->prepare("INSERT INTO demand_forecasts (product_id, forecast_period, forecast_days, forecasted_quantity, forecast_type, accuracy, reorder_quantity, reorder_date, forecast_status, created_at)
            VALUES (?, '7_days', 7, ?, 'moving_average', ?, ?, ?, ?, NOW())");

        if ($insert) {
            $insert->bind_param('iidsis', $productId, $forecastedQuantity, $accuracy, $reorderQuantity, $reorderDate, $status);
            $insert->execute();
            $insert->close();
        }
    }

    return true;
}

?>
