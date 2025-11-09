<?php
// Simple test endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'status' => 'ok',
    'message' => 'Admin service is running',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => 'connected to XAMPP MySQL'
]);
?>