<?php
/**
 * Configuration file for the CMS
 */

// Define constants
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}");
define('BASE_PATH', rtrim(dirname(__DIR__), '/'));
define('STORAGE_PATH', BASE_PATH . '/storage');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('TEMPLATES_PATH', BASE_PATH . '/templates');
define('ADMIN_PATH', BASE_PATH . '/admin');

// Load language configuration
if (file_exists(STORAGE_PATH . '/lang_config.json')) {
    $lang_config = json_decode(file_get_contents(STORAGE_PATH . '/lang_config.json'), true);
    if (is_array($lang_config) && isset($lang_config['default'])) {
        define('DEFAULT_LANGUAGE', $lang_config['default']);
        define('AVAILABLE_LANGUAGES', $lang_config['languages']);
    } else {
        define('DEFAULT_LANGUAGE', 'en');
        define('AVAILABLE_LANGUAGES', ['en']);
    }
} else {
    define('DEFAULT_LANGUAGE', 'en');
    define('AVAILABLE_LANGUAGES', ['en']);
}

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set('UTC');

// Set session options before session starts
// These must be set in php.ini or before session_start() is called
// For reference only - we don't set them here to avoid warnings
// 
// session.cookie_httponly = 1
// session.use_only_cookies = 1
// session.cookie_secure = when using HTTPS

// File upload settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', [
    'jpg', 'jpeg', 'png', 'gif', 'svg', 
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 
    'ppt', 'pptx', 'txt', 'zip'
]);

// Define image sizes for thumbnails and previews
define('IMAGE_SIZES', [
    'thumbnail' => ['width' => 150, 'height' => 150],
    'medium' => ['width' => 300, 'height' => 300],
    'large' => ['width' => 800, 'height' => 800]
]);

// Set up autoloading if needed

// Initialize storage directory if it doesn't exist
if (!file_exists(STORAGE_PATH)) {
    mkdir(STORAGE_PATH, 0755, true);
}

if (!file_exists(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0755, true);
}

// Note: get_general_settings() is defined in admin-functions.php

// Function to save general settings
function save_general_settings($settings) {
    $settings_file = STORAGE_PATH . '/general_settings.json';
    return file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT)) !== false;
}

// Function to get language settings
function get_language_settings() {
    $settings_file = STORAGE_PATH . '/lang_config.json';
    
    if (file_exists($settings_file)) {
        return json_decode(file_get_contents($settings_file), true) ?: [];
    }
    
    // Default settings
    return [
        'default' => 'en',
        'languages' => ['en', 'fr'],
        'active_languages' => ['en', 'fr'],
        'language_in_url' => true
    ];
}

// Function to save language settings
function save_language_settings($settings) {
    $settings_file = STORAGE_PATH . '/lang_config.json';
    
    // Ensure required keys exist
    if (!isset($settings['default'])) {
        $settings['default'] = 'en';
    }
    
    if (!isset($settings['languages'])) {
        $settings['languages'] = ['en'];
    }
    
    if (!isset($settings['active_languages'])) {
        $settings['active_languages'] = ['en'];
    }
    
    return file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT)) !== false;
}

// Function to get sitemap settings
function get_sitemap_settings() {
    $settings_file = STORAGE_PATH . '/sitemap_settings.json';
    
    if (file_exists($settings_file)) {
        return json_decode(file_get_contents($settings_file), true) ?: [];
    }
    
    // Default settings
    return [
        'include_blog' => true,
        'include_pages' => true,
        'include_taxonomies' => true,
        'update_frequency' => 'weekly'
    ];
}

// Function to save sitemap settings
function save_sitemap_settings($settings) {
    $settings_file = STORAGE_PATH . '/sitemap_settings.json';
    return file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT)) !== false;
}

