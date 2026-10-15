<?php
/**
 * POS Complete Sale API
 * Processes a complete sale transaction with multiple items
 * Handles inventory deduction, batch tracking, and discount recording
 * Uses database transactions for data integrity
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Check authentication
if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check role access (Owner or Staff only)
if (!Auth::hasAnyRole(['owner', 'staff'])) {
    echo json_encode(['success' => false, 'error' => 'POS access denied']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit();
}

// Validate input
$items = $input['items'] ?? [];
$subtotal = floatval($input['subtotal'] ?? 0);
$discount_type = $input['discount_type'] ?? null;
$discount_value = floatval($input['discount_value'] ?? 0);
$discount_amount = floatval($input['discount_amount'] ?? 0);
$total = floatval($input['total'] ?? 0);
$amount_paid = floatval($input['amount_paid'] ?? 0);
$change = floatval($input['change'] ?? 0);
$user_id = intval($input['user_id'] ?? $_SESSION['user_id']);

// Validation
if (empty($items) || count($items) === 0) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit();
}

if ($subtotal <= 0 || $total <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid sale amount']);
    exit();
}

if ($amount_paid < $total) {
    echo json_encode(['success' => false, 'error' => 'Insufficient payment']);
    exit();
}

// Generate unique transaction reference
$transaction_ref = 'POS-' . date('YmdHis') . '-' . substr(md5(uniqid()), 0, 6);

// Begin transaction
$conn->begin_transaction();

try {
    $sales_records = [];
    $receipt_items = [];

    // Process each cart item
    foreach ($items as $item) {
        $product_id = intval($item['product_id'] ?? 0);
        $quantity = intval($item['quantity'] ?? 0);
        $price = floatval($item['price'] ?? 0);
        $item_subtotal = floatval($item['subtotal'] ?? 0);

        // Validation
        if ($product_id <= 0 || $quantity <= 0 || $price < 0) {
            throw new Exception('Invalid item data');
        }

        // Check if product exists
        $product_check = $conn->prepare("SELECT id, name, quantity, min_stock FROM products WHERE id = ?");
        $product_check->bind_param('i', $product_id);
        $product_check->execute();
        $prod_result = $product_check->get_result();

        if ($prod_result->num_rows === 0) {
            throw new Exception('Product not found: ID ' . $product_id);
        }

        $product = $prod_result->fetch_assoc();

        // Check stock availability (re-validate at checkout)
        if ($product['quantity'] < $quantity) {
            throw new Exception('Insufficient stock for: ' . $product['name'] . '. Available: ' . $product['quantity']);
        }

        // Check for expired products - get most recent batch
        $batch_check = $conn->prepare("
            SELECT it.id, it.reference_number, it.expiration_date
            FROM inventory_transactions it
            WHERE it.product_id = ? 
            AND it.transaction_type = 'stock_in'
            AND it.expiration_date IS NOT NULL
            AND it.expiration_date >= CURDATE()
            ORDER BY it.expiration_date ASC, it.transaction_date DESC
            LIMIT 1
        ");
        $batch_check->bind_param('i', $product_id);
        $batch_check->execute();
        $batch_result = $batch_check->get_result();

        $batch_reference = null;
        if ($batch_result->num_rows > 0) {
            $batch = $batch_result->fetch_assoc();
            $batch_reference = $batch['reference_number'];
        }

        // Create sales record (individual item)
        $sales_insert = $conn->prepare("
            INSERT INTO sales (
                product_id,
                quantity,
                price,
                subtotal,
                total_amount,
                discount_type,
                discount_value,
                discount_amount,
                amount_paid,
                change_amount,
                transaction_ref,
                recorded_by,
                created_at,
                payment_method
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");

        // For individual item in grouped transaction: calculate proportional discount
        $item_discount_amount = 0;
        if ($discount_type) {
            if ($discount_type === 'percentage') {
                $item_discount_amount = ($item_subtotal / $subtotal) * $discount_amount;
            } else {
                $item_discount_amount = ($item_subtotal / $subtotal) * $discount_amount;
            }
        }

        $item_total = $item_subtotal - $item_discount_amount;
        $payment_method = 'cash'; // Default to cash for POS

        $sales_insert->bind_param(
            'iidddsddddsis',
            $product_id,
            $quantity,
            $price,
            $item_subtotal,
            $item_total,
            $discount_type,
            $discount_value,
            $discount_amount,
            $amount_paid,
            $change,
            $transaction_ref,
            $user_id,
            $payment_method
        );

        if (!$sales_insert->execute()) {
            throw new Exception('Failed to insert sales record: ' . $conn->error);
        }

        $sale_id = $conn->insert_id;

        // Create inventory transaction (stock_out)
        $inv_trans = $conn->prepare("
            INSERT INTO inventory_transactions (
                product_id,
                transaction_type,
                quantity,
                reference_number,
                notes,
                recorded_by,
                transaction_date
            ) VALUES (?, 'stock_out', ?, ?, ?, ?, NOW())
        ");

        $notes = 'POS Sale - Transaction: ' . $transaction_ref;
        $inv_trans->bind_param(
            'iisss',
            $product_id,
            $quantity,
            $batch_reference,
            $notes,
            $user_id
        );

        if (!$inv_trans->execute()) {
            throw new Exception('Failed to create inventory transaction: ' . $conn->error);
        }

        // Update product quantity
        $product_update = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
        $product_update->bind_param('ii', $quantity, $product_id);

        if (!$product_update->execute()) {
            throw new Exception('Failed to update product quantity: ' . $conn->error);
        }

        // Store for receipt
        $receipt_items[] = [
            'id' => $sale_id,
            'name' => $product['name'],
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $item_subtotal
        ];

        $sales_records[] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'price' => $price
        ];
    }

    // Commit transaction if all operations successful
    $conn->commit();

    $auth = new Auth($conn);
    $user = $auth->getUserById($user_id);
    $cashier_name = $user['full_name'] ?? 'Staff';

    // Generate receipt data
    $receipt = [
        'transaction_id' => $transaction_ref,
        'date' => date('m/d/Y'),
        'time' => date('g:i A'),
        'cashier' => $cashier_name,
        'items' => $receipt_items,
        'subtotal' => $subtotal,
        'discount_type' => $discount_type,
        'discount_value' => $discount_value,
        'discount_amount' => $discount_amount,
        'total' => $total,
        'amount_paid' => $amount_paid,
        'change' => $change
    ];

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Sale completed successfully',
        'transaction_ref' => $transaction_ref,
        'receipt' => $receipt,
        'sales_count' => count($sales_records)
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'transaction_ref' => $transaction_ref ?? null
    ]);
}

?>
