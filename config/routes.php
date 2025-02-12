<?php

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Middleware\AuthMiddleware;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

// ✅ Get All View Files Recursively
function getViewFiles($baseDir)
{
    $views = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS));
    
    foreach ($iterator as $file) {
        if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $relativePath = str_replace([$baseDir, '\\'], ['', '/'], $file->getPathname());
            $routePath = '/' . trim(str_replace('.php', '', $relativePath), '/');
            $views[$routePath] = $file->getPathname();
        }
    }

    return $views;
}

// ✅ Base Directory for Views
$baseViewPath = __DIR__ . '/../public/views';
$viewFiles = getViewFiles($baseViewPath);

// ✅ Setup FastRoute Dispatcher
return simpleDispatcher(function (RouteCollector $router) use ($viewFiles) {

    // ✅ Register View Routes Dynamically
    foreach ($viewFiles as $route => $filePath) {
        $router->addRoute(['GET', 'POST'], $route, function () use ($filePath) {
            include $filePath;
        });
    }

    // ✅ Protected Routes (Require JWT Authentication)
    $protectedRoutes = ['/dashboard', '/profile', '/reports'];
    foreach ($protectedRoutes as $route) {
        if (isset($viewFiles[$route])) {
            $router->addRoute(['GET', 'POST'], $route, function () use ($route, $viewFiles) {
                AuthMiddleware::validateJWT(true);
                include $viewFiles[$route];
            });
        }
    }

    // ✅ Dynamic API Routing
    $router->addRoute(['GET', 'POST'], '/api/{endpoint:.+}', function ($vars) {
        $apiFile = __DIR__ . "/../public/api/{$vars['endpoint']}.php";
        if (file_exists($apiFile)) {
            include $apiFile;
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
