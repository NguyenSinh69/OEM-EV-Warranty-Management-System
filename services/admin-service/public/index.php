<?php
require_once dirname(__DIR__) . '/src/Core/ResponseHelper.php';
require_once dirname(__DIR__) . '/src/Core/AuthMiddleware.php';
require_once dirname(__DIR__) . '/src/app/Http/Controllers/AdminController.php';

use App\Http\Controllers\AdminController;
use Core\ResponseHelper;

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

$controller = new AdminController();

if ($uri === '/admin/roles' && $method === 'GET') {
    $controller->getRoles();
} elseif (preg_match('#^/admin/claims/(\d+)/decision$#', $uri, $matches) && $method === 'POST') {
    $controller->decideClaim($matches[1]);
} else {
    ResponseHelper::json(['error' => 'Not Found'], 404);
}
