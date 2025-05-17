<?php
/**
 * Core functions for the CMS
 */



/**
 * Read JSON file
 *
 * @param string $file
 * @return array|null
 */
function read_json_file($file) {
    if (!file_exists($file)) {
        return null;
    }
    
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    
    return is_array($data) ? $data : null;
}

/**
 * Write JSON file
 *
 * @param string $file
 * @param array $data
 * @return bool
 */
function write_json_file($file, $data) {
    $dir = dirname($file);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $content = json_encode($data, JSON_PRETTY_PRINT);
    return file_put_contents($file, $content) !== false;
}

/**
 * Generate a unique ID
 *
 * @return string
 */
function generate_id() {
    return uniqid() . bin2hex(random_bytes(4));
}

/**
 * Create a slug from a string
 *
 * @param string $string
 * @return string
 */
function create_slug($string) {
    // Replace spaces with hyphens
    $string = str_replace(' ', '-', $string);
    
    // Remove any non-alphanumeric characters except hyphens
    $string = preg_replace('/[^a-z0-9-]/i', '', $string);
    
    // Convert to lowercase
    $string = strtolower($string);
    
    // Remove multiple hyphens
    $string = preg_replace('/-+/', '-', $string);
    
    // Trim hyphens from beginning and end
    $string = trim($string, '-');
    
    // If empty, generate a random slug
    if (empty($string)) {
        $string = 'post-' . substr(md5(time()), 0, 6);
    }
    
    return $string;
}

/**
 * Check if a slug is already used
 *
 * @param string $post_type
 * @param string $slug
 * @param string $exclude_id
 * @return bool
 */
