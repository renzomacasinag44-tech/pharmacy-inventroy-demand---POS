# Quick Setup Guide - MangaRingMed Pharmacy Inventory System

## 🚀 Quick Start (5 Minutes)

### Prerequisites
- XAMPP installed and running
- Apache and MySQL services active
- Browser (Chrome, Firefox, Safari, or Edge)

### Step 1: Import Database (1 minute)
1. Open **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Click on **SQL** tab at the top
3. Copy all content from `database_schema.sql` file
4. Paste into the SQL editor
5. Click **Go** button to execute
6. Wait for "Success" message

### Step 2: Access the System (1 minute)
1. Open browser and go to: `http://localhost/Inventory/`
2. You'll be redirected to login page
3. System is ready!

### Step 3: Login with Demo Account (1 minute)
Choose any of these accounts:

**👨‍💼 System Admin** (Full Access)
- Email: `admin@mangaringmed.com`
- Password: `admin123`

**👔 Owner** (Inventory Manager)
- Email: `owner@mangaringmed.com`
- Password: `admin123`

**👷 Staff** (Basic Operations)
- Email: `staff@mangaringmed.com`
- Password: `admin123`

### Step 4: Explore Features (2 minutes)
- Click on dashboard to view overview
- Check sidebar for available features
- Change password in profile section
- Browse sample products and categories

---

## 📊 Feature Overview

### For System Admin
✅ Manage users and assign roles
✅ Add and edit products, categories, suppliers
✅ View all inventory transactions
✅ Monitor system activities
✅ Access demand forecasts
✅ Full system control

### For Owner/Head
✅ Manage products and inventory
✅ Record stock in/out
✅ View demand forecasting dashboard
✅ Get reorder recommendations
✅ Analyze fast/slow-moving products
✅ Monitor sales trends

### For Staff
✅ Record stock movements
✅ View product information
✅ Get low stock alerts
✅ View demand forecasts (read-only)
✅ Update personal profile

---

## 🎨 UI Design Features

- **Green Pharmacy Theme**: MangaRingMed colors (#1e7e34, #2d9b4a)
- **Responsive Design**: Works on desktop, tablet, mobile
- **Bootstrap 5**: Modern, accessible components
- **Interactive Charts**: Sales trends, demand forecasts
- **Smart Navigation**: Sidebar with role-based menus
- **Data Validation**: Real-time form validation

---

## 📈 Demand Forecasting

The system uses two popular algorithms:

### 1. Moving Average
- Averages last 7 days of sales
- Good for stable demand products
- Smooths out daily fluctuations

### 2. Linear Regression
- Finds trend line in sales data
- Best for growing/declining products
- Predicts 7-30 days ahead

**Example:**
- If product sells 100 units/day on average
- Suggested reorder: 100 × 30 = 3000 units
- Recommended date: Today + 3 days (lead time)

---

## 📝 Common Tasks

### Add a Product
1. Login as **Admin**
2. Go to **Manage Products**
3. Click **Add Product** button
4. Fill in details (name, SKU, price, category, supplier)
5. Click **Save**
6. Product appears in catalog

### Record Stock In
1. Login as **Staff** or **Owner**
2. Go to **Record Stock In**
3. Select product
4. Enter quantity received
5. Add reference number (optional)
6. Click **Record**

### Record Stock Out (Sale)
1. Go to **Record Stock Out**
2. Select product
3. Enter quantity sold
4. Set transaction type as "Sale"
5. Enter customer name and price
6. Click **Record**

### View Demand Forecast
1. Login as **Owner** or **Staff**
2. Go to **Demand Forecast**
3. See 7-day predictions for products
4. Identify fast-moving and slow-moving items
5. Get automated reorder recommendations

---

## 🔒 Security Notes

- Passwords are encrypted with Bcrypt
- Session expires after 30 minutes of inactivity
- All activities are logged with user info and timestamp
- SQL injection protection via prepared statements
- User roles control access to all features

---

## 🛠️ Database Structure

**Key Tables:**
- `users` - User accounts (admin, owner, staff)
- `products` - Product catalog
- `categories` - Product categories
- `suppliers` - Supplier information
- `inventory_transactions` - Stock in/out history
- `sales` - Sales records
- `demand_forecasts` - Forecast data
- `activity_logs` - User action audit trail

---

## 📌 Tips & Best Practices

1. **Set Proper Stock Levels**
   - Min Stock: When to reorder
   - Max Stock: Storage capacity limit

2. **Record Transactions Immediately**
   - Ensures accurate forecasts
   - Prevents stock discrepancies

3. **Review Forecasts Weekly**
   - Check trending products
   - Adjust purchasing strategy

4. **Monitor Low Stock Alerts**
   - Proactively prevents stockouts
   - Helps maintain service levels

5. **Backup Your Data**
   - Export important reports
   - Maintain backup database copies

---

## 🆘 Troubleshooting

**Can't access system?**
- Check if XAMPP Apache and MySQL are running
- Verify URL: `http://localhost/Inventory/`
- Check files exist in `xampp/htdocs/Inventory/`

**Database error on login?**
- Open phpMyAdmin
- Check if `pharmacy_inventory` database exists
- Re-import database_schema.sql if needed

**Forecast not showing?**
- Need at least 7 days of sales history
- Check if sales records exist in database
- Forecasts update after data is recorded

**Password issues?**
- Click "Change Password" in Profile
- Old password must match current
- New password must be 6+ characters

---

## 📞 Support

For detailed documentation, see **README.md**

Key sections:
- Installation Guide
- Feature Breakdown
- Database Schema
- API Endpoints
- Forecasting Algorithms

---

**Version**: 1.0.0  
**Last Updated**: August 2026  
**System**: MangaRingMed Pharmacy Inventory Management
