<?php
/**
 * Static pages functions
 */

require_once 'config.php';
require_once 'functions.php';

/**
 * Get all static pages
 *
 * @return array
 */
function get_static_pages() {
    $pages_file = STORAGE_PATH . '/index_static_pages.json';
    
    if (file_exists($pages_file)) {
        $pages = read_json_file($pages_file);
        
        if (is_array($pages) && isset($pages['pages'])) {
            return $pages['pages'];
        }
    }
    
    // Default pages if file doesn't exist or is invalid
    $default_pages = [
        [
            'id' => 'home',
            'title_en' => 'Home',
            'title_fr' => 'Accueil',
            'slug_en' => 'home',
            'slug_fr' => 'accueil',
            'content_en' => '<h1>Welcome to Our Website</h1><p>This is a flat headless CMS built with PHP and JSON storage.</p>',
            'content_fr' => '<h1>Bienvenue sur notre site Web</h1><p>Ceci est un CMS headless à plat construit avec PHP et stockage JSON.</p>',
            'template' => 'home.html',
            'status' => 'published',
            'created_at' => date('Y-m-d\TH:i:s\Z'),
            'updated_at' => date('Y-m-d\TH:i:s\Z')
        ],
        [
            'id' => 'about',
            'title_en' => 'About Us',
            'title_fr' => 'À propos de nous',
            'slug_en' => 'about',
            'slug_fr' => 'a-propos',
            'content_en' => '<h1>About Our Company</h1><p>Welcome to our company page.</p>',
            'content_fr' => '<h1>À propos de notre entreprise</h1><p>Bienvenue sur la page de notre entreprise.</p>',
            'template' => 'about.html',
            'status' => 'published',
            'created_at' => date('Y-m-d\TH:i:s\Z'),
            'updated_at' => date('Y-m-d\TH:i:s\Z')
        ],
        [
            'id' => 'blog',
            'title_en' => 'Blog',
            'title_fr' => 'Blog',
            'slug_en' => 'blog',
            'slug_fr' => 'blog',
            'content_en' => '<h1>Our Blog</h1><p>Stay updated with our latest news and insights.</p>',
            'content_fr' => '<h1>Notre Blog</h1><p>Restez à jour avec nos dernières nouvelles et insights.</p>',
            'template' => 'blog.html',
            'status' => 'published',
            'created_at' => date('Y-m-d\TH:i:s\Z'),
            'updated_at' => date('Y-m-d\TH:i:s\Z')
        ],
        [
            'id' => 'contact',
            'title_en' => 'Contact Us',
            'title_fr' => 'Contactez-nous',
            'slug_en' => 'contact',
            'slug_fr' => 'contact',
            'content_en' => '<h1>Contact Us</h1><p>We\'d love to hear from you!</p>',
            'content_fr' => '<h1>Contactez-nous</h1><p>Nous aimerions avoir de vos nouvelles !</p>',
            'template' => 'contact.html',
            'status' => 'published',
            'created_at' => date('Y-m-d\TH:i:s\Z'),
            'updated_at' => date('Y-m-d\TH:i:s\Z')
        ]
    ];
    
    // Save default pages
    write_json_file($pages_file, ['pages' => $default_pages]);
    
    return $default_pages;
}

/**
 * Get static pages with filtering and pagination
 *
 * @param array $args
 * @return array
 */
function get_static_pages_filtered($args = []) {
    $pages = get_static_pages();
    
    $defaults = [
        'search' => '',
        'status' => '',
        'sort' => 'name_asc',
        'page' => 1,
        'per_page' => 10
    ];
    
    $args = array_merge($defaults, $args);
    $page = max(1, intval($args['page']));
    $per_page = max(0, intval($args['per_page']));
    
    // Apply filters
    if (!empty($args['search'])) {
        $search = strtolower($args['search']);
        $pages = array_filter($pages, function($page) use ($search) {
            return strpos(strtolower($page['name_en']), $search) !== false ||
                   strpos(strtolower($page['name_fr'] ?? ''), $search) !== false ||
                   strpos(strtolower($page['slug']), $search) !== false;
        });
    }
    
    if (!empty($args['status'])) {
        $pages = array_filter($pages, function($page) use ($args) {
            return $page['status'] === $args['status'];
        });
    }
    
    // Apply sorting
    switch ($args['sort']) {
        case 'name_asc':
            usort($pages, function($a, $b) {
                return strcmp(strtolower($a['name_en']), strtolower($b['name_en']));
            });
            break;
        
        case 'name_desc':
            usort($pages, function($a, $b) {
                return strcmp(strtolower($b['name_en']), strtolower($a['name_en']));
            });
            break;
        
        case 'date_asc':
            usort($pages, function($a, $b) {
                return strtotime($a['date_modified']) - strtotime($b['date_modified']);
            });
            break;
        
        case 'date_desc':
            usort($pages, function($a, $b) {
                return strtotime($b['date_modified']) - strtotime($a['date_modified']);
            });
            break;
        
        case 'slug_asc':
            usort($pages, function($a, $b) {
                return strcmp(strtolower($a['slug']), strtolower($b['slug']));
            });
            break;
        
        case 'status_asc':
            usort($pages, function($a, $b) {
                return strcmp(strtolower($a['status']), strtolower($b['status']));
            });
            break;
    }
    
    // Apply pagination
    if ($per_page > 0) {
        $offset = ($page - 1) * $per_page;
        $pages = array_slice($pages, $offset, $per_page);
    }
    
    return $pages;
}

