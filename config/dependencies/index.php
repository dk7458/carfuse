<?php

/**
 * Dependencies Index File
 * 
 * This file initializes the dependency container and loads all modular 
 * dependency component files in the correct order.
 */

use DI\ContainerBuilder;

// Initialize the container builder
$containerBuilder = new ContainerBuilder();

// Define a basic array to hold our definitions
$definitions = [];

// First, manually load the logging dependencies to ensure logger is available
$loggingFile = __DIR__ . '/logging_dependencies.php';
if (file_exists($loggingFile)) {
    $loggingDefinitions = require $loggingFile; // Changed from require_once to require
    if (is_array($loggingDefinitions)) {
        $definitions = array_merge($definitions, $loggingDefinitions);
        $containerBuilder->addDefinitions($loggingDefinitions);
    } else {
        error_log("Warning: Logging definitions file did not return an array");
    }
    
    // Build a temporary container just for the logger
    try {
        $tempContainer = $containerBuilder->build();
        $dependenciesLogger = null;
        
        try {
            $dependenciesLogger = $tempContainer->get('dependencies_logger');
        } catch (Exception $e) {
            // If logger initialization fails, create a simple error logger to stderr
            error_log("Warning: Could not initialize dependencies_logger - " . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log("Failed to build temporary container: " . $e->getMessage());
    }
} else {
    error_log("Warning: Logging dependencies file not found at {$loggingFile}, creating an empty file");
    
    // Create the directory if it doesn't exist
    $dir = dirname($loggingFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    
    // Create a basic logging dependencies file
    $basicLoggerContent = <<<'PHP'
<?php
/**
 * Basic Logging Dependencies Configuration
 */
use Psr\Log\LoggerInterface;

class BasicLogger implements LoggerInterface {
    public function emergency($message, array $context = []) { error_log("EMERGENCY: {$message}"); }
    public function alert($message, array $context = []) { error_log("ALERT: {$message}"); }
    public function critical($message, array $context = []) { error_log("CRITICAL: {$message}"); }
    public function error($message, array $context = []) { error_log("ERROR: {$message}"); }
    public function warning($message, array $context = []) { error_log("WARNING: {$message}"); }
    public function notice($message, array $context = []) { error_log("NOTICE: {$message}"); }
    public function info($message, array $context = []) { error_log("INFO: {$message}"); }
    public function debug($message, array $context = []) { error_log("DEBUG: {$message}"); }
    public function log($level, $message, array $context = []) { error_log("{$level}: {$message}"); }
}

return [
    LoggerInterface::class => function() { return new BasicLogger(); },
    'dependencies_logger' => function() { return new BasicLogger(); }
];
PHP;
    
    file_put_contents($loggingFile, $basicLoggerContent);
    $loggingDefinitions = require $loggingFile;
    $definitions = array_merge($definitions, $loggingDefinitions);
    $containerBuilder->addDefinitions($loggingDefinitions);
}

// Get all PHP files in the dependencies directory except index.php and logging_dependencies.php (already loaded)
$dependencyFiles = glob(__DIR__ . '/*.php');
if ($dependencyFiles === false) {
    error_log("Failed to get dependency files from directory: " . __DIR__);
    $dependencyFiles = [];
}

$dependencyFiles = array_filter($dependencyFiles, function($file) {
    $basename = basename($file);
    return $basename !== 'index.php' && $basename !== 'logging_dependencies.php';
});

// Sort files to ensure consistent loading order
sort($dependencyFiles);

// Load each dependency file and collect definitions
foreach ($dependencyFiles as $file) {
    try {
        $fileDefinitions = require $file; // Changed from require_once to require
        if (is_array($fileDefinitions)) {
            $definitions = array_merge($definitions, $fileDefinitions);
        } else {
            error_log("Warning: Dependency file {$file} did not return an array");
        }
        
        if (isset($dependenciesLogger)) {
            $dependenciesLogger->info("Loaded dependency file: " . basename($file));
        }
    } catch (Exception $e) {
        $errorMsg = "Error loading dependency file {$file}: " . $e->getMessage();
        
        if (isset($dependenciesLogger)) {
            $dependenciesLogger->error($errorMsg);
        } else {
            error_log($errorMsg);
        }
    }
}

// Add all collected definitions to the container
$containerBuilder->addDefinitions($definitions);

// Build the container
try {
    $container = $containerBuilder->build();
    
    if (isset($dependenciesLogger)) {
        $dependenciesLogger->info("DI container successfully built with " . count($definitions) . " definitions");
    }
    
    return [
        'container' => $container,
        'definitions' => $definitions,
    ];
} catch (Exception $e) {
    $errorMsg = "Failed to build DI container: " . $e->getMessage();
    
    if (isset($dependenciesLogger)) {
        $dependenciesLogger->error($errorMsg);
    } else {
        error_log($errorMsg);
    }
    
    // Instead of re-throwing, return a minimal result that includes the error
    return [
        'container' => null,
        'definitions' => $definitions,
        'error' => $e->getMessage()
    ];
}
