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
        'version' => '1.0.0'
    ]);
    exit;
    exit;
}

// For all other API endpoints, route to the API handler
if (strpos($uri, '/api/') === 0) {
    require_once __DIR__ . '/../src/routes/api.php';
    exit;
}
        'vin' => 'VF3GHIJKL87654321',
        'model' => 'VinFast VF9',
        'year' => 2024,
        'color' => 'Trắng Ngọc Trai',
        'customer_id' => 2,
        'purchase_date' => '2024-02-20',
        'warranty_start_date' => '2024-02-20',
        'warranty_end_date' => '2026-02-20',
        'status' => 'active',
        'mileage' => 3000,
        'battery_capacity' => '123 kWh',
        'motor_power' => '300 kW'
    ]
];

// Vehicles endpoint using new controller
if (strpos($uri, '/api/vehicles') === 0) {
    $controller = new VehicleController();
    $request = new Request();
    
    try {
        if ($method === 'POST' && $uri === '/api/vehicles') {
            // POST /vehicles - Create new vehicle
            $result = $controller->store($request);
            echo $result->getContent();
        } elseif ($method === 'GET' && preg_match('/\/api\/vehicles\/([A-Za-z0-9]+)$/', $uri, $matches)) {
            // GET /vehicles/{vin} - Get vehicle by VIN
            $vin = $matches[1];
            $result = $controller->show($vin);
            echo $result->getContent();
        } elseif ($method === 'GET' && $uri === '/api/vehicles') {
            // GET /vehicles or GET /vehicles?customer_id=xxx
            $result = $controller->index($request);
            echo $result->getContent();
        } elseif ($method === 'PUT' && preg_match('/\/api\/vehicles\/([A-Za-z0-9]+)$/', $uri, $matches)) {
            // PUT /vehicles/{vin} - Update vehicle
            $vin = $matches[1];
            $result = $controller->update($request, $vin);
            echo $result->getContent();
        } else {
            // Handle legacy warranty endpoint
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
            }
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
    exit;
}

// Vehicle lookup endpoint
if ($uri === '/api/vehicles/lookup' && $method === 'GET') {
    $vin = $_GET['vin'] ?? '';
    echo json_encode([
        'success' => true,
        'data' => [
            'vin' => $vin,
            'exists' => true,
            'customer_id' => 1,
            'model' => 'VinFast VF8',
            'warranty_active' => true,
            'warranty_end_date' => '2026-01-15'
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