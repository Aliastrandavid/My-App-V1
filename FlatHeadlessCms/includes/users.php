<?php
/**
 * Users functions
 */

require_once 'config.php';
require_once 'functions.php';

/**
 * Get all users
 *
 * @return array
 */
function get_all_users() {
    $users_file = STORAGE_PATH . '/users.json';
    
    if (file_exists($users_file)) {
        $users_data = read_json_file($users_file);
        
        if (is_array($users_data) && isset($users_data['users']) && is_array($users_data['users'])) {
            return $users_data['users'];
        }
    }
    
    // Create default admin user if no users exist
    $admin_user = [
        'id' => 1,
        'username' => 'admin',
        'email' => 'admin@example.com',
        'password' => password_hash('admin', PASSWORD_DEFAULT),
        'first_name' => 'Admin',
        'last_name' => 'User',
        'role' => 'admin',
        'status' => 'active',
        'created_at' => date('Y-m-d\TH:i:s\Z'),
        'updated_at' => date('Y-m-d\TH:i:s\Z'),
        'last_login' => null
    ];
    
    $users = [$admin_user];
    write_json_file($users_file, ['users' => $users]);
    
    return $users;
}

/**
 * Get users with filtering and pagination
 *
 * @param array $args
 * @return array
 */
function get_users($args = []) {
    $users = get_all_users();
    
    $defaults = [
        'search' => '',
        'role' => '',
        'sort' => 'username_asc',
        'page' => 1,
        'per_page' => 10
    ];
    
    $args = array_merge($defaults, $args);
    $page = max(1, intval($args['page']));
    $per_page = max(0, intval($args['per_page']));
    
    // Apply filters
    if (!empty($args['search'])) {
        $search = strtolower($args['search']);
        $users = array_filter($users, function($user) use ($search) {
            return strpos(strtolower($user['username']), $search) !== false ||
                   strpos(strtolower($user['name']), $search) !== false ||
                   strpos(strtolower($user['email']), $search) !== false;
        });
    }
    
    if (!empty($args['role'])) {
        $users = array_filter($users, function($user) use ($args) {
            return $user['role'] === $args['role'];
        });
    }
    
    // Apply sorting
    switch ($args['sort']) {
        case 'username_asc':
            usort($users, function($a, $b) {
                return strcmp(strtolower($a['username']), strtolower($b['username']));
            });
            break;
        
        case 'username_desc':
            usort($users, function($a, $b) {
                return strcmp(strtolower($b['username']), strtolower($a['username']));
            });
            break;
        
        case 'name_asc':
            usort($users, function($a, $b) {
                return strcmp(strtolower($a['name']), strtolower($b['name']));
            });
            break;
        
        case 'name_desc':
            usort($users, function($a, $b) {
                return strcmp(strtolower($b['name']), strtolower($a['name']));
            });
            break;
        
        case 'date_asc':
            usort($users, function($a, $b) {
                return strtotime($a['date_created']) - strtotime($b['date_created']);
            });
            break;
        
        case 'date_desc':
            usort($users, function($a, $b) {
                return strtotime($b['date_created']) - strtotime($a['date_created']);
            });
            break;
    }
    
    // Apply pagination
    if ($per_page > 0) {
        $offset = ($page - 1) * $per_page;
        $users = array_slice($users, $offset, $per_page);
    }
    
    return $users;
}

/**
 * Count users
 *
 * @param array $args
 * @return int
 */
function count_users($args = []) {
    $defaults = [
        'search' => '',
        'role' => ''
    ];
    
    $args = array_merge($defaults, $args);
    $args['per_page'] = 0;  // No pagination for counting
    
    $users = get_users($args);
    return count($users);
}

/**
 * Get user by ID
 *
 * @param string $id
 * @return array|null
 */
function get_user_by_id($id) {
    $users = get_all_users();
    
    foreach ($users as $user) {
        if ($user['id'] === $id) {
            return $user;
        }
    }
    
    return null;
}

