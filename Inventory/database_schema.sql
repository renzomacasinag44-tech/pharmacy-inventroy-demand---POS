-- Create Database
CREATE DATABASE IF NOT EXISTS pharmacy_inventory;
USE pharmacy_inventory;

-- Roles Table
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- System Settings Table
CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO system_settings (setting_key, setting_value)
VALUES
    ('app_name', 'MangaringMed Pharmacy Inventory System'),
    ('company_name', 'MangaringMed Pharmacy'),
    ('currency_code', 'PHP'),
    ('currency_symbol', '₱'),
    ('tax_rate', '0'),
    ('low_stock_threshold', '10'),
    ('session_timeout', '1800'),
    ('allow_registration', '1'),
    ('maintenance_mode', '0')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role_id INT NOT NULL,
    phone VARCHAR(20),
    status ENUM('active', 'inactive', 'deactivated') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Categories Table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Suppliers Table
CREATE TABLE suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    country VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products Table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    category_id INT NOT NULL,
    supplier_id INT NOT NULL,
    sku VARCHAR(50) UNIQUE NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    cost DECIMAL(10, 2),
    quantity INT DEFAULT 0,
    min_stock INT DEFAULT 10,
    max_stock INT DEFAULT 500,
    unit VARCHAR(20),
    barcode VARCHAR(100),
    status ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    INDEX idx_category (category_id),
    INDEX idx_supplier (supplier_id),
    INDEX idx_status (status)
);

-- Inventory Transactions Table
CREATE TABLE inventory_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    transaction_type ENUM('stock_in', 'stock_out', 'adjustment', 'sale') NOT NULL,
    quantity INT NOT NULL,
    reference_number VARCHAR(50),
    notes TEXT,
    expiration_date DATE,
    recorded_by INT NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id),
    INDEX idx_product (product_id),
    INDEX idx_date (transaction_date),
    INDEX idx_expiration (expiration_date)
);

-- Sales Table
CREATE TABLE sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2),
    subtotal DECIMAL(12, 2),
    total_amount DECIMAL(12, 2),
    discount_type ENUM('percentage', 'fixed_amount') DEFAULT NULL,
    discount_value DECIMAL(10, 2) DEFAULT 0.00,
    discount_amount DECIMAL(12, 2) DEFAULT 0.00,
    amount_paid DECIMAL(12, 2) DEFAULT 0.00,
    change_amount DECIMAL(12, 2) DEFAULT 0.00,
    customer_name VARCHAR(100),
    payment_method VARCHAR(50),
    transaction_ref VARCHAR(100),
    notes TEXT,
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id),
    INDEX idx_product (product_id),
    INDEX idx_date (created_at),
    INDEX idx_transaction_ref (transaction_ref),
    INDEX idx_discount_type (discount_type)
);

-- Demand Forecasts Table
CREATE TABLE demand_forecasts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    forecast_period VARCHAR(50),
    forecast_days INT,
    forecasted_quantity INT,
    forecast_type ENUM('moving_average', 'linear_regression') DEFAULT 'moving_average',
    accuracy DECIMAL(5, 2),
    reorder_quantity INT,
    reorder_date DATE,
    forecast_status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_product (product_id),
    INDEX idx_date (created_at)
);

-- Forecast History Table
CREATE TABLE forecast_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    forecast_date DATE,
    actual_sales INT,
    forecasted_sales INT,
    variance INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_product (product_id),
    INDEX idx_date (forecast_date)
);

-- Activity Logs Table
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100),
    description TEXT,
    table_name VARCHAR(50),
    record_id INT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user (user_id),
    INDEX idx_timestamp (timestamp)
);

-- Notifications Table removed

-- POS support for existing databases
-- Run this block if the database was created before the POS module was added.
ALTER TABLE sales
    ADD COLUMN IF NOT EXISTS subtotal DECIMAL(12,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS discount_type ENUM('percentage', 'fixed_amount') DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS discount_value DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(12,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS amount_paid DECIMAL(12,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS change_amount DECIMAL(12,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS transaction_ref VARCHAR(100) DEFAULT NULL;

ALTER TABLE inventory_transactions
    ADD COLUMN IF NOT EXISTS expiration_date DATE DEFAULT NULL;

CREATE INDEX idx_transaction_ref ON sales(transaction_ref);
CREATE INDEX idx_discount_type ON sales(discount_type);
CREATE INDEX idx_expiration ON inventory_transactions(expiration_date);

SELECT 'POS database schema is ready' AS status;

