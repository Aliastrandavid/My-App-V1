<?php
session_start();

include '../includes/users.php';

function validate_login($username, $password) {
    // Get user by username
    $user = get_user_by_username($username);

    if (!$user) {
        return false;
    }

    // Verify password
    if (password_verify($password, $user['password'])) {
        return true;
    }

    return false;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple validation for testing
    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'admin';
        
        // Redirect to dashboard
        header('Location: dashboard.php');
        exit;
    } else {
        if (validate_login($username, $password)) {
            // Valid login, get user details
            $user = get_user_by_username($username);

            // Set session variables
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Update last login time
            //$user['last_login'] = date('Y-m-d H:i:s');
           // update_user($user['id'], $user);

            // Redirect to dashboard
            header('Location: /admin/dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
        header('Location: simple_login.php?error=1');
        exit;
    }
} else {
    // Redirect back to login page if accessed directly
    header('Location: simple_login.php');
    exit;
}
?>