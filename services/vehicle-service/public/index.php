<?php

// Vehicle Service Entry Point
require_once __DIR__ . '/../src/bootstrap.php';

use App\Http\Controllers\VehicleController;

header('Content-Type: application/json');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Mock vehicle data - expanded for complete testing
$mockVehicles = [
    [
        'id' => 1,
        'vin' => 'VF3ABCDEF12345678',
        'model' => 'VinFast VF8',
        'year' => 2024,
        'color' => 'Đen Kim Cương',
        'customer_id' => 1,
        'customer_name' => 'Nguyễn Văn A',
        'customer_phone' => '0901234567',
        'purchase_date' => '2024-01-15',
        'warranty_start_date' => '2024-01-15',
        'warranty_end_date' => '2026-01-15',
        'status' => 'active',
        'mileage' => 5000,
        'battery_capacity' => '87.7 kWh',
        'motor_power' => '300 kW',
        'license_plate' => '30A-12345',
        'service_center_id' => 1,
        'registration_date' => '2024-01-15',
        'last_service_date' => '2024-10-15'
    ],
    [
        'id' => 2,
        'vin' => 'VF3GHIJKL87654321',
        'model' => 'VinFast VF9',
        'year' => 2024,
        'color' => 'Trắng Ngọc Trai',
        'customer_id' => 2,
        'customer_name' => 'Trần Thị B',
        'customer_phone' => '0912345678',
        'purchase_date' => '2024-02-20',
        'warranty_start_date' => '2024-02-20',
        'warranty_end_date' => '2026-02-20',
        'status' => 'active',
        'mileage' => 3000,
        'battery_capacity' => '123 kWh',
        'motor_power' => '300 kW',
        'license_plate' => '51H-67890',
        'service_center_id' => 1,
        'registration_date' => '2024-02-20',
        'last_service_date' => '2024-09-20'
    ],
    [
        'id' => 3,
        'vin' => 'VF3MNOPQR11111111',
        'model' => 'VinFast VF8',
        'year' => 2024,
        'color' => 'Xanh Đại Dương',
        'customer_id' => 3,
        'customer_name' => 'Lê Văn C',
        'customer_phone' => '0923456789',
        'purchase_date' => '2024-11-01',
        'warranty_start_date' => '2024-11-01',
        'warranty_end_date' => '2026-11-01',
        'status' => 'active',
        'mileage' => 500,
        'battery_capacity' => '87.7 kWh',
        'motor_power' => '300 kW',
        'license_plate' => '43B-98765',
        'service_center_id' => 1,
        'registration_date' => '2024-11-05',
        'last_service_date' => null
    ],
    [
        'id' => 4,
        'vin' => 'VF3STUVWX22222222',
        'model' => 'VinFast VF9',
        'year' => 2024,
        'color' => 'Đỏ Quyến Rũ',
        'customer_id' => 4,
        'customer_name' => 'Phạm Thị D',
        'customer_phone' => '0934567890',
        'purchase_date' => '2024-11-05',
        'warranty_start_date' => '2024-11-05',
        'warranty_end_date' => '2026-11-05',
        'status' => 'registered',
        'mileage' => 0,
        'battery_capacity' => '123 kWh',
        'motor_power' => '300 kW',
        'license_plate' => null,
        'service_center_id' => 1,
        'registration_date' => '2024-11-05',
        'last_service_date' => null
    ]
];

// Mock customers data for lookup
$mockCustomers = [
    ['id' => 1, 'name' => 'Nguyễn Văn A', 'phone' => '0901234567', 'email' => 'nguyenvana@example.com'],
    ['id' => 2, 'name' => 'Trần Thị B', 'phone' => '0912345678', 'email' => 'tranthib@example.com'],
    ['id' => 3, 'name' => 'Lê Văn C', 'phone' => '0923456789', 'email' => 'levanc@example.com'],
    ['id' => 4, 'name' => 'Phạm Thị D', 'phone' => '0934567890', 'email' => 'phamthid@example.com']
];

