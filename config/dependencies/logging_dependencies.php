<?php

/**
 * Logging Dependencies Configuration
 * 
 * This file contains all logging-related dependency registrations for the DI container.
 * It centralizes the logger configuration to make the main dependencies.php cleaner.
 */

use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\MemoryUsageProcessor;

// Define log directory
$logDir = __DIR__ . '/../logs';

// Create logs directory if it doesn't exist
if (!is_dir($logDir)) {
    mkdir($logDir, 0775, true);
}

// Set up formatter for consistent log format
$dateFormat = "Y-m-d H:i:s";
$lineFormat = "[%datetime%] [%level_name%] %channel%: %message% %context% %extra%\n";
$formatter = new LineFormatter($lineFormat, $dateFormat);

// Common processors for all loggers
$processors = [
    new WebProcessor(),
    new IntrospectionProcessor(),
    new MemoryUsageProcessor()
];

// Define logger definitions
$loggerDefinitions = [
    // Main PSR-3 logger
    LoggerInterface::class => function() use ($logDir, $formatter, $processors) {
        $logger = new Logger('system');
        $handler = new RotatingFileHandler(
            $logDir . '/system.log',
            10, // Keep 10 days of logs
            Logger::INFO
        );
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);
        
        // Add processors
        foreach ($processors as $processor) {
            $logger->pushProcessor($processor);
        }
        
        return $logger;
    },
];

// Register specialized loggers
$specializedLoggers = [
    'auth_logger' => [
        'name' => 'auth',
        'level' => Logger::INFO
    ],
    'db_logger' => [
        'name' => 'db',
        'level' => Logger::DEBUG
    ],
    'api_logger' => [
        'name' => 'api',
        'level' => Logger::INFO
    ],
    'security_logger' => [
        'name' => 'security',
        'level' => Logger::INFO
    ],
    'audit_logger' => [
        'name' => 'audit',
        'level' => Logger::INFO
    ],
    'dependencies_logger' => [
        'name' => 'dependencies',
        'level' => Logger::INFO
    ],
    'user_logger' => [
        'name' => 'user',
        'level' => Logger::INFO
    ]
];

// Add specialized loggers to definitions
foreach ($specializedLoggers as $key => $config) {
    $loggerDefinitions[$key] = function() use ($logDir, $formatter, $processors, $config) {
        $logger = new Logger($config['name']);
        $handler = new RotatingFileHandler(
            $logDir . '/' . $config['name'] . '.log',
            10, // Keep 10 days of logs
            $config['level']
        );
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);
        
        // Add processors
        foreach ($processors as $processor) {
            $logger->pushProcessor($processor);
        }
        
        return $logger;
    };
}

// Return all logger definitions
return $loggerDefinitions;
