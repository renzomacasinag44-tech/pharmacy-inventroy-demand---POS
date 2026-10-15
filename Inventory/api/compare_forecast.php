<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!Auth::hasRole('admin')) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$product_id = intval($_POST['product_id'] ?? 0);
$date_from = $_POST['date_from'] ?? '';
$date_to = $_POST['date_to'] ?? '';
$method = $_POST['method'] ?? 'linear_regression';
$ma_period = intval($_POST['ma_period'] ?? 7);

if ($product_id <= 0 || !$date_from || !$date_to) {
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

$start_ts = strtotime($date_from);
$end_ts = strtotime($date_to);
if ($start_ts === false || $end_ts === false || $end_ts < $start_ts) {
    echo json_encode(['error' => 'Invalid date range']);
    exit();
}

$days = intval(($end_ts - $start_ts) / 86400) + 1;

// Get training data: all daily sales prior to date_from for this product
$train_stmt = $conn->prepare("SELECT DATE(created_at) as d, IFNULL(SUM(quantity),0) as qty FROM sales WHERE product_id = ? AND created_at < ? GROUP BY d ORDER BY d ASC");
$before_time = date('Y-m-d', $start_ts) . ' 00:00:00';
$train_stmt->bind_param('is', $product_id, $before_time);
$train_stmt->execute();
$train_res = $train_stmt->get_result();
$training = [];
while ($r = $train_res->fetch_assoc()) {
    $training[] = intval($r['qty']);
}

if (count($training) < 1) {
    // no history, return error
    echo json_encode(['error' => 'Not enough historical data for forecasting']);
    exit();
}

// Generate forecast for days (use existing helpers where possible)
$forecast = [];
if ($method === 'linear_regression') {
    $forecast = calculateLinearRegression($training, $days);
} else {
    // moving average - iterative forecast using last N values
    $period = max(1, $ma_period);
    $window = $training;
    for ($i = 0; $i < $days; $i++) {
        $len = count($window);
        $slice = array_slice($window, max(0, $len - $period));
        $avg = 0;
        if (count($slice) > 0) $avg = round(array_sum($slice) / count($slice), 2);
        $forecast[] = $avg;
        $window[] = $avg; // append predicted for iterative forecast
    }
}

// Get actuals for date range per day
$actual = [];
$dates = [];
for ($d = 0; $d < $days; $d++) {
    $dt = date('Y-m-d', $start_ts + ($d * 86400));
    $dates[] = $dt;
    $actual[$dt] = 0;
}

$act_stmt = $conn->prepare("SELECT DATE(created_at) as d, IFNULL(SUM(quantity),0) as qty FROM sales WHERE product_id = ? AND created_at BETWEEN ? AND ? GROUP BY d ORDER BY d ASC");
$from_time = $date_from . ' 00:00:00';
$to_time = $date_to . ' 23:59:59';
$act_stmt->bind_param('iss', $product_id, $from_time, $to_time);
$act_stmt->execute();
$act_res = $act_stmt->get_result();
while ($r = $act_res->fetch_assoc()) {
    $actual[$r['d']] = intval($r['qty']);
}

$actual_series = [];
for ($d = 0; $d < $days; $d++) {
    $dt = $dates[$d];
    $actual_series[] = intval($actual[$dt] ?? 0);
}

// Align forecast array length
if (count($forecast) < $days) {
    $diff = $days - count($forecast);
    for ($i=0;$i<$diff;$i++) $forecast[] = 0;
}

// Differences
$diffs = [];
for ($i=0;$i<$days;$i++) {
    $diffs[] = round($actual_series[$i] - $forecast[$i], 2);
}

// Totals
$total_actual = array_sum($actual_series);
$total_forecast = array_sum($forecast);

echo json_encode([
    'success' => true,
    'dates' => $dates,
    'actual' => $actual_series,
    'forecast' => $forecast,
    'diffs' => $diffs,
    'total_actual' => $total_actual,
    'total_forecast' => $total_forecast,
]);
exit();
?>