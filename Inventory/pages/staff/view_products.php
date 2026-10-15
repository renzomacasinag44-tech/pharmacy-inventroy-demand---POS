<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/navbar.php';

// Check if user is logged in and has permission to view products
if (!Auth::hasAnyRole(['staff', 'admin', 'owner'])) {
    header('Location: ../../login.php?error=Unauthorized access');
    exit();
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$records_per_page = 15;

// Count total products
$count_result = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
$total = $count_result->fetch_assoc()['count'];
$pagination = paginate($total, $records_per_page, $page);
$offset = $pagination['offset'];

// Get products
$products = $conn->query("SELECT * FROM products WHERE status = 'active' LIMIT $offset, $records_per_page");

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
                    <p>View all available products</p>
                </div>
                <div class="page-actions">
                    <a href="record_stock_in.php" class="btn btn-success btn-sm">
                        <i class="fas fa-arrow-up"></i> Stock In
                    </a>
                    <a href="record_stock_out.php" class="btn btn-warning btn-sm">
                        <i class="fas fa-arrow-down"></i> Stock Out
                    </a>
                </div>
            </div>
            
            <!-- PRODUCTS TABLE -->
            <div class="card">
                <div class="card-header">
                    <h5 style="margin: 0;"><i class="fas fa-list"></i> Products (<?php echo $total; ?> total)</h5>
                </div>
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Current Stock</th>
                                <th>Min Level</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $products->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($product['sku']); ?></strong></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo formatCurrency($product['price']); ?></td>
                                <td>
                                    <strong><?php echo $product['quantity']; ?></strong> <?php echo htmlspecialchars($product['unit']); ?>
                                </td>
                                <td><?php echo $product['min_stock']; ?></td>
                                <td>
                                    <?php echo getStockStatus($product['quantity'], $product['min_stock']); ?>
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
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
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($pagination['has_next']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
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
    </script>
</body>
</html>
<?php $conn->close(); ?>
