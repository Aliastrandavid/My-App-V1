<?php
/**
 * Post Edit Page
 * 
 * Form for creating and editing posts
 */

// Start session
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
require_once '../includes/media.php';

// Get username from session
$username = $_SESSION['username'] ?? 'User';

// Using existing functions from includes/*.php files instead of defining them here

function save_post($post_type, $post_data) {
    // Use the specific post type JSON file
    $file = "../storage/{$post_type}.json";
    $posts = read_json_file($file);
    
    if (!$posts || !isset($posts['posts'])) {
        $posts = ['posts' => []];
    }

    // For new posts, ensure sequential ID
    if (empty($post_data['id'])) {
        $max_id = 0;
        foreach ($posts['posts'] as $existing_post) {
            $id_num = (int)preg_replace('/[^0-9]/', '', $existing_post['id']);
            if ($id_num > $max_id) {
                $max_id = $id_num;
            }
        }
        $post_data['id'] = (string)($max_id + 1);
    }

    // Look for existing post with same ID
    $found = false;
    foreach ($posts['posts'] as $key => $post) {
        if ($post['id'] === $post_data['id']) {
            $posts['posts'][$key] = $post_data;
            $found = true;
            break;
        }
    }

    // If not found, add new post
    if (!$found) {
        $posts['posts'][] = $post_data;
    }

    // Save to file
    return write_json_file($file, $posts);
}

// Using get_post() from includes/post-types.php


// Get post type from query parameters
$post_type = $_GET['type'] ?? 'blog';

// Get post ID from query parameters (if editing)
$post_id = $_GET['id'] ?? '';
$is_editing = !empty($post_id);

// Get post data from the specific post type file
$file = "../storage/{$post_type}.json";
$posts_data = read_json_file($file);
$posts = isset($posts_data['posts']) ? $posts_data['posts'] : [];

// Initialize empty post data
$post = [
    'id' => '',
    'date' => date('Y-m-d'),
    'status' => 'draft'
];

// If editing, load existing post data
if ($is_editing) {
    foreach ($posts as $existing_post) {
        if ($existing_post['id'] === $post_id) {
            $post = $existing_post;
            break;
        }
    }
}

// Generate sequential ID for new posts
if (!$is_editing) {
    $max_id = 0;
    foreach ($posts as $existing_post) {
        $current_id = (int)preg_replace('/[^0-9]/', '', $existing_post['id']);
        if ($current_id > $max_id) {
            $max_id = $current_id;
        }
    }
    $post['id'] = $post_type . '-' . ($max_id + 1);
}

// Get all post types
$post_types = get_post_types();

// Check if post type exists
if (!array_key_exists($post_type, $post_types)) {
    header('Location: posts.php');
    exit;
}

// Get post type configuration
$post_type_config = $post_types[$post_type];

// Get all taxonomies for this post type
$taxonomies = get_taxonomies_for_post_type($post_type);

// Define default language if not already defined
if (!defined('DEFAULT_LANGUAGE')) {
    define('DEFAULT_LANGUAGE', 'en');
}

// Get all available languages
$languages_file = '../storage/lang_config.json';
$lang_config = [];
$languages = ['en']; // Fallback to English only

if (file_exists($languages_file)) {
    $lang_config = read_json_file($languages_file);
    if (isset($lang_config['active_languages']) && is_array($lang_config['active_languages'])) {
        $languages = $lang_config['active_languages'];
    }
}

// Get post ID from query parameters (if editing)
$post_id = $_GET['id'] ?? '';
$is_editing = !empty($post_id);

// Initialize post data
$post = [
    'id' => '',
    'date' => date('Y-m-d'),
    'status' => 'draft'
];

// Define is_editing before use
$post_id = $_GET['id'] ?? '';
$is_editing = !empty($post_id);

// Add language-specific fields
foreach ($languages as $lang_code) {
    $post['title_' . $lang_code] = '';
    $post['content_' . $lang_code] = '';
    $post['meta_title_' . $lang_code] = '';
    $post['meta_description_' . $lang_code] = '';
    $post['slug_' . $lang_code] = ''; // Added slug fields for each language
}

