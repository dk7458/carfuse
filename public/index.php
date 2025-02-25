<?php
use function getLogger;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\App;

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ Load Bootstrap (Dependencies, Configs, Logger, DB)
$bootstrap = require_once __DIR__ . '/../bootstrap.php';
// Replace bootstrap logger with centralized logger
$logger = getLogger('api');

// Create Container using PHP-DI
$container = new Container();
AppFactory::setContainer($container);

// Create App instance
$app = AppFactory::create();

// Load routes
(require __DIR__ . '/../config/routes.php')($app);

// Ensure the app is of type Slim\App before calling dispatch
if ($app instanceof App) {
    $app->run();
} else {
    throw new RuntimeException('The application instance is not of type Slim\App');
}
?>
