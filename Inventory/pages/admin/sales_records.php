<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/navbar.php';

if (!Auth::hasRole('admin')) {
    header('Location: ../../login.php?error=Unauthorized access');
    exit();
}

// Filters
$product_search = isset($_GET['product_search']) ? trim($_GET['product_search']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build where clauses safely
$where = [];
$params = [];
$types = '';

if ($product_search !== '') {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? )";
    $like = "%" . $product_search . "%";
    $params[] = $like; $params[] = $like;
    $types .= 'ss';
}

if ($date_from !== '') {
    $where[] = 's.created_at >= ?';
    $params[] = $date_from . ' 00:00:00';
    $types .= 's';
}

if ($date_to !== '') {
    $where[] = 's.created_at <= ?';
    $params[] = $date_to . ' 23:59:59';
    $types .= 's';
}

$where_sql = '';
if (count($where) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

// Summary cards (Today's metrics)
$today_start = date('Y-m-d') . ' 00:00:00';
$today_end = date('Y-m-d') . ' 23:59:59';
$stmt = $conn->prepare("SELECT IFNULL(SUM(total_amount),0) as todays_sales, IFNULL(SUM(quantity),0) as items_sold, COUNT(*) as transactions
                        FROM sales WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param('ss', $today_start, $today_end);
$stmt->execute();
$res = $stmt->get_result();
$summary = $res->fetch_assoc();

// Total sales overall
$totalSalesRes = $conn->query("SELECT IFNULL(SUM(total_amount),0) as total_sales FROM sales");
$totalSales = $totalSalesRes ? $totalSalesRes->fetch_assoc()['total_sales'] : 0;

// Count total matching records for pagination
$count_sql = "SELECT COUNT(*) as cnt FROM sales s LEFT JOIN products p ON s.product_id = p.id LEFT JOIN users u ON s.recorded_by = u.id $where_sql";
if ($count_stmt = $conn->prepare($count_sql)) {
    if (!empty($params)) {
        // bind only original params
        $bind_names = [];
        $bind_types = $types;
        $bind_names[] = $bind_types;
        for ($i=0; $i<count($params); $i++) {
            $bn = 'cb' . $i;
            $$bn = $params[$i];
            $bind_names[] = &$$bn;
        }
        call_user_func_array([$count_stmt, 'bind_param'], $bind_names);
    }
    $count_stmt->execute();
    $cres = $count_stmt->get_result();
    $totalCount = intval($cres->fetch_assoc()['cnt']);
} else {
    $totalCount = 0;
}

// Main sales query with batch lookup (attempt to get most recent stock_in reference_number before sale)
$sql = "SELECT s.id as sale_id, s.created_at, s.product_id, p.name as product_name, p.sku,
           (SELECT it.reference_number FROM inventory_transactions it WHERE it.product_id = s.product_id AND it.transaction_type = 'stock_in' AND it.transaction_date <= s.created_at ORDER BY it.transaction_date DESC LIMIT 1) as batch_number,
           s.quantity, s.price, s.total_amount, u.full_name as recorded_by_name, s.notes
    FROM sales s
    LEFT JOIN products p ON s.product_id = p.id
    LEFT JOIN users u ON s.recorded_by = u.id
    $where_sql
    ORDER BY s.created_at DESC
    LIMIT ? OFFSET ?";

if ($stmt = $conn->prepare($sql)) {
    // bind dynamic params if any, then append LIMIT/OFFSET
    $bind_names = [];
    $bind_types_all = $types . 'ii';
    $bind_names[] = $bind_types_all;
    // bind original params
    for ($i=0; $i<count($params); $i++) {
        $bind_name = 'bind' . $i;
        $$bind_name = $params[$i];
        $bind_names[] = &$$bind_name;
    }
    // bind perPage and offset
    $bp = $perPage; $bo = $offset;
    $bind_names[] = &$bp;
    $bind_names[] = &$bo;
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
    $stmt->execute();
    $sales = $stmt->get_result();
} else {
    $sales = $conn->query("SELECT s.*, p.name as product_name, u.full_name as recorded_by_name FROM sales s
        LEFT JOIN products p ON s.product_id = p.id
        LEFT JOIN users u ON s.recorded_by = u.id
        ORDER BY s.created_at DESC LIMIT 200");
}

// pagination values
$totalPages = max(1, ceil($totalCount / $perPage));
$startShowing = $totalCount > 0 ? $offset + 1 : 0;
$endShowing = min($offset + $perPage, $totalCount);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sales Records - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php renderSidebar('sales'); ?>
    <div class="main-wrapper">
        <?php renderTopNavbar(); ?>
        <div class="main-content">
            <div class="page-header">
                <div class="page-title">
                    <h1><i class="fas fa-receipt"></i> Sales Records</h1>
                    <p>Manage and monitor all sales transactions.</p>
                </div>
            </div>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
                <?php unset($_SESSION['success']); endif; ?>
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
                <?php unset($_SESSION['error']); endif; ?>

            <!-- STATS CARDS -->
            <div class="dashboard-grid">
                <!-- Today's Sales -->
                <div class="stat-card success">
                    <div class="stat-card-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-label">Today's Sales</div>
                    <div class="stat-value"><?php echo formatCurrency($summary['todays_sales']); ?></div>
                    <div class="stat-change">Total amount</div>
                </div>
                
                <!-- Items Sold Today -->
                <div class="stat-card info">
                    <div class="stat-card-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-label">Items Sold Today</div>
                    <div class="stat-value"><?php echo intval($summary['items_sold']); ?></div>
                    <div class="stat-change">Total units</div>
                </div>

                <!-- Transactions Today -->
                <div class="stat-card warning">
                    <div class="stat-card-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="stat-label">Transactions Today</div>
                    <div class="stat-value"><?php echo intval($summary['transactions']); ?></div>
                    <div class="stat-change">Total transactions</div>
                </div>

                <!-- Total Sales -->
                <div class="stat-card success">
                    <div class="stat-card-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-label">Total Sales</div>
                    <div class="stat-value"><?php echo formatCurrency($totalSales); ?></div>
                    <div class="stat-change">Overall</div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h5><i class="fas fa-filter"></i> Search & Filter</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">Search Product</label>
                            <input type="text" name="product_search" class="form-control" value="<?php echo htmlspecialchars($product_search); ?>" placeholder="Product name or SKU">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-success">Filter</button>
                        </div>
                    </form>
                    <div class="mt-3 d-flex gap-2">
                        <a href="sales_records.php" class="btn btn-sm btn-secondary"><i class="fas fa-redo"></i> Clear</a>
                        <a href="../../api/export_sales_csv.php?<?php echo http_build_query(['product_search'=>$product_search,'date_from'=>$date_from,'date_to'=>$date_to]); ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-download"></i> Export CSV</a>
                        <button id="populateSampleBtnTop" class="btn btn-sm btn-outline-primary"><i class="fas fa-seedling"></i> Populate Sample</button>
                        <button class="btn btn-sm btn-success" type="button" data-bs-toggle="offcanvas" data-bs-target="#addSaleDrawer"><i class="fas fa-plus"></i> Add Record</button>
                    </div>
                </div>
            </div>

            <!-- Sales Trend Chart -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #198754; color: white; border-bottom: none;">
                    <h5 style="margin: 0; font-weight: 600;"><i class="fas fa-chart-line"></i> 7-Day Sales Trend</h5>
                </div>
                <div class="card-body ps-4 pe-1 pt-4 pb-4" style="background-color: #f8f9fa;">
                    <div style="position: relative; width: 100%; height: 350px;">
                        <canvas id="salesTrendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Compare to Forecast Widget -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar"></i> Compare to Forecast</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Product</label>
                            <select id="compare_product" class="form-select">
                                <option value="">-- Select Product --</option>
                                <?php
                                $prods = $conn->query("SELECT id, name, sku FROM products WHERE status = 'active' ORDER BY name LIMIT 500");
                                while ($pr = $prods->fetch_assoc()):
                                ?>
                                <option value="<?php echo $pr['id']; ?>"><?php echo htmlspecialchars($pr['name'] . ' (' . $pr['sku'] . ')'); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" id="compare_from" class="form-control" value="<?php echo htmlspecialchars($date_from ?: date('Y-m-d', strtotime('-30 days'))); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" id="compare_to" class="form-control" value="<?php echo htmlspecialchars($date_to ?: date('Y-m-d')); ?>">
                        </div>
                        <div class="col-md-2">
                            <button id="compareBtn" class="btn btn-primary w-100">Compare</button>
                        </div>
                    </div>

                    <div id="compareResults" class="mt-3" style="display:none;">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="p-2 bg-light rounded">
                                    <strong>Actual Total:</strong> <span id="actualTotal" class="d-block fs-5 text-success"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 bg-light rounded">
                                    <strong>Forecast Total:</strong> <span id="forecastTotal" class="d-block fs-5 text-info"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 bg-light rounded">
                                    <strong>Difference:</strong> <span id="diffTotal" class="d-block fs-5 text-warning"></span>
                                </div>
                            </div>
                        </div>
                        <div style="position: relative; height: 300px;">
                            <canvas id="compareChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Sales Records</h5>
                </div>
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Sale ID</th>
                                <th>Date & Time</th>
                                <th>Product</th>
                                <th>Batch / Lot</th>
                                <th>QTY</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Recorded By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($sales && $sales->num_rows > 0): ?>
                                <?php while ($row = $sales->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['sale_id'] ?? $row['id']); ?></td>
                                    <td><?php echo htmlspecialchars(formatDateTime($row['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['product_name'] . ' (' . $row['sku'] . ')'); ?></td>
                                    <td><?php echo htmlspecialchars($row['batch_number'] ?? '-'); ?></td>
                                    <td><?php echo intval($row['quantity']); ?></td>
                                    <td><?php echo formatCurrency($row['price']); ?></td>
                                    <td><?php echo formatCurrency($row['total_amount'] ?? ($row['quantity'] * $row['price'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['recorded_by_name']); ?></td>
                                    <td>
                                        <a href="#" class="text-decoration-none me-2" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="#" class="text-decoration-none text-danger" title="Delete"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No sales records found for the selected period.</td>
                                </tr>
                                <?php if (empty($totalCount) || $totalCount === 0): ?>
                                <tr>
                                    <td colspan="9" class="text-center">
                                        <button id="populateSampleBtn" class="btn btn-outline-primary">Populate sample sales</button>
                                        <div id="populateStatus" class="mt-2"></div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Sales Record Drawer -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="addSaleDrawer" aria-labelledby="addSaleDrawerLabel">
                <div class="offcanvas-header">
                    <h5 id="addSaleDrawerLabel">Add Sales Record</h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <form id="addSaleForm" method="POST" action="../../api/add_sales_record.php">
                        <input type="hidden" name="redirect" value="1">
                        <div class="mb-3">
                            <label class="form-label">Product *</label>
                            <select id="add_product" name="product_id" class="form-select" required>
                                <option value="">Select product</option>
                                <?php
                                $prods = $conn->query("SELECT id, name, sku FROM products WHERE status = 'active' ORDER BY name LIMIT 500");
                                while ($pr = $prods->fetch_assoc()):
                                ?>
                                <option value="<?php echo $pr['id']; ?>"><?php echo htmlspecialchars($pr['name'] . ' (' . $pr['sku'] . ')'); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Batch / Lot Number</label>
                            <select id="add_batch" name="batch" class="form-select">
                                <option value="">Select batch / lot</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity *</label>
                            <input type="number" min="1" id="add_qty" name="quantity" class="form-control" value="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Unit Price (₱) *</label>
                            <input type="number" step="0.01" id="add_price" name="price" class="form-control" value="0.00" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total (₱)</label>
                            <input type="text" id="add_total" class="form-control" readonly value="0.00">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date & Time</label>
                            <input type="datetime-local" id="add_datetime" name="transaction_date" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Recorded By</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="add_notes" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">Cancel</button>
                            <button type="submit" class="btn btn-success">Save Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Prepare sales trend data (fetch via AJAX)
        function fetchSalesTrend() {
            var from = document.querySelector('input[name="date_from"]').value || document.getElementById('compare_from').value;
            var to = document.querySelector('input[name="date_to"]').value || document.getElementById('compare_to').value;
            // default to last 30 days if empty
            if (!from) from = '<?php echo date('Y-m-d', strtotime('-30 days')); ?>';
            if (!to) to = '<?php echo date('Y-m-d'); ?>';

            fetch('../../api/sales_trend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ date_from: from, date_to: to })
            }).then(res => res.json()).then(data => {
                if (data.success) renderSalesTrend(data.dates, data.totals);
            }).catch(err => console.error(err));
        }

        var salesTrendChart = null;
        function renderSalesTrend(labels, dataSeries) {
            var ctx = document.getElementById('salesTrendChart').getContext('2d');
            if (salesTrendChart) salesTrendChart.destroy();
            salesTrendChart = new Chart(ctx, {
                type: 'line',
                data: { labels: labels, datasets: [{ label: 'Items Sold', data: dataSeries, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,0.15)', fill: true }] },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { display: true }, y: { display: true } } }
            });
        }

        // Compare to forecast
        var compareChart = null;
        document.getElementById('compareBtn').addEventListener('click', function() {
            var pid = document.getElementById('compare_product').value;
            var from = document.getElementById('compare_from').value;
            var to = document.getElementById('compare_to').value;
            if (!pid) { alert('Select a product'); return; }
            var fd = new FormData();
            fd.append('product_id', pid);
            fd.append('date_from', from);
            fd.append('date_to', to);
            fd.append('method', 'linear_regression');

            fetch('../../api/compare_forecast.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (!data.success) { alert(data.error || 'Failed to compute forecast'); return; }
                document.getElementById('compareResults').style.display = 'block';
                document.getElementById('actualTotal').innerText = data.total_actual;
                document.getElementById('forecastTotal').innerText = data.total_forecast.toFixed(2);
                document.getElementById('diffTotal').innerText = (data.total_actual - data.total_forecast).toFixed(2);

                var labels = data.dates;
                var actual = data.actual;
                var forecast = data.forecast;

                var ctx = document.getElementById('compareChart').getContext('2d');
                if (compareChart) compareChart.destroy();
                compareChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: 'Actual Sales', data: actual, borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,0.12)', fill: false },
                            { label: 'Forecast', data: forecast, borderColor: '#ffc107', backgroundColor: 'rgba(255,193,7,0.12)', fill: false }
                        ]
                    },
                    options: { responsive: true }
                });
            }).catch(err => { console.error(err); alert('Error'); });
        });

        // initial load
        fetchSalesTrend();

        // Export CSV button wiring
        document.getElementById('exportCsvBtn').addEventListener('click', function(e){
            e.preventDefault();
            var params = new URLSearchParams();
            var ps = document.querySelector('input[name="product_search"]').value;
            var df = document.querySelector('input[name="date_from"]').value;
            var dt = document.querySelector('input[name="date_to"]').value;
            if (ps) params.append('product_search', ps);
            if (df) params.append('date_from', df);
            if (dt) params.append('date_to', dt);
            window.location = '../../api/export_sales_csv.php?' + params.toString();
        });

        // Add sales drawer logic
        var productSelect = document.getElementById('add_product');
        var batchSelect = document.getElementById('add_batch');
        productSelect.addEventListener('change', function(){
            var pid = this.value;
            batchSelect.innerHTML = '<option value="">Loading...</option>';
            if (!pid) { batchSelect.innerHTML = '<option value="">Select batch / lot</option>'; return; }
            fetch('../../api/get_batches.php?product_id=' + pid).then(r=>r.json()).then(j=>{
                if (!j.success) { batchSelect.innerHTML = '<option value="">Select batch / lot</option>'; return; }
                var html = '<option value="">Select batch / lot</option>';
                j.batches.forEach(function(b){ html += '<option value="'+ (b.reference||'') +'">'+ (b.reference || '') +' — '+ b.date +'</option>'; });
                batchSelect.innerHTML = html;
            }).catch(err => { batchSelect.innerHTML = '<option value="">Select batch / lot</option>'; });
        });

        function updateTotal() {
            var q = parseFloat(document.getElementById('add_qty').value) || 0;
            var p = parseFloat(document.getElementById('add_price').value) || 0;
            document.getElementById('add_total').value = (q * p).toFixed(2);
        }
        document.getElementById('add_qty').addEventListener('input', updateTotal);
        document.getElementById('add_price').addEventListener('input', updateTotal);

        document.getElementById('addSaleForm').addEventListener('submit', function(e){
            e.preventDefault();
            var fd = new FormData(this);
            fetch('../../api/add_sales_record.php', { method: 'POST', body: fd }).then(r=>r.json()).then(j=>{
                if (!j.success) { alert(j.error || 'Failed to add sale'); return; }
                // success — reload to show the new record
                location.reload();
            }).catch(err=>{ console.error(err); alert('Error saving record'); });
        });

        // Populate sample sales (top and bottom buttons)
        function handlePopulateClick(statusElem) {
            if (statusElem) statusElem.innerText = 'Populating...';
            fetch('../../api/seed_sales.php', { method: 'POST' }).then(r=>r.json()).then(j=>{
                if (!j.success) {
                    if (statusElem) statusElem.innerText = 'Failed: ' + (j.error || 'unknown');
                    else alert('Failed: ' + (j.error || 'Unauthorized'));
                    return;
                }
                if (statusElem) statusElem.innerText = 'Inserted ' + j.inserted + ' sample sales. Reloading...';
                else alert('Inserted ' + j.inserted + ' sample sales. Reloading...');
                setTimeout(()=> location.reload(), 900);
            }).catch(err=>{ if (statusElem) statusElem.innerText = 'Error'; console.error(err); });
        }

        var populateBtnBottom = document.getElementById('populateSampleBtn');
        if (populateBtnBottom) {
            populateBtnBottom.addEventListener('click', function(){
                var status = document.getElementById('populateStatus');
                handlePopulateClick(status);
            });
        }
        var populateBtnTop = document.getElementById('populateSampleBtnTop');
        if (populateBtnTop) {
            populateBtnTop.addEventListener('click', function(){ handlePopulateClick(null); });
        }

        // Floating populate button (always visible)
        (function(){
            var btn = document.createElement('button');
            btn.id = 'populateSampleFloating';
            btn.className = 'btn btn-primary';
            btn.title = 'Populate sample sales';
            btn.style.position = 'fixed';
            btn.style.right = '18px';
            btn.style.bottom = '18px';
            btn.style.zIndex = 9999;
            btn.style.borderRadius = '50%';
            btn.style.width = '52px';
            btn.style.height = '52px';
            btn.innerHTML = '<i class="fas fa-database"></i>';
            document.body.appendChild(btn);
            btn.addEventListener('click', function(){
                if (!confirm('Populate sample sales? This will insert test records.')) return;
                handlePopulateClick(null);
            });
        })();

        // Inline form handlers: load batches and update total
        var inlineProduct = document.getElementById('inline_product');
        var inlineBatch = document.getElementById('inline_batch');
        var inlineQty = document.getElementById('inline_qty');
        var inlinePrice = document.getElementById('inline_price');
        var inlineTotal = document.getElementById('inline_total');

        function updateInlineTotal(){
            var q = parseFloat(inlineQty.value) || 0;
            var p = parseFloat(inlinePrice.value) || 0;
            inlineTotal.value = (q * p).toFixed(2);
        }

        if (inlineProduct){
            inlineProduct.addEventListener('change', function(){
                var pid = this.value;
                // set price from data attribute
                var opt = this.selectedOptions[0];
                var price = opt ? opt.dataset.price || '0.00' : '0.00';
                inlinePrice.value = parseFloat(price).toFixed(2);
                updateInlineTotal();
                // load batches
                inlineBatch.innerHTML = '<option>Loading...</option>';
                if (!pid){ inlineBatch.innerHTML = '<option value="">Select batch/lot</option>'; return; }
                fetch('../../api/get_batches.php?product_id=' + pid).then(r=>r.json()).then(j=>{
                    if (!j.success) { inlineBatch.innerHTML = '<option value="">Select batch/lot</option>'; return; }
                    var html = '<option value="">Select batch/lot</option>';
                    j.batches.forEach(function(b){ html += '<option value="'+ (b.reference||'') +'">'+ (b.reference || '') +' — '+ b.date +'</option>'; });
                    inlineBatch.innerHTML = html;
                }).catch(err=>{ inlineBatch.innerHTML = '<option value="">Select batch/lot</option>'; });
            });
        }
        if (inlineQty) inlineQty.addEventListener('input', updateInlineTotal);
        if (inlinePrice) inlinePrice.addEventListener('input', updateInlineTotal);
    </script>
</body>
</html>
