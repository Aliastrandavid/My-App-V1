<?php
/**
 * Term edit page
 * 
 * Displays a form for editing or creating a taxonomy term
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
require_once '../includes/taxonomy.php';
require_once '../includes/admin-functions.php';

// Get username from session
$username = $_SESSION['username'] ?? 'User';

// Check if taxonomy parameter exists
if (empty($_GET['taxonomy'])) {
    header('Location: taxonomies.php');
    exit;
}

$taxonomy_slug = $_GET['taxonomy'];

// Get taxonomy details
$taxonomies = get_taxonomies();
if (!isset($taxonomies[$taxonomy_slug])) {
    header('Location: taxonomies.php');
    exit;
}

$taxonomy = $taxonomies[$taxonomy_slug];
$taxonomy_name = $taxonomy['name'] ?? $taxonomy_slug;

// Check if term parameter exists
$term_id = $_GET['term'] ?? null;
$is_new = ($term_id === 'new');
$term = [];

if (!$is_new && $term_id) {
    // Get existing term
    $term = get_term($taxonomy_slug, $term_id);
    
    if (empty($term)) {
        // Term not found, redirect to terms list
        header("Location: terms.php?taxonomy={$taxonomy_slug}");
        exit;
    }
}

// Define default language if not already defined
if (!defined('DEFAULT_LANGUAGE')) {
    define('DEFAULT_LANGUAGE', 'en');
}

// Get available languages
$lang_config = get_language_settings();
$languages = $lang_config['languages'] ?? ['en'];

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save_term') {
        $new_term = [];
        
        // Generate ID for new term
        if ($is_new) {
            // Get highest term ID for this taxonomy and increment
            $terms = get_terms($taxonomy_slug);
            $highest_id = 0;
            
            foreach ($terms as $existing_term) {
                if (isset($existing_term['id']) && $existing_term['id'] > $highest_id) {
                    $highest_id = $existing_term['id'];
                }
            }
            
            $new_term['id'] = $highest_id + 1;
        } else {
            $new_term['id'] = (int)$term_id;
        }
        
        // Process term data for each language
        foreach ($languages as $lang) {
            // Name field
            $new_term["name_{$lang}"] = $_POST["name_{$lang}"] ?? '';
            
            // Slug field - generate from name if empty
            if (empty($_POST["slug_{$lang}"])) {
                $new_term["slug_{$lang}"] = sanitize_slug($new_term["name_{$lang}"]);
            } else {
                $new_term["slug_{$lang}"] = sanitize_slug($_POST["slug_{$lang}"]);
            }
            
            // Description field
            $new_term["description_{$lang}"] = $_POST["description_{$lang}"] ?? '';
        }
        
        // Parent term
        $new_term['parent'] = isset($_POST['parent']) ? (int)$_POST['parent'] : 0;
        
        // Maintain term usage count
        $new_term['count'] = $is_new ? 0 : ($term['count'] ?? 0);
        
        // Save term
        $result = save_term($taxonomy_slug, $new_term);
        
        if ($result) {
            $success_message = $is_new ? 'Term created successfully.' : 'Term updated successfully.';
            
            // If it's a new term, redirect to edit page
            if ($is_new) {
                header("Location: term-edit.php?taxonomy={$taxonomy_slug}&term={$new_term['id']}&success=1");
                exit;
            }
        } else {
            $error_message = 'Error saving term. Please try again.';
        }
    } elseif (isset($_GET['action']) && $_GET['action'] === 'delete') {
        // Delete term
        $result = delete_term($taxonomy_slug, $term_id);
        
        if ($result) {
            header("Location: terms.php?taxonomy={$taxonomy_slug}&deleted=1");
            exit;
        } else {
            $error_message = 'Error deleting term. Please try again.';
        }
    }
}

// Success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = 'Term saved successfully.';
}

// Get all terms for parent selector
$all_terms = get_terms($taxonomy_slug);

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
                <h1 class="h2"><?php echo $is_new ? "Add New {$taxonomy_name} Term" : "Edit {$taxonomy_name} Term"; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="terms.php?taxonomy=<?php echo urlencode($taxonomy_slug); ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Terms
                    </a>
                </div>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-tag"></i> Term Details
                </div>
                <div class="card-body">
                    <form method="post" action="term-edit.php?taxonomy=<?php echo urlencode($taxonomy_slug); ?>&term=<?php echo $is_new ? 'new' : urlencode($term_id); ?>">
                        <input type="hidden" name="action" value="save_term">
                        
                        <!-- Tabs for languages -->
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
                        
                        <!-- Tab content -->
                        <div class="tab-content" id="languageTabsContent">
                            <?php foreach ($languages as $i => $lang): ?>
                                <div class="tab-pane fade <?php echo ($i === 0) ? 'show active' : ''; ?>" 
                                     id="<?php echo $lang; ?>-content" 
                                     role="tabpanel" 
                                     aria-labelledby="<?php echo $lang; ?>-tab">
                                    
                                    <div class="mb-3">
                                        <label for="name_<?php echo $lang; ?>" class="form-label">Name (<?php echo strtoupper($lang); ?>) <?php echo ($lang === DEFAULT_LANGUAGE) ? '*' : ''; ?></label>
                                        <input type="text" class="form-control" id="name_<?php echo $lang; ?>" name="name_<?php echo $lang; ?>" 
                                               value="<?php echo htmlspecialchars($term["name_{$lang}"] ?? ''); ?>" 
                                               <?php echo ($lang === DEFAULT_LANGUAGE) ? 'required' : ''; ?>>
                                        <?php if ($lang === DEFAULT_LANGUAGE): ?>
                                            <div class="form-text">Required. This will be displayed as the term name.</div>
                                        <?php else: ?>
                                            <div class="form-text">Leave empty to use the default language value.</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="slug_<?php echo $lang; ?>" class="form-label">Slug (<?php echo strtoupper($lang); ?>)</label>
                                        <input type="text" class="form-control" id="slug_<?php echo $lang; ?>" name="slug_<?php echo $lang; ?>" 
                                               value="<?php echo htmlspecialchars($term["slug_{$lang}"] ?? ''); ?>">
                                        <div class="form-text">URL-friendly version of the name. Leave empty to generate automatically.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description_<?php echo $lang; ?>" class="form-label">Description (<?php echo strtoupper($lang); ?>)</label>
                                        <textarea class="form-control" id="description_<?php echo $lang; ?>" name="description_<?php echo $lang; ?>" rows="3"><?php echo htmlspecialchars($term["description_{$lang}"] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="parent" class="form-label">Parent Term</label>
                            <select class="form-select" id="parent" name="parent">
                                <option value="0">None (Top Level Term)</option>
                                <?php foreach ($all_terms as $parent_term): ?>
                                    <?php if ($is_new || ($parent_term['id'] != $term['id'])): ?>
                                        <option value="<?php echo (int)$parent_term['id']; ?>" 
                                                <?php echo (!$is_new && isset($term['parent']) && $term['parent'] == $parent_term['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($parent_term['name_' . DEFAULT_LANGUAGE] ?? $parent_term['name_en'] ?? "Term #{$parent_term['id']}"); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Optional. Select a parent term to create a hierarchy.</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Term
                            </button>
                            
                            <?php if (!$is_new): ?>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteTermModal">
                                    <i class="bi bi-trash"></i> Delete Term
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if (!$is_new): ?>
                <!-- Delete Modal -->
                <div class="modal fade" id="deleteTermModal" tabindex="-1" aria-labelledby="deleteTermModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteTermModalLabel">Confirm Delete</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this term?</p>
                                <p class="text-danger">This action cannot be undone. All associations with this term will be removed.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <a href="term-edit.php?taxonomy=<?php echo urlencode($taxonomy_slug); ?>&term=<?php echo urlencode($term_id); ?>&action=delete" class="btn btn-danger">Delete Term</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>