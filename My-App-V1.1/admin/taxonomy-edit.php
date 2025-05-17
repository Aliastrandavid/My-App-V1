<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: simple_login.php');
    exit;
}

// Include necessary files
require_once '../includes/functions.php';
require_once '../includes/taxonomy.php';
require_once '../includes/post-types.php';
require_once '../includes/languages.php';

// Get available languages
$languages = get_active_languages();

$username = $_SESSION['username'] ?? 'User';

// Initialize variables
$taxonomy = [];
$slug = '';
$is_new = true;
$page_title = 'Add New Taxonomy';
$success_message = '';
$error_message = '';

// Get taxonomy if editing
if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    $slug = $_GET['slug'];
    $taxonomies = get_taxonomies();
    
    if (isset($taxonomies[$slug])) {
        $taxonomy = $taxonomies[$slug];
        $is_new = false;
        $page_title = 'Edit Taxonomy: ' . htmlspecialchars($taxonomy['name_' . DEFAULT_LANGUAGE] ?? '');
    } else {
        $error_message = 'Taxonomy not found.';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_taxonomy = [
        'name' => $_POST['name'] ?? '',
        'description' => $_POST['description'] ?? '',
        'slug' => $_POST['slug'] ?? '',
        'hierarchical' => isset($_POST['hierarchical']) && $_POST['hierarchical'] === 'on',
        'multiple' => isset($_POST['multiple']) && $_POST['multiple'] === 'on',
        'post_types' => isset($_POST['post_types']) ? $_POST['post_types'] : [],
    ];
    
    // Validate required fields
    if (empty($new_taxonomy['name'])) {
        $error_message = 'Taxonomy name is required.';
    } elseif (empty($new_taxonomy['slug'])) {
        $error_message = 'Taxonomy slug is required.';
    } else {
        // If editing, keep the original slug for the update
        $update_slug = $is_new ? $new_taxonomy['slug'] : $slug;
        
        // Save taxonomy
        if (save_taxonomy($update_slug, $new_taxonomy)) {
            $success_message = $is_new ? 'Taxonomy created successfully.' : 'Taxonomy updated successfully.';
            
            if ($is_new) {
                // Redirect to edit page with new slug
                header("Location: taxonomy-edit.php?slug=" . urlencode($new_taxonomy['slug']) . "&saved=1");
                exit;
            } else {
                $taxonomy = $new_taxonomy; // Update current taxonomy
            }
        } else {
            $error_message = 'Failed to save taxonomy. Check file permissions.';
        }
    }
}

// Handle saved parameter
if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $success_message = 'Taxonomy saved successfully.';
}

