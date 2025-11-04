<?php
require_once __DIR__ . '/../src/Controllers/WarrantyClaimController.php';

header('Content-Type: application/json');

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

$controller = new WarrantyClaimController();

if ($method === 'POST' && preg_match('#^/api/warranty-claims$#', $uri)) {
    $data = json_decode(file_get_contents('php://input'), true);
    echo json_encode($controller->createClaim($data));
} elseif ($method === 'GET' && preg_match('#^/api/warranty-claims$#', $uri)) {
    echo json_encode($controller->getAllClaims());
} else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint not found"]);
}
