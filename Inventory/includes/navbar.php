<?php
// Sidebar Navigation Component
function renderSidebar($currentPage = '') {
    global $conn;
    $role = $_SESSION['role'] ?? '';
    
    ?>
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-pills"></i>
            <div>
                <h5>MangaringMed Pharmacy</h5>
                <p style="margin: 0; font-size: 11px; opacity: 0.8;">Pharmacy</p>
            </div>
        </div>
        
        <div class="sidebar-nav">
            <?php if ($role === 'admin'): ?>
                <!-- ADMIN NAVIGATION -->
                <div class="nav-section-title">Main</div>
                
                <div class="nav-item <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>">
                    <a href="dashboard.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                
                <div class="nav-section-title">Management</div>
                
                <div class="nav-item <?php echo ($currentPage === 'users') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/admin/manage_users.php">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </div>
                
                <div class="nav-item <?php echo ($currentPage === 'products') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/admin/manage_products.php">
                        <i class="fas fa-box"></i>
                        <span>Manage Products</span>
                    </a>
                </div>
                
                <div class="nav-item <?php echo ($currentPage === 'categories') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/admin/manage_categories.php">
                        <i class="fas fa-layer-group"></i>
                        <span>Categories</span>
                    </a>
                </div>
                
                <div class="nav-item <?php echo ($currentPage === 'suppliers') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/admin/manage_suppliers.php">
                        <i class="fas fa-truck"></i>
                        <span>Suppliers</span>
                    </a>
                </div>
                
                <div class="nav-section-title">Monitoring</div>
                
                <div class="nav-item <?php echo ($currentPage === 'sales') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/admin/sales_records.php">
                        <i class="fas fa-receipt"></i>
                        <span>Sales Records</span>
                    </a>
                </div>
                
                <div class="nav-item <?php echo ($currentPage === 'forecast') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/admin/demand_forecast.php">
                        <i class="fas fa-chart-area"></i>
                        <span>Demand Forecast</span>
                    </a>
                </div>
                
                <div class="nav-item <?php echo ($currentPage === 'activity_logs') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/admin/activity_logs.php">
                        <i class="fas fa-history"></i>
                        <span>Activity Logs</span>
                    </a>
                </div>
                
                <div class="nav-section-title">Settings</div>
                
                <div class="nav-item <?php echo ($currentPage === 'settings') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/admin/system_settings.php">
                        <i class="fas fa-cog"></i>
                        <span>System Settings</span>
                    </a>
                </div>
                
                <div class="nav-item <?php echo ($currentPage === 'backup') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/admin/backup_restore.php">
                        <i class="fas fa-database"></i>
                        <span>Backup & Restore</span>
                    </a>
                </div>
                
            <?php elseif ($role === 'owner'): ?>
                <!-- OWNER NAVIGATION -->
                <div class="nav-section-title">Main</div>
                
                <div class="nav-item <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>">
                    <a href="dashboard.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <div class="nav-item <?php echo ($currentPage === 'pos') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/owner/pos.php">
                        <i class="fas fa-cash-register"></i>
                        <span>Point of Sale</span>
                    </a>
                </div>
                
                <div class="nav-section-title">Management</div>
                
                <div class="nav-item <?php echo ($currentPage === 'products') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/owner/manage_products.php">
                        <i class="fas fa-box"></i>
                        <span>Products</span>
                    </a>
                </div>
                
                <div class="nav-item <?php echo ($currentPage === 'categories') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/owner/manage_categories.php">
                        <i class="fas fa-layer-group"></i>
                        <span>Categories</span>
                    </a>
                </div>
                
                <div class="nav-item <?php echo ($currentPage === 'suppliers') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/owner/manage_suppliers.php">
                        <i class="fas fa-truck"></i>
                        <span>Suppliers</span>
                    </a>
                </div>
                
                <div class="nav-section-title">Operations</div>
                
                <div class="nav-item <?php echo ($currentPage === 'inventory') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/owner/monitor_inventory.php">
                        <i class="fas fa-barcode"></i>
                        <span>Monitor Inventory</span>
                    </a>
                </div>
                
                <div class="nav-item <?php echo ($currentPage === 'stock_in_out') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/owner/record_stock.php">
                        <i class="fas fa-plus-minus"></i>
                        <span>Stock In/Out</span>
                    </a>
                </div>

                <div class="nav-item <?php echo ($currentPage === 'settings') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/admin/system_settings.php">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </div>
                
                <div class="nav-section-title">Analytics</div>
                
                <div class="nav-item <?php echo ($currentPage === 'forecast') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/owner/demand_forecast.php">
                        <i class="fas fa-chart-area"></i>
                        <span>Demand Forecast</span>
                    </a>
                </div>
                
                <div class="nav-item <?php echo ($currentPage === 'sales_reports') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/owner/sales_reports.php">
                        <i class="fas fa-chart-bar"></i>
                        <span>Sales Reports</span>
                    </a>
                </div>
                
                <div class="nav-item <?php echo ($currentPage === 'inventory_reports') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/owner/inventory_reports.php">
                        <i class="fas fa-receipt"></i>
                        <span>Inventory Reports</span>
                    </a>
                </div>
                
            <?php elseif ($role === 'staff'): ?>
                <!-- STAFF NAVIGATION -->
                <div class="nav-section-title">Main</div>
                
                <div class="nav-item <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>">
                    <a href="dashboard.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <div class="nav-item <?php echo ($currentPage === 'pos_staff') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/staff/pos.php">
                        <i class="fas fa-cash-register"></i>
                        <span>Point of Sale</span>
                    </a>
                </div>
                
                <div class="nav-section-title">Operations</div>
                
                <div class="nav-item <?php echo ($currentPage === 'products') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/staff/view_products.php">
                        <i class="fas fa-box"></i>
                        <span>View Products</span>
                    </a>
                </div>
                
                <div class="nav-item <?php echo ($currentPage === 'stock_in') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/staff/record_stock_in.php">
                        <i class="fas fa-arrow-up"></i>
                        <span>Record Stock In</span>
                    </a>
                </div>
                
                <div class="nav-item <?php echo ($currentPage === 'stock_out') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/staff/record_stock_out.php">
                        <i class="fas fa-arrow-down"></i>
                        <span>Record Stock Out</span>
                    </a>
                </div>
                
                <div class="nav-section-title">Monitoring</div>
                
                <div class="nav-item <?php echo ($currentPage === 'forecast') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>pages/staff/view_forecast.php">
                        <i class="fas fa-chart-area"></i>
                        <span>Demand Forecast</span>
                    </a>
                </div>
                
            <?php endif; ?>

            <div class="nav-section-title">Account</div>
            
            <div class="nav-item <?php echo ($currentPage === 'profile') ? 'active' : ''; ?>">
                <a href="<?php echo APP_URL; ?>pages/profile.php">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="<?php echo APP_URL; ?>logout.php" onclick="return confirm('Are you sure you want to logout?');">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
    <?php
}

