<?php
/**
 * Media library functions
 */

require_once 'config.php';
require_once 'functions.php';

/**
 * Get all media items
 *
 * @return array
 */
function get_all_media() {
    $media_file = STORAGE_PATH . '/media_library.json';
    
    if (file_exists($media_file)) {
        $media = read_json_file($media_file);
        
        if (is_array($media)) {
            return $media;
        }
    }
    
    return [];
}

/**
 * Get media items with filtering and pagination
 *
 * @param array $args
 * @return array
 */
function get_media_items($args = []) {
    $media = get_all_media();
    
    $defaults = [
        'search' => '',
        'type' => '',
        'sort' => 'date_desc',
        'page' => 1,
        'per_page' => 12
    ];
    
    $args = array_merge($defaults, $args);
    $page = max(1, intval($args['page']));
    $per_page = max(0, intval($args['per_page']));
    
    // Apply filters
    if (!empty($args['search'])) {
        $search = strtolower($args['search']);
        $media = array_filter($media, function($item) use ($search) {
            return strpos(strtolower($item['name']), $search) !== false ||
                   strpos(strtolower($item['alt']), $search) !== false ||
                   strpos(strtolower($item['description']), $search) !== false;
        });
    }
    
    if (!empty($args['type'])) {
        $media = array_filter($media, function($item) use ($args) {
            return $item['type'] === $args['type'];
        });
    }
    
    // Apply sorting
    switch ($args['sort']) {
        case 'date_asc':
            usort($media, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });
            break;
        
        case 'date_desc':
            usort($media, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            break;
        
        case 'name_asc':
            usort($media, function($a, $b) {
                return strcmp(strtolower($a['name']), strtolower($b['name']));
            });
            break;
        
        case 'name_desc':
            usort($media, function($a, $b) {
                return strcmp(strtolower($b['name']), strtolower($a['name']));
            });
            break;
    }
    
    // Apply pagination
    if ($per_page > 0) {
        $offset = ($page - 1) * $per_page;
        $media = array_slice($media, $offset, $per_page);
    }
    
    return $media;
}

/**
 * Count media items
 *
 * @param array $args
 * @return int
 */
function count_media_items($args = []) {
    $defaults = [
        'search' => '',
        'type' => ''
    ];
    
    $args = array_merge($defaults, $args);
    $args['per_page'] = 0;  // No pagination for counting
    
    $media = get_media_items($args);
    return count($media);
}

/**
 * Get media by ID
 *
 * @param string $id
 * @return array|null
 */
function get_media_by_id($id) {
    $media = get_all_media();
    
    foreach ($media as $item) {
        if ($item['id'] === $id) {
            return $item;
        }
    }
    
    return null;
}

/**
 * Upload a media file
 *
 * @param array $file
 * @param string $name
 * @param string $alt
 * @param string $description
 * @return array
 */
function upload_media($file, $name = '', $alt = '', $description = '') {
    // Validate file
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    // Check for errors
    if ($file['error'] !== 0) {
        $error_message = 'Upload error: ';
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $error_message .= 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message .= 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message .= 'The uploaded file was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message .= 'No file was uploaded';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message .= 'Missing a temporary folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message .= 'Failed to write file to disk';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message .= 'A PHP extension stopped the file upload';
                break;
            default:
                $error_message .= 'Unknown error';
        }
        
        return ['success' => false, 'message' => $error_message];
    }
    
    // Check file size
    $max_size = MAX_UPLOAD_SIZE;
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size exceeds limit of ' . format_file_size($max_size)];
    }
    
    // Get file info
    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension']);
    
    // Check file type
    if (!in_array($extension, ALLOWED_FILE_TYPES)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    // Create uploads directory if it doesn't exist
    if (!file_exists(UPLOADS_PATH)) {
        mkdir(UPLOADS_PATH, 0755, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '.' . $extension;
    $upload_path = UPLOADS_PATH . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
    
    // Set media name if not provided
    if (empty($name)) {
        $name = $file_info['filename'];
    }
    
    // Create media item
    $media_item = [
        'id' => generate_id(),
        'name' => $name,
        'filename' => $filename,
        'url' => get_site_url() . '/uploads/' . $filename,
        'alt' => $alt,
        'description' => $description,
        'type' => $extension,
        'size' => $file['size'],
        'date' => date('Y-m-d H:i:s')
    ];
    
    // Add to media library
    $media_library = get_all_media();
    $media_library[] = $media_item;
    
    $media_file = STORAGE_PATH . '/media_library.json';
    
    if (write_json_file($media_file, $media_library)) {
        return ['success' => true, 'media' => $media_item];
    } else {
        // Delete the uploaded file if we couldn't update the media library
        unlink($upload_path);
        return ['success' => false, 'message' => 'Failed to update media library'];
    }
}

/**
 * Update media information
 *
 * @param string $media_id
 * @param string $name
 * @param string $alt
 * @param string $description
 * @return bool
 */
function update_media_info($media_id, $name, $alt, $description) {
    $media_file = STORAGE_PATH . '/media_library.json';
    $media_library = get_all_media();
    
    foreach ($media_library as $index => $item) {
        if ($item['id'] === $media_id) {
            $media_library[$index]['name'] = $name;
            $media_library[$index]['alt'] = $alt;
            $media_library[$index]['description'] = $description;
            
            return write_json_file($media_file, $media_library);
        }
    }
    
    return false;
}

/**
 * Delete media
 *
 * @param string $media_id
 * @return bool
 */
function delete_media($media_id) {
    $media_file = STORAGE_PATH . '/media_library.json';
    $media_library = get_all_media();
    $media_to_delete = null;
    
    // Find media item to delete
    foreach ($media_library as $index => $item) {
        if ($item['id'] === $media_id) {
            $media_to_delete = $item;
            unset($media_library[$index]);
            break;
        }
    }
    
    if ($media_to_delete === null) {
        return false;
    }
    
    // Delete file
    $file_path = UPLOADS_PATH . '/' . $media_to_delete['filename'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Update media library
    return write_json_file($media_file, array_values($media_library));
}

/**
 * Get media types
 *
 * @return array
 */
function get_media_types() {
    return [
        'jpg' => 'JPEG Image',
        'jpeg' => 'JPEG Image',
        'png' => 'PNG Image',
        'gif' => 'GIF Image',
        'svg' => 'SVG Image',
        'pdf' => 'PDF Document',
        'doc' => 'Word Document',
        'docx' => 'Word Document',
        'xls' => 'Excel Spreadsheet',
        'xlsx' => 'Excel Spreadsheet',
        'ppt' => 'PowerPoint Presentation',
        'pptx' => 'PowerPoint Presentation',
        'txt' => 'Text File',
        'zip' => 'ZIP Archive'
    ];
}
