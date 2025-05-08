<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: simple_login.php');
    exit;
}

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Include necessary files
require_once '../includes/functions.php';

$username = $_SESSION['username'] ?? 'User';

// Load settings
$settings_file = '../storage/general_settings.json';
$settings = [];

if (file_exists($settings_file)) {
    $settings = read_json_file($settings_file);
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process site settings
    $site_title = $_POST['site_title'] ?? '';
    $site_description = $_POST['site_description'] ?? '';
    $site_url = $_POST['site_url'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $default_language = $_POST['default_language'] ?? 'en';
    $timezone = $_POST['timezone'] ?? 'UTC';
    
    // Update settings
    $settings['site_title'] = $site_title;
    $settings['site_description'] = $site_description;
    $settings['site_url'] = $site_url;
    $settings['admin_email'] = $admin_email;
    $settings['default_language'] = $default_language;
    $settings['timezone'] = $timezone;
    
    // Save settings
    if (write_json_file($settings_file, $settings)) {
        $success_message = 'Settings saved successfully.';
    } else {
        $error_message = 'Failed to save settings. Check file permissions.';
    }
}

// Get available languages
$languages_file = '../storage/lang_config.json';
$lang_config = [];
$languages = [];

if (file_exists($languages_file)) {
    $lang_config = read_json_file($languages_file);
    
    // Create a structured languages array for the template
    if (isset($lang_config['languages'])) {
        foreach ($lang_config['languages'] as $code) {
            $is_active = in_array($code, $lang_config['active_languages'] ?? []);
            $languages[$code] = [
                'code' => $code,
                'name' => strtoupper($code) === 'EN' ? 'English' : 
                         (strtoupper($code) === 'FR' ? 'French' : 
                         (strtoupper($code) === 'ES' ? 'Spanish' : 
                         (strtoupper($code) === 'DE' ? 'German' : ucfirst($code)))),
                'active' => $is_active
            ];
        }
    }
}

// Get available timezones
$timezones = DateTimeZone::listIdentifiers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
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
                    <h1 class="h2">Settings</h1>
                </div>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">General Settings</h5>
                        
                        <form method="post" action="settings.php">
                            <div class="mb-3">
                                <label for="site_title" class="form-label">Site Title</label>
                                <input type="text" class="form-control" id="site_title" name="site_title" 
                                       value="<?php echo htmlspecialchars($settings['site_title'] ?? 'Flat Headless CMS'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="site_description" class="form-label">Site Description</label>
                                <textarea class="form-control" id="site_description" name="site_description" rows="2"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="site_url" class="form-label">Site URL</label>
                                <input type="url" class="form-control" id="site_url" name="site_url" 
                                       value="<?php echo htmlspecialchars($settings['site_url'] ?? ''); ?>" placeholder="https://example.com">
                            </div>
                            
                            <div class="mb-3">
                                <label for="admin_email" class="form-label">Admin Email</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                       value="<?php echo htmlspecialchars($settings['admin_email'] ?? ''); ?>" placeholder="admin@example.com">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="default_language" class="form-label">Default Language</label>
                                        <select class="form-select" id="default_language" name="default_language">
                                            <option value="en" <?php echo ($settings['default_language'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                                            <option value="fr" <?php echo ($settings['default_language'] ?? '') === 'fr' ? 'selected' : ''; ?>>French</option>
                                            <?php foreach ($languages as $code => $language): ?>
                                                <?php if ($code !== 'en' && $code !== 'fr'): ?>
                                                    <option value="<?php echo htmlspecialchars($code); ?>" 
                                                            <?php echo ($settings['default_language'] ?? '') === $code ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($language['name']); ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="timezone" class="form-label">Timezone</label>
                                        <select class="form-select" id="timezone" name="timezone">
                                            <?php foreach ($timezones as $tz): ?>
                                                <option value="<?php echo htmlspecialchars($tz); ?>" 
                                                        <?php echo ($settings['timezone'] ?? 'UTC') === $tz ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($tz); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </form>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Language Settings</h5>
                        <p class="card-text">Configure available languages for your content.</p>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Language Code</th>
                                        <th>Language Name</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>en</td>
                                        <td>English</td>
                                        <td><span class="badge bg-success">Active</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-secondary" disabled>Default</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>fr</td>
                                        <td>French</td>
                                        <td><span class="badge bg-success">Active</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger">Disable</button>
                                        </td>
                                    </tr>
                                    <?php foreach ($languages as $code => $language): ?>
                                        <?php if ($code !== 'en' && $code !== 'fr'): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($code); ?></td>
                                                <td><?php echo htmlspecialchars($language['name']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $language['active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                        <?php echo $language['active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-<?php echo $language['active'] ? 'danger' : 'success'; ?>">
                                                        <?php echo $language['active'] ? 'Disable' : 'Enable'; ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addLanguageModal">
                            <i class="bi bi-plus-lg"></i> Add Language
                        </button>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Add Language Modal -->
    <div class="modal fade" id="addLanguageModal" tabindex="-1" aria-labelledby="addLanguageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLanguageModalLabel">Add New Language</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addLanguageForm">
                        <div class="mb-3">
                            <label for="language_code" class="form-label">Language Code</label>
                            <input type="text" class="form-control" id="language_code" name="language_code" 
                                   placeholder="e.g., es, de, it" required>
                            <div class="form-text">Use the ISO 639-1 two-letter code for the language.</div>
                        </div>
                        <div class="mb-3">
                            <label for="language_name" class="form-label">Language Name</label>
                            <input type="text" class="form-control" id="language_name" name="language_name" 
                                   placeholder="e.g., Spanish, German, Italian" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Add Language</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>