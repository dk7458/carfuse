<?php

/**
 * Logger Configuration
 *
 * This file initializes Monolog as the application-wide logger,
 * ensuring all services use a single logging instance with proper rotation,
 * formatting, and contextual information.
 */
require_once __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Psr\Log\LoggerInterface;

// ✅ Define Log Directory with proper permissions
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    if (!mkdir($logDir, 0775, true)) {
        error_log("❌ [LOGGER] Failed to create logs directory: {$logDir}");
        die("❌ Logger initialization failed: Could not create log directory.\n");
    }
}

// ✅ Define a consistent formatter for all loggers
$dateFormat = "Y-m-d H:i:s.u";
$output = "[%datetime%] [%channel%] %level_name%: %message% %context% %extra%\n";
$formatter = new LineFormatter($output, $dateFormat, true, true);

/**
 * Create a logger with proper handlers, formatters, and processors
 */
function createLogger(string $channel, string $logFile, int $level = Logger::DEBUG): Logger {
    global $formatter;
    
    $logger = new Logger($channel);
    
    // Add file handler with rotation (14 days retention)
    $fileHandler = new RotatingFileHandler($logFile, 14, $level);
    $fileHandler->setFormatter($formatter);
    $logger->pushHandler($fileHandler);
    
    // Add stderr handler for immediate visibility during development
    if ($_ENV['APP_ENV'] ?? 'development' !== 'production') {
        $streamHandler = new StreamHandler('php://stderr', $level);
        $streamHandler->setFormatter($formatter);
        $logger->pushHandler($streamHandler);
    }
    
    // Add processors for additional context
    $logger->pushProcessor(new WebProcessor());
    $logger->pushProcessor(new IntrospectionProcessor());
    $logger->pushProcessor(new MemoryUsageProcessor());
    
    return $logger;
}

// ✅ Log Categories (Complete set for all application components)
$logCategories = [
    'application',  // General application logs
    'auth',         // Authentication and authorization
    'user',         // User-related operations
    'db',           // Database operations
    'api',          // API requests and responses
    'security',     // Security events and warnings
    'system',       // System-level events
    'audit',        // Security audit trail
    'dependencies', // Service dependencies
    'payment',      // Payment processing
    'booking',      // Booking operations
    'file',         // File operations
    'admin',        // Admin operations
    'metrics',      // Performance metrics
    'report',       // Report generation
    'revenue',      // Revenue tracking
    'notification', // Notification services
    'document',     // Document management
    'dashboard',    // Dashboard operations
    'encryption',   // Encryption operations
    'cache',        // Cache operations
    'session',      // Session management
    'validation',   // Data validation
];

// Initialize all loggers
$loggers = [];
$fallbackCreated = false;

foreach ($logCategories as $category) {
    $logFile = "{$logDir}/{$category}.log";
    
    // Ensure log file exists and is writable
    try {
        if (!file_exists($logFile)) {
            touch($logFile);
            chmod($logFile, 0664);
        }
        
        if (!is_writable($logFile)) {
            throw new \Exception("Log file not writable: {$logFile}");
        }
        
        // Create the logger with all proper handlers
        $loggers[$category] = createLogger($category, $logFile);
        
    } catch (\Exception $e) {
        // Create a fallback logger if we haven't already
        if (!$fallbackCreated) {
            $fallbackLogger = new Logger('fallback');
            $fallbackHandler = new StreamHandler('php://stderr', Logger::WARNING);
            $fallbackHandler->setFormatter($formatter);
            $fallbackLogger->pushHandler($fallbackHandler);
            $fallbackCreated = true;
        }
        
        error_log("❌ [LOGGER] Failed to initialize logger for {$category}: " . $e->getMessage());
        $loggers[$category] = $fallbackLogger;
    }
}

// Set the default application logger
$logger = $loggers['application'];
$logger->info("Logger system initialized with " . count($logCategories) . " categories");

/**
 * Helper function to retrieve a specific logger
 */
function getLogger(string $category = 'application'): LoggerInterface {
    global $loggers;
    if (!isset($loggers[$category])) {
        error_log("❌ [LOGGER] Requested logger for category '{$category}' not found, using fallback");
        return $loggers['application'] ?? new Logger('fallback');
    }
    return $loggers[$category];
}

/**
 * Helper function to retrieve the default logger
 */
function getDefaultLogger(): LoggerInterface {
    global $logger;
    return $logger;
}

// Make loggers globally available
$GLOBALS['logger'] = $logger;
$GLOBALS['loggers'] = $loggers;

// Return the default logger
return $logger;

