<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the requested path from query parameter
$path = $_GET['path'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Docker admin service URL
$dockerUrl = 'http://localhost:8004';

// Get request body for POST/PUT requests
$requestBody = null;
if ($method === 'POST' || $method === 'PUT') {
    $requestBody = file_get_contents('php://input');
}

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $dockerUrl . $path);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

// Set headers
$headers = ['Content-Type: application/json'];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Add request body if needed
if ($requestBody) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
}

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

// Handle cURL errors
if ($error) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Connection failed: ' . $error,
        'success' => false
    ]);
    exit();
}

// Return response
http_response_code($httpCode);
echo $response;
?>