# Health check endpoint
if ($uri === '/api/health') {
    echo json_encode([
        'status' => 'healthy',
        'service' => 'vehicle-service',
        'timestamp' => date('c'),
        'version' => '1.0.0'
    ]);
    exit;
}

// Route SC Staff API requests
if (strpos($uri, '/api/sc-staff') === 0) {
    require_once __DIR__ . '/sc-staff-api.php';
    exit;
}

// Route SC Technician API requests  
if (strpos($uri, '/api/sc-technician') === 0) {
    require_once __DIR__ . '/sc-technician-api.php';
    exit;
}

// Route EVM Staff API requests
if (strpos($uri, '/api/evm-staff') === 0) {
    require_once __DIR__ . '/evm-staff-api.php';
    exit;
}

// Route Admin API requests
if (strpos($uri, '/api/admin') === 0) {
    require_once __DIR__ . '/admin-api.php';
    exit;
}

// Handle specific endpoints BEFORE general vehicles endpoint
// Vehicle search endpoint
if ($uri === '/api/vehicles/search' && $method === 'GET') {
    $query = $_GET['q'] ?? '';
    $type = $_GET['type'] ?? 'all'; // vin, customer_name, license_plate, all
    
    $results = [];
    
    if (!empty($query)) {
        foreach ($mockVehicles as $vehicle) {
            $match = false;
            
            switch ($type) {
                case 'vin':
                    $match = stripos($vehicle['vin'], $query) !== false;
                    break;
                case 'customer_name':
                    $match = stripos($vehicle['customer_name'], $query) !== false;
                    break;
                case 'license_plate':
                    $match = $vehicle['license_plate'] && stripos($vehicle['license_plate'], $query) !== false;
                    break;
                case 'all':
                default:
                    $match = stripos($vehicle['vin'], $query) !== false ||
                           stripos($vehicle['customer_name'], $query) !== false ||
                           ($vehicle['license_plate'] && stripos($vehicle['license_plate'], $query) !== false) ||
                           stripos($vehicle['model'], $query) !== false;
                    break;
            }
            
            if ($match) {
                $results[] = $vehicle;
            }
        }
    } else {
        // Return recent vehicles if no query
        $results = array_slice($mockVehicles, 0, 10);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $results,
        'message' => 'Vehicle search completed',
        'total' => count($results)
    ]);
    exit;
}

// Vehicle statistics endpoint
if ($uri === '/api/vehicles/stats' && $method === 'GET') {
    $today = date('Y-m-d');
    $totalVehicles = count($mockVehicles);
    $todayRegistrations = count(array_filter($mockVehicles, function($v) use ($today) {
        return $v['registration_date'] === $today;
    }));
    $vf8Count = count(array_filter($mockVehicles, function($v) {
        return stripos($v['model'], 'VF8') !== false;
    }));
    $vf9Count = count(array_filter($mockVehicles, function($v) {
        return stripos($v['model'], 'VF9') !== false;
    }));
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_vehicles' => $totalVehicles,
            'today_registrations' => $todayRegistrations,
            'vf8_count' => $vf8Count,
            'vf9_count' => $vf9Count,
            'active_warranties' => $totalVehicles - 1,
            'pending_registrations' => 2
        ],
        'message' => 'Vehicle statistics retrieved successfully'
    ]);
    exit;
}

// Vehicle search endpoint


