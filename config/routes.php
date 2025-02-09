<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

$logFile = __DIR__ . '/../logs/debug.log';
$timestamp = date('Y-m-d H:i:s');

$dispatcher = simpleDispatcher(function (RouteCollector $router) {
    // Register routes in the correct order
    $router->addRoute('GET', '/test', 'test.php');
    $router->addRoute('GET', '/dashboard', '/dashboard/dashboard.php');
    $router->addRoute('GET', '/', '/home.php');
    $router->addRoute('GET', '/profile', 'user/profile.php');
    $router->addRoute('GET', '/user/profile', '/user/profile.php');
    $router->addRoute('GET', '/vehicles', 'vehicles.php');
    $router->addRoute('GET', '/login', '/auth/login.php');
    $router->addRoute('GET', '/{view}', 'default.php'); // Dynamic view route
});

$logger = function($routeInfo) use ($logFile, $timestamp) {
    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::FOUND:
            error_log("[$timestamp][info] Route matched: " . $routeInfo[1] . "\n", 3, $logFile);
            break;
        case FastRoute\Dispatcher::NOT_FOUND:
            error_log("[$timestamp][error] Route not found\n", 3, $logFile);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            error_log("[$timestamp][error] Method not allowed\n", 3, $logFile);
            break;
    }
};

return function($method, $uri) use ($dispatcher, $logger) {
    $routeInfo = $dispatcher->dispatch($method, $uri);
    $logger($routeInfo);
    return $routeInfo;
};
?>
