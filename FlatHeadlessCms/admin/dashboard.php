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

$username = $_SESSION['username'] ?? 'User';

// Load data from JSON files
$post_types = get_post_types();
$posts_data = [];
$post_counts = [];
$taxonomy_counts = [];

// Read posts JSON file
$posts_file = '../storage/posts.json';
if (file_exists($posts_file)) {
    $posts_data = read_json_file($posts_file);
    
    // Count posts by type
    foreach ($posts_data as $post_type => $posts) {
        $post_counts[$post_type] = count($posts);
    }
}

// Read taxonomies and terms
$taxonomies_file = '../storage/taxonomies.json';
$terms_file = '../storage/terms.json';
if (file_exists($taxonomies_file) && file_exists($terms_file)) {
    $taxonomies = read_json_file($taxonomies_file);
    $terms = read_json_file($terms_file);
    
    // Count terms by taxonomy
    foreach ($terms as $taxonomy => $term_list) {
        $taxonomy_counts[$taxonomy] = count($term_list);
    }
}

// Get total content count
$total_posts = array_sum($post_counts);
$total_taxonomies = count($taxonomies ?? []);
$total_terms = array_sum($taxonomy_counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
                    <h1 class="h2">Dashboard</h1>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Total Content</h5>
                                <p class="card-text display-4"><?php echo $total_posts; ?></p>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="posts.php">View Details</a>
                                <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-success text-white mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Post Types</h5>
                                <p class="card-text display-4"><?php echo count($post_types); ?></p>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="post-types.php">View Details</a>
                                <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Taxonomies</h5>
                                <p class="card-text display-4"><?php echo $total_taxonomies; ?></p>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-dark stretched-link" href="taxonomies.php">View Details</a>
                                <div class="small text-dark"><i class="bi bi-chevron-right"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-info text-white mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Terms</h5>
                                <p class="card-text display-4"><?php echo $total_terms; ?></p>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="taxonomies.php">View Details</a>
                                <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="bi bi-table me-1"></i>
                                Content by Type
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Post Type</th>
                                                <th>Count</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($post_types as $slug => $post_type): ?>
                                                <tr>
                                                    <td>
                                                        <i class="bi <?php echo $post_type['icon'] ?? 'bi-file-earmark'; ?>"></i>
                                                        <?php echo htmlspecialchars($post_type['name']); ?>
                                                    </td>
                                                    <td><?php echo $post_counts[$slug] ?? 0; ?></td>
                                                    <td>
                                                        <a href="posts.php?type=<?php echo urlencode($slug); ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="bi bi-diagram-3 me-1"></i>
                                Taxonomies
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Taxonomy</th>
                                                <th>Terms</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($taxonomies)): ?>
                                                <?php foreach ($taxonomies as $slug => $taxonomy): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($taxonomy['name']); ?></td>
                                                        <td><?php echo $taxonomy_counts[$slug] ?? 0; ?></td>
                                                        <td>
                                                            <a href="taxonomy-edit.php?slug=<?php echo urlencode($slug); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No taxonomies found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-clock-history me-1"></i>
                        Recent Content
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $recent_posts = [];
                                    
                                    // Combine all post types into a single array for sorting
                                    foreach ($posts_data as $type => $posts) {
                                        foreach ($posts as $post) {
                                            $post['post_type'] = $type;
                                            $recent_posts[] = $post;
                                        }
                                    }
                                    
                                    // Sort by updated_at date (descending)
                                    usort($recent_posts, function($a, $b) {
                                        return strtotime($b['updated_at'] ?? 0) - strtotime($a['updated_at'] ?? 0);
                                    });
                                    
                                    // Take only the 5 most recent posts
                                    $recent_posts = array_slice($recent_posts, 0, 5);
                                    
                                    if (!empty($recent_posts)):
                                        foreach ($recent_posts as $post):
                                            $post_type = $post['post_type'] ?? '';
                                            $post_type_info = $post_types[$post_type] ?? ['name' => ucfirst($post_type)];
                                            $updated_at = !empty($post['updated_at']) ? date('M j, Y', strtotime($post['updated_at'])) : '';
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($post['title_en'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($post_type_info['name']); ?></td>
                                            <td><?php echo $updated_at; ?></td>
                                            <td>
                                                <?php if (($post['status'] ?? '') === 'published'): ?>
                                                    <span class="badge bg-success">Published</span>
                                                <?php elseif (($post['status'] ?? '') === 'draft'): ?>
                                                    <span class="badge bg-warning text-dark">Draft</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?php echo ucfirst($post['status'] ?? 'Unknown'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="post-edit.php?type=<?php echo urlencode($post_type); ?>&id=<?php echo urlencode($post['id'] ?? ''); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                            </td>
                                        </tr>
                                    <?php 
                                        endforeach;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No content found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>