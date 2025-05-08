<?php
/**
 * Single post template
 */

// Get post data from slug
$post_data = null;
$post_type = isset($path_parts[0]) ? $path_parts[0] : 'blog';

// Get all posts of this type
$posts = get_posts($post_type);

// Find the post by slug
foreach ($posts as $post) {
    if ($post['slug'] === $slug) {
        $post_data = $post;
        break;
    }
}

// 404 if post not found or not published
if (!$post_data || $post_data['status'] !== 'published') {
    header("HTTP/1.0 404 Not Found");
    echo "<h1>404 - Post Not Found</h1>";
    echo "<p>The post you are looking for does not exist or is not published.</p>";
    echo "<p><a href='" . get_site_url() . "'>Go to homepage</a></p>";
    exit;
}

// Get site settings
$site_title = get_general_settings()['site_title'] ?? 'Flat Headless CMS';

// Get post title in current language or default
$title_field = 'title_' . CURRENT_LANG;
$post_title = isset($post_data[$title_field]) && !empty($post_data[$title_field]) 
    ? $post_data[$title_field] 
    : $post_data['title_' . DEFAULT_LANGUAGE];

// Get post content in current language or default
$content_field = 'content_' . CURRENT_LANG;
$post_content = isset($post_data[$content_field]) && !empty($post_data[$content_field]) 
    ? $post_data[$content_field] 
    : $post_data['content_' . DEFAULT_LANGUAGE];

// Get post meta data in current language or default
$meta_title_field = 'meta_title_' . CURRENT_LANG;
$meta_title = isset($post_data[$meta_title_field]) && !empty($post_data[$meta_title_field]) 
    ? $post_data[$meta_title_field] 
    : ($post_data['meta_title_' . DEFAULT_LANGUAGE] ?? $post_title);

$meta_description_field = 'meta_description_' . CURRENT_LANG;
$meta_description = isset($post_data[$meta_description_field]) && !empty($post_data[$meta_description_field]) 
    ? $post_data[$meta_description_field] 
    : ($post_data['meta_description_' . DEFAULT_LANGUAGE] ?? '');

// Get featured image
$featured_image = isset($post_data['featured_image']) ? $post_data['featured_image'] : '';

// Get post date
$post_date = isset($post_data['date']) ? date('F j, Y', strtotime($post_data['date'])) : '';

// Get post author
$author = null;
if (isset($post_data['author_id'])) {
    $author = get_user_by_id($post_data['author_id']);
}

// Get categories and tags
$categories = isset($post_data['tax_category']) ? $post_data['tax_category'] : [];
$tags = isset($post_data['tax_tag']) ? $post_data['tax_tag'] : [];
?>
<!DOCTYPE html>
<html lang="<?php echo CURRENT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($meta_title); ?> - <?php echo htmlspecialchars($site_title); ?></title>
    <?php if (!empty($meta_description)): ?>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <?php endif; ?>
    
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

    <!-- Article -->
    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <article>
                    <header class="mb-4">
                        <h1 class="fw-bolder mb-3"><?php echo htmlspecialchars($post_title); ?></h1>
                        
                        <!-- Post metadata -->
                        <div class="text-muted mb-3">
                            <?php if (!empty($post_date)): ?>
                            <span class="me-3"><i class="bi bi-calendar"></i> <?php echo $post_date; ?></span>
                            <?php endif; ?>
                            
                            <?php if ($author): ?>
                            <span class="me-3"><i class="bi bi-person"></i> <?php echo htmlspecialchars($author['name']); ?></span>
                            <?php endif; ?>
                            
                            <?php if (!empty($categories)): ?>
                            <span class="me-3">
                                <i class="bi bi-folder"></i> 
                                <?php 
                                $category_links = [];
                                foreach ($categories as $cat_id) {
                                    $term = get_term('category', $cat_id);
                                    if ($term) {
                                        $term_name_field = 'name_' . CURRENT_LANG;
                                        $term_name = isset($term[$term_name_field]) && !empty($term[$term_name_field]) 
                                            ? $term[$term_name_field] 
                                            : $term['name_' . DEFAULT_LANGUAGE];
                                        $term_url = get_site_url() . '/category/' . $term['slug'];
                                        $category_links[] = '<a href="' . $term_url . '">' . htmlspecialchars($term_name) . '</a>';
                                    }
                                }
                                echo implode(', ', $category_links);
                                ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </header>
                    
                    <!-- Featured image -->
                    <?php if (!empty($featured_image)): ?>
                    <figure class="mb-4">
                        <img class="img-fluid rounded" src="<?php echo get_site_url() . '/uploads/' . $featured_image; ?>" alt="<?php echo htmlspecialchars($post_title); ?>">
                    </figure>
                    <?php endif; ?>
                    
                    <!-- Post content -->
                    <div class="mb-5">
                        <?php echo $post_content; ?>
                    </div>
                    
                    <!-- Tags -->
                    <?php if (!empty($tags)): ?>
                    <div class="mb-4">
                        <i class="bi bi-tags"></i> 
                        <?php 
                        $tag_links = [];
                        foreach ($tags as $tag_id) {
                            $term = get_term('tag', $tag_id);
                            if ($term) {
                                $term_name_field = 'name_' . CURRENT_LANG;
                                $term_name = isset($term[$term_name_field]) && !empty($term[$term_name_field]) 
                                    ? $term[$term_name_field] 
                                    : $term['name_' . DEFAULT_LANGUAGE];
                                $term_url = get_site_url() . '/tag/' . $term['slug'];
                                $tag_links[] = '<a href="' . $term_url . '" class="badge bg-secondary text-decoration-none link-light">' . htmlspecialchars($term_name) . '</a>';
                            }
                        }
                        echo implode(' ', $tag_links);
                        ?>
                    </div>
                    <?php endif; ?>
                </article>
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