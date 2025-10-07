<?php

// Simple index file for warranty service
header('Content-Type: application/json');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Health check endpoint
if ($uri === '/api/health') {
    echo json_encode([
        'status' => 'healthy',
        'service' => 'warranty-service',
        'timestamp' => date('c'),
        'version' => '1.0.0'
    ]);
    exit;
}

// Mock warranty claims data
$mockClaims = [
    [
        'id' => 1,
        'claim_number' => 'WC-2024-000001',
        'customer_id' => 1,
        'vehicle_vin' => 'VF3ABCDEF12345678',
        'description' => 'Pin không sạc được',
        'issue_type' => 'battery',
        'priority' => 'high',
        'status' => 'pending',
        'created_at' => '2024-10-01T10:00:00Z',
        'estimated_cost' => 0
    ],
    [
        'id' => 2,
        'claim_number' => 'WC-2024-000002',
        'customer_id' => 2,
        'vehicle_vin' => 'VF3GHIJKL87654321',
        'description' => 'Hệ thống điều hòa không hoạt động',
        'issue_type' => 'electrical',
        'priority' => 'medium',
        'status' => 'in_progress',
        'created_at' => '2024-10-02T14:30:00Z',
        'estimated_cost' => 500000
    ]
];

// Warranties endpoint
if (strpos($uri, '/api/warranties') === 0) {
    if ($method === 'GET') {
        if (preg_match('/\/api\/warranties\/(\d+)$/', $uri, $matches)) {
            // Get specific warranty claim
            $id = (int)$matches[1];
            $claim = null;
            foreach ($mockClaims as $c) {
                if ($c['id'] === $id) {
                    $claim = $c;
                    break;
                }
            }
            echo json_encode([
                'success' => true,
                'data' => $claim ?: null,
                'message' => $claim ? 'Warranty claim retrieved successfully' : 'Warranty claim not found'
            ]);
        } else {
            // List all warranty claims
            $status = $_GET['status'] ?? null;
            $filteredClaims = $mockClaims;
            if ($status) {
                $filteredClaims = array_filter($mockClaims, function($claim) use ($status) {
                    return $claim['status'] === $status;
                });
            }
            echo json_encode([
                'success' => true,
                'data' => array_values($filteredClaims),
                'message' => 'Warranty claims retrieved successfully'
            ]);
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $claimNumber = 'WC-' . date('Y') . '-' . str_pad(count($mockClaims) + 1, 6, '0', STR_PAD_LEFT);
        
        $newClaim = [
            'id' => count($mockClaims) + 1,
            'claim_number' => $claimNumber,
            'customer_id' => $input['customer_id'] ?? 1,
            'vehicle_vin' => $input['vehicle_vin'] ?? 'VF3UNKNOWN000000',
            'description' => $input['description'] ?? 'No description provided',
            'issue_type' => $input['issue_type'] ?? 'other',
            'priority' => $input['priority'] ?? 'medium',
            'status' => 'pending',
            'created_at' => date('c'),
            'estimated_cost' => 0
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $newClaim,
            'message' => 'Warranty claim created successfully'
        ]);
    }
    exit;
}

// Update warranty status endpoint
if (preg_match('/\/api\/warranties\/(\d+)\/status$/', $uri, $matches) && $method === 'PUT') {
    $id = (int)$matches[1];
    $input = json_decode(file_get_contents('php://input'), true);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $id,
            'claim_number' => 'WC-2024-00000' . $id,
            'status' => $input['status'] ?? 'updated',
            'notes' => $input['notes'] ?? '',
            'updated_at' => date('c')
        ],
        'message' => 'Warranty claim status updated successfully'
    ]);
    exit;
}

// Claims endpoint (alias for warranties)
if (strpos($uri, '/api/claims') === 0) {
    if ($method === 'GET') {
        echo json_encode([
            'success' => true,
            'data' => $mockClaims,
            'message' => 'Claims retrieved successfully'
        ]);
    }
    exit;
}

// Default response
http_response_code(404);
echo json_encode([
    'success' => false,
    'message' => 'Endpoint not found',
    'service' => 'warranty-service'
]);