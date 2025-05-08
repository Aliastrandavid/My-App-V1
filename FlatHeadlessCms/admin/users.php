<?php
/**
 * Users Management Page
 * 
 * List, search, and manage user accounts
 */

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/users.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has admin privileges
if (!is_logged_in() || !is_admin()) {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $user_id = $_POST['user_id'] ?? '';
    
    // Prevent admin from deleting their own account
    if ($user_id === $_SESSION['user_id']) {
        $error_message = 'You cannot delete your own account.';
    } elseif (!empty($user_id)) {
        if (delete_user($user_id)) {
            $success_message = 'User deleted successfully.';
        } else {
            $error_message = 'Failed to delete user.';
        }
    }
}

// Process filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'username_asc';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

// Get users with filters
$users = get_users([
    'search' => $search,
    'role' => $role,
    'sort' => $sort,
    'page' => $page,
    'per_page' => $per_page
]);

// Get total users for pagination
$total_users = count_users([
    'search' => $search,
    'role' => $role
]);

// Calculate total pages
$total_pages = ceil($total_users / $per_page);

// Get available roles
$roles = get_available_roles();

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
                <h1 class="h2">Users</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="user-edit.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-person-plus"></i> Add New User
                    </a>
                </div>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form action="users.php" method="get" class="row g-3">
                        <div class="col-md-5">
                            <div class="input-group">
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <select class="form-select" id="role" name="role" onchange="this.form.submit()">
                                <option value="">All Roles</option>
                                <?php foreach ($roles as $role_key => $role_name): ?>
                                    <option value="<?php echo htmlspecialchars($role_key); ?>" 
                                            <?php echo $role === $role_key ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($role_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <select class="form-select" id="sort" name="sort" onchange="this.form.submit()">
                                <option value="username_asc" <?php echo $sort === 'username_asc' ? 'selected' : ''; ?>>
                                    Username (A-Z)
                                </option>
                                <option value="username_desc" <?php echo $sort === 'username_desc' ? 'selected' : ''; ?>>
                                    Username (Z-A)
                                </option>
                                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>
                                    Name (A-Z)
                                </option>
                                <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>
                                    Name (Z-A)
                                </option>
                                <option value="date_desc" <?php echo $sort === 'date_desc' ? 'selected' : ''; ?>>
                                    Newest First
                                </option>
                                <option value="date_asc" <?php echo $sort === 'date_asc' ? 'selected' : ''; ?>>
                                    Oldest First
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <select class="form-select" id="per_page" name="per_page" onchange="this.form.submit()">
                                <option value="10" <?php echo $per_page === 10 ? 'selected' : ''; ?>>10 per page</option>
                                <option value="25" <?php echo $per_page === 25 ? 'selected' : ''; ?>>25 per page</option>
                                <option value="50" <?php echo $per_page === 50 ? 'selected' : ''; ?>>50 per page</option>
                                <option value="100" <?php echo $per_page === 100 ? 'selected' : ''; ?>>100 per page</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Users table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                            <?php echo htmlspecialchars($roles[$user['role']] ?? $user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['date_created'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="user-edit.php?id=<?php echo urlencode($user['id']); ?>" class="btn btn-outline-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $user['id']; ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Delete modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1" 
                                             aria-labelledby="deleteModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $user['id']; ?>">
                                                            Confirm Delete
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to delete this user?</p>
                                                        <p class="fw-bold"><?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['name']); ?>)</p>
                                                        <p class="text-danger">This action cannot be undone.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form action="users.php" method="post">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
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
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&sort=<?php echo urlencode($sort); ?>&per_page=<?php echo $per_page; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '&role=' . urlencode($role) . '&sort=' . urlencode($sort) . '&per_page=' . $per_page . '">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '&search=' . urlencode($search) . '&role=' . urlencode($role) . '&sort=' . urlencode($sort) . '&per_page=' . $per_page . '">' . $i . '</a></li>';
                        }
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&search=' . urlencode($search) . '&role=' . urlencode($role) . '&sort=' . urlencode($sort) . '&per_page=' . $per_page . '">' . $total_pages . '</a></li>';
                        }
                        ?>
                        
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&sort=<?php echo urlencode($sort); ?>&per_page=<?php echo $per_page; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
            
            <div class="card mt-4">
                <div class="card-header">
                    User Roles
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-group">
                                <?php foreach ($roles as $role_key => $role_name): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge <?php echo $role_key === 'admin' ? 'bg-danger' : 'bg-primary'; ?> me-2">
                                                <?php echo htmlspecialchars($role_name); ?>
                                            </span>
                                            <span class="text-muted"><?php echo htmlspecialchars($role_key); ?></span>
                                        </div>
                                        <span class="badge bg-secondary rounded-pill">
                                            <?php echo count_users(['role' => $role_key]); ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5 class="card-title">Role Permissions</h5>
                            <p class="card-text">
                                <strong>Admin:</strong> Full access to all features and settings of the CMS.
                            </p>
                            <p class="card-text">
                                <strong>Editor:</strong> Can create, edit, publish, and delete all content, but cannot manage users or system settings.
                            </p>
                            <p class="card-text">
                                <strong>Author:</strong> Can create, edit, and publish their own content, but cannot delete content or access system settings.
                            </p>
                            <p class="card-text">
                                <strong>Contributor:</strong> Can create and edit their own content, but cannot publish content.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>
