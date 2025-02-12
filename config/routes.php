<?php

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Middleware\AuthMiddleware;
use App\Helpers\SecurityHelper;

// ✅ Setup FastRoute Dispatcher
return simpleDispatcher(function (RouteCollector $router) {

    // ✅ Public Routes (Views & API)
    $publicRoutes = ['/', '/home', '/auth/login', '/auth/register', '/vehicles'];
    foreach ($publicRoutes as $route) {
        $router->addRoute(['GET', 'POST'], $route, function () use ($route) {
            include __DIR__ . "/../public/views" . ($route === '/' ? '/home.php' : "{$route}.php");
        });
    }

    // ✅ Protected Routes (Require JWT Authentication)
    $protectedRoutes = ['/dashboard', '/profile', '/reports'];
    foreach ($protectedRoutes as $route) {
        $router->addRoute(['GET', 'POST'], $route, function () use ($route) {
            include __DIR__ . "/../public/views{$route}.php";
        });
    }

    // ✅ Dynamic API Routing
    $router->addRoute(['GET', 'POST'], '/api/{endpoint}', function ($vars) {
        $apiFile = __DIR__ . "/../public/api.php";
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
