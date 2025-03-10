<?php
// Database connection
$db_name = "mysql:host=localhost;dbname=user_form";
$username = "root";
$password = "";

try {
    $conn = new PDO($db_name, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// RBAC Helper Functions
function has_permission($permission_name) {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Get user's role
    $stmt = $conn->prepare("
        SELECT r.id FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.id = :user_id
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $role = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$role) {
        return false;
    }
    
    // Check if role has the permission
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM role_permissions rp
        JOIN permissions p ON rp.permission_id = p.id
        WHERE rp.role_id = :role_id AND p.permission_name = :permission_name
    ");
    $stmt->bindParam(':role_id', $role['id']);
    $stmt->bindParam(':permission_name', $permission_name);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['count'] > 0;
}

function get_user_role($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT r.role_name FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.id = :user_id
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['role_name'] : null;
}

function authenticate() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function redirect_if_authenticated() {
    if (isset($_SESSION['user_id'])) {
        header("Location: dashboard.php");
        exit();
    }
}

function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_csrf_token() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
        $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
}
?>