// Mock vehicle data - expanded for complete testing
$mockVehicles = [
    [
        'id' => 1,
        'vin' => 'VF3ABCDEF12345678',
        'model' => 'VinFast VF8',
        'year' => 2024,
        'color' => 'Đen Kim Cương',
        'customer_id' => 1,
        'customer_name' => 'Nguyễn Văn A',
        'customer_phone' => '0901234567',
        'purchase_date' => '2024-01-15',
        'warranty_start_date' => '2024-01-15',
        'warranty_end_date' => '2026-01-15',
        'status' => 'active',
        'mileage' => 5000,
        'battery_capacity' => '87.7 kWh',
        'motor_power' => '300 kW',
        'license_plate' => '30A-12345',
        'service_center_id' => 1,
        'registration_date' => '2024-01-15',
        'last_service_date' => '2024-10-15'
    ],
    [
        'id' => 2,
        'vin' => 'VF3GHIJKL87654321',
        'model' => 'VinFast VF9',
        'year' => 2024,
        'color' => 'Trắng Ngọc Trai',
        'customer_id' => 2,
        'customer_name' => 'Trần Thị B',
        'customer_phone' => '0912345678',
        'purchase_date' => '2024-02-20',
        'warranty_start_date' => '2024-02-20',
        'warranty_end_date' => '2026-02-20',
        'status' => 'active',
        'mileage' => 3000,
        'battery_capacity' => '123 kWh',
        'motor_power' => '300 kW',
        'license_plate' => '51H-67890',
        'service_center_id' => 1,
        'registration_date' => '2024-02-20',
        'last_service_date' => '2024-09-20'
    ],
    [
        'id' => 3,
        'vin' => 'VF3MNOPQR11111111',
        'model' => 'VinFast VF8',
        'year' => 2024,
        'color' => 'Xanh Đại Dương',
        'customer_id' => 3,
        'customer_name' => 'Lê Văn C',
        'customer_phone' => '0923456789',
        'purchase_date' => '2024-11-01',
        'warranty_start_date' => '2024-11-01',
        'warranty_end_date' => '2026-11-01',
        'status' => 'active',
        'mileage' => 500,
        'battery_capacity' => '87.7 kWh',
        'motor_power' => '300 kW',
        'license_plate' => '43B-98765',
        'service_center_id' => 1,
        'registration_date' => '2024-11-05',
        'last_service_date' => null
    ],
    [
        'id' => 4,
        'vin' => 'VF3STUVWX22222222',
        'model' => 'VinFast VF9',
        'year' => 2024,
        'color' => 'Đỏ Quyến Rũ',
        'customer_id' => 4,
        'customer_name' => 'Phạm Thị D',
        'customer_phone' => '0934567890',
        'purchase_date' => '2024-11-05',
        'warranty_start_date' => '2024-11-05',
        'warranty_end_date' => '2026-11-05',
        'status' => 'registered',
        'mileage' => 0,
        'battery_capacity' => '123 kWh',
        'motor_power' => '300 kW',
        'license_plate' => null,
        'service_center_id' => 1,
        'registration_date' => '2024-11-05',
        'last_service_date' => null
    ]
];

// Mock customers data for lookup
$mockCustomers = [
    ['id' => 1, 'name' => 'Nguyễn Văn A', 'phone' => '0901234567', 'email' => 'nguyenvana@example.com'],
    ['id' => 2, 'name' => 'Trần Thị B', 'phone' => '0912345678', 'email' => 'tranthib@example.com'],
    ['id' => 3, 'name' => 'Lê Văn C', 'phone' => '0923456789', 'email' => 'levanc@example.com'],
    ['id' => 4, 'name' => 'Phạm Thị D', 'phone' => '0934567890', 'email' => 'phamthid@example.com']
];

