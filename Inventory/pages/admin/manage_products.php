<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/navbar.php';

// Check if user is logged in and is admin
if (!Auth::hasRole('admin')) {
    header('Location: ../../login.php?error=Unauthorized access');
    exit();
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$records_per_page = 15;

// Count total products
$count_result = $conn->query("SELECT COUNT(*) as count FROM products");
$total = $count_result->fetch_assoc()['count'];
$pagination = paginate($total, $records_per_page, $page);

// Get products with pagination (include category/supplier names)
$offset = $pagination['offset'];
$products = $conn->query("SELECT p.*, c.name as category_name, s.name as supplier_name 
                         FROM products p 
                         JOIN categories c ON p.category_id = c.id 
                         JOIN suppliers s ON p.supplier_id = s.id 
                         ORDER BY p.created_at DESC 
                         LIMIT $offset, $records_per_page");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - MangaRingMed Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .action-buttons { display: flex; gap: 5px; }
    </style>
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
                    <h1><i class="fas fa-box"></i> Manage Products</h1>
                    <p>Add, edit, and manage all pharmacy products</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </div>
            </div>
            
            <!-- PRODUCTS TABLE -->
            <div class="card">
                <div class="card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h5 style="margin: 0;"><i class="fas fa-list"></i> Product List (<?php echo $total; ?> total)</h5>
                        <input type="text" class="form-control form-control-sm" placeholder="Search products..." style="width: 250px;">
                    </div>
                </div>
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Supplier</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $products->fetch_assoc()): ?>
                            <tr data-product-id="<?php echo $product['id']; ?>">
                                <td><strong><?php echo htmlspecialchars($product['sku']); ?></strong></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($product['category_name']); ?></span></td>
                                <td><?php echo htmlspecialchars($product['supplier_name']); ?></td>
                                <td><?php echo formatCurrency($product['price']); ?></td>
                                <td>
                                    <strong><?php echo $product['quantity']; ?></strong>
                                    <?php if ($product['quantity'] <= $product['min_stock']): ?>
                                        <br><span class="badge bg-warning" style="font-size: 11px;">Low Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">EXPIRED</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-info" 
                                                data-id="<?php echo $product['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>" 
                                                data-sku="<?php echo htmlspecialchars($product['sku'], ENT_QUOTES); ?>" 
                                                data-category_id="<?php echo $product['category_id']; ?>" 
                                                data-supplier_id="<?php echo $product['supplier_id']; ?>" 
                                                data-price="<?php echo $product['price']; ?>" 
                                                data-cost="<?php echo $product['cost']; ?>" 
                                                data-quantity="<?php echo $product['quantity']; ?>" 
                                                data-min_stock="<?php echo $product['min_stock']; ?>" 
                                                data-max_stock="<?php echo $product['max_stock']; ?>" 
                                                data-description="<?php echo htmlspecialchars($product['description'] ?? '', ENT_QUOTES); ?>"
                                                onclick="openEditModal(this)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
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

    <!-- ADD PRODUCT MODAL -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="../../api/add_product.php">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Product Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SKU *</label>
                                <input type="text" class="form-control" name="sku" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Category *</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php
                                    $categories = $conn->query("SELECT * FROM categories WHERE status = 'active'");
                                    while ($cat = $categories->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Supplier *</label>
                                <select class="form-select" name="supplier_id" required>
                                    <option value="">Select Supplier</option>
                                    <?php
                                    $suppliers = $conn->query("SELECT * FROM suppliers WHERE status = 'active'");
                                    while ($supp = $suppliers->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $supp['id']; ?>"><?php echo htmlspecialchars($supp['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Price (₱) *</label>
                                <input type="number" class="form-control" name="price" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cost (₱)</label>
                                <input type="number" class="form-control" name="cost" step="0.01">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Initial Quantity</label>
                                <input type="number" class="form-control" name="quantity" value="0" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Min Stock Level</label>
                                <input type="number" class="form-control" name="min_stock" value="10" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Max Stock Level</label>
                                <input type="number" class="form-control" name="max_stock" value="500" min="0">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT PRODUCT MODAL -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editProductForm" method="POST" action="../../api/update_product.php">
                        <input type="hidden" name="id" id="edit_product_id">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Product Name *</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SKU *</label>
                                <input type="text" class="form-control" name="sku" id="edit_sku" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Category *</label>
                                <select class="form-select" name="category_id" id="edit_category_id" required>
                                    <option value="">Select Category</option>
                                    <?php
                                    $categories = $conn->query("SELECT * FROM categories WHERE status = 'active'");
                                    while ($cat = $categories->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Supplier *</label>
                                <select class="form-select" name="supplier_id" id="edit_supplier_id" required>
                                    <option value="">Select Supplier</option>
                                    <?php
                                    $suppliers = $conn->query("SELECT * FROM suppliers WHERE status = 'active'");
                                    while ($supp = $suppliers->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $supp['id']; ?>"><?php echo htmlspecialchars($supp['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Price (₱) *</label>
                                <input type="number" class="form-control" name="price" id="edit_price" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cost (₱)</label>
                                <input type="number" class="form-control" name="cost" id="edit_cost" step="0.01">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="quantity" id="edit_quantity" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Min Stock Level</label>
                                <input type="number" class="form-control" name="min_stock" id="edit_min_stock" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Max Stock Level</label>
                                <input type="number" class="form-control" name="max_stock" id="edit_max_stock" min="0">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <form id="deleteForm" method="POST" action="../../api/delete_product.php" style="display:none;">
        <input type="hidden" name="id" id="delete_product_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/script.js"></script>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.querySelector('.main-wrapper').classList.toggle('sidebar-collapsed');
        }

        function openEditModal(btn) {
            var id = btn.getAttribute('data-id');
            document.getElementById('edit_product_id').value = id;
            document.getElementById('edit_name').value = btn.getAttribute('data-name') || '';
            document.getElementById('edit_sku').value = btn.getAttribute('data-sku') || '';
            document.getElementById('edit_category_id').value = btn.getAttribute('data-category_id') || '';
            document.getElementById('edit_supplier_id').value = btn.getAttribute('data-supplier_id') || '';
            document.getElementById('edit_price').value = btn.getAttribute('data-price') || '';
            document.getElementById('edit_cost').value = btn.getAttribute('data-cost') || '';
            document.getElementById('edit_quantity').value = btn.getAttribute('data-quantity') || '';
            document.getElementById('edit_min_stock').value = btn.getAttribute('data-min_stock') || '';
            document.getElementById('edit_max_stock').value = btn.getAttribute('data-max_stock') || '';
            document.getElementById('edit_description').value = btn.getAttribute('data-description') || '';

            var editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
            editModal.show();
        }

        // AJAX submit for edit form
        document.addEventListener('DOMContentLoaded', function() {
            var editForm = document.getElementById('editProductForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var fd = new FormData(editForm);
                    fetch('../../api/update_product.php', {
                        method: 'POST',
                        body: fd,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    }).then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data && data.success) {
                            // update row in table
                            updateRow(data.product);
                            var editModalEl = document.getElementById('editProductModal');
                            var modal = bootstrap.Modal.getInstance(editModalEl);
                            if (modal) modal.hide();
                        } else {
                            alert(data.error || 'Failed to update product');
                        }
                    }).catch(function(err) {
                        console.error(err);
                        alert('Error saving product');
                    });
                });
            }

            // override delete to use AJAX
            window.confirmDelete = function(id, name) {
                if (!confirm('Delete product "' + name + '"? This action will mark it EXPIRED.')) return;
                var fd = new FormData(); fd.append('id', id);
                fetch('../../api/delete_product.php', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function(res){ return res.json(); })
                .then(function(data){
                    if (data && data.success) {
                        // mark row status as Expired
                        var tr = document.querySelector('tr[data-product-id="' + id + '"]');
                        if (tr) {
                            var statusCell = tr.children[6];
                            if (statusCell) statusCell.innerHTML = '<span class="badge bg-danger">EXPIRED</span>';
                        }
                    } else {
                        alert(data.error || 'Failed to delete product');
                    }
                }).catch(function(err){ console.error(err); alert('Error deleting product'); });
            };
        });

        function updateRow(product) {
            var tr = document.querySelector('tr[data-product-id="' + product.id + '"]');
            if (!tr) return;
            var tds = tr.querySelectorAll('td');
            if (tds.length < 7) return;
            tds[0].innerText = product.sku || '';
            tds[1].innerText = product.name || '';
            tds[2].innerHTML = '<span class="badge bg-info">' + (product.category_name || '') + '</span>';
            tds[3].innerText = product.supplier_name || '';
            tds[4].innerText = product.price ? new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(product.price) : '';
            tds[5].innerHTML = '<strong>' + (product.quantity || 0) + '</strong>' + ((product.quantity <= product.min_stock) ? '<br><span class="badge bg-warning" style="font-size:11px;">Low Stock</span>' : '');
            var statusHtml = product.status === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">EXPIRED</span>';
            tds[6].innerHTML = statusHtml;
            // update action buttons data attributes
            var editBtn = tr.querySelector('.action-buttons .btn-info');
            if (editBtn) {
                editBtn.setAttribute('data-name', product.name || '');
                editBtn.setAttribute('data-sku', product.sku || '');
                editBtn.setAttribute('data-category_id', product.category_id || '');
                editBtn.setAttribute('data-supplier_id', product.supplier_id || '');
                editBtn.setAttribute('data-price', product.price || '');
                editBtn.setAttribute('data-cost', product.cost || '');
                editBtn.setAttribute('data-quantity', product.quantity || '');
                editBtn.setAttribute('data-min_stock', product.min_stock || '');
                editBtn.setAttribute('data-max_stock', product.max_stock || '');
                editBtn.setAttribute('data-description', product.description || '');
            }
        }
    </script>
