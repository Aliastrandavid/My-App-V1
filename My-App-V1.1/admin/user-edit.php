<?php
ob_start ();
session_start();

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/users.php';


// Check if user is logged in and has admin privileges
if (!is_logged_in() || !is_admin()) {
    header('Location: login.php');
    exit;
}


/**
 * User Edit Page
 * 
 * Create or edit user accounts
 */

// Get user ID from query parameters (if editing)
$user_id = $_GET['id'] ?? '';
$is_editing = !empty($user_id);
$is_current_user = $is_editing && ($user_id === ($_SESSION['user_id'] ?? ''));

// Initialize user data
$user = [
    'username' => '',
    'name' => '',
    'email' => '',
    'role' => 'contributor',
    'created_at' => date('Y-m-d H:i:s')
];


// If editing, load existing user data
if ($is_editing) {
    $loaded_user = get_user_by_id($user_id);
    
    if ($loaded_user) {
        $user = $loaded_user;
    } else {
        $_SESSION['error_message'] = 'User not found';
        header('Location: users.php');
        exit;
    }
}

// Get available roles
$roles = get_available_roles();


// Process form submission
$errors = [];
$success_message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = $_POST['username'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? 'contributor';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validate username
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores';
    } elseif (!$is_editing && username_exists($username)) {
        $errors[] = 'Username already exists';
    }

    // Validate name
    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    // Validate email
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    } elseif (!$is_editing && email_exists($email)) {
        $errors[] = 'Email already exists';
    }

    // Validate password if adding new user or changing password
    if (!$is_editing || !empty($password)) {
        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        } elseif ($password !== $password_confirm) {
            $errors[] = 'Passwords do not match';
        }
    }

    // Prevent non-admin users from being promoted to admin
    if ($is_editing && $user['role'] !== 'admin' && $role === 'admin' && !is_admin()) {
        $errors[] = 'You cannot promote users to admin role';
    }

    // Prevent admin from removing their own admin rights
    if ($is_current_user && $user['role'] === 'admin' && $role !== 'admin') {
        $errors[] = 'You cannot remove your own admin privileges';
    }

    // If no errors, save the user
    if (empty($errors)) {
        $user_data = [
            'username' => $username,
            'name' => $name,
            'email' => $email,
            'role' => $role
        ];

        // Add password if provided or creating new user
        if (!$is_editing || !empty($password)) {
            $user_data['password'] = $password;
        }

        // For new users, generate an ID
        if (!$is_editing) {
            $user_data['id'] = generate_id();
            $user_data['created_at'] = date('Y-m-d H:i:s');
        }

        // Save user
        if ($is_editing) {
            if (update_user($user_id, $user_data)) {
                $success_message = 'User updated successfully.';

                // Reload user data
                $user = get_user_by_id($user_id);
            } else {
                $errors[] = 'Failed to update user. Please try again.';
            }
        } else {
            if (create_user($user_data)) {
                // Redirect to users list
                header('Location: users.php?message=created');
                exit;
            } else {
                $errors[] = 'Failed to create user. Please try again.';
            }
        }
    }
}

// Include header - this will handle all the HTML head elements
$html = ob_get_clean ();
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $is_editing ? 'Edit' : 'Add New'; ?> User</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="users.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Users
                    </a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                                           <?php echo $is_editing ? 'readonly' : ''; ?> required>
                                    <?php if ($is_editing): ?>
                                        <div class="form-text">Username cannot be changed after creation.</div>
                                    <?php else: ?>
                                        <div class="form-text">Can only contain letters, numbers, and underscores.</div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                    <select class="form-select" id="role" name="role" required>
                                        <?php foreach ($roles as $role_key => $role_name): ?>
                                            <option value="<?php echo htmlspecialchars($role_key); ?>" 
                                                    <?php echo $user['role'] === $role_key ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($role_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        Password <?php echo $is_editing ? '' : '<span class="text-danger">*</span>'; ?>
                                    </label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           <?php echo $is_editing ? '' : 'required'; ?>>
                                    <?php if ($is_editing): ?>
                                        <div class="form-text">Leave blank to keep current password.</div>
                                    <?php else: ?>
                                        <div class="form-text">Must be at least 8 characters long.</div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirm" class="form-label">
                                        Confirm Password <?php echo $is_editing ? '' : '<span class="text-danger">*</span>'; ?>
                                    </label>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                           <?php echo $is_editing ? '' : 'required'; ?>>
                                </div>

                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo $is_editing ? 'Update User' : 'Create User'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            User Information
                        </div>
                        <div class="card-body">
                            <?php if ($is_editing): ?>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>User ID</span>
                                        <span class="text-muted"><?php echo htmlspecialchars($user['id']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Date Created</span>
                                        <span class="text-muted">
                                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                        </span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Current Role</span>
                                        <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                            <?php echo htmlspecialchars($roles[$user['role']] ?? $user['role']); ?>
                                        </span>
                                    </li>
                                    <?php if ($is_current_user): ?>
                                        <li class="list-group-item text-center text-primary">
                                            <i class="bi bi-info-circle"></i> This is your account
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            <?php else: ?>
                                <p class="card-text">
                                    Create a new user account by filling out the form.
                                </p>
                                <p class="card-text">
                                    All users can log in to the CMS and access their assigned features based on their role.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            Role Permissions
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <h6 class="card-subtitle mb-2 text-danger">Admin</h6>
                                    <p class="card-text small">Full access to all features and settings of the CMS.</p>
                                </li>
                                <li class="list-group-item">
                                    <h6 class="card-subtitle mb-2 text-primary">Editor</h6>
                                    <p class="card-text small">Can create, edit, publish, and delete all content, but cannot manage users or system settings.</p>
                                </li>
                                <li class="list-group-item">
                                    <h6 class="card-subtitle mb-2 text-primary">Author</h6>
                                    <p class="card-text small">Can create, edit, and publish their own content, but cannot delete content or access system settings.</p>
                                </li>
                                <li class="list-group-item">
                                    <h6 class="card-subtitle mb-2 text-primary">Contributor</h6>
                                    <p class="card-text small">Can create and edit their own content, but cannot publish content.</p>
                                </li>
                            </ul>
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
</body>
</html>