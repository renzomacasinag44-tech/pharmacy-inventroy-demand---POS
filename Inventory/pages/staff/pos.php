<?php
// Staff POS Page - Same as Owner POS
// Staff role also has access to POS for processing sales

require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/navbar.php';

// Check if user is logged in and has POS access (Staff)
if (!Auth::isLoggedIn()) {
    header('Location: ../../login.php?error=Unauthorized access');
    exit();
}

if (!Auth::hasRole('staff')) {
    header('Location: ../../login.php?error=POS access denied - Staff only');
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['name'] ?? 'Cashier';
$role = $_SESSION['role'] ?? '';

// Determine allowed page for this role
$page_key = 'pos_staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale - MangaRingMed Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .pos-container {
            display: flex;
            height: calc(100vh - 70px);
            gap: 0;
            margin: 0;
        }

        .pos-left {
            flex: 0 0 50%;
            overflow-y: auto;
            border-right: 2px solid #e0e0e0;
            padding: 20px;
            background: #fff;
        }

        .pos-right {
            flex: 0 0 50%;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
        }

        .pos-search-box {
            position: sticky;
            top: 0;
            background: white;
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
            z-index: 100;
        }

        .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .product-card:hover {
            box-shadow: 0 4px 12px rgba(30, 126, 52, 0.15);
            transform: translateY(-2px);
            border-color: var(--primary-color);
        }

        .product-card.active {
            background: #f0f8f4;
            border-color: var(--primary-color);
        }

        .product-card h6 {
            margin: 0 0 8px 0;
            color: var(--primary-color);
            font-weight: 600;
        }

        .product-info-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #666;
            margin: 4px 0;
        }

        .stock-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .stock-badge.in-stock {
            background: #d4edda;
            color: #155724;
        }

        .stock-badge.low-stock {
            background: #fff3cd;
            color: #856404;
        }

        .stock-badge.out-of-stock {
            background: #f8d7da;
            color: #721c24;
        }

        .pos-price {
            font-size: 1.2rem;
            color: var(--primary-color);
            font-weight: 700;
        }

        .cart-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .cart-items {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            overflow-y: auto;
            background: white;
            min-height: 300px;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            gap: 10px;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #eee;
            background: white;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item-info {
            display: flex;
            flex-direction: column;
        }

        .cart-item-name {
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .cart-item-meta {
            font-size: 0.85rem;
            color: #999;
            margin: 2px 0 0 0;
        }

        .qty-control {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .qty-control button {
            padding: 2px 8px;
            font-size: 0.85rem;
            min-width: auto;
        }

        .qty-control input {
            width: 50px;
            text-align: center;
            padding: 4px;
        }

        .cart-item-price {
            text-align: right;
            min-width: 100px;
            font-weight: 600;
        }

        .cart-item-remove {
            background: #f8d7da;
            color: #721c24;
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .cart-item-remove:hover {
            background: #f5c6cb;
        }

        .empty-cart {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
        }

        .empty-cart i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .checkout-section {
            background: white;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #ddd;
        }

        .checkout-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 0.95rem;
        }

        .checkout-row-total {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 1.1rem;
            font-weight: 700;
            border-top: 2px solid #e0e0e0;
            border-bottom: 2px solid #e0e0e0;
            margin: 10px 0;
            color: var(--primary-color);
        }

        .discount-section {
            margin: 12px 0;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }

        .discount-input-group {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .discount-input-group input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .discount-input-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .discount-buttons {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .discount-buttons button {
            flex: 1;
            padding: 8px;
            font-size: 0.9rem;
        }

        .payment-section {
            margin-top: 12px;
        }

        .payment-input {
            margin: 8px 0;
        }

        .payment-input input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }

        .payment-label {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 4px;
            color: #333;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }

        .action-buttons button {
            flex: 1;
            padding: 12px;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-complete-sale {
            background: var(--primary-color);
            color: white;
        }

        .btn-complete-sale:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 126, 52, 0.3);
        }

        .btn-cancel {
            background: #e0e0e0;
            color: #333;
        }

        .btn-cancel:hover {
            background: #d0d0d0;
        }

        .btn-clear-cart {
            background: #f8d7da;
            color: #721c24;
            margin-top: 8px;
            width: 100%;
        }

        .btn-clear-cart:hover {
            background: #f5c6cb;
        }

        @media (max-width: 1024px) {
            .pos-left, .pos-right {
                flex: 0 0 100%;
            }

            .pos-container {
                flex-direction: column;
            }

            .pos-left {
                border-right: none;
                border-bottom: 2px solid #e0e0e0;
            }
        }

        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }

        .error-message {
            padding: 12px;
            background: #f8d7da;
            color: #721c24;
            border-radius: 6px;
            margin-bottom: 12px;
            display: none;
        }

        .success-message {
            padding: 12px;
            background: #d4edda;
            color: #155724;
            border-radius: 6px;
            margin-bottom: 12px;
            display: none;
        }

        .info-badge {
            display: inline-block;
            background: #d1ecf1;
            color: #0c5460;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            margin: 2px;
        }
    </style>
</head>
<body>
    <?php renderSidebar($page_key); ?>
    
    <div class="main-wrapper">
        <?php renderTopNavbar(); ?>
        
        <div class="pos-container">
            <!-- LEFT PANEL: PRODUCT SEARCH & SELECTION -->
            <div class="pos-left">
                <div class="pos-search-box">
                    <h5><i class="fas fa-search"></i> Search Products</h5>
                    <div class="input-group" style="margin-top: 12px;">
                        <input type="text" id="productSearch" class="form-control" placeholder="Search by name, SKU, or barcode...">
                        <button class="btn btn-primary" onclick="searchProducts()" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div style="margin-top: 8px; font-size: 0.9rem; color: #666;">
                        <i class="fas fa-info-circle"></i> Press Enter or click search
                    </div>
                </div>

                <div id="productResults"></div>
            </div>

            <!-- RIGHT PANEL: CART & CHECKOUT -->
            <div class="pos-right">
                <div class="cart-section">
                    <div>
                        <h5><i class="fas fa-shopping-cart"></i> Shopping Cart</h5>
                    </div>

                    <div class="error-message" id="errorMessage"></div>
                    <div class="success-message" id="successMessage"></div>

                    <div class="cart-items" id="cartItems">
                        <div class="empty-cart">
                            <i class="fas fa-inbox"></i>
                            <p>Your cart is empty</p>
                            <small>Search and add products from the left panel</small>
                        </div>
                    </div>

                    <button class="btn-clear-cart" onclick="clearCart()" id="clearCartBtn" style="display: none;">
                        <i class="fas fa-trash"></i> Clear Cart
                    </button>
                </div>

                <!-- CHECKOUT SECTION -->
                <div class="checkout-section">
                    <h5 style="margin-bottom: 15px;"><i class="fas fa-credit-card"></i> Checkout</h5>

                    <div class="checkout-row">
                        <span>Subtotal:</span>
                        <span id="subtotalDisplay">₱ 0.00</span>
                    </div>

                    <!-- DISCOUNT SECTION -->
                    <div class="discount-section" id="discountSection" style="display: none;">
                        <label style="font-weight: 600; margin-bottom: 8px; display: block;">
                            <i class="fas fa-tag"></i> Apply Discount
                        </label>
                        
                        <div class="discount-input-group">
                            <select id="discountType" onchange="updateDiscountDisplay()">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed_amount">Fixed Amount (₱)</option>
                            </select>
                            <input type="number" id="discountValue" placeholder="Enter discount value" min="0" step="0.01" onchange="updateDiscountDisplay()">
                        </div>

                        <div class="checkout-row" style="color: #ff6b6b; font-weight: 600;">
                            <span>Discount Amount:</span>
                            <span id="discountAmountDisplay">-₱ 0.00</span>
                        </div>

                        <div class="discount-buttons">
                            <button class="btn btn-sm btn-success" onclick="applyDiscount()">Apply</button>
                            <button class="btn btn-sm btn-secondary" onclick="removeDiscount()">Remove</button>
                        </div>
                    </div>

                    <button class="btn btn-sm btn-outline-primary" onclick="toggleDiscountSection()" id="toggleDiscountBtn" style="display: none; width: 100%; margin: 8px 0;">
                        <i class="fas fa-plus"></i> Add Discount
                    </button>

                    <div class="checkout-row-total">
                        <span>TOTAL:</span>
                        <span id="totalDisplay">₱ 0.00</span>
                    </div>

                    <!-- PAYMENT SECTION -->
                    <div class="payment-section">
                        <div class="payment-input">
                            <label class="payment-label">Amount Paid</label>
                            <input type="number" id="amountPaid" placeholder="0.00" min="0" step="0.01" onchange="calculateChange()">
                        </div>

                        <div class="checkout-row" style="background: #f0f8f4; padding: 10px; border-radius: 4px; margin-top: 8px;">
                            <span>Change:</span>
                            <span id="changeDisplay" style="color: var(--primary-color); font-weight: 600;">₱ 0.00</span>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button class="btn-complete-sale" onclick="completeSale()">
                            <i class="fas fa-check-circle"></i> Complete Sale
                        </button>
                        <button class="btn-cancel" onclick="resetCart()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1" backdrop="static" keyboard="false">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title">Receipt</h5>
                </div>
                <div class="modal-body" id="receiptContent" style="font-family: monospace; background: white;">
                    <!-- Receipt will be inserted here -->
                </div>
                <div class="modal-footer border-top-0">
                    <button class="btn btn-primary" onclick="printReceipt()">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button class="btn btn-success" onclick="newSale()">
                        <i class="fas fa-plus"></i> New Sale
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner-border" role="status" style="color: var(--primary-color);">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/pos.js"></script>
    <script>
        // Initialize POS on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializePOS({
                userId: <?php echo $user_id; ?>,
                userName: '<?php echo htmlspecialchars($user_name); ?>'
            });
            searchProducts();
        });

        // Allow search on Enter key
        document.getElementById('productSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchProducts();
            }
        });
    </script>
</body>
</html>
