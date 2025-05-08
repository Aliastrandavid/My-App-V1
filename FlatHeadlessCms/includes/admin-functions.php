<?php
/**
 * Admin functionality specific functions
 */

// Include configuration
require_once __DIR__ . '/config.php';

/**
 * Get general settings
 *
 * @return array
 */
function get_general_settings() {
    $settings_file = STORAGE_PATH . '/general_settings.json';
    
    if (file_exists($settings_file)) {
        $settings = read_json_file($settings_file);
        
        if (is_array($settings)) {
            return $settings;
        }
    }
    
    // Default settings if file doesn't exist or is invalid
    $default_settings = [
        'site_title' => 'Flat Headless CMS',
        'site_description' => 'A PHP-based flat headless CMS using JSON files for storage',
        'default_language' => 'en',
        'posts_per_page' => 10
    ];
    
    // Save default settings
    write_json_file($settings_file, $default_settings);
    
    return $default_settings;
}

// Note: get_user_by_id() is defined in includes/users.php

// Note: get_users() is defined in includes/users.php

/**
 * Count all posts across all post types
 *
 * @return int
 */
function count_all_posts() {
    $post_types = get_post_types();
    $count = 0;
    
    foreach ($post_types as $type_key => $type) {
        $count += count_posts(['post_type' => $type_key]);
    }
    
    return $count;
}

/**
 * Count posts for a specific post type
 *
 * @param array $args
 * @return int
 */
function count_posts($args = []) {
    $defaults = [
        'post_type' => 'post',
        'status' => 'publish'
    ];
    
    $args = array_merge($defaults, $args);
    $posts = get_posts($args);
    
    return count($posts);
}

/**
 * Get recent posts across all post types
 *
 * @param int $limit
 * @return array
 */
function get_recent_posts($limit = 5) {
    $post_types = get_post_types();
    $all_posts = [];
    
    foreach ($post_types as $type_key => $type) {
        $posts = get_posts([
            'post_type' => $type_key,
            'per_page' => $limit,
            'sort' => 'date_desc'
        ]);
        
        $all_posts = array_merge($all_posts, $posts);
    }
    
    // Sort by date
    usort($all_posts, function($a, $b) {
        $date_a = isset($a['published_at']) ? strtotime($a['published_at']) : 0;
        $date_b = isset($b['published_at']) ? strtotime($b['published_at']) : 0;
        
        return $date_b - $date_a;
    });
    
    // Limit results
    return array_slice($all_posts, 0, $limit);
}