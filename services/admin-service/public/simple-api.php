<?php
// Simple test authentication with JSON file storage
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Data file paths
$usersDataFile = __DIR__ . '/data/users.json';
$dataDir = __DIR__ . '/data';

// Ensure data directory exists
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

// Helper functions for file operations
function loadUsersFromFile($filePath) {
    if (!file_exists($filePath)) {
        // Create default users if file doesn't exist
        $defaultUsers = [
            ['id' => 1, 'username' => 'admin', 'role' => 'Admin', 'email' => 'admin@evm.com', 'note' => 'Tài khoản quản trị viên chính', 'created_at' => '2024-11-01 10:00:00', 'status' => 'active'],
            ['id' => 2, 'username' => 'user1', 'role' => 'EVM_Staff', 'email' => 'user1@evm.com', 'note' => 'Nhân viên EVM', 'created_at' => '2024-11-02 14:30:00', 'status' => 'active'],
            ['id' => 3, 'username' => 'user2', 'role' => 'SC_Staff', 'email' => 'user2@evm.com', 'note' => 'Nhân viên trung tâm dịch vụ', 'created_at' => '2024-11-03 09:15:00', 'status' => 'active']
        ];
        saveUsersToFile($filePath, $defaultUsers);
        return $defaultUsers;
    }
    
    $jsonData = file_get_contents($filePath);
    $users = json_decode($jsonData, true);
    return $users ? $users : [];
}

function saveUsersToFile($filePath, $users) {
    $jsonData = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($filePath, $jsonData) !== false;
}

function getNextUserId($users) {
    $maxId = 0;
    foreach ($users as $user) {
        if ($user['id'] > $maxId) {
            $maxId = $user['id'];
        }
    }
    return $maxId + 1;
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Parse the request
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Simple routing
if ($method === 'POST' && strpos($path, '/api/login') !== false) {
    // Handle login
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['user'] = [
            'id' => 1,
            'username' => 'admin',
            'role' => 'Admin'
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Đăng nhập thành công!',
            'user' => $_SESSION['user']
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Tên đăng nhập hoặc mật khẩu không đúng'
        ]);
    }
    exit();
}

if ($method === 'POST' && strpos($path, '/api/users') !== false) {
    // Handle register/add user
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? '';
    $email = $input['email'] ?? '';
    $note = $input['note'] ?? '';
    
    if (empty($username) || empty($password) || empty($role)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Vui lòng điền đầy đủ thông tin bắt buộc (tên đăng nhập, mật khẩu, vai trò)'
        ]);
        exit();
    }
    
    if (strlen($username) < 3) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Tên đăng nhập phải có ít nhất 3 ký tự'
        ]);
        exit();
    }
    
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Mật khẩu phải có ít nhất 6 ký tự'
        ]);
        exit();
    }
    
    // Validate role
    $validRoles = ['Admin', 'EVM_Staff', 'SC_Staff', 'SC_Technician'];
    if (!in_array($role, $validRoles)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Vai trò không hợp lệ'
        ]);
        exit();
    }
    
    // Load current users from file
    $users = loadUsersFromFile($usersDataFile);
    
    // Check if username already exists
    foreach ($users as $existingUser) {
        if (strtolower($existingUser['username']) === strtolower($username)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.'
            ]);
            exit();
        }
    }
    
    // Generate new user ID
    $newUserId = getNextUserId($users);
    
    // Create new user
    $newUser = [
        'id' => $newUserId,
        'username' => $username,
        'role' => $role,
        'email' => $email ?: null,
        'note' => $note ?: null,
        'created_at' => date('Y-m-d H:i:s'),
        'status' => 'active'
    ];
    
    // Add to users list
    $users[] = $newUser;
    
    // Save to file
    if (saveUsersToFile($usersDataFile, $users)) {
        echo json_encode([
            'success' => true,
            'message' => 'Tạo người dùng thành công và đã lưu vào database!',
            'user' => $newUser,
            'total_users' => count($users)
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Không thể lưu dữ liệu người dùng'
        ]);
    }
    exit();
}

