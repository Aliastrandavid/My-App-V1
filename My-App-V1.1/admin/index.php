<?php
/**
 * Admin Panel Entry Point
 */

// Check if user is already logged in
session_start();
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Redirect to dashboard
    header('Location: dashboard.php');
    exit;
} else {
    // Redirect to login page
    header('Location: simple_login.php');
    exit;
}
?>