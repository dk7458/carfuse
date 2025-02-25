<?php

namespace App\Helpers;

use DI\Container;
use FastRoute\Dispatcher;

class RouterHelper
{
    private Container $container;
    
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Handle a route match
     * 
     * @param mixed $handler The route handler (controller@method or callable)
     * @param array $vars Route variables
     * @return mixed Response from the handler
     */
    public function handleRoute($handler, array $vars = [])
    {
        // Handle function/closure directly
        if (is_callable($handler)) {
            return call_user_func_array($handler, $vars);
        } 
        // Handle Controller@method format
        else if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controllerName, $method) = explode('@', $handler);
            
            // Complete controller namespace if it doesn't have one
            if (strpos($controllerName, '\\') === false) {
                $controllerName = 'App\\Controllers\\' . $controllerName;
            }
            
            // Get controller from DI container
            $controller = $this->container->get($controllerName);
            
            // Create request object
            $request = $this->createRequestObject($vars);
            
            // Call the controller method
            return call_user_func_array([$controller, $method], [$request]);
        }
        
        throw new \Exception("Invalid route handler");
    }
    
    /**
     * Create a standardized request object
     * 
     * @param array $routeVars Variables extracted from the route
     * @return object Request object
     */
    private function createRequestObject(array $routeVars): object
    {
        $request = new \stdClass();
        
        // Add route variables
        $request->vars = $routeVars;
        
        // Add request data
        $request->method = $_SERVER['REQUEST_METHOD'];
        $request->uri = $_SERVER['REQUEST_URI'];
        $request->queryParams = $_GET;
        
        // Parse JSON body for appropriate content types
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $request->body = json_decode(file_get_contents('php://input'), true);
        } else {
            $request->body = $_POST;
        }
        
        // Add headers
        $request->headers = getallheaders();
        
        return $request;
    }
    
    /**
     * Process the route dispatcher result
     * 
     * @param array $routeInfo Route information from FastRoute dispatcher
     * @return mixed Response or sends appropriate HTTP error
     */
    public function processRouteResult(array $routeInfo)
    {
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                header('HTTP/1.1 404 Not Found');
                return ApiHelper::sendJsonResponse('error', 'Resource not found', null, 404);
                
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                header('HTTP/1.1 405 Method Not Allowed');
                header('Allow: ' . implode(', ', $allowedMethods));
                return ApiHelper::sendJsonResponse('error', 'Method not allowed', null, 405);
                
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                
                return $this->handleRoute($handler, $vars);
        }
    }
}
