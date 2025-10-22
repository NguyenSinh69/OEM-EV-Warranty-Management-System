<?php
require_once(__DIR__ . '/../../vendor/autoload.php');

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
    if (!isValidVin($vin)) {
        http_response_code(400);
        echo json_encode(['error' => 'VIN not found in vehicle-service']);
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

// ------------------ GET /claims?vin= ------------------
if ($method === 'GET' && preg_match('#^/claims$#', parse_url($path, PHP_URL_PATH))) {
    $query = $_GET;
    if (isset($query['vin'])) {
        $vin = $query['vin'];
        $filtered = array_values(array_filter($claims, fn($c) => $c['vin'] === $vin));
        echo json_encode($filtered);
        exit;
    }
    echo json_encode($claims);
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
