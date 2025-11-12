<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../src/bootstrap.php';

use App\Controllers\ClaimsController;

// Middleware để parse JSON, form-data (Slim 4)
$app->addBodyParsingMiddleware();

// (Nếu bạn có middleware JSON chuẩn hoá header, bật dòng dưới)
// $app->add(\App\Middleware\JsonMiddleware::class);

$claims = new ClaimsController();

/**
 * ===== ADMIN / INTERNAL  =====
 * /claims, /claims/{id}
 */
$app->get   ('/claims',            [$claims, 'index']);
$app->post  ('/claims',            [$claims, 'create']);
$app->get   ('/claims/{id}',       [$claims, 'show']);
$app->put   ('/claims/{id}',       [$claims, 'update']);
$app->delete('/claims/{id}',       [$claims, 'delete']);

/**
 * ===== CUSTOMER (Ticket #34) =====
 * Tất cả route public cho khách hàng sẽ đi qua Kong:
 *  - POST /api/customer/claims                          → tạo claim (status=PENDING)
 *  - GET  /api/customer/claims?customer_id=&status=...  → liệt kê theo customer
 *  - GET  /api/customer/claims/{id}?customer_id=        → chi tiết + kiểm quyền
 *  - POST /api/customer/claims/{id}/attachments         → upload files[] (multipart)
 */
$app->post('/api/customer/claims',                  [$claims, 'create']);
$app->get ('/api/customer/claims',                  [$claims, 'listByCustomer']);
$app->get ('/api/customer/claims/{id}',             [$claims, 'customerDetail']);
$app->post('/api/customer/claims/{id}/attachments', [$claims, 'uploadAttachments']);

$app->run();
