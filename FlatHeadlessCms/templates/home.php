<?php
/**
 * Home template
 */

// Include required files
require_once __DIR__ . '/../includes/admin-functions.php';

// Get page data
$home_page = get_static_page_by_slug('home');
$site_title = get_general_settings()['site_title'] ?? 'Flat Headless CMS';
$site_description = get_general_settings()['site_description'] ?? '';

// Get latest blog posts
$latest_posts = get_posts('blog', [
    'status' => 'published',
    'per_page' => 5,
    'sort' => 'date_desc'
]);

// Set page title
$page_title = isset($home_page['meta_title_' . CURRENT_LANG]) 
    ? $home_page['meta_title_' . CURRENT_LANG] 
    : ($home_page['meta_title_' . DEFAULT_LANGUAGE] ?? $site_title);

// Set page description
$page_description = isset($home_page['meta_description_' . CURRENT_LANG])
    ? $home_page['meta_description_' . CURRENT_LANG]
    : ($home_page['meta_description_' . DEFAULT_LANGUAGE] ?? $site_description);
?>
<!DOCTYPE html>
<html lang="<?php echo CURRENT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    
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

    <!-- Hero Section -->
    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1><?php echo isset($home_page['name_' . CURRENT_LANG]) ? htmlspecialchars($home_page['name_' . CURRENT_LANG]) : 'Welcome'; ?></h1>
                <p class="lead"><?php echo htmlspecialchars($site_description); ?></p>
            </div>
        </div>
    </div>

    <!-- Latest Posts Section -->
    <?php if (!empty($latest_posts)): ?>
    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h2 class="mb-4">Latest Posts</h2>
                
                <?php foreach ($latest_posts as $post): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">
                            <?php 
                            $title_field = 'title_' . CURRENT_LANG;
                            $title = isset($post[$title_field]) && !empty($post[$title_field]) 
                                ? $post[$title_field] 
                                : $post['title_' . DEFAULT_LANGUAGE];
                            ?>
                            <a href="<?php echo get_site_url() . '/blog/' . $post['slug']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($title); ?>
                            </a>
                        </h3>
                        <p class="card-text">
                            <?php 
                            $excerpt_field = 'excerpt_' . CURRENT_LANG;
                            $excerpt = isset($post[$excerpt_field]) && !empty($post[$excerpt_field]) 
                                ? $post[$excerpt_field] 
                                : (isset($post['excerpt_' . DEFAULT_LANGUAGE]) ? $post['excerpt_' . DEFAULT_LANGUAGE] : '');
                            
                            echo htmlspecialchars($excerpt);
                            ?>
                        </p>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted"><?php echo date('F j, Y', strtotime($post['date'])); ?></small>
                            <a href="<?php echo get_site_url() . '/blog/' . $post['slug']; ?>" class="btn btn-sm btn-primary">Read More</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

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