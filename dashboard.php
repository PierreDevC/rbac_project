<?php 
include 'header.php';
authenticate();

// Check if user has permission to view dashboard
if (!has_permission('view_dashboard')) {
    header('Location: home.php');
    exit();
}

// Get user information
$stmt = $conn->prepare("
    SELECT u.*, r.role_name 
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = :user_id
");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user permissions
$stmt = $conn->prepare("
    SELECT p.permission_name 
    FROM permissions p
    JOIN role_permissions rp ON p.id = rp.permission_id
    WHERE rp.role_id = :role_id
");
$stmt->bindParam(':role_id', $user['role_id']);
$stmt->execute();
$permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container">
    <div class="dashboard-container">
        <div class="profile-header">
            <img src="uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" class="profile-picture" alt="Profile Picture">
            <div>
                <h1 class="mb-1">Welcome, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h1>
                <p class="text-muted mb-0">
                    <span class="badge bg-primary"><?php echo htmlspecialchars($user['role_name']); ?></span>
                    <span class="ms-2"><?php echo htmlspecialchars($user['email']); ?></span>
                </p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Account Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role_name']); ?></p>
                        <p><strong>Joined:</strong> <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                        <a href="update_profile.php" class="btn btn-primary">Edit Profile</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Your Permissions</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php foreach ($permissions as $permission): ?>
                                <li class="list-group-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $permission))); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <?php if (has_permission('manage_users')): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Admin Panel</h5>
                    </div>
                    <div class="card-body">
                        <p>As an administrator, you have access to additional features:</p>
                        <div class="d-grid gap-2">
                            <a href="manage_users.php" class="btn btn-outline-primary">Manage Users</a>
                            <?php if (has_permission('manage_roles')): ?>
                                <a href="manage_roles.php" class="btn btn-outline-primary">Manage Roles</a>
                            <?php endif; ?>
                            <?php if (has_permission('manage_permissions')): ?>
                                <a href="manage_permissions.php" class="btn btn-outline-primary">Manage Permissions</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>