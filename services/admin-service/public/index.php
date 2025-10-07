<?php

// Simple index file for admin service
header('Content-Type: application/json');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Health check endpoint
if ($uri === '/api/health') {
    echo json_encode([
        'status' => 'healthy',
        'service' => 'admin-service',
        'timestamp' => date('c'),
        'version' => '1.0.0'
    ]);
    exit;
}

// Simple routing
if (strpos($uri, '/api/admin') === 0) {
    echo json_encode([
        'success' => true,
        'service' => 'admin-service',
        'message' => 'Admin service is running',
        'endpoint' => $uri,
        'method' => $method
    ]);
    exit;
}

// Default response
http_response_code(404);
echo json_encode([
    'success' => false,
    'message' => 'Endpoint not found',
    'service' => 'admin-service'
]);