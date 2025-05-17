<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: simple_login.php');
    exit;
}

// Include necessary files
require_once '../includes/functions.php';
require_once '../includes/post-types.php';
require_once '../includes/taxonomy.php';
require_once '../includes/languages.php';

if (!defined('DEFAULT_LANGUAGE')) {
    define('DEFAULT_LANGUAGE', 'en');
}

$username = $_SESSION['username'] ?? 'User';

// Initialize variables
$post_type = [];
$slug = '';
$is_new = true;
$page_title = 'Add New Post Type';
$success_message = '';
$error_message = '';

// Get post type if editing
if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    $slug = $_GET['slug'];
    $post_types = get_post_types();

    if (isset($post_types[$slug])) {
        $post_type = $post_types[$slug];
        $is_new = false;
        $page_title = 'Edit Post Type: ' . htmlspecialchars($post_type['name_' . DEFAULT_LANGUAGE] ?? '');
    } else {
        $error_message = 'Post type not found.';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_post_type = [
        'name' => $_POST['name'] ?? '',
        'description' => $_POST['description'] ?? '',
        'slug' => $_POST['slug'] ?? '',
        'icon' => $_POST['icon'] ?? 'bi-file-text',
        'supports' => [
            'title' => isset($_POST['support_title']) && $_POST['support_title'] === 'on',
            'editor' => isset($_POST['support_editor']) && $_POST['support_editor'] === 'on',
            'excerpt' => isset($_POST['support_excerpt']) && $_POST['support_excerpt'] === 'on',
            'thumbnail' => isset($_POST['support_thumbnail']) && $_POST['support_thumbnail'] === 'on',
            'custom_fields' => isset($_POST['support_custom_fields']) && $_POST['support_custom_fields'] === 'on'
        ],
        'taxonomies' => isset($_POST['taxonomies']) ? $_POST['taxonomies'] : [],
        'public' => isset($_POST['public']) && $_POST['public'] === 'on',
        'has_archive' => isset($_POST['has_archive']) && $_POST['has_archive'] === 'on',
        'labels' => [
            'singular' => $_POST['label_singular'] ?? '',
            'plural' => $_POST['label_plural'] ?? '',
            'add_new' => $_POST['label_add_new'] ?? '',
            'edit' => $_POST['label_edit'] ?? '',
            'all_items' => $_POST['label_all_items'] ?? ''
        ]
    ];

    // Validate required fields
    if (empty($new_post_type['name'])) {
        $error_message = 'Post type name is required.';
    } elseif (empty($new_post_type['slug'])) {
        $error_message = 'Post type slug is required.';
    } else {
        // If editing, keep the original slug for the update
        $update_slug = $is_new ? $new_post_type['slug'] : $slug;

        // Save post type
        if (save_post_type($update_slug, $new_post_type)) {
            $success_message = $is_new ? 'Post type created successfully.' : 'Post type updated successfully.';

            if ($is_new) {
                // Redirect to edit page with new slug
                header("Location: post-type-edit.php?slug=" . urlencode($new_post_type['slug']) . "&saved=1");
                exit;
            } else {
                $post_type = $new_post_type; // Update current post type
            }
        } else {
            $error_message = 'Failed to save post type. Check file permissions.';
        }
    }
}

// Handle saved parameter
if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $success_message = 'Post type saved successfully.';
}

// Get available taxonomies for selection
$taxonomies = get_taxonomies();

// Get all available languages
require_once '../includes/languages.php';
$languages = get_active_languages();

