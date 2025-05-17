<?php
/**
 * Static page template
 */

// Get page data if not already set in index.php
if (!isset($static_page)) {
    $static_page = get_static_page_by_slug($slug, CURRENT_LANG);
}

// 404 if page not found or not published
if (!$static_page || $static_page['status'] !== 'published') {
    header("HTTP/1.0 404 Not Found");
    echo "<h1>404 - Page Not Found</h1>";
    echo "<p>The page you are looking for does not exist.</p>";
    echo "<p><a href='" . get_site_url() . "'>Go to homepage</a></p>";
    exit;
}

// Get site settings
$site_title = get_general_settings()['site_title'] ?? 'Flat Headless CMS';

// Get page title and content in current language or default
if (!isset($title)) {
    $title_field = 'title_' . CURRENT_LANG;
    $title = isset($static_page[$title_field]) && !empty($static_page[$title_field]) 
        ? $static_page[$title_field] 
        : $static_page['title_' . DEFAULT_LANGUAGE];
}

if (!isset($content)) {
    $content_field = 'content_' . CURRENT_LANG;
    $content = isset($static_page[$content_field]) && !empty($static_page[$content_field]) 
        ? $static_page[$content_field] 
        : $static_page['content_' . DEFAULT_LANGUAGE];
}

// Handle template if specified and not already loaded
$page_content = $content; // Default to using the content directly

if (isset($static_page['template']) && strpos($static_page['template'], '.html') !== false) {
    $template_file = TEMPLATES_PATH . '/' . $static_page['template'];
    if (file_exists($template_file)) {
        // Load HTML template and replace placeholders
        $template_content = file_get_contents($template_file);
        
        // Replace placeholders in template
        $template_content = str_replace('{{title}}', $title, $template_content);
        $template_content = str_replace('{{content}}', $content, $template_content);
        
        $page_content = $template_content;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo CURRENT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - <?php echo htmlspecialchars($site_title); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo get_site_url(); ?>/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="<?php echo get_site_url(); ?>"><?php echo htmlspecialchars($site_title); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php echo generate_navigation_menu(CURRENT_LANG); ?>
                <div class="ms-auto">
                    <?php echo create_language_switcher('dropdown'); ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h1 class="fw-bolder mb-4"><?php echo htmlspecialchars($title); ?></h1>
                
                <div class="page-content">
                    <?php echo $page_content; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <p class="text-center text-muted mb-0">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_title); ?>
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>