<?php

/**
 * Application Bootstrap File
 * 
 * This file initializes the application, loads dependencies, and sets up the router
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Initialize the dependency container
$container = require_once __DIR__ . '/../config/dependencies.php';

// Initialize router
$dispatcher = require_once __DIR__ . '/../config/routes.php';

// Set up error handling
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // This error code is not included in error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Register shutdown function to handle fatal errors
register_shutdown_function(function () use ($container) {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $logger = $container->get(Psr\Log\LoggerInterface::class);
        $logger->critical('Fatal error: ' . $error['message'], [
            'file' => $error['file'],
            'line' => $error['line']
        ]);
        
        if (php_sapi_name() !== 'cli') {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error',
                'code' => 500
            ]);
        }
    }
});

return [
    'container' => $container,
    'dispatcher' => $dispatcher
];
