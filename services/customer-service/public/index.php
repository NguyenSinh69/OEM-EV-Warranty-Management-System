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

// ===============================================
// CUSTOMER PORTAL API ENDPOINTS
// ===============================================

// Get customer's vehicles
if ($uri === '/api/customer/vehicles' && $method === 'GET') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    // Mock vehicles for customer
    $vehicles = [
        [
            'id' => 1,
            'vin' => 'VF3ABCDEF12345678',
            'license_plate' => '29A-12345',
            'model' => 'VinFast VF8',
            'make' => 'VinFast',
            'year' => 2024,
            'color' => 'Đỏ',
            'purchase_date' => '2024-01-15',
            'warranty_start_date' => '2024-01-15',
            'warranty_end_date' => '2026-01-15',
            'warranty_months' => 24,
            'mileage' => 15000,
            'status' => 'under_warranty',
            'customer_id' => 1
        ],
        [
            'id' => 2,
            'vin' => 'VF5XYZ789012345678',
            'license_plate' => '29B-67890',
            'model' => 'VinFast VF9',
            'make' => 'VinFast',
            'year' => 2023,
            'color' => 'Xanh dương',
            'purchase_date' => '2023-06-10',
            'warranty_start_date' => '2023-06-10',
            'warranty_end_date' => '2025-06-10',
            'warranty_months' => 24,
            'mileage' => 32000,
            'status' => 'under_warranty',
            'customer_id' => 1
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $vehicles,
        'message' => 'Vehicles retrieved successfully'
    ]);
    exit;
}

// Get customer's warranty claims
if ($uri === '/api/customer/claims' && $method === 'GET') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    // Mock claims for customer
    $claims = [
        [
            'id' => 1,
            'claim_number' => 'WC-2024-001',
            'vin' => 'VF3ABCDEF12345678',
            'vehicle_model' => 'VinFast VF8',
            'component' => 'Battery',
            'failure_description' => 'Pin sạc không đầy',
            'failure_date' => '2024-10-01',
            'mileage' => 14500,
            'status' => 'under_review',
            'status_notes' => 'Đang chờ kỹ thuật viên kiểm tra',
            'submission_date' => '2024-10-02',
            'images' => [
                'https://via.placeholder.com/300x200?text=Battery+Issue+1',
                'https://via.placeholder.com/300x200?text=Battery+Issue+2'
            ]
        ],
        [
            'id' => 2,
            'claim_number' => 'WC-2024-002',
            'vin' => 'VF5XYZ789012345678',
            'vehicle_model' => 'VinFast VF9',
            'component' => 'Motor',
            'failure_description' => 'Động cơ có tiếng kêu bất thường',
            'failure_date' => '2024-09-15',
            'mileage' => 31800,
            'status' => 'approved',
            'status_notes' => 'Yêu cầu bảo hành được chấp nhận',
            'submission_date' => '2024-09-16',
            'approved_date' => '2024-09-18',
            'images' => []
        ],
        [
            'id' => 3,
            'claim_number' => 'WC-2024-003',
            'vin' => 'VF3ABCDEF12345678',
            'vehicle_model' => 'VinFast VF8',
            'component' => 'Inverter',
            'failure_description' => 'Inverter hỏng',
            'failure_date' => '2024-08-20',
            'mileage' => 13200,
            'status' => 'completed',
            'status_notes' => 'Đã thay thế inverter mới',
            'submission_date' => '2024-08-21',
            'approved_date' => '2024-08-22',
            'completed_date' => '2024-08-25',
            'images' => []
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $claims,
        'message' => 'Claims retrieved successfully'
    ]);
    exit;
}

// Create new warranty claim
if ($uri === '/api/customer/claims' && $method === 'POST') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['vehicle_id', 'component', 'failure_description', 'failure_date', 'mileage'];
    $missing = [];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: ' . implode(', ', $missing)
        ]);
        exit;
    }
    
    // Create new claim
    $newClaim = [
        'id' => rand(1000, 9999),
        'claim_number' => 'WC-2024-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
        'vehicle_id' => $input['vehicle_id'],
        'vin' => $input['vin'] ?? 'VF3ABCDEF12345678',
        'component' => $input['component'],
        'failure_description' => $input['failure_description'],
        'failure_date' => $input['failure_date'],
        'mileage' => $input['mileage'],
        'status' => 'submitted',
        'status_notes' => 'Yêu cầu bảo hành đã được gửi thành công',
        'submission_date' => date('Y-m-d'),
        'images' => $input['images'] ?? [],
        'customer_id' => 1
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $newClaim,
        'message' => 'Claim created successfully'
    ]);
    exit;
}

// Get claim details
if (preg_match('/^\/api\/customer\/claims\/(\d+)$/', $uri, $matches) && $method === 'GET') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $claimId = (int)$matches[1];
    
    // Mock claim details
    $claim = [
        'id' => $claimId,
        'claim_number' => 'WC-2024-001',
        'vin' => 'VF3ABCDEF12345678',
        'vehicle_id' => 1,
        'vehicle_model' => 'VinFast VF8',
        'component' => 'Battery',
        'failure_description' => 'Pin sạc không đầy, chỉ sạc được tối đa 80%',
        'failure_date' => '2024-10-01',
        'mileage' => 14500,
        'status' => 'under_review',
        'status_notes' => 'Đang chờ kỹ thuật viên kiểm tra. Dự kiến hoàn thành trong 3-5 ngày làm việc.',
        'submission_date' => '2024-10-02',
        'approved_date' => null,
        'completed_date' => null,
        'rejection_reason' => null,
        'images' => [
            'https://via.placeholder.com/800x600?text=Battery+Status+Display',
            'https://via.placeholder.com/800x600?text=Battery+Serial+Number',
            'https://via.placeholder.com/800x600?text=Charging+Port'
        ],
        'service_center' => 'Trung tâm bảo hành Hà Nội',
        'technician' => 'Nguyễn Văn Kỹ thuật',
        'customer_id' => 1
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $claim,
        'message' => 'Claim details retrieved successfully'
    ]);
    exit;
}

