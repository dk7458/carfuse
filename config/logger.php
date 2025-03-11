<?php
/**
 * Logger configuration
 * This file provides helper functions for working with the pre-initialized loggers
 */

// Access the global loggers already created in root logger.php
global $logger, $loggers;

/**
 * Get a logger by category, falls back to main logger if category doesn't exist
 *
 * @param string $category The logger category to retrieve
 * @return \Psr\Log\LoggerInterface The requested logger
 */
function getLoggerByCategory(string $category = 'application'): \Psr\Log\LoggerInterface {
    global $loggers, $logger;
    
    if (!isset($loggers[$category])) {
        // Log warning about missing category but return fallback
        if (isset($logger)) {
            $logger->warning("Requested logger for category '{$category}' not found, using fallback");
        }
        return $logger;
    }
    
    return $loggers[$category];
}

/**
 * Get a collection of loggers by their categories
 *
 * @param array $categories List of logger categories to retrieve
 * @return array Array of logger instances indexed by category
 */
function getLoggers(array $categories = []): array {
    global $loggers;
    
    if (empty($categories)) {
        return $loggers;
    }
    
    $result = [];
    foreach ($categories as $category) {
        $result[$category] = getLoggerByCategory($category);
    }
    
    return $result;
}

/**
 * Get available logger categories
 *
 * @return array List of available logger categories
 */
function getLoggerCategories(): array {
    global $loggers;
    return array_keys($loggers);
}

/**
 * Register a new custom logger in the global loggers array
 *
 * @param string $category The category name for the logger
 * @param \Psr\Log\LoggerInterface $logger The logger instance
 * @return bool Success status
 */
function registerCustomLogger(string $category, \Psr\Log\LoggerInterface $logger): bool {
    global $loggers;
    
    if (isset($loggers[$category])) {
        // Don't overwrite existing loggers
        return false;
    }
    
    $loggers[$category] = $logger;
    return true;
}

/**
 * Create a child logger with a specific context
 *
 * @param string $category The parent logger category
 * @param string $context The context to add to the logger name
 * @return \Psr\Log\LoggerInterface The child logger
 */
function createContextLogger(string $category, string $context): \Psr\Log\LoggerInterface {
    $parentLogger = getLoggerByCategory($category);
    
    if ($parentLogger instanceof \Monolog\Logger) {
        return $parentLogger->withName("{$category}.{$context}");
    }
    
    return $parentLogger; // Return original if not Monolog
}

// Return functions for importing
return [
    'getLogger' => 'getLoggerByCategory',
    'getLoggers' => 'getLoggers',
    'getLoggerCategories' => 'getLoggerCategories',
    'registerCustomLogger' => 'registerCustomLogger',
    'createContextLogger' => 'createContextLogger'
];