// Function to get advanced settings
function get_advanced_settings() {
    $settings_file = STORAGE_PATH . '/advanced_settings.json';
    
    if (file_exists($settings_file)) {
        return json_decode(file_get_contents($settings_file), true) ?: [];
    }
    
    // Default settings
    return [
        'media_max_size' => 5, // MB
        'media_allowed_types' => ALLOWED_FILE_TYPES,
        'cache_enabled' => false,
        'cache_duration' => 3600 // seconds
    ];
}

// Function to save advanced settings
function save_advanced_settings($settings) {
    $settings_file = STORAGE_PATH . '/advanced_settings.json';
    return file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT)) !== false;
}

// Get the current site URL
function get_site_url() {
    // Get custom domain from settings if it exists
    $settings = get_general_settings();
    if (isset($settings['site_url']) && !empty($settings['site_url'])) {
        return rtrim($settings['site_url'], '/');
    }
    
    return SITE_URL;
}

// Generate sitemap
function generate_sitemap() {
    $sitemap_file = BASE_PATH . '/sitemap.xml';
    $sitemap_json = STORAGE_PATH . '/sitemap.json';
    
    // Get sitemap settings
    $settings = get_sitemap_settings();
    
    // Get all published posts
    $sitemap_data = [
        'generated' => date('Y-m-d H:i:s'),
        'urls' => []
    ];
    
    // Add homepage
    $sitemap_data['urls'][] = [
        'loc' => get_site_url() . '/',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'daily',
        'priority' => '1.0'
    ];
    
    // Include blog posts
    if ($settings['include_blog']) {
        // Get all post types
        $post_types = get_post_types();
        
        foreach ($post_types as $post_type_key => $post_type) {
            // Get all published posts of this type
            $posts = get_posts($post_type_key, ['status' => 'published']);
            
            foreach ($posts as $post) {
                // Add each post URL
                $sitemap_data['urls'][] = [
                    'loc' => get_site_url() . '/' . $post_type_key . '/' . $post['slug'],
                    'lastmod' => date('Y-m-d', strtotime($post['date'])),
                    'changefreq' => $settings['update_frequency'],
                    'priority' => '0.8'
                ];
            }
        }
    }
    
    // Include static pages
    if ($settings['include_pages']) {
        $pages = get_static_pages();
        
        foreach ($pages as $page) {
            // Only include published pages
            if ($page['status'] === 'published') {
                $sitemap_data['urls'][] = [
                    'loc' => get_site_url() . '/' . $page['slug'],
                    'lastmod' => date('Y-m-d'),
                    'changefreq' => $settings['update_frequency'],
                    'priority' => '0.7'
                ];
            }
        }
    }
    
    // Include taxonomy archives
    if ($settings['include_taxonomies']) {
        $taxonomies = get_all_taxonomies();
        
        foreach ($taxonomies as $tax_key => $taxonomy) {
            $terms = get_terms($tax_key);
            
            foreach ($terms as $term) {
                $sitemap_data['urls'][] = [
                    'loc' => get_site_url() . '/' . $tax_key . '/' . $term['slug'],
                    'lastmod' => date('Y-m-d'),
                    'changefreq' => $settings['update_frequency'],
                    'priority' => '0.6'
                ];
            }
        }
    }
    
    // Save sitemap data to JSON file
    file_put_contents($sitemap_json, json_encode($sitemap_data, JSON_PRETTY_PRINT));
    
    // Generate XML sitemap
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
    
    foreach ($sitemap_data['urls'] as $url) {
        $xml .= '  <url>' . PHP_EOL;
        $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . PHP_EOL;
        $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . PHP_EOL;
        $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . PHP_EOL;
        $xml .= '    <priority>' . $url['priority'] . '</priority>' . PHP_EOL;
        $xml .= '  </url>' . PHP_EOL;
    }
    
    $xml .= '</urlset>';
    
    // Save XML sitemap
    if (file_put_contents($sitemap_file, $xml) !== false) {
        return true;
    } else {
        return false;
    }
}
