<?php
/**
 * Post Types Functions
 * 
 * Functions to manage post types in the CMS
 */

/**
 * Get all post types
 * 
 * @return array
 */
function get_post_types() {
    $file = __DIR__ . '/../storage/post_types.json';
    $post_types = read_json_file($file);
    
    return is_array($post_types) ? $post_types : [];
}

/**
 * Get a single post type by slug
 * 
 * @param string $slug
 * @return array|null
 */
function get_post_type($slug) {
    $post_types = get_post_types();
    
    return isset($post_types[$slug]) ? $post_types[$slug] : null;
}

/**
 * Create a new post type
 * 
 * @param string $slug
 * @param array $data
 * @return bool
 */
function create_post_type($slug, $data) {
    $post_types = get_post_types();
    
    // Check if post type already exists
    if (isset($post_types[$slug])) {
        return false;
    }
    
    // Add new post type
    $post_types[$slug] = $data;
    
    // Save to file
    $file = __DIR__ . '/../storage/post_types.json';
    return write_json_file($file, $post_types);
}

/**
 * Update a post type
 * 
 * @param string $slug
 * @param array $data
 * @return bool
 */
function update_post_type($slug, $data) {
    $post_types = get_post_types();
    
    // Check if post type exists
    if (!isset($post_types[$slug])) {
        return false;
    }
    
    // Update post type
    $post_types[$slug] = $data;
    
    // Save to file
    $file = __DIR__ . '/../storage/post_types.json';
    return write_json_file($file, $post_types);
}

/**
 * Delete a post type
 * 
 * @param string $slug
 * @return bool
 */
function delete_post_type($slug) {
    $post_types = get_post_types();
    
    // Check if post type exists
    if (!isset($post_types[$slug])) {
        return false;
    }
    
    // Remove post type
    unset($post_types[$slug]);
    
    // Save to file
    $file = __DIR__ . '/../storage/post_types.json';
    return write_json_file($file, $post_types);
}

/**
 * Get post count by post type
 * 
 * @param string $post_type
 * @return int
 */
function get_post_count($post_type) {
    $file = __DIR__ . '/../storage/posts.json';
    $posts = read_json_file($file);
    
    if (!is_array($posts) || !isset($posts[$post_type])) {
        return 0;
    }
    
    return count($posts[$post_type]);
}

/**
 * Get posts by post type
 * 
 * @param string $post_type
 * @return array
 */
function get_posts($post_type) {
    $file = __DIR__ . '/../storage/'.$post_type.'.json';
    $posts = read_json_file($file);

    if (!is_array($posts) || !isset($posts)) {
        return [];
    }
    
    return $posts['posts'];
}

/**
 * Get a single post
 * 
 * @param string $post_type
 * @param string $post_id
 * @return array|null
 */
function get_post($post_type, $post_id) {
    $posts = get_posts($post_type);
    
    foreach ($posts as $post) {
        if ($post['id'] === $post_id) {
            return $post;
        }
    }
    
    return null;
}

/**
 * Get taxonomies assigned to a post type
 * 
 * @param string $post_type
 * @return array
 */
function get_post_type_taxonomies($post_type) {
    $post_type_data = get_post_type($post_type);
    
    if (!$post_type_data || !isset($post_type_data['taxonomies'])) {
        return [];
    }
    
    return $post_type_data['taxonomies'];
}

/**
 * Check if post type supports a specific feature
 * 
 * @param string $post_type
 * @param string $feature
 * @return bool
 */
function post_type_supports($post_type, $feature) {
    $post_type_data = get_post_type($post_type);
    
    if (!$post_type_data || !isset($post_type_data['supports'])) {
        return false;
    }
    
    return in_array($feature, $post_type_data['supports']);
}

/**
 * Get custom fields for a post type
 * 
 * @param string $post_type
 * @return array
 */
function get_post_type_custom_fields($post_type) {
    $post_type_data = get_post_type($post_type);
    
    if (!$post_type_data || !isset($post_type_data['custom_fields'])) {
        return [];
    }
    
    return $post_type_data['custom_fields'];
}

/**
 * Save a post type (create or update)
 * 
 * @param string $slug
 * @param array $data
 * @return bool
 */
function save_post_type($slug, $data) {
    $post_types = get_post_types();
    
    if (isset($post_types[$slug])) {
        // Update existing post type
        return update_post_type($slug, $data);
    } else {
        // Create new post type
        return create_post_type($slug, $data);
    }
}