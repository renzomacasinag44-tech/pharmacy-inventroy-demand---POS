# MangaRingMed Pharmacy - Inventory Management System with Demand Forecasting

A modern web-based inventory management system with AI-powered demand forecasting for the MangaRingMed Pharmacy. The system includes three user roles (Admin, Owner, and Staff) with role-based access control and comprehensive inventory tracking features.

## Features

### Core Functionality
- **Role-Based Access Control**: Three user roles with different permissions
  - System Admin: Full system management and monitoring
  - Owner/Head: Inventory management and analytics
  - Staff/Worker: Basic inventory operations

### Demand Forecasting Module
- Historical sales and stock analysis
- Forecasting algorithms:
  - Moving Average method
  - Linear Regression method
- Predictions for 7, 30, or custom days
- Forecast accuracy metrics (MAPE)
- Automatic product classification (fast-moving, slow-moving)
- Reorder recommendations with suggested quantities and dates
- AI-based inventory alerts:
  - Restock Now
  - Overstock Warning
  - Low Demand Warning
  - High Demand Alert

### User Management
- Secure authentication with password hashing
- User creation and management (Admin only)
- Role assignment and deactivation
- Profile management
- Password change functionality
- Activity logging

### Inventory Management
- Product catalog management
- Category and supplier management
- Stock tracking (in/out)
- Inventory transactions history
- Real-time stock level monitoring
- Low stock alerts

### Dashboard & Analytics
- Role-specific dashboards with key metrics
- Sales reporting
- Inventory value tracking
- Fast-moving and slow-moving product identification
- Trend analysis with interactive charts
- Activity logs and audit trails

### Database Features
- 11 normalized database tables
- Activity logging
- Notification system
- Transaction history tracking

## System Requirements

- **Server**: Apache/Nginx with PHP 7.4+
- **Database**: MySQL 5.7+
- **Browser**: Modern browsers (Chrome, Firefox, Safari, Edge)
- **Libraries**: Bootstrap 5, Chart.js, Font Awesome 6

## Installation Guide

### Step 1: Setup Database

1. Open phpMyAdmin at `http://localhost/phpmyadmin`
2. Import the database schema:
   - Copy the contents of `database_schema.sql`
   - Paste into the SQL tab in phpMyAdmin
   - Click "Go" to execute

Alternatively, using MySQL command line:
```bash
mysql -u root -p < database_schema.sql
```

### Step 2: Configure Application

1. Edit `includes/config.php` if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Add password if needed
   define('DB_NAME', 'pharmacy_inventory');
   ```

2. Ensure folder permissions:
   - `logs/` folder should be writable

### Step 3: Access the Application

1. Start Apache in XAMPP
2. Navigate to: `http://localhost/Inventory/`
3. You will be redirected to login page

## Default Login Credentials

The system comes with three pre-configured users for testing:

### System Admin
- **Email**: `admin@mangaringmed.com`
- **Password**: `admin123`
- **Access**: Full system access, all features

### Owner
- **Email**: `owner@mangaringmed.com`
- **Password**: `admin123`
- **Access**: Inventory management, analytics, forecasting

### Staff
- **Email**: `staff@mangaringmed.com`
- **Password**: `admin123`
- **Access**: Basic inventory operations, view-only access to forecasts

## Project Structure

```
Inventory/
├── index.php                 # Main entry point
├── login.php                 # Login page
├── logout.php                # Logout handler
├── profile.php               # User profile page
├── database_schema.sql       # Database setup script
├── includes/
│   ├── config.php           # Database configuration
│   ├── auth.php             # Authentication class
│   ├── functions.php        # Utility functions
│   └── navbar.php           # Navigation components
├── pages/
│   ├── admin/
│   │   ├── dashboard.php
│   │   ├── manage_products.php
│   │   ├── manage_users.php
│   │   ├── manage_categories.php
│   │   ├── manage_suppliers.php
│   │   ├── sales_records.php
│   │   ├── demand_forecast.php
│   │   ├── activity_logs.php
│   │   ├── system_settings.php
│   │   └── backup_restore.php
│   ├── owner/
│   │   ├── dashboard.php
│   │   ├── manage_products.php
│   │   ├── manage_categories.php
│   │   ├── manage_suppliers.php
│   │   ├── monitor_inventory.php
│   │   ├── record_stock.php
│   │   ├── demand_forecast.php
│   │   ├── sales_reports.php
│   │   └── inventory_reports.php
│   └── staff/
│       ├── dashboard.php
│       ├── view_products.php
│       ├── record_stock_in.php
│       ├── record_stock_out.php
│       └── view_forecast.php
├── api/
│   ├── add_product.php
│   ├── record_stock_in.php
│   ├── record_stock_out.php
│   ├── update_profile.php
│   └── change_password.php
├── assets/
│   ├── css/
│   │   └── style.css        # Main stylesheet (green pharmacy theme)
│   ├── js/
│   │   └── script.js        # JavaScript utilities
│   └── images/              # Logo and images
└── logs/                     # Activity and error logs
```

