<?php

// Vehicle Service Entry Point
require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Health check endpoint
if ($uri === '/api/health' || $uri === '/health') {
    echo json_encode([
        'status' => 'healthy',
        'service' => 'vehicle-service',
        'timestamp' => date('c'),
        'version' => '1.0.0',
        'description' => 'Vehicle Service for OEM EV Warranty Management System',
        'endpoints' => [
            'POST /api/components' => 'Create new EV component',
            'GET /api/components' => 'Get all components',
            'GET /api/components/{id}' => 'Get component by ID',
            'POST /api/warranty-policies' => 'Create warranty policy',
            'GET /api/warranty-policies' => 'Get all warranty policies',
            'POST /api/campaigns' => 'Create new campaign',
            'GET /api/campaigns' => 'Get all campaigns',
            'GET /api/campaigns/{id}/vehicles' => 'Get affected vehicles',
            'POST /api/campaigns/{id}/notify' => 'Send notifications',
            'GET /api/campaigns/{id}/progress' => 'Get campaign progress'
        ]
    ]);
    exit;
}

// For all other API endpoints, route to the API handler
if (strpos($uri, '/api/') === 0) {
    require_once __DIR__ . '/../src/routes/api.php';
    exit;
}

// Default response for non-API requests
echo json_encode([
    'message' => 'Vehicle Service API',
    'version' => '1.0.0',
    'health_check' => '/api/health',
    'documentation' => 'See /api/health for available endpoints'
]);