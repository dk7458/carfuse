<?php

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

return simpleDispatcher(function (RouteCollector $r) {
    // Home route (simplified)
    $r->addRoute('GET', '/', function () {
        require __DIR__ . '/../public/index.php';
    });

    // Basic API test
    $r->addRoute('GET', '/api/test', function () {
        echo json_encode(["message" => "API is working"]);
    });
});
