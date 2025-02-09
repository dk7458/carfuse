<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

$dispatcher = simpleDispatcher(function (RouteCollector $router) {
    // ✅ Define Routes
    $router->get('/', 'home.php');
    $router->get('/dashboard', 'dashboard.php');
    $router->get('/profile', 'profile.php');
    $router->get('/vehicles', 'vehicles.php');

    // ✅ Ensure `/test` works
    $router->get('/test', function () {
        file_put_contents(__DIR__ . '/../logs/debug.log', date('Y-m-d H:i:s') . " - Accessed /test route\n", FILE_APPEND);
        echo json_encode(["status" => "Test route working"]);
    });
});

return $dispatcher;
?>
