<?php

// Simple index file for vehicle service
header('Content-Type: application/json');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Health check endpoint
if ($uri === '/api/health') {
    echo json_encode([
        'status' => 'healthy',
        'service' => 'vehicle-service',
        'timestamp' => date('c'),
        'version' => '1.0.0'
    ]);
    exit;
}

// Mock vehicle data
$mockVehicles = [
    [
        'id' => 1,
        'vin' => 'VF3ABCDEF12345678',
        'model' => 'VinFast VF8',
        'year' => 2024,
        'color' => 'Đen Kim Cương',
        'customer_id' => 1,
        'purchase_date' => '2024-01-15',
        'warranty_start_date' => '2024-01-15',
        'warranty_end_date' => '2026-01-15',
        'status' => 'active',
        'mileage' => 5000,
        'battery_capacity' => '87.7 kWh',
        'motor_power' => '300 kW'
    ],
    [
        'id' => 2,
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

// Vehicles endpoint
if (strpos($uri, '/api/vehicles') === 0) {
    if ($method === 'GET') {
        if (preg_match('/\/api\/vehicles\/([A-Z0-9]+)\/warranty/', $uri, $matches)) {
            // Get warranty info for specific VIN
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
        } elseif (preg_match('/\/api\/vehicles\/([A-Z0-9]+)$/', $uri, $matches)) {
            // Get specific vehicle by VIN
            $vin = $matches[1];
            $vehicle = null;
            foreach ($mockVehicles as $v) {
                if ($v['vin'] === $vin) {
                    $vehicle = $v;
                    break;
                }
            }
            echo json_encode([
                'success' => true,
                'data' => $vehicle ?: ['message' => 'Vehicle not found'],
                'message' => $vehicle ? 'Vehicle retrieved successfully' : 'Vehicle not found'
            ]);
        } else {
            // List all vehicles
            echo json_encode([
                'success' => true,
                'data' => $mockVehicles,
                'message' => 'Vehicles retrieved successfully'
            ]);
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $newVehicle = array_merge($input, [
            'id' => rand(1000, 9999),
            'status' => 'active',
            'created_at' => date('c')
        ]);
        echo json_encode([
            'success' => true,
            'data' => $newVehicle,
            'message' => 'Vehicle registered successfully'
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