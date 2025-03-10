<?php 
include 'header.php';
redirect_if_authenticated();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'CSRF token validation failed';
    } else {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $full_name = trim($_POST['full_name']);
        
        // Basic validation
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'Please fill out all required fields';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $error = 'Username or email already exists';
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Get the default role (user role)
                $stmt = $conn->prepare("SELECT id FROM roles WHERE role_name = 'user'");
                $stmt->execute();
                $role = $stmt->fetch(PDO::FETCH_ASSOC);
                $role_id = $role ? $role['id'] : 3; // Default to 3 (user) if not found
                
                // Insert the new user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role_id) VALUES (:username, :email, :password, :full_name, :role_id)");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':role_id', $role_id);
                
                if ($stmt->execute()) {
                    $success = 'Registration successful! You can now login.';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-tabs">
            <div class="auth-tab" data-form="login" onclick="window.location.href='login.php'">Login</div>
            <div class="auth-tab active" data-form="register">Register</div>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="register.php" id="register-form">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                <label for="username">Username</label>
            </div>
            
            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                <label for="email">Email</label>
            </div>
            
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Full Name">
                <label for="full_name">Full Name</label>
            </div>
            
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
                <small class="text-muted">Minimum 8 characters</small>
            </div>
            
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                <label for="confirm_password">Confirm Password</label>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Register</button>
            </div>
        </form>
        
        <div class="mt-4 text-center">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>