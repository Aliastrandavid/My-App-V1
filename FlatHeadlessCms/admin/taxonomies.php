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

$username = $_SESSION['username'] ?? 'User';

// Get all taxonomies
$taxonomies = get_taxonomies();
$terms = get_terms_all();

// Count terms for each taxonomy
$term_counts = [];
foreach ($terms as $taxonomy => $term_list) {
    $term_counts[$taxonomy] = count($term_list);
}

// Handle delete action
$delete_message = '';
$delete_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $taxonomy_slug = $_POST['taxonomy_slug'] ?? '';
    
    if (!empty($taxonomy_slug)) {
        if (delete_taxonomy($taxonomy_slug)) {
            $delete_message = 'Taxonomy deleted successfully.';
            $delete_success = true;
            
            // Remove from local array to update the display
            unset($taxonomies[$taxonomy_slug]);
            unset($term_counts[$taxonomy_slug]);
        } else {
            $delete_message = 'Failed to delete taxonomy. Check file permissions.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxonomies - Admin Panel</title>
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Taxonomies</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="taxonomy-edit.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg"></i> Add New Taxonomy
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($delete_message)): ?>
                    <div class="alert alert-<?php echo $delete_success ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($delete_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Hierarchical</th>
                                        <th>Multiple</th>
                                        <th>Terms</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($taxonomies)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No taxonomies found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($taxonomies as $slug => $taxonomy): ?>
                                            <tr>
                                                <td>
                                                    <a href="taxonomy-edit.php?slug=<?php echo urlencode($slug); ?>">
                                                        <?php echo htmlspecialchars($taxonomy['name']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($taxonomy['description'] ?? ''); ?></td>
                                                <td>
                                                    <?php if ($taxonomy['hierarchical'] ?? false): ?>
                                                        <span class="badge bg-success">Yes</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($taxonomy['multiple'] ?? false): ?>
                                                        <span class="badge bg-success">Yes</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="terms.php?taxonomy=<?php echo urlencode($slug); ?>" class="btn btn-sm btn-outline-secondary">
                                                        <?php echo $term_counts[$slug] ?? 0; ?> terms
                                                    </a>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="taxonomy-edit.php?slug=<?php echo urlencode($slug); ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                data-bs-toggle="modal" data-bs-target="#deleteTaxonomyModal<?php echo $slug; ?>">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Delete Modal -->
                                                    <div class="modal fade" id="deleteTaxonomyModal<?php echo $slug; ?>" tabindex="-1" 
                                                         aria-labelledby="deleteTaxonomyModalLabel<?php echo $slug; ?>" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="deleteTaxonomyModalLabel<?php echo $slug; ?>">
                                                                        Confirm Delete
                                                                    </h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Are you sure you want to delete the taxonomy "<?php echo htmlspecialchars($taxonomy['name']); ?>"?</p>
                                                                    <div class="alert alert-warning">
                                                                        <i class="bi bi-exclamation-triangle"></i> Warning:
                                                                        This will also delete all terms associated with this taxonomy.
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <form method="post" action="taxonomies.php">
                                                                        <input type="hidden" name="action" value="delete">
                                                                        <input type="hidden" name="taxonomy_slug" value="<?php echo htmlspecialchars($slug); ?>">
                                                                        <button type="submit" class="btn btn-danger">Delete</button>
                                                                    </form>
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
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">What are taxonomies?</h5>
                    </div>
                    <div class="card-body">
                        <p>Taxonomies are ways to group and organize your content. Think of them as categories or tags for your posts.</p>
                        <ul>
                            <li><strong>Hierarchical taxonomies</strong> can have parent-child relationships (like categories).</li>
                            <li><strong>Multiple selection</strong> allows posts to belong to more than one term of the taxonomy (like tags).</li>
                        </ul>
                        <p>Examples of common taxonomies include:</p>
                        <ul>
                            <li>Categories - Hierarchical, single selection</li>
                            <li>Tags - Non-hierarchical, multiple selection</li>
                            <li>Product Categories - Hierarchical, for organizing products</li>
                        </ul>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>