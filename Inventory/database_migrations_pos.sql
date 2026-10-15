-- POS Module Database Migration
-- Add columns to support Point of Sale functionality
-- Run this migration after backing up your database

-- Add discount and payment fields to sales table
ALTER TABLE sales
    ADD COLUMN subtotal DECIMAL(12,2) COMMENT 'Original subtotal before discount',
    ADD COLUMN discount_type ENUM('percentage', 'fixed_amount') COMMENT 'Type of discount applied',
    ADD COLUMN discount_value DECIMAL(10,2) COMMENT 'Discount value (% or fixed amount)',
    ADD COLUMN discount_amount DECIMAL(12,2) COMMENT 'Calculated discount amount',
    ADD COLUMN amount_paid DECIMAL(12,2) COMMENT 'Amount paid by customer',
    ADD COLUMN change_amount DECIMAL(12,2) COMMENT 'Change to give back to customer',
    ADD COLUMN transaction_ref VARCHAR(100) COMMENT 'POS transaction reference for grouping items';

-- Update existing sales records to populate subtotal if NULL
UPDATE sales SET subtotal = total_amount WHERE subtotal IS NULL;

-- Add expiration date tracking to inventory transactions (for batch level)
ALTER TABLE inventory_transactions
    ADD COLUMN expiration_date DATE COMMENT 'Expiration date for batch/lot tracking';

-- Create indices for performance
CREATE INDEX idx_transaction_ref ON sales(transaction_ref);
CREATE INDEX idx_discount_type ON sales(discount_type);
CREATE INDEX idx_expiration ON inventory_transactions(expiration_date);

-- Verify migration
SELECT 'Migration Complete - Check sales and inventory_transactions columns' AS Status;
