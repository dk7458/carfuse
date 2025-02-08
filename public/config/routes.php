<?php

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\UserController;

return simpleDispatcher(function (RouteCollector $router) {
    // Home Page (Loads index.php)
    $router->get('/', function () {
        require BASE_PATH . '/public/index.php';
    });

    // Authentication
    $router->get('/login', [AuthController::class, 'loginView']);
    $router->post('/login', [UserController::class, 'login']);

    // Dashboard
    $router->get('/dashboard', [DashboardController::class, 'userDashboard']);

    // API Test Route
    $router->get('/api/test', function () {
        header("Content-Type: application/json");
        echo json_encode(["message" => "API is working"]);
    });
});
