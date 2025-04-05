<?php

// Bootstrap the application (loads autoloading, env, config, DI setup)
$bootstrap = require_once __DIR__ . '/../bootstrap.php';

// Get access to the pre-configured container and logger
global $container, $logger;

// Create PSR-7 request from globals
$request = $container->get('request');

try {
    // Get the router dispatcher from routes.php
    $dispatcher = require_once __DIR__ . '/../config/routes.php';
    $dispatcher = $dispatcher($container);
    
    // Extract method and URI
    $httpMethod = $request->getMethod();
    $uri = $request->getUri()->getPath();
    
    // Strip query string and decode URI
    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }
    $uri = rawurldecode($uri);
    
    // Dispatch the request through FastRoute
    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
    
    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            // 404 Not Found
            $response = $container->get('response')->withStatus(404);
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Route not found'
            ]));
            break;
            
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            // 405 Method Not Allowed
            $allowedMethods = $routeInfo[1];
            $response = $container->get('response')->withStatus(405);
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Method not allowed',
                'allowed_methods' => $allowedMethods
            ]));
            break;
            
        case FastRoute\Dispatcher::FOUND:
            // Route found - execute the handler
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            
            // Call the controller method with the request and route parameters
            $response = $handler($request, $vars);
            break;
    }
    
} catch (Exception $e) {
    // Log the error
    $logger->error('Request processing error: ' . $e->getMessage(), [
        'exception' => get_class($e),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Return error response
    $response = $container->get('response')->withStatus(500);
    $response->getBody()->write(json_encode([
        'status' => 'error',
        'message' => 'Internal server error'
    ]));
}

// Send the response
$responseEmitter = $container->get('responseEmitter');
$responseEmitter->emit($response);