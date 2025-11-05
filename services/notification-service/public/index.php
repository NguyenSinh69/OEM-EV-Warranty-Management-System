<?php

require_once __DIR__ . '/../src/bootstrap.php';

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\CampaignController;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Health check endpoint
if ($uri === '/api/health') {
    echo json_encode([
        'status' => 'healthy',
        'service' => 'notification-service',
        'timestamp' => date('c'),
        'version' => '2.0.0',
        'features' => [
            'notifications',
            'appointments', 
            'inventory',
            'campaigns',
            'email_sms_integration'
        ]
    ]);
    exit;
}

// Route handling
try {
    // Initialize controllers
    $notificationController = new NotificationController();
    $appointmentController = new AppointmentController();
    $inventoryController = new InventoryController();
    $campaignController = new CampaignController();
    
    // Notifications routes
    if (preg_match('#^/api/notifications/send$#', $uri) && $method === 'POST') {
        echo $notificationController->send();
        exit;
    }
    
    if (preg_match('#^/api/notifications/(\d+)$#', $uri, $matches) && $method === 'GET') {
        echo $notificationController->getByCustomer($matches[1]);
        exit;
    }
    
    if (preg_match('#^/api/notifications/(\d+)/read$#', $uri, $matches) && $method === 'PUT') {
        echo $notificationController->markAsRead($matches[1]);
        exit;
    }
    
    // Appointments routes
    if ($uri === '/api/appointments' && $method === 'POST') {
        echo $appointmentController->create();
        exit;
    }
    
    if ($uri === '/api/appointments/calendar' && $method === 'GET') {
        echo $appointmentController->getCalendar();
        exit;
    }
    
    if (preg_match('#^/api/appointments/(\d+)$#', $uri, $matches) && $method === 'GET') {
        echo $appointmentController->show($matches[1]);
        exit;
    }
    
    if (preg_match('#^/api/appointments/(\d+)$#', $uri, $matches) && $method === 'PUT') {
        echo $appointmentController->update($matches[1]);
        exit;
    }
    
    // Inventory routes
    if ($uri === '/api/inventory' && $method === 'GET') {
        echo $inventoryController->index();
        exit;
    }
    
    if ($uri === '/api/inventory/update' && $method === 'POST') {
        echo $inventoryController->updateStock();
        exit;
    }
    
    if ($uri === '/api/inventory/allocate' && $method === 'POST') {
        echo $inventoryController->allocateParts();
        exit;
    }
    
    if ($uri === '/api/inventory/alerts' && $method === 'GET') {
        echo $inventoryController->getAlerts();
        exit;
    }
    
    // Campaign routes
    if ($uri === '/api/notifications/campaign' && $method === 'POST') {
        echo $campaignController->create();
        exit;
    }
    
    if ($uri === '/api/campaigns' && $method === 'GET') {
        echo $campaignController->index();
        exit;
    }
    
    // Default 404 response
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Endpoint not found',
        'service' => 'notification-service',
        'uri' => $uri,
        'method' => $method
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage(),
        'service' => 'notification-service'
    ]);
}