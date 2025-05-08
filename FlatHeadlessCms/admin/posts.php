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

$username = $_SESSION['username'] ?? 'User';

// Get all post types
$post_types = get_post_types();
$selected_post_type = $_GET['type'] ?? 'blog';

// Check if post type exists
if (!array_key_exists($selected_post_type, $post_types)) {
    $selected_post_type = 'blog';
}

// Get posts for the selected post type
$all_posts = [];
$posts_file = '../storage/posts.json';
if (file_exists($posts_file)) {
    $posts_data = read_json_file($posts_file);
    if (isset($posts_data[$selected_post_type])) {
        $all_posts = $posts_data[$selected_post_type];
        
        // Sort by updated_at date (newest first)
        usort($all_posts, function($a, $b) {
            $date_a = strtotime($a['updated_at'] ?? $a['created_at'] ?? 0);
            $date_b = strtotime($b['updated_at'] ?? $b['created_at'] ?? 0);
            return $date_b - $date_a;
        });
    }
}

// Pagination settings
$current_page = $_GET['page'] ?? 1;
$current_page = (int) $current_page;
if ($current_page < 1) $current_page = 1;

$items_per_page = $_GET['per_page'] ?? 10;
$items_per_page = (int) $items_per_page;
if ($items_per_page < 1) $items_per_page = 10;

$total_posts = count($all_posts);
$total_pages = ceil($total_posts / $items_per_page);

// Get posts for current page
$offset = ($current_page - 1) * $items_per_page;
$posts = array_slice($all_posts, $offset, $items_per_page);

// Handle delete action
$delete_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $post_id = $_POST['post_id'] ?? '';
    
    if (!empty($post_id)) {
        // Find the post
        $post_index = -1;
        foreach ($posts_data[$selected_post_type] as $index => $post) {
            if ($post['id'] === $post_id) {
                $post_index = $index;
                break;
            }
        }
        
        // Remove the post
        if ($post_index >= 0) {
            array_splice($posts_data[$selected_post_type], $post_index, 1);
            
            // Save changes
            if (write_json_file($posts_file, $posts_data)) {
                $delete_message = 'Post deleted successfully.';
                
                // Redirect to avoid resubmission
                header('Location: posts.php?type=' . urlencode($selected_post_type) . '&message=deleted');
                exit;
            } else {
                $delete_message = 'Failed to delete post. Check file permissions.';
            }
        } else {
            $delete_message = 'Post not found.';
        }
    }
}

// Build pagination URL
$pagination_url = 'posts.php?type=' . urlencode($selected_post_type) . '&per_page=' . $items_per_page . '&page=';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts - Admin Panel</title>
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
                    <h1 class="h2">
                        Posts: <?php echo htmlspecialchars($post_types[$selected_post_type]['name']); ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown me-2">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-filter"></i> Post Type
                            </button>
                            <ul class="dropdown-menu">
                                <?php foreach ($post_types as $slug => $post_type): ?>
                                    <li>
                                        <a class="dropdown-item <?php echo $slug === $selected_post_type ? 'active' : ''; ?>" 
                                           href="posts.php?type=<?php echo urlencode($slug); ?>">
                                            <?php echo htmlspecialchars($post_type['name']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <a href="post-edit.php?type=<?php echo urlencode($selected_post_type); ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg"></i> Add New
                        </a>
                    </div>
                </div>
                
                <?php if (isset($_GET['message']) && $_GET['message'] === 'deleted'): ?>
                    <div class="alert alert-success" role="alert">
                        Post deleted successfully.
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($delete_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($delete_message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Filter Posts</h5>
                        <form class="row g-3" method="get" action="posts.php">
                            <input type="hidden" name="type" value="<?php echo htmlspecialchars($selected_post_type); ?>">
                            
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Search posts...">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All</option>
                                    <option value="published">Published</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th scope="col">Title</th>
                                <th scope="col">Status</th>
                                <th scope="col">Date</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($posts)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No posts found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($post['title_en'] ?? $post['id']); ?></td>
                                        <td>
                                            <span class="badge <?php echo ($post['status'] ?? '') === 'published' ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($post['status'] ?? 'unknown')); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $date = $post['updated_at'] ?? $post['created_at'] ?? null;
                                            echo $date ? date('M d, Y', strtotime($date)) : 'Unknown';
                                            ?>
                                        </td>
                                        <td>
                                            <a href="post-edit.php?type=<?php echo urlencode($selected_post_type); ?>&id=<?php echo urlencode($post['id']); ?>" 
                                               class="btn btn-sm btn-outline-primary me-1">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $post['id']; ?>">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                            
                                            <!-- Delete confirmation modal -->
                                            <div class="modal fade" id="deleteModal<?php echo $post['id']; ?>" tabindex="-1" 
                                                 aria-labelledby="deleteModalLabel<?php echo $post['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $post['id']; ?>">Confirm Delete</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Are you sure you want to delete this post? This action cannot be undone.
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <form method="post" action="posts.php?type=<?php echo urlencode($selected_post_type); ?>">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
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
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $pagination_url . ($current_page - 1); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo $pagination_url . $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $pagination_url . ($current_page + 1); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <!-- Items per page selector -->
                <div class="text-center mb-4">
                    <div class="btn-group" role="group" aria-label="Items per page">
                        <a href="posts.php?type=<?php echo urlencode($selected_post_type); ?>&page=1&per_page=10" 
                           class="btn btn-sm <?php echo $items_per_page == 10 ? 'btn-primary' : 'btn-outline-primary'; ?>">10</a>
                        <a href="posts.php?type=<?php echo urlencode($selected_post_type); ?>&page=1&per_page=25" 
                           class="btn btn-sm <?php echo $items_per_page == 25 ? 'btn-primary' : 'btn-outline-primary'; ?>">25</a>
                        <a href="posts.php?type=<?php echo urlencode($selected_post_type); ?>&page=1&per_page=50" 
                           class="btn btn-sm <?php echo $items_per_page == 50 ? 'btn-primary' : 'btn-outline-primary'; ?>">50</a>
                        <a href="posts.php?type=<?php echo urlencode($selected_post_type); ?>&page=1&per_page=100" 
                           class="btn btn-sm <?php echo $items_per_page == 100 ? 'btn-primary' : 'btn-outline-primary'; ?>">100</a>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>