// Vehicles endpoints - simplified implementation
if (strpos($uri, '/api/vehicles') === 0) {
    try {
        // GET /api/vehicles - List all vehicles
        if ($method === 'GET' && $uri === '/api/vehicles') {
            $customer_id = $_GET['customer_id'] ?? null;
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $filteredVehicles = $mockVehicles;
            if ($customer_id) {
                $filteredVehicles = array_filter($mockVehicles, function($v) use ($customer_id) {
                    return $v['customer_id'] == $customer_id;
                });
            }
            
            $total = count($filteredVehicles);
            $vehicles = array_slice(array_values($filteredVehicles), $offset, $limit);
            
            echo json_encode([
                'success' => true,
                'data' => $vehicles,
                'meta' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset
                ],
                'message' => 'Vehicles retrieved successfully'
            ]);
            exit;
        }
        
        // POST /api/vehicles - Create new vehicle
        if ($method === 'POST' && $uri === '/api/vehicles') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Simple validation
            if (empty($input['vin']) || empty($input['model']) || empty($input['customer_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'VIN, model, and customer_id are required'
                ]);
                exit;
            }
            
            // Create vehicle
            $newVehicle = array_merge([
                'id' => count($mockVehicles) + 1,
                'status' => 'active',
                'registration_date' => date('Y-m-d'),
                'warranty_start_date' => $input['purchase_date'] ?? date('Y-m-d'),
                'warranty_end_date' => date('Y-m-d', strtotime('+2 years'))
            ], $input);
            
            echo json_encode([
                'success' => true,
                'data' => $newVehicle,
                'message' => 'Vehicle created successfully'
            ]);
            exit;
        }
        
        // GET /api/vehicles/{vin} - Get vehicle by VIN
        if ($method === 'GET' && preg_match('/\/api\/vehicles\/([A-Za-z0-9]+)$/', $uri, $matches)) {
            $vin = $matches[1];
            $vehicle = null;
            
            foreach ($mockVehicles as $v) {
                if ($v['vin'] === $vin) {
                    $vehicle = $v;
                    break;
                }
            }
            
            if ($vehicle) {
                echo json_encode([
                    'success' => true,
                    'data' => $vehicle,
                    'message' => 'Vehicle retrieved successfully'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Vehicle not found'
                ]);
            }
            exit;
        }
        
        // Handle warranty endpoint
        if (preg_match('/\/api\/vehicles\/([A-Z0-9]+)\/warranty/', $uri, $matches)) {
            $vin = $matches[1];
            echo json_encode([
                'success' => true,
                'data' => [
                    'vin' => $vin,
                    'warranty_active' => true,
                    'warranty_start_date' => '2024-01-15',
                    'warranty_end_date' => '2026-01-15',
                    'remaining_days' => 400,
                    'coverage' => [
                        'battery' => ['covered' => true, 'duration' => '8 năm hoặc 160,000 km'],
                        'motor' => ['covered' => true, 'duration' => '3 năm hoặc 100,000 km'],
                        'electrical' => ['covered' => true, 'duration' => '2 năm hoặc 50,000 km']
                    ]
                ],
                'message' => 'Vehicle warranty information retrieved successfully'
            ]);
            exit;
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => $e->getMessage()
            ],
            'timestamp' => date('c')
        ]);
    }
}


if ($uri === '/api/vehicles/search' && $method === 'GET') {
    $query = $_GET['q'] ?? '';
    $type = $_GET['type'] ?? 'all'; // vin, customer_name, license_plate, all
    
    $results = [];
    
    if (!empty($query)) {
        foreach ($mockVehicles as $vehicle) {
            $match = false;
            
            switch ($type) {
                case 'vin':
                    $match = stripos($vehicle['vin'], $query) !== false;
                    break;
                case 'customer_name':
                    $match = stripos($vehicle['customer_name'], $query) !== false;
                    break;
                case 'license_plate':
                    $match = $vehicle['license_plate'] && stripos($vehicle['license_plate'], $query) !== false;
                    break;
                case 'all':
                default:
                    $match = stripos($vehicle['vin'], $query) !== false ||
                           stripos($vehicle['customer_name'], $query) !== false ||
                           ($vehicle['license_plate'] && stripos($vehicle['license_plate'], $query) !== false) ||
                           stripos($vehicle['model'], $query) !== false;
                    break;
            }
            
            if ($match) {
                $results[] = $vehicle;
            }
        }
    } else {
        // Return recent vehicles if no query
        $results = array_slice($mockVehicles, 0, 10);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $results,
        'message' => 'Vehicle search completed',
        'total' => count($results)
    ]);
    exit;
}



