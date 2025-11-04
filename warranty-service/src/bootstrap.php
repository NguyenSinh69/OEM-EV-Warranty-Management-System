<?php
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;


require __DIR__ . '/../vendor/autoload.php';


// Load env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();


// Timezone
if ($tz = getenv('APP_TIMEZONE')) date_default_timezone_set($tz);


// Create app without DI bridge
$app = AppFactory::create();


// Add body parser & routing middleware
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();


// Error middleware
$errorMiddleware = $app->addErrorMiddleware((bool)getenv('APP_DEBUG'), true, true);


// Return app
return $app;