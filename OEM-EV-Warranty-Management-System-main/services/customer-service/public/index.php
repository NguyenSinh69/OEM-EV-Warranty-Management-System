<?php

// Simple index file for customer service
header('Content-Type: application/json');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Health check endpoint
if ($uri === '/api/health') {
    echo json_encode([
        'status' => 'healthy',
        'service' => 'customer-service',
        'timestamp' => date('c'),
        'version' => '1.0.0'
    ]);
    exit;
}

// Mock customer data
$mockCustomers = [
    [
        'id' => 1,
        'name' => 'Nguyễn Văn A',
        'email' => 'nguyenvana@example.com',
        'phone' => '0901234567',
        'address' => 'Hà Nội',
        'date_of_birth' => '1990-01-01',
        'id_number' => '123456789',
        'status' => 'active'
    ],
    [
        'id' => 2,
        'name' => 'Trần Thị B',
        'email' => 'tranthib@example.com',
        'phone' => '0912345678',
        'address' => 'TP.HCM',
        'date_of_birth' => '1985-05-15',
        'id_number' => '987654321',
        'status' => 'active'
    ],
    [
        'id' => 3,
        'name' => 'Lê Văn C',
        'email' => 'levanc@example.com',
        'phone' => '0923456789',
        'address' => 'Đà Nẵng',
        'date_of_birth' => '1988-03-20',
        'id_number' => '456789123',
        'status' => 'active'
    ]
];

// Authentication endpoints
if (strpos($uri, '/api/auth') === 0) {
    if ($uri === '/api/auth/login' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
        // Simple auth check
        $customer = null;
        foreach ($mockCustomers as $c) {
            if ($c['email'] === $email) {
                $customer = $c;
                break;
            }
        }
        
        if ($customer && $password === 'password123') {
            // Generate mock JWT token
            $token = base64_encode(json_encode([
                'sub' => $customer['id'],
                'email' => $customer['email'],
                'exp' => time() + 3600
            ]));
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'customer' => $customer,
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600
                ],
                'message' => 'Login successful'
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid credentials'
            ]);
        }
        exit;
    }
    
    if ($uri === '/api/auth/register' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $newCustomer = [
            'id' => count($mockCustomers) + 1,
            'name' => $input['name'] ?? 'Unknown',
            'email' => $input['email'] ?? '',
            'phone' => $input['phone'] ?? '',
            'address' => $input['address'] ?? '',
            'date_of_birth' => $input['date_of_birth'] ?? '',
            'id_number' => $input['id_number'] ?? '',
            'status' => 'active',
            'created_at' => date('c')
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $newCustomer,
            'message' => 'Registration successful'
        ]);
        exit;
    }
    
    // Profile endpoint (requires token - simplified check)
    if ($uri === '/api/auth/profile' && $method === 'GET') {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (strpos($authHeader, 'Bearer ') === 0) {
            echo json_encode([
                'success' => true,
                'data' => $mockCustomers[0], // Return first customer for demo
                'message' => 'Profile retrieved successfully'
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
        }
        exit;
    }
}

// Customers endpoint
if (strpos($uri, '/api/customers') === 0) {
    if ($method === 'GET') {
        if (preg_match('/\/api\/customers\/(\d+)$/', $uri, $matches)) {
            // Get specific customer
            $id = (int)$matches[1];
            $customer = null;
            foreach ($mockCustomers as $c) {
                if ($c['id'] === $id) {
                    $customer = $c;
                    break;
                }
            }
            echo json_encode([
                'success' => true,
                'data' => $customer,
                'message' => $customer ? 'Customer retrieved successfully' : 'Customer not found'
            ]);
        } elseif (preg_match('/\/api\/customers\/(\d+)\/vehicles$/', $uri, $matches)) {
            // Get customer's vehicles
            $customerId = (int)$matches[1];
            $vehicles = [
                [
                    'id' => 1,
                    'vin' => 'VF3ABCDEF12345678',
                    'model' => 'VinFast VF8',
                    'year' => 2024,
                    'customer_id' => $customerId
                ]
            ];
            echo json_encode([
                'success' => true,
                'data' => $vehicles,
                'message' => 'Customer vehicles retrieved successfully'
            ]);
        } else {
            // List all customers
            echo json_encode([
                'success' => true,
                'data' => $mockCustomers,
                'message' => 'Customers retrieved successfully'
            ]);
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $newCustomer = array_merge($input, [
            'id' => count($mockCustomers) + 1,
            'status' => 'active',
            'created_at' => date('c')
        ]);
        echo json_encode([
            'success' => true,
            'data' => $newCustomer,
            'message' => 'Customer created successfully'
        ]);
    }
    exit;
}

// Public endpoints for other services
if (strpos($uri, '/api/public/customers') === 0) {
    if (preg_match('/\/api\/public\/customers\/(\d+)$/', $uri, $matches)) {
        $id = (int)$matches[1];
        $customer = null;
        foreach ($mockCustomers as $c) {
            if ($c['id'] === $id) {
                $customer = $c;
                break;
            }
        }
        echo json_encode([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer retrieved successfully'
        ]);
        exit;
    }
}

// Default response
http_response_code(404);
echo json_encode([
    'success' => false,
    'message' => 'Endpoint not found',
    'service' => 'customer-service'
]);