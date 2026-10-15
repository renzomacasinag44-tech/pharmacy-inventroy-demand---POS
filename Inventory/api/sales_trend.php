<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!Auth::hasRole('admin')) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$date_from = $input['date_from'] ?? '';
$date_to = $input['date_to'] ?? '';

if (!$date_from) $date_from = date('Y-m-d', strtotime('-30 days'));
if (!$date_to) $date_to = date('Y-m-d');

$start_ts = strtotime($date_from);
$end_ts = strtotime($date_to);
if ($start_ts === false || $end_ts === false || $end_ts < $start_ts) {
    echo json_encode(['error' => 'Invalid date range']);
    exit();
}

$days = intval(($end_ts - $start_ts) / 86400) + 1;
$dates = [];
$totals_map = [];
for ($i = 0; $i < $days; $i++) {
    $d = date('Y-m-d', $start_ts + ($i * 86400));
    $dates[] = $d;
    $totals_map[$d] = 0;
}

$stmt = $conn->prepare("SELECT DATE(created_at) as d, IFNULL(SUM(quantity),0) as qty FROM sales WHERE created_at BETWEEN ? AND ? GROUP BY d ORDER BY d ASC");
$from_time = $date_from . ' 00:00:00';
$to_time = $date_to . ' 23:59:59';
$stmt->bind_param('ss', $from_time, $to_time);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $totals_map[$r['d']] = intval($r['qty']);
}

$totals = [];
foreach ($dates as $d) $totals[] = $totals_map[$d] ?? 0;

echo json_encode(['success' => true, 'dates' => $dates, 'totals' => $totals]);
exit();
?>