if (empty($languages)) {
    $languages = ['en']; // Fallback to English only if no active languages
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="post-types.php">Post Types</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo $is_new ? 'Add New' : 'Edit'; ?></li>
                    </ol>
                </nav>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $page_title; ?></h1>
                    <?php if (!$is_new): ?>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="post-type-edit.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-plus-lg"></i> Add New
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <form method="post" action="post-type-edit.php<?php echo $is_new ? '' : '?slug=' . urlencode($slug); ?>">
                            <div class="row">
                                <div class="col-md-8">
                                    <!-- Language Tabs -->
                                    <ul class="nav nav-tabs mb-3" id="languageTabs" role="tablist">
                                        <?php foreach ($languages as $i => $lang): ?>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link <?php echo ($i === 0) ? 'active' : ''; ?>" 
                                                        id="<?php echo $lang; ?>-tab" 
                                                        data-bs-toggle="tab" 
                                                        data-bs-target="#<?php echo $lang; ?>-content" 
                                                        type="button" 
                                                        role="tab" 
                                                        aria-controls="<?php echo $lang; ?>-content" 
                                                        aria-selected="<?php echo ($i === 0) ? 'true' : 'false'; ?>">
                                                    <?php echo strtoupper($lang); ?>
                                                </button>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>

                                    <!-- Tab Content -->
                                    <div class="tab-content" id="languageTabsContent">
                                        <?php foreach ($languages as $i => $lang): ?>
                                            <div class="tab-pane fade <?php echo ($i === 0) ? 'show active' : ''; ?>" 
                                                 id="<?php echo $lang; ?>-content" 
                                                 role="tabpanel" 
                                                 aria-labelledby="<?php echo $lang; ?>-tab">

                                                <div class="mb-3">
                                                    <label for="name_<?php echo $lang; ?>" class="form-label">
                                                        Name (<?php echo strtoupper($lang); ?>) <?php echo ($lang === DEFAULT_LANGUAGE) ? '*' : ''; ?>
                                                    </label>
                                                    <input type="text" 
                                                           class="form-control" 
                                                           id="name_<?php echo $lang; ?>" 
                                                           name="name_<?php echo $lang; ?>" 
                                                           value="<?php echo htmlspecialchars($post_type['name_' . $lang] ?? ''); ?>"
                                                           <?php echo ($lang === DEFAULT_LANGUAGE) ? 'required' : ''; ?>>
                                                    <div class="form-text">
                                                        <?php echo ($lang === DEFAULT_LANGUAGE) ? 
                                                            'The name of the post type as it appears in the admin interface.' : 
                                                            'Leave empty to use the default language value.'; ?>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="description_<?php echo $lang; ?>" class="form-label">
                                                        Description (<?php echo strtoupper($lang); ?>)
                                                    </label>
                                                    <textarea class="form-control" 
                                                              id="description_<?php echo $lang; ?>" 
                                                              name="description_<?php echo $lang; ?>" 
                                                              rows="3"><?php echo htmlspecialchars($post_type['description_' . $lang] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php foreach ($languages as $lang): ?>
                                    <div class="mb-3">
                                        <label for="slug_<?php echo $lang; ?>" class="form-label">
                                            Slug (<?php echo strtoupper($lang); ?>) <?php echo ($lang === DEFAULT_LANGUAGE) ? '*' : ''; ?>
                                        </label>
                                        <input type="text" class="form-control" 
                                               id="slug_<?php echo $lang; ?>" 
                                               name="slug_<?php echo $lang; ?>" 
                                               value="<?php echo htmlspecialchars($post_type['slug_' . $lang] ?? ''); ?>"
                                               <?php echo $is_new ? '' : 'readonly'; ?> 
                                               <?php echo ($lang === DEFAULT_LANGUAGE) ? 'required' : ''; ?>>
                                        <div class="form-text">
                                            <?php if ($lang === DEFAULT_LANGUAGE): ?>
                                                The unique identifier for this post type in <?php echo strtoupper($lang); ?>.
                                                Use only lowercase letters, numbers, and hyphens.
                                                <?php if (!$is_new): ?>
                                                    <strong>Note:</strong> Slug cannot be changed after creation.
                                                <?php endif; ?>
                                            <?php else: ?>
                                                Leave empty to use the default language slug.
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($post_type['description'] ?? ''); ?></textarea>
                                        <div class="form-text">A brief description of what this post type is for.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="icon" class="form-label">Icon</label>
                                        <input type="text" class="form-control" id="icon" name="icon" 
                                               value="<?php echo htmlspecialchars($post_type['icon'] ?? 'bi-file-text'); ?>">
                                        <div class="form-text">
                                            A Bootstrap icon name for this post type (e.g., bi-file-text, bi-image).
                                            See <a href="https://icons.getbootstrap.com/" target="_blank">Bootstrap Icons</a>.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h5 class="mb-0">Settings</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="public" name="public" 
                                                       <?php echo (!isset($post_type['public']) || $post_type['public']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="public">Public</label>
                                                <div class="form-text">Whether posts of this type should be shown in the front-end.</div>
                                            </div>

                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="has_archive" name="has_archive" 
                                                       <?php echo (isset($post_type['has_archive']) && $post_type['has_archive']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="has_archive">Has Archive</label>
                                                <div class="form-text">Whether this post type should have an archive page.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Taxonomies</h5>
                                        </div>
                                        <div class="card-body">
                                            <?php if (empty($taxonomies)): ?>
                                                <p class="text-muted">No taxonomies available. <a href="taxonomy-edit.php">Create one</a>.</p>
                                            <?php else: ?>
                                                <?php foreach ($taxonomies as $tax_slug => $taxonomy): ?>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="taxonomy_<?php echo $tax_slug; ?>" 
                                                               name="taxonomies[]" value="<?php echo $tax_slug; ?>"
                                                               <?php echo (isset($post_type['taxonomies']) && in_array($tax_slug, $post_type['taxonomies'])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="taxonomy_<?php echo $tax_slug; ?>">
                                                            <?php echo htmlspecialchars($taxonomy['name']); ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Supports -->
                            <div class="card mb-4 mt-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Features</h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">Select which features this post type supports:</p>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="support_title" name="support_title" 
                                                       <?php echo (!isset($post_type['supports']['title']) || $post_type['supports']['title']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="support_title">Title</label>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="support_editor" name="support_editor" 
                                                       <?php echo (!isset($post_type['supports']['editor']) || $post_type['supports']['editor']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="support_editor">Content Editor</label>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="support_excerpt" name="support_excerpt" 
                                                       <?php echo (isset($post_type['supports']['excerpt']) && $post_type['supports']['excerpt']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="support_excerpt">Excerpt</label>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="support_thumbnail" name="support_thumbnail" 
                                                       <?php echo (isset($post_type['supports']['thumbnail']) && $post_type['supports']['thumbnail']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="support_thumbnail">Featured Image</label>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="support_custom_fields" name="support_custom_fields" 
                                                       <?php echo (isset($post_type['supports']['custom_fields']) && $post_type['supports']['custom_fields']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="support_custom_fields">Custom Fields</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Labels -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Labels</h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">Customize the text labels used for this post type:</p>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="label_singular" class="form-label">Singular Name</label>
                                                <input type="text" class="form-control" id="label_singular" name="label_singular" 
                                                       value="<?php echo htmlspecialchars($post_type['labels']['singular'] ?? ''); ?>" 
                                                       placeholder="e.g., Post">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="label_plural" class="form-label">Plural Name</label>
                                                <input type="text" class="form-control" id="label_plural" name="label_plural" 
                                                       value="<?php echo htmlspecialchars($post_type['labels']['plural'] ?? ''); ?>" 
                                                       placeholder="e.g., Posts">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="label_add_new" class="form-label">Add New</label>
                                                <input type="text" class="form-control" id="label_add_new" name="label_add_new" 
                                                       value="<?php echo htmlspecialchars($post_type['labels']['add_new'] ?? ''); ?>" 
                                                       placeholder="e.g., Add New Post">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="label_edit" class="form-label">Edit</label>
                                                <input type="text" class="form-control" id="label_edit" name="label_edit" 
                                                       value="<?php echo htmlspecialchars($post_type['labels']['edit'] ?? ''); ?>" 
                                                       placeholder="e.g., Edit Post">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="label_all_items" class="form-label">All Items</label>
                                                <input type="text" class="form-control" id="label_all_items" name="label_all_items" 
                                                       value="<?php echo htmlspecialchars($post_type['labels']['all_items'] ?? ''); ?>" 
                                                       placeholder="e.g., All Posts">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="post-types.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> <?php echo $is_new ? 'Create Post Type' : 'Update Post Type'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-generate slug from name if slug is empty
        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');

        if (nameInput && slugInput && slugInput.value === '') {
            nameInput.addEventListener('input', function() {
                // Only update if slug field is editable and empty
                if (!slugInput.hasAttribute('readonly') && slugInput.value === '') {
                    const slug = nameInput.value
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '');

                    slugInput.value = slug;
                }
            });
        }
    });
    </script>
</body>
</html>