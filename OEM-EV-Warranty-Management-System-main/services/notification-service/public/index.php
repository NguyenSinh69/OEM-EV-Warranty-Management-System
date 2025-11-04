<?php

// Simple index file for notification service
header('Content-Type: application/json');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Health check endpoint
if ($uri === '/api/health') {
    echo json_encode([
        'status' => 'healthy',
        'service' => 'notification-service',
        'timestamp' => date('c'),
        'version' => '1.0.0'
    ]);
    exit;
}

// Simple routing
if (strpos($uri, '/api/notifications') === 0) {
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        echo json_encode([
            'success' => true,
            'message' => 'Notification sent successfully',
            'data' => [
                'notification_id' => uniqid('notif_'),
                'customer_id' => $input['customer_id'] ?? null,
                'type' => $input['type'] ?? 'general',
                'status' => 'sent',
                'sent_at' => date('c')
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'service' => 'notification-service',
            'message' => 'Notification service is running',
            'endpoint' => $uri,
            'method' => $method
        ]);
    }
    exit;
}

// Default response
http_response_code(404);
echo json_encode([
    'success' => false,
    'message' => 'Endpoint not found',
    'service' => 'notification-service'
]);