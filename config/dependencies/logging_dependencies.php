<?php

/**
 * Logging Dependencies Configuration
 * 
 * This file contains all logging-related dependency registrations for the DI container.
 * It centralizes the logger configuration to make the main dependencies.php cleaner.
 */

use Psr\Log\LoggerInterface;
use DI\Container;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\MemoryUsageProcessor;

/**
 * Register logger dependencies in the container
 * 
 * @param Container $container The DI container
 * @return Container The container with registered loggers
 */
function registerLoggers(Container $container): Container
{
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
    
    // Register main system logger (PSR-3 compatible)
    $container->set(LoggerInterface::class, function() use ($logDir, $formatter, $processors) {
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
    });
    
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
    
    // Register each specialized logger
    foreach ($specializedLoggers as $key => $config) {
        $container->set($key, function() use ($logDir, $formatter, $processors, $config) {
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
        });
    }
    
    return $container;
}

/**
 * Helper function to get a logger by name (useful for direct invocation)
 * 
 * @param string $name Logger name
 * @return Logger The configured logger
 */
function getConfiguredLogger(string $name): Logger
{
    $logDir = __DIR__ . '/../logs';
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }
    
    // Set up formatter
    $dateFormat = "Y-m-d H:i:s";
    $lineFormat = "[%datetime%] [%level_name%] %channel%: %message% %context% %extra%\n";
    $formatter = new LineFormatter($lineFormat, $dateFormat);
    
    // Create logger
    $logger = new Logger($name);
    $handler = new RotatingFileHandler(
        $logDir . '/' . $name . '.log',
        10,
        Logger::INFO
    );
    $handler->setFormatter($formatter);
    $logger->pushHandler($handler);
    
    // Add processors
    $logger->pushProcessor(new WebProcessor());
    $logger->pushProcessor(new IntrospectionProcessor());
    $logger->pushProcessor(new MemoryUsageProcessor());
    
    return $logger;
}

// If this file is included directly, return a container with registered loggers
if (!isset($container) && basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    $container = new Container();
    $container = registerLoggers($container);
    return $container;
}
