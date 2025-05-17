<?php
/**
 * Admin functionality specific functions
 */

// Include configuration
require_once __DIR__ . '/config.php';

// Note: get_general_settings() is defined in includes/functions.php

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