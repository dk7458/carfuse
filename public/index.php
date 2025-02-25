<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load dependency container
$container = require_once __DIR__ . '/../config/dependencies.php';

// Get request URI and method
$uri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Load routes configuration
$dispatcher = require_once __DIR__ . '/../config/routes.php';

// Strip query string and decode URI
$uri = rawurldecode(parse_url($uri, PHP_URL_PATH));

// Dispatch the request
$routeInfo = $dispatcher->dispatch($requestMethod, $uri);

// Use RouterHelper to process the route
$routerHelper = new App\Helpers\RouterHelper($container);
$response = $routerHelper->processRouteResult($routeInfo);

// Handle the response if it hasn't been sent yet
if (is_array($response) || is_object($response)) {
    header('Content-Type: application/json');
    echo json_encode($response);
}
?>