if ($method === 'PUT' && strpos($path, '/api/users/') !== false) {
    // Handle update user
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    
    // Extract user ID from path
    $pathParts = explode('/', trim($path, '/'));
    $userId = end($pathParts);
    
    if (!is_numeric($userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid user ID']);
        exit();
    }
    
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['username']) || !isset($input['role'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: username, role']);
        exit();
    }
    
    // Load existing users
    $users = loadUsersFromFile($usersDataFile);
    
    // Find user to update
    $userFound = false;
    for ($i = 0; $i < count($users); $i++) {
        if ($users[$i]['id'] == $userId) {
            // Check if new username already exists (excluding current user)
            foreach ($users as $user) {
                if ($user['username'] === $input['username'] && $user['id'] != $userId) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Tên đăng nhập đã tồn tại']);
                    exit();
                }
            }
            
            // Update user data
            $users[$i]['username'] = $input['username'];
            $users[$i]['role'] = $input['role'];
            $users[$i]['email'] = $input['email'] ?? $users[$i]['email'];
            $users[$i]['note'] = $input['note'] ?? $users[$i]['note'];
            $users[$i]['updated_at'] = date('Y-m-d H:i:s');
            
            $userFound = true;
            break;
        }
    }
    
    if (!$userFound) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit();
    }
    
    // Save to file
    if (saveUsersToFile($usersDataFile, $users)) {
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật người dùng thành công!',
            'total_users' => count($users)
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Không thể lưu dữ liệu người dùng'
        ]);
    }
    exit();
}

if ($method === 'DELETE' && strpos($path, '/api/users/') !== false) {
    // Handle delete user
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    
    // Extract user ID from path
    $pathParts = explode('/', trim($path, '/'));
    $userId = end($pathParts);
    
    if (!is_numeric($userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid user ID']);
        exit();
    }
    
    // Prevent admin from deleting themselves
    if (isset($_SESSION['user']) && $_SESSION['user']['id'] == $userId) {
        http_response_code(400);
        echo json_encode(['error' => 'Không thể xóa chính mình']);
        exit();
    }
    
    // Load existing users
    $users = loadUsersFromFile($usersDataFile);
    
    // Find and remove user
    $userFound = false;
    $deletedUsername = '';
    for ($i = 0; $i < count($users); $i++) {
        if ($users[$i]['id'] == $userId) {
            $deletedUsername = $users[$i]['username'];
            array_splice($users, $i, 1);
            $userFound = true;
            break;
        }
    }
    
    if (!$userFound) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit();
    }
    
    // Save to file
    if (saveUsersToFile($usersDataFile, $users)) {
        echo json_encode([
            'success' => true,
            'message' => "Đã xóa người dùng '{$deletedUsername}' thành công!",
            'total_users' => count($users)
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Không thể lưu dữ liệu người dùng'
        ]);
    }
    exit();
}

if ($method === 'GET' && strpos($path, '/api/users') !== false) {
    // Handle get users
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    
    // Load users from file
    $users = loadUsersFromFile($usersDataFile);
    
    // Return current users list
    echo json_encode([
        'success' => true,
        'data' => $users,
        'total' => count($users),
        'source' => 'file_storage'
    ]);
    exit();
}

if ($method === 'GET' && strpos($path, '/api/test') !== false) {
    // Handle test endpoint
    echo json_encode([
        'success' => true,
        'message' => 'API is working!',
        'timestamp' => date('Y-m-d H:i:s'),
        'session' => isset($_SESSION['user']) ? $_SESSION['user'] : null,
        'data_file_exists' => file_exists($usersDataFile),
        'data_file_size' => file_exists($usersDataFile) ? filesize($usersDataFile) : 0
    ]);
    exit();
}

if ($method === 'GET' && strpos($path, '/api/backup') !== false) {
    // Handle backup download
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Only admin can download backup']);
        exit();
    }
    
    if (file_exists($usersDataFile)) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="users_backup_' . date('Y-m-d_H-i-s') . '.json"');
        readfile($usersDataFile);
        exit();
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Backup file not found']);
        exit();
    }
}

// Default response
http_response_code(404);
echo json_encode([
    'error' => 'Endpoint not found',
    'path' => $path,
    'method' => $method
]);
?>