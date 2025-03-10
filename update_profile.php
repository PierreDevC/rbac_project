<?php 
include 'header.php';
authenticate();

// Check if user has permission to edit profile
if (!has_permission('edit_profile')) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'CSRF token validation failed';
    } else {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Check if email changed and if it's already taken
        if ($email !== $user['email']) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND id != :user_id");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $error = 'Email already exists';
            }
        }
        
        // If no error and current password is provided, verify it
        if (empty($error) && !empty($current_password)) {
            if (!password_verify($current_password, $user['password'])) {
                $error = 'Current password is incorrect';
            } elseif (!empty($new_password)) {
                // Password change is requested
                if (strlen($new_password) < 8) {
                    $error = 'New password must be at least 8 characters long';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match';
                }
            }
        }
        
        // Handle profile picture upload
        $profile_picture = $user['profile_picture']; // Default to current
        
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $file_type = finfo_file($file_info, $_FILES['profile_picture']['tmp_name']);
            finfo_close($file_info);
            
            if (!in_array($file_type, $allowed_types)) {
                $error = 'Only JPG, PNG, and GIF files are allowed';
            } else {
                $upload_dir = 'uploads/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['profile_picture']['name']);
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                    $profile_picture = $file_name;
                } else {
                    $error = 'Failed to upload profile picture';
                }
            }
        }
        
        // Update user information if no errors
        if (empty($error)) {
            try {
                $conn->beginTransaction();
                
                // Update profile information
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET full_name = :full_name, 
                        email = :email,
                        profile_picture = :profile_picture
                    WHERE id = :user_id
                ");
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':profile_picture', $profile_picture);
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->execute();
                
                // Update password if requested
                if (!empty($new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :user_id");
                    $stmt->bindParam(':password', $hashed_password);
                    $stmt->bindParam(':user_id', $_SESSION['user_id']);
                    $stmt->execute();
                }
                
                $conn->commit();
                $success = 'Profile updated successfully';
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $conn->rollBack();
                $error = 'Profile update failed: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="container">
    <div class="dashboard-container">
        <h1 class="mb-4">Edit Profile</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" class="profile-picture mx-auto d-block mb-3" alt="Profile Picture">
                        <h5><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars(get_user_role($_SESSION['user_id'])); ?></p>
                        <a href="dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="update_profile.php" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                <div class="form-text">Username cannot be changed.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">Profile Picture</label>
                                <input type="file" class="form-control" id="profile_picture" name="profile_picture">
                                <div class="form-text">Allowed formats: JPG, PNG, GIF</div>
                            </div>
                            
                            <h5 class="mt-4 mb-3">Change Password</h5>
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                                <div class="form-text">Leave blank if you don't want to change your password.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>