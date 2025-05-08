<?php
session_start();

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
        // Redirect back to login with error
        header('Location: simple_login.php?error=1');
        exit;
    }
} else {
    // Redirect back to login page if accessed directly
    header('Location: simple_login.php');
    exit;
}
?>