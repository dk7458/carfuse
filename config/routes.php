<?php

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Middleware\AuthMiddleware;

// ✅ Prevent Function Redeclaration
if (!function_exists('getViewFiles')) {
    function getViewFiles($baseDir)
    {
        $views = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $relativePath = str_replace([$baseDir, '\\'], ['', '/'], $file->getPathname());
                $routePath = '/' . trim(str_replace('.php', '', $relativePath), '/');

                // ✅ Prevent duplicate route registrations
                if (!isset($views[$routePath])) {
                    $views[$routePath] = $file->getPathname();
                }
            }
        }
        return $views;
    }
}

// ✅ Base Directory for Views
$baseViewPath = __DIR__ . '/../public/views';
$viewFiles = getViewFiles($baseViewPath);

// ✅ Setup FastRoute Dispatcher
return simpleDispatcher(function (RouteCollector $router) use ($viewFiles) {
    
    $registeredRoutes = []; // ✅ Track registered routes to prevent duplication

    // ✅ Register View Routes Dynamically
    foreach ($viewFiles as $route => $filePath) {
        if (!isset($registeredRoutes[$route])) {
            $router->addRoute(['GET', 'POST'], $route, function () use ($filePath) {
                include $filePath;
            });
            $registeredRoutes[$route] = true;
        }
    }

    // ✅ Protected Routes (Require JWT Authentication)
    $protectedRoutes = ['/dashboard', '/profile', '/reports'];
    foreach ($protectedRoutes as $route) {
        if (isset($viewFiles[$route]) && !isset($registeredRoutes[$route])) {
            $router->addRoute(['GET', 'POST'], $route, function () use ($route, $viewFiles) {
                AuthMiddleware::validateJWT(true);
                include $viewFiles[$route];
            });
            $registeredRoutes[$route] = true;
        }
    }

    // ✅ Dynamic API Routing (Prevent Direct Access)
    $router->addRoute(['GET', 'POST'], '/api/{endpoint:.+}', function ($vars) {
        $apiFile = __DIR__ . "/../public/api/" . basename($vars['endpoint']) . ".php"; // ✅ Security check

        if (file_exists($apiFile) && !isset($registeredRoutes["/api/" . $vars['endpoint']])) {
            include $apiFile;
            $registeredRoutes["/api/" . $vars['endpoint']] = true;
        } else {
            http_response_code(404);
            echo json_encode(["error" => "API not found"]);
        }
    });

    // ✅ Default Route for Unmatched Requests
    $router->addRoute('GET', '/{any:.+}', function () {
        http_response_code(404);
        echo json_encode(["error" => "Page not found"]);
    });
});
