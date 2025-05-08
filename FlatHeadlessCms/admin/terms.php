<?php
/**
 * Terms management page
 * 
 * Displays and manages terms for a specific taxonomy
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

// Get terms for this taxonomy
$terms = get_terms($taxonomy_slug);

// Define default language if not already defined
if (!defined('DEFAULT_LANGUAGE')) {
    define('DEFAULT_LANGUAGE', 'en');
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
                <h1 class="h2"><?php echo htmlspecialchars($taxonomy_name); ?> Terms</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="taxonomy-edit.php?slug=<?php echo urlencode($taxonomy_slug); ?>" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-gear"></i> Taxonomy Settings
                    </a>
                    <a href="term-edit.php?taxonomy=<?php echo urlencode($taxonomy_slug); ?>&term=new" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus"></i> Add New Term
                    </a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-list-ul"></i> Terms List
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Description</th>
                                    <th>Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($terms)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No terms found. <a href="term-edit.php?taxonomy=<?php echo urlencode($taxonomy_slug); ?>&term=new">Add your first term</a>.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($terms as $term): ?>
                                        <?php
                                        // Use language-specific name if available, or default language name
                                        $term_name = isset($term['name_' . DEFAULT_LANGUAGE]) ? $term['name_' . DEFAULT_LANGUAGE] : 
                                                     (isset($term['name_en']) ? $term['name_en'] : ('ID: ' . $term['id']));
                                        $term_slug = isset($term['slug_' . DEFAULT_LANGUAGE]) ? $term['slug_' . DEFAULT_LANGUAGE] : 
                                                     (isset($term['slug_en']) ? $term['slug_en'] : '');
                                        $term_desc = isset($term['description_' . DEFAULT_LANGUAGE]) ? $term['description_' . DEFAULT_LANGUAGE] : 
                                                     (isset($term['description_en']) ? $term['description_en'] : '');
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="term-edit.php?taxonomy=<?php echo urlencode($taxonomy_slug); ?>&term=<?php echo urlencode($term['id']); ?>">
                                                    <?php echo htmlspecialchars($term_name); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($term_slug); ?></td>
                                            <td><?php echo htmlspecialchars(substr($term_desc, 0, 50) . (strlen($term_desc) > 50 ? '...' : '')); ?></td>
                                            <td><?php echo (int)($term['count'] ?? 0); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="term-edit.php?taxonomy=<?php echo urlencode($taxonomy_slug); ?>&term=<?php echo urlencode($term['id']); ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            data-bs-toggle="modal" data-bs-target="#deleteTermModal<?php echo $term['id']; ?>">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </div>
                                                
                                                <!-- Delete Modal -->
                                                <div class="modal fade" id="deleteTermModal<?php echo $term['id']; ?>" tabindex="-1" 
                                                     aria-labelledby="deleteTermModalLabel<?php echo $term['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="deleteTermModalLabel<?php echo $term['id']; ?>">
                                                                    Confirm Delete
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Are you sure you want to delete the term <strong><?php echo htmlspecialchars($term_name); ?></strong>?</p>
                                                                <p class="text-danger">This action cannot be undone. All associations with this term will be removed.</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <a href="term-edit.php?taxonomy=<?php echo urlencode($taxonomy_slug); ?>&term=<?php echo urlencode($term['id']); ?>&action=delete" class="btn btn-danger">Delete Term</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>