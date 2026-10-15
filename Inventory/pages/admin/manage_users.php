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

// Count total users
$count_result = $conn->query("SELECT COUNT(*) as count FROM users");
$total = $count_result->fetch_assoc()['count'];
$pagination = paginate($total, $records_per_page, $page);
$offset = $pagination['offset'];

// Get users with roles
$users = $conn->query("SELECT u.*, r.role_name FROM users u 
                      JOIN roles r ON u.role_id = r.id 
                      ORDER BY u.created_at DESC 
                      LIMIT $offset, $records_per_page");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - MangaRingMed Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <!-- SIDEBAR -->
    <?php renderSidebar('users'); ?>
    
    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        <!-- TOP NAVBAR -->
        <?php renderTopNavbar(); ?>
        
        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <div class="page-title">
                    <h1><i class="fas fa-users"></i> Manage Users</h1>
                    <p>Create, edit, and manage user accounts</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
            </div>
            
            <!-- USERS TABLE -->
            <div class="card">
                <div class="card-header">
                    <h5 style="margin: 0;"><i class="fas fa-users-cog"></i> User List (<?php echo $total; ?> total)</h5>
                </div>
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="user-avatar" style="display: inline-flex; width: 30px; height: 30px; font-size: 12px; margin-right: 8px;">
                                        <?php echo strtoupper($user['full_name'][0]); ?>
                                    </div>
                                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge" style="background-color: 
                                        <?php 
                                            if ($user['role_name'] === 'admin') echo '#1e7e34';
                                            elseif ($user['role_name'] === 'owner') echo '#2d9b4a';
                                            else echo '#ffc107';
                                        ?>
                                    ">
                                        <?php echo ucfirst(htmlspecialchars($user['role_name'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php elseif ($user['status'] === 'inactive'): ?>
                                        <span class="badge bg-warning">Inactive</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Deactivated</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['last_login']): ?>
                                        <?php echo formatDateTime($user['last_login']); ?>
                                    <?php else: ?>
                                        <small class="text-muted">Never logged in</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" 
                                            data-id="<?php echo $user['id']; ?>"
                                            data-full_name="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>"
                                            data-phone="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES); ?>"
                                            data-role_id="<?php echo $user['role_id']; ?>"
                                            data-status="<?php echo $user['status']; ?>"
                                            onclick="openEditModal(this)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="toggleStatus(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-toggle-off"></i>
                                    </button>
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
    
                <!-- ADD USER MODAL -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Create New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="../../api/create_user.php">
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" class="form-control" name="password" required>
                            <small class="text-muted">Min. 6 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role *</label>
                            <select class="form-select" name="role_id" required>
                                <option value="">-- Select Role --</option>
                                <?php
                                $roles = $conn->query("SELECT * FROM roles WHERE role_name != 'admin'");
                                while ($role = $roles->fetch_assoc()):
                                ?>
                                <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
                <!-- EDIT USER MODAL -->
                <div class="modal fade" id="editUserModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-user-edit"></i> Edit User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="editUserForm" method="POST" action="../../api/update_user.php">
                                    <input type="hidden" name="id" id="edit_user_id">
                                    <div class="mb-3">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" id="edit_email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" name="phone" id="edit_phone">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Role *</label>
                                        <select class="form-select" name="role_id" id="edit_role_id" required>
                                            <option value="">-- Select Role --</option>
                                            <?php
                                            $roles = $conn->query("SELECT * FROM roles WHERE role_name != 'admin'");
                                            while ($role = $roles->fetch_assoc()):
                                            ?>
                                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
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

                <script>
                    function openEditModal(btn) {
                        var id = btn.getAttribute('data-id');
                        document.getElementById('edit_user_id').value = id;
                        document.getElementById('edit_full_name').value = btn.getAttribute('data-full_name') || '';
                        document.getElementById('edit_email').value = btn.getAttribute('data-email') || '';
                        document.getElementById('edit_phone').value = btn.getAttribute('data-phone') || '';
                        document.getElementById('edit_role_id').value = btn.getAttribute('data-role_id') || '';

                        var editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                        editModal.show();
                    }

                    document.addEventListener('DOMContentLoaded', function() {
                        var editForm = document.getElementById('editUserForm');
                        if (editForm) {
                            editForm.addEventListener('submit', function(e) {
                                e.preventDefault();
                                var fd = new FormData(editForm);
                                fetch('../../api/update_user.php', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                                .then(res => res.json())
                                .then(data => {
                                    if (data && data.success) {
                                        // update row
                                        var tr = document.querySelector('tr td strong:contains("' + data.user.full_name + '")');
                                        // simpler: reload page to reflect change
                                        location.reload();
                                    } else {
                                        alert(data.error || 'Failed to update user');
                                    }
                                }).catch(err => { console.error(err); alert('Error updating user'); });
                            });
                        }

                    });

                    function toggleStatus(id, name) {
                        if (!confirm('Toggle status for ' + name + '?')) return;
                        var fd = new FormData(); fd.append('id', id);
                        fetch('../../api/toggle_user_status.php', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.success) {
                                location.reload();
                            } else {
                                alert(data.error || 'Failed to toggle status');
                            }
                        }).catch(err => { console.error(err); alert('Error toggling status'); });
                    }
                </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/script.js"></script>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.querySelector('.main-wrapper').classList.toggle('sidebar-collapsed');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
