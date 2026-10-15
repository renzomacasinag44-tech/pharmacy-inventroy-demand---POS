<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/navbar.php';

if (!Auth::hasRole('admin')) {
    header('Location: ../../login.php?error=Unauthorized');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Insert Sale - Quick</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php renderSidebar('sales'); ?>
    <div class="main-wrapper">
        <?php renderTopNavbar(); ?>
        <div class="main-content container py-4">
            <h2>Insert Sale (Quick)</h2>
            <p>Use this form to manually insert a sale record into the database.</p>
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
                <?php unset($_SESSION['success']); endif; ?>
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
                <?php unset($_SESSION['error']); endif; ?>

            <form method="POST" action="../../api/add_sales_record.php" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Product *</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">-- Select product --</option>
                        <?php
                        $prods = $conn->query("SELECT id, name, sku, price FROM products WHERE status='active' ORDER BY name LIMIT 1000");
                        while ($pr = $prods->fetch_assoc()): ?>
                        <option data-price="<?php echo $pr['price']; ?>" value="<?php echo $pr['id']; ?>"><?php echo htmlspecialchars($pr['name'] . ' (' . $pr['sku'] . ')'); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantity *</label>
                    <input type="number" name="quantity" min="1" value="1" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Unit Price *</label>
                    <input type="number" step="0.01" name="price" class="form-control" value="0.00" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date & Time</label>
                    <input type="datetime-local" name="transaction_date" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Insert Sale</button>
                    <a href="sales_records.php" class="btn btn-secondary">Back to Sales Records</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        // auto-fill price when product selected
        document.querySelector('select[name="product_id"]').addEventListener('change', function(){
            var opt = this.selectedOptions[0];
            var price = opt ? opt.dataset.price || '0.00' : '0.00';
            document.querySelector('input[name="price"]').value = parseFloat(price).toFixed(2);
        });
    </script>
</body>
</html>
