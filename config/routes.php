<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

use App\Middleware\AuthMiddleware;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

// Define debug log file for route resolution
$logFile = __DIR__ . '/../logs/debug.log';
$timestamp = date('Y-m-d H:i:s');

// Define public and protected routes arrays
$publicRoutes = [
    '/', 
    '/home', 
    '/auth/login', 
    '/auth/register', 
    '/vehicles.php'
];

$protectedRoutes = [
    '/dashboard.php', 
    '/profile.php', 
    '/reports.php'
];

$dispatcher = simpleDispatcher(function (RouteCollector $router) use ($publicRoutes, $protectedRoutes) {
    // Register public routes without JWT authentication
    foreach ($publicRoutes as $route) {
        $router->addRoute(['GET', 'POST'], $route, function() use ($route) {
            // Load correct file for public routes
            if ($route === '/') {
                include BASE_PATH . "/public/index.php";
            } elseif (strpos($route, '.php') === false) {
                include BASE_PATH . "/public{$route}.php";
            } else {
                include BASE_PATH . "/public{$route}";
            }
        });
    }
    // Register protected routes with JWT validation
    foreach ($protectedRoutes as $route) {
        $router->addRoute(['GET', 'POST'], $route, function() use ($route) {
            AuthMiddleware::validateJWT(true);
            include BASE_PATH . "/public{$route}";
        });
    }

    // Dynamically register all view files in /public/views
    $viewsDir = __DIR__ . '/../public/views';
    $viewRoutes = [];
    if (is_dir($viewsDir)) {
        foreach (scandir($viewsDir) as $file) {
            if (is_file($viewsDir . '/' . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $route = '/' . str_replace('.php', '', $file);
                $viewRoutes[$route] = $viewsDir . '/' . $file;
            }
        }
    }

    // Ensure specific routes are correctly routed
    $requiredRoutes = [
        '/dashboard'  => $viewsDir . '/dashboard.php',
        '/profile'    => $viewsDir . '/profile.php',
        '/auth/login' => $viewsDir . '/auth/login.php',
    ];
    $viewRoutes = array_merge($viewRoutes, $requiredRoutes);

    // Register routes (example using FastRoute)
    foreach ($viewRoutes as $route => $file) {
        $router->addRoute('GET', $route, $file);
    }

    // Default route for unmatched requests
    $router->addRoute('GET', '/{any:.+}', 'default.php');
});

// Log all route resolutions
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

// Log route resolutions
if (isset($_SERVER['REQUEST_URI'])) {
    $route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    file_put_contents(__DIR__ . '/../logs/debug.log', "[" . date('Y-m-d H:i:s') . "] Route resolved: {$route}\n", FILE_APPEND);
}
?>
