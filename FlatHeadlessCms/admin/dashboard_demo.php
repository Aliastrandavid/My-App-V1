<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: simple_login.php');
    exit;
}

$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Posts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Media</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Settings</a>
                    </li>
                </ul>
                <span class="navbar-text me-2">
                    Welcome, <?php echo htmlspecialchars($username); ?>
                </span>
                <a href="simple_logout.php" class="btn btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h2>Dashboard</h2>
                    </div>
                    <div class="card-body">
                        <h5>Welcome to the Admin Dashboard!</h5>
                        <p>This is a simple demo of the admin panel.</p>
                        
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="card bg-primary text-white mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title">Posts</h5>
                                        <p class="card-text display-4">12</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success text-white mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title">Categories</h5>
                                        <p class="card-text display-4">5</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-warning text-dark mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title">Media</h5>
                                        <p class="card-text display-4">25</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h4>Recent Activity</h4>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Activity</th>
                                        <th>User</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>May 7, 2025</td>
                                        <td>Login</td>
                                        <td>admin</td>
                                    </tr>
                                    <tr>
                                        <td>May 6, 2025</td>
                                        <td>Post created</td>
                                        <td>editor</td>
                                    </tr>
                                    <tr>
                                        <td>May 5, 2025</td>
                                        <td>Media uploaded</td>
                                        <td>admin</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>