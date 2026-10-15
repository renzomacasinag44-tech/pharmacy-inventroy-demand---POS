<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/navbar.php';

// Check if user is logged in and has appropriate role
if (!Auth::hasAnyRole(['admin', 'owner'])) {
    header('Location: ../../login.php?error=Unauthorized access');
    exit();
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$records_per_page = 15;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Count total products
if ($search) {
    $count_result = $conn->query("SELECT COUNT(*) as count FROM products WHERE name LIKE '%$search%' OR sku LIKE '%$search%'");
} else {
    $count_result = $conn->query("SELECT COUNT(*) as count FROM products");
}
$total = $count_result->fetch_assoc()['count'];
$pagination = paginate($total, $records_per_page, $page);
$offset = $pagination['offset'];

// Get products
if ($search) {
    $products = $conn->query("SELECT * FROM products WHERE name LIKE '%$search%' OR sku LIKE '%$search%' LIMIT $offset, $records_per_page");
} else {
    $products = $conn->query("SELECT * FROM products LIMIT $offset, $records_per_page");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Products - MangaRingMed Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <!-- SIDEBAR -->
    <?php renderSidebar('products'); ?>
    
    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <!-- TOP NAVBAR -->
        <?php renderTopNavbar(); ?>
        
        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <div class="page-title">
                    <h1><i class="fas fa-box"></i> Product Catalog</h1>
                    <p>Browse and manage all products in inventory</p>
                </div>
            </div>
            
            <!-- SEARCH & FILTER -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="search" placeholder="Search by product name or SKU..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- PRODUCTS TABLE -->
            <div class="card">
                <div class="card-header">
                    <h5 style="margin: 0;"><i class="fas fa-list"></i> Products (<?php echo $total; ?> found)</h5>
                </div>
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $products->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($product['sku']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($product['name']); ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($product['description'] ?? ''); ?></small>
                                </td>
                                <td><?php echo formatCurrency($product['price']); ?></td>
                                <td>
                                    <strong><?php echo $product['quantity']; ?></strong> <?php echo htmlspecialchars($product['unit'] ?? 'unit'); ?>
                                    <?php echo getStockStatus($product['quantity'], $product['min_stock']); ?>
                                </td>
                                <td>
                                    <?php if ($product['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php elseif ($product['status'] === 'inactive'): ?>
                                        <span class="badge bg-warning">Inactive</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Discontinued</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewProductDetails(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- PAGINATION -->
                <div class="card-footer">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($pagination['has_previous']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">Previous</span>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                <?php if ($i === $page): ?>
                                    <li class="page-item active">
                                        <span class="page-link"><?php echo $i; ?></span>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($pagination['has_next']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">Next</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
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
        
        function viewProductDetails(productId) {
            alert('Product details modal coming soon!');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
