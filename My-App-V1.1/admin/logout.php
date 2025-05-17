<?php
/**
 * Admin logout
 */

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Logout user
logout_user();

// Redirect to login page
header('Location: /admin/login.php');
exit;
?>