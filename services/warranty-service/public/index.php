<?php
require __DIR__ . '/../vendor/autoload.php';


$app = require __DIR__ . '/../src/bootstrap.php';


use App\Controllers\ClaimsController;


$claims = new ClaimsController();


$app->get('/claims', [$claims, 'index']);
$app->post('/claims', [$claims, 'create']);
$app->get('/claims/{id}', [$claims, 'show']);
$app->put('/claims/{id}', [$claims, 'update']);
$app->delete('/claims/{id}', [$claims, 'delete']);


$app->run();