// Top Navbar Component
function renderTopNavbar() {
    global $conn;
    $role = $_SESSION['role'] ?? '';
    
    ?>
    <div class="top-navbar">
        <div class="navbar-left">
            <button class="toggle-sidebar-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search products, users...">
            </div>
        </div>
        
        <div class="navbar-right">
            
            <!-- NOTIFICATION ICON IN NAVBAR -->
            <div class="navbar-item notification-wrapper">
                <button class="navbar-link" onclick="toggleNotificationPanel(event)" style="position: relative;">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                
                <!-- NOTIFICATION DROPDOWN PANEL -->
                <div class="notification-panel" id="notificationPanel">
                    <div class="notification-panel-header">
                        <h6>Notifications</h6>
                        <button class="close-btn" onclick="closeNotificationPanel()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="notification-panel-body">
                        <div class="notification-card">
                            <div class="notification-icon">
                                <i class="fas fa-box" style="color: #ffc107;"></i>
                            </div>
                            <div class="notification-details">
                                <h6>Low Stock Alert</h6>
                                <p>Aspirin 80mg has low stock</p>
                                <span class="notification-time">5 minutes ago</span>
                            </div>
                        </div>
                        <div class="notification-card">
                            <div class="notification-icon">
                                <i class="fas fa-user" style="color: #17a2b8;"></i>
                            </div>
                            <div class="notification-details">
                                <h6>New User</h6>
                                <p>New staff member has been added</p>
                                <span class="notification-time">1 hour ago</span>
                            </div>
                        </div>
                        <div class="notification-card">
                            <div class="notification-icon">
                                <i class="fas fa-cube" style="color: #28a745;"></i>
                            </div>
                            <div class="notification-details">
                                <h6>Stock In Complete</h6>
                                <p>50 units of Amoxicillin received</p>
                                <span class="notification-time">2 hours ago</span>
                            </div>
                        </div>
                    </div>
                    <div class="notification-panel-footer">
                        <button type="button" class="notification-view-all" onclick="openNotificationModal()">View All Notifications</button>
                    </div>
                </div>
            </div>
            
            <?php if ($role === 'admin' || $role === 'owner'): ?>
                <div class="navbar-item">
                    <a href="<?php echo APP_URL; ?>pages/admin/system_settings.php" class="navbar-link">
                        <i class="fas fa-cog"></i>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="user-profile" onclick="document.getElementById('userMenu').classList.toggle('show')">
                <div class="user-avatar"><?php echo strtoupper($_SESSION['name'][0] ?? 'U'); ?></div>
                <div class="user-info">
                    <div class="name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></div>
                    <div class="role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Role'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- NOTIFICATION MODAL -->
    <div class="notification-modal" id="notificationModal">
        <div class="notification-modal-content">
            <div class="notification-modal-header">
                <h5>Notifications</h5>
                <button type="button" class="close-btn" onclick="closeNotificationModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="notification-modal-body">
                <div class="notification-card">
                    <div class="notification-icon">
                        <i class="fas fa-box" style="color: #ffc107;"></i>
                    </div>
                    <div class="notification-details">
                        <h6>Low Stock Alert</h6>
                        <p>Aspirin 80mg has low stock</p>
                        <span class="notification-time">5 minutes ago</span>
                    </div>
                </div>
                <div class="notification-card">
                    <div class="notification-icon">
                        <i class="fas fa-user" style="color: #17a2b8;"></i>
                    </div>
                    <div class="notification-details">
                        <h6>New User</h6>
                        <p>New staff member has been added</p>
                        <span class="notification-time">1 hour ago</span>
                    </div>
                </div>
                <div class="notification-card">
                    <div class="notification-icon">
                        <i class="fas fa-cube" style="color: #28a745;"></i>
                    </div>
                    <div class="notification-details">
                        <h6>Stock In Complete</h6>
                        <p>50 units of Amoxicillin received</p>
                        <span class="notification-time">2 hours ago</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
