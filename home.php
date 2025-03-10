<?php include 'header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto text-center">
            <h1 class="display-4 mb-4">Welcome to AuthSystem</h1>
            <p class="lead mb-5">A secure authentication system with Role Based Access Control</p>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="d-flex justify-content-center gap-3">
                    <a href="login.php" class="btn btn-primary btn-lg">Login</a>
                    <a href="register.php" class="btn btn-outline-primary btn-lg">Register</a>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <h4>You are logged in as <?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                    <p>Your role: <?php echo htmlspecialchars(get_user_role($_SESSION['user_id'])); ?></p>
                    <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-shield-alt fa-3x mb-3 text-primary"></i>
                    <h3>Secure Authentication</h3>
                    <p>Password hashing and secure session management to protect your account.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-users-cog fa-3x mb-3 text-primary"></i>
                    <h3>Role-Based Access</h3>
                    <p>Different permission levels for different user roles within the system.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-user-edit fa-3x mb-3 text-primary"></i>
                    <h3>Profile Management</h3>
                    <p>Customize your profile with personal information and profile picture.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>