function is_slug_used($post_type, $slug, $exclude_id = '', $lang = null) {
    $posts = get_posts($post_type);
    $lang = $lang ?? DEFAULT_LANGUAGE;
    
    foreach ($posts as $post) {
        if (($post['slug_' . $lang] ?? '') === $slug && $post['id'] !== $exclude_id) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get post preview URL
 *
 * @param string $post_type
 * @param string $post_id
 * @return string
 */
function get_post_preview_url($post_type, $post_id, $lang = null) {
    $post = get_post($post_type, $post_id);
    
    if (!$post) {
        return '#';
    }
    
    // Use current language if not specified
    if ($lang === null) {
        $lang = defined('CURRENT_LANG') ? CURRENT_LANG : DEFAULT_LANGUAGE;
    }
    
    // Get the language-specific slug
    $slug = isset($post['slug_' . $lang]) ? $post['slug_' . $lang] : '';
    
    // Generate URL based on post type with language prefix
    $base_url = get_site_url();
    $base_url = "";
    $lang_prefix = '/' . $lang;
    
    if ($post_type === 'page') {
        return $base_url . $lang_prefix . '/page/' . $slug . '?preview=true';
    } else {
        return $base_url . $lang_prefix . '/' . $post_type . '/' . $slug . '?preview=true';
    }
}

/**
 * Format file size for display
 *
 * @param int $size
 * @return string
 */
function format_file_size($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    
    return round($size, 2) . ' ' . $units[$i];
}

/**
 * Sanitize input data
 *
 * @param string $data
 * @return string
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Convert a string to a URL-friendly slug
 * 
 * @param string $string The string to convert
 * @return string The slug
 */
function sanitize_slug($string) {
    // Convert to lowercase
    $string = strtolower($string);
    
    // Replace non-alphanumeric characters with hyphens
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    
    // Remove leading and trailing hyphens
    $string = trim($string, '-');
    
    // If the string is empty, provide a default
    if (empty($string)) {
        $string = 'term-' . time();
    }
    
    return $string;
}

/**
 * Convert object to array recursively
 *
 * @param object|array $obj
 * @return array
 */
function object_to_array($obj) {
    if (is_object($obj)) {
        $obj = (array) $obj;
    }
    
    if (is_array($obj)) {
        $new = [];
        foreach ($obj as $key => $val) {
            $new[$key] = object_to_array($val);
        }
    } else {
        $new = $obj;
    }
    
    return $new;
}

// Improved versions of these functions are defined at the top of this file

/**
 * Get URL for a static asset
 *
 * @param string $path
 * @return string
 */
function get_asset_url($path) {
    return get_site_url() . '/assets/' . ltrim($path, '/');
}

// This function has been moved to languages.php

/**
 * Get translated content based on current language
 *
 * @param array $post
 * @param string $field
 * @param string $language
 * @return string
 */
function get_translated_content($post, $field, $language = null) {
    // Use current language if not specified
    if ($language === null) {
        $language = defined('CURRENT_LANG') ? CURRENT_LANG : DEFAULT_LANGUAGE;
    }
    
    // Try to get content in specified language
    $field_name = $field . '_' . $language;
    
    if (isset($post[$field_name]) && !empty($post[$field_name])) {
        return $post[$field_name];
    }
    
    // Fall back to default language if translation not available
    $default_field_name = $field . '_' . DEFAULT_LANGUAGE;
    
    if (isset($post[$default_field_name])) {
        return $post[$default_field_name];
    }
    
    // Return empty string if all else fails
    return '';
}

// This function has been moved to languages.php

/**
 * Get current page URL
 *
 * @return string
 */
function get_current_url() {
    return get_site_url() . $_SERVER['REQUEST_URI'];
}

/**
 * Create pagination links
 *
 * @param int $current_page
 * @param int $total_pages
 * @param string $url_pattern
 * @return string
 */
function create_pagination($current_page, $total_pages, $url_pattern = '?page=%d') {
    if ($total_pages <= 1) {
        return '';
    }
    
    $pagination = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($current_page > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . sprintf($url_pattern, $current_page - 1) . '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';
    } else {
        $pagination .= '<li class="page-item disabled"><a class="page-link" href="#" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    // Always show first page
    if ($start > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . sprintf($url_pattern, 1) . '">1</a></li>';
        if ($start > 2) {
            $pagination .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
    }
    
    // Page links
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            $pagination .= '<li class="page-item active"><a class="page-link" href="#">' . $i . '</a></li>';
        } else {
            $pagination .= '<li class="page-item"><a class="page-link" href="' . sprintf($url_pattern, $i) . '">' . $i . '</a></li>';
        }
    }
    
    // Always show last page
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $pagination .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
        $pagination .= '<li class="page-item"><a class="page-link" href="' . sprintf($url_pattern, $total_pages) . '">' . $total_pages . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . sprintf($url_pattern, $current_page + 1) . '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
    } else {
        $pagination .= '<li class="page-item disabled"><a class="page-link" href="#" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
    }
    
    $pagination .= '</ul></nav>';
    
    return $pagination;
}

/**
 * Convert HTML to plain text
 *
 * @param string $html
 * @return string
 */
function html_to_plain_text($html) {
    // Remove HTML tags
    $text = strip_tags($html);
    
    // Replace multiple spaces with single space
    $text = preg_replace('/\s+/', ' ', $text);
    
    // Trim and return
    return trim($text);
}

/**
 * Create excerpt from HTML content
 *
 * @param string $content
 * @param int $length
 * @return string
 */
function create_excerpt($content, $length = 150) {
    $text = html_to_plain_text($content);
    
    if (strlen($text) <= $length) {
        return $text;
    }
    
    // Truncate to nearest word boundary
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    
    return $text . '...';
}

/**
 * Check if current user can perform action
 *
 * @param string $action
 * @return bool
 */
function current_user_can($action) {
    if (!is_logged_in()) {
        return false;
    }
    
    $role = $_SESSION['role'] ?? '';
    
    switch ($action) {
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
 * Log errors to file
 *
 * @param string $message
 * @param string $level
 * @return void
 */
function log_error($message, $level = 'ERROR') {
    $log_file = BASE_PATH . '/error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    
    $log_message = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    error_log($log_message, 3, $log_file);
}

/**
 * Display error message
 *
 * @param string $message
 * @return string
 */
function display_error($message) {
    return '<div class="alert alert-danger" role="alert">' . htmlspecialchars($message) . '</div>';
}

/**
 * Display success message
 *
 * @param string $message
 * @return string
 */
function display_success($message) {
    return '<div class="alert alert-success" role="alert">' . htmlspecialchars($message) . '</div>';
}


/**
 * Get terms for a post
 *
 * @param array $post
 * @param string $taxonomy
 * @return array
 */
function get_post_terms($post, $taxonomy) {
    $terms = [];
    $tax_key = 'tax_' . $taxonomy;
    
    if (isset($post[$tax_key]) && is_array($post[$tax_key])) {
        $all_terms = get_terms($taxonomy);
        $term_ids = $post[$tax_key];
        
        foreach ($all_terms as $term) {
            if (in_array($term['id'], $term_ids)) {
                $terms[] = $term;
            }
        }
    }
    
    return $terms;
}

/**
 * Get term URL
 *
 * @param string $taxonomy
 * @param string $term_slug
 * @return string
 */
function get_term_url($taxonomy, $term_slug) {
    return get_site_url() . '/' . $taxonomy . '/' . $term_slug;
}



/**
 * Load template part
 *
 * @param string $template
 * @param array $data
 * @return void
 */

function load_template_part($template, $data = []) {
    $template_file = TEMPLATES_PATH . '/' . $template . '.php';
    
    if (file_exists($template_file)) {
        extract($data);
        include $template_file;
    } else {
        echo "Template part not found: {$template}";
    }
}


/**
 * Get general settings
 *
 * @return array
 */
function get_general_settings() {
    $settings_file = BASE_PATH . '/storage/general_settings.json';
    $settings = read_json_file($settings_file);
    return is_array($settings) ? $settings : [];
}
