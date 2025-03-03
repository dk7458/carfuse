<?php
/**
 * Logger configuration
 * This file contains functions for creating and configuring loggers
 */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;

/**
 * Creates a configured logger instance
 *
 * @param string $name The logger channel name
 * @param string $logDir The directory to store log files
 * @return Logger The configured logger instance
 */
function createLogger(string $name, string $logDir = null): Logger {
    if ($logDir === null) {
        $logDir = dirname(__DIR__) . '/logs';
    }
    
    // Create directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Create logger
    $logger = new Logger($name);
    
    // Create formatter
    $formatter = new LineFormatter(
        "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
        "Y-m-d H:i:s.u",
        true,
        true
    );
    
    // Add handlers with the formatter
    $streamHandler = new StreamHandler('php://stderr', Logger::DEBUG);
    $streamHandler->setFormatter($formatter);
    $logger->pushHandler($streamHandler);
    
    $fileHandler = new RotatingFileHandler($logDir . "/{$name}.log", 14, Logger::INFO);
    $fileHandler->setFormatter($formatter);
    $logger->pushHandler($fileHandler);
    
    // Add processors
    $logger->pushProcessor(new WebProcessor());
    $logger->pushProcessor(new IntrospectionProcessor());
    
    return $logger;
}

/**
 * Creates the standard set of application loggers
 *
 * @param string $logDir The directory to store log files
 * @return array Array of logger instances indexed by category
 */
function createLoggers(string $logDir = null): array {
    $categories = [
        'app', 'db', 'auth', 'api', 'audit', 'security',
        'payment', 'booking', 'metrics', 'report',
        'revenue', 'dependencies'
    ];
    
    $loggers = [];
    foreach ($categories as $category) {
        try {
            $loggers[$category] = createLogger($category, $logDir);
        } catch (Exception $e) {
            // Create a minimal fallback logger for this category
            $fallbackLogger = new Logger($category . '_fallback');
            $fallbackLogger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));
            $loggers[$category] = $fallbackLogger;
            
            // Log error about logger creation failure
            error_log("Failed to create {$category} logger: " . $e->getMessage());
        }
    }
    
    return $loggers;
}

return [
    'createLogger' => 'createLogger',
    'createLoggers' => 'createLoggers'
];
