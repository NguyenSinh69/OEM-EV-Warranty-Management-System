<?php
// Start session for authentication
session_start();

// Enable CORS for cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once dirname(__DIR__) . '/src/Core/ResponseHelper.php';
require_once dirname(__DIR__) . '/src/Core/AuthMiddleware.php';
require_once dirname(__DIR__) . '/src/Core/Database.php';
require_once dirname(__DIR__) . '/src/app/Http/Controllers/AdminController.php';

use App\Http\Controllers\AdminController;
use Core\ResponseHelper;

// Get request info
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove leading slashes and normalize URI
$uri = '/' . trim($uri, '/');

$controller = new AdminController();

try {
    // Authentication endpoints
    if ($uri === '/api/login' && $method === 'POST') {
        $controller->login();
    } elseif ($uri === '/api/logout' && $method === 'POST') {
        $controller->logout();
    } elseif ($uri === '/api/auth/status' && $method === 'GET') {
        $controller->getAuthStatus();
        
    // User management endpoints
    } elseif ($uri === '/api/users' && $method === 'GET') {
        $controller->getUsers();
    } elseif ($uri === '/api/users' && $method === 'POST') {
        $controller->createUser();
    } elseif (preg_match('#^/api/users/(\d+)$#', $uri, $matches) && $method === 'GET') {
        $controller->getUser($matches[1]);
    } elseif (preg_match('#^/api/users/(\d+)$#', $uri, $matches) && $method === 'PUT') {
        $controller->updateUser($matches[1]);
    } elseif (preg_match('#^/api/users/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
        $controller->deleteUser($matches[1]);
        
    // Service center endpoints
    } elseif ($uri === '/api/service-centers' && $method === 'GET') {
        $controller->getServiceCenters();
        
    // Assignment endpoints
    } elseif ($uri === '/api/assignments' && $method === 'POST') {
        $controller->createAssignment();
        
    // Analytics endpoints
    } elseif ($uri === '/api/analytics/failures' && $method === 'GET') {
        $controller->getFailureAnalytics();
    } elseif ($uri === '/api/analytics/costs' && $method === 'GET') {
        $controller->getCostAnalytics();
    } elseif ($uri === '/api/analytics/performance' && $method === 'GET') {
        $controller->getPerformanceAnalytics();
        
    // Report endpoints
    } elseif ($uri === '/api/reports/export' && $method === 'POST') {
        $controller->exportReport();
        
    // Dashboard endpoints
    } elseif ($uri === '/api/dashboard/summary' && $method === 'GET') {
        $controller->getDashboardSummary();
        
    // Role endpoints
    } elseif ($uri === '/api/roles' && $method === 'GET') {
        $controller->getRoles();
        
    // Claim decision endpoints
    } elseif (preg_match('#^/api/claims/(\d+)/decision$#', $uri, $matches) && $method === 'POST') {
        $controller->decideClaim($matches[1]);
        
    // Health check
    } elseif ($uri === '/health' && $method === 'GET') {
        ResponseHelper::json(['status' => 'OK', 'service' => 'admin-service', 'timestamp' => date('c')]);
        
    // 404 for unmatched routes
    } else {
        ResponseHelper::json(['error' => 'Endpoint not found', 'uri' => $uri, 'method' => $method], 404);
    }
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("Admin Service Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    ResponseHelper::json(['error' => 'Internal server error: ' . $e->getMessage()], 500);
}
