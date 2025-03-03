<?php

/**
 * Logger Configuration
 *
 * This file initializes Monolog as the application-wide logger,
 * ensuring all services use a single logging instance.
 */
require_once __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerInterface;

/**
 * Create a fallback logger in case the main logging system fails
 */
if (!function_exists('getLogger')) {
    function getLogger($name = 'system', $level = Logger::INFO) {
        $logger = new Logger($name);
        $handler = new StreamHandler('php://stderr', $level);
        $formatter = new LineFormatter(
            "[%datetime%] [%channel%] %level_name%: %message%\n",
            "Y-m-d H:i:s"
        );
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);
        return $logger;
    }
}

// Initialize a global logger for early bootstrap operations
$logger = getLogger('bootstrap');

// ✅ Define Log Directory
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    if (!mkdir($logDir, 0775, true)) {
        error_log("❌ [LOGGER] Failed to create logs directory: {$logDir}");
        die("❌ Logger initialization failed: Could not create log directory.\n");
    }
}

// ✅ Log Categories (Separated Logs for Different Services)
$logFiles = [
    'application' => 'application.log',
    'auth'        => 'auth.log',
    'db'          => 'db.log',
    'api'         => 'api.log',
    'security'    => 'security.log',
    'system'      => 'system.log',
    'audit'       => 'audit.log',    // Added for audit logging
    'dependencies' => 'dependencies.log' // Added for dependency tracking
];

$loggers = [];

foreach ($logFiles as $category => $fileName) {
    $logFile = "{$logDir}/{$fileName}";

    if (!file_exists($logFile)) {
        if (!touch($logFile)) {
            error_log("❌ [LOGGER] Failed to create log file: {$logFile}");
            continue;
        }
        chmod($logFile, 0664);
    }

    try {
        $logger = new Logger($category);
        $streamHandler = new StreamHandler($logFile, Logger::DEBUG);

        // ✅ JSON Formatting for Structured Logs
        $formatter = new LineFormatter(
            "[%datetime%] [%channel%] %level_name%: %message%\n",
            "Y-m-d H:i:s",
            true,
            true
        );

        $streamHandler->setFormatter($formatter);
        $logger->pushHandler($streamHandler);

        $loggers[$category] = $logger;
    } catch (Exception $e) {
        error_log("❌ [LOGGER] Failed to initialize logger for {$category}: " . $e->getMessage());
    }
}

// ✅ Function to Retrieve Logger by Category
function getLogger(string $category = 'application'): LoggerInterface
{
    global $loggers;
    if (!isset($loggers[$category])) {
        error_log("❌ [LOGGER] Logger for category '{$category}' not initialized.");
        return new Logger('fallback');
    }
    return $loggers[$category];
}

// ✅ Function to Retrieve Default Logger
function getDefaultLogger(): LoggerInterface
{
    return getLogger('application');
}

// ✅ Return Default Logger (Ensuring Availability)
return getDefaultLogger();