</body>
</html>
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
    
    <!-- ADD PRODUCT MODAL -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="../../api/add_product.php">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Product Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SKU *</label>
                                <input type="text" class="form-control" name="sku" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Category *</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php
                                    $categories = $conn->query("SELECT * FROM categories WHERE status = 'active'");
                                    while ($cat = $categories->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Supplier *</label>
                                <select class="form-select" name="supplier_id" required>
                                    <option value="">Select Supplier</option>
                                    <?php
                                    $suppliers = $conn->query("SELECT * FROM suppliers WHERE status = 'active'");
                                    while ($supp = $suppliers->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $supp['id']; ?>"><?php echo htmlspecialchars($supp['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Price (₱) *</label>
                                <input type="number" class="form-control" name="price" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cost (₱)</label>
                                <input type="number" class="form-control" name="cost" step="0.01">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Initial Quantity</label>
                                <input type="number" class="form-control" name="quantity" value="0" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Min Stock Level</label>
                                <input type="number" class="form-control" name="min_stock" value="10" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Max Stock Level</label>
                                <input type="number" class="form-control" name="max_stock" value="500" min="0">
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- EDIT PRODUCT MODAL -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="../../api/update_product.php">
                        <input type="hidden" name="id" id="edit_product_id">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Product Name *</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SKU *</label>
                                <input type="text" class="form-control" name="sku" id="edit_sku" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Category *</label>
                                <select class="form-select" name="category_id" id="edit_category_id" required>
                                    <option value="">Select Category</option>
                                    <?php
                                    $categories = $conn->query("SELECT * FROM categories WHERE status = 'active'");
                                    while ($cat = $categories->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Supplier *</label>
                                <select class="form-select" name="supplier_id" id="edit_supplier_id" required>
                                    <option value="">Select Supplier</option>
                                    <?php
                                    $suppliers = $conn->query("SELECT * FROM suppliers WHERE status = 'active'");
                                    while ($supp = $suppliers->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $supp['id']; ?>"><?php echo htmlspecialchars($supp['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Price (₱) *</label>
                                <input type="number" class="form-control" name="price" id="edit_price" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cost (₱)</label>
                                <input type="number" class="form-control" name="cost" id="edit_cost" step="0.01">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="quantity" id="edit_quantity" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Min Stock Level</label>
                                <input type="number" class="form-control" name="min_stock" id="edit_min_stock" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Max Stock Level</label>
                                <input type="number" class="form-control" name="max_stock" id="edit_max_stock" min="0">
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.querySelector('.main-wrapper').classList.toggle('sidebar-collapsed');
        }
        
        function openEditModal(btn) {
            var id = btn.getAttribute('data-id');
            document.getElementById('edit_product_id').value = id;
            document.getElementById('edit_name').value = btn.getAttribute('data-name') || '';
            document.getElementById('edit_sku').value = btn.getAttribute('data-sku') || '';
            document.getElementById('edit_description').value = btn.getAttribute('data-description') || '';
            document.getElementById('edit_price').value = btn.getAttribute('data-price') || '';
            document.getElementById('edit_cost').value = btn.getAttribute('data-cost') || '';
            document.getElementById('edit_quantity').value = btn.getAttribute('data-quantity') || 0;
            document.getElementById('edit_min_stock').value = btn.getAttribute('data-min_stock') || 0;
            document.getElementById('edit_max_stock').value = btn.getAttribute('data-max_stock') || 0;
            document.getElementById('edit_category_id').value = btn.getAttribute('data-category_id') || '';
            document.getElementById('edit_supplier_id').value = btn.getAttribute('data-supplier_id') || '';

            var editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
            editModal.show();
        }

        function confirmDelete(id, name) {
            if (!confirm('Delete product "' + name + '"? This action cannot be undone.')) return;

            var formData = new FormData();
            formData.append('id', id);

            fetch('../../api/delete_product.php', {
                method: 'POST',
                body: formData
            }).then(res => res.json())
              .then(data => {
                  if (data.success) {
                      location.reload();
                  } else {
                      alert(data.error || 'Failed to delete product');
                  }
              }).catch(err => {
                  console.error(err);
                  alert('Error deleting product');
              });
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