/**
 * Count static pages
 *
 * @param array $args
 * @return int
 */
function count_static_pages($args = []) {
    $defaults = [
        'search' => '',
        'status' => ''
    ];
    
    $args = array_merge($defaults, $args);
    $args['per_page'] = 0;  // No pagination for counting
    
    $pages = get_static_pages_filtered($args);
    return count($pages);
}

/**
 * Get a static page by ID
 *
 * @param string $id
 * @return array|null
 */
function get_static_page($id) {
    $pages = get_static_pages();
    
    foreach ($pages as $page) {
        if ($page['id'] === $id) {
            return $page;
        }
    }
    
    return null;
}

/**
 * Get a static page by slug
 *
 * @param string $slug
 * @param string $language
 * @return array|null
 */
function get_static_page_by_slug($slug, $language = null) {
    if ($language === null) {
        $language = defined('CURRENT_LANG') ? CURRENT_LANG : DEFAULT_LANGUAGE;
    }
    
    $pages = get_static_pages();
    $slug_field = 'slug_' . $language;
    
    foreach ($pages as $page) {
        if (isset($page[$slug_field]) && $page[$slug_field] === $slug) {
            return $page;
        }
        
        // Fallback to English slug if language slug not found
        if (!isset($page[$slug_field]) && $page['slug_en'] === $slug) {
            return $page;
        }
    }
    
    return null;
}

/**
 * Save a static page
 *
 * @param array $page_data
 * @return bool
 */
function save_static_page($page_data) {
    $pages_file = STORAGE_PATH . '/index_static_pages.json';
    $pages = get_static_pages();
    
    // Check if page already exists
    $is_new = true;
    $page_id = $page_data['id'];
    
    foreach ($pages as $index => $page) {
        if ($page['id'] === $page_id) {
            // Update existing page
            $pages[$index] = $page_data;
            $is_new = false;
            break;
        }
    }
    
    // Add new page
    if ($is_new) {
        // Add date_created if not set
        if (!isset($page_data['date_created'])) {
            $page_data['date_created'] = date('Y-m-d H:i:s');
        }
        
        $pages[] = $page_data;
    }
    
    // Update date_modified
    $page_data['date_modified'] = date('Y-m-d H:i:s');
    
    return write_json_file($pages_file, $pages);
}

/**
 * Delete a static page
 *
 * @param string $id
 * @return bool
 */
function delete_static_page($id) {
    $pages_file = STORAGE_PATH . '/index_static_pages.json';
    $pages = get_static_pages();
    
    // Find page to delete
    $found = false;
    foreach ($pages as $index => $page) {
        if ($page['id'] === $id) {
            unset($pages[$index]);
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        return false;
    }
    
    return write_json_file($pages_file, array_values($pages));
}

/**
 * Check if a static page slug is already used
 *
 * @param string $slug
 * @param string $exclude_id
 * @return bool
 */
function is_static_page_slug_used($slug, $exclude_id = '') {
    $pages = get_static_pages();
    
    foreach ($pages as $page) {
        if ($page['slug'] === $slug && $page['id'] !== $exclude_id) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get all published static pages for menu
 *
 * @return array
 */
function get_menu_pages() {
    $pages = get_static_pages();
    
    return array_filter($pages, function($page) {
        return $page['status'] === 'published';
    });
}

/**
 * Generate navigation menu
 *
 * @param string $language
 * @return string
 */
function generate_navigation_menu($language = null) {
    // Use current language if not specified
    if ($language === null) {
        $language = defined('CURRENT_LANG') ? CURRENT_LANG : DEFAULT_LANGUAGE;
    }
    
    $pages = get_menu_pages();
    $output = '';
    
    if (!empty($pages)) {
        $output .= '<ul class="navbar-nav me-auto mb-2 mb-lg-0">';
        
        foreach ($pages as $page) {
            $title_field = 'title_' . $language;
            $slug_field = 'slug_' . $language;
            
            // Get title in current language or fall back to default
            $title = isset($page[$title_field]) && !empty($page[$title_field]) 
                ? $page[$title_field] 
                : $page['title_' . DEFAULT_LANGUAGE];
            
            // Get slug in current language or fall back to default
            $slug = isset($page[$slug_field]) && !empty($page[$slug_field]) 
                ? $page[$slug_field] 
                : $page['slug_' . DEFAULT_LANGUAGE];
            
            // Build URL with language prefix if needed
            $url = get_site_url();
            if (defined('CURRENT_LANG') && CURRENT_LANG !== DEFAULT_LANGUAGE) {
                $url .= '/' . CURRENT_LANG;
            }
            $url .= '/' . $slug;
            
            $active = '';
            
            // Check if this is the current page
            if (isset($_SERVER['REQUEST_URI'])) {
                $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                $current_path = trim($current_path, '/');
                
                // Remove language from path if present
                $path_parts = explode('/', $current_path);
                if (count($path_parts) > 0 && in_array($path_parts[0], get_available_languages())) {
                    array_shift($path_parts);
                    $current_path = implode('/', $path_parts);
                }
                
                if ($current_path === $slug || ($current_path === '' && $slug === 'home')) {
                    $active = ' active';
                }
            }
            
            $output .= '<li class="nav-item">';
            $output .= '<a class="nav-link' . $active . '" href="' . $url . '">' . htmlspecialchars($title) . '</a>';
            $output .= '</li>';
        }
        
        $output .= '</ul>';
    }
    
    return $output;
}