// Customer lookup endpoint
if ($uri === '/api/customers/search' && $method === 'GET') {
    $query = $_GET['q'] ?? '';
    $results = [];
    
    if (!empty($query)) {
        foreach ($mockCustomers as $customer) {
            if (stripos($customer['name'], $query) !== false || 
                stripos($customer['phone'], $query) !== false ||
                stripos($customer['email'], $query) !== false) {
                $results[] = $customer;
            }
        }
    } else {
        $results = $mockCustomers;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $results,
        'message' => 'Customer search completed',
        'total' => count($results)
    ]);
    exit;
}

// Vehicle registration endpoint (POST)
if ($uri === '/api/vehicles/register' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validation
    $required = ['vin', 'model', 'year', 'color', 'customer_id', 'purchase_date'];
    $errors = [];
    
    foreach ($required as $field) {
        if (empty($input[$field])) {
            $errors[$field] = ucfirst($field) . ' is required';
        }
    }
    
    // Check if VIN already exists
    $vinExists = false;
    foreach ($mockVehicles as $vehicle) {
        if ($vehicle['vin'] === $input['vin']) {
            $vinExists = true;
            break;
        }
    }
    
    if ($vinExists) {
        $errors['vin'] = 'VIN already registered';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'errors' => $errors,
            'message' => 'Validation failed'
        ]);
        exit;
    }
    
    // Find customer info
    $customer = null;
    foreach ($mockCustomers as $c) {
        if ($c['id'] == $input['customer_id']) {
            $customer = $c;
            break;
        }
    }
    
    // Create new vehicle record
    $newVehicle = [
        'id' => count($mockVehicles) + 1,
        'vin' => $input['vin'],
        'model' => $input['model'],
        'year' => (int)$input['year'],
        'color' => $input['color'],
        'customer_id' => (int)$input['customer_id'],
        'customer_name' => $customer ? $customer['name'] : 'Unknown',
        'customer_phone' => $customer ? $customer['phone'] : '',
        'purchase_date' => $input['purchase_date'],
        'warranty_start_date' => $input['purchase_date'],
        'warranty_end_date' => date('Y-m-d', strtotime($input['purchase_date'] . ' +2 years')),
        'status' => 'registered',
        'mileage' => 0,
        'battery_capacity' => stripos($input['model'], 'VF8') !== false ? '87.7 kWh' : '123 kWh',
        'motor_power' => '300 kW',
        'license_plate' => $input['license_plate'] ?? null,
        'service_center_id' => 1,
        'registration_date' => date('Y-m-d'),
        'last_service_date' => null
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $newVehicle,
        'message' => 'Vehicle registered successfully'
    ]);
    exit;
}

// Vehicle lookup endpoint (legacy)
if ($uri === '/api/vehicles/lookup' && $method === 'GET') {
    $vin = $_GET['vin'] ?? '';
    
    $vehicle = null;
    foreach ($mockVehicles as $v) {
        if ($v['vin'] === $vin) {
            $vehicle = $v;
            break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $vehicle ? [
            'vin' => $vehicle['vin'],
            'exists' => true,
            'customer_id' => $vehicle['customer_id'],
            'customer_name' => $vehicle['customer_name'],
            'model' => $vehicle['model'],
            'warranty_active' => true,
            'warranty_end_date' => $vehicle['warranty_end_date']
        ] : [
            'vin' => $vin,
            'exists' => false
        ],
        'message' => 'Vehicle lookup completed'
    ]);
    exit;
}

// Default response
http_response_code(404);
echo json_encode([
    'success' => false,
    'message' => 'Endpoint not found',
    'service' => 'vehicle-service'
]);