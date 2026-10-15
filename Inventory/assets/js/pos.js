// ========================================
// MangaRingMed POS System - JavaScript Module
// ========================================

let POSState = {
    cart: [],
    subtotal: 0,
    discount: {
        applied: false,
        type: 'percentage',
        value: 0,
        amount: 0
    },
    total: 0,
    userId: 0,
    userName: '',
    amountPaid: 0,
    change: 0,
    lastTransactionRef: null
};

// Prevent duplicate submission flag
let isSubmitting = false;

/**
 * Initialize POS System
 */
function initializePOS(config) {
    POSState.userId = config.userId;
    POSState.userName = config.userName;
    console.log('POS Initialized for:', config.userName);
    updateCartDisplay();
}

/**
 * PRODUCT SEARCH & SELECTION
 */
function searchProducts() {
    const searchTerm = document.getElementById('productSearch').value.trim();
    
    // Allow blank search to show all active products on first load
    showLoading();

    fetch('/Inventory/api/pos_search_products.php?search=' + encodeURIComponent(searchTerm))
        .then(response => response.json())
        .then(data => {
            hideLoading();
            
            if (!data.success) {
                showError(data.error || 'Search failed');
                return;
            }

            displaySearchResults(data.products || []);
        })
        .catch(error => {
            hideLoading();
            showError('Network error: ' + error.message);
            console.error('Search error:', error);
        });
}

function displaySearchResults(products) {
    const resultsDiv = document.getElementById('productResults');
    
    if (products.length === 0) {
        resultsDiv.innerHTML = '<div class="alert alert-warning">No products found</div>';
        return;
    }

    const grouped = {};
    products.forEach(product => {
        const category = product.category || 'Uncategorized';
        if (!grouped[category]) grouped[category] = [];
        grouped[category].push(product);
    });

    let html = '<div style="margin-bottom: 10px; color: #666; font-weight: 600;">Found ' + products.length + ' product(s):</div>';

    Object.keys(grouped).sort().forEach(category => {
        html += `
            <div style="margin-bottom: 18px;">
                <div style="background:#f3f6f4; border:1px solid #dfe8e2; border-radius:8px 8px 0 0; padding:10px 12px; font-weight:700; color:#1e7e34;">
                    ${escapeHtml(category)}
                </div>
                <div style="border:1px solid #dfe8e2; border-top:none; border-radius:0 0 8px 8px; padding:10px; background:#fff;">
        `;

        grouped[category].forEach(product => {
            const stockClass = product.quantity <= 0 ? 'out-of-stock' : 
                              (product.quantity <= product.min_stock ? 'low-stock' : 'in-stock');
            const stockText = product.quantity <= 0 ? 'Out of Stock' : 
                             (product.quantity <= product.min_stock ? 'Low Stock' : 'In Stock');

            html += `
                <div class="product-card" onclick="addToCart(${product.id}, '${escapeHtml(product.name)}', ${product.price}, ${product.quantity}, '${product.sku || ''}', '${product.expiration_date || 'N/A'}', '${product.status || 'active'}')">
                    <h6>${escapeHtml(product.name)}</h6>
                    <div class="product-info-row">
                        <span><strong>SKU:</strong> ${escapeHtml(product.sku || 'N/A')}</span>
                        <span class="stock-badge ${stockClass}">${stockText}</span>
                    </div>
                    <div class="product-info-row">
                        <span><strong>Stock:</strong> ${product.quantity} ${escapeHtml(product.unit || 'unit')}</span>
                        <span style="font-size:0.75rem; color:#666; text-transform:capitalize;">${escapeHtml(product.status || 'active')}</span>
                    </div>
                    ${product.expiration_date ? `
                    <div class="product-info-row" style="color: #d9534f; font-weight: 600;">
                        <span><strong>Expires:</strong> ${product.expiration_date}</span>
                    </div>
                    ` : ''}
                    <div class="product-info-row" style="margin-top: 8px;">
                        <span class="pos-price">₱ ${parseFloat(product.price).toFixed(2)}</span>
                        <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); addToCart(${product.id}, '${escapeHtml(product.name)}', ${product.price}, ${product.quantity}, '${product.sku || ''}', '${product.expiration_date || 'N/A'}', '${product.status || 'active'}')">
                            <i class="fas fa-cart-plus"></i> Add
                        </button>
                    </div>
                </div>
            `;
        });

        html += '</div></div>';
    });

    resultsDiv.innerHTML = html;
}

/**
 * ADD TO CART
 */
