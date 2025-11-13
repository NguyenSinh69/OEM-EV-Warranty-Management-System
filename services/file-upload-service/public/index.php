<?php
/*
===============================================
OEM EV Warranty Management System
File Upload Service - Port 8006
===============================================
Handles file uploads for warranty claims and vehicle documentation
- Image uploads for warranty claims
- Document uploads for vehicles
- File validation and security
- Storage management
===============================================
*/

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);

// Ensure upload directory exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Create subdirectories for organization
$subdirs = ['claims', 'vehicles', 'temp'];
foreach ($subdirs as $subdir) {
    $path = UPLOAD_DIR . $subdir;
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
    }
}

// Get request URI and method
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];

// Remove base path and get route
$route = str_replace('/api/upload', '', $request_uri);

// Helper function to generate unique filename
function generateUniqueFilename($originalName) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $basename = pathinfo($originalName, PATHINFO_FILENAME);
    $basename = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename);
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    return $basename . '_' . $timestamp . '_' . $random . '.' . $extension;
}

// Helper function to validate file
function validateFile($file) {
    $errors = [];
    
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'No file uploaded';
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = 'File size exceeds maximum allowed size (5MB)';
    }
    
    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', ALLOWED_EXTENSIONS);
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        $errors[] = 'Invalid file type detected';
    }
    
    return $errors;
}

// ===============================================
// ROUTE: Health Check
// ===============================================
if ($route === '/health' && $request_method === 'GET') {
    echo json_encode([
        'status' => 'healthy',
        'service' => 'File Upload Service',
        'timestamp' => date('Y-m-d H:i:s'),
        'upload_dir' => UPLOAD_DIR,
        'max_file_size' => MAX_FILE_SIZE,
        'allowed_extensions' => ALLOWED_EXTENSIONS
    ]);
    exit();
}

// ===============================================
// ROUTE: Upload File
// ===============================================
if ($route === '/file' && $request_method === 'POST') {
    try {
        // Check if file was uploaded
        if (!isset($_FILES['file'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'No file provided'
            ]);
            exit();
        }
        
        $file = $_FILES['file'];
        $category = $_POST['category'] ?? 'temp'; // claims, vehicles, or temp
        
        // Validate file
        $errors = validateFile($file);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'File validation failed',
                'errors' => $errors
            ]);
            exit();
        }
        
        // Generate unique filename
        $uniqueFilename = generateUniqueFilename($file['name']);
        
        // Determine upload path
        $uploadPath = UPLOAD_DIR . $category . '/' . $uniqueFilename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        // Get file info
        $fileInfo = [
            'filename' => $uniqueFilename,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'mime_type' => mime_content_type($uploadPath),
            'category' => $category,
            'url' => '/api/upload/file/' . $category . '/' . $uniqueFilename,
            'uploaded_at' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => $fileInfo
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Upload failed: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ===============================================
// ROUTE: Upload Multiple Files
// ===============================================
if ($route === '/files' && $request_method === 'POST') {
    try {
        // Check if files were uploaded
        if (!isset($_FILES['files'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'No files provided'
            ]);
            exit();
        }
        
        $files = $_FILES['files'];
        $category = $_POST['category'] ?? 'temp';
        $uploadedFiles = [];
        $errors = [];
        
        // Handle multiple files
        $fileCount = count($files['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            // Validate file
            $validationErrors = validateFile($file);
            if (!empty($validationErrors)) {
                $errors[] = [
                    'file' => $file['name'],
                    'errors' => $validationErrors
                ];
                continue;
            }
            
            // Generate unique filename
            $uniqueFilename = generateUniqueFilename($file['name']);
            
            // Determine upload path
            $uploadPath = UPLOAD_DIR . $category . '/' . $uniqueFilename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $uploadedFiles[] = [
                    'filename' => $uniqueFilename,
                    'original_name' => $file['name'],
                    'size' => $file['size'],
                    'mime_type' => mime_content_type($uploadPath),
                    'category' => $category,
                    'url' => '/api/upload/file/' . $category . '/' . $uniqueFilename,
                    'uploaded_at' => date('Y-m-d H:i:s')
                ];
            } else {
                $errors[] = [
                    'file' => $file['name'],
                    'errors' => ['Failed to move uploaded file']
                ];
            }
        }
        
        echo json_encode([
            'success' => count($uploadedFiles) > 0,
            'message' => count($uploadedFiles) . ' file(s) uploaded successfully',
            'data' => $uploadedFiles,
            'errors' => $errors
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Upload failed: ' . $e->getMessage()
        ]);
    }
    exit();
}

// ===============================================
// ROUTE: Get File
// ===============================================
if (preg_match('/^\/file\/([a-z]+)\/(.+)$/', $route, $matches) && $request_method === 'GET') {
    $category = $matches[1];
    $filename = $matches[2];
    
    // Sanitize filename to prevent directory traversal
    $filename = basename($filename);
    $filePath = UPLOAD_DIR . $category . '/' . $filename;
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'File not found'
        ]);
        exit();
    }
    
    // Get MIME type
    $mimeType = mime_content_type($filePath);
    
    // Set headers for file download/display
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: inline; filename="' . $filename . '"');
    
    // Output file
    readfile($filePath);
    exit();
}

// ===============================================
// ROUTE: Delete File
// ===============================================
if (preg_match('/^\/file\/([a-z]+)\/(.+)$/', $route, $matches) && $request_method === 'DELETE') {
    $category = $matches[1];
    $filename = $matches[2];
    
    // Sanitize filename
    $filename = basename($filename);
    $filePath = UPLOAD_DIR . $category . '/' . $filename;
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'File not found'
        ]);
        exit();
    }
    
    // Delete file
    if (unlink($filePath)) {
        echo json_encode([
            'success' => true,
            'message' => 'File deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete file'
        ]);
    }
    exit();
}

// ===============================================
// ROUTE: List Files
// ===============================================
if ($route === '/files' && $request_method === 'GET') {
    $category = $_GET['category'] ?? 'temp';
    $dirPath = UPLOAD_DIR . $category;
    
    if (!is_dir($dirPath)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Category not found'
        ]);
        exit();
    }
    
    $files = [];
    $items = scandir($dirPath);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $filePath = $dirPath . '/' . $item;
        if (is_file($filePath)) {
            $files[] = [
                'filename' => $item,
                'size' => filesize($filePath),
                'mime_type' => mime_content_type($filePath),
                'url' => '/api/upload/file/' . $category . '/' . $item,
                'modified_at' => date('Y-m-d H:i:s', filemtime($filePath))
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $files,
        'count' => count($files)
    ]);
    exit();
}

// ===============================================
// DEFAULT ROUTE
// ===============================================
http_response_code(404);
echo json_encode([
    'error' => 'Route not found',
    'available_routes' => [
        'GET /api/upload/health' => 'Health check',
        'POST /api/upload/file' => 'Upload single file',
        'POST /api/upload/files' => 'Upload multiple files',
        'GET /api/upload/file/{category}/{filename}' => 'Get file',
        'DELETE /api/upload/file/{category}/{filename}' => 'Delete file',
        'GET /api/upload/files?category={category}' => 'List files'
    ]
]);
?>
