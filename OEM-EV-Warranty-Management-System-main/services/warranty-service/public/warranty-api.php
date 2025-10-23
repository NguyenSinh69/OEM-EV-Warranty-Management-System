<?php
require __DIR__ . '/../vendor/autoload.php'; 

$vehicleService = new Dell\WarrantyService\Services\VehicleService();

header('Content-Type: application/json');

// Đọc dữ liệu từ file (tạm thay cho database)
$dataFile = 'claims.json';
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}
$claims = json_decode(file_get_contents($dataFile), true);

// Lấy thông tin request
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

function saveClaims($claims, $file) {
    file_put_contents($file, json_encode($claims, JSON_PRETTY_PRINT));
}

// ------------------ POST /claims ------------------
if ($method === 'POST' && preg_match('#^/claims$#', $path)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $vin = $input['vin'] ?? null;
    $customer_id = $input['customer_id'] ?? null;
    $description = $input['description'] ?? '';

    if (!$vin || !$customer_id) {
        http_response_code(400);
        echo json_encode(['error' => 'vin and customer_id are required']);
        exit;
    }

    // Validate VIN tồn tại qua mock service
    $vin = $input['vehicle_vin'] ?? '';
    if (!$vehicleService->validateVinExists($vin)) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false,
        'message' => 'VIN is invalid or does not exist (Mock validation failed).'
    ]);
    exit;
    }

    $newClaim = [
        'id' => uniqid(),
        'vin' => $vin,
        'customer_id' => $customer_id,
        'status' => 'PENDING', // mặc định
        'description' => $description,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $claims[] = $newClaim;
    saveClaims($claims, $dataFile);

    echo json_encode(['message' => 'Claim created successfully', 'claim' => $newClaim]);
    exit;
}

// ------------------ GET /claims/{id} ------------------
if ($method === 'GET' && preg_match('#^/claims/([a-zA-Z0-9]+)$#', $path, $matches)) {
    $id = $matches[1];
    foreach ($claims as $claim) {
        if ($claim['id'] === $id) {
            echo json_encode($claim);
            exit;
        }
    }
    http_response_code(404);
    echo json_encode(['error' => 'Claim not found']);
    exit;
}

// ------------------ GET /claims, GET /claims?vin=, GET /claims?status= ------------------

if ($_SERVER['REQUEST_METHOD'] === 'GET' && parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/claims') {
    
    // Sử dụng $claims (hoặc $mockClaims) là nguồn dữ liệu gốc
    $filteredClaims = $claims; 
    
    $vinParam = $_GET['vin'] ?? null;
    $statusParam = $_GET['status'] ?? null;
    
    // 1. Filter theo VIN (GET /claims?vin=)
    if ($vinParam) {
        $filteredClaims = array_filter($filteredClaims, function($claim) use ($vinParam) {
            // Đảm bảo sử dụng key VIN thống nhất ('vin' hoặc 'vehicle_vin')
            return strtolower($claim['vin'] ?? '') === strtolower($vinParam); 
        });
    }

    // 2. Filter theo Status (GET /claims?status=)
    if ($statusParam) {
        $filteredClaims = array_filter($filteredClaims, function($claim) use ($statusParam) {
            return strtolower($claim['status'] ?? '') === strtolower($statusParam);
        });
    }

    // Trả về kết quả JSON
    http_response_code(200);
    echo json_encode([
        'success' => true,
        // array_values() để đảm bảo mảng được mã hóa thành JSON Array [] chứ không phải Object {}
        'data' => array_values($filteredClaims), 
        'message' => 'Warranty claims retrieved successfully'
    ]);
    exit;
}

// ------------------ GET /claims/status/{status} ------------------
if ($method === 'GET' && preg_match('#^/claims/status/([A-Z]+)$#', $path, $matches)) {
    $status = $matches[1];
    $filtered = array_values(array_filter($claims, fn($c) => strtoupper($c['status']) === $status));
    echo json_encode($filtered);
    exit;
}

// ------------------ Default ------------------
http_response_code(404);
echo json_encode(['error' => 'Endpoint not found']);
