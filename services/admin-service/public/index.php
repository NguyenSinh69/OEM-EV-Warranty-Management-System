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

// Mock admin endpoints
if (strpos($uri, '/api/admin') === 0) {
    if ($uri === '/api/admin/stats' && $method === 'GET') {
        echo json_encode([
            'success' => true,
            'data' => [
                'total_customers' => 150,
                'total_vehicles' => 89,
                'total_claims' => 25,
                'pending_claims' => 8,
                'approved_claims' => 12,
                'rejected_claims' => 5
            ],
            'message' => 'Admin statistics retrieved successfully'
        ]);
        exit;
    }
    
    if ($uri === '/api/admin/users' && $method === 'GET') {
        $mockUsers = [
            ['id' => 1, 'email' => 'admin@evm.com', 'role' => 'admin', 'status' => 'active'],
            ['id' => 2, 'email' => 'staff@evm.com', 'role' => 'evm_staff', 'status' => 'active'],
            ['id' => 3, 'email' => 'sc-staff@evm.com', 'role' => 'sc_staff', 'status' => 'active']
        ];
        echo json_encode([
            'success' => true,
            'data' => $mockUsers,
            'message' => 'Users retrieved successfully'
        ]);
        exit;
    }
}

// Default response
http_response_code(404);
echo json_encode([
    'success' => false,
    'message' => 'Endpoint not found',
    'service' => 'admin-service'
]);
