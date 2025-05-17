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
require_once '../includes/languages.php';

if (!defined('DEFAULT_LANGUAGE')) {
    define('DEFAULT_LANGUAGE', 'en');
}

$username = $_SESSION['username'] ?? 'User';
$post_types = get_post_types();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Types - Admin Panel</title>
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
                    <h1 class="h2">Post Types</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="post-type-edit.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg"></i> Add New Post Type
                        </a>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Slug</th>
                                <th>Taxonomies</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($post_types as $slug => $post_type): ?>
                            <tr>
                                <td>
                                    <i class="bi <?php echo $post_type['icon'] ?? 'bi-file-earmark'; ?>"></i>
                                    <?php echo htmlspecialchars($post_type['name_' . DEFAULT_LANGUAGE] ?? $post_type['name'] ?? ''); ?>
                                </td>
                                <td><?php echo htmlspecialchars($post_type['description_' . DEFAULT_LANGUAGE] ?? $post_type['description'] ?? ''); ?></td>
                                <td><code><?php echo htmlspecialchars($slug); ?></code></td>
                                <td>
                                    <?php if (!empty($post_type['taxonomies'])): ?>
                                        <?php foreach ($post_type['taxonomies'] as $taxonomy): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($taxonomy); ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="post-type-edit.php?slug=<?php echo urlencode($slug); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <a href="posts.php?type=<?php echo urlencode($slug); ?>" class="btn btn-sm btn-outline-secondary">View Posts</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>