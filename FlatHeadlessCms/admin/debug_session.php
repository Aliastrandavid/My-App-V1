<?php
/**
 * Debug session and redirects
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Set content type to plain text for easier reading
header('Content-Type: text/plain');

// Print session data
echo "SESSION DATA:\n";
print_r($_SESSION);
echo "\n\n";

// Check if logged in
echo "IS LOGGED IN: " . (is_logged_in() ? 'YES' : 'NO') . "\n\n";

// Print server info
echo "SERVER DATA:\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";

// End
echo "\n\nEND DEBUG";
?>