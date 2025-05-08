<?php
/**
 * Authentication and user management functions
 */

require_once 'config.php';
require_once 'functions.php';
require_once 'users.php';

/**
 * Check if user is logged in
 *
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if user is admin
 *
 * @return bool
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Validate login credentials
 *
 * @param string $username
 * @param string $password
 * @return bool
 */
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

/**
 * Logout the current user
 *
 * @return void
 */
function logout_user() {
    // Unset all session variables
    $_SESSION = array();
    
    // If it's desired to kill the session, also delete the session cookie.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finally, destroy the session
    session_destroy();
}

/**
 * Change user password
 *
 * @param string $user_id
 * @param string $old_password
 * @param string $new_password
 * @return bool|string
 */
function change_password($user_id, $old_password, $new_password) {
    // Get user
    $user = get_user_by_id($user_id);
    
    if (!$user) {
        return 'User not found';
    }
    
    // Verify old password
    if (!password_verify($old_password, $user['password'])) {
        return 'Current password is incorrect';
    }
    
    // Validate new password
    if (strlen($new_password) < 8) {
        return 'New password must be at least 8 characters long';
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update user
    $user['password'] = $hashed_password;
    
    if (update_user($user_id, $user)) {
        return true;
    }
    
    return 'Failed to update password';
}

/**
 * Reset user password
 *
 * @param string $user_id
 * @param string $new_password
 * @return bool
 */
function reset_password($user_id, $new_password) {
    // Get user
    $user = get_user_by_id($user_id);
    
    if (!$user) {
        return false;
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update user
    $user['password'] = $hashed_password;
    
    return update_user($user_id, $user);
}

/**
 * Check if user has permission
 *
 * @param string $user_id
 * @param string $permission
 * @return bool
 */
function user_has_permission($user_id, $permission) {
    $user = get_user_by_id($user_id);
    
    if (!$user) {
        return false;
    }
    
    $role = $user['role'];
    
    switch ($permission) {
        case 'manage_options':
            return $role === 'admin';
        
        case 'edit_posts':
            return in_array($role, ['admin', 'editor', 'author']);
        
        case 'publish_posts':
            return in_array($role, ['admin', 'editor', 'author']);
        
        case 'edit_others_posts':
            return in_array($role, ['admin', 'editor']);
        
        case 'delete_posts':
            return in_array($role, ['admin', 'editor']);
        
        case 'upload_files':
            return in_array($role, ['admin', 'editor', 'author', 'contributor']);
        
        default:
            return false;
    }
}

/**
 * Check if user can edit a post
 *
 * @param string $user_id
 * @param array $post
 * @return bool
 */
function user_can_edit_post($user_id, $post) {
    $user = get_user_by_id($user_id);
    
    if (!$user) {
        return false;
    }
    
    // Admins and editors can edit any post
    if (in_array($user['role'], ['admin', 'editor'])) {
        return true;
    }
    
    // Authors can only edit their own posts
    if ($user['role'] === 'author') {
        return isset($post['author_id']) && $post['author_id'] === $user_id;
    }
    
    return false;
}

/**
 * Get permissions for a role
 *
 * @param string $role
 * @return array
 */
function get_role_permissions($role) {
    $permissions = [];
    
    switch ($role) {
        case 'admin':
            $permissions = [
                'manage_options' => true,
                'edit_posts' => true,
                'publish_posts' => true,
                'edit_others_posts' => true,
                'delete_posts' => true,
                'upload_files' => true
            ];
            break;
        
        case 'editor':
            $permissions = [
                'edit_posts' => true,
                'publish_posts' => true,
                'edit_others_posts' => true,
                'delete_posts' => true,
                'upload_files' => true
            ];
            break;
        
        case 'author':
            $permissions = [
                'edit_posts' => true,
                'publish_posts' => true,
                'upload_files' => true
            ];
            break;
        
        case 'contributor':
            $permissions = [
                'edit_posts' => true,
                'upload_files' => true
            ];
            break;
        
        default:
            // No permissions for unknown roles
            break;
    }
    
    return $permissions;
}

/**
 * Restrict page access based on user role
 *
 * @param array $allowed_roles
 * @return bool
 */
function restrict_access($allowed_roles = ['admin']) {
    if (!is_logged_in()) {
        // Redirect to login page
        header('Location: /admin/login.php');
        exit;
    }
    
    $user_role = $_SESSION['role'] ?? '';
    
    if (!in_array($user_role, $allowed_roles)) {
        // Redirect to dashboard with error
        header('Location: /admin/dashboard.php?error=access_denied');
        exit;
    }
    
    return true;
}