function addToCart(productId, productName, price, availableStock, sku, expirationDate, status) {
    if ((status || 'active') !== 'active') {
        showError('This product is not active and cannot be sold: ' + productName);
        return;
    }

    // Check if expired
    if (expirationDate && expirationDate !== 'N/A') {
        const expDate = new Date(expirationDate);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (expDate < today) {
            showError('Cannot add expired product: ' + productName);
            return;
        }
    }

    // Check if out of stock
    if (availableStock <= 0) {
        showError('Out of stock: ' + productName);
        return;
    }

    // Check if already in cart
    const existingItem = POSState.cart.find(item => item.id === productId);
    
    if (existingItem) {
        // Increase quantity
        existingItem.quantity += 1;
    } else {
        // Add new item
        POSState.cart.push({
            id: productId,
            name: productName,
            price: parseFloat(price),
            quantity: 1,
            availableStock: availableStock,
            sku: sku,
            expirationDate: expirationDate,
            status: status || 'active'
        });
    }

    updateCartDisplay();
    showSuccess(productName + ' added to cart');

    // Keep the product catalog visible so the cashier can continue adding items.
    // Do not clear the search field/results after each item.
    searchProducts();
}

/**
 * UPDATE CART DISPLAY
 */
function updateCartDisplay() {
    const cartItemsDiv = document.getElementById('cartItems');
    
    if (POSState.cart.length === 0) {
        cartItemsDiv.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-inbox"></i>
                <p>Your cart is empty</p>
                <small>Search and add products from the left panel</small>
            </div>
        `;
        document.getElementById('clearCartBtn').style.display = 'none';
        document.getElementById('toggleDiscountBtn').style.display = 'none';
    } else {
        let html = '';
        POSState.subtotal = 0;

        POSState.cart.forEach((item, index) => {
            const itemSubtotal = item.price * item.quantity;
            POSState.subtotal += itemSubtotal;

            html += `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <p class="cart-item-name">${escapeHtml(item.name)}</p>
                        <p class="cart-item-meta">SKU: ${escapeHtml(item.sku || 'N/A')}</p>
                        ${item.expirationDate && item.expirationDate !== 'N/A' ? `
                        <p class="cart-item-meta" style="color: #d9534f;">Exp: ${item.expirationDate}</p>
                        ` : ''}
                    </div>
                    <div class="qty-control">
                        <button onclick="changeQty(${index}, -1)" class="btn btn-sm btn-outline-secondary">-</button>
                        <input type="number" value="${item.quantity}" min="1" max="${item.availableStock}" onchange="setQty(${index}, this.value)" class="form-control">
                        <button onclick="changeQty(${index}, 1)" class="btn btn-sm btn-outline-secondary">+</button>
                    </div>
                    <div class="cart-item-price">
                        <div>₱ ${itemSubtotal.toFixed(2)}</div>
                        <small style="display: block; color: #666;">₱ ${item.price.toFixed(2)} × ${item.quantity}</small>
                    </div>
                    <button class="cart-item-remove" onclick="removeFromCart(${index})">Remove</button>
                </div>
            `;
        });

        cartItemsDiv.innerHTML = html;
        document.getElementById('clearCartBtn').style.display = 'block';
        document.getElementById('toggleDiscountBtn').style.display = 'block';
    }

    // Update totals
    calculateTotals();
}

/**
 * CART OPERATIONS
 */
function changeQty(index, delta) {
    const item = POSState.cart[index];
    const newQty = item.quantity + delta;
    
    if (newQty < 1) {
        removeFromCart(index);
        return;
    }
    
    if (newQty > item.availableStock) {
        showError('Cannot exceed available stock: ' + item.availableStock);
        return;
    }
    
    item.quantity = newQty;
    updateCartDisplay();
}

function setQty(index, newValue) {
    const qty = parseInt(newValue, 10) || 0;
    const item = POSState.cart[index];
    
    if (qty < 1 || qty > item.availableStock) {
        showError('Invalid quantity. Available: ' + item.availableStock);
        updateCartDisplay();
        return;
    }
    
    item.quantity = qty;
    updateCartDisplay();
}

function removeFromCart(index) {
    const item = POSState.cart[index];
    POSState.cart.splice(index, 1);
    showSuccess(item.name + ' removed from cart');
    updateCartDisplay();
}

function clearCart() {
    if (confirm('Clear entire cart?')) {
        POSState.cart = [];
        POSState.discount = {
            applied: false,
            type: 'percentage',
            value: 0,
            amount: 0
        };
        updateCartDisplay();
        showSuccess('Cart cleared');
    }
}

function resetCart() {
    if (confirm('Cancel this transaction and clear the cart?')) {
        clearCart();
        document.getElementById('amountPaid').value = '';
        calculateChange();
    }
}

/**
 * DISCOUNT HANDLING
 */
function toggleDiscountSection() {
    const section = document.getElementById('discountSection');
    section.style.display = section.style.display === 'none' ? 'block' : 'none';
    document.getElementById('toggleDiscountBtn').style.display = 'none';
}

function updateDiscountDisplay() {
    const type = document.getElementById('discountType').value;
    const value = parseFloat(document.getElementById('discountValue').value) || 0;
    
    let discountAmount = 0;
    
    if (type === 'percentage') {
        discountAmount = (POSState.subtotal * value) / 100;
    } else {
        discountAmount = value;
    }
    
    // Prevent discount from exceeding subtotal
    if (discountAmount > POSState.subtotal) {
        discountAmount = POSState.subtotal;
    }
    
    POSState.discount.type = type;
    POSState.discount.value = value;
    POSState.discount.amount = discountAmount;
    
    document.getElementById('discountAmountDisplay').textContent = '-₱ ' + discountAmount.toFixed(2);
    calculateTotals();
}

function applyDiscount() {
    const value = parseFloat(document.getElementById('discountValue').value);
    
    if (!value || value < 0) {
        showError('Enter a valid discount value');
        return;
    }
    
    updateDiscountDisplay();
    POSState.discount.applied = true;
    showSuccess('Discount applied');
}

function removeDiscount() {
    POSState.discount = {
        applied: false,
        type: 'percentage',
        value: 0,
        amount: 0
    };
    document.getElementById('discountValue').value = '';
    document.getElementById('discountType').value = 'percentage';
    document.getElementById('discountAmountDisplay').textContent = '-₱ 0.00';
    document.getElementById('discountSection').style.display = 'none';
    document.getElementById('toggleDiscountBtn').style.display = 'block';
    calculateTotals();
    showSuccess('Discount removed');
}

/**
 * CALCULATIONS
 */
function calculateTotals() {
    if (POSState.discount.applied) {
        POSState.total = POSState.subtotal - POSState.discount.amount;
    } else {
        POSState.total = POSState.subtotal;
    }
    
    // Ensure total is not negative
    if (POSState.total < 0) POSState.total = 0;
    
    document.getElementById('subtotalDisplay').textContent = '₱ ' + POSState.subtotal.toFixed(2);
    document.getElementById('totalDisplay').textContent = '₱ ' + POSState.total.toFixed(2);
    calculateChange();
}

function calculateChange() {
    const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
    POSState.amountPaid = amountPaid;
    POSState.change = amountPaid - POSState.total;
    
    document.getElementById('changeDisplay').textContent = '₱ ' + POSState.change.toFixed(2);
}

/**
 * COMPLETE SALE
 */
function completeSale() {
    // Validation
    if (POSState.cart.length === 0) {
        showError('Cart is empty');
        return;
    }

    if (POSState.amountPaid < POSState.total) {
        showError('Amount paid is insufficient (Minimum: ₱ ' + POSState.total.toFixed(2) + ')');
        return;
    }

    if (isSubmitting) {
        showError('Processing... Please wait');
        return;
    }

    isSubmitting = true;
    showLoading();

    // Prepare sale data
    const saleData = {
        items: POSState.cart.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
            price: item.price,
            subtotal: item.price * item.quantity
        })),
        subtotal: POSState.subtotal,
        discount_type: POSState.discount.applied ? POSState.discount.type : null,
        discount_value: POSState.discount.applied ? POSState.discount.value : 0,
        discount_amount: POSState.discount.applied ? POSState.discount.amount : 0,
        total: POSState.total,
        amount_paid: POSState.amountPaid,
        change: POSState.change,
        user_id: POSState.userId
    };

    // Send to backend
    fetch('/Inventory/api/pos_complete_sale.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(saleData)
    })
    .then(async response => {
        const rawText = await response.text();
        let data;

        try {
            data = rawText ? JSON.parse(rawText) : {};
        } catch (e) {
            console.error('POS API returned non-JSON response:', rawText);
            throw new Error('Server returned an invalid response. Check database migration and PHP error logs.');
        }

        if (!response.ok) {
            throw new Error(data.error || 'Sale request failed');
        }

        return data;
    })
    .then(data => {
        hideLoading();
        isSubmitting = false;

        if (!data.success) {
            showError(data.error || 'Sale processing failed');
            return;
        }

        // Store transaction reference for receipt
        POSState.lastTransactionRef = data.transaction_ref;
        
        // Display receipt
        showReceipt(data.receipt);
    })
    .catch(error => {
        hideLoading();
        isSubmitting = false;
        showError(error.message || 'Network error');
        console.error('Sale error:', error);
    });
}

/**
 * RECEIPT HANDLING
 */
function showReceipt(receiptData) {
    const receiptHtml = generateReceiptHTML(receiptData);
    document.getElementById('receiptContent').innerHTML = receiptHtml;
    
    const modal = new bootstrap.Modal(document.getElementById('receiptModal'), {
        keyboard: false,
        backdrop: 'static'
    });
    modal.show();
}

function generateReceiptHTML(data) {
    let html = `
        <div style="text-align: center; margin-bottom: 20px;">
            <h5 style="margin: 0; font-weight: 700;">MangaringMed Pharmacy</h5>
            <small style="display: block; color: #666; margin: 4px 0;">═════════════════════</small>
        </div>
        
        <div style="margin-bottom: 12px; font-size: 0.9rem;">
            <div><strong>Transaction:</strong> ${escapeHtml(data.transaction_id)}</div>
            <div><strong>Date:</strong> ${data.date}</div>
            <div><strong>Time:</strong> ${data.time}</div>
            <div><strong>Cashier:</strong> ${escapeHtml(data.cashier)}</div>
        </div>
        
        <div style="margin: 12px 0; font-size: 0.85rem;">
            <div style="border-top: 1px solid #333; border-bottom: 1px solid #333; padding: 8px 0;">
                ${data.items.map(item => `
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>${escapeHtml(item.name)}</span>
                        <span>${item.quantity} × ₱${parseFloat(item.price).toFixed(2)}</span>
                    </div>
                    <div style="text-align: right; font-weight: 600; margin-bottom: 8px;">₱${parseFloat(item.subtotal).toFixed(2)}</div>
                `).join('')}
            </div>
        </div>
        
        <div style="font-size: 0.9rem; margin: 12px 0;">
            <div style="display: flex; justify-content: space-between;">
                <span>Subtotal:</span>
                <span>₱${parseFloat(data.subtotal).toFixed(2)}</span>
            </div>
            ${data.discount_amount > 0 ? `
                <div style="display: flex; justify-content: space-between; color: #2d9b4a;">
                    <span>Discount (${data.discount_type === 'percentage' ? data.discount_value + '%' : 'Fixed'}):</span>
                    <span>-₱${parseFloat(data.discount_amount).toFixed(2)}</span>
                </div>
            ` : ''}
            <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 1rem; margin-top: 8px; border-top: 1px solid #333; padding-top: 8px;">
                <span>TOTAL:</span>
                <span>₱${parseFloat(data.total).toFixed(2)}</span>
            </div>
        </div>
        
        <div style="font-size: 0.9rem; margin: 12px 0; background: #f0f8f4; padding: 8px; border-radius: 4px;">
            <div style="display: flex; justify-content: space-between;">
                <span>Amount Paid:</span>
                <span><strong>₱${parseFloat(data.amount_paid).toFixed(2)}</strong></span>
            </div>
            <div style="display: flex; justify-content: space-between; color: #2d9b4a;">
                <span>Change:</span>
                <span><strong>₱${parseFloat(data.change).toFixed(2)}</strong></span>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px; font-size: 0.85rem; color: #666;">
            <p style="margin: 4px 0;">Thank you for your purchase!</p>
            <small style="display: block;">═════════════════════</small>
        </div>
    `;

    return html;
}

function printReceipt() {
    const printWindow = window.open('', '', 'width=400,height=600');
    const receiptContent = document.getElementById('receiptContent').innerHTML;
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt - Mangarin Pharmacy</title>
            <style>
                body { font-family: monospace; font-size: 14px; margin: 10px; }
                * { margin: 0; padding: 0; }
                @media print { body { margin: 0; padding: 0; } }
            </style>
        </head>
        <body>
            ${receiptContent}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
}

function newSale() {
    clearCart();
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('receiptModal'));
    modal.hide();
    
    document.getElementById('productSearch').focus();
    showSuccess('Ready for new sale');
}

/**
 * UI HELPERS
 */
function showError(message) {
    const errorDiv = document.getElementById('errorMessage');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    setTimeout(() => {
        errorDiv.style.display = 'none';
    }, 5000);
}

function showSuccess(message) {
    const successDiv = document.getElementById('successMessage');
    successDiv.textContent = message;
    successDiv.style.display = 'block';
    setTimeout(() => {
        successDiv.style.display = 'none';
    }, 3000);
}

function showLoading() {
    document.getElementById('loadingSpinner').style.display = 'block';
}

function hideLoading() {
    document.getElementById('loadingSpinner').style.display = 'none';
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
