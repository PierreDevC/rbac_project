<?php 
include 'header.php';
authenticate();

// Check if user has permission to manage users
if (!has_permission('manage_users')) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'CSRF token validation failed';
    } else {
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        
        // Change user role
        if ($_POST['action'] === 'change_role' && isset($_POST['role_id'])) {
            $role_id = (int)$_POST['role_id'];
            
            // Prevent changing own role
            if ($user_id === (int)$_SESSION['user_id']) {
                $error = 'You cannot change your own role';
            } else {
                $stmt = $conn->prepare("UPDATE users SET role_id = :role_id WHERE id = :user_id");
                $stmt->bindParam(':role_id', $role_id);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $success = 'User role updated successfully';
                } else {
                    $error = 'Failed to update user role';
                }
            }
        }
        
        // Delete user
        if ($_POST['action'] === 'delete') {
            // Prevent deleting own account
            if ($user_id === (int)$_SESSION['user_id']) {
                $error = 'You cannot delete your own account';
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = :user_id");
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $success = 'User deleted successfully';
                } else {
                    $error = 'Failed to delete user';
                }
            }
        }
    }
}

// Get all roles for dropdown
$stmt = $conn->prepare("SELECT id, role_name FROM roles ORDER BY id");
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all users
$stmt = $conn->prepare("
    SELECT u.*, r.role_name 
    FROM users u
    JOIN roles r ON u.role_id = r.id
    ORDER BY u.id
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="dashboard-container">
        <h1 class="mb-4">Manage Users</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">User List</h5>
                <a href="dashboard.php" class="btn btn-outline-primary btn-sm">Back to Dashboard</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name'] ?: '-'); ?></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($user['role_name']); ?></span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['id'] !== (int)$_SESSION['user_id']): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changeRoleModal<?php echo $user['id']; ?>">
                                                    Change Role
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?php echo $user['id']; ?>">
                                                    Delete User
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <!-- Change Role Modal -->
                                    <div class="modal fade" id="changeRoleModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Change Role for <?php echo htmlspecialchars($user['username']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="manage_users.php">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                        <input type="hidden" name="action" value="change_role">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label for="role_id" class="form-label">Select Role</label>
                                                            <select class="form-select" id="role_id" name="role_id">
                                                                <?php foreach ($roles as $role): ?>
                                                                    <option value="<?php echo $role['id']; ?>" <?php echo ($role['id'] === $user['role_id']) ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete User Modal -->
                                    <div class="modal fade" id="deleteUserModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Delete User</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to delete user <strong><?php echo htmlspecialchars($user['username']); ?></strong>?</p>
                                                    <p class="text-danger">This action cannot be undone!</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <form method="POST" action="manage_users.php">
                                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-danger">Delete User</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                        <span class="text-muted">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>