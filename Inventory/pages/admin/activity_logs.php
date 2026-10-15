<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/navbar.php';

if (!Auth::hasRole('admin')) {
    header('Location: ../../login.php?error=Unauthorized access');
    exit();
}

$logs = $conn->query("SELECT l.*, u.full_name FROM activity_logs l JOIN users u ON l.user_id = u.id ORDER BY l.timestamp DESC LIMIT 200");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Activity Logs - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php renderSidebar('activity_logs'); ?>
    <div class="main-wrapper">
        <?php renderTopNavbar(); ?>
        <div class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-history"></i> Activity Logs</h1>
            </div>

            <div class="card">
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Table</th>
                                <th>Record ID</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $logs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['action']); ?></td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td><?php echo htmlspecialchars($row['table_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['record_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
