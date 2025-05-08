<?php
/**
 * Main entry point for the flat headless CMS
 * 
 * This file handles routing for the frontend and API requests
 */

// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/languages.php';
require_once 'includes/static-pages.php';
require_once 'includes/post-types.php';
require_once 'includes/taxonomy.php';
require_once 'includes/users.php';
require_once 'includes/media.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the request URI and parse it
$request_uri = $_SERVER['REQUEST_URI'];
$request_path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($request_path, '/'));

// Check if this is an API request
if (isset($path_parts[0]) && $path_parts[0] === 'api') {
    // Route API requests
    include 'api/index.php';
    exit;
}

// Check if this is an admin request
if (isset($path_parts[0]) && $path_parts[0] === 'admin') {
    // Include admin file based on the path
    $admin_path = implode('/', array_slice($path_parts, 1));
    
    if (empty($admin_path)) {
        // Base admin URL - include index
        include 'admin/index.php';
    } else {
        // Check if the file exists
        $admin_file = 'admin/' . $admin_path . '.php';
        if (file_exists($admin_file)) {
            include $admin_file;
        } else {
            // 404 page
            header("HTTP/1.0 404 Not Found");
            echo "<h1>404 - Admin Page Not Found</h1>";
            echo "<p>The admin page you are looking for does not exist.</p>";
            echo "<p><a href='/admin'>Go to admin dashboard</a></p>";
        }
    }
    exit;
}

// Detect language from URL
$current_lang = detect_language_from_url($path_parts);
define('CURRENT_LANG', $current_lang);

// Remove language segment from URL if present
if (in_array($path_parts[0], get_available_languages())) {
    array_shift($path_parts);
}

// Default page is home
$page = 'home';
$slug = '';
$is_post = false;

// Determine what to load
if (!empty($path_parts[0])) {
    if ($path_parts[0] === 'blog' && isset($path_parts[1])) {
        // Blog post
        $page = 'post';
        $slug = $path_parts[1];
        $is_post = true;
    } else {
        // Static page
        $slug = $path_parts[0];
        $static_page = get_static_page_by_slug($slug, CURRENT_LANG);
        
        if ($static_page) {
            $page = $static_page['id'];
        }
    }
}

// Load appropriate template
if ($is_post) {
    include 'templates/post.php';
} else {
    // First check if a PHP template exists
    $template_file = 'templates/' . $page . '.php';
    
    if (file_exists($template_file)) {
        include $template_file;
    } else {
        // Then check if the static page has a template
        $static_page = get_static_page_by_slug($slug, CURRENT_LANG) ?: get_static_page('home');
        
        if ($static_page && isset($static_page['template'])) {
            $html_template = 'templates/' . $static_page['template'];
            
            if (file_exists($html_template)) {
                // Extract page data to local variables
                $title = $static_page['title_' . CURRENT_LANG] ?? $static_page['title_en'];
                $content = $static_page['content_' . CURRENT_LANG] ?? $static_page['content_en'];
                
                include 'templates/page.php';
            } else {
                // 404 page
                header("HTTP/1.0 404 Not Found");
                echo "<h1>404 - Page Not Found</h1>";
                echo "<p>The template file for this page does not exist.</p>";
                echo "<p><a href='/'>Go to homepage</a></p>";
            }
        } else {
            // 404 page
            header("HTTP/1.0 404 Not Found");
            echo "<h1>404 - Page Not Found</h1>";
            echo "<p>The page you are looking for does not exist.</p>";
            echo "<p><a href='/'>Go to homepage</a></p>";
        }
    }
}