// Add custom fields from post type
if (isset($post_type_config['fields']) && is_array($post_type_config['fields'])) {
    foreach ($post_type_config['fields'] as $field_key => $field) {
        if ($field['type'] === 'gallery') {
            $post[$field_key] = [];
        } else {
            $post[$field_key] = '';
        }
    }
}

// Add taxonomies
foreach ($taxonomies as $tax_key => $tax) {
    $post['tax_' . $tax_key] = [];
}

// If editing, load existing post data
if ($is_editing) {
    $loaded_post = get_post($post_type, $post_id);
    if ($loaded_post) {
        $post = array_merge($post, $loaded_post);
    } else {
        // Post not found
        header('Location: posts.php?type=' . urlencode($post_type));
        exit;
    }
}

// Process form submission
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (empty($_POST['title_' . DEFAULT_LANGUAGE])) {
        $errors[] = 'Title is required';
    }

    // Handle multilingual slugs
    foreach ($languages as $lang_code) {
        if (empty($_POST['slug_' . $lang_code])) {
            // Generate slug from title
            $_POST['slug_' . $lang_code] = create_slug($_POST['title_' . $lang_code] ?? $_POST['title_' . DEFAULT_LANGUAGE]);
        } else {
            // Sanitize provided slug
            $_POST['slug_' . $lang_code] = create_slug($_POST['slug_' . $lang_code]);
        }
    }

    // Check if any language slug is already used
    foreach ($languages as $lang_code) {
        if (!$is_editing || ($is_editing && $_POST['slug_' . $lang_code] !== ($post['slug_' . $lang_code] ?? ''))) {
            if (is_slug_used($post_type, $_POST['slug_' . $lang_code], $post_id)) {
                $errors[] = 'Slug "' . $_POST['slug_' . $lang_code] . '" already in use for language ' . strtoupper($lang_code) . '. Please choose a different one.';
                break; // Exit loop after finding the first conflict
            }
        }
    }


    // If no errors, save the post
    if (empty($errors)) {
        $post_data = [
            'id' => $is_editing ? $post_id : generate_id(),
            'date' => $_POST['date'],
            'status' => $_POST['status'],
            'featured_image' => $_POST['featured_image'] ?? ''
        ];

        // Add language-specific fields
        foreach ($languages as $lang_code) {
            $post_data['title_' . $lang_code] = $_POST['title_' . $lang_code] ?? '';
            $post_data['content_' . $lang_code] = $_POST['content_' . $lang_code] ?? '';
            $post_data['meta_title_' . $lang_code] = $_POST['meta_title_' . $lang_code] ?? '';
            $post_data['meta_description_' . $lang_code] = $_POST['meta_description_' . $lang_code] ?? '';
            $post_data['slug_' . $lang_code] = $_POST['slug_' . $lang_code]; //Added slug field for each language
        }

        // Add custom fields
        if (isset($post_type_config['fields']) && is_array($post_type_config['fields'])) {
            foreach ($post_type_config['fields'] as $field_key => $field) {
                if ($field['type'] === 'gallery') {
                    $post_data[$field_key] = isset($_POST[$field_key]) ? json_decode($_POST[$field_key], true) : [];
                } else {
                    $post_data[$field_key] = $_POST[$field_key] ?? '';
                }
            }
        }

        // Add taxonomies
        foreach ($taxonomies as $tax_key => $tax) {
            $post_data['tax_' . $tax_key] = isset($_POST['tax_' . $tax_key]) ? $_POST['tax_' . $tax_key] : [];
        }

        // Save post
        if (save_post($post_type, $post_data)) {
            if ($is_editing) {
                $success_message = 'Post updated successfully.';
                // Reload post data
                $post = get_post($post_type, $post_id);
            } else {
                // Redirect to edit page for the new post
                header('Location: post-edit.php?type=' . urlencode($post_type) . '&id=' . urlencode($post_data['id']) . '&message=created');
                exit;
            }
        } else {
            $errors[] = 'Failed to save post. Please try again.';
        }
    }
}

