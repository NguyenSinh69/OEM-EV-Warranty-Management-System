<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\App;
use App\Core\Router;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize the application
$app = new App();

// Set up database connection
Database::getInstance();

// Handle the request
$request = new Request();
$response = new Response();

// Set CORS headers
$response->header('Access-Control-Allow-Origin', '*');
$response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
$response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');

if ($request->getMethod() === 'OPTIONS') {
    $response->json(['status' => 'OK']);
    exit;
}

// Load routes
require_once __DIR__ . '/../routes/api.php';

// Handle the request
$router = Router::getInstance();
$router->handle($request, $response);