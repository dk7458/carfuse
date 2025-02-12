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
return simpleDispatcher(function (RouteCollector $router) use ($publicRoutes, $protectedRoutes, &$registeredRoutes) {
    // ✅ Register public routes
    foreach ($publicRoutes as $route) {
        if (!isset($registeredRoutes[$route])) {
            $router->addRoute(['GET', 'POST'], $route, function() use ($route) {
                $filePath = __DIR__ . "/../public" . ($route === '/' ? "/index.php" : "{$route}.php");

                if (file_exists($filePath)) {
                    include $filePath;
                } else {
                    http_response_code(404);
                    echo json_encode(["error" => "Page not found"]);
                }
            });
            $registeredRoutes[$route] = true;
        }
    }

    // ✅ Register protected routes with JWT validation
    foreach ($protectedRoutes as $route) {
        if (!isset($registeredRoutes[$route])) {
            $router->addRoute(['GET', 'POST'], $route, function() use ($route) {
                AuthMiddleware::validateJWT(true);
                $filePath = __DIR__ . "/../public" . "{$route}.php";

                if (file_exists($filePath)) {
                    include $filePath;
                } else {
                    http_response_code(404);
                    echo json_encode(["error" => "Page not found"]);
                }
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