// Load all media for media selector
$media_items = get_all_media();

// Check for success message in query parameters
if (isset($_GET['message']) && $_GET['message'] === 'created') {
    $success_message = 'Post created successfully.';
}

// Include header
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $is_editing ? 'Edit' : 'Add New'; ?> <?php echo htmlspecialchars($post_type_config['name_' . DEFAULT_LANGUAGE] ?? $post_type_config['name'] ?? ''); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="posts.php?type=<?php echo urlencode($post_type); ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="post-edit.php?type=<?php echo urlencode($post_type); ?><?php echo $is_editing ? '&id=' . urlencode($post_id) : ''; ?>">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Language tabs for content -->
                        <ul class="nav nav-tabs mb-3" id="languageTabs" role="tablist">
                            <?php foreach ($languages as $index => $lang_code): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                                            id="<?php echo $lang_code; ?>-tab" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#<?php echo $lang_code; ?>-content" 
                                            type="button" 
                                            role="tab" 
                                            aria-controls="<?php echo $lang_code; ?>-content" 
                                            aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                                        <?php echo strtoupper($lang_code); ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="tab-content" id="languageTabsContent">
                            <?php foreach ($languages as $index => $lang_code): ?>
                                <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
                                     id="<?php echo $lang_code; ?>-content" 
                                     role="tabpanel" 
                                     aria-labelledby="<?php echo $lang_code; ?>-tab">

                                    <!-- Title -->
                                    <div class="mb-3">
                                        <label for="title_<?php echo $lang_code; ?>" class="form-label">
                                            Title <?php echo strtoupper($lang_code); ?>
                                            <?php echo $lang_code === DEFAULT_LANGUAGE ? '<span class="text-danger">*</span>' : ''; ?>
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="title_<?php echo $lang_code; ?>" 
                                               name="title_<?php echo $lang_code; ?>" 
                                               value="<?php echo htmlspecialchars($post['title_' . $lang_code] ?? ''); ?>" 
                                               <?php echo $lang_code === DEFAULT_LANGUAGE ? 'required' : ''; ?>>
                                    </div>

                                    <div class="mb-3">
                                        <label for="slug_<?php echo $lang_code; ?>" class="form-label">
                                            Slug <?php echo strtoupper($lang_code); ?>
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="slug_<?php echo $lang_code; ?>" 
                                               name="slug_<?php echo $lang_code; ?>" 
                                               value="<?php echo htmlspecialchars($post['slug_' . $lang_code] ?? ''); ?>">
                                        <div class="form-text">Leave empty to generate from title.</div>
                                    </div>

                                    <!-- Content -->
                                    <div class="mb-3">
                                        <label for="content_<?php echo $lang_code; ?>" class="form-label">Content <?php echo strtoupper($lang_code); ?></label>
                                        <textarea class="form-control rich-editor" 
                                                  id="content_<?php echo $lang_code; ?>" 
                                                  name="content_<?php echo $lang_code; ?>" 
                                                  rows="10"><?php echo htmlspecialchars($post['content_' . $lang_code] ?? ''); ?></textarea>
                                    </div>

                                    <!-- SEO Fields -->
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            SEO Settings (<?php echo strtoupper($lang_code); ?>)
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="meta_title_<?php echo $lang_code; ?>" class="form-label">Meta Title</label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="meta_title_<?php echo $lang_code; ?>" 
                                                       name="meta_title_<?php echo $lang_code; ?>" 
                                                       value="<?php echo htmlspecialchars($post['meta_title_' . $lang_code] ?? ''); ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="meta_description_<?php echo $lang_code; ?>" class="form-label">Meta Description</label>
                                                <textarea class="form-control" 
                                                          id="meta_description_<?php echo $lang_code; ?>" 
                                                          name="meta_description_<?php echo $lang_code; ?>" 
                                                          rows="3"><?php echo htmlspecialchars($post['meta_description_' . $lang_code] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Custom Fields -->
                        <?php if (isset($post_type_config['fields']) && is_array($post_type_config['fields']) && !empty($post_type_config['fields'])): ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    Custom Fields
                                </div>
                                <div class="card-body">
                                    <?php foreach ($post_type_config['fields'] as $field_key => $field): ?>
                                        <div class="mb-3">
                                            <label for="<?php echo $field_key; ?>" class="form-label">
                                                <?php echo htmlspecialchars($field['label']); ?>
                                                <?php echo isset($field['required']) && $field['required'] ? '<span class="text-danger">*</span>' : ''; ?>
                                            </label>

                                            <?php if ($field['type'] === 'text'): ?>
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="<?php echo $field_key; ?>" 
                                                       name="<?php echo $field_key; ?>" 
                                                       value="<?php echo htmlspecialchars($post[$field_key] ?? ''); ?>"
                                                       <?php echo isset($field['required']) && $field['required'] ? 'required' : ''; ?>>

                                            <?php elseif ($field['type'] === 'textarea'): ?>
                                                <textarea class="form-control" 
                                                          id="<?php echo $field_key; ?>" 
                                                          name="<?php echo $field_key; ?>" 
                                                          rows="4"
                                                          <?php echo isset($field['required']) && $field['required'] ? 'required' : ''; ?>><?php echo htmlspecialchars($post[$field_key] ?? ''); ?></textarea>

                                            <?php elseif ($field['type'] === 'select' && isset($field['options'])): ?>
                                                <select class="form-select" 
                                                        id="<?php echo $field_key; ?>" 
                                                        name="<?php echo $field_key; ?>"
                                                        <?php echo isset($field['required']) && $field['required'] ? 'required' : ''; ?>>
                                                    <option value="">Select an option</option>
                                                    <?php foreach ($field['options'] as $option_value => $option_label): ?>
                                                        <option value="<?php echo htmlspecialchars($option_value); ?>" 
                                                                <?php echo ($post[$field_key] ?? '') === $option_value ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($option_label); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                            <?php elseif ($field['type'] === 'date'): ?>
                                                <input type="date" 
                                                       class="form-control" 
                                                       id="<?php echo $field_key; ?>" 
                                                       name="<?php echo $field_key; ?>" 
                                                       value="<?php echo htmlspecialchars($post[$field_key] ?? ''); ?>"
                                                       <?php echo isset($field['required']) && $field['required'] ? 'required' : ''; ?>>

                                            <?php elseif ($field['type'] === 'gallery'): ?>
                                                <div class="gallery-field" id="gallery-<?php echo $field_key; ?>">
                                                    <div class="gallery-items row mb-3">
                                                        <?php 
                                                        $gallery_items = $post[$field_key] ?? [];
                                                        if (!empty($gallery_items)):
                                                            foreach ($gallery_items as $index => $gallery_item):
                                                                $media_item = get_media_by_id($gallery_item);
                                                                if ($media_item):
                                                        ?>
                                                            <div class="col-md-3 mb-3 gallery-item" data-id="<?php echo htmlspecialchars($gallery_item); ?>">
                                                                <div class="card">
                                                                    <div class="card-img-top gallery-item-preview" style="height: 150px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                                                        <?php if (in_array($media_item['type'], ['jpg', 'jpeg', 'png', 'gif', 'svg'])): ?>
                                                                            <img src="<?php echo htmlspecialchars($media_item['url']); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($media_item['alt']); ?>" style="max-height: 150px;">
                                                                        <?php else: ?>
                                                                            <i class="bi bi-file-earmark fs-1"></i>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="card-body p-2">
                                                                        <p class="card-text small text-truncate"><?php echo htmlspecialchars($media_item['name']); ?></p>
                                                                        <button type="button" class="btn btn-sm btn-outline-danger remove-gallery-item">Remove</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php 
                                                                endif;
                                                            endforeach;
                                                        endif;
                                                        ?>
                                                    </div>
                                                    <button type="button" class="btn btn-primary add-gallery-item" data-field="<?php echo $field_key; ?>">
                                                        <i class="bi bi-plus-lg"></i> Add Items
                                                    </button>
                                                    <input type="hidden" name="<?php echo $field_key; ?>" id="<?php echo $field_key; ?>-input" value='<?php echo htmlspecialchars(json_encode($post[$field_key] ?? [])); ?>'>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (isset($field['description'])): ?>
                                                <div class="form-text"><?php echo htmlspecialchars($field['description']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4">
                        <!-- Publish Settings -->
                        <div class="card mb-3">
                            <div class="card-header">
                                Publish Settings
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="draft" <?php echo ($post['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="published" <?php echo ($post['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($post['date'] ?? date('Y-m-d')); ?>">
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Save <?php echo htmlspecialchars($post_type_config['name_' . DEFAULT_LANGUAGE] ?? $post_type_config['name'] ?? ''); ?></button>
                                <?php if ($is_editing): ?>
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="previewDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            Preview
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="previewDropdown">
                                            <?php foreach ($languages as $lang_code): ?>
                                                <li>
                                                    <a class="dropdown-item" href="<?php echo get_post_preview_url($post_type, $post_id, $lang_code); ?>" target="_blank">
                                                        Preview <?php echo strtoupper($lang_code); ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Featured Image -->
                        <div class="card mb-3">
                            <div class="card-header">
                                Featured Image
                            </div>
                            <div class="card-body">
                                <div id="featured-image-preview" class="mb-3 text-center">
                                    <?php 
                                    $featured_image = $post['featured_image'] ?? '';
                                    $media_item = !empty($featured_image) ? get_media_by_id($featured_image) : null;

                                    if ($media_item && in_array($media_item['type'], ['jpg', 'jpeg', 'png', 'gif', 'svg'])):
                                    ?>
                                        <img src="<?php echo htmlspecialchars($media_item['url']); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($media_item['alt']); ?>">
                                    <?php else: ?>
                                        <div class="p-4 bg-light text-center">
                                            <i class="bi bi-image fs-1"></i>
                                            <p>No featured image selected</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <input type="hidden" id="featured_image" name="featured_image" value="<?php echo htmlspecialchars($post['featured_image'] ?? ''); ?>">

                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-outline-primary" id="select-featured-image">
                                        <?php echo !empty($featured_image) ? 'Change Featured Image' : 'Set Featured Image'; ?>
                                    </button>
                                    <?php if (!empty($featured_image)): ?>
                                        <button type="button" class="btn btn-outline-danger" id="remove-featured-image">
                                            Remove Featured Image
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Taxonomies -->
                        <?php if (!empty($taxonomies)): ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    Taxonomies
                                </div>
                                <div class="card-body">
                                    <?php foreach ($taxonomies as $tax_key => $tax): ?>
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo htmlspecialchars($tax['name']); ?></label>
                                            <?php
                                            $terms = get_terms($tax_key);
                                            $post_terms = $post['tax_' . $tax_key] ?? [];

                                            if (!empty($terms)):
                                                if ($tax['multiple']):
                                            ?>
                                                <div class="taxonomy-checkboxes">
                                                    <?php foreach ($terms as $term): ?>
                                                        <div class="form-check">
                                                            <input class="form-check-input" 
                                                                   type="checkbox" 
                                                                   id="tax_<?php echo $tax_key; ?>_<?php echo $term['id']; ?>" 
                                                                   name="tax_<?php echo $tax_key; ?>[]" 
                                                                   value="<?php echo htmlspecialchars($term['id']); ?>"
                                                                   <?php echo in_array($term['id'], $post_terms) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="tax_<?php echo $tax_key; ?>_<?php echo $term['id']; ?>">
                                                                <?php 
                                                                // Use language-specific name if available, or default language name
                                                                $term_name = isset($term['name_' . DEFAULT_LANGUAGE]) ? $term['name_' . DEFAULT_LANGUAGE] : 
                                                                              (isset($term['name_en']) ? $term['name_en'] : ('ID: ' . $term['id']));
                                                                echo htmlspecialchars($term_name);
                                                                ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <select class="form-select" name="tax_<?php echo $tax_key; ?>[]">
                                                    <option value="">Select <?php echo htmlspecialchars($tax['name']); ?></option>
                                                    <?php foreach ($terms as $term): ?>
                                                        <option value="<?php echo htmlspecialchars($term['id']); ?>"
                                                                <?php echo in_array($term['id'], $post_terms) ? 'selected' : ''; ?>>
                                                            <?php 
                                                            // Use language-specific name if available, or default language name
                                                            $term_name = isset($term['name_' . DEFAULT_LANGUAGE]) ? $term['name_' . DEFAULT_LANGUAGE] : 
                                                                         (isset($term['name_en']) ? $term['name_en'] : ('ID: ' . $term['id']));
                                                            echo htmlspecialchars($term_name);
                                                            ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php 
                                                endif;
                                            else:
                                            ?>
                                                <p class="text-muted">No terms available. <a href="taxonomy-edit.php?taxonomy=<?php echo urlencode($tax_key); ?>">Add some</a>.</p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<!-- Media Gallery Modal -->
<div class="modal fade" id="mediaGalleryModal" tabindex="-1" aria-labelledby="mediaGalleryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaGalleryModalLabel">Media Gallery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="media-search" placeholder="Search media...">
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="media.php" class="btn btn-outline-primary" target="_blank">Manage Media</a>
                    </div>
                </div>

                <div class="row" id="media-items-container">
                    <?php foreach ($media_items as $item): ?>
                        <div class="col-md-2 mb-3 media-item" data-id="<?php echo htmlspecialchars($item['id']); ?>">
                            <div class="card h-100 <?php echo $item['id'] === ($post['featured_image'] ?? '') ? 'border-primary' : ''; ?>">
                                <div class="card-img-top media-item-preview" style="height: 120px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                    <?php if (in_array($item['type'], ['jpg', 'jpeg', 'png', 'gif', 'svg'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['url']); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($item['alt']); ?>" style="max-height: 120px;">
                                    <?php else: ?>
                                        <i class="bi bi-file-earmark fs-1"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body p-2">
                                    <p class="card-text small text-truncate"><?php echo htmlspecialchars($item['name']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="select-media-button">Select</button>
            </div>
        </div>
    </div>
</div>
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize Bootstrap components
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize all popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });

        // Initialize all dropdowns
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });
    });

    // Keep track of which field is being edited for gallery
    let activeGalleryField = '';
    let mediaSelectionMode = 'single'; // 'single' for featured image, 'multiple' for gallery

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize rich text editors
        document.querySelectorAll('.rich-editor').forEach(function(editor) {
            // Simple rich text initialization - in production, you'd use a full editor like TinyMCE or CKEditor
            editor.style.minHeight = '200px';
        });

        // Featured image selection
        document.getElementById('select-featured-image').addEventListener('click', function() {
            mediaSelectionMode = 'single';
            const modal = new bootstrap.Modal(document.getElementById('mediaGalleryModal'));
            modal.show();
        });

        // Remove featured image
        const removeFeatureBtn = document.getElementById('remove-featured-image');
        if (removeFeatureBtn) {
            removeFeatureBtn.addEventListener('click', function() {
                document.getElementById('featured_image').value = '';
                document.getElementById('featured-image-preview').innerHTML = `
                    <div class="p-4 bg-light text-center">
                        <i class="bi bi-image fs-1"></i>
                        <p>No featured image selected</p>
                    </div>
                `;
                document.getElementById('select-featured-image').textContent = 'Set Featured Image';
                this.style.display = 'none';
            });
        }

        // Media selection in modal
        document.querySelectorAll('.media-item').forEach(function(item) {
            item.addEventListener('click', function() {
                if (mediaSelectionMode === 'single') {
                    // Single selection mode - remove all other selections
                    document