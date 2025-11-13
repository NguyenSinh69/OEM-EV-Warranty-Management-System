<?php

use App\Core\Router;

$router = Router::getInstance();

// Health check
$router->get('/api/health', function($request, $response) {
    $response->json([
        'status' => 'OK',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0'
    ]);
});

// Warranty Claims Routes
$router->get('/api/warranty-claims', 'App\Controllers\WarrantyClaimController@index');
$router->get('/api/warranty-claims/{id}', 'App\Controllers\WarrantyClaimController@show');
$router->post('/api/warranty-claims', 'App\Controllers\WarrantyClaimController@store');
$router->put('/api/warranty-claims/{id}', 'App\Controllers\WarrantyClaimController@update');
$router->patch('/api/warranty-claims/{id}/status', 'App\Controllers\WarrantyClaimController@updateStatus');
$router->delete('/api/warranty-claims/{id}', 'App\Controllers\WarrantyClaimController@delete');

// Warranty Claims - Additional endpoints
$router->get('/api/warranty-claims/statistics/summary', 'App\Controllers\WarrantyClaimController@getStatistics');
$router->get('/api/warranty-claims/approval/queue', 'App\Controllers\WarrantyClaimController@getApprovalQueue');

// Vehicle Warranties Routes
$router->get('/api/vehicle-warranties', 'App\Controllers\VehicleWarrantyController@index');
$router->get('/api/vehicle-warranties/{id}', 'App\Controllers\VehicleWarrantyController@show');
$router->get('/api/vehicle-warranties/vehicle/{vehicleId}', 'App\Controllers\VehicleWarrantyController@getByVehicle');
$router->get('/api/vehicle-warranties/vin/{vin}', 'App\Controllers\VehicleWarrantyController@getByVin');
$router->post('/api/vehicle-warranties', 'App\Controllers\VehicleWarrantyController@store');
$router->put('/api/vehicle-warranties/{id}', 'App\Controllers\VehicleWarrantyController@update');

// Vehicles Routes
$router->get('/api/vehicles', 'App\Controllers\VehicleController@index');
$router->get('/api/vehicles/{id}', 'App\Controllers\VehicleController@show');
$router->get('/api/vehicles/vin/{vin}', 'App\Controllers\VehicleController@getByVin');
$router->post('/api/vehicles', 'App\Controllers\VehicleController@store');
$router->put('/api/vehicles/{id}', 'App\Controllers\VehicleController@update');
$router->delete('/api/vehicles/{id}', 'App\Controllers\VehicleController@delete');

// Customers Routes
$router->get('/api/customers', 'App\Controllers\CustomerController@index');
$router->get('/api/customers/{id}', 'App\Controllers\CustomerController@show');
$router->get('/api/customers/{id}/vehicles', 'App\Controllers\CustomerController@getVehicles');
$router->get('/api/customers/{id}/claims', 'App\Controllers\CustomerController@getClaims');
$router->post('/api/customers', 'App\Controllers\CustomerController@store');
$router->put('/api/customers/{id}', 'App\Controllers\CustomerController@update');

// Approval Workflow Routes
$router->get('/api/approvals/claim/{claimId}', 'App\Controllers\ApprovalController@getClaimApprovals');
$router->post('/api/approvals/claim/{claimId}', 'App\Controllers\ApprovalController@processApproval');
$router->get('/api/approvals/pending', 'App\Controllers\ApprovalController@getPendingApprovals');

// Customer Service Integration Routes
$router->post('/api/customer-service/sync-customer', 'App\Controllers\CustomerServiceController@syncCustomer');
$router->post('/api/customer-service/sync-vehicle', 'App\Controllers\CustomerServiceController@syncVehicle');
$router->get('/api/customer-service/customer/{customerCode}', 'App\Controllers\CustomerServiceController@getCustomerInfo');

// Notifications Routes
$router->get('/api/notifications', 'App\Controllers\NotificationController@index');
$router->get('/api/notifications/{id}', 'App\Controllers\NotificationController@show');
$router->patch('/api/notifications/{id}/read', 'App\Controllers\NotificationController@markAsRead');
$router->post('/api/notifications/send', 'App\Controllers\NotificationController@send');

// Authentication Routes (will be implemented later)
$router->post('/api/auth/login', 'App\Controllers\AuthController@login');
$router->post('/api/auth/refresh', 'App\Controllers\AuthController@refresh');
$router->post('/api/auth/logout', 'App\Controllers\AuthController@logout');

// Admin Routes
$router->get('/api/admin/dashboard', 'App\Controllers\AdminController@dashboard');
$router->get('/api/admin/users', 'App\Controllers\AdminController@getUsers');
$router->get('/api/admin/system-logs', 'App\Controllers\AdminController@getSystemLogs');