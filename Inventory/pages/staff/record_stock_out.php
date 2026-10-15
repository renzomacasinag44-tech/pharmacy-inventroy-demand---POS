<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/navbar.php';

// Check if user is logged in and has permission to record stock
if (!Auth::hasAnyRole(['staff', 'admin', 'owner'])) {
    header('Location: ../../login.php?error=Unauthorized access');
    exit();
}

// Get products for dropdown
$products = $conn->query("SELECT id, name, sku, quantity, unit FROM products WHERE status = 'active' ORDER BY name");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Stock Out - MangaRingMed Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <!-- SIDEBAR -->
    <?php renderSidebar('stock_out'); ?>
    
    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <!-- TOP NAVBAR -->
        <?php renderTopNavbar(); ?>
        
        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <div class="page-title">
                    <h1><i class="fas fa-arrow-down"></i> Record Stock Out</h1>
                    <p>Record sold or used products</p>
                </div>
            </div>
            
            <!-- STOCK OUT FORM -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-clipboard-list"></i> Stock Out Record</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="../../api/record_stock_out.php">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Select Product *</label>
                                        <select class="form-select" name="product_id" id="product_id" required onchange="updateProductInfo()">
                                            <option value="">-- Select Product --</option>
                                            <?php while ($product = $products->fetch_assoc()): ?>
                                                <option value="<?php echo $product['id']; ?>" data-unit="<?php echo htmlspecialchars($product['unit']); ?>" data-qty="<?php echo $product['quantity']; ?>">
                                                    <?php echo htmlspecialchars($product['name']); ?> (<?php echo $product['quantity']; ?> available)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Available Quantity</label>
                                        <input type="text" class="form-control" id="current_qty" disabled>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Quantity to Remove *</label>
                                        <input type="number" class="form-control" name="quantity" id="quantity" min="1" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Unit</label>
                                        <input type="text" class="form-control" id="unit" disabled>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Transaction Type *</label>
                                        <select class="form-select" name="transaction_type" required>
                                            <option value="">-- Select Type --</option>
                                            <option value="sale">Sale / Customer Purchase</option>
                                            <option value="damage">Damage / Defective</option>
                                            <option value="expiration">Expiration / Return</option>
                                            <option value="adjustment">Inventory Adjustment</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date *</label>
                                        <input type="date" class="form-control" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Customer Name (if Sale)</label>
                                        <input type="text" class="form-control" name="customer_name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Sale Price per Unit</label>
                                        <input type="number" class="form-control" name="price" step="0.01">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Notes / Remarks</label>
                                    <textarea class="form-control" name="notes" rows="3" placeholder="Add any notes about this stock out..."></textarea>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Record Stock Out
                                    </button>
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="fas fa-redo"></i> Clear
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="if (document.referrer) { history.back(); } else { window.location.href='../../pages/owner/record_stock.php'; }">
                                        <i class="fas fa-arrow-left"></i> Back
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- RECENT STOCK OUT -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-history"></i> Recent Stock Out</h5>
                        </div>
                        <div class="table-container" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm">
                                <tbody>
                                    <?php
                                    $recent = $conn->query("SELECT it.*, p.name FROM inventory_transactions it 
                                                           JOIN products p ON it.product_id = p.id 
                                                           WHERE it.transaction_type = 'stock_out' 
                                                           ORDER BY it.transaction_date DESC 
                                                           LIMIT 8");
                                    
                                    while ($transaction = $recent->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td>
                                            <strong style="font-size: 12px;"><?php echo htmlspecialchars($transaction['name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo formatDate($transaction['transaction_date']); ?></small>
                                        </td>
                                        <td class="text-end">
                                            <strong style="color: #dc3545;">-<?php echo $transaction['quantity']; ?></strong>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
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
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.querySelector('.main-wrapper').classList.toggle('sidebar-collapsed');
        }
        
        function updateProductInfo() {
            const select = document.getElementById('product_id');
            const option = select.options[select.selectedIndex];
            document.getElementById('current_qty').value = option.getAttribute('data-qty') || '';
            document.getElementById('unit').value = option.getAttribute('data-unit') || '';
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
