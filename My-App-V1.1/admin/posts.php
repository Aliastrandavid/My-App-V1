<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: simple_login.php');
    exit;
}

// Include necessary files
require_once '../includes/config.php';
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

// Get posts from the specific JSON file for this post type
$all_posts = [];
$posts_file = "../storage/{$selected_post_type}.json";

if (file_exists($posts_file)) {
    $posts_data = read_json_file($posts_file);

    // Check for posts in the correct structure
    if (isset($posts_data['posts']) && is_array($posts_data['posts'])) {
        $all_posts = $posts_data['posts'];
    }
} else {
    // Fallback to legacy posts.json if needed
    $legacy_file = "../storage/posts.json";
    if (file_exists($legacy_file)) {
        $legacy_data = read_json_file($legacy_file);
        if (isset($legacy_data[$selected_post_type]) && is_array($legacy_data[$selected_post_type])) {
            $all_posts = $legacy_data[$selected_post_type];
        }
    }
}

foreach ($all_posts as $key=>$the_post) {
    if(isset($_GET['status'])) {
        if(strlen($_GET['status']) >0) {
            if($the_post['status'] == $_GET['status']) {

            } else {
                unset($all_posts[$key]);
            }
        }
    }
    if(isset($_GET['search'])) {
        if(strlen($_GET['search']) >0) {
            if(str_contains($the_post['title_en'],$_GET['search']) || str_contains($the_post['title_fr'], $_GET['search'])) {
                
            } else {
                unset($all_posts[$key]);
            }
        }
    }
    
}

// Sort posts by date (newest first)
if (!empty($all_posts)) {
    usort($all_posts, function($a, $b) {
        $date_a = strtotime($a['date'] ?? $a['updated_at'] ?? $a['created_at'] ?? '0');
        $date_b = strtotime($b['date'] ?? $b['updated_at'] ?? $b['created_at'] ?? '0');
        return $date_b - $date_a;
    });
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
        $posts_data = read_json_file($posts_file);
        // Find and remove the post
        foreach ($posts_data['posts'] as $key => $post) {
            if ($post['id'] === $post_id) {
                unset($posts_data['posts'][$key]);
                break;
            }
        }
        $posts_data['posts'] = array_values($posts_data['posts']); // Reindex array

        // Save changes
        if (write_json_file($posts_file, $posts_data)) {
            header('Location: posts.php?type=' . urlencode($selected_post_type) . '&message=deleted');
            exit;
        } else {
            $delete_message = 'Failed to delete post. Check file permissions.';
        }
    }
}
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
                        Posts: <?php echo htmlspecialchars($post_types[$selected_post_type]['name_' . DEFAULT_LANGUAGE] ?? ''); ?>
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
                                            <?php echo htmlspecialchars($post_type['name_' . DEFAULT_LANGUAGE] ?? ''); ?>
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
<!-- Filter Posts -->
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
                                        <td><?php echo htmlspecialchars($post['title_' . DEFAULT_LANGUAGE] ?? ''); ?></td>
                                        <td>
                                            <span class="badge <?php echo ($post['status'] ?? '') === 'published' ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($post['status'] ?? 'unknown')); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $date = $post['updated_at'] ?? $post['created_at'] ?? $post['date'] ?? null;
                                            echo $date ? date('M d, Y', strtotime($date)) : 'Unknown';
                                            ?>
                                        </td>
                                        <td>
                                            <a href="post-edit.php?type=<?php echo urlencode($selected_post_type); ?>&id=<?php echo urlencode($post['id'] ?? ''); ?>" 
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
                <!-- Items per page selector -->
                <?php
                $nbofpost = 0;
                $posts_data = read_json_file($posts_file);
                // Find and remove the post
                foreach ($posts_data['posts'] as $key => $post) {
                    $nbofpost++;
                }
                ?>
                <div class="text-center mb-4">
                    <div class="btn-group" role="group" aria-label="Items per page">
                        <?php $nbpages = ($nbofpost/10)+1;?>
                        <?php for ($page = 1; $page <= $nbpages; $page+=1) { ?>
                        &nbsp<a href="posts.php?page=<?=$page?>&status=<?=(isset($_GET['status']) ? $_GET['status'] : "")?>" 
                           class="btn btn-sm  <?php echo $items_per_page == 10 ? 'btn-primary' : 'btn-outline-primary'; ?>"><?=$page?></a>
                                                         <?php } ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>