## Database Tables

1. **roles** - User roles (Admin, Owner, Staff)
2. **users** - User accounts and credentials
3. **categories** - Product categories
4. **suppliers** - Supplier information
5. **products** - Product catalog
6. **sales** - Sales records
7. **demand_forecasts** - Forecast data and predictions
8. **forecast_history** - Historical forecast data
9. **activity_logs** - User activity audit trail

## Key Features Breakdown

### Admin Dashboard
- Total users, products, and sales statistics
- Low/out-of-stock products alerts
- Recent activity log
- Sales trends chart
- Inventory distribution pie chart

### Owner Dashboard
- Inventory value tracking
- Daily and monthly sales metrics
- Demand forecasting dashboard
- Recommended reorders
- Fast and slow-moving products
- Low stock alerts with reorder buttons

### Staff Dashboard
- Quick access to common tasks
- Stock in/out recording
- Product search and viewing
- Low stock alerts
- Daily inventory summary

## Demand Forecasting Algorithm

### Moving Average Method
- Calculates average of previous 7 days (configurable)
- Useful for products with steady demand
- Shows trend smoothing

### Linear Regression Method
- Fits a line through historical data points
- Projects future demand based on trend
- Better for products with clear growth/decline patterns

### Forecast Accuracy
- MAPE (Mean Absolute Percentage Error) calculation
- Compares forecast vs. actual sales
- Accuracy percentage display

### Reorder Logic
- Calculates average daily sales
- Considers lead time (default 3 days)
- Suggests reorder quantity = (Avg Daily Sales × 30) - Current Stock
- Suggests reorder date = Today + Lead Time

## Color Scheme (MangaRingMed Pharmacy Theme)

- **Primary Green**: #1e7e34
- **Dark Green**: #166a2a
- **Light Green**: #2d9b4a
- **Secondary Yellow**: #ffc107
- **Success Green**: #28a745
- **Warning Orange**: #fd7e14
- **Danger Red**: #dc3545

## Security Features

1. **Password Security**
   - Bcrypt hashing with PASSWORD_BCRYPT
   - Minimum password length enforcement

2. **Session Management**
   - Session timeout after 30 minutes of inactivity
   - Role-based access control on every page
   - Activity logging for all actions

3. **Input Validation**
   - HTML escaping for output
   - SQL prepared statements to prevent injection
   - Email format validation

4. **Audit Trail**
   - All activities logged with timestamp
   - IP address tracking
   - User action monitoring

## API Endpoints

### POST `/api/record_stock_in.php`
Record incoming inventory

### POST `/api/record_stock_out.php`
Record outgoing inventory (sales, damage, etc.)

### POST `/api/add_product.php`
Add new product to catalog

### POST `/api/update_profile.php`
Update user profile information

### POST `/api/change_password.php`
Change user password

## Forecasting Calculations

### Moving Average Forecast
```
Forecast[i] = Average of previous 7 days sales
Accuracy = 100 - MAPE%
```

### Linear Regression Forecast
```
y = mx + b
m = (n×Σ(xy) - Σx×Σy) / (n×Σ(x²) - (Σx)²)
b = (Σy - m×Σx) / n
Future_Demand = Intercept + Slope × (Current_Day + Forecast_Days)
```

## Tips for Using the System

### For Admin Users
1. Start by setting up categories and suppliers
2. Add all products to the catalog
3. Set appropriate min/max stock levels for each product
4. Monitor activity logs regularly
5. Review demand forecasts to optimize stock levels

### For Owner Users
1. Check the dashboard daily for low stock alerts
2. Review demand forecasts weekly
3. Use reorder recommendations to plan purchases
4. Analyze fast and slow-moving products
5. Export reports for analysis and archiving

### For Staff Users
1. Record stock in/out immediately when transactions occur
2. Add detailed notes for unusual transactions
3. Check low stock alerts when scanning products
4. Report any inventory discrepancies to management
5. Use the product catalog for quick lookups

## Troubleshooting

### Database Connection Error
- Verify MySQL is running
- Check database name, user, and password in `config.php`
- Ensure `pharmacy_inventory` database exists

### Login Error
- Verify credentials are correct
- Check if user account is active (not deactivated)
- Clear browser cache and cookies
- Check if user has been assigned a role

### Pages Not Loading
- Verify folder permissions (755 for folders, 644 for files)
- Check PHP error logs in XAMPP
- Ensure all required files are in correct locations
- Clear browser cache

### Forecast Not Showing
- Ensure there is at least 7 days of sales history
- Check if products have transactions recorded
- Verify data is in the `sales` table
- Try refreshing the page

## Support

For issues or feature requests, check:
1. Database integrity with phpMyAdmin
2. Recent activity logs
3. Browser console for JavaScript errors
4. Server error logs

## License

© 2026 MangaRingMed Pharmacy. All rights reserved.

---

**Version**: 1.0.0  
**Last Updated**: August 2026  
**Built With**: PHP 7.4+, MySQL 5.7+, Bootstrap 5, Chart.js
