<?php
use function getLogger;
use FastRoute\Dispatcher;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load Bootstrap (Dependencies, Configs, Logger, DB)
$bootstrap = require_once __DIR__ . '/../bootstrap.php';
// Replace bootstrap logger with centralized logger
$logger = getLogger('api');
$container = $bootstrap['container'];

// Create PSR-17 factories
$psr17Factory = new Psr17Factory();

// Create ServerRequest from globals
$request = $psr17Factory->createServerRequest(
    $_SERVER['REQUEST_METHOD'],
    (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/')
);

// Optionally, you can add query params, headers, etc., from the globals:
$request = $request->withQueryParams($_GET)
                   ->withParsedBody(json_decode(file_get_contents('php://input'), true));

// Create an empty Response object
$response = $psr17Factory->createResponse();

// Get Requested URI (extract path)
$parsedUrl = parse_url($_SERVER['REQUEST_URI']);
$requestPath = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';

// Load routes (which return a closure expecting the DI container)
$routes = require __DIR__ . '/../config/routes.php';
if (is_callable($routes)) {
    // Pass the DI container to the closure to get the dispatcher
    $dispatcher = $routes($container);
} else {
    $dispatcher = $routes;
}

// Verify that we have a proper dispatcher
if (!($dispatcher instanceof Dispatcher)) {
    http_response_code(500);
    echo json_encode(["error" => "Dispatcher is not properly configured."]);
    exit;
}

// Dispatch the request using the method and the path
$routeInfo = $dispatcher->dispatch($request->getMethod(), $requestPath);

switch ($routeInfo[0]) {
    case Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        // If handler is callable, call it with request, response, and route variables
        if (is_callable($handler)) {
            $response = call_user_func($handler, $request, $response, $vars);
        } elseif (is_string($handler) && strpos($handler, '@') !== false) {
            list($class, $method) = explode('@', $handler, 2);
            $controller = $container->get($class);
            if (method_exists($controller, $method)) {
                // Call the controller method passing the request, response, and route variables
                $response = $controller->{$method}($request, $response, $vars);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Controller method not found"]);
                exit;
            }
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Handler not callable"]);
            exit;
        }
        break;

    case Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode(["error" => "Route not found"]);
        exit;

    case Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo json_encode(["error" => "Method Not Allowed"]);
        exit;
}

// Emit the response (basic emitter)
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}
echo $response->getBody();
