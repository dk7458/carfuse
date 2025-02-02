<?php

require_once __DIR__ . '/../bootstrap.php';
use FastRoute\RouteCollector;

$container = require __DIR__ . '/../bootstrap.php';

$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
    $routes = require __DIR__ . '/../routes/web.php';
    foreach ($routes->getData() as $route) {
        [$method, $uri, $handler] = $route;
        $r->addRoute($method, $uri, $handler);
    }
});

// Fetch method and URI
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Remove query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Not found']);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;

    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = $container[$class];
            echo json_encode($controller->$method(...array_values($vars)));
        } elseif (is_callable($handler)) {
            echo json_encode($handler(...array_values($vars)));
        }
        break;
}