// Get available post types for selection
$post_types = get_post_types();
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
                        <li class="breadcrumb-item"><a href="taxonomies.php">Taxonomies</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo $is_new ? 'Add New' : 'Edit'; ?></li>
                    </ol>
                </nav>
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $page_title; ?></h1>
                    <?php if (!$is_new): ?>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="taxonomy-edit.php" class="btn btn-sm btn-outline-secondary">
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
                        <form method="post" action="taxonomy-edit.php<?php echo $is_new ? '' : '?slug=' . urlencode($slug); ?>">
                            <div class="row">
                                <div class="col-md-8">
                                    <ul class="nav nav-tabs mb-3" id="langTabs" role="tablist">
                                        <?php foreach ($languages as $lang): ?>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link <?php echo $lang === DEFAULT_LANGUAGE ? 'active' : ''; ?>" 
                                                    id="<?php echo $lang; ?>-tab" 
                                                    data-bs-toggle="tab" 
                                                    data-bs-target="#<?php echo $lang; ?>-content" 
                                                    type="button" 
                                                    role="tab"><?php echo strtoupper($lang); ?></button>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    
                                    <div class="tab-content" id="langTabsContent">
                                        <?php foreach ($languages as $lang): ?>
                                        <div class="tab-pane fade <?php echo $lang === DEFAULT_LANGUAGE ? 'show active' : ''; ?>" 
                                             id="<?php echo $lang; ?>-content" 
                                             role="tabpanel">
                                            
                                            <div class="mb-3">
                                                <label for="name_<?php echo $lang; ?>" class="form-label">
                                                    Name (<?php echo strtoupper($lang); ?>) <?php echo $lang === DEFAULT_LANGUAGE ? '*' : ''; ?>
                                                </label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="name_<?php echo $lang; ?>" 
                                                       name="name_<?php echo $lang; ?>" 
                                                       value="<?php echo htmlspecialchars($taxonomy['name_' . $lang] ?? ''); ?>"
                                                       <?php echo $lang === DEFAULT_LANGUAGE ? 'required' : ''; ?>>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="slug_<?php echo $lang; ?>" class="form-label">
                                                    Slug (<?php echo strtoupper($lang); ?>) <?php echo $lang === DEFAULT_LANGUAGE ? '*' : ''; ?>
                                                </label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="slug_<?php echo $lang; ?>" 
                                                       name="slug_<?php echo $lang; ?>" 
                                                       value="<?php echo htmlspecialchars($taxonomy['slug_' . $lang] ?? ''); ?>"
                                                       <?php echo (!$is_new && $lang === DEFAULT_LANGUAGE) ? 'readonly' : ''; ?>
                                                       <?php echo $lang === DEFAULT_LANGUAGE ? 'required' : ''; ?>>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="description_<?php echo $lang; ?>" class="form-label">
                                                    Description (<?php echo strtoupper($lang); ?>)
                                                </label>
                                                <textarea class="form-control" 
                                                          id="description_<?php echo $lang; ?>" 
                                                          name="description_<?php echo $lang; ?>"><?php echo htmlspecialchars($taxonomy['description_' . $lang] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($taxonomy['description'] ?? ''); ?></textarea>
                                        <div class="form-text">A brief description of what this taxonomy is used for.</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="mb-0">Settings</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="hierarchical" name="hierarchical" 
                                                       <?php echo (isset($taxonomy['hierarchical']) && $taxonomy['hierarchical']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="hierarchical">Hierarchical</label>
                                                <div class="form-text">Allow parent-child relationships (like categories).</div>
                                            </div>
                                            
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="multiple" name="multiple" 
                                                       <?php echo (isset($taxonomy['multiple']) && $taxonomy['multiple']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="multiple">Multiple</label>
                                                <div class="form-text">Allow selection of multiple terms (like tags).</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Post Types</h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">Select which post types can use this taxonomy:</p>
                                            
                                            <?php if (empty($post_types)): ?>
                                                <p class="text-muted">No post types available. <a href="post-type-edit.php">Create one</a>.</p>
                                            <?php else: ?>
                                                <?php foreach ($post_types as $pt_slug => $post_type): ?>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="post_type_<?php echo $pt_slug; ?>" 
                                                               name="post_types[]" value="<?php echo $pt_slug; ?>"
                                                               <?php echo (isset($taxonomy['post_types']) && in_array($pt_slug, $taxonomy['post_types'])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="post_type_<?php echo $pt_slug; ?>">
                                                            <?php echo htmlspecialchars($post_type['name_' . $lang] ?? ''); ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <span style="color: red;"> @mathieu = correction du label pour afficher le nom du post type en fonction de la langue voir si le reste des checkbox est fonctionnel ???
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="taxonomies.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> <?php echo $is_new ? 'Create Taxonomy' : 'Update Taxonomy'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if (!$is_new): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Terms</h5>
                        </div>
                        <div class="card-body">
                            <p>Manage the terms for this taxonomy:</p>
                            <a href="terms.php?taxonomy=<?php echo urlencode($slug); ?>" class="btn btn-outline-primary">
                                <i class="bi bi-tag"></i> Manage Terms
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
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