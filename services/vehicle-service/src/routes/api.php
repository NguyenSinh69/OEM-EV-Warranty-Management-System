<?php

/**
 * API Routes for Vehicle Service
 * 
 * All routes are prefixed with /api/
 * 
 * Components Routes:
 * - POST /api/components - Create new EV component
 * - GET /api/components - Get all components (with filters)
 * - GET /api/components/{id} - Get component by ID
 * - PUT /api/components/{id} - Update component
 * - DELETE /api/components/{id} - Delete component
 * 
 * Warranty Policies Routes:
 * - POST /api/warranty-policies - Create new warranty policy
 * - GET /api/warranty-policies - Get all policies (with filters)
 * - GET /api/warranty-policies/{id} - Get policy by ID
 * - PUT /api/warranty-policies/{id} - Update policy
 * - DELETE /api/warranty-policies/{id} - Delete policy
 * 
 * Campaigns Routes:
 * - POST /api/campaigns - Create new campaign
 * - GET /api/campaigns - Get all campaigns (with filters)
 * - GET /api/campaigns/{id} - Get campaign by ID
 * - GET /api/campaigns/{id}/vehicles - Get affected vehicles
 * - POST /api/campaigns/{id}/notify - Send notifications to customers
 * - GET /api/campaigns/{id}/progress - Get campaign progress
 * - PUT /api/campaigns/{id} - Update campaign
 * - DELETE /api/campaigns/{id} - Delete campaign
 */

require_once __DIR__ . '/../app/Http/Controllers/ComponentsController.php';
require_once __DIR__ . '/../../shared/database.php';

use App\Http\Controllers\ComponentsController;

// CORS Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Get HTTP method and URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Get request body for POST/PUT requests
$input = null;
if ($method === 'POST' || $method === 'PUT') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE && !empty($rawInput)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid JSON in request body']);
        exit;
    }
}

try {
    // Initialize database connection
    $database = getDatabaseConnection();
    
    // Initialize controller
    $controller = new ComponentsController($database);
    
    // Route the request
    $response = $controller->handleRequest($method, $uri, $input);
    
    // Output the response
    echo $response;
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Get database connection
 */
function getDatabaseConnection() {
    // Database configuration
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'vehicle_service_db';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';
    
    try {
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, $username, $password, $options);
        return $pdo;
        
    } catch (PDOException $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}