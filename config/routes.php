<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

use App\Middleware\AuthMiddleware;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

// ✅ Define debug log file for route resolution
$logFile = __DIR__ . '/../logs/debug.log';
$timestamp = date('Y-m-d H:i:s');

// ✅ Define public and protected routes
$publicRoutes = [
    '/', 
    '/home', 
    '/auth/login', 
    '/auth/register', 
    '/vehicles'
];

$protectedRoutes = [
    '/dashboard', 
    '/profile', 
    '/reports'
];

// ✅ Track registered routes to prevent duplicates
$registeredRoutes = [];

// ✅ Setup FastRoute dispatcher
$dispatcher = simpleDispatcher(function (RouteCollector $router) use ($publicRoutes, $protectedRoutes, &$registeredRoutes) {
    // ✅ Register public routes
    foreach ($publicRoutes as $route) {
        if (!isset($registeredRoutes[$route])) {
            $router->addRoute(['GET', 'POST'], $route, function() use ($route) {
                include __DIR__ . "/../public{$route}.php";
            });
            $registeredRoutes[$route] = true;
        }
    }

    // ✅ Register protected routes with JWT validation
    foreach ($protectedRoutes as $route) {
        if (!isset($registeredRoutes[$route])) {
            $router->addRoute(['GET', 'POST'], $route, function() use ($route) {
                AuthMiddleware::validateJWT(true);
                include __DIR__ . "/../public{$route}.php";
            });
            $registeredRoutes[$route] = true;
        }
    }

    // ✅ Dynamically register all view files in /public/views (Avoid Duplicates)
    $viewsDir = __DIR__ . '/../public/views';
    if (is_dir($viewsDir)) {
        foreach (scandir($viewsDir) as $file) {
            if (is_file("$viewsDir/$file") && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $route = '/' . str_replace('.php', '', $file);
                if (!isset($registeredRoutes[$route])) {
                    $router->addRoute('GET', $route, function() use ($viewsDir, $file) {
                        include "$viewsDir/$file";
                    });
                    $registeredRoutes[$route] = true;
                }
            }
        }
    }

    // ✅ Default route for unmatched requests
    $router->addRoute('GET', '/{any:.+}', function() {
        http_response_code(404);
        echo json_encode(["error" => "Page not found"]);
    });
});

// ✅ Log all route resolutions
$logger = function($routeInfo) use ($logFile, $timestamp) {
    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::FOUND:
            error_log("[$timestamp][info] Route matched: " . json_encode($routeInfo[1]) . "\n", 3, $logFile);
            break;
        case FastRoute\Dispatcher::NOT_FOUND:
            error_log("[$timestamp][error] Route not found\n", 3, $logFile);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            error_log("[$timestamp][error] Method not allowed\n", 3, $logFile);
            break;
    }
};

// ✅ Route Request Execution
return function($method, $uri) use ($dispatcher, $logger) {
    $routeInfo = $dispatcher->dispatch($method, $uri);
    $logger($routeInfo);
    return $routeInfo;
};

// ✅ Log route resolutions
if (isset($_SERVER['REQUEST_URI'])) {
    $route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    file_put_contents(__DIR__ . '/../logs/debug.log', "[" . date('Y-m-d H:i:s') . "] Route resolved: {$route}\n", FILE_APPEND);
}