/**
 * Get user by username
 *
 * @param string $username
 * @return array|null
 */
function get_user_by_username($username) {
    $users = get_all_users();
    
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            return $user;
        }
    }
    
    return null;
}

/**
 * Get user by email
 *
 * @param string $email
 * @return array|null
 */
function get_user_by_email($email) {
    $users = get_all_users();
    
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return $user;
        }
    }
    
    return null;
}

/**
 * Create a new user
 *
 * @param array $user_data
 * @return bool
 */
function create_user($user_data) {
    $users_file = STORAGE_PATH . '/users.json';
    $users = get_all_users();
    
    // Validate required fields
    if (!isset($user_data['username']) || !isset($user_data['password']) || !isset($user_data['email'])) {
        return false;
    }
    
    // Check if username or email already exists
    foreach ($users as $user) {
        if ($user['username'] === $user_data['username']) {
            return false;
        }
        
        if ($user['email'] === $user_data['email']) {
            return false;
        }
    }
    
    // Hash password
    $user_data['password'] = password_hash($user_data['password'], PASSWORD_DEFAULT);
    
    // Set timestamps
    $user_data['created_at'] = date('Y-m-d\TH:i:s\Z');
    $user_data['updated_at'] = date('Y-m-d\TH:i:s\Z');
    
    // Generate ID if not provided
    if (!isset($user_data['id'])) {
        $user_data['id'] = count($users) + 1;
    }
    
    // Add user to array
    $users[] = $user_data;
    
    return write_json_file($users_file, ['users' => $users]);
}

/**
 * Update user
 *
 * @param string $user_id
 * @param array $user_data
 * @return bool
 */
function update_user($user_id, $user_data) {
    $users_file = STORAGE_PATH . '/users.json';
    $users = get_all_users();
    
    // Find user to update
    foreach ($users as $index => $user) {
        if ($user['id'] === $user_id) {
            // Check if email is changed and already exists
            if (isset($user_data['email']) && $user_data['email'] !== $user['email']) {
                foreach ($users as $existing_user) {
                    if ($existing_user['email'] === $user_data['email'] && $existing_user['id'] !== $user_id) {
                        return false;
                    }
                }
            }
            
            // If password is provided, hash it
            if (isset($user_data['password']) && !empty($user_data['password'])) {
                $user_data['password'] = password_hash($user_data['password'], PASSWORD_DEFAULT);
            } else {
                // Keep existing password
                $user_data['password'] = $user['password'];
            }
            
            // Update user
            $user_data['updated_at'] = date('Y-m-d\TH:i:s\Z');
            $users[$index] = array_merge($user, $user_data);
            
            // Write back to file with the correct structure
            $users_data = ['users' => $users];
            return write_json_file($users_file, $users_data);
        }
    }
    
    return false;
}

/**
 * Delete user
 *
 * @param string $user_id
 * @return bool
 */
function delete_user($user_id) {
    $users_file = STORAGE_PATH . '/users.json';
    $users = get_all_users();
    
    // Remove user
    $users = array_filter($users, function($user) use ($user_id) {
        return $user['id'] !== $user_id;
    });
    
    // Reindex array
    $users = array_values($users);
    
    // Write back to file with the correct structure
    return write_json_file($users_file, ['users' => $users]);
}

/**
 * Check if username exists
 *
 * @param string $username
 * @return bool
 */
function username_exists($username) {
    return get_user_by_username($username) !== null;
}

/**
 * Check if email exists
 *
 * @param string $email
 * @return bool
 */
function email_exists($email) {
    return get_user_by_email($email) !== null;
}

/**
 * Get available roles
 *
 * @return array
 */
function get_available_roles() {
    return [
        'admin' => 'Administrator',
        'editor' => 'Editor',
        'author' => 'Author',
        'contributor' => 'Contributor'
    ];
}