// Book service appointment
if ($uri === '/api/customer/appointments' && $method === 'POST') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['vehicle_id', 'service_type', 'appointment_date', 'appointment_time'];
    $missing = [];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: ' . implode(', ', $missing)
        ]);
        exit;
    }
    
    // Create new appointment
    $newAppointment = [
        'id' => rand(1000, 9999),
        'appointment_number' => 'APT-2024-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
        'vehicle_id' => $input['vehicle_id'],
        'service_type' => $input['service_type'],
        'appointment_date' => $input['appointment_date'],
        'appointment_time' => $input['appointment_time'],
        'notes' => $input['notes'] ?? '',
        'status' => 'scheduled',
        'service_center' => 'Trung tâm bảo hành Hà Nội',
        'service_center_address' => '123 Đường ABC, Quận XYZ, Hà Nội',
        'service_center_phone' => '024-1234-5678',
        'created_at' => date('Y-m-d H:i:s'),
        'customer_id' => 1
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $newAppointment,
        'message' => 'Appointment booked successfully'
    ]);
    exit;
}

// Get customer's appointments
if ($uri === '/api/customer/appointments' && $method === 'GET') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    // Mock appointments
    $appointments = [
        [
            'id' => 1,
            'appointment_number' => 'APT-2024-001',
            'vehicle_id' => 1,
            'vehicle_model' => 'VinFast VF8',
            'vin' => 'VF3ABCDEF12345678',
            'service_type' => 'Bảo dưỡng định kỳ',
            'appointment_date' => '2024-11-20',
            'appointment_time' => '09:00',
            'status' => 'scheduled',
            'service_center' => 'Trung tâm bảo hành Hà Nội',
            'notes' => 'Kiểm tra tổng thể và thay dầu',
            'created_at' => '2024-11-10 14:30:00'
        ],
        [
            'id' => 2,
            'appointment_number' => 'APT-2024-002',
            'vehicle_id' => 2,
            'vehicle_model' => 'VinFast VF9',
            'vin' => 'VF5XYZ789012345678',
            'service_type' => 'Sửa chữa bảo hành',
            'appointment_date' => '2024-11-15',
            'appointment_time' => '14:00',
            'status' => 'completed',
            'service_center' => 'Trung tâm bảo hành Hà Nội',
            'notes' => 'Thay thế động cơ theo bảo hành',
            'created_at' => '2024-11-08 10:15:00'
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $appointments,
        'message' => 'Appointments retrieved successfully'
    ]);
    exit;
}

// Get customer notifications
if ($uri === '/api/customer/notifications' && $method === 'GET') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    // Mock notifications
    $notifications = [
        [
            'id' => 1,
            'type' => 'claim',
            'title' => 'Yêu cầu bảo hành được chấp nhận',
            'message' => 'Yêu cầu bảo hành WC-2024-002 của bạn đã được chấp nhận. Vui lòng liên hệ trung tâm để lên lịch sửa chữa.',
            'read' => false,
            'created_at' => '2024-11-10 09:30:00',
            'link' => '/customer/claims/2'
        ],
        [
            'id' => 2,
            'type' => 'appointment',
            'title' => 'Nhắc nhở lịch hẹn',
            'message' => 'Bạn có lịch hẹn bảo dưỡng vào ngày 20/11/2024 lúc 09:00.',
            'read' => false,
            'created_at' => '2024-11-09 14:00:00',
            'link' => '/customer/booking'
        ],
        [
            'id' => 3,
            'type' => 'recall',
            'title' => 'Thông báo triệu hồi',
            'message' => 'Xe VF8 của bạn nằm trong đợt triệu hồi để cập nhật phần mềm. Vui lòng liên hệ trung tâm.',
            'read' => true,
            'created_at' => '2024-11-05 10:00:00',
            'link' => '/customer'
        ],
        [
            'id' => 4,
            'type' => 'system',
            'title' => 'Cập nhật hệ thống',
            'message' => 'Hệ thống quản lý bảo hành đã được cập nhật phiên bản mới với nhiều tính năng cải tiến.',
            'read' => true,
            'created_at' => '2024-11-01 08:00:00',
            'link' => null
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $notifications,
        'message' => 'Notifications retrieved successfully'
    ]);
    exit;
}

// Mark notification as read
if (preg_match('/^\/api\/customer\/notifications\/(\d+)\/read$/', $uri, $matches) && $method === 'PUT') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $notificationId = (int)$matches[1];
    
    echo json_encode([
        'success' => true,
        'message' => 'Notification marked as read'
    ]);
    exit;
}

// Delete notification
if (preg_match('/^\/api\/customer\/notifications\/(\d+)$/', $uri, $matches) && $method === 'DELETE') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $notificationId = (int)$matches[1];
    
    echo json_encode([
        'success' => true,
        'message' => 'Notification deleted successfully'
    ]);
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