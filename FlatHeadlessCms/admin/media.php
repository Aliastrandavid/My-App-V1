<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: simple_login.php');
    exit;
}

// Include necessary files
require_once '../includes/functions.php';

$username = $_SESSION['username'] ?? 'User';

// Check for file upload
$upload_message = '';
$upload_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_file'])) {
    $upload_dir = '../uploads/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['media_file'];
    
    // Check for errors
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Generate unique filename
        $filename = uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $destination = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $upload_success = true;
            $upload_message = 'File uploaded successfully.';
        } else {
            $upload_message = 'Failed to move uploaded file.';
        }
    } else {
        // Handle upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $upload_message = 'The file is too large.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $upload_message = 'The file was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $upload_message = 'No file was uploaded.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $upload_message = 'Missing a temporary folder.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $upload_message = 'Failed to write file to disk.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $upload_message = 'A PHP extension stopped the file upload.';
                break;
            default:
                $upload_message = 'Unknown error occurred.';
                break;
        }
    }
}

// Get all media files
$upload_dir = '../uploads/';
$media_files = [];

if (file_exists($upload_dir) && is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && !is_dir($upload_dir . $file)) {
            $file_path = $upload_dir . $file;
            $media_files[] = [
                'name' => $file,
                'path' => '/uploads/' . $file,
                'size' => filesize($file_path),
                'type' => mime_content_type($file_path),
                'date' => filemtime($file_path)
            ];
        }
    }
    
    // Sort by date (newest first)
    usort($media_files, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Library - Admin Panel</title>
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
                    <h1 class="h2">Media Library</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="bi bi-upload"></i> Upload New Media
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($upload_message)): ?>
                    <div class="alert alert-<?php echo $upload_success ? 'success' : 'danger'; ?>" role="alert">
                        <?php echo htmlspecialchars($upload_message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <?php if (empty($media_files)): ?>
                        <div class="col-12">
                            <div class="alert alert-info" role="alert">
                                No media files found. Upload files using the button above.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($media_files as $file): ?>
                            <div class="col-md-3 mb-4">
                                <div class="card h-100">
                                    <div class="media-preview">
                                        <?php if (strpos($file['type'], 'image/') === 0): ?>
                                            <img src="<?php echo htmlspecialchars($file['path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($file['name']); ?>">
                                        <?php else: ?>
                                            <div class="file-icon">
                                                <i class="bi bi-file-earmark"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($file['name']); ?></h5>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                Type: <?php echo htmlspecialchars($file['type']); ?><br>
                                                Size: <?php echo format_file_size($file['size']); ?><br>
                                                Date: <?php echo date('M d, Y', $file['date']); ?>
                                            </small>
                                        </p>
                                    </div>
                                    <div class="card-footer">
                                        <div class="d-flex justify-content-between">
                                            <a href="<?php echo htmlspecialchars($file['path']); ?>" class="btn btn-sm btn-outline-primary" target="_blank">View</a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmDelete('<?php echo htmlspecialchars($file['name']); ?>')">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="media.php" method="post" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadModalLabel">Upload Media</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="media_file" class="form-label">Select File</label>
                            <input class="form-control" type="file" id="media_file" name="media_file" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(filename) {
            if (confirm('Are you sure you want to delete ' + filename + '?')) {
                // TODO: Implement delete functionality
                alert('Delete functionality will be implemented soon.');
            }
        }
    </script>
</body>
</html>