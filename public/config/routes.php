<?php
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

return simpleDispatcher(function (RouteCollector $router) {
    $router->get('/', function () { require __DIR__ . '/../public/index.php'; });
    $router->get('/dashboard', function () { require __DIR__ . '/../public/dashboard.php'; });
    $router->get('/login', function () { require __DIR__ . '/../public/login.php'; });
    $router->get('/profile', function () { require __DIR__ . '/../public/profile.php'; });

    // Minimal API route for testing
    $router->get('/api/test', function () {
        header("Content-Type: application/json");
        echo json_encode(["message" => "API is working"]);
    });
});
