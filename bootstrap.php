<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Psr\Log\LoggerInterface;
use Stringable;

// Load environment variables
try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (InvalidPathException $e) {
    // Log error if .env file is missing
    $logger = new class implements LoggerInterface {
        // ...implement all methods of LoggerInterface...
        public function emergency(string|Stringable $message, array $context = array()): void { /* ... */ }
        public function alert(string|Stringable $message, array $context = array()): void { /* ... */ }
        public function critical(string|Stringable $message, array $context = array()): void { /* ... */ }
        public function error(string|Stringable $message, array $context = array()): void { /* ... */ }
        public function warning(string|Stringable $message, array $context = array()): void { /* ... */ }
        public function notice(string|Stringable $message, array $context = array()): void { /* ... */ }
        public function info(string|Stringable $message, array $context = array()): void { /* ... */ }
        public function debug(string|Stringable $message, array $context = array()): void { /* ... */ }
        public function log($level, string|Stringable $message, array $context = array()): void { /* ... */ }
    };
    $logger->error('Environment file not found: ' . $e->getMessage());
}

// Load dependencies
$dependencies = require __DIR__ . '/config/dependencies.php';

// Validate required services
$requiredServices = [
    'NotificationService',
    'TokenService',
    'Validator',
];

foreach ($requiredServices as $service) {
    if (!isset($dependencies[$service])) {
        $logger->error("Required service {$service} is missing in dependencies.");
        // Run composer dump-autoload to regenerate the autoload files
        exec('composer dump-autoload');
        break;
    }
}

// Ensure all dependencies are correctly initialized
foreach ($dependencies as $dependency) {
    if (is_callable($dependency)) {
        $dependency();
    }
}

// Return the dependencies array for use across the application
return $dependencies;
