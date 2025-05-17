<?php
/**
 * Admin login page
 */

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/users.php';
require_once '../includes/admin-functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if already logged in, redirect to dashboard
if (is_logged_in()) {
    header('Location: /admin/dashboard.php');
    exit;
}

// Initialize variables
$error = '';
$username = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Check credentials
        if (validate_login($username, $password)) {
            // Valid login, get user details
            $user = get_user_by_username($username);
            
            // Set session variables
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Update last login time
            $user['last_login'] = date('Y-m-d H:i:s');
            update_user($user['id'], $user);
            
            // Redirect to dashboard
            header('Location: /admin/dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$site_title = get_general_settings()['site_title'] ?? 'Flat Headless CMS';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($site_title); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom styles -->
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body class="bg-light">
    <div class="login-container">
        <div class="login-form">
            <h1 class="h3 mb-3 fw-normal text-center"><?php echo htmlspecialchars($site_title); ?></h1>
            <h2 class="h5 mb-3 fw-normal text-center">Admin Login</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="/admin/login.php">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username); ?>" required>
                    <label for="username">Username</label>
                </div>
                
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                </div>
                
                <button class="w-100 btn btn-lg btn-primary" type="submit">Log In</button>
                
                <p class="mt-3 text-center">
                    <a href="/" class="text-decoration-none">← Back to Website</a>